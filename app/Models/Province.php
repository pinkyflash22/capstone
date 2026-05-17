<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\City;
class Province extends Model
{
    use HasFactory;
    protected $primaryKey = 'province_id';
    protected $table='provinces';
    protected $fillable=[
        'province_name',
    ];
      public function cities() {
          return $this->hasMany(City::class, 'province_id', 'province_id');
    }
}
