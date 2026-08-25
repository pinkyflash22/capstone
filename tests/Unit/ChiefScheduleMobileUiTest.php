<?php

namespace Tests\Unit;

use Tests\TestCase;

class ChiefScheduleMobileUiTest extends TestCase
{
    public function test_schedule_uses_a_compact_mobile_calendar_and_modal_workflows(): void
    {
        $view = file_get_contents(resource_path('views/chief/schedule.blade.php'));
        $audit = file_get_contents(base_path('tests/Browser/run-announcement-visual-smoke.ps1'));

        $this->assertStringContainsString('(display-mode: standalone) and (max-width: 1024px)', $view);
        $this->assertStringContainsString('.lector-top-bar { display: none !important; }', $view);
        $this->assertStringContainsString('grid-template-columns: repeat(7, minmax(0, 1fr))', $view);
        $this->assertStringContainsString('min-height: 54px', $view);
        $this->assertStringContainsString('aspect-ratio: .86', $view);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $view);

        $this->assertStringContainsString('id="requestReviewModal"', $view);
        $this->assertStringContainsString('id="openRequestReviewBtn"', $view);
        $this->assertStringContainsString('id="dayAgendaModal"', $view);
        $this->assertStringContainsString('id="selectedDayAddBtn"', $view);
        $this->assertStringContainsString('openDayAgendaModal();', $view);
        $this->assertStringContainsString('setModalVisibility(scheduleModal, true);', $view);
        $this->assertStringContainsString('body.schedule-modal-open { overflow: hidden; }', $view);

        $this->assertStringNotContainsString('data-add-date=', $view);
        $this->assertStringNotContainsString('min="2026-08-10"', $view);
        $this->assertStringContainsString("'announcements', 'checklists', 'schedule'", $audit);
        $this->assertStringContainsString('The monthly calendar did not render 42 day cells.', $audit);
    }
}
