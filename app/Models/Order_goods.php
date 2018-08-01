<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order_goods extends Model
{
    //
    protected $fillable=['order_id','goods_id','amount','goods_name','goods_img','goods_price',];
    public function menu()
    {
        return $this->belongsTo(Menus::class,'goods_id');
        //Student::class ==== 'App\Models\Student'
    }
}
