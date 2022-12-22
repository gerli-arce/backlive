<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifeGoal extends Model
{
    static $rules = [
        'goal'=>'require',
        'date_start'=>'require',
        'date_end'=>'require',
    ];

    public $timestamps = false;

    public function user(){
        $this->hasOne(User::class,'id','_user');
    }
}
