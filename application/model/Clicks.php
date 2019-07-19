<?php


namespace app\model;


use think\Model;

class Clicks extends Model
{
    protected $pk = 'id';

    public function book()
    {
        return $this->belongsTo('Book');
    }
}