<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\AfterActivityReport;
use App\Notifications\AfterActivityReportReviewedNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AfterActivityReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['after_activity_report_reviews', 'investigation_report_images', 'after_activity_reports', 'event_activities', 'station_officers', 'stations', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('roles', function (Blueprint $table) { $table->id('role_id'); $table->string('role'); $table->timestamps(); });
        Schema::create('users', function (Blueprint $table) { $table->id('user_id'); $table->unsignedBigInteger('role_id'); $table->string('first_name'); $table->string('last_name'); $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->timestamps(); });
        Schema::create('stations', function (Blueprint $table) { $table->id('station_id'); $table->string('station_name'); $table->timestamps(); });
        Schema::create('station_officers', function (Blueprint $table) { $table->id('station_officer_id'); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id'); $table->timestamps(); });
        Schema::create('event_activities', function (Blueprint $table) { $table->id('event_id'); $table->string('event'); $table->date('event_date'); $table->time('start_time')->nullable(); $table->time('end_time')->nullable(); $table->string('activity_type')->nullable(); $table->string('barangay')->nullable(); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('user_id')->nullable(); $table->timestamps(); });
        Schema::create('after_activity_reports', function (Blueprint $table) {
            $table->id('after_activity_report_id'); $table->unsignedBigInteger('event_activity_id')->nullable()->unique(); $table->unsignedBigInteger('station_id'); $table->unsignedBigInteger('station_officer_id')->nullable(); $table->unsignedBigInteger('approved_by_station_officer_id')->nullable(); $table->string('activity_title'); $table->string('activity_type')->nullable(); $table->date('activity_date'); $table->string('activity_location')->nullable(); $table->json('participants')->nullable(); $table->json('deployed_resources')->nullable(); $table->string('memorandum_for')->nullable(); $table->timestamp('report_completed_at')->nullable(); $table->text('references')->nullable(); $table->text('background')->nullable(); $table->text('significance')->nullable(); $table->text('activity_details'); $table->text('issues_challenges')->nullable(); $table->text('discussion_assessment')->nullable(); $table->text('recommendations')->nullable(); $table->string('review_status')->default('submitted'); $table->text('review_comments')->nullable(); $table->unsignedBigInteger('reviewed_by_user_id')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamp('prepared_signed_at')->nullable(); $table->string('prepared_signature_hash')->nullable(); $table->string('approval_signer_name')->nullable(); $table->timestamp('approved_signed_at')->nullable(); $table->string('approval_signature_hash')->nullable(); $table->timestamps();
        });
        Schema::create('investigation_report_images', function (Blueprint $table) { $table->id('investigation_report_image_id'); $table->string('report_type'); $table->unsignedBigInteger('report_id'); $table->string('file_path'); $table->string('file_name'); $table->string('mime_type')->nullable(); $table->unsignedBigInteger('file_size')->default(0); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); });
        Schema::create('after_activity_report_reviews', function (Blueprint $table) { $table->id('after_activity_report_review_id'); $table->unsignedBigInteger('after_activity_report_id'); $table->unsignedBigInteger('reviewer_user_id')->nullable(); $table->string('decision'); $table->text('comments')->nullable(); $table->string('signer_name')->nullable(); $table->timestamp('signed_at')->nullable(); $table->string('signature_hash')->nullable(); $table->timestamp('created_at')->useCurrent(); });

        DB::table('roles')->insert([['role_id' => 2, 'role' => 'firefighter'], ['role_id' => 3, 'role' => 'chief']]);
        DB::table('users')->insert(['user_id' => 5, 'role_id' => 2, 'first_name' => 'Test', 'last_name' => 'Firefighter']);
        DB::table('users')->insert(['user_id' => 6, 'role_id' => 3, 'first_name' => 'Chief', 'last_name' => 'Reviewer']);
        DB::table('stations')->insert([['station_id' => 10, 'station_name' => 'Station A'], ['station_id' => 20, 'station_name' => 'Station B']]);
        DB::table('station_officers')->insert([['station_officer_id' => 50, 'station_id' => 10, 'user_id' => 5], ['station_officer_id' => 60, 'station_id' => 10, 'user_id' => 6]]);
        DB::table('event_activities')->insert([
            ['event_id' => 100, 'event' => 'Fire Safety Seminar', 'event_date' => today()->subDay()->toDateString(), 'activity_type' => 'seminar', 'barangay' => 'Barangay Uno', 'station_id' => 10, 'user_id' => null],
            ['event_id' => 200, 'event' => 'Other Station Drill', 'event_date' => today()->toDateString(), 'activity_type' => 'drill', 'barangay' => null, 'station_id' => 20, 'user_id' => null],
        ]);
        Role::flushDefinitionCache();
    }

    public function test_firefighter_can_create_an_unscheduled_operation_aar(): void
    {
        $this->actingAs(User::findOrFail(5))
            ->postJson('/firefighter/after-activity-reports', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.activity_title', 'Immediate rescue operation')
            ->assertJsonPath('data.event_activity_id', null);

        $this->assertDatabaseHas('after_activity_reports', [
            'station_id' => 10,
            'station_officer_id' => 50,
            'activity_title' => 'Immediate rescue operation',
            'event_activity_id' => null,
        ]);
    }

    public function test_scheduled_activity_is_linked_and_uses_the_saved_schedule_details(): void
    {
        $payload = $this->payload() + ['event_activity_id' => 100];
        $payload['activity_title'] = 'Changed in browser';

        $this->actingAs(User::findOrFail(5))
            ->postJson('/firefighter/after-activity-reports', $payload)
            ->assertOk()
            ->assertJsonPath('data.activity_title', 'Fire Safety Seminar')
            ->assertJsonPath('data.activity_location', 'Barangay Uno');

        $this->assertDatabaseHas('after_activity_reports', ['event_activity_id' => 100, 'station_id' => 10]);
    }

    public function test_firefighter_cannot_link_another_stations_activity(): void
    {
        $this->actingAs(User::findOrFail(5))
            ->postJson('/firefighter/after-activity-reports', $this->payload() + ['event_activity_id' => 200])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event_activity_id');
    }

    public function test_chief_can_review_approve_and_digitally_sign_an_aar(): void
    {
        Notification::fake();
        $this->actingAs(User::findOrFail(5))->postJson('/firefighter/after-activity-reports', $this->payload())->assertOk();
        $reportId = (int) DB::table('after_activity_reports')->value('after_activity_report_id');

        $this->actingAs(User::findOrFail(6))
            ->patch('/station-admin/after-activity-reports/'.$reportId.'/review', [
                'decision' => 'approve',
                'review_comments' => 'Reviewed and complete.',
                'signature_name' => 'Chief Reviewer',
                'signature_consent' => '1',
            ])
            ->assertRedirect();

        $report = DB::table('after_activity_reports')->where('after_activity_report_id', $reportId)->first();
        $this->assertSame('approved', $report->review_status);
        $this->assertSame(6, (int) $report->reviewed_by_user_id);
        $this->assertSame(64, strlen($report->approval_signature_hash));
        $this->assertTrue(AfterActivityReport::findOrFail($reportId)->approvalSignatureIsValid());
        $this->assertDatabaseHas('after_activity_report_reviews', [
            'after_activity_report_id' => $reportId,
            'reviewer_user_id' => 6,
            'decision' => 'approved',
        ]);
        Notification::assertSentTo(User::findOrFail(5), AfterActivityReportReviewedNotification::class);
    }

    public function test_chief_can_request_changes_without_signing_approval(): void
    {
        Notification::fake();
        $this->actingAs(User::findOrFail(5))->postJson('/firefighter/after-activity-reports', $this->payload())->assertOk();
        $reportId = (int) DB::table('after_activity_reports')->value('after_activity_report_id');

        $this->actingAs(User::findOrFail(6))->patch('/station-admin/after-activity-reports/'.$reportId.'/review', [
            'decision' => 'request_changes',
            'review_comments' => 'Add the missing apparatus quantity.',
        ])->assertRedirect();

        $this->assertDatabaseHas('after_activity_reports', [
            'after_activity_report_id' => $reportId,
            'review_status' => 'changes_requested',
            'approval_signature_hash' => null,
        ]);
        $this->assertDatabaseHas('after_activity_report_reviews', [
            'after_activity_report_id' => $reportId,
            'decision' => 'changes_requested',
            'comments' => 'Add the missing apparatus quantity.',
        ]);
        Notification::assertSentTo(User::findOrFail(5), AfterActivityReportReviewedNotification::class);
    }

    public function test_signed_verification_url_validates_the_current_approved_report(): void
    {
        Notification::fake();
        $this->actingAs(User::findOrFail(5))->postJson('/firefighter/after-activity-reports', $this->payload())->assertOk();
        $reportId = (int) DB::table('after_activity_reports')->value('after_activity_report_id');
        $this->actingAs(User::findOrFail(6))->patch('/station-admin/after-activity-reports/'.$reportId.'/review', [
            'decision' => 'approve', 'signature_name' => 'Chief Reviewer', 'signature_consent' => '1',
        ])->assertRedirect();
        $report = AfterActivityReport::findOrFail($reportId);
        $url = URL::signedRoute('after-activity-reports.verify', ['afterActivityReport' => $report, 'approval' => $report->approval_signature_hash]);

        $this->get($url)->assertOk()->assertSee('Valid digitally approved AAR');
        $this->actingAs(User::findOrFail(5))
            ->get('/firefighter/after-activity-reports/'.$reportId.'/aar.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        DB::table('after_activity_reports')->where('after_activity_report_id', $reportId)->update(['activity_title' => 'Altered title']);
        $this->get($url)->assertStatus(422)->assertSee('Verification failed');
    }

    public function test_review_notification_supports_database_and_email_channels(): void
    {
        $user = User::findOrFail(5);
        $user->email = 'firefighter@example.test';
        $notification = new AfterActivityReportReviewedNotification(44, 'Community Drill', 'approved', 'Complete.', 'Chief Reviewer');

        $this->assertSame(['database', 'mail'], $notification->via($user));
        $this->assertSame('Your AAR was approved', $notification->toMail($user)->subject);
        $this->assertSame('AAR approved', $notification->toArray($user)['title']);
    }

    private function payload(): array
    {
        return [
            'activity_title' => 'Immediate rescue operation',
            'activity_type' => 'rescue',
            'activity_date' => today()->toDateString(),
            'activity_location' => 'Barangay Uno',
            'participants_text' => "FO1 Test Firefighter — Team Leader\nBarangay volunteers",
            'deployed_resources_text' => "Engine 1 — 1 unit\nFirst aid kit — 2 sets",
            'activity_details' => 'The station team responded and safely completed the rescue operation.',
            'issues_challenges' => 'None',
            'recommendations' => 'Continue readiness checks.',
            'signature_name' => 'Test Firefighter',
            'signature_consent' => '1',
        ];
    }
}
