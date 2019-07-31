<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/25
 * Time: 17:57
 */

namespace app\api\controller;

use app\model\VipCode;
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
        if (empty($day)) {
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

    public function genvipcode()
    {
        $api_key = input('api_key');
        if (empty($api_key) || is_null($api_key)) {
            return json(['err' => 1, 'msg' => 'api密钥错误']);
        }
        if ($api_key != config('site.api_key')) {
            return json(['err' => 1, 'msg' => 'api密钥错误']);
        }
        $num = (int)config('vipcode.num'); //产生多少个
        $day = config('vipcode.day');
        $salt = config('site.' . config('vipcode.salt'));//根据配置，获取盐的方式
        for ($i = 1; $i <= $num; $i++) {
            $code = substr(md5($salt . time()), 8, 16);
            VipCode::create([
                'code' => $code,
                'add_day' => $day
            ]);
            sleep(1);
        }
        return json(['success' => 1, 'msg' => '成功生成vip码']) ;
    }
}