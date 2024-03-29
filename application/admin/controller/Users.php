<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/26
 * Time: 13:47
 */

namespace app\admin\controller;


use app\model\User;
use app\service\UserService;
use function Couchbase\passthruDecoder;
use think\App;

class Users extends BaseAdmin
{
    protected $userService;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->userService = new UserService();
    }

    public function index()
    {
        $data = $this->userService->getAdminPagedUsers(1, [], 'id', 'desc');
        $this->assign([
            'users' => $data['users'],
            'count' => $data['count']
        ]);
        return view();
    }

    public function search()
    {
        $username = input('username');
        $status = input('status');
        $isvip = input('isvip');
        $sort = input('sort');
        $where[] = ['username', 'like', '%' . $username . '%'];
        $time = time();
        if ($isvip == 'yes') {
            $where[] = ['vip_expire_time', '>', $time];
        } else if ($isvip == 'no') {
            $where[] = ['vip_expire_time', '<', $time];
        }
        if ($sort) {
            $orderBy = 'last_login_time';
        } else {
            $orderBy = 'id';
        }
        $data = $this->userService->getAdminPagedUsers($status, $where, $orderBy, $sort);
        $this->assign([
            'users' => $data['users'],
            'count' => $data['count']
        ]);
        if ($status == 1) {
            return view('index');
        } else {
            return view('disabled');
        }

    }

    public function disabled()
    {
        $data = $this->userService->getAdminPagedUsers(0, [], 'id', 'desc');
        $this->assign([
            'users' => $data['users'],
            'count' => $data['count']
        ]);
        return view();
    }

    public function disable()
    {
        $id = input('id');
        if (empty($id) || is_null($id)) {
            return ['status' => 0];
        }
        $user = User::get($id);
        $result = $user->delete();
        if ($result) {
            return ['status' => 1];
        } else {
            return ['status' => 0];
        }
    }

    public function enable()
    {
        $id = input('id');
        if (empty($id) || is_null($id)) {
            return ['status' => 0];
        }
        $user = User::onlyTrashed()->find($id);
        $result = $user->restore();
        if ($result) {
            return ['status' => 1];
        } else {
            return ['status' => 0];
        }
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $password = input('password');
            $user = new User();
            $user->id = input('id');
            $user->vip_expire_time = strtotime(input('expire_time'));
            if ($password != '') {
                if (strlen($password) < 6 || strlen($password) > 20) {
                    $this->error('密码长度不符合要求');
                }
                $user->password = $password;
            }
            $user->isUpdate(true)->save();
            $this->success('编辑成功');
        }
        $id = input('id');
        $user = User::get($id);
        if (!$user) {
            $this->error('该用户不存在');
        }
        $expire_time = (date('Y-m-d', $user->vip_expire_time));
        $this->assign([
            'user' => $user,
            'expire_time' => $expire_time
        ]);
        return view();
    }

    public function delete()
    {
        $id = input('id');
        if (empty($id) || is_null($id)) {
            return ['status' => 0];
        }
        $user = User::get($id);
        $result = $user->delete(true);
        if ($result) {
            return ['err' => 0, 'msg' => '删除成功'];
        } else {
            return ['err' => 1, 'msg' => '删除失败'];
        }
    }
}