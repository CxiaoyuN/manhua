<?php
return [
    'zhapay' => [ //幻兮支付，官网地址:https://www.zhapay.com/
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 2, 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 1, 'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'vkzf' => [ //快支付，官网地址：https://www.vkzf.cn/
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 'alipay', 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 'wxpay', 'img' => 'weixin', 'title' => '微信支付'],
            ['type' => 'qqpay', 'img' => 'qq', 'title' => 'QQ钱包']
        ]
    ],
    'xunhupay' => [ //虎皮椒，官网地址：https://www.xunhupay.com
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 'alipay', 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 'wechat', 'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'vip' => [  //设置vip天数及相应的价格
        ['month' => 1, 'price' => 5],
        ['month' => 6, 'price' => 100],
        ['month' => 12, 'price' => 400]
    ],
    'money' => [1, 10, 30, 100], //设置支付金额
    'promotional_rewards_rate' => 0.1, //设置充值提成比例，必须是小数
    'reg_rewards' => 1 //注册奖励金额，单位是元
];