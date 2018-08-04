<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Member;
use App\Models\MenuCategories;
use App\Models\Menus;
use App\Models\Order;
use App\Models\Order_goods;
use App\Models\Shop;
use App\Models\ShoppingCart;
use App\SignatureHelper;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;

class ApiController extends Controller
{

    //商家列表的接口
    public function shops()
    {
        $shops = Shop::all();
        foreach ($shops as &$shop) {
            $shop['distance'] = mt_rand(100, 1800);
            $shop['estimate_time'] = mt_rand(10, 60);
        }
        return json_encode($shops);
    }

    //指定商家的接口
    public function shop(Request $request)
    {
        $shop = Shop::where('id', $request->id)->first();
//        return json_encode($shop);
        /**
         *  * "service_code": 4.6,// 服务总评分---
         * "foods_code": 4.4,// 食物总评分---
         * "high_or_low": true,// ---
         * 低于还是高于周边商家
         * "h_l_percent": 30,// ---低于还是高于周边商家的百分比
         */

        $shop['service_code'] = 4.6;
        $shop['foods_code'] = 4.4;
        $shop['high_or_low'] = true;
        $shop['h_l_percent'] = 20;
        $shop['evaluate'] = [
            [
                "user_id" => 12344,
                "username" => "w******k",
                "user_img" => "http://www.homework.com/images/slider-pic4.jpeg",
                "time" => "2017-2-22",
                "evaluate_code" => 1,
                "send_time" => 30,
                "evaluate_details" => "不怎么好吃"

            ], [
                "user_id" => 12344,
                "username" => "w******k",
                "user_img" => "http://www.homework.com/images/slider-pic4.jpeg",
                "time" => "2017-2-22",
                "evaluate_code" => 5,
                "send_time" => 30,
                "evaluate_details" => "很好吃"

            ]];

//        $shop['commodity'] = [
//            [
//                "description" => "大家喜欢吃，才叫真好吃。",
//                "is_selected" => true,
//                "name" => "热销榜",
//                "type_accumulation" => "c1",
//                "goods_list" => [
//                    ["goods_id" => 100001,
//                        "goods_name" => "吮指原味鸡",
//                        "rating" => 4.67,
//                        "goods_price" => 11,
//                        "description" => "",
//                        "month_sales" => 590,
//                        "rating_count" => 91,
//                        "tips" => "具有神秘配方浓郁的香料所散发的绝佳风味，鲜嫩多汁。",
//                        "satisfy_count" => 8,
//                        "satisfy_rate" => 95,
//                        "goods_img" => "http://www.homework.com/images/slider-pic4.jpeg"]
//                ]
//            ]
//        ];
        $menuscategories = MenuCategories::where('shop_id', $request->id)->get();
        $commodity = [];
        foreach ($menuscategories as $menuscategory) {
            $menus = Menus::where('category_id', $menuscategory->id)->get();
            $goods_list = [];
            foreach ($menus as $key=>$menu) {
                $goods_list[]=
                [
                    'goods_id' => $menu->id,
                    'goods_name' => $menu->goods_name,
                    'rating' => $menu->rating,
                    'goods_price' => $menu->goods_price,
                    'description' => $menu->description,
                    'month_sales' => $menu->month_sales,
                    'rating_count' => $menu->rating_count,
                    'tips' => $menu->tips,
                    'satisfy_count' => $menu->satisfy_count,
                    'satisfy_rate' => $menu->satisfy_rate,
                    'goods_img' => $menu->goods_img,
                ];
            }
                $commodity[] = [
                    'description' => $menuscategory->description,
                    'is_selected' => $menuscategory->is_selected,
                    'name' => $menuscategory->name,
                    'type_accumulation' => $menuscategory->type_accumulation,
                    'goods_list' => $goods_list,
                ];


        }
        $shop['commodity'] = $commodity;
        return json_encode($shop);
    }

    //发送验证码的接口
    public function sms(Request $request)
    {
        $tel = $request->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIY8hBBtqqvhel";
        $accessKeySecret = "ve7xkPfJYV2nzZMLH7EhF0xiLLF7Kc";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "任万琪";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140555046";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $num = random_int(1000, 9999);
        $params['TemplateParam'] = Array(
            "code" => $num,
//            "product" => "阿里通信"
        );


        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );

