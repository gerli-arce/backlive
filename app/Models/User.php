<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model{

    static $rules = [
        
        'relative_id'=> 'required',
        'username' => 'required',
        'password' => 'required',
        'auth_token' => '',
        'lastname' => 'required',
        'name' => 'required',
        'email' => 'nullable|email|min:0|max:320',
        'phone_prefix' => '',
        'phone_number' => '',
        'status' => 'required'
    ];
    public $timestamps = false;

    public function role(){
        return $this->hasOne(Role::class, 'id', '_role');
    }
}
