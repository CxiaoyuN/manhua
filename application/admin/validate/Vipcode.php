<?php


namespace app\admin\validate;


use think\Validate;

class Vipcode extends Validate
{
    protected $rule = [
        'day' => 'integer',
        'num' => 'integer',
    ];


    protected $message = [
        'day' => 'vip天数必须是整数',
        'num' => '生成个数必须是整数'
    ];
}