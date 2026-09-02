const DEFAULT_MAX_ACCURACY_METERS = 100;
const DEFAULT_TARGET_ACCURACY_METERS = 35;
const DEFAULT_TIMEOUT_MS = 15000;
const MAX_FIX_AGE_MS = 15000;

let activeReliableFixRequest = null;
let activeReliableFixAcceptsBestAvailable = false;
let activePermissionDialog = null;

function normalizePosition(position) {
    const lat = Number(position?.coords?.latitude);
    const lng = Number(position?.coords?.longitude);
    const accuracy = Number(position?.coords?.accuracy);
    const timestamp = Number(position?.timestamp) || Date.now();

    if (!Number.isFinite(lat) || !Number.isFinite(lng) || !Number.isFinite(accuracy) || accuracy <= 0) {
        return null;
    }

    if (Date.now() - timestamp > MAX_FIX_AGE_MS) {
        return null;
    }

    return { lat, lng, accuracy, timestamp };
}

function deviceInstructions() {
    const userAgent = navigator.userAgent || '';

    if (/iPhone|iPad|iPod/i.test(userAgent)) {
        return [
            'Open iPhone Settings > Privacy & Security > Location Services and turn it on.',
            'Open Settings > Apps > Safari > Location and allow access, or change this website permission from Safari.',
            'Return to PyroSense and tap Try Again.',
        ];
    }

    if (/Android/i.test(userAgent)) {
        return [
            'Turn on Location from Android Quick Settings or Settings > Location.',
            'In Chrome, open Settings > Site settings > Location and allow PyroSense.',
            'Choose precise location when Android offers Precise or Approximate access.',
        ];
    }

    return [
        'Turn on Location Services in your device settings.',
        'Use the lock or location icon beside the browser address and allow Location for PyroSense.',
        'Reload PyroSense and try again.',
    ];
}

function ensureDialogElements() {
    let dialog = document.getElementById('pyroLocationPermissionDialog');
    if (dialog) return dialog;

    const style = document.createElement('style');
    style.textContent = `
        .pyro-location-permission{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58);backdrop-filter:blur(5px)}
        .pyro-location-permission.is-visible{display:flex}
        .pyro-location-permission__card{width:min(100%,440px);max-height:min(720px,calc(100dvh - 32px));overflow:auto;border:1px solid rgba(255,255,255,.7);border-radius:28px;background:#fff;padding:24px;box-shadow:0 30px 70px rgba(15,23,42,.28);font-family:Inter,ui-sans-serif,system-ui,sans-serif}
        .pyro-location-permission__icon{width:58px;height:58px;border-radius:20px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed,#fee2e2);color:#dc2626;font-size:27px;font-weight:900}
        .pyro-location-permission h2{margin:18px 0 8px;color:#0f172a;font-size:1.35rem;line-height:1.2}
        .pyro-location-permission p{margin:0;color:#64748b;font-size:.92rem;line-height:1.6}
        .pyro-location-permission__privacy{margin-top:14px!important;padding:11px 12px;border-radius:14px;background:#f8fafc;color:#475569!important;font-size:.78rem!important}
        .pyro-location-permission__steps{margin:16px 0 0;padding:14px 14px 14px 32px;border-radius:16px;background:#fff7ed;color:#7c2d12;font-size:.84rem;line-height:1.5}
        .pyro-location-permission__steps li+li{margin-top:7px}
        .pyro-location-permission__actions{display:flex;gap:10px;margin-top:20px}
        .pyro-location-permission__button{min-height:46px;border:0;border-radius:15px;padding:0 17px;font-size:.88rem;font-weight:800;cursor:pointer}
        .pyro-location-permission__button--primary{flex:1;background:linear-gradient(135deg,#ea580c,#dc2626);color:#fff;box-shadow:0 12px 24px rgba(220,38,38,.2)}
        .pyro-location-permission__button--secondary{background:#f1f5f9;color:#475569}
        @media(max-width:520px){.pyro-location-permission{align-items:flex-end;padding:12px}.pyro-location-permission__card{border-radius:26px 26px 20px 20px;padding:21px}.pyro-location-permission__actions{flex-direction:column-reverse}.pyro-location-permission__button{width:100%}}
    `;
    document.head.appendChild(style);

    dialog = document.createElement('div');
    dialog.id = 'pyroLocationPermissionDialog';
    dialog.className = 'pyro-location-permission';
    dialog.setAttribute('aria-hidden', 'true');
    dialog.innerHTML = `
        <section class="pyro-location-permission__card" role="dialog" aria-modal="true" aria-labelledby="pyroLocationPermissionTitle">
            <div class="pyro-location-permission__icon" aria-hidden="true">&#9673;</div>
            <h2 id="pyroLocationPermissionTitle"></h2>
            <p data-pyro-location-message></p>
            <p class="pyro-location-permission__privacy" data-pyro-location-privacy>PyroSense uses your location only for the requested safety feature and incident response.</p>
            <ol class="pyro-location-permission__steps" data-pyro-location-steps hidden></ol>
            <div class="pyro-location-permission__actions">
                <button type="button" class="pyro-location-permission__button pyro-location-permission__button--secondary" data-pyro-location-cancel>Not now</button>
                <button type="button" class="pyro-location-permission__button pyro-location-permission__button--primary" data-pyro-location-confirm>Enable Location</button>
            </div>
        </section>
    `;
    document.body.appendChild(dialog);
    return dialog;
}

function showLocationDialog(mode = 'request', detail = '') {
    if (activePermissionDialog) return activePermissionDialog;

    const dialog = ensureDialogElements();
    const title = dialog.querySelector('#pyroLocationPermissionTitle');
    const message = dialog.querySelector('[data-pyro-location-message]');
    const steps = dialog.querySelector('[data-pyro-location-steps]');
    const confirm = dialog.querySelector('[data-pyro-location-confirm]');
    const cancel = dialog.querySelector('[data-pyro-location-cancel]');

    const content = {
        request: {
            title: 'Enable precise location',
            message: 'Your current GPS position helps PyroSense place reports correctly and guide responders to the right street or area.',
            confirm: 'Continue',
            cancel: 'Not now',
            showSteps: false,
        },
        blocked: {
            title: 'Location access is blocked',
            message: 'PyroSense cannot display the browser permission again until location access is enabled in your phone and browser settings.',
            confirm: 'I changed it - Try Again',
            cancel: 'Close',
            showSteps: true,
        },
        unavailable: {
            title: 'Turn on device location',
            message: detail || 'The browser could not obtain a fresh GPS position. Location Services may be disabled or the signal may be weak.',
            confirm: 'Try Again',
            cancel: 'Close',
            showSteps: true,
        },
        inaccurate: {
            title: 'GPS accuracy is too weak',
            message: detail || 'Move outdoors or near a window, enable precise location, and try again before submitting the report.',
            confirm: 'Try Again',
            cancel: 'Close',
            showSteps: true,
        },
    }[mode];

    title.textContent = content.title;
    message.textContent = content.message;
    confirm.textContent = content.confirm;
    cancel.textContent = content.cancel;
    steps.hidden = !content.showSteps;
    steps.innerHTML = '';
    if (content.showSteps) {
        deviceInstructions().forEach((instruction) => {
            const item = document.createElement('li');
            item.textContent = instruction;
            steps.appendChild(item);
        });
    }

    dialog.classList.add('is-visible');
    dialog.setAttribute('aria-hidden', 'false');
    window.setTimeout(() => confirm.focus(), 0);

    activePermissionDialog = new Promise((resolve) => {
        const close = (accepted) => {
            dialog.classList.remove('is-visible');
            dialog.setAttribute('aria-hidden', 'true');
            confirm.onclick = null;
            cancel.onclick = null;
            dialog.onclick = null;
            activePermissionDialog = null;
            resolve(accepted);
        };

        confirm.onclick = () => close(true);
        cancel.onclick = () => close(false);
        dialog.onclick = (event) => {
            if (event.target === dialog) close(false);
        };
    });

    return activePermissionDialog;
}

async function geolocationPermissionState() {
    if (!navigator.permissions?.query) return 'prompt';

    try {
        const permission = await navigator.permissions.query({ name: 'geolocation' });
        return permission.state || 'prompt';
    } catch (error) {
        return 'prompt';
    }
}