        Redis::set('code' . $tel, $num);
        Redis::expire('code', 300);
        return json_encode([
            "status" => "true",
            "message" => "发送短信验证码成功"
        ]);
    }

    //注册接口
    public function regist(Request $request)
    {
        /* username: 用户名
    * tel: 手机号
    * sms: 短信验证码
    * password: 密码
         *
         */
//        $count = Member::where('username',$request->username)->first();
//        if ($count){
//            return json_encode(
//                [
//                    'status'=>'false',
//                    'message'=>'用户名已存在',
//                ]
//            );
//        }
//        $count1 = Member::where('tel',$request->tel)->first();
//        if ($count1){
//            return [
//                    'status'=>'false',
//                    'message'=>'该号码已被注册',
//                ];
//        }

        $validator = Validator::make($request->all(), [
            'username' => 'unique:members',
            'tel' => 'unique:members',
        ], [
            'username.unique' => '用户名已存在',
            'tel.unique' => '该号码已被注册',
        ]);

        if ($validator->fails()) {
            return [
                'status' => "false",
                'message' => $validator->errors()->first(),
            ];
        }
        $num = Redis::get('code' . $request->tel);
        if ($num == $request->sms) {
            Member::create([
                'username' => $request->username,
                'tel' => $request->tel,
                'password' => bcrypt($request->password),
                'remember_token' => 'abc',
                'status'=>'1',
            ]);
            return json_encode([
                'status' => "true",
                'message' => '注册成功'
            ]);
        } else {
            return json_encode([
                'status' => "false",
                'message' => '注册失败,验证码错误'
            ]);
        }
//        return json_encode([
//            'status' => false,
//            'message' => '注册失败'
//            ]);
    }

    //登录接口
    public function login(Request $request)
    {
        if (Auth::attempt([
            'username' => $request->name,
            'password' => $request->password,
        ])
        ) {
            return [
                "status" => "true",
                "message" => "登录成功",
                "user_id" => Auth::user()->id,
                "username" => $request->name,
            ];
        } else {
            return [
                "status" => 'false',
                "message" => "登录失败",];
        }
    }

    //地址列表接口
    public function addresslist()
    {
//        $user_id = Auth::user()->id;
        $user_id = 1;
        $address = Address::where('user_id', $user_id)->get();
        foreach ($address as &$v) {
            unset($v['user_id']);
            unset($v['created_at']);
            unset($v['updated_at']);
            unset($v['is_default']);
            $v['area'] = $v['county'];
            unset($v['county']);
            $v['detail_address'] = $v['address'];
            unset($v['address']);
            $v['provence'] = $v['province'];
            unset($v['province']);
        }
        return $address;
    }

    // 保存新增地址接口
    public function addAddress(Request $request)
    {
        /**
         * name: 收货人
         * tel: 联系方式
         * provence: 省
         * city: 市
         * area: 区
         * detail_address: 详细地址
         */

        //验证规则
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'username.required' => '用户名不能为空',
            'tel.required' => '手机号码不能为空',
            'provence.required' => '请选择所在省',
            'city.required' => '请选择所在市',
            'area.required' => '请选择所在区',
            'detail_address.required' => '请填写详细地址',
        ]);
        //判断验证是否有错
        if ($validator->fails()) {
            return [
                'status' => "false",
                'message' => $validator->errors()->first(),
            ];
        }

        Address::create([
            'user_id' => Auth::user()->id,
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
            'is_default' => 0,
        ]);

        return [
            'status' => 'true',
            'message' => '添加成功',
        ];
    }

    // 指定地址接口
    public function address(Request $request)
    {
        $id = $request->id;
        $address = Address::where('id', $id)->first();
//        return $address;
        unset($address['user_id']);
        unset($address['created_at']);
        unset($address['updated_at']);
        unset($address['is_default']);
        $address['area'] = $address['county'];
        unset($address['county']);
        $address['detail_address'] = $address['address'];
        unset($address['address']);
        $address['provence'] = $address['province'];
        unset($address['province']);
        return $address;
    }

    // 保存修改地址接口
    public function editAddress(Request $request)
    {
        /**
         * id: 地址id,
         * name: 收货人
         * tel: 联系方式
         * provence: 省
         * city: 市
         * area: 区
         * detail_address: 详细地址
         */

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'username.required' => '用户名不能为空',
            'tel.required' => '手机号码不能为空',
            'provence.required' => '请选择所在省',
            'city.required' => '请选择所在市',
            'area.required' => '请选择所在区',
            'detail_address.required' => '请填写详细地址',
        ]);
        //判断验证是否有错
        if ($validator->fails()) {
            return [
                'status' => "false",
                'message' => $validator->errors()->first(),
            ];
        }

        $add = Address::where('id', $request->id)->first();
        $add->update([
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
        ]);

        return [
            "status" => "true",
            "message" => "修改成功"
        ];
    }

    // 保存购物车接口
    public function addCart(Request $request)
    {
        /**
         * goodsList: 商品列表
         * goodsCount: 商品数量
         */

        $goodList = $request->goodsList;
        $goodsCount = $request->goodsCount;
        if (!$goodList) {
            return [
                "status" => "false",
                "message" => "请选择商品"
            ];
        }
        $shops = ShoppingCart::where('user_id', Auth::user()->id)->get();
        foreach ($shops as $shop) {
            $shop->delete();
        }
        for ($i = 0; $i < count($goodList); $i++) {
            ShoppingCart::create([
                'user_id' => Auth::user()->id,
                'goods_id' => $goodList[$i],
                'amount' => $goodsCount[$i],
            ]);
        }

        return [
            "status" => "true",
            "message" => "添加成功"
        ];
    }

    // 获取购物车数据接口
    public function cart()
    {
        $shops = ShoppingCart::where('user_id', Auth::user()->id)->get();
        $goods_list = [];
        $totalCost = 0;
        foreach ($shops as $shop) {
            $goods = Menus::where('id', $shop->goods_id)->first();
            $goods_list[] = [
                'goods_id' => $goods->id,
                'goods_name' => $goods->goods_name,
                'goods_img' => $goods->goods_img,
                'amount' => $shop->amount,
                'goods_price' => $goods->goods_price,
            ];
            $totalCost += $shop->amount * $goods->goods_price;
        }

        $goods_lists['goods_list'] = $goods_list;
        $goods_lists['totalCost'] = $totalCost;
        return $goods_lists;
    }

    // 获得订单列表接口
    public function orderList()
    {
        /**
         * "order_code": 订单号
         * "order_birth_time": 订单创建日期
         * "order_status": 订单状态
         * "shop_id": 商家id
         * "shop_name": 商家名字
         * "shop_img": 商家图片
         * "goods_list": [{//购买商品列表
         * "goods_id": "1"//
         * "goods_name": "汉堡"
         * "goods_img": "http://www.homework.com/images/slider-pic2.jpeg"
         * "amount": 6
         * "goods_price": 10
         * }]
         */

        $orders = Order::where('user_id', Auth::user()->id)->get();
        $orderlist = [];
        foreach ($orders as $order) {
            $order_goods = Order_goods::where('order_id', $order->id)->get();
            $goods_list = [];
            $order_price = 0;
            foreach ($order_goods as $order_good) {
                $goods_list[] = [
                    'goods_id' => $order_good->goods_id,
                    'goods_name' => $order_good->menu->goods_name,
                    'goods_img' => $order_good->menu->goods_img,
                    'amount' => $order_good->amount,
                    'goods_price' => $order_good->goods_price,
                ];
                $order_price += $order_good->amount * $order_good->goods_price;
            }
            $orderlist[] = [
                'id' => $order->id,
                'order_code' => $order->sn,
                'order_birth_time' => date('Y-m-d H:i', strtotime($order->created_at)),
                'order_status' => '待支付',
                'shop_id' => $order->shop_id,
                'shop_name' => $order->shop->shop_name,
                'shop_img' => $order->shop->shop_img,
                'goods_list' => $goods_list,
                'order_price' => $order_price,
                'order_address' => $order->address,
            ];
        }
        return $orderlist;
    }

    // 添加订单接口
    public function addorder(Request $request)
    {
        /**
         * user_id    int    用户ID
         * shop_id    int    商家ID
         * sn    string    订单编号
         * province    string    省
         * city    string    市
         * county    string    县
         * address    string    详细地址.
         * tel    string    收货人电话
         * name    string    收货人姓名
         * total    decimal    价格
         * status    int    状态(-1:已取消,0:待支付,1:待发货,2:待确认,3:完成)
         * created_at    datetime    创建时间
         * out_trade_no    string    第三方交易号（微信支付需要）
         */

        DB::beginTransaction();
        try {
            $shops = ShoppingCart::where('user_id', Auth::user()->id)->get();
            $total = 0;
            $shop_id = '';
            foreach ($shops as $shop) {
                $total += ($shop->menu->goods_price) * ($shop->amount);
                $shop_id = $shop->menu->shop_id;
            }
            $address = Address::where('id', $request->address_id)->first();

            $order = Order::create([
                'user_id' => $address->user_id,
                'shop_id' => $shop_id,
                'sn' => date('Ymd') . str_random(10),
                'province' => $address->province,
                'city' => $address->city,
                'county' => $address->county,
                'address' => $address->address,
                'tel' => $address->tel,
                'name' => $address->name,
                'total' => $total,
                'status' => 0,
                'out_trade_no' => random_int(1000,99999),
            ]);

            foreach ($shops as $shop) {
                Order_goods::create([
                    'order_id' => $order->id,
                    'goods_id' => $shop->menu->id,
                    'amount' => $shop->amount,
                    'goods_name' => $shop->menu->goods_name,
                    'goods_img' => $shop->menu->goods_img,
                    'goods_price' => $shop->menu->goods_price,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::roolback();
            throw $e;
        }
        $tel = Auth::user()->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIY8hBBtqqvhel";
        $accessKeySecret = "ve7xkPfJYV2nzZMLH7EhF0xiLLF7Kc";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "任万琪";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140722140";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $shop_name = Shop::where('id',$shop_id)->first()->shop_name;
        $params['TemplateParam'] = Array(
            "name" => $shop_name,
//            "product" => "阿里通信"
        );


        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

         //初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        $r=\Illuminate\Support\Facades\Mail::raw('您有新的订单,请及时处理',function ($message){
            $message->subject('订单提醒');
            $message->to('a578393196@163.com');
            $message->from('a578393196@163.com','jay');
        });

        return [
            "status" => "true",
            "message" => "添加成功",
            "order_id" => $order->id,
        ];
    }

    // 获得指定订单接口
    public function order(Request $request)
    {
        /**
         * {
         * "id": "1",
         * "order_code": "0000001",
         * "order_birth_time": "2017-02-17 18:36",
         * "order_status": "代付款",
         * "shop_id": "1",
         * "shop_name": "上沙麦当劳",
         * "shop_img": "http://www.homework.com/images/shop-logo.png",
         * "goods_list": [{
         * "goods_id": "1",
         * "goods_name": "汉堡",
         * "goods_img": "http://www.homework.com/images/slider-pic2.jpeg",
         * "amount": 6,
         * "goods_price": 10
         * }, {
         * "goods_id": "1",
         * "goods_name": "汉堡",
         * "goods_img": "http://www.homework.com/images/slider-pic2.jpeg",
         * "amount": 6,
         * "goods_price": 10
         * }],
         * "order_price": 120,
         * "order_address": "北京市朝阳区霄云路50号 距离市中心约7378米北京市朝阳区霄云路50号 距离市中心约7378米"
         * }
         */
        $id = $request->id;
        $orders = Order::where('id', $id)->first();
        $shop = Shop::where('id', $orders->shop_id)->first();
        $order_goods = Order_goods::where('order_id', $orders->id)->get();
        $order = [];
        $order['id'] = $orders->id;
        $order['order_code'] = $orders->sn;
        $order['order_birth_time'] = date('Y-m-d H:i', strtotime($orders->created_at));
        $order['order_status'] = '待付款';
        $order['shop_id'] = $orders->shop_id;
        $order['shop_name'] = $shop->shop_name;
        $order['shop_img'] = $shop->shop_img;
        $goods_list = [];
        $order_price = 0;
        foreach ($order_goods as $order_good) {
            $goods_list[] = [
                'goods_id' => $order_good->goods_id,
                'goods_name' => $order_good->goods_name,
                'goods_img' => $order_good->goods_img,
                'amount' => $order_good->amount,
                'goods_price' => $order_good->goods_price,
            ];
            $order_price += $order_good->amount * $order_good->goods_price;
        }
        $order['goods_list'] = $goods_list;
        $order['order_price'] = $order_price;
        $order['order_address'] = $orders->address;
        return $order;
    }

    //修改密码接口
    public function changePassword(Request $request)
    {
        if (Hash::check($request->oldPassword, auth()->user()->password)) {

//            auth()->user()->update(['password' => bcrypt($request->newPassword)]);
            Auth::user()->update([
                'password' => bcrypt($request->newPassword)
            ]);

            return json_encode([
                "status" => "true",
                "message" => "修改成功"]);
        } else {
            return json_encode([
                "status" => "false",
                "message" => "修改失败"]);
        }
    }

    //忘记密码接口
    public function forgetPassword(Request $request)
    {
        /**
         * tel: 手机号
         * sms: 短信验证码
         * password: 密码
         */

        $tel = $request->tel;
        $sms = $request->sms;
        $password = $request->password;

        $member = Member::where('tel', $tel)->first();
        if ($member) {
            if ($request->sms == Redis::get('code' . $tel)) {
                $member->update(['password' => bcrypt($request->password)]);
                return json_encode([
                    "status" => "true",
                    "message" => "重置成功"
                ]);
            } else {
                return json_encode([
                    "status" => "false",
                    "message" => "验证码错误"
                ]);
            }
        } else {
            return json_encode([
                "status" => "false", "message" => "号码不存在"
            ]);
        }
    }


}









