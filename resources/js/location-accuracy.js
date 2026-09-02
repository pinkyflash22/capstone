const DEFAULT_MAX_ACCURACY_METERS = 100;
const DEFAULT_TARGET_ACCURACY_METERS = 35;
const DEFAULT_TIMEOUT_MS = 15000;
const MAX_FIX_AGE_MS = 15000;

let activeReliableFixRequest = null;

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

export function getReliableLocationFix(options = {}) {
    if (activeReliableFixRequest) {
        return activeReliableFixRequest;
    }

    const maxAccuracyMeters = Number(options.maxAccuracyMeters) || DEFAULT_MAX_ACCURACY_METERS;
    const targetAccuracyMeters = Number(options.targetAccuracyMeters) || DEFAULT_TARGET_ACCURACY_METERS;
    const timeoutMs = Number(options.timeoutMs) || DEFAULT_TIMEOUT_MS;

    activeReliableFixRequest = new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Location services are not available on this device.'));
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

            if (bestFix && bestFix.accuracy <= maxAccuracyMeters) {
                resolve(bestFix);
                return;
            }

            if (bestFix) {
                reject(new Error(`Location accuracy is only ±${Math.round(bestFix.accuracy)} m. Move outdoors, enable precise location, and try again.`));
                return;
            }

            reject(error || new Error('Unable to get a fresh GPS location. Enable precise location and try again.'));
        };

        const timeoutId = window.setTimeout(() => finish(), timeoutMs);

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                const fix = normalizePosition(position);
                if (!fix) return;

                if (!bestFix || fix.accuracy < bestFix.accuracy) {
                    bestFix = fix;
                }

                if (bestFix.accuracy <= targetAccuracyMeters) {
                    finish();
                }
            },
            (error) => {
                if (error?.code === error.PERMISSION_DENIED) {
                    finish(new Error('Location permission was denied. Allow precise location and try again.'));
                }
            },
            {
                enableHighAccuracy: true,
                timeout: timeoutMs,
                maximumAge: 0,
            }
        );
    }).finally(() => {
        activeReliableFixRequest = null;
    });

    return activeReliableFixRequest;
}

window.PyroLocation = {
    ...(window.PyroLocation || {}),
    getReliableLocationFix,
};
