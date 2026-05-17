@php
    $showPwaInstallButton = $showPwaInstallButton ?? false;
@endphp

@laravelPwa
@pwaUpdateNotifier

@if ($showPwaInstallButton)
    @pwaInstallButton
@endif
