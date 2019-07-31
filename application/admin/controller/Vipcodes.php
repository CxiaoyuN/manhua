<?php


namespace app\admin\controller;

use app\model\VipCode;
use think\facade\App;
use think\Request;

class Vipcodes extends BaseAdmin
{
    public function config(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->param();
            $validate = new \app\admin\validate\Vipcode();
            if ($validate->check($data)) {
                $str = <<<INFO
        <?php
        return [
            'salt' => '{$data["salt"]}',
            'day' => '{$data["day"]}',
            'num' => '{$data["num"]}'   
        ];
INFO;
                file_put_contents(App::getRootPath() . 'config/vipcode.php', $str);
                $this->success('保存成功');
            } else {
                $this->error($validate->getError());
            }
        } else {
            $this->assign([
                'salt' => config('vipcode.salt'),
                'day' => config('vipcode.day'),
                'num' => config('vipcode.num')
            ]);
            return view();
        }
    }

    public function index()
    {
        $data = VipCode::order('id', 'desc');
        $codes = $data->paginate(5, false,
            [
                //'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ]);
        $this->assign([
            'codes' => $codes,
            'count' => $data->count(),
            'api_key' => config('site.api_key')
        ]) ;
        return view();
    }
}