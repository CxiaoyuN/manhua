<?php


namespace app\model;


use think\Model;

class UserOrder extends Model
{
    protected $pk='id';

    public function setSummaryAttr($value){
        return trim($value);
    }
}