<?php


namespace app\app\controller;


use app\model\Chapter;
use app\model\UserBuy;
use think\Db;

class Chapters extends Base
{
    public function getList()
    {
        $book_id = input('book_id');
        $chapters = Chapter::where('book_id', '=', $book_id)->select();
        $result = [
            'success' => 1,
            'chapters' => $chapters
        ];
        return json($result);
    }

    public function detail()
    {
        $id = input('id');
        $chapter = Chapter::with(['photos' => function ($query) {
            $query->order('pic_order');
        }], 'book')->cache('chapter:' . $id, 600, 'redis')->find($id);
        $flag = true;
        if ($chapter->chapter_order >= $chapter->book->start_pay) { //如果本章是本漫画设定的付费章节
            $flag = false;
        }
        $uid = session('xwx_user_id');
        if (!is_null($uid)) { //如果用户已经登录
            $vip_expire_time = session('xwx_vip_expire_time'); //用户等级
            $time = $vip_expire_time - time(); //计算vip用户时长
            if ($time > 0) { //如果是vip会员且没过期，则可以不受限制
                $flag = true;
            } else { //如果不是会员，则判断用户是否购买本章节
                $map[] = ['user_id', '=', $uid];
                $map[] = ['chapter_id', '=', $id];
                $userBuy = UserBuy::where($map)->find();
                if (!is_null($userBuy)) { //说明用户购买了本章节
                    $flag = true;
                }
            }
        }

        if ($flag) {
            $book_id = $chapter->book_id;

            $uid = session('xwx_user_id');
            if ($uid) {
                $redis = new_redis();
                $arr = [
                    'book_id' => $chapter->book->id,
                    'cover_url' => $chapter->book->cover_url,
                    'chapter_id' => $chapter->id,
                    'chapter_name' => $chapter->chapter_name,
                    'book_name' => $chapter->book->book_name,
                    'end' => $chapter->book->end,
                    'last_time' => $chapter->book->last_time
                ];
                $redis->hSet($this->redis_prefix . ':history:' . $uid, $chapter->book->id, json_encode($arr)); //利用hash表，保证用户及book的唯一性
                $redis->rPush($this->redis_prefix . ':history:log', $chapter->book->id); //将key记录进队列，用于日后按顺序删除
                if ($redis->hLen($this->redis_prefix . ':history:' . $uid) > 10) {
                    $key = $redis->lPop($this->redis_prefix . ':history:log'); //拿到队列最早的key
                    $redis->hDel($this->redis_prefix . ':history:' . $uid, $key); //按照key从hash表删除
                }
            }
            $prev = cache('chapter_prev:' . $id);
            if (!$prev) {
                $prev = Db::query(
                    'select * from ' . $this->prefix . 'chapter where book_id=' . $book_id . ' and chapter_order<' . $chapter->chapter_order . ' order by id desc limit 1');
                cache('chapter_prev:' . $id, $prev, null, 'redis');
            }
            $next = cache('chapter_next:' . $id);
            if (!$next) {
                $next = Db::query(
                    'select * from ' . $this->prefix . 'chapter where book_id=' . $book_id . ' and chapter_order>' . $chapter->chapter_order . ' order by id limit 1');
                cache('chapter_next:' . $id, $next, null, 'redis');
            }

            $result = [
                'success' => 1,
                'chapter' => $chapter
            ];

        } else {
            $result = [
                'success' => 0,
                'msg' => '没有购买此章节'
            ];
        }
        return json($result);
    }
}