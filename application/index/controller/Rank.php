<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/9
 * Time: 11:47
 */

namespace app\index\controller;


class Rank extends Base
{
    protected $bookService;

    protected function initialize()
    {
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $redis = new_redis();
        $hots = $redis->zRevRange($this->redis_prefix . 'hot_books', 0, 12, true);
        $hot_books = array();
        foreach ($hots as $k => $v) {
            $hot_books[] = json_decode($k, true);
        }

        $newest = cache('newest_homepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks('create_time', '1=1', 14);
            cache('newest_homepage', $newest, null, 'redis');
        }
        $ends = cache('ends_homepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks('update_time', [['end', '=', '1']], 14);
            cache('ends_homepage', $ends, null, 'redis');
        }

        $most_charged = cache('most_charged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook();
            foreach ($arr as $item){
                $most_charged[] = $item['book'];
            }
            cache('most_charged', $most_charged, null, 'redis');
        }

        $this->assign([
            'list' => [
                ['keyword' => $newest, 'title' => '新书榜'],
                ['keyword' => $hot_books, 'title' => '人气榜'],
                ['keyword' => $most_charged, 'title' => '充值榜']
            ],
            'most_charged' => $most_charged,
            'header_title' => '排行榜'
        ]);

        return view($this->tpl);
    }
}