<?php
// database/migrations/2024_01_01_000001_create_posts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users','user_id')->onDelete('cascade');
            $table->text('content');
            $table->json('images')->nullable();
            $table->string('video')->nullable();
            $table->json('poll_data')->nullable();
            $table->enum('post_type', ['announcement', 'update', 'alert', 'poll'])->default('announcement');
            $table->boolean('is_important')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'created_at']);
            $table->index('post_type');
            $table->index('is_important');
            $table->index('is_pinned');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
};