<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProfileImageService;
use App\Support\MediaPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('image')->nullable();
            $table->string('profile_thumbnail')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Storage::fake('supabase');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_it_stores_a_square_webp_thumbnail_and_keeps_the_original(): void
    {
        $user = User::create([
            'first_name' => 'Station',
            'last_name' => 'Officer',
            'email' => 'officer@example.test',
        ]);

        $updated = app(ProfileImageService::class)->replace(
            $user,
            UploadedFile::fake()->image('profile.png', 640, 320)
        );

        $originalPath = MediaPath::stripPrefix($updated->image);
        $thumbnailPath = MediaPath::stripPrefix($updated->profile_thumbnail);

        Storage::disk('supabase')->assertExists($originalPath);
        Storage::disk('supabase')->assertExists($thumbnailPath);
        $this->assertStringEndsWith('.webp', $thumbnailPath);

        $dimensions = getimagesizefromstring(Storage::disk('supabase')->get($thumbnailPath));
        $this->assertSame(192, $dimensions[0]);
        $this->assertSame(192, $dimensions[1]);
        $this->assertSame($updated->profile_thumbnail_url, MediaPath::url($updated->profile_thumbnail));
    }

    public function test_an_existing_profile_without_a_thumbnail_falls_back_to_the_original(): void
    {
        $user = User::create([
            'first_name' => 'Legacy',
            'last_name' => 'Chief',
            'email' => 'chief@example.test',
            'image' => 'supabase://profile/legacy.jpg',
        ]);

        $this->assertSame($user->profile_image_url, $user->profile_thumbnail_url);
    }
}
