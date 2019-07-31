<?php


namespace app\model;


use think\Model;

class UserFinance extends Model
{
    protected $pk='id';

    public function setSummaryAttr($value){
        return trim($value);
    }
}