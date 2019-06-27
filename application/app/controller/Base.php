<?php


namespace app\app\controller;

use think\App;
use think\Controller;
use think\facade\Response;

class Base extends Controller
{
    protected $prefix;
    protected $redis_prefix;
    protected $uid;

    protected function initialize()
    {
        $token = $this->request->param('token');
        $time = $this->request->param('time');
        $key = config('site.api_key');
        if ($token != md5($key . $time)) {
            echo json_encode(['success' => 0, 'msg' => '非法请求']);
            exit();
        }

        $this->uid = session('xwx_user_id');
        $this->prefix = config('database.prefix');
        $this->redis_prefix = config('cache.prefix');
    }
}