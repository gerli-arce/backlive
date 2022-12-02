<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    static $rules = [
        'view' => 'unique|require',
        'placement' => 'unique|require',
        'path' => 'unique|require',
        'description' => '',
        'status' => 'require'
    ];

    public $timestamps = false;

    public function permissions(){
        return $this->hasMany(Permission::class, '_view', 'id');
    }

}
