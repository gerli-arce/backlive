<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    static $rules = [
        'file' => 'unique',
        'caption' => 'require',
        'status' => 'require',
    ];

    public $timestamps = false;

    protected $table = 'evidences';
    

    public function activity(){
        return $this ->hasOne(Activity::class, 'id', '_activity');
    }

}