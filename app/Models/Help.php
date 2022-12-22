<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Help extends Model
{
    static $rules = [
        'lifegoals'=>'require'
    ];

    public $timestamps = false;

    public function user(){
        $this->hasOne(User::class,'id','_user');
    }
}
