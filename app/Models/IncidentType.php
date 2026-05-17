<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Incident;
class IncidentType extends Model
{
    use HasFactory;
    protected $table = "incident_type";
    protected $primaryKey = 'incident_type_id';
    protected $fillable = ['type'];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_type_id', 'incident_type_id');
    }

    public function getTypeNameAttribute()
    {
        return $this->type;
    }
}
