<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/25
 * Time: 17:57
 */

namespace app\api\controller;

use think\facade\App;
use think\facade\Cache;
use think\Controller;
use app\model\Clicks;

class Common extends Controller
{
    public function clearcache()
    {
        $key = input('api_key');
        if (empty($key) || is_null($key)) {
            return 'api密钥不能为空！';
        }
        if ($key != config('site.api_key')) {
            return 'api密钥错误！';
        }
        Cache::clear('redis');
        $rootPath = App::getRootPath();
        delete_dir_file($rootPath . '/runtime/cache/') && delete_dir_file($rootPath . '/runtime/temp/');
        return '清理成功';
    }

    public function sycnclicks()
    {
        $key = input('api_key');
        if (empty($key) || is_null($key)) {
            return 'api密钥不能为空！';
        }
        if ($key != config('site.api_key')) {
            return 'api密钥错误！';
        }
        $day = input('date');
        if (!$day){
            $day = date("Y-m-d", strtotime("-1 day"));
        }
        $redis = new_redis();
        $hots = $redis->zRevRange('click:' . $day, 0, 10, true);
        foreach ($hots as $k => $v) {
            $clicks = new Clicks();
            $clicks->book_id = $k;
            $clicks->clicks = $v;
            $clicks->cdate = $day;
            $clicks->save();
        }
        return json(['success' => 1, 'msg' => '同步完成']);
    }
}