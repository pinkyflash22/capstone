<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
// use App\Models\Account;


class Register extends Model
{
    //the info of user
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        
        'address_id',
        'role_id',
        'contact_number',
    ];
       public function user()
    {
        return $this->hasMany(Role::class);
    }

}
