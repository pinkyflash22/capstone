<?php
// app/Models/Post.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\MediaPath;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'images',
        'video',
        'poll_data',
        'post_type',
        'is_important',
        'is_pinned',
        'likes_count',
        'comments_count',
        'shares_count',
        'scheduled_for'
    ];

    protected $casts = [
        'images' => 'array',
        'poll_data' => 'array',
        'is_important' => 'boolean',
        'is_pinned' => 'boolean',
        'scheduled_for' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['image_urls', 'video_url', 'time_ago'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function isLikedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->user_id)->exists();
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
// app/Models/Post.php
// app/Models/Post.php - Add this temporarily for debugging
public function getImageUrlsAttribute()
{
    if (!$this->images) {
        return [];
    }

    return collect($this->images)->map(function ($image) {
        return MediaPath::url($image);
    })->toArray();
}

    public function getVideoUrlAttribute()
    {
        if (!$this->video) {
            return null;
        }

        return MediaPath::url($this->video);
    }
// public function getImageUrlsAttribute()
// {
//     if (!$this->images) {
//         return [];
//     }

//     return collect($this->images)->map(function ($image) {
//         // Remove escaped backslashes and build URL
//         $cleanPath = str_replace('\\/', '/', $image);
//         return Storage::disk('public')->url($cleanPath);
//     })->toArray();
// }
    // public function getImageUrlsAttribute()
    // {
    //     if (!$this->images) {
    //         return [];
    //     }

    //     return collect($this->images)->map(function ($image) {
    //         return Storage::disk('public')->url($image);
    //     })->toArray();
    // }

    public function getFirstImageUrlAttribute()
    {
        $urls = $this->image_urls;
        return !empty($urls) ? $urls[0] : null;
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeAnnouncements($query)
    {
        return $query->where('post_type', 'announcement');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }
}
