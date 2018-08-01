<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    //
    protected  $fillable = ['name','tel','user_id','province','city','county','address','is_default'];
}
