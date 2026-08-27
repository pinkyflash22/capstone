<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VoiceCallUiSemanticsTest extends TestCase
{
    #[Test]
    public function caller_coordinates_require_a_fresh_accurate_browser_fix(): void
    {
        $script = file_get_contents(resource_path('js/voice-calls.js'));
        $controller = file_get_contents(app_path('Http/Controllers/VoiceCallController.php'));
        $map = file_get_contents(resource_path('js/map.js'));

        $this->assertStringContainsString('function getFreshCallerLocationPayload()', $script);
        $this->assertStringContainsString('maximumAge: 0', $script);
        $this->assertStringContainsString('isReliableCallerLocation(normalized, 30000, 150)', $script);
        $this->assertStringContainsString('getFreshCallerLocationPayload()', $script);
        $this->assertStringContainsString('(within ±${Math.round(accuracy)} m)', $script);
        $this->assertStringContainsString('&& (int) $data[\'caller_accuracy\'] <= 150', $controller);
        $this->assertStringContainsString('Accuracy radius:', $map);
    }

    #[Test]
    public function responder_call_center_is_an_opt_in_responsive_drawer(): void
    {
        $script = file_get_contents(resource_path('js/voice-calls.js'));
        $styles = file_get_contents(resource_path('css/voice-calls.css'));
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $controller = file_get_contents(app_path('Http/Controllers/VoiceCallController.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $landingView = file_get_contents(resource_path('views/dashboard.blade.php'));
        $landingScript = file_get_contents(resource_path('js/landing/main.js'));
        $landingController = file_get_contents(app_path('Http/Controllers/LandingPageController.php'));

        $this->assertStringContainsString('let responderPanelOpen = false;', $script);
        $this->assertStringContainsString('panel.hidden = !responderPanelOpen;', $script);
        $this->assertStringNotContainsString('panel.hidden = total === 0;', $script);
        $this->assertStringContainsString('data-pyro-voice-launcher', $script);
        $this->assertStringContainsString('data-pyro-voice-panel-close', $script);
        $this->assertStringContainsString('data-pyro-voice-search', $script);
        $this->assertStringContainsString('data-pyro-voice-filter', $script);
        $this->assertStringContainsString('data-pyro-voice-sort', $script);
        $this->assertStringContainsString('filterAndSortResponderCalls', $script);
        $this->assertStringContainsString("import('agora-rtc-sdk-ng/esm')", $script);
        $this->assertStringContainsString('.then(({ createClient, createMicrophoneAudioTrack })', $script);
        $this->assertStringNotContainsString("import AgoraRTC from 'agora-rtc-sdk-ng'", $script);
        $this->assertStringContainsString("function prepareAgoraForCallInterface(reason = 'call-ui')", $script);
        $this->assertStringContainsString("prepareAgoraForCallInterface('responder-panel')", $script);
        $this->assertStringContainsString("prepareAgoraForCallInterface('caller-panel')", $script);
        $this->assertStringContainsString("prepareAgoraForCallInterface('incoming-callback')", $script);
        $this->assertStringContainsString('Preparing secure call…', $script);
        $this->assertStringContainsString('Secure internet calling could not connect.', $script);
        $this->assertStringNotContainsString("openEmergencyTelephoneFallback('Agora", $script);
        $this->assertStringContainsString('data-pyro-voice-filter="active"', $script);
        $this->assertStringContainsString('data-pyro-voice-filter="missed"', $script);
        $this->assertStringContainsString('data-pyro-voice-filter="completed"', $script);
        $this->assertStringContainsString('focusResponderCall', $script);
        $this->assertStringContainsString('PYRO_OPEN_VOICE_CALL', $script);
        $this->assertStringContainsString('updateResponderLauncher(calls.length + liveCalls.length + missedCalls.length, calls.length);', $script);
        $this->assertStringContainsString("'voice-calls/anonymous/session'", $bootstrap);
        $this->assertStringContainsString("'csrf_token' => csrf_token()", $controller);
        $this->assertStringContainsString("->middleware('throttle:20,1')", $routes);
        $this->assertStringContainsString('let csrfToken =', $script);
        $this->assertStringContainsString('csrfToken = payload.csrf_token;', $script);
        $this->assertStringContainsString('data-pyro-landing-agora-call', $landingView);
        $this->assertStringContainsString("window.addEventListener('pyro:voice-call-failed'", $landingScript);
        $this->assertStringContainsString('resolveLandingEmergencyStation', $landingScript);
        $this->assertStringContainsString('LANDING_STATION_DIRECTORY_CACHE_KEY', $landingScript);
        $this->assertStringContainsString('window.startLandingStationCall', $landingScript);
        $this->assertStringContainsString('window.location.href = `tel:${button.dataset.fallbackPhone}`', $landingScript);
        $this->assertStringContainsString('nearestStationIdForCoordinates', $controller);
        $this->assertStringContainsString("'contact_number' => \$call->station?->contact_number", $controller);
        $this->assertStringContainsString("'station_id' => \$stationRecord?->station_id", $landingController);

        $this->assertStringContainsString('.pyro-voice-launcher', $styles);
        $this->assertStringContainsString('.pyro-voice-panel-backdrop:not([hidden])', $styles);
        $this->assertStringContainsString('bottom:calc(var(--mobile-bottom-nav-h, 72px) + 12px)', $styles);
        $this->assertStringContainsString('max-height:min(82dvh, 720px)', $styles);
        $this->assertStringContainsString('body.pyro-voice-panel-open { overflow:hidden; }', $styles);
    }
}
