@php
    $notificationRoutePrefix = $notificationRoutePrefix ?? 'station-admin';
    $commandNotificationUrls = $commandNotificationUrls ?? [
        'index' => route($notificationRoutePrefix.'.command-notifications.index'),
        'read_all' => route($notificationRoutePrefix.'.command-notifications.read-all'),
        'read' => route($notificationRoutePrefix.'.command-notifications.read', ['notificationId' => '__ID__']),
    ];
@endphp

<div data-command-notification-center hidden></div>

<style>
    .command-notification-header { position:relative; display:block; flex:0 0 auto; }
    .command-notification-header__trigger { position:relative; width:40px; height:40px; border:0; border-radius:999px; background:#f1f5f9; color:#475569; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background .18s ease,color .18s ease; }
    .command-notification-header__trigger:hover,.command-notification-header__trigger[aria-expanded="true"] { color:#9a3412; background:#ffedd5; }
    .command-notification-header__trigger svg { width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .command-notification-center__badge { position:absolute; top:-4px; right:-4px; min-width:19px; height:19px; padding:0 4px; border-radius:999px; background:#dc2626; color:#fff; border:2px solid #fff; font:800 9px/15px Inter,sans-serif; text-align:center; }
    .command-notification-center__panel { position:absolute; top:calc(100% + 12px); right:0; z-index:10020; width:min(380px,calc(100vw - 32px)); max-height:min(520px,calc(100vh - 100px)); overflow:hidden; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 24px 60px rgba(15,23,42,.22); }
    .command-notification-center__panel[hidden] { display:none !important; }
    .command-notification-center__panel header { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:15px 16px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff7ed,#fffaf5); }
    .command-notification-center__panel header div { display:flex; flex-direction:column; gap:2px; }
    .command-notification-center__panel header small { color:#9a3412; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .command-notification-center__panel header strong { color:#0f172a; font-size:15px; }
    .command-notification-center__panel header button { border:0; background:transparent; color:#c2410c; font-size:11px; font-weight:800; cursor:pointer; }
    .command-notification-center__actions { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
    .command-notification-center__actions button[data-command-notification-test] { padding:6px 9px; border:1px solid #fed7aa; border-radius:999px; background:#fff; }
    .command-notification-center__list { max-height:390px; overflow-y:auto; }
    .command-notification-center__item { display:block; padding:14px 16px; border-bottom:1px solid #f1f5f9; color:#334155; text-decoration:none; transition:background .18s ease; }
    .command-notification-center__item:hover { background:#f8fafc; }
    .command-notification-center__item.is-unread { border-left:3px solid #f97316; background:#fffaf5; }
    .command-notification-center__item strong,.command-notification-center__item span,.command-notification-center__item small { display:block; }
    .command-notification-center__item strong { color:#0f172a; font-size:13px; }
    .command-notification-center__item span { margin-top:4px; font-size:12px; line-height:1.45; }
    .command-notification-center__item small { margin-top:7px; color:#94a3b8; font-size:10px; }
    .command-notification-center__meta { display:flex !important; align-items:center; justify-content:space-between; gap:8px; }
    .command-notification-center__category { color:#c2410c; font-weight:800; text-transform:capitalize; }
    .command-notification-center__empty { padding:28px 18px; color:#64748b; text-align:center; font-size:12px; }
    .command-notification-mobile-badge { position:absolute; top:3px; right:16%; min-width:17px; height:17px; padding:0 4px; border-radius:999px; background:#dc2626; color:#fff; border:2px solid #fff; font-size:9px; font-weight:900; line-height:13px; text-align:center; }
    #incidentAlertCenterToggle { display:none !important; }
    @media (max-width:767px) { .command-notification-header { display:none !important; } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const center = document.querySelector('[data-command-notification-center]');
        if (!center || center.dataset.ready === '1') return;
        center.dataset.ready = '1';

        const urls = @json($commandNotificationUrls);
        let headerHost = document.querySelector('[data-command-notification-header-host]');
        if (!headerHost) {
            const profileDropdown = document.getElementById('userDropdownBtn')?.closest('.user-dropdown')
                || document.querySelector('.lector-top-bar .user-dropdown, .checklist-topbar .profile-pill, .top-nav .user-dropdown, .portal-top-bar .user-dropdown');
            const headerFallback = document.querySelector('.lector-top-bar, .checklist-topbar, .top-nav-container, .portal-top-bar');
            const insertionParent = profileDropdown?.parentElement || headerFallback;
            if (insertionParent) {
                headerHost = document.createElement('div');
                headerHost.dataset.commandNotificationHeaderHost = '';
                if (profileDropdown) insertionParent.insertBefore(headerHost, profileDropdown);
                else insertionParent.appendChild(headerHost);
            }
        }
        if (headerHost) {
            headerHost.className = 'command-notification-header';
            headerHost.innerHTML = `
                <button type="button" class="command-notification-header__trigger" data-command-notification-trigger aria-label="Open all notifications" aria-expanded="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <span class="command-notification-center__badge" data-command-notification-badge hidden>0</span>
                </button>
                <section class="command-notification-center__panel" data-command-notification-panel hidden>
                    <header><div><small>Notification center</small><strong>All notifications</strong></div><span class="command-notification-center__actions"><button type="button" data-command-notification-test>Enable / test alert</button><button type="button" data-command-notification-read-all>Mark all read</button></span></header>
                    <div class="command-notification-center__list" data-command-notification-list><p class="command-notification-center__empty">Loading notifications…</p></div>
                </section>`;
        }

        // The map alert feed is merged below, so its separate floating bell is
        // redundant on pages that provide this unified notification center.
        document.getElementById('incidentAlertCenterToggle')?.remove();
        const trigger = headerHost?.querySelector('[data-command-notification-trigger]');
        const panel = headerHost?.querySelector('[data-command-notification-panel]');
        const list = headerHost?.querySelector('[data-command-notification-list]');
        const badge = headerHost?.querySelector('[data-command-notification-badge]');
        const readAll = headerHost?.querySelector('[data-command-notification-read-all]');
        const testAlert = headerHost?.querySelector('[data-command-notification-test]');
        const mobileLists = document.querySelectorAll('[data-command-notification-mobile]');
        const mobileBadges = document.querySelectorAll('[data-command-notification-mobile-badge]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
        const safeUrl = (value) => {
            try {
                const target = new URL(String(value || '#'), window.location.origin);
                return target.origin === window.location.origin ? target.href : '#';
            } catch (error) {
                return '#';
            }
        };
        let databaseNotifications = [];
        let databaseUnreadCount = 0;
        let knownDatabaseIds = new Set();
        let hasLoadedDatabaseNotifications = false;
        const audibleCategories = new Set(['incident_assignment', 'incident_reported', 'dispatch_unavailable', 'fire_nearby', 'fire-nearby']);
        let localAlertAudioContext = null;

        const unlockLocalAlertAudio = () => {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                localAlertAudioContext ||= new AudioContextClass();
                localAlertAudioContext.resume?.();
            } catch (error) {}
        };
        document.addEventListener('pointerdown', unlockLocalAlertAudio, { once:true, capture:true });

        const signalEmergencyAlert = async (item, requestPermission = false) => {
            if (typeof window.triggerPyroNotificationAlert === 'function' && !requestPermission) {
                window.triggerPyroNotificationAlert(item);
                return;
            }
            if (requestPermission && 'Notification' in window && Notification.permission === 'default') {
                await Notification.requestPermission().catch(() => 'default');
            }
            unlockLocalAlertAudio();
            if (localAlertAudioContext) {
                const now = localAlertAudioContext.currentTime;
                [880, 660, 880].forEach((frequency, index) => {
                    const oscillator = localAlertAudioContext.createOscillator();
                    const gain = localAlertAudioContext.createGain();
                    const startAt = now + (index * .18);
                    oscillator.frequency.value = frequency;
                    gain.gain.setValueAtTime(.0001, startAt);
                    gain.gain.exponentialRampToValueAtTime(.16, startAt + .03);
                    gain.gain.exponentialRampToValueAtTime(.0001, startAt + .16);
                    oscillator.connect(gain);
                    gain.connect(localAlertAudioContext.destination);
                    oscillator.start(startAt);
                    oscillator.stop(startAt + .17);
                });
            }
            navigator.vibrate?.([240, 120, 240]);
            window.PyroNotify?.notify(item.message, { type:'warning', title:item.title, duration:5500 });
            if ('Notification' in window && Notification.permission === 'granted') {
                const options = {
                    body:item.message,
                    tag:item.tag || 'pyrosense-emergency-alert',
                    icon:'/icon-192x192.png',
                    badge:'/icon-72x72.png',
                    vibrate:[220,120,220],
                    renotify:true,
                    data:{ type:'PYRO_FIRE_INCIDENT', incidentId:Number(item.incident_id || 0), url:item.url || window.location.href }
                };
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.getRegistration('/');
                    if (registration) {
                        await registration.showNotification(item.title, options).catch(() => {});
                        return;
                    }
                }
                new Notification(item.title, options);
            }
        };

        const incidentNotifications = () => {
            if (typeof window.getIncidentAlertNotifications !== 'function') return [];
            return window.getIncidentAlertNotifications().map((item) => ({
                ...item,
                id: `incident:${item.id}`,
                source_id: item.id,
                category: 'incident',
                is_read: Boolean(item.read),
                created_at_human: item.created_at ? new Date(item.created_at).toLocaleString() : '',
                url: '#',
                source: 'incident',
            }));
        };

        const render = () => {
            const localIncidents = incidentNotifications();
            const notifications = [...localIncidents, ...databaseNotifications]
                .sort((left, right) => new Date(right.created_at || right.created_at_human || 0) - new Date(left.created_at || left.created_at_human || 0))
                .slice(0, 30);
            const unreadCount = databaseUnreadCount + localIncidents.filter((item) => !item.is_read).length;
            if (badge) {
                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                badge.hidden = unreadCount < 1;
            }
            mobileBadges.forEach((mobileBadge) => {
                mobileBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                mobileBadge.hidden = unreadCount < 1;
            });
            const html = notifications.length ? notifications.map((item) => `
                <a class="command-notification-center__item ${item.is_read ? '' : 'is-unread'}" href="${escapeHtml(safeUrl(item.url))}" data-command-notification-id="${escapeHtml(item.id)}">
                    <strong>${escapeHtml(item.title)}</strong>
                    <span>${escapeHtml(item.message)}</span>
                    <small class="command-notification-center__meta"><span class="command-notification-center__category">${escapeHtml(String(item.category || 'general').replace(/_/g,' '))}</span><span>${escapeHtml(item.created_at_human || '')}</span></small>
                </a>
            `).join('') : '<p class="command-notification-center__empty">You have no notifications yet.</p>';
            if (list) list.innerHTML = html;
            mobileLists.forEach((mobileList) => { mobileList.innerHTML = html; });
        };

        const refresh = async () => {
            try {
                const response = await fetch(urls.index, { headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                if (!response.ok) throw new Error('Notification refresh failed.');
                const payload = await response.json();
                const incomingNotifications = (Array.isArray(payload.notifications) ? payload.notifications : []).map((item) => ({ ...item, source:'database' }));
                if (hasLoadedDatabaseNotifications) {
                    incomingNotifications
                        .filter((item) => !knownDatabaseIds.has(String(item.id)) && !item.is_read && audibleCategories.has(String(item.category || '').toLowerCase()))
                        .slice(0, 1)
                        .forEach((item) => signalEmergencyAlert({
                            incident_id: Number(item.meta?.incident_id || 0),
                            title: item.title || 'PyroSense emergency alert',
                            message: item.message || 'A new incident notification requires attention.',
                            url: item.url || window.location.href,
                            tag: `notification-${item.id}`,
                        }));
                }
                databaseNotifications = incomingNotifications;
                knownDatabaseIds = new Set(incomingNotifications.map((item) => String(item.id)));
                hasLoadedDatabaseNotifications = true;
                databaseUnreadCount = Number(payload.unread_count || 0);
                render();
            } catch (error) {
                if (list) list.innerHTML = '<p class="command-notification-center__empty">Notifications are unavailable right now.</p>';
            }
        };

        const post = async (url) => fetch(url, { method:'POST', headers:{Accept:'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });

        document.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-command-notification-id]');
            if (!item) return;
            const id = item.dataset.commandNotificationId;
            if (id?.startsWith('incident:')) {
                event.preventDefault();
                window.focusIncidentAlertEntry?.(id.slice('incident:'.length));
                return;
            }
            if (id) await post(urls.read.replace('__ID__', encodeURIComponent(id)));
        });

        trigger?.addEventListener('click', () => {
            if (!panel) return;
            const willOpen = panel.hidden;
            panel.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) refresh();
        });
        readAll?.addEventListener('click', async () => {
            window.markAllIncidentAlertsRead?.();
            await post(urls.read_all);
            await refresh();
        });
        testAlert?.addEventListener('click', async () => {
            if (typeof window.testPyroIncidentAlert === 'function') {
                await window.testPyroIncidentAlert();
                return;
            }
            await signalEmergencyAlert({
                incident_id:0,
                title:'PyroSense fire alert test',
                message:'Sound, vibration, and emergency notifications are ready.',
                url:window.location.href,
                tag:`pyrosense-alert-test-${Date.now()}`
            }, true);
        });
        document.addEventListener('click', (event) => {
            if (panel && headerHost && !headerHost.contains(event.target)) { panel.hidden = true; trigger?.setAttribute('aria-expanded','false'); }
        });

        window.refreshCommandNotifications = refresh;
        window.addEventListener('pyro:incident-notifications-updated', render);
        refresh();
        window.setInterval(refresh, 30000);
    });
</script>
