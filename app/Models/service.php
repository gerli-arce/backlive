<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    static $rules = [
        'service' => 'unique|require',
        'correlative' => 'require',
        'repository' => 'require',
        'status' => 'require'
    ];
    public $timestamps = false;

    public function module(){
        return $this->hasMany(Module::class, '_service', 'id');
    }
}
