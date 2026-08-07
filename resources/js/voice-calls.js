import AgoraRTC from 'agora-rtc-sdk-ng';

const voiceConfig = window.PyroSenseVoiceCall || {};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const role = String(voiceConfig.role || '').toLowerCase();
const isResponder = ['station admin', 'chief', 'chief station admin'].includes(role);
const isCitizen = role === 'citizen';

let incomingPollTimer = null;
let callerPollTimer = null;
let durationTimer = null;
let activeCall = null;
let activeAgora = null;
let agoraClient = null;
let localAudioTrack = null;
let isMuted = false;
let joinedCallId = null;
let ringtoneState = null;
let knownIncomingIds = new Set();
let knownCallsById = new Map();
let incomingPollFailureCount = 0;
let activeCallerTrigger = null;
let callerVoicePrimeAttempted = false;
let callerHasMicrophoneAccess = false;
let agoraJoinState = 'idle';
let incomingPollInFlight = null;

const routes = voiceConfig.routes || {};
const pollMs = Number(voiceConfig.pollIntervalMs || 2000);
const AGORA_JOIN_TIMEOUT_MS = 15000;
const REQUEST_TIMEOUT_MS = 12000;

function routeFor(name, callId = '') {
    return String(routes[name] || '').replace('__CALL__', callId);
}

function ensureToastStack() {
    let stack = document.querySelector('[data-pyro-voice-toast-stack]');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'pyro-voice-toast-stack';
        stack.dataset.pyroVoiceToastStack = 'true';
        document.body.appendChild(stack);
    }
    return stack;
}

function toast(message, type = 'info') {
    const stack = ensureToastStack();
    const item = document.createElement('div');
    item.className = `pyro-voice-toast pyro-voice-toast--${type}`;
    item.textContent = message;
    stack.appendChild(item);
    setTimeout(() => item.remove(), 4200);
}

function setCallerTriggerState(trigger, state = 'idle') {
    if (!trigger) {
        return;
    }

    const labelNode = trigger.querySelector('.mobile-hub-btn__copy strong');
    const originalLabel = trigger.dataset.originalVoiceLabel
        || labelNode?.textContent
        || trigger.textContent?.trim()
        || 'Call BFP';

    trigger.dataset.originalVoiceLabel = originalLabel;
    trigger.classList.toggle('is-loading', state === 'calling');
    trigger.classList.toggle('is-active-call', state === 'connected' || state === 'ringing');
    trigger.setAttribute('aria-busy', state === 'calling' ? 'true' : 'false');
    trigger.disabled = state === 'calling' || state === 'connected' || state === 'ringing';

    if (labelNode) {
        if (state === 'calling') {
            labelNode.textContent = 'Calling BFP...';
        } else if (state === 'connected' || state === 'ringing') {
            labelNode.textContent = 'BFP Call Active';
        } else {
            labelNode.textContent = originalLabel;
        }
    }
}

function setAgoraJoinState(state = 'idle') {
    agoraJoinState = state;

    if (activeCall && isCitizen) {
        renderCallerDock(activeCall);
    }
}

async function jsonFetch(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), options.timeoutMs || REQUEST_TIMEOUT_MS);
    let response;

    try {
        response = await fetch(url, {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {}),
            },
            body: options.body ? JSON.stringify(options.body) : undefined,
            signal: controller.signal,
        });
    } catch (error) {
        clearTimeout(timer);
        if (error?.name === 'AbortError') {
            throw new Error(options.timeoutMessage || 'Request timed out. Please try again.');
        }
        throw error;
    }

    clearTimeout(timer);

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(payload.message || `Request failed with status ${response.status}.`);
    }
    return payload;
}

function withTimeout(promise, timeoutMs, message) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error(message)), timeoutMs);
        promise
            .then((value) => {
                clearTimeout(timer);
                resolve(value);
            })
            .catch((error) => {
                clearTimeout(timer);
                reject(error);
            });
    });
}

async function primeCallerVoiceAccess() {
    if (callerVoicePrimeAttempted || !isCitizen || !navigator.mediaDevices?.getUserMedia) {
        return;
    }

    callerVoicePrimeAttempted = true;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        callerHasMicrophoneAccess = true;
        stream.getTracks().forEach((track) => track.stop());
    } catch (error) {
        callerHasMicrophoneAccess = false;
        console.warn('Caller microphone priming failed:', error);
    }
}

function initials(name) {
    return String(name || 'Citizen')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('') || 'C';
}

function formatSeconds(seconds) {
    const safe = Math.max(0, Number(seconds || 0));
    const minutes = Math.floor(safe / 60).toString().padStart(2, '0');
    const remainder = Math.floor(safe % 60).toString().padStart(2, '0');
    return `${minutes}:${remainder}`;
}

function callAge(call) {
    const started = call?.answered_at || call?.created_at;
    if (!started) return call?.ringing_seconds || 0;
    return Math.max(0, Math.floor((Date.now() - new Date(started).getTime()) / 1000));
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function finiteNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function normalizeLocation(source) {
    if (!source || typeof source !== 'object') return null;
    const lat = finiteNumber(source.lat ?? source.latitude ?? source.currentLatitude);
    const lng = finiteNumber(source.lng ?? source.lon ?? source.longitude ?? source.currentLongitude);
    if (lat === null || lng === null) return null;

    return {
        caller_latitude: lat,
        caller_longitude: lng,
        caller_accuracy: Math.round(finiteNumber(source.accuracy ?? source.radiusMeters) || 0) || null,
        caller_location_label: source.label || source.address || source.display || null,
    };
}

function currentCallerLocationPayload() {
    const globals = [
        window.userCoords,
        window.PyroSenseCurrentLocation,
        window.currentUserLocation,
        window.citizenLocation,
        window.currentLocation,
    ];

    for (const source of globals) {
        const normalized = normalizeLocation(source);
        if (normalized) return normalized;
    }

    for (const key of ['pyrosense:user-location', 'pyrosense:current-location', 'citizen:user-location', 'citizen:current-location']) {
        try {
            const normalized = normalizeLocation(JSON.parse(localStorage.getItem(key) || 'null'));
            if (normalized) return normalized;
        } catch {}
    }

    try {
        const rawLastKnownCoords = JSON.parse(localStorage.getItem('pyrosense:lastKnownCoords') || 'null');
        const normalizedLastKnownCoords = normalizeLocation(rawLastKnownCoords);
        if (normalizedLastKnownCoords) {
            return normalizedLastKnownCoords;
        }
    } catch {}

    return {};
}

function callLocation(call) {
    return call?.caller_location || {};
}

function candidateMaps() {
    return [
        window.map,
        window.userMap,
        window.adminMap,
        window.chiefMap,
        window.stationAdminMap,
        window.pyroMap,
        window.dashboardMap,
    ].filter((map) => map && typeof map.setView === 'function');
}

function viewCallerLocation(call) {
    const location = callLocation(call);
    if (!location.has_location) {
        toast('Caller location is unavailable.', 'error');
        return;
    }

    const latLng = [Number(location.lat), Number(location.lng)];
    const targetMap = candidateMaps()[0];
    if (targetMap) {
        targetMap.setView(latLng, Math.max(targetMap.getZoom?.() || 15, 16), { animate: true });
    }

    window.dispatchEvent(new CustomEvent('pyro:voice-caller-location', { detail: { call, latLng } }));
    toast('Caller location centered.');
}

function refreshIncidentMarkersFromVoice(payload) {
    window.dispatchEvent(new CustomEvent('pyro:voice-call-incident-created', { detail: payload }));

    const refresh = window.refreshMarkers || window.loadMarkers || window.refreshMapMarkers || window.reloadMapMarkers;
    if (typeof refresh === 'function') {
        refresh();
    }
}

function ensureIncomingPanel() {
    let panel = document.querySelector('[data-pyro-voice-panel]');
    if (panel) return panel;

    panel = document.createElement('section');
    panel.className = 'pyro-voice-panel';
    panel.dataset.pyroVoicePanel = 'true';
    panel.hidden = true;
    panel.innerHTML = `
        <div class="pyro-voice-panel__header">
            <div>
                <p class="pyro-voice-panel__eyebrow">Emergency voice</p>
                <h2 class="pyro-voice-panel__title">Incoming calls</h2>
            </div>
            <span class="pyro-voice-badge" data-pyro-voice-count>0</span>
        </div>
        <div class="pyro-voice-panel__body" data-pyro-voice-list></div>
    `;
    document.body.appendChild(panel);
    panel.addEventListener('click', handleIncomingClick);
    return panel;
}

function ensureCallerDock() {
    let dock = document.querySelector('[data-pyro-voice-caller-dock]');
    if (dock) return dock;

    dock = document.createElement('section');
    dock.className = 'pyro-voice-caller-dock';
    dock.dataset.pyroVoiceCallerDock = 'true';
    dock.hidden = true;
    dock.innerHTML = `
        <div class="pyro-voice-caller-dock__header">
            <div>
                <p class="pyro-voice-caller-dock__eyebrow">Emergency voice</p>
                <h2 class="pyro-voice-caller-dock__title" data-pyro-voice-caller-title>Calling station</h2>
            </div>
            <span class="pyro-voice-badge" data-pyro-voice-caller-status>Calling</span>
        </div>
        <div class="pyro-voice-caller-dock__body">
            <p class="pyro-voice-status-line" data-pyro-voice-caller-line>Connecting to the station...</p>
            <div class="pyro-voice-controls">
                <button type="button" class="pyro-voice-btn" data-pyro-voice-mute disabled>Mute</button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-end>End</button>
            </div>
        </div>
    `;
    document.body.appendChild(dock);
    dock.addEventListener('click', handleCallerDockClick);
    return dock;
}

function renderCallCard(call, active = false) {
    const callerName = call?.caller?.name || 'Anonymous caller';
    const location = callLocation(call);
    const locationText = location.has_location ? location.display : 'Location unavailable';
    const state = active ? 'Active' : call.status;
    const hasIncident = Boolean(call.created_incident_id || call.incident_id);
    const flaggedClass = call.is_flagged_prank ? 'pyro-voice-call-card--flagged' : '';
    return `
        <article class="pyro-voice-call-card ${active ? 'pyro-voice-call-card--active' : ''} ${flaggedClass}" data-call-id="${call.id}">
            <div class="pyro-voice-call-card__top">
                <div class="pyro-voice-avatar">${escapeHtml(initials(callerName))}</div>
                <div>
                    <p class="pyro-voice-call-card__name">${escapeHtml(callerName)}</p>
                    <p class="pyro-voice-call-card__meta">${escapeHtml(call.caller?.contact_number || call.caller?.email || 'Citizen')} • ${formatSeconds(callAge(call))}</p>
                </div>
                <span class="pyro-voice-badge">${escapeHtml(state)}</span>
            </div>
            <div class="pyro-voice-call-card__location">
                <span class="pyro-voice-call-card__location-icon" aria-hidden="true">⌖</span>
                <span>${escapeHtml(locationText)}</span>
            </div>
            <div class="pyro-voice-actions">
                ${active ? `
                    <button type="button" class="pyro-voice-btn" data-pyro-voice-mute>${isMuted ? 'Unmute' : 'Mute'}</button>
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-end>End</button>
                ` : `
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--primary" data-pyro-voice-accept ${call.can_accept ? '' : 'disabled'}>Accept</button>
                    <button type="button" class="pyro-voice-btn pyro-voice-btn--danger" data-pyro-voice-reject>Reject</button>
                `}
            </div>
            <div class="pyro-voice-actions pyro-voice-actions--triage">
                <button type="button" class="pyro-voice-btn pyro-voice-btn--success" data-pyro-voice-create-incident ${hasIncident || call.is_flagged_prank || !location.has_location ? 'disabled' : ''}>
                    ${hasIncident ? 'Incident created' : 'Create Incident from Call'}
                </button>
                <button type="button" class="pyro-voice-btn" data-pyro-voice-view-location ${location.has_location ? '' : 'disabled'}>View caller location</button>
                <button type="button" class="pyro-voice-btn pyro-voice-btn--warning" data-pyro-voice-flag ${call.is_flagged_prank || hasIncident ? 'disabled' : ''}>Flag as prank call</button>
            </div>
        </article>
    `;
}

function renderIncoming(payload) {
    const panel = ensureIncomingPanel();
    const list = panel.querySelector('[data-pyro-voice-list]');
    const count = panel.querySelector('[data-pyro-voice-count]');
    const calls = payload.incoming_calls || [];
    const total = calls.length + (payload.active_call ? 1 : 0);

    panel.hidden = total === 0;
    count.textContent = String(total);

    if (!total) {
        stopRinging();
        knownIncomingIds = new Set();
        knownCallsById = new Map();
        return;
    }

    knownCallsById = new Map([
        ...(payload.active_call ? [[payload.active_call.id, payload.active_call]] : []),
        ...calls.map((call) => [call.id, call]),
    ]);

    const newIds = calls.map((call) => call.id).filter((id) => !knownIncomingIds.has(id));
    knownIncomingIds = new Set(calls.map((call) => call.id));
    if (newIds.length) {
        startRinging();
        notifyIncoming(calls.find((call) => call.id === newIds[0]));
    }

    list.innerHTML = [
        payload.active_call ? renderCallCard(payload.active_call, true) : '',
        ...calls.map((call) => renderCallCard(call)),
    ].join('');
}

async function pollIncoming() {
    if (!routes.incoming) return;

    if (incomingPollInFlight) {
        return incomingPollInFlight;
    }

    incomingPollInFlight = (async () => {
        try {
            const payload = await jsonFetch(routes.incoming);
            incomingPollFailureCount = 0;
            renderIncoming(payload);
        } catch (error) {
            incomingPollFailureCount += 1;
            if (incomingPollFailureCount === 1 || incomingPollFailureCount % 5 === 0) {
                console.warn('Voice call incoming poll failed:', error);
            }
        } finally {
            incomingPollInFlight = null;
        }
    })();

    try {
        await incomingPollInFlight;
    } finally {
        return incomingPollInFlight;
    }
}

function clearIncomingPollTimer() {
    if (incomingPollTimer) {
        clearTimeout(incomingPollTimer);
        incomingPollTimer = null;
    }
}

function getIncomingPollDelay() {
    if (isResponder && document.visibilityState === 'visible') {
        return pollMs;
    }

    const visibilityDelay = Math.max(8000, pollMs * 3);
    const backoffDelay = pollMs * Math.max(1, Math.min(6, 2 ** Math.min(incomingPollFailureCount, 3)));
    return Math.max(backoffDelay, visibilityDelay);
}

function scheduleIncomingPoll(delay = getIncomingPollDelay()) {
    clearIncomingPollTimer();
    incomingPollTimer = setTimeout(async () => {
        await pollIncoming();
        scheduleIncomingPoll();
    }, delay);
}

function getResponderVoiceBootDelay() {
    const path = String(window.location?.pathname || '');
    if (path.startsWith('/station-admin/')) {
        return 250;
    }
    return 0;
}

async function handleIncomingClick(event) {
    const card = event.target.closest('[data-call-id]');
    if (!card) return;
    const callId = card.dataset.callId;
    const call = knownCallsById.get(Number(callId)) || knownCallsById.get(callId);

    if (event.target.closest('[data-pyro-voice-view-location]')) {
        viewCallerLocation(call);
        return;
    }

    if (event.target.closest('[data-pyro-voice-create-incident]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const originalText = button.textContent;
        button.textContent = 'Creating...';
        try {
            const payload = await jsonFetch(routeFor('createIncident', callId), { method: 'POST' });
            refreshIncidentMarkersFromVoice(payload);
            toast(payload.message || 'Incident marker created.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to create incident from call.', 'error');
            button.disabled = false;
            button.textContent = originalText;
        } finally {
            button.removeAttribute('aria-busy');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-flag]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const originalText = button.textContent;
        button.textContent = 'Flagging...';
        try {
            const payload = await jsonFetch(routeFor('flagPrank', callId), {
                method: 'POST',
                body: { reason: 'Flagged from incoming call panel' },
            });
            toast(payload.message || 'Call flagged as prank.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to flag call.', 'error');
            button.disabled = false;
            button.textContent = originalText;
        } finally {
            button.removeAttribute('aria-busy');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-accept]')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.textContent = 'Accepting...';
        try {
            const payload = await jsonFetch(routeFor('accept', callId), {
                method: 'POST',
                timeoutMs: 10000,
                timeoutMessage: 'Accept request took too long. Please try again.',
            });
            activeCall = payload.call;
            activeAgora = payload.agora;
            stopRinging();
            renderIncoming({
                active_call: activeCall,
                incoming_calls: [],
            });
            try {
                await withTimeout(
                    joinAgora(activeCall, activeAgora),
                    AGORA_JOIN_TIMEOUT_MS,
                    'Voice connection took too long. Please try accepting again.'
                );
                toast('Voice call connected.');
            } catch (joinError) {
                setAgoraJoinState('failed');
                console.warn('Voice call accepted but Agora join failed:', joinError);
                toast(joinError.message || 'Call accepted, but voice connection is still trying to connect.', 'error');
            }
        } catch (error) {
            toast(error.message || 'Unable to accept call.', 'error');
        } finally {
            await pollIncoming();
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-reject]')) {
        try {
            await jsonFetch(routeFor('reject', callId), { method: 'POST' });
            toast('Call rejected.');
            await pollIncoming();
        } catch (error) {
            toast(error.message || 'Unable to reject call.', 'error');
        }
        return;
    }

    if (event.target.closest('[data-pyro-voice-end]')) {
        await endCurrentCall(callId);
        await pollIncoming();
        return;
    }

    if (event.target.closest('[data-pyro-voice-mute]')) {
        await toggleMute();
        await pollIncoming();
    }
}

function renderCallerDock(call) {
    const dock = ensureCallerDock();
    const status = call.status === 'ringing'
        ? 'Calling'
        : (call.status === 'answered' && joinedCallId !== call.id
            ? 'Connecting'
            : call.status);
    dock.hidden = false;
    dock.querySelector('[data-pyro-voice-caller-status]').textContent = status;
    dock.querySelector('[data-pyro-voice-caller-title]').textContent = call.station?.name || 'Calling station';
    dock.querySelector('[data-pyro-voice-caller-line]').textContent = callerStatusText(call);
    dock.querySelector('[data-pyro-voice-mute]').disabled = call.status !== 'answered' || !localAudioTrack;
    dock.querySelector('[data-pyro-voice-mute]').textContent = isMuted ? 'Unmute' : 'Mute';
}

function callerStatusText(call) {
    if (call.status === 'ringing') return `Ringing for ${formatSeconds(callAge(call))}`;
    if (call.status === 'queued') return 'Queued. A station admin will answer after the active call.';
    if (call.status === 'answered' && joinedCallId !== call.id) return 'Answered. Connecting voice channel...';
    if (call.status === 'answered') return `Connected • ${formatSeconds(callAge(call))}`;
    if (call.status === 'rejected') return 'The station rejected this call.';
    if (call.status === 'missed') return 'No station admin answered in time.';
    if (call.status === 'ended') return 'Call ended.';
    return 'Connecting...';
}

async function startCitizenCall(event) {
    if (!voiceConfig.configured || !routes.store) return;
    event.preventDefault();

    const trigger = event.currentTarget;
    activeCallerTrigger = trigger;
    setCallerTriggerState(trigger, 'calling');
    toast('Calling station...');
    await primeCallerVoiceAccess();

    try {
        const payload = await jsonFetch(routes.store, {
            method: 'POST',
            body: {
                station_id: trigger.dataset.voiceStationId || null,
                incident_id: trigger.dataset.voiceIncidentId || null,
                ...currentCallerLocationPayload(),
            },
        });
        activeCall = payload.call;
        renderCallerDock(activeCall);
        startDurationTicker();
        startCallerPolling(activeCall.id);
    } catch (error) {
        setCallerTriggerState(trigger, 'idle');
        toast(error.message || 'Unable to start voice call.', 'error');
    }
}

function startCallerPolling(callId) {
    clearInterval(callerPollTimer);
    pollCaller(callId);
    callerPollTimer = setInterval(() => pollCaller(callId), pollMs);
}

async function pollCaller(callId) {
    try {
        const payload = await jsonFetch(routeFor('show', callId));
        activeCall = payload.call;
        renderCallerDock(activeCall);

        if (activeCallerTrigger) {
            if (activeCall.status === 'answered') {
                setCallerTriggerState(activeCallerTrigger, 'connected');
            } else if (['ringing', 'queued'].includes(activeCall.status)) {
                setCallerTriggerState(activeCallerTrigger, 'ringing');
            }
        }

        if (activeCall.status === 'answered' && joinedCallId !== activeCall.id) {
            setAgoraJoinState('connecting');
            const tokenPayload = await jsonFetch(routeFor('token', activeCall.id), { method: 'POST' });
            await withTimeout(
                joinAgora(activeCall, tokenPayload.agora),
                AGORA_JOIN_TIMEOUT_MS,
                'Voice connection took too long.'
            );
            toast('Station answered. Voice connected.');
        }

        if (['rejected', 'missed', 'ended'].includes(activeCall.status)) {
            clearInterval(callerPollTimer);
            await leaveAgora();
            if (activeCallerTrigger) {
                setCallerTriggerState(activeCallerTrigger, 'idle');
                activeCallerTrigger = null;
            }
            setTimeout(() => {
                const dock = ensureCallerDock();
                dock.hidden = true;
            }, 3500);
        }
    } catch (error) {
        console.warn('Voice call status poll failed:', error);
    }
}

async function handleCallerDockClick(event) {
    if (event.target.closest('[data-pyro-voice-end]') && activeCall?.id) {
        await endCurrentCall(activeCall.id);
    }
    if (event.target.closest('[data-pyro-voice-mute]')) {
        await toggleMute();
        if (activeCall) renderCallerDock(activeCall);
    }
}

async function joinAgora(call, agora) {
    if (!agora?.app_id || !agora?.token) {
        setAgoraJoinState('failed');
        throw new Error('Voice token is unavailable.');
    }
    if (joinedCallId === call.id) return;

    setAgoraJoinState('connecting');
    await leaveAgora();
    agoraClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
    activeAgora = agora;

    agoraClient.on('user-published', async (remoteUser, mediaType) => {
        await agoraClient.subscribe(remoteUser, mediaType);
        if (mediaType === 'audio') remoteUser.audioTrack?.play();
    });
    agoraClient.on('connection-state-change', (currentState) => {
        if (currentState === 'RECONNECTING') toast('Voice reconnecting...');
        if (currentState === 'CONNECTED' && joinedCallId) toast('Voice connected.');
    });
    agoraClient.on('token-privilege-will-expire', renewAgoraToken);
    agoraClient.on('token-privilege-did-expire', renewAgoraToken);

    await agoraClient.join(agora.app_id, agora.channel_name, agora.token, agora.uid);
    try {
        localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        await agoraClient.publish([localAudioTrack]);
        callerHasMicrophoneAccess = true;
    } catch (error) {
        localAudioTrack = null;
        callerHasMicrophoneAccess = false;
        console.warn('Voice call joined without microphone publishing:', error);
        toast('Connected without microphone access. Enable mic permissions to speak.', 'error');
    }

    joinedCallId = call.id;
    isMuted = false;
    setAgoraJoinState('connected');
    startDurationTicker();
}

async function renewAgoraToken() {
    if (!activeCall?.id || !agoraClient) return;
    try {
        const payload = await jsonFetch(routeFor('token', activeCall.id), { method: 'POST' });
        activeAgora = payload.agora;
        await agoraClient.renewToken(payload.agora.token);
    } catch (error) {
        toast('Voice token renewal failed. Reconnecting may be interrupted.', 'error');
    }
}

async function toggleMute() {
    if (!localAudioTrack) {
        toast('Microphone is not available for this call.', 'error');
        return;
    }
    isMuted = !isMuted;
    await localAudioTrack.setEnabled(!isMuted);
    toast(isMuted ? 'Microphone muted.' : 'Microphone active.');
}

async function endCurrentCall(callId) {
    const effectiveCallId = callId || activeCall?.id || joinedCallId;
    if (!effectiveCallId) {
        toast('No active call to end.', 'error');
        return;
    }

    try {
        await jsonFetch(routeFor('end', effectiveCallId), { method: 'POST' });
        toast('Call ended.');
    } catch (error) {
        toast(error.message || 'Unable to end call.', 'error');
    } finally {
        await leaveAgora();
        clearInterval(callerPollTimer);
        activeCall = null;
        if (activeCallerTrigger) {
            setCallerTriggerState(activeCallerTrigger, 'idle');
            activeCallerTrigger = null;
        }
        const dock = document.querySelector('[data-pyro-voice-caller-dock]');
        if (dock) dock.hidden = true;
    }
}

async function leaveAgora() {
    clearInterval(durationTimer);
    durationTimer = null;
    if (localAudioTrack) {
        localAudioTrack.stop();
        localAudioTrack.close();
        localAudioTrack = null;
    }
    if (agoraClient) {
        try {
            await agoraClient.leave();
        } catch (error) {
            console.warn('Agora leave failed:', error);
        }
    }
    agoraClient = null;
    joinedCallId = null;
    isMuted = false;
    setAgoraJoinState('idle');
}

function startDurationTicker() {
    clearInterval(durationTimer);
    durationTimer = setInterval(() => {
        if (isCitizen && activeCall) renderCallerDock(activeCall);
    }, 1000);
}

function startRinging() {
    if (ringtoneState?.interval) return;

    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;

        const context = new AudioContext();
        const gain = context.createGain();
        gain.gain.value = 0.0001;
        gain.connect(context.destination);

        const playTone = () => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.value = 880;
            oscillator.connect(gain);
            gain.gain.setValueAtTime(0.0001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.08, context.currentTime + 0.04);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.42);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.46);
        };

        context.resume().then(playTone).catch(() => {});
        ringtoneState = {
            context,
            interval: setInterval(playTone, 1500),
        };
    } catch {}
}

function stopRinging() {
    if (!ringtoneState) return;
    clearInterval(ringtoneState.interval);
    ringtoneState.context?.close?.().catch(() => {});
    ringtoneState = null;
}

function notifyIncoming(call) {
    if (!call || !('Notification' in window) || Notification.permission !== 'granted') return;
    try {
        new Notification('Incoming PyroSense voice call', {
            body: call.caller?.name || 'Anonymous caller',
            tag: `voice-call-${call.id}`,
        });
    } catch {}
}

function requestNotificationPermissionOnGesture() {
    if (!isResponder || !('Notification' in window) || Notification.permission !== 'default') return;
    Notification.requestPermission().catch(() => {});
}

function initVoiceCalls() {
    if (!voiceConfig.configured) {
        console.warn('PyroSense voice calls are disabled because AGORA_APP_ID is missing.');
        return;
    }

    document.querySelectorAll('[data-pyro-voice-call]').forEach((button) => {
        button.addEventListener('click', startCitizenCall);
    });

    if (isResponder) {
        ensureIncomingPanel();
        window.setTimeout(() => {
            pollIncoming().finally(() => {
                scheduleIncomingPoll();
            });
        }, getResponderVoiceBootDelay());
        document.addEventListener('click', requestNotificationPermissionOnGesture, { once: true });
    }

    window.addEventListener('visibilitychange', () => {
        if (!isResponder) {
            return;
        }

        if (document.visibilityState === 'visible') {
            scheduleIncomingPoll(pollMs);
            return;
        }

        scheduleIncomingPoll(getIncomingPollDelay());
    });

    window.addEventListener('pagehide', () => {
        stopRinging();
        clearIncomingPollTimer();
    });
}

document.addEventListener('DOMContentLoaded', initVoiceCalls);
