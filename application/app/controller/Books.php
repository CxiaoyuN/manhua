<?php


namespace app\app\controller;


use app\model\Author;
use app\model\Book;
use app\model\Comments;
use app\model\UserBook;
use think\Db;
use think\facade\App;

class Books extends Base
{
    protected $bookService;

    public function initialize()
    {
        parent::initialize();
        $this->bookService = new \app\service\BookService();
    }

    public function getNewest(){
        $newest = cache('newest_homepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks('last_time', '1=1', 14);
            cache('newest_homepage', $newest, null, 'redis');
        }
        $result = [
            'success' => 1,
            'newest' => $newest
        ];
        return json($result);
    }

    public function getHot(){
        $redis = new_redis();
        $hots = $redis->zRevRange($this->redis_prefix . 'hot_books', 0, 12, true);
        $hot_books = array();
        foreach ($hots as $k => $v) {
            $hot_books[] = json_decode($k, true);
        }
        $result = [
            'success' => 1,
            'hot' => $hot_books
        ];
        return json($result);
    }

    public function getEnds(){
        $ends = cache('ends_homepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks('create_time', [['end', '=', '1']], 14);
            cache('ends_homepage', $ends, null, 'redis');
        }
        $result = [
            'success' => 1,
            'ends' => $ends
        ];
        return json($result);
    }

    public function getMostCharged(){
        $most_charged = cache('most_charged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook();
            foreach ($arr as $item) {
                $most_charged[] = $item['book'];
            }
            cache('most_charged', $most_charged, null, 'redis');
        }
        $result = [
            'success' => 1,
            'most_charged' => $most_charged
        ];
        return json($result);
    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = new_redis();
        $redis->zIncrBy($this->redis_prefix . 'hot_search:', 1, $keyword);
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 1, 4, true);
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

        $result = [
            'success' => 1,
            'data' => [
                'books' => $books,
                'count' => count($books),
                'hot_search' => $hot_search
            ]
        ];
        return json($result);
    }

    public function detail(){
        $id = input('id');
        $book = cache('book:' . $id);
        $tags = cache('tags:book:' . $id );
        if ($book ==false) {
            $book = Book::with(['chapters' => function($query){
                $query->order('chapter_order');
            }])->find($id);
            $tags = [];
            if(!empty($book->tags) || is_null($book->tags)){
                $tags = explode('|', $book->tags);
            }
            cache('book:' . $id, $book,null,'redis');
            cache('tags:book:' . $id , $tags,null,'redis');
        }

        $start = cache('book_start:' . $id);
        if ($start == false) {
            $db = Db::query('SELECT id FROM '.$this->prefix.'chapter WHERE book_id = ' . $id . ' ORDER BY id LIMIT 1');
            $start = $db ? $db[0]['id'] : -1;
            cache('book_start:' . $id, $start,null,'redis');
        }

        $result = [
            'success' => 1,
            'book' => $book,
            'tags' => $tags,
            'start' => $start
        ];
        return json($result);
    }

    public function isfavor(){
        $id = input('id');
        $uid=input('uid');
        $isfavor = 0;
        $where[] = ['user_id','=',$uid];
        $where[] = ['book_id','=',$id];
        $userfavor = UserBook::where($where)->find();
        if (!is_null($userfavor)){ //未收藏本漫画
            $isfavor = 1;
        }
        $result = [
            'success' => 1,
            'isfavor' => $isfavor
        ];
        return json($result);
    }

    public function getComments(){
        $book_id = input('book_id');
        $comments = cache('comments:'.$book_id);
        if (!$comments){
            $comments = Comments::where('book_id','=',$book_id)->order('create_time','desc')
                ->limit(0,5)->select();
            cache('comments:'.$book_id,$comments);
        }
        $dir = App::getRootPath().'public/static/upload/comments/'.$book_id;
        foreach ($comments as &$comment){
            $comment['content'] = file_get_contents($dir.'/'.$comment->id.'.txt');
        }
    }
}