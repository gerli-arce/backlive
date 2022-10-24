<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    static $rules = [
        'activity' => 'require',
        'description' => 'require',
        'status' => 'require',
        'date_creation' => 'require',
        'date_ejecution' => 'require'
    ];
    public $timestamps = false;

    public function user(){
        return $this->hasOne(User::class, 'id', '_user');
    }

  
}
