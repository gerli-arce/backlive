<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
    static $rules = [
        'environment' => 'unique|require',
        'description' => 'require',
        'status' => 'require'
    ];
    public $timestamps = false;

    public function activity(){
        return $this->hasMany(Activity::class, '_module', 'id');
    }
}