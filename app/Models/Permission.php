<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model{

    static $rules = [
        
        'permission'=> 'required',
        'description' => 'required',
        'status' => 'required',
 
    ];
    public $timestamps = false;

    public function view(){
        return $this->hasOne(View::class, 'id', '_view');
    }
}
