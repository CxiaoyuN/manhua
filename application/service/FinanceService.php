<?php


namespace app\service;

use app\model\Book;
use app\model\Chapter;
use app\model\UserBuy;
use app\model\UserFinance;
use app\model\UserOrder;
use think\Controller;

class FinanceService extends Controller
{
    public function getUserChargeHistory($uid)
    {
        $map = array();
        $map[] = ['user_id', '=', $uid];
        $map[] = ['usage', '=', 1];
        $type = 'util\Page';
        if ($this->request->isMobile()) {
            $type = 'util\MPage';
        }
        $charges = UserFinance::where($map)->paginate(10, false,
            [
                'query' => request()->param(),
                'type' => $type,
                'var_page' => 'page',
            ]);

        return $charges;
    }

    public function getUserSpendingHistory($uid)
    {
        $map = array();
        $map[] = ['user_id', '=', $uid];
        $map[] = ['usage', ['=', 2], ['=', 3], 'or'];
        $type = 'util\Page';
        if ($this->request->isMobile()) {
            $type = 'util\MPage';
        }
        $spendings = UserFinance::where($map)->paginate(10, true,
            [
                'query' => request()->param(),
                'type' => $type,
                'var_page' => 'page',
            ]);
        return $spendings;
    }

    //获得当前用户充值总和
    public function getChargeSum($uid)
    {
        $map = array();
        $map[] = ['user_id', '=', $uid];
        $map[] = ['usage', 'in', [1, 4]];
        $sum = UserFinance::where($map)->sum('money');
        return $sum;
    }

    //获得当前用户消费总和
    public function getSpendingSum($uid)
    {
        $map = array();
        $map[] = ['user_id', '=', $uid];
        $map[] = ['usage', ['=', 2], ['=', 3], 'or'];
        $sum = UserFinance::where($map)->sum('money');
        return $sum;
    }

    public function getBalance($uid)
    {
        $charge_sum = $this->getChargeSum($uid);
        $spending_sum = $this->getSpendingSum($uid);
        return $charge_sum - $spending_sum;
    }

    public function getUserBuyHistory($uid)
    {
        $type = 'util\Page';
        if ($this->request->isMobile()) {
            $type = 'util\MPage';
        }
        $buys = UserBuy::where('user_id', '=', $uid)->order('id', 'desc')
            ->paginate(10, false,
                [
                    'query' => request()->param(),
                    'type' => $type,
                    'var_page' => 'page',
                ]);
        foreach ($buys as &$buy) {
            $chapter = Chapter::with('book')->find($buy['chapter_id']);
            $buy['chapter'] = $chapter;
        }
        return $buys;
    }

    public function getPagedOrders($where = '1=1')
    {
        $data = UserOrder::where($where)->order('id', 'desc');
        $orders = $data->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
        return [
            'orders' => $orders,
            'count' => $data->count()
        ];
    }

    public function getPagedFinance($where = '1=1')
    {
        $data = UserFinance::where($where)->order('id', 'desc');
        $finances = $data->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
        return [
            'finances' => $finances,
            'count' => $data->count()
        ];
    }

    public function getPagedBuyHistory()
    {
        $data = UserBuy::order('id', 'desc');
        $buys = $data->paginate(5, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
        foreach ($buys as &$buy) {
            $buy['chapter'] = Chapter::get($buy->chapter_id);
            $buy['book'] = Book::get($buy->book_id);
        }
        return [
            'buys' => $buys,
            'count' => $data->count()
        ];
    }
}