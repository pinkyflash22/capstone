<?php

namespace Tests\Unit;

use Tests\TestCase;

class DutyRosterRecurrenceUiTest extends TestCase
{
    public function test_roster_form_exposes_daily_weekly_and_selected_weekday_recurrence(): void
    {
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $script = file_get_contents(resource_path('js/duty-roster.js'));

        $this->assertStringContainsString('<option value="daily">Every day</option>', $view);
        $this->assertStringContainsString('<option value="weekdays">Selected weekdays</option>', $view);
        $this->assertStringContainsString("1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'", $view);
        $this->assertStringContainsString('data-weekday-picker', $view);
        $this->assertStringContainsString("data.getAll('weekdays[]').map(Number)", $script);
        $this->assertStringContainsString("shift.recurrence_type === 'daily'", $script);
    }

    public function test_provincial_admin_selects_an_explicit_station_for_every_roster_request(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $script = file_get_contents(resource_path('js/duty-roster.js'));
        $controller = file_get_contents(app_path('Http/Controllers/DutyRosterController.php'));

        $this->assertSame(2, substr_count($routes, "Route::prefix('duty-roster')->name('duty-roster.')"));
        $this->assertStringContainsString('id="dutyRosterStationSelect"', $view);
        $this->assertStringContainsString("'/admin/duty-roster'", $view);
        $this->assertStringContainsString("requestUrl.searchParams.set('station_id', config.stationId)", $script);
        $this->assertStringContainsString("if (\$role === 'provincial admin')", $controller);
        $this->assertStringContainsString("Station::query()->whereKey(\$stationId)->exists()", $controller);
    }

    public function test_recurrence_schema_and_generator_support_multiple_patterns(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_29_000000_extend_duty_roster_recurrence_options.php'));
        $service = file_get_contents(app_path('Services/DutyRosterRecurrenceService.php'));

        $this->assertStringContainsString("'recurrence_type'", $migration);
        $this->assertStringContainsString("'weekdays'", $migration);
        $this->assertStringContainsString("'daily' => true", $service);
        $this->assertStringContainsString("'weekdays' => \$selectedWeekdays->contains", $service);
    }

    public function test_repeat_shift_delete_uses_short_plain_choices(): void
    {
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $script = file_get_contents(resource_path('js/duty-roster.js'));
        $controller = file_get_contents(app_path('Http/Controllers/DutyRosterController.php'));

        $this->assertStringContainsString('Only this day', $view);
        $this->assertStringContainsString('This and next days', $view);
        $this->assertStringNotContainsString('This occurrence', $view);
        $this->assertStringNotContainsString('This and future', $view);
        $this->assertStringContainsString("JSON.stringify({ delete_scope: scope })", $script);
        $this->assertStringContainsString("(\$data['delete_scope'] ?? 'this') === 'future'", $controller);
    }

    public function test_roster_has_two_simple_team_turn_plans(): void
    {
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $script = file_get_contents(resource_path('js/duty-roster.js'));
        $controller = file_get_contents(app_path('Http/Controllers/DutyRosterController.php'));
        $service = file_get_contents(app_path('Services/DutyRosterRecurrenceService.php'));

        $this->assertStringContainsString('Two teams, 12-hour turns', $view);
        $this->assertStringContainsString('Three teams, 24-hour turns', $view);
        $this->assertStringContainsString('data-rotation-teams', $view);
        $this->assertStringContainsString('toggleScheduleTemplate', $script);
        $this->assertStringContainsString('storeTeamRotation', $controller);
        $this->assertStringContainsString("'rotation' =>", $service);
    }

    public function test_team_turn_picker_shows_readiness_and_a_short_preview(): void
    {
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $script = file_get_contents(resource_path('js/duty-roster.js'));
        $styles = file_get_contents(resource_path('css/duty-roster.css'));

        $this->assertStringContainsString('Turn preview', $view);
        $this->assertStringContainsString('data-turn-preview-list', $view);
        $this->assertStringContainsString("'Ready'", $script);
        $this->assertStringContainsString('Needs ${missing}', $script);
        $this->assertStringContainsString('renderTurnPreview', $script);
        $this->assertStringContainsString('is-not-ready', $styles);
    }

    public function test_agora_lazy_download_is_split_into_smaller_build_parts(): void
    {
        $vite = file_get_contents(base_path('vite.config.js'));
        $voice = file_get_contents(resource_path('js/voice-calls.js'));

        $this->assertStringContainsString("import('agora-rtc-sdk-ng/esm')", $voice);
        foreach (['agora-core', 'agora-media', 'agora-protocol', 'agora-report', 'agora-shared'] as $chunk) {
            $this->assertStringContainsString("'{$chunk}'", $vite);
        }
    }
}
