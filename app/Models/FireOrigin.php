<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FireOrigin extends Model
{
    use HasFactory;

    protected $table ='fire_origin';
    protected $primaryKey = 'fire_origin_id';
    protected $fillable = ['origin'];

    public function progressInvestigations()
    {
        return $this->hasMany(ProgressInvestigationReport::class, 'fire_origin_id', 'fire_origin_id');
    }
}