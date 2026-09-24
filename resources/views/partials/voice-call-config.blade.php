@once
@php
    $pyroVoiceUser = auth()->user();
    $pyroVoiceRole = strtolower((string) ($pyroVoiceUser?->role?->role ?? ''));
    $pyroVoiceStationId = $pyroVoiceStationId ?? null;
    $pyroVoiceFallbackStations = collect($pyroVoiceFallbackStations ?? [])->values();

    if (! $pyroVoiceStationId && in_array($pyroVoiceRole, ['stationadmin', 'chief'], true)) {
        $pyroVoiceStationId = \App\Models\StationOfficer::query()
            ->where('user_id', $pyroVoiceUser?->user_id)
            ->value('station_id');
    }
@endphp

<script>
    window.PyroSenseVoiceCall = {
        configured: @json(filled(config('services.agora.app_id')) && filled(config('services.agora.app_certificate'))),
        role: @json($pyroVoiceRole),
        userId: @json($pyroVoiceUser?->user_id),
        stationId: @json($pyroVoiceStationId ? (int) $pyroVoiceStationId : null),
        fallbackStations: @json($pyroVoiceFallbackStations),
        pollIntervalMs: @json((int) config('services.voice_calls.poll_interval_ms', 2000)),
        routes: {
            store: @json(route('voice-calls.store')),
            incoming: @json(route('voice-calls.incoming')),
            stream: @json(route('voice-calls.stream')),
            callback: @json(route('voice-calls.callback')),
            incomingCallback: @json(route('voice-calls.callbacks.incoming')),
            show: @json(url('/voice-calls/__CALL__')),
            seen: @json(url('/voice-calls/__CALL__/seen')),
            accept: @json(url('/voice-calls/__CALL__/accept')),
            acceptCallback: @json(url('/voice-calls/__CALL__/accept-callback')),
            rejectCallback: @json(url('/voice-calls/__CALL__/reject-callback')),
            reject: @json(url('/voice-calls/__CALL__/reject')),
            end: @json(url('/voice-calls/__CALL__/end')),
            hold: @json(url('/voice-calls/__CALL__/hold')),
            resume: @json(url('/voice-calls/__CALL__/resume')),
            createIncident: @json(url('/voice-calls/__CALL__/create-incident')),
            flagPrank: @json(url('/voice-calls/__CALL__/flag-prank')),
            token: @json(url('/voice-calls/__CALL__/token')),
        },
    };
</script>
@vite(['resources/css/voice-calls.css', 'resources/js/voice-calls.js'])
@endonce
