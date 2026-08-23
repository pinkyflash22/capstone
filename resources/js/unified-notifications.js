import '../css/unified-notifications.css';

const GLOBAL_KEY = '__pyroUnifiedNotifications';

if (!window[GLOBAL_KEY]) {
    const legacySelector = [
        '#toast',
        '.toast:not(.pyro-notice)',
        '.landing-toast',
        '.report-toast',
        '.duty-roster__toast',
        '.pyro-voice-toast',
        '.chief-route-toast',
        '.incident-alert-toast',
        '.dashboard-welcome-toast',
        '.chief-mobile-welcome-toast',
        '.firefighter-mobile-welcome-toast',
        '.citizen-mobile-welcome-toast',
        '.notification.notification-success',
        '.notification.notification-error',
        '.notification.notification-warning',
        '.notification.notification-info',
    ].join(',');
    const typeTitles = {
        success: 'Completed',
        error: 'Action needed',
        warning: 'Please review',
        info: 'PyroSense update',
    };
    const icons = {
        success: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.55 17.2 4.8 12.45l1.9-1.9 2.85 2.85 7.75-7.75 1.9 1.9z"/></svg>',
        error: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v6h-2zm0 8h2v2h-2zm1-13a10 10 0 1 0 0 20 10 10 0 0 0 0-20m0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16"/></svg>',
        warning: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 21h22L12 2zm12-3h-2v-2h2zm0-4h-2v-4h2z"/></svg>',
        info: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 17h2v-6h-2zm1-15a10 10 0 1 0 0 20 10 10 0 0 0 0-20m0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16M11 9h2V7h-2z"/></svg>',
    };
    const closeIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18.3 5.7-1-1L12 10l-5.3-5.3-1 1L11 11l-5.3 5.3 1 1L12 12l5.3 5.3 1-1L13 11z"/></svg>';
    const recent = new Map();
    const activeByKey = new Map();
    let region;

    const normalizeType = (value) => {
        if (value === true) return 'error';
        if (value === false) return 'success';
        const type = String(value || 'info').toLowerCase();
        if (['danger', 'failed', 'failure'].includes(type)) return 'error';
        return ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    };

    const ensureRegion = () => {
        if (region?.isConnected) return region;
        region = document.createElement('section');
        region.id = 'pyroNotificationRegion';
        region.className = 'pyro-notice-region';
        region.setAttribute('aria-label', 'Notifications');
        region.setAttribute('aria-live', 'polite');
        document.body.appendChild(region);
        return region;
    };

    const dismiss = (notice) => {
        if (!notice || notice.dataset.leaving === 'true') return;
        notice.dataset.leaving = 'true';
        window.clearTimeout(Number(notice.dataset.timer || 0));
        if (notice.dataset.noticeKey && activeByKey.get(notice.dataset.noticeKey) === notice) {
            activeByKey.delete(notice.dataset.noticeKey);
        }
        notice.classList.remove('is-visible');
        notice.classList.add('is-leaving');
        window.setTimeout(() => notice.remove(), 210);
    };

    const notify = (message, typeOrOptions = 'info', durationArg) => {
        const options = typeof typeOrOptions === 'object' && typeOrOptions !== null
            ? typeOrOptions
            : { type: typeOrOptions, duration: durationArg };
        const cleanMessage = String(message ?? options.message ?? '').replace(/\s+/g, ' ').trim();
        if (!cleanMessage) return null;

        const type = normalizeType(options.type);
        const duration = Math.max(1800, Math.min(15000, Number(options.duration) || 5000));
        const noticeKey = String(options.key || '').trim();
        const dedupeKey = `${type}:${cleanMessage}`;
        const now = Date.now();
        if ((recent.get(dedupeKey) || 0) > now - 1200) return null;
        recent.set(dedupeKey, now);
        if (noticeKey && activeByKey.has(noticeKey)) dismiss(activeByKey.get(noticeKey));

        const host = ensureRegion();
        const notice = document.createElement('article');
        notice.className = `pyro-notice pyro-notice--${type}`;
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        notice.style.setProperty('--pyro-notice-duration', `${duration}ms`);
        if (noticeKey) {
            notice.dataset.noticeKey = noticeKey;
            activeByKey.set(noticeKey, notice);
        }

        const icon = document.createElement('span');
        icon.className = 'pyro-notice__icon';
        icon.innerHTML = icons[type];

        const content = document.createElement('span');
        content.className = 'pyro-notice__content';
        const title = document.createElement('strong');
        title.className = 'pyro-notice__title';
        title.textContent = String(options.title || typeTitles[type]);
        const copy = document.createElement('span');
        copy.className = 'pyro-notice__message';
        copy.textContent = cleanMessage;
        content.append(title, copy);

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'pyro-notice__close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = closeIcon;
        close.addEventListener('click', () => dismiss(notice));

        const actionLabel = String(options.actionLabel || '').trim();
        const action = actionLabel && typeof options.onAction === 'function'
            ? document.createElement('button')
            : null;
        if (action) {
            action.type = 'button';
            action.className = 'pyro-notice__action';
            action.textContent = actionLabel;
            action.addEventListener('click', () => {
                dismiss(notice);
                options.onAction();
            });
            content.appendChild(action);
        }

        const progress = document.createElement('span');
        progress.className = 'pyro-notice__progress';
        progress.setAttribute('aria-hidden', 'true');
        notice.append(icon, content, close, progress);

        if (options.url) {
            notice.style.cursor = 'pointer';
            notice.addEventListener('click', (event) => {
                if (event.target.closest('button')) return;
                window.location.assign(String(options.url));
            });
        }

        host.prepend(notice);
        [...host.children].slice(4).forEach(dismiss);
        window.requestAnimationFrame(() => notice.classList.add('is-visible'));
        if (!options.persistent) {
            notice.dataset.timer = String(window.setTimeout(() => dismiss(notice), duration));
        } else {
            progress.hidden = true;
        }
        return notice;
    };

    const inferLegacyType = (element) => {
        const source = `${element.className || ''} ${element.id || ''} ${element.dataset.type || ''} ${element.textContent || ''}`.toLowerCase();
        if (/error|danger|failed|invalid|unable|expired/.test(source)) return 'error';
        if (/warn|review|missing|required/.test(source)) return 'warning';
        if (/success|complete|saved|updated|created|sent|approved|registered/.test(source)) return 'success';
        return 'info';
    };

    const ingestLegacy = (element) => {
        if (!(element instanceof HTMLElement) || element.matches('.pyro-notice')) return;
        const messageNode = element.querySelector('[data-toast-message], .toast-message, .pyro-voice-toast__message, .chief-route-toast__copy, span');
        const message = (messageNode?.textContent || element.textContent || '').replace(/\s+/g, ' ').trim();
        if (!message) return;
        notify(message, inferLegacyType(element));
        element.dataset.pyroNotificationConsumed = 'true';
        element.setAttribute('aria-hidden', 'true');
    };

    const inspectAddedNode = (node) => {
        if (!(node instanceof Element)) return;
        if (node.matches(legacySelector)) ingestLegacy(node);
        node.querySelectorAll?.(legacySelector).forEach(ingestLegacy);
    };

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach(inspectAddedNode);
            if (mutation.type === 'attributes' && mutation.target.matches?.(legacySelector)) {
                ingestLegacy(mutation.target);
            }
            if (mutation.type === 'characterData') {
                const legacy = mutation.target.parentElement?.closest?.(legacySelector);
                if (legacy) ingestLegacy(legacy);
            }
        });
    });

    const api = { notify, success: (message, options = {}) => notify(message, { ...options, type: 'success' }), error: (message, options = {}) => notify(message, { ...options, type: 'error' }), warning: (message, options = {}) => notify(message, { ...options, type: 'warning' }), info: (message, options = {}) => notify(message, { ...options, type: 'info' }) };
    window[GLOBAL_KEY] = api;
    window.PyroNotify = api;
    window.showToast = (message, type = 'success', duration) => notify(message, type, duration);
    window.showNotification = (message, type = 'info', duration) => notify(message, type, duration);
    window.alert = (message) => notify(message, 'info');

    const start = () => {
        document.documentElement.classList.add('pyro-notifications-ready');
        ensureRegion();
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'], characterData: true });
        const queue = Array.isArray(window.__pyroNotificationQueue) ? window.__pyroNotificationQueue.splice(0) : [];
        queue.forEach((entry) => notify(entry.message, entry));
        window.dispatchEvent(new CustomEvent('pyro:ready', { detail: api }));
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();

    window.addEventListener('pyro:notify', (event) => {
        const detail = event.detail || {};
        notify(detail.message, detail);
    });
}
