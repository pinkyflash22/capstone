<?php

namespace Tests\Unit;

use Tests\TestCase;

class ChiefMitigationFormUiTest extends TestCase
{
    public function test_create_and_edit_forms_use_clear_sectioned_unique_layouts(): void
    {
        $page = file_get_contents(resource_path('views/chief/mitigations.blade.php'));
        $fields = file_get_contents(resource_path('views/chief/partials/mitigation-form-fields.blade.php'));

        $this->assertStringContainsString('mitigation-form-dialog', $page);
        $this->assertStringContainsString("'fieldPrefix' => 'createMitigation'", $page);
        $this->assertStringContainsString("'fieldPrefix' => 'editMitigation'", $page);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $page);
        $this->assertStringContainsString('.mitigation-form-block__grid { grid-template-columns:1fr;', $page);

        $this->assertStringContainsString('Plan and coverage', $fields);
        $this->assertStringContainsString('Ownership and traceability', $fields);
        $this->assertStringContainsString('Delivery and evaluation', $fields);
        $this->assertStringContainsString("@if(\$formMode === 'edit') open @endif", $fields);
        $this->assertStringContainsString('$fieldId = static fn', $fields);
        $this->assertStringNotContainsString('id="stationMitigation', $fields);
        $this->assertStringNotContainsString('.mitigation-field label::before', $page);
    }

    public function test_form_preserves_every_mitigation_submission_field(): void
    {
        $fields = file_get_contents(resource_path('views/chief/partials/mitigation-form-fields.blade.php'));

        foreach ([
            'scope_type', 'barangay_id', 'title', 'category', 'priority_level', 'status',
            'target_date', 'description', 'remarks', 'responsible_team_id',
            'responsible_user_id', 'source_type', 'risk_area_id',
            'barangay_prediction_checklist_id', 'ai_action_plan_checklist_id',
            'implementation_notes', 'completion_notes', 'outcome_result', 'effectiveness',
            'follow_up_required', 'follow_up_notes', 'attachments[]',
        ] as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $fields);
        }
    }
}
