<?php


namespace app\service;


use app\model\User;
use app\model\UserFinance;
use think\Controller;
use think\facade\Cache;

class PromotionService extends Controller
{
    /*
     * $uid:接收充值用户id
     * $money:接收本次充值金额
     * */
    public function rewards($uid, $money, $type)
    {
        $user = User::get($uid);
        if ($user->pid > 0) {
            $rate = config('payment.promotional_rewards_rate');
            $userFinance = new UserFinance();
            $userFinance->user_id = $user->pid; //用户上线id
            $userFinance->usage = 4; //推广提成
            if ($type == 1) { //下线充值奖励，2为注册奖励
                $userFinance->money = round($money * $rate, 2); //根据提成比率来计算出本次奖励金额
                $userFinance->summary = '下线用户' . $uid . '充值提成';
            } else {
                $userFinance->money = $money; //直接奖励
                $userFinance->summary = '用户注册奖励';
            }

            $userFinance->save(); //存储用户充值数据
            cache('rewards:' . $uid, null); //删除奖励缓存
            cache('rewards:sum:' . $uid, null); //删除奖励总和缓存
            Cache::clear('pay'); //清除支付缓存
        }
    }

    public function getRewardsHistory()
    {
        $uid = session('xwx_user_id');
        if (!$uid) {
            return [];
        } else {
            $map = array();
            $map[] = ['user_id', '=', $uid];
            $map[] = ['usage', '=', 4]; //4为奖励记录
            $type = 'util\Page';
            if ($this->request->isMobile()) {
                $type = 'util\MPage';
            }
            $rewards = UserFinance::where($map)->paginate(10, false,
                [
                    'query' => request()->param(),
                    'type' => $type,
                    'var_page' => 'page',
                ]);
            return $rewards;
        }
    }

    public function getRewardsSum()
    {
        $uid = session('xwx_user_id');
        if (!$uid) {
            return 0;
        } else {
            $map = array();
            $map[] = ['user_id', '=', $uid];
            $map[] = ['usage', '=', 4];
            $sum = UserFinance::where($map)->sum('money');
            return $sum;
        }

    }
}