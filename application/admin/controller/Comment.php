<?php


namespace app\admin\controller;


use app\model\Comments;
use app\model\User;
use think\facade\Env;

class Comment extends BaseAdmin
{
    public function index()
    {
        $map = array();
        $uid = input('uid');
        if ($uid){
            $map[] = ['user_id','=',$uid];
        }

        $book_id = input('book_id');
        if ($book_id){
            $map[] = ['book_id','=',$book_id];
        }
        $data = Comments::where($map)->with('book,user');
        $comments = $data->order('id', 'desc')->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ])->each(function ($item, $key) {
            $dir = Env::get('root_path') . '/public/static/upload/comments/' . $item->book->id . '/';
            $item['content'] = file_get_contents($dir . $item->id . '.txt'); //获取用户评论内容
        });
        $this->assign([
            'comments' => $comments,
            'count' => $data->count()
        ]);
        return view();
    }
}