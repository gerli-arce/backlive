<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    static $rules = [
        'module' => 'unique|require',
        'status' => 'require'
    ];
    public $timestamps = false;
    public function service(){
        return $this ->hasOne(Service::class, 'id', '_service');
    }

    public function activiti(){
        return $this->hasMany(Activity::class, '_module', 'id');
    }
}