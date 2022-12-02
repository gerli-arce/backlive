<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model{
    static $rutes =[
        'activity' => 'required',
        'observation' => '',
        'priority' => 'required',
        'relative_hours'=>'',
        'accepted_hours'=>'',
        'creation_user'=>'require',
        'creation_date'=>'require',
        'update_user'=>'',
        'update_date'=>'',
        'onservation_date'=>'',
        'status' => 'required',
    ];

    public $timestamps = false;

    protected $dateFormat = 'ES';

    public function module(){
        return $this ->hasOne(Module::class, 'id', '_modulo');
    }
    public function environment(){
        return $this ->hasOne(Module::class, 'id', '_environment');
    }
    public function evoice(){
        return $this ->hasOne(Module::class, 'id', '_evoice');
    }
    public function whodidit(){
        return $this ->hasOne(Module::class, 'id', '_whodidit');
    }
    public function activiti(){
        return $this->hasMany(Evidence::class, '_activity', 'id');
    }
}