function collectReliableFix(options = {}) {
    const maxAccuracyMeters = Number(options.maxAccuracyMeters) || DEFAULT_MAX_ACCURACY_METERS;
    const targetAccuracyMeters = Number(options.targetAccuracyMeters) || DEFAULT_TARGET_ACCURACY_METERS;
    const timeoutMs = Number(options.timeoutMs) || DEFAULT_TIMEOUT_MS;
    const acceptBestAvailable = options.acceptBestAvailable === true;

    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(Object.assign(new Error('Location services are not available on this device.'), { locationReason: 'unavailable' }));
            return;
        }

        let bestFix = null;
        let watchId = null;
        let finished = false;

        const finish = (error = null) => {
            if (finished) return;
            finished = true;
            window.clearTimeout(timeoutId);
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);

            if (bestFix && (bestFix.accuracy <= maxAccuracyMeters || acceptBestAvailable)) {
                resolve({
                    ...bestFix,
                    accuracyWarning: bestFix.accuracy > maxAccuracyMeters,
                    accuracyLimit: maxAccuracyMeters,
                });
                return;
            }

            if (bestFix) {
                reject(Object.assign(
                    new Error(`Location accuracy is only \u00B1${Math.round(bestFix.accuracy)} m. Move outdoors, enable precise location, and try again.`),
                    { locationReason: 'inaccurate' }
                ));
                return;
            }

            reject(error || Object.assign(
                new Error('Unable to get a fresh GPS location. Enable precise location and try again.'),
                { locationReason: 'unavailable' }
            ));
        };

        const timeoutId = window.setTimeout(() => finish(), timeoutMs);
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                const fix = normalizePosition(position);
                if (!fix) return;
                if (!bestFix || fix.accuracy < bestFix.accuracy) bestFix = fix;
                if (bestFix.accuracy <= targetAccuracyMeters) finish();
            },
            (error) => {
                if (error?.code === error.PERMISSION_DENIED) {
                    finish(Object.assign(new Error('Location permission was denied.'), { locationReason: 'blocked' }));
                } else if (error?.code === error.POSITION_UNAVAILABLE) {
                    finish(Object.assign(new Error('Device location is unavailable.'), { locationReason: 'unavailable' }));
                }
            },
            { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 0 }
        );
    });
}

async function requestReliableLocationFix(options = {}) {
    const permissionState = await geolocationPermissionState();

    if (permissionState === 'denied') {
        const retry = await showLocationDialog('blocked');
        if (!retry) throw Object.assign(new Error('Location permission is blocked.'), { locationReason: 'blocked' });
    } else if (permissionState === 'prompt') {
        const accepted = await showLocationDialog('request');
        if (!accepted) throw Object.assign(new Error('Location request was cancelled.'), { locationReason: 'cancelled' });
    }

    try {
        return await collectReliableFix(options);
    } catch (error) {
        if (error?.locationReason && error.locationReason !== 'cancelled') {
            const retry = await showLocationDialog(error.locationReason, error.message);
            if (retry) return requestReliableLocationFix(options);
        }
        throw error;
    }
}

export function getReliableLocationFix(options = {}) {
    const acceptsBestAvailable = options.acceptBestAvailable === true;
    if (activeReliableFixRequest) {
        // An emergency report must not inherit a stricter background map request
        // that would reject the best fix and block submission.
        if (acceptsBestAvailable && !activeReliableFixAcceptsBestAvailable) {
            return requestReliableLocationFix(options);
        }

        return activeReliableFixRequest;
    }

    const request = requestReliableLocationFix(options).finally(() => {
        if (activeReliableFixRequest === request) {
            activeReliableFixRequest = null;
            activeReliableFixAcceptsBestAvailable = false;
        }
    });
    activeReliableFixRequest = request;
    activeReliableFixAcceptsBestAvailable = acceptsBestAvailable;

    return activeReliableFixRequest;
}

window.PyroLocation = {
    ...(window.PyroLocation || {}),
    getReliableLocationFix,
    showPermissionHelp: () => showLocationDialog('blocked'),
};
