<?php


namespace app\admin\controller;


use app\service\FinanceService;
use think\facade\App;

class Payment extends BaseAdmin
{
    protected $financeService;

    protected function initialize()
    {
        $this->financeService = new FinanceService();
    }

    //支付配置文件
    public function index()
    {
        if ($this->request->isPost()) {
            $content = input('json');
            file_put_contents(App::getRootPath() . 'config/payment.php', $content);
            $this->success('保存成功');
        }
        $content = file_get_contents(App::getRootPath() . 'config/payment.php');
        $this->assign('json', $content);
        return view();
    }

    //订单查询
    public function orders()
    {
        $data = $this->financeService->getPagedOrders();
        $this->assign([
            'orders' => $data['orders'],
            'count' => $data['count']
        ]);
        return view();
    }

    //用户消费记录
    public function finance(){
        $data = $this->financeService->getPagedFinance();
        $this->assign([
            'finances' => $data['finances'],
            'count' => $data['count']
        ]);
        return view();
    }

    //用户购买记录
    public function buy(){
        $data = $this->financeService->getPagedBuyHistory();
        $this->assign([
            'buys' => $data['buys'],
            'count' => $data['count']
        ]);
        return view();
    }
}