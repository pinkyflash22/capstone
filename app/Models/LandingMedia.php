<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class LandingMedia extends Model
{
    protected $table = 'landing_media';

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'media_path',
        'poster_path',
        'button_label',
        'button_url',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['media_url', 'poster_url'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function getMediaUrlAttribute(): ?string
    {
        return MediaPath::url($this->media_path);
    }

    public function getPosterUrlAttribute(): ?string
    {
        return MediaPath::url($this->poster_path);
    }
}
