<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    //
    protected  $fillable = ['user_id','goods_id','amount',];

    public function menu()
    {
        return $this->belongsTo(Menus::class,'goods_id');
        //Student::class ==== 'App\Models\Student'
    }
}
