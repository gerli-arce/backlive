<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public $timestamps = false;

    protected $table = 'invoices';

    public function activities(){
        return $this ->hasMany(Activity::class, 'id', '_invoice');
    }
}
