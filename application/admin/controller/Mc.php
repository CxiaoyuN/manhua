<?php


namespace app\admin\controller;


use app\model\Message;
use app\model\User;
use think\facade\Env;

class Mc extends BaseAdmin
{
    public function message()
    {
        $data = Message::where('type', '=', 0);
        $msgs = $data->paginate(5, true,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ])->each(function ($item, $key) {
            $dir = Env::get('root_path') . '/public/static/upload/message/' . $item['id'] . '/';
            $item['content'] = file_get_contents($dir . 'msg.txt'); //获取用户留言内容
            $user = User::get($item['msg_key']);//根据留言用户ID查出用户
            $item['user'] = $user;
        });
        $this->assign([
            'msgs' => $msgs,
            'count' => $data->count()
        ]);
        return view();
    }

    public function reply()
    {
        $id = input('id');
        $dir = Env::get('root_path') . '/public/static/upload/message/' . $id . '/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        if ($this->request->isPost()) {
            $content = input('content');
            $msg = new Message();
            $msg->msg_key = $id; //受回复消息的id
            $msg->type = 1;//类型是回复
            $res = $msg->save();
            if ($res) {
                $savename = $dir . $msg->id . '.txt'; //回复消息的存储方式为id.txt
                file_put_contents($savename, $content);
            }
            $this->success('回复成功');
        }
        $msg = Message::get($id);
        $msg->content = file_get_contents($dir . 'msg.txt');
        $map[] = ['msg_key', '=', $id]; //key为受回复消息id
        $map[] = ['type', '=', 1]; //类型为回复
        $replys = Message::where($map)->select();
        foreach ($replys as &$reply) {
            $reply['content'] = file_get_contents($dir . $reply->id . '.txt');
        }
        $this->assign([
            'msg' => $msg,
            'replys' => $replys
        ]);
        return view();
    }

    public function delete($id){
        Message::destroy($id);
        return ['err' => '0','msg' => '删除成功'];
    }
}