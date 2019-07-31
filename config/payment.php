<?php
return [
    'zhapay' => [ //幻兮支付，官网地址:https://www.zhapay.com/
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 2, 'code'=>1, 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 1, 'code'=>3,'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'vkzf' => [ //快支付，官网地址：https://www.vkzf.cn/
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 'alipay','code'=>1,  'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 'wxpay','code'=>3, 'img' => 'weixin', 'title' => '微信支付'],
            ['type' => 'qqpay', 'code'=>2,'img' => 'qq', 'title' => 'QQ钱包']
        ]
    ],
    'kakapay' => [
        'meid' => '2019260',
        'mekey' => '4cc513851d754fd7c9a8d1f37c6a13a2',
        'channel' => [
            ['type' => 'aliwap', 'code'=>1,'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 'wxwap', 'code'=>3,'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'vip' => [  //设置vip天数及相应的价格
        ['month' => 1, 'price' => 5],
        ['month' => 6, 'price' => 100],
        ['month' => 12, 'price' => 400]
    ],
    'money' => [1,5,10,30, 50], //设置支付金额
    'promotional_rewards_rate' => 0.1, //设置充值提成比例，必须是小数
    'reg_rewards' => 1 //注册奖励金额，单位是元
];