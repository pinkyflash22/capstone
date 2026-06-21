<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationMedia extends Model
{
    protected $table = 'station_media';
    protected $primaryKey = 'station_media_id';

    protected $fillable = [
        'station_id',
        'path',
        'kind',
        'caption',
        'sort_order',
        'uploaded_by_user_id',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id', 'user_id');
    }
}
