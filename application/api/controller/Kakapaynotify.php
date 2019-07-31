<?php


namespace app\api\controller;


use app\model\UserFinance;
use app\model\UserOrder;
use app\service\PromotionService;
use think\Controller;
use think\Request;
use Util\Kakapay;

class Kakapaynotify extends Controller
{
    protected $kakapayUtil;

    protected function initialize()
    {
        $this->kakapayUtil = new Kakapay();
    }

    public function index(Request $request)
    {
        $para = $request->param();
        $meid = $para['meid'];
        $meorder = str_replace('xwx_order_', '', $para['meorder']);
        $ordernum = $para['ordernum'];
        $money = $para['amount'];
        $tradestate = $para['tradestate'];
        $paytime = $para['paytime'];
        $attach = $para['attach'];

        $status = 0;
        if (!$this->kakapayUtil->checkSign($para)) {
            return 'sign error';
        } else if ($tradestate === 'SUCCESS') {
            $status = 1;
            $order = UserOrder::get($meorder); //通过返回的订单id查询数据库
            if ($order) {
                if ((int)$order->status == 0) {
                    $order->money = $money;
                    $order->update_time = $paytime; //云端处理订单时间戳
                    $order->status = $status;
                    $order->isupdate(true)->save(); //更新订单

                    $userFinance = new UserFinance();
                    $userFinance->user_id = $order->user_id;
                    $userFinance->money = $order->money;
                    $userFinance->usage = 1; //用户充值
                    $userFinance->summary = '卡卡支付';
                    $userFinance->save(); //存储用户充值数据

                    $promotionService = new PromotionService();
                    $promotionService->rewards($order->user_id, $order->money, 1); //调用推广处理函数
                }
            }
            return 'success';
        } else {
            return 'fail';
        }
    }
}