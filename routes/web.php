<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function (){
    Route::get('shops','ApiController@shops');
    Route::get('shop','ApiController@shop');
    Route::get('sms','ApiController@sms');
    Route::post('login','ApiController@login');
    Route::post('regist','ApiController@regist');
    // 地址列表接口
    Route::get('addresslist','ApiController@addresslist');
    // 保存新增地址接口
    Route::post('addAddress','ApiController@addAddress');
    // 指定地址接口
    Route::get('address','ApiController@address');
    // 保存修改地址接口
    Route::post('editAddress','ApiController@editAddress');
    // 保存购物车接口
    Route::post('addCart','ApiController@addCart');
    // 获取购物车数据接口
    Route::get('cart','ApiController@cart');
    //添加订单接口
    Route::post('addorder','ApiController@addorder');
    // 获得指定订单接口
    Route::get('order','ApiController@order');
    // 获得订单列表接口
    Route::get('orderList','ApiController@orderList');
    // 修改密码接口
    Route::post('changePassword','ApiController@changePassword');
    //忘记密码接口
    Route::post('forgetPassword','ApiController@forgetPassword');
});