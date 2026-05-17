<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Barangay;
use App\Models\User;
use App\Models\Station;

class FullAddress extends Model
{
      use HasFactory;
     protected $primaryKey = 'address_id';
    protected $fillable = ['street', 'barangay_id'];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'address_id', 'address_id');
    }

    public function stations()
    {
        return $this->hasMany(Station::class, 'address_id', 'address_id');
    }

}
