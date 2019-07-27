<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2018/10/3
 * Time: 23:39
 */

namespace app\service;

use app\index\controller\Base;
use app\model\Book;
use app\model\Chapter;
use app\model\Clicks;
use app\model\UserBuy;
use think\Db;

class BookService extends Base
{
    public function getPagedBooks($order = 'id', $where = '1=1', $num = 5)
    {
        $type = 'util\Page';
        if ($this->request->isMobile()) {
            $type = 'util\MPage';
        }
        $books = Book::where($where)->with('chapters')->order($order, 'desc')
            ->paginate($num, false,
                [
                    'query' => request()->param(),
                    'type' => $type,
                    'var_page' => 'page',
                ]);
        foreach ($books as &$book) {
            $book['chapter_count'] = count($book->chapters);
        }
        return $books;
    }

    public function getPagedBooksAdmin($status, $where = '1=1')
    {
        if ($status == 1) { //正常用户
            $data = Book::where($where);
        } else {
            $data = Book::onlyTrashed()->where($where);
        }
        $books = $data->with('author,chapters')->order('id', 'desc')
            ->paginate(5, false,
                [
                    'query' => request()->param(),
                    'type' => 'util\AdminPage',
                    'var_page' => 'page',
                ]);

        return [
            'books' => $books,
            'count' => $data->count()
        ];
    }

    public function getBooks($order = 'update_time', $where = '1=1', $num = 6)
    {
        $books = Book::where($where)->with('author,chapters')
            ->limit($num)->order($order, 'desc')->select();
        foreach ($books as &$book) {
            $book['chapter_count'] = count($book->chapters);
            $book['taglist'] = explode('|', $book->tags);
        }
        return $books;
    }

    public function getMostChargedBook()
    {
        $data = UserBuy::with(['book' => ['author']])->field('book_id,sum(money) as sum')
            ->group('book_id')->select();
        foreach ($data as &$item) {
            $chapters = Chapter::where('book_id', '=', $item['book_id'])->select();
            $book = $item['book'];
            $book['chapter_count'] = count($chapters);
            $book['taglist'] = explode('|', $item['book']['tags']);
            $item['book'] = $book;
        }
        $arr = $data->toArray();
        array_multisort(array_column($arr, 'sum'), SORT_DESC, $arr);
        return $arr;
    }

    public function getBooksById($ids)
    {
        if (empty($ids) || strlen($ids) <= 0) {
            return [];
        }
        $exp = new \think\db\Expression('field(id,' . $ids . ')');
        $books = Book::where('id', 'in', $ids)->with('author,chapters')->order($exp)->select();
        foreach ($books as &$book) {
            $book['chapter_count'] = count($book->chapters);
        }
        return $books;
    }

    public function getRecommand($tags)
    {
        $arr = explode('|', $tags);
        $map = array();
        foreach ($arr as $value) {
            $map[] = ['tags', 'like', '%' . $value . '%'];
        }
        $books = Book::where($map)->limit(10)->select();
        foreach ($books as &$book) {
            $book['chapter_count'] = Chapter::where('book_id', '=', $book['id'])->count();
        }
        return $books;
    }

    public function getRandBooks()
    {
        $books = Db::query('SELECT ad1.id,book_name,summary,cover_url FROM ' . $this->prefix . 'book AS ad1 JOIN 
(SELECT ROUND(RAND() * ((SELECT MAX(id) FROM ' . $this->prefix . 'book)-(SELECT MIN(id) FROM ' . $this->prefix . 'book))+(SELECT MIN(id) FROM ' . $this->prefix . 'book)) AS id)
 AS t2 WHERE ad1.id >= t2.id ORDER BY ad1.id LIMIT 10');
        foreach ($books as &$book) {
            $book['chapter_count'] = Chapter::where('book_id', '=', $book['id'])->count();
        }
        return $books;
    }

    public function getByName($name)
    {
        return Book::where('book_name', '=', $name)->find();
    }

    public function getRand($num)
    {
        $books = Db::query('SELECT a.id,a.book_name,a.summary,a.end,b.author_name FROM 
(SELECT ad1.id,book_name,summary,end,author_id,cover_url
FROM ' . $this->prefix . 'book AS ad1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM ' . $this->prefix . 'book)-(SELECT MIN(id) FROM ' . $this->prefix . 'book))+(SELECT MIN(id) FROM ' . $this->prefix . 'book)) AS id)
 AS t2 WHERE ad1.id >= t2.id ORDER BY ad1.id LIMIT ' . $num . ') as a
 INNER JOIN author as b on a.author_id = b.id');
        return $books;
    }

    public function getNewest()
    {
        return Book::with('author')->limit(3)->order('update_time', 'desc')->select();
    }

    public function search($keyword)
    {
        return Db::query(
            "select * from " . $this->prefix . "book where match(book_name,summary,author_name,nick_name) 
            against ('" . $keyword . "' IN NATURAL LANGUAGE MODE)"
        );
    }

    public function getHotBooks($date = '1900-01-01', $num = 10)
    {
        $data = Db::query("SELECT book_id,SUM(clicks) as clicks FROM xwx_clicks WHERE cdate>=:cdate
 GROUP BY book_id ORDER BY clicks DESC LIMIT :num", ['cdate' => $date, 'num' => $num]);
        $books = array();
        foreach ($data as $val) {
            $book = Book::with('chapters')->find($val['book_id']);
            if ($book){
                $book['chapter_count'] = count($book->chapters);
                $book['taglist'] = explode('|', $book->tags);
                array_push($books,$book);
            }
        }
        return $books;
    }
}