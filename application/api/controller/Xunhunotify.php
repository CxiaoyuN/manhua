<?php


namespace app\api\controller;


use app\model\UserFinance;
use app\model\UserOrder;
use app\service\PromotionService;
use think\Controller;
use think\facade\Cache;
use think\Request;
use util\Xunhupay;

class Xunhunotify extends Controller
{
    protected $xunhuUtil;

    protected function initialize()
    {
        $this->xunhuUtil = new Xunhupay();
    }

    public function index(Request $request)
    {
        $appid = trim(config('payment.xunhupay.appid'));
        $appsecret = trim(config('payment.xunhupay.appkey'));

        $data = $request->param();
        foreach ($data as $k => $v) {
            $data[$k] = stripslashes($v);
        }
        if (!isset($data['hash']) || !isset($data['trade_order_id'])) {
            return 'failed';
        }

//自定义插件ID,请与支付请求时一致
        if (isset($data['plugins']) && $data['plugins'] != 'racooncms') {
            return 'failed';;
        }

//APP SECRET
        $appkey = $appsecret;
        $hash = $this->xunhuUtil->generate_xh_hash($data, $appkey);
        if ($data['hash'] != $hash) {
            //签名验证失败
            return 'failed';
        }

//商户订单ID
        $order_id = str_replace('xwx_order_', '', $data['trade_order_id']);
        $type = 0;
        switch ($data['type']) {
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
        if ($data['status'] == 'OD') {
            $status = 1;
        } else {
            return 'failed';
        }
        $order = UserOrder::get($order_id); //通过返回的订单id查询数据库
        if ($order) {
            $order->money = $data['money'];
            $order->pay_type = $type; //支付类型
            $order->update_time = time(); //云端处理订单时间戳
            $order->status = $status;
            $order->isupdate(true)->save(); //更新订单

            if ($status == 1) { //如果已支付，则更新用户财务信息
                $userFinance = new UserFinance();
                $userFinance->user_id = $order->user_id;
                $userFinance->money = $order->money;
                $userFinance->usage = 1; //用户充值
                $userFinance->summary = '虎皮椒支付';
                $userFinance->save(); //存储用户充值数据

                $promotionService = new PromotionService();
                $promotionService->rewards($order->user_id, $order->money, 1); //调用推广处理函数
            }
            Cache::clear('pay'); //清除支付缓存
        }

//以下是处理成功后输出，当支付平台接收到此消息后，将不再重复回调当前接口
        return 'success';
    }
}