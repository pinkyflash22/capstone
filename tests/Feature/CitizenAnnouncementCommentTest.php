<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CitizenAnnouncementCommentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['comments', 'posts', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('content')->nullable();
            $table->json('images')->nullable();
            $table->string('video')->nullable();
            $table->json('poll_data')->nullable();
            $table->string('post_type')->default('announcement');
            $table->boolean('is_important')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->timestamps();
        });

        DB::table('roles')->insert(['role_id' => 1, 'role' => 'citizen']);
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'first_name' => 'First', 'last_name' => 'Citizen'],
            ['user_id' => 2, 'role_id' => 1, 'first_name' => 'Second', 'last_name' => 'Citizen'],
        ]);
        DB::table('posts')->insert(['id' => 10, 'user_id' => 1, 'content' => 'Community update']);
        Role::flushDefinitionCache();
    }

    protected function tearDown(): void
    {
        Role::flushDefinitionCache();
        foreach (['comments', 'posts', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_citizen_can_create_edit_and_delete_their_comment(): void
    {
        $citizen = User::findOrFail(1);

        $create = $this->actingAs($citizen)
            ->postJson('/citizen/api/announcements/10/comment', ['content' => 'Initial comment'])
            ->assertCreated()
            ->assertJsonPath('data.can_manage', true)
            ->assertJsonStructure(['data' => ['user' => ['profile_thumbnail_url', 'profile_image_url']]]);

        $commentId = $create->json('data.id');
        $this->assertIsInt($commentId);
        $this->assertDatabaseHas('comments', ['comment_id' => $commentId, 'content' => 'Initial comment']);
        $this->assertSame(1, (int) DB::table('posts')->where('id', 10)->value('comments_count'));

        $this->actingAs($citizen)
            ->putJson("/citizen/api/announcements/10/comment/{$commentId}", ['content' => 'Edited comment'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Edited comment');

        $this->actingAs(User::findOrFail(2))
            ->deleteJson("/citizen/api/announcements/10/comment/{$commentId}")
            ->assertForbidden();

        $this->actingAs($citizen)
            ->deleteJson("/citizen/api/announcements/10/comment/{$commentId}")
            ->assertOk()
            ->assertJsonPath('comments_count', 0);

        $this->assertDatabaseMissing('comments', ['comment_id' => $commentId]);
        $this->assertSame(0, (int) DB::table('posts')->where('id', 10)->value('comments_count'));
    }
}
