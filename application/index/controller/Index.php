<?php

namespace app\index\controller;

use app\model\Banner;
use app\model\Author;
use think\Db;

class Index extends Base
{
    protected $bookService;

    protected function initialize()
    {
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $pid = input('pid');
        if ($pid) { //如果有推广pid
            cookie('xwx_promotion', $pid); //将pid写入cookie
        }
        $banners = cache('banners_homepage');
        if (!$banners) {
            $banners = Db::query('SELECT * FROM xwx_banner WHERE id >= 
((SELECT MAX(id) FROM xwx_banner)-(SELECT MIN(id) FROM xwx_banner)) * RAND() + (SELECT MIN(id) FROM xwx_banner) LIMIT 5');
            cache('banners_homepage', $banners, null, 'redis');
        }

        $redis = new_redis();
        $hots = $redis->zRevRange($this->redis_prefix . 'hot_books', 0, 12, true);
        $hot_books = array();
        foreach ($hots as $k => $v) {
            $hot_books[] = json_decode($k, true);
        }

        $newest = cache('newest_homepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks('last_time', '1=1', 14);
            cache('newest_homepage', $newest, null, 'redis');
        }

        $ends = cache('ends_homepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks('create_time', [['end', '=', '1']], 14);
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
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'most_charged' => $most_charged
        ]);
        if (!$this->request->isMobile()) {
            $tags = \app\model\Tags::all();
            $this->assign('tags', $tags);
        }
        return view($this->tpl);
    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = new_redis();
        $redis->zIncrBy($this->redis_prefix . 'hot_search:', 1, $keyword);
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search:', 1, 4, true);
        $hot_search = array();
        foreach ($hot_search_json as $k => $v) {
            $hot_search[] = $k;
        }
        $books = cache('searchresult:' . $keyword);
        if (!$books) {
            $books = $this->bookService->search($keyword);
            cache('searchresult:' . $keyword, $books, null, 'redis');
        }
        foreach ($books as &$book) {
            $author = Author::get($book['author_id']);
            $book['author'] = $author;
        }
        $this->assign([
            'books' => $books,
            'count' => count($books),
            'hot_search' => $hot_search
        ]);
        return view($this->tpl);
    }

    public function bookshelf()
    {
        $this->assign('header_title', '书架');
        return view($this->tpl);
    }
}

