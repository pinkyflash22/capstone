<?php
// database/seeders/PostSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;

class PostSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'Chief Administrator'
            ]);
        }

        $posts = [
            [
                'content' => '🚨 Important: New safety protocols implemented. Please review the updated guidelines for wildfire response.',
                'is_important' => true,
                'post_type' => 'alert'
            ],
            [
                'content' => '📢 Training session this Friday at 10 AM. All personnel are required to attend.',
                'is_important' => false,
                'post_type' => 'announcement'
            ],
            [
                'content' => '🎉 Congratulations to Station 7 for completing 100 days without any safety incidents!',
                'is_important' => false,
                'post_type' => 'update'
            ]
        ];

        foreach ($posts as $postData) {
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $postData['content'],
                'is_important' => $postData['is_important'],
                'post_type' => $postData['post_type'],
                'likes_count' => rand(5, 50),
                'comments_count' => rand(0, 10)
            ]);

            if (rand(0, 1)) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'content' => 'Thanks for the update!'
                ]);
            }
        }
    }
}