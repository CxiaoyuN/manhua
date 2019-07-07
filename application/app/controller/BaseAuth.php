<?php


namespace app\app\controller;


use think\Controller;

class BaseAuth extends Controller
{
    protected $prefix;
    protected $redis_prefix;
    protected $uid;

    protected function initialize()
    {
        $token = $this->request->param('token');
        $time = $this->request->param('time');
        if (time() - $time > 180) {
            echo json_encode(['success' => 0, 'msg' => '非法请求1']);
            exit();
        }
        $key = config('site.api_key');
        if ($token != md5($key . $time)) {
            echo json_encode(['success' => 0, 'msg' => '非法请求2']);
            exit();
        }

        $this->uid = session('xwx_user_id');
        if (is_null($this->uid)){
            echo json_encode(['success' => 0, 'msg' => '用户未登录']);
            exit();
        }

        $this->uid = session('xwx_user_id');
        $this->prefix = config('database.prefix');
        $this->redis_prefix = config('cache.prefix');
    }

}