<?php


namespace app\model;


use think\Model;

class UserBuy extends Model
{
    protected $pk='id';

    public function setSummaryAttr($value){
        return trim($value);
    }

    public function book(){
        return $this->belongsTo('book');
    }
}