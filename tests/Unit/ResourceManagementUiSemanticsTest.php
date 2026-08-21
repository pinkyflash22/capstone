<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResourceManagementUiSemanticsTest extends TestCase
{
    #[Test]
    public function every_resource_table_is_paginated_and_mobile_dialogs_are_scroll_safe(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/ResourceManagementController.php'));
        $view = file_get_contents(resource_path('views/chief/resource-management.blade.php'));
        $resourceStyles = file_get_contents(resource_path('css/admin/resource-management.css'));
        $rosterStyles = file_get_contents(resource_path('css/duty-roster.css'));

        foreach (['personnel_page', 'teams_page', 'users_page', 'equipment_page', 'boundaries_page', 'barangay_officers_page', 'apparatus_page'] as $pageName) {
            $this->assertStringContainsString("'{$pageName}'", $controller . $view);
        }

        $this->assertStringContainsString('$barangayOfficersPage->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('$apparatusPage->onEachSide(1)->links()', $view);
        $this->assertStringContainsString('resource-management-page .modal .modal-body', $resourceStyles);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $resourceStyles);
        $this->assertStringContainsString('position:fixed;inset:0', $rosterStyles);
        $this->assertStringContainsString('overflow-y:auto', $rosterStyles);
    }
}
