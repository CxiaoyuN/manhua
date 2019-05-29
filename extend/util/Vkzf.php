<?php


namespace Util;


class Vkzf
{
    public function submit($order_id, $money, $pay_type = 'alipay')
    {
        $parameter = array(
            "pid" => trim(config('payment.vkzf.appid')),
            "type" => $pay_type,
            "notify_url" => config('site.url') . '/vkzfnotify',//通知地址,
            "return_url" => config('site.url') . '/feedback',//跳转地址,
            "out_trade_no" => $order_id, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
            "name" => config('site.site_name') . '充值',
            "money" => $money
        );

        $para_filter = $this->paraFilter($parameter);
        $para_sort = $this->argSort($para_filter); //对待签名参数数组排序
        $mysign = $this->buildRequestMysign($para_sort); //生成签名结果
        $urls = 'https://pay.qfme.top/submit.php?'; //初始化URL参数为空

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper('MD5');

        $para_str = $this->createLinkstringUrlencode($para_sort);

        $urls = $urls . $para_str;
        header("Location:{$urls}"); //跳转到支付页面
    }

    public function paraFilter($para)
    {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * $para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     * $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    public function buildRequestMysign($para_sort)
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $mysign = $this->md5Sign($prestr, config('payment.vkzf.appkey'));
        return $mysign;
    }

    /**
     * 签名字符串
     * $prestr 需要签名的字符串
     *  $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstring($para)
    {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }

        //去掉最后一个&字符
        $arg = rtrim($arg,'&');

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstringUrlencode($para)
    {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        //去掉最后一个&字符
        $arg = rtrim($arg,'&');

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 验证签名
     * $prestr 需要签名的字符串
     * $sign 签名结果
     * $key 私钥
     * return 签名结果
     */
    public function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if($mysgin == $sign) {
            return true;
        }
        else {
            return false;
        }
    }

    public function verifyNotify($request,$sign){
        if(empty($request)) {//判断POST来的数组是否为空
            return false;
        }
        else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($_GET, $sign);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'true';
            //if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}

            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i",$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        $isSgin = $this->md5Verify($prestr, $sign, config('payment.vkzf.appkey'));

        return $isSgin;
    }
}