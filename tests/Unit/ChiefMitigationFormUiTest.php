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

    public function test_register_drafts_suggestions_and_details_use_compact_responsive_presentations(): void
    {
        $page = file_get_contents(resource_path('views/chief/mitigations.blade.php'));

        $this->assertStringContainsString('class="insight-list"', $page);
        $this->assertStringContainsString('class="insight-card"', $page);
        $this->assertStringContainsString('Checklist suggestion', $page);
        $this->assertStringContainsString('data-payload=\'@json($suggestionReviewPayload)\'', $page);

        $this->assertStringContainsString('class="mitigation-register-table"', $page);
        $this->assertStringContainsString('Plan and coverage', $page);
        $this->assertStringContainsString('register-col-location', $page);
        $this->assertStringContainsString('register-plan__location', $page);

        $this->assertStringContainsString('class="mitigation-dialog__card details-dialog"', $page);
        $this->assertStringContainsString('const renderCompactDetails', $page);
        $this->assertStringContainsString('Evidence and rationale', $page);
        $this->assertStringContainsString('Execution and checklist', $page);
        $this->assertStringContainsString('Results and documentation', $page);
        $this->assertStringContainsString('left:var(--sidebar-w,280px)', $page);
    }

    public function test_documentation_thumbnails_and_server_backed_register_sorting_are_available(): void
    {
        $page = file_get_contents(resource_path('views/chief/mitigations.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Chief/StationAdminController.php'));

        $this->assertStringContainsString('class="attachment-gallery"', $page);
        $this->assertStringContainsString('class="attachment-thumb"', $page);
        $this->assertStringContainsString('loading="lazy" decoding="async"', $page);
        $this->assertStringContainsString('imagePattern.test', $page);

        $this->assertStringContainsString('data-mitigation-sort', $page);
        $this->assertStringContainsString('data-mitigation-sort-value', $page);
        $this->assertStringContainsString('<option value="priority"', $page);
        $this->assertStringContainsString('<option value="due_date_asc"', $page);
        $this->assertStringContainsString('<option value="due_date_desc"', $page);
        $this->assertStringContainsString('<option value="status"', $page);
        $this->assertStringContainsString("['priority', 'due_date_asc', 'due_date_desc', 'status']", $controller);
        $this->assertStringContainsString("\$filters['sort'] === 'due_date_asc'", $controller);
        $this->assertStringContainsString("\$filters['sort'] === 'due_date_desc'", $controller);
        $this->assertStringContainsString("\$filters['sort'] === 'status'", $controller);
        $this->assertStringContainsString('id="mitigationAttachmentLightbox"', $page);
        $this->assertStringContainsString('data-attachment-image', $page);
        $this->assertStringContainsString('openAttachmentLightbox', $page);
    }
}
