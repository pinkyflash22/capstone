<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VoiceCallUiSemanticsTest extends TestCase
{
    #[Test]
    public function responder_call_center_is_an_opt_in_responsive_drawer(): void
    {
        $script = file_get_contents(resource_path('js/voice-calls.js'));
        $styles = file_get_contents(resource_path('css/voice-calls.css'));

        $this->assertStringContainsString('let responderPanelOpen = false;', $script);
        $this->assertStringContainsString('panel.hidden = !responderPanelOpen;', $script);
        $this->assertStringNotContainsString('panel.hidden = total === 0;', $script);
        $this->assertStringContainsString('data-pyro-voice-launcher', $script);
        $this->assertStringContainsString('data-pyro-voice-panel-close', $script);
        $this->assertStringContainsString('data-pyro-voice-search', $script);
        $this->assertStringContainsString('data-pyro-voice-filter', $script);
        $this->assertStringContainsString('data-pyro-voice-sort', $script);
        $this->assertStringContainsString('filterAndSortResponderCalls', $script);
        $this->assertStringContainsString("import('agora-rtc-sdk-ng')", $script);
        $this->assertStringNotContainsString("import AgoraRTC from 'agora-rtc-sdk-ng'", $script);
        $this->assertStringContainsString('data-pyro-voice-filter="active"', $script);
        $this->assertStringContainsString('data-pyro-voice-filter="missed"', $script);
        $this->assertStringContainsString('data-pyro-voice-filter="completed"', $script);
        $this->assertStringContainsString('focusResponderCall', $script);
        $this->assertStringContainsString('PYRO_OPEN_VOICE_CALL', $script);
        $this->assertStringContainsString('updateResponderLauncher(calls.length + liveCalls.length + missedCalls.length, calls.length);', $script);

        $this->assertStringContainsString('.pyro-voice-launcher', $styles);
        $this->assertStringContainsString('.pyro-voice-panel-backdrop:not([hidden])', $styles);
        $this->assertStringContainsString('bottom:calc(var(--mobile-bottom-nav-h, 72px) + 12px)', $styles);
        $this->assertStringContainsString('max-height:min(82dvh, 720px)', $styles);
        $this->assertStringContainsString('body.pyro-voice-panel-open { overflow:hidden; }', $styles);
    }
}
