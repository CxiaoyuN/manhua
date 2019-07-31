<?php


namespace app\model;


use think\Model;

class Clicks extends Model
{
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function book()
    {
        return $this->belongsTo('Book');
    }
}