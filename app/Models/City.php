<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Province;
use App\Models\Barangay;
use App\Models\PostalCode;
class City extends Model
{

    use HasFactory;

    protected $primaryKey = 'city_id';
    protected $fillable = ['city_name', 'province_id'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function barangays()
    {
        return $this->hasMany(Barangay::class, 'city_id', 'city_id');
    }

  
}
