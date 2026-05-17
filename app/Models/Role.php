<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Role extends Model
{
    protected $table = 'roles';

   protected $primaryKey = 'role_id';
    protected $fillable = ['role'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}