<script>
(function () {
    if (window.__userActivityLoggerInitialized) {
        return;
    }

    window.__userActivityLoggerInitialized = true;

    const userRole = (document.querySelector('meta[name="user-role"]')?.getAttribute('content') || 'user')
        .trim()
        .toLowerCase();
    const endpoint = @json(route('user-logs.store'));
    const recentActions = new Map();

    function compactText(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .replace(/[^\w\s\-/#&().,:]/g, '')
            .trim();
    }

    function toAction(action) {
        return compactText(action).slice(0, 50);
    }

    function isReadOnlyAction(action) {
        return /^(?:view(?:ed|ing)?|open(?:ed|ing)?|load(?:ed|ing)?|access(?:ed|ing)?|read(?:ing)?|visit(?:ed|ing)?|navigate(?:d|ing)?)(?:\s|$)/i.test(action);
    }

    function send(action) {
        const normalized = toAction(action);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!normalized || isReadOnlyAction(normalized) || !csrfToken) {
            return;
        }

        const now = Date.now();
        const lastSent = recentActions.get(normalized) || 0;

        if (now - lastSent < 1500) {
            return;
        }

        recentActions.set(normalized, now);

        const request = window.pyroFetch || window.fetch.bind(window);

        request(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: normalized })
        }).catch(() => {});
    }

    window.logUserInteraction = send;

    if (typeof window.print === 'function') {
        const originalPrint = window.print.bind(window);
        window.print = function () {
            send(`${userRole} printed page`);
            return originalPrint();
        };
    }
})();
</script>
