@once
    @php
        $pyroFlashNotifications = collect([
            ['type' => 'success', 'message' => session('success')],
            ['type' => 'error', 'message' => session('error')],
            ['type' => 'warning', 'message' => session('warning')],
            ['type' => 'info', 'message' => session('info')],
            ['type' => 'info', 'message' => session('status')],
            ['type' => 'error', 'message' => isset($errors) && $errors->any() ? $errors->first() : null],
        ])->filter(fn ($item) => filled($item['message']))->values();
    @endphp
    <script>
        window.__pyroNotificationQueue = [
            ...(Array.isArray(window.__pyroNotificationQueue) ? window.__pyroNotificationQueue : []),
            ...@json($pyroFlashNotifications),
        ];
    </script>
    @vite('resources/js/unified-notifications.js')
@endonce
