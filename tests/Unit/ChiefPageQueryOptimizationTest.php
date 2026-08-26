<?php

namespace Tests\Unit;

use Tests\TestCase;

class ChiefPageQueryOptimizationTest extends TestCase
{
    public function test_post_feed_batches_user_specific_state_without_per_post_queries(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Admin/PostController.php'));
        $feedMethod = str($controller)
            ->after('public function getPosts')
            ->before("\n    public function store(")
            ->toString();

        $this->assertStringContainsString('->simplePaginate(10)', $feedMethod);
        $this->assertStringContainsString("->whereIn('post_id', \$posts->getCollection()->pluck('id'))", $feedMethod);
        $this->assertStringContainsString("->whereIn('user_id', \$postUserIds)", $feedMethod);
        $this->assertStringNotContainsString("\$post->liked_by_user = \$post->isLikedBy", $feedMethod);
        $this->assertStringNotContainsString("Post::with(['user.role', 'comments.user.role', 'likes'])", $feedMethod);
    }

    public function test_checklist_summary_and_filter_options_use_compact_queries(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/AnalyticController.php'));
        $indexMethod = str($controller)->between('public function checklistIndex', 'public function getStationsForAnalytics')->toString();

        $this->assertStringContainsString("SUM(CASE WHEN status IN ('pending', 'in_progress')", $indexMethod);
        $this->assertStringContainsString("->whereIn('barangay_id', \$barangayIdsQuery)", $indexMethod);
        $this->assertStringContainsString("->whereIn('user_id', \$assigneeIdsQuery)", $indexMethod);
        $this->assertStringNotContainsString("->with(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions'", $indexMethod);
    }

    public function test_dashboard_defers_marker_graph_and_reuses_station_data(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Chief/StationAdminController.php'));
        $view = file_get_contents(resource_path('views/chief/dashboard.blade.php'));

        $this->assertStringContainsString('$mapmarker = [];', $controller);
        $this->assertStringContainsString("'analysis_id',", $controller);
        $this->assertStringContainsString("'predictions',", $controller);
        $this->assertStringContainsString('getTeamsForStationOfficerIds($stationOfficerIds)', $controller);
        $this->assertStringContainsString('buildDashboardMlTrendSeries($recentAnalyses, $stationId, $teamIds, $incidents)', $controller);
        $this->assertStringContainsString('refreshMapMarkers({', $view);
        $this->assertStringContainsString('revalidate: true', $view);
    }

    public function test_migrations_cover_profiled_filter_and_order_columns(): void
    {
        $primary = file_get_contents(database_path('migrations/2026_08_26_080000_add_chief_page_query_indexes.php'));
        $supporting = file_get_contents(database_path('migrations/2026_08_26_081000_add_chief_dashboard_supporting_indexes.php'));

        foreach ([
            'posts_feed_order_idx',
            'station_officers_user_station_idx',
            'incidents_station_status_created_idx',
            'analysis_station_created_idx',
            'ai_action_plan_station_current_status_idx',
            'ai_action_plan_items_order_idx',
            'mitigation_checklist_current_latest_idx',
        ] as $indexName) {
            $this->assertStringContainsString($indexName, $primary);
        }

        $this->assertStringContainsString('fsic_approvals_officer_issued_idx', $supporting);
        $this->assertStringContainsString('barangay_officers_status_position_idx', $supporting);
    }
}
