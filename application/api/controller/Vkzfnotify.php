<?php


namespace app\api\controller;

use app\model\UserFinance;
use app\model\UserOrder;
use think\Controller;
use think\Request;
use util\Vkzf;
use app\service\PromotionService;

class Vkzfnotify extends Controller
{
    protected $vkzfUtil;

    protected function initialize()
    {
        $this->vkzfUtil = new Vkzf();
    }

    public function index(Request $request)
    {
        $para = $request->param();
        $sign = input('sign');
        $isSgin = $this->vkzfUtil->verifyNotify($request, $sign);
        if ($isSgin) {
            $order_id = str_replace('xwx_order_', '', $para['out_trade_no']);
            $type = 0;
            switch ($para['type']) {
                case 'alipay':
                    $type = 1;
                    break;
                case 'qqpay':
                    $type = 2;
                    break;
                case 'wxpay':
                    $type = 3;
                    break;
            }
            $status = 0;
            if ($para['trade_status'] == 'TRADE_SUCCESS') {
                $status = 1; //如果已支付，则更新用户财务信息
                $order = UserOrder::get($order_id); //通过返回的订单id查询数据库
                if ($order) {
                    if ((int)$order->status == 0) { //如果是未完成订单，才进行更新
                        $order->status = $status;
                        $order->money = $para['money'];
                        $order->update_time = time(); //云端处理订单时间戳
                        $order->isupdate(true)->save(); //更新订单

                        $userFinance = new UserFinance();
                        $userFinance->user_id = $order->user_id;
                        $userFinance->money = $order->money;
                        $userFinance->usage = 1; //用户充值
                        $userFinance->summary = '快支付';
                        $userFinance->save(); //存储用户充值数据

                        $promotionService = new PromotionService();
                        $promotionService->rewards($order->user_id, $order->money, 1); //调用推广处理函数

                    }
                }
            }

            return json(['code' => true, 'status' => 'success']);
        }
    }
}