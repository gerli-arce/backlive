<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    static $rules = [
        'username'=> 'required|unique',
        'password' => 'required',
        'auth_token' => '',
        'name' => 'required',
        'lastname' => 'required',
        'email' => 'nullable|email|min:0|max:320',
        'phone_number' => '',
        'phone_prefix' => '',
        'status' => 'required'
    ];
    public $timestamps = false;

    public function activity(){
        return $this->hasMany(Activity::class, '_user', 'id');
    }
  
}
