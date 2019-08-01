<?php


namespace Util;


class Kakapay
{
    // 支付网关
    private $gateway_pay = 'http://47.100.17.66/pay';
    // 查询网关
    private $gateway_query = 'http://47.100.17.66/query';
    // MEID
    private $meid = '';
    // MEKEY
    private $mekey = '';
    // 订单必填字段
    private $order_require = array('apicode', 'meorder', 'amount', 'notifyurl', 'returnurl', 'subject', 'clientip');
    // 订单选填字段
    private $order_optional = array('attach', 'noback', 'notifytype');

    public function __construct()
    {
        $this->meid = trim(config('payment.kakapay.meid'));
        $this->mekey = trim(config('payment.kakapay.mekey'));

        if (empty($this->meid)) {
            exception('meid should not be NULL!', 10006);
        }
        if (empty($this->mekey)) {
            exception('mekey should not be NULL!', 10006);
        }
    }

    public function submit($order_id, $money, $pay_type)
    {
        $order = array(
            "apicode" => $pay_type, // 支付类型，此处可选项以网站对接文档为准，微信公众号：wxmp，微信H5网页：wxwap，微信扫码：wxscan，支付宝WAP网页：aliwap，支付宝PC网页：alipc，详细参考API
            "meorder" => $order_id, // 商户订单号
            "amount" => $money, // 支付金额，单位元
            "notifyurl" => config('site.url') . '/kakapaynotify', // 异步回调，支付结果以异步为准
            "returnurl" => config('site.url') . '/feedback', // 同步回调，不作为最终支付结果为准，请以异步回调为准
            "subject" => config('site.site_name') . '漫画充值', // 商品名称
            "clientip" => $this->getIp() // IP
        );

        //$params = $this->setOrder($order);
        $pay = $this->payCode($order);
        // 验证返回信息
        if ($pay["result_code"] == 'SUCCESS') {
            if ($pay['paytype'] == 'QR') {
                // 扫码支付
                exit('<p style="width:100%;text-align:center;padding-top:200px">
                <img src="' . $pay['payurl'] . '" style="width:200px;height:200px;"/>
                <br><br>
                <a href="' . $pay['jumpurl'] . '">已完成支付</a>&nbsp;>&nbsp;<a href="index.php">重新支付</a></p>');
            } elseif ($pay['paytype'] == 'LINK') {
                // 转入支付页面
                header('Location:' . $pay['payurl']);
                exit;
            }
        } else {
            // 输出错误信息
            echo $pay['err_msg'];
            exit;
        }
    }

    public function payCode($order)
    {
        $order['meid'] = $this->meid;
        $order['sign'] = $this->sign($order);
        // 直接跳转
        if (!empty($order['noback'])) {
            $url = $this->gateway_pay . '?' . http_build_query($order);
            header('Location:' . $url);
            exit;
        }
        // 调用支付接口，获取支付链接
        try {
            $res = curl($this->gateway_pay, $order);
        } catch (Exception $e) {
            return array('result_code' => 'FAIL', 'err_msg' => $e->getMessage());
        }
        $resArray = json_decode($res, true);
        // 转换错误，原样返回
        if (empty($resArray)) {
            return array('result_code' => 'FAIL', 'err_msg' => $res);
        }
        return $resArray;
    }

    public function sign($array)
    {
        if ( isset( $array['notifyurl'] ) ) {
            return md5( $array['meid'] . $array['meorder'] . $array['amount'] . $array['notifyurl'] . $this -> mekey );
        } else {
            return md5( $array['meid'] . $array['meorder'] . $array['amount'] . $this -> mekey );
        }
//        ksort($array);
//        $prestr = $this->getSignContent($array); //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
//        $prestr = $prestr . $this->mekey; //把拼接后的字符串再与安全校验码直接连接起来
//        $mysgin = md5($prestr); //把最终的字符串加密，获得签名结果
//        return $mysgin;
    }

    private function getSignContent($params)
    {
        $arg = '';
        foreach ($params as $key => $val) {
            $arg .= $key . '=' . $val . '&';
        }

        $arg = substr($arg, 0, -1); //去掉最后一个&字符
        unset ($key, $val);
        return $arg;
    }

    // 此方法对value做urlencode
    private function getSignContentUrlencode($params)
    {
        $arg = '';
        while (list ($key, $val) = each($params)) {
            $arg .= $key . '=' . urlencode($val) . '&';
        }
        $arg = substr($arg, 0, -1); //去掉最后一个&字符
        unset ($key, $val);
        return $arg;
    }

    private function getIp()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        } else {
            $cip = '';
        }
        preg_match("/[\d\.]{7,15}/", $cip, $cips);
        $cip = isset($cips[0]) ? $cips[0] : 'unknown';
        unset($cips);
        return $cip;
    }

    public function checkSign( $params ) {
        $sign = $params['sign'];
        unset( $params['sign'] );
        return $sign === $this -> sign( $params );
    }
}