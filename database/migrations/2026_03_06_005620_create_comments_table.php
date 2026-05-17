<?php
// database/migrations/2024_01_01_000002_create_comments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
       Schema::create('comments', function (Blueprint $table) {
    $table->id('comment_id'); // optional: custom PK
    $table->foreignId('post_id')
          ->constrained('posts')
          ->onDelete('cascade');
    $table->foreignId('user_id')
          ->constrained('users', 'user_id') // ✅ matches your users PK
          ->onDelete('cascade');
    $table->text('content');
    $table->timestamps();

    $table->index(['post_id', 'created_at']);
});
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};