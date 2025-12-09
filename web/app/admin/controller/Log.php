<?php

namespace app\admin\controller;

use clt\leftnav;
use think\Db;

class Log extends Common
{

    /**
     * 后台提币  强制操作日志
     */
    public function log_agree()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['user_id|log_market_id'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_agree')
                ->where($where)
                ->field('id,user_id,log_market_id,info,from_unixtime(addtime) as addtime,coin_id')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 会员账户日志
     */
    public function log_coin()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['l.user_id|i.id_name|l.id|users.mobile'] = $key;
            }

            $cat_id = input('post.cat_id');
            if ($cat_id != '') {
                $where['type'] = $cat_id;
            }

            $coin_id = input('coin_id', '') ? input('coin_id', '') : '';
            if ($coin_id != '') {
                $where['l.coin_id'] = $coin_id;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_coin')->alias('l')->join('coin c', 'l.coin_id=c.coin_id')
                ->join('users', 'users.user_id=l.user_id', 'left')
                ->join('idaudit i', 'i.user_id=l.user_id', 'left')
                ->where($where)
                ->field('l.id,l.user_id,l.num,l.amount,l.balance,l.type,from_unixtime(l.addtime) as addtime,l.remark,l.union,l.union_id,c.name,users.mobile,i.id_name')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();


            $coins = Db('coin')->field('coin_id,name')->select();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('log_coin')[$v['type']];
            }
            $action = config('log_coin');
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins, 'action' => $action];
        }
        return $this->fetch();
    }

    /**
     * 矩阵拍卖日志
     */
    public function log_cube_market()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['l.user_id|l.cube_id'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_cube_market')->alias('l')->join('coin c', 'l.coin_id = c.coin_id')
                ->where($where)
                ->field('l.user_id,l.cube_id,remark,from_unixtime(l.addtime) as addtime,l.price,c.name')
                ->order('l.cube_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 会员锁仓日志
     */
    public function log_lock()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['b.number|b.username'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_lock')->alias('a')
                ->join('users b', 'a.user_id=b.user_id', 'left')
                ->join('lock l', 'a.lock_id=l.lock_id', 'left')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) as addtime,b.username,b.number,l.name')
                ->order('a.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $type = [1 => '投资', 2 => '提取'];
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = $type[$v['type']];
                //$list['data'][$k]['ip'] = long2ip($v['ip']);
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 牧场VIP 日志
     */
    public function log_vip()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['user_id'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
//                $arr = explode(" - ", $sldate);
//                if (count($arr) == 2) {
//                    $arrdateone = strtotime($arr[0]);
//                    $arrdatetwo = strtotime($arr[1]);
//                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
//                }
                $where['FROM_UNIXTIME(addtime,"Y-m-d 00:00:00")'] = $sldate;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_vip')
                ->where($where)
                ->field('id,user_id,remarks,FROM_UNIXTIME(addtime) addtime')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 密码修改日志
     */
    public function log_change_pass()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.number|users.username'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_change_pass')->alias('a')
                ->join('users', 'a.user_id=users.user_id', 'left')
                ->where($where)
                ->field('a.id,a.type,a.user_id,a.old,a.new,a.intro,from_unixtime(a.addtime) as addtime,users.username,users.number')
                ->order('a.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('log_action')[$v['type']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 用户名修改日志
     */
    public function log_users()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.number|a.old|a.new'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_users')->alias('a')
                ->join('users', 'a.user_id=users.user_id', 'left')
                ->where($where)
                ->field('a.id,a.user_id,a.old,a.new,a.intro,from_unixtime(a.addtime) as addtime,users.number')
                ->order('a.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
//            foreach($list['data'] as $k=>$v){
//                $list['data'][$k]['type'] = config('log_action')[$v['type']];
//            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 会员登录日志
     */
    public function log_login()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['username'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_login')->alias('l')
                ->join('users u', 'l.username = u.mobile', 'left')
                ->join('idaudit i', 'i.user_id = u.user_id', 'left')
                ->where($where)
                ->field('l.id,l.username,INET_NTOA(l.ip) as ip,l.status,from_unixtime(l.addtime) as addtime,l.desc,l.address,i.id_name ')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

//            $status = [-1=>'账号不存',0=>'失败',1=>'成功'];
//            foreach($list['data'] as $k=>$v){
//                $list['data'][$k]['status'] = $status[$v['status']];
//                $list['data'][$k]['ip'] = long2ip($v['ip']);
//            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 账号冻结日志
     */
    public function log_freeze()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['username'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_freeze')
                ->where($where)
                ->field('id,username,ip,status,desc,from_unixtime(addtime) as addtime')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $status = [-1 => '账号不存', 0 => '失败', 1 => '成功'];
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['status'] = $status[$v['status']];
                $list['data'][$k]['ip'] = long2ip(intval($v['ip']));
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 后台操作日志
     */
    public function log_action()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['admin_name|object|union_id'] = $key;
            }

            $cat_id = input('post.cat_id');
            if ($cat_id != '') {
                $where['type'] = $cat_id;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['last_time'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_action')
                ->where($where)
                ->field('id,admin_name,INET_NTOA(ip) as ip,type,object,from_unixtime(last_time) as last_time,union,union_id')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            foreach ($list['data'] as $k => $v) {
                //$list['data'][$k]['ip'] = long2ip($v['ip']);
                $list['data'][$k]['type'] = config('log_action')[$v['type']];
            }
            $action = config('log_action');
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'action' => $action];
        }
        return $this->fetch();
    }

    /**
     * 充值记录日志
     */
    public function log_recharge()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['id|account'] = $key;
            }

            /*            $sldate = input('date', '')?input('date', ''):'';
                        if($sldate !=''){
                            $arr = explode(" - ", $sldate);
                            if (count($arr) == 2) {
                                $arrdateone = strtotime($arr[0]);
                                $arrdatetwo = strtotime($arr[1]);
                                $where['time'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                            }
                        }*/
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_recharge')
                ->where($where)
                ->field('*,from_unixtime(blocktime) as blocktime,from_unixtime(time) as time,from_unixtime(timereceived) as timereceived')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }


    /**
     * 账号注册日志
     */
    public function log_register()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['mobile'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_register')
                ->where($where)
                ->field('id,mobile,INET_NTOA(ip) as ip,status,code,from_unixtime(addtime) as addtime')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $status = [
                -1 => '目前注册用户过多，请稍后再试!',
                -2 => '用户名不合法',
                -3 => '用户名重复',
                -4 => '手机号不合法',
                -5 => '手机号错误',
                -6 => '密码不能为空',
                -7 => '手机验证码错误',
                -8 => '失败 数据回滚',
                1 => '成功'];
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['status'] = $status[$v['status']];
                //$list['data'][$k]['ip'] = long2ip($v['ip']);
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 会员任务日志
     */
    public function log_task()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            $id = input('post.id');
            if ($key != '') {
                $where['l.user_id'] = $key;
            }
            if ($id != '') {
                $where['l.task_id'] = $id;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_task')->alias('l')->join('task t', 'l.task_id = t.task_id', 'left')
                ->where($where)
                ->field('l.id,l.user_id,l.remarks,l.force,from_unixtime(l.addtime) as addtime,t.name')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        $redis = \RedisHelper::instance();
        $task_list1 = $redis->zRangeByScore('task_list', 441, 441);
        $task_list2 = $redis->zRangeByScore('task_list', 442, 442);
        $task_list = array_merge(array_keys($task_list2), array_keys($task_list1));
        foreach ($task_list as $k => $v) {
            $task = $redis->hGetAll('task:' . $v);
            $info[] = $task;
        }
        $info = Db('task')->where('status', 1)->order('type desc,task_id')->field('task_id,name')->select();
        $this->assign('info', json_encode($info));
        return $this->fetch();
    }

    /**
     * 支付修改日志
     */
    public function log_payment()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['user_id'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_change_payment')
                ->where($where)
                ->field('id,user_id,intro,old,new,type,from_unixtime(addtime) as addtime')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * @return 提现审核日志
     */
    public function log_change()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['user_change_id|admin_name'] = $key;
        }
        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['audit_time'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_change')
                ->where($where)
                ->field('*,from_unixtime(audit_time) audit_time')
                ->order('user_change_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();


            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];

        }
        return $this->fetch();
    }


    /**
     * @return 活动钱包日志表
     */
    public function log_activity()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['a.user_id|b.username'] = $key;
        }
        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_activity')->alias('a')
                ->join('users b', 'a.user_id=b.user_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) addtime,b.username')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('log_activity')[$v['type']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * @return 兑换审核日志
     */
    public function log_exchange()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['user_exchange_id|admin_name'] = $key;
        }
        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['audit_time'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_exchange')
                ->where($where)
                ->field('*,from_unixtime(audit_time) audit_time')
                ->order('user_exchange_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();


            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];

        }
        return $this->fetch();
    }

    /**
     * @return 临时钱包日志
     */
    public function log_temp()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['a.user_id|b.username'] = $key;
        }
        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_temp')->alias('a')
                ->join('users b', 'a.user_id=b.user_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) addtime,b.username')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('log_temp')[$v['type']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * @return eth充值日志
     */
    public function log_coin_eth()
    {
        if (request()->isPost()) {
            $where['name'] = 'ETH';
            $where['union'] = 'trx_eth';
            $key = input('post.key');
            if ($key != '') {
                $where['l.user_id|u.username'] = $key;
            }

            $status1 = input('status1');
            if ($status1 != '') {
                $where['t.status'] = $status1;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_coin')->alias('l')
                ->join('coin c', 'l.coin_id=c.coin_id')
                ->join('users u', 'u.user_id=l.user_id')
                ->join('trx_eth t', 't.id=l.union_id')
                ->where($where)
                ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.remark,l.union,l.union_id,c.name,u.username,t.status t_status')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            $coins = Db('coin')->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        return $this->fetch();
    }

    /**
     * @return sht充值日志
     */
    public function log_coin_sht()
    {
        if (request()->isPost()) {
            $where['name'] = 'SHT';
            $where['union'] = 'trx_sht';
            $key = input('post.key');
            if ($key != '') {
                $where['l.user_id|u.username'] = $key;
            }

            $status1 = input('status1');
            if ($status1 != '') {
                $where['t.status'] = $status1;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_coin')->alias('l')
                ->join('coin c', 'l.coin_id=c.coin_id')
                ->join('users u', 'u.user_id=l.user_id')
                ->join('trx_sht t', 't.id=l.union_id')
                ->where($where)
                ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.remark,l.union,l.union_id,c.name,u.username,t.status t_status')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            $coins = Db('coin')->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        return $this->fetch();
    }

    /**
     * @return usdt充值日志
     */
    public function log_coin_usdt()
    {
        if (request()->isPost()) {
            $where['name'] = 'USDT';
            $where['union'] = 'trx_usdt';
            $key = input('post.key');
            if ($key != '') {
                $where['l.user_id|u.username'] = $key;
            }

            $status1 = input('status1');
            if ($status1 != '') {
                $where['t.status'] = $status1;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_coin')->alias('l')
                ->join('coin c', 'l.coin_id=c.coin_id')
                ->join('users u', 'u.user_id=l.user_id')
                ->join('trx_usdt t', 't.id=l.union_id')
                ->where($where)
                ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.remark,l.union,l.union_id,c.name,u.username,t.status as t_status')
                ->order('l.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $coins = Db('coin')->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        return $this->fetch();
    }

    //导出eth充值记录
    public function export_trx_eth()
    {
        $where['name'] = 'ETH';
        $where['union'] = 'trx_eth';
        $key = input('key');
        if ($key != '') {
            $where['l.user_id|u.username'] = $key;
        }

        $status1 = input('status1');
        if ($status1 != '') {
            $where['t.status'] = $status1;
        }

        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        $data = db('log_coin')->alias('l')
            ->join('coin c', 'l.coin_id=c.coin_id')
            ->join('users u', 'u.user_id=l.user_id')
            ->join('trx_eth t', 't.id=l.union_id')
            ->where($where)
            ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.union_id,c.name,u.username,t.status t_status')
            ->order('l.id desc')
            ->select();

        Vendor('phpexcel.PHPExcel');//调用类库,路径是基于vendor文件夹的
        Vendor('phpexcel.PHPExcel.Worksheet.Drawing');
        Vendor('phpexcel.PHPExcel.Writer.Excel2007');
        $objExcel = new \PHPExcel();
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');

        $objActSheet = $objExcel->getActiveSheet();
        $letter = explode(',', "A,B,C,D,E,F,G,H,I,J");
        $arrHeader = array('编号', '会员id', '会员名', '通证名称', '充值数量', '充值前数量', '充值后数量', '状态', '充值时间');;
        //填充表头信息
        $lenth = count($arrHeader);
        for ($i = 0; $i < $lenth; $i++) {
            $objActSheet->setCellValue("$letter[$i]1", "$arrHeader[$i]");
        };
        //填充表格信息
        foreach ($data as $k => $v) {
            if ($v['t_status'] == 1) {
                $v['t_status'] = '已确认';
            } elseif ($v['t_status'] == 2) {
                $v['t_status'] = '已入账';
            }

            $k += 2;
            $objActSheet->setCellValue('A' . $k, $v['id']);
            $objActSheet->setCellValue('B' . $k, $v['user_id']);
            $objActSheet->setCellValue('C' . $k, $v['username']);
            $objActSheet->setCellValue('D' . $k, $v['name']);
            $objActSheet->setCellValue('E' . $k, $v['num']);
            $objActSheet->setCellValue('F' . $k, $v['amount']);
            $objActSheet->setCellValue('G' . $k, $v['balance']);
            $objActSheet->setCellValue('H' . $k, $v['t_status']);
            $objActSheet->setCellValue('I' . $k, $v['addtime']);
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);
        }

        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth('15');
        $objActSheet->getColumnDimension('B')->setWidth('15');
        $objActSheet->getColumnDimension('C')->setWidth('15');
        $objActSheet->getColumnDimension('D')->setWidth('15');
        $objActSheet->getColumnDimension('E')->setWidth('15');
        $objActSheet->getColumnDimension('F')->setWidth('15');
        $objActSheet->getColumnDimension('G')->setWidth('15');
        $objActSheet->getColumnDimension('H')->setWidth('15');
        $objActSheet->getColumnDimension('I')->setWidth('20');

        $outfile = "ETH充值日志" . date("YmdHis") . ".xls";
        ob_end_clean();
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outfile . '"');
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
    }

    //导出sht充值记录
    public function export_trx_sht()
    {
        $where['name'] = 'SHT';
        $where['union'] = 'trx_sht';
        $key = input('key');
        if ($key != '') {
            $where['l.user_id|u.username'] = $key;
        }

        $status1 = input('status1');
        if ($status1 != '') {
            $where['t.status'] = $status1;
        }

        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        $data = db('log_coin')->alias('l')
            ->join('coin c', 'l.coin_id=c.coin_id')
            ->join('users u', 'u.user_id=l.user_id')
            ->join('trx_sht t', 't.id=l.union_id')
            ->where($where)
            ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.union_id,c.name,u.username,t.status t_status')
            ->order('l.id desc')
            ->select();

        Vendor('phpexcel.PHPExcel');//调用类库,路径是基于vendor文件夹的
        Vendor('phpexcel.PHPExcel.Worksheet.Drawing');
        Vendor('phpexcel.PHPExcel.Writer.Excel2007');
        $objExcel = new \PHPExcel();
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');

        $objActSheet = $objExcel->getActiveSheet();
        $letter = explode(',', "A,B,C,D,E,F,G,H,I,J");
        $arrHeader = array('编号', '会员id', '会员名', '通证名称', '充值数量', '充值前数量', '充值后数量', '状态', '充值时间');;
        //填充表头信息
        $lenth = count($arrHeader);
        for ($i = 0; $i < $lenth; $i++) {
            $objActSheet->setCellValue("$letter[$i]1", "$arrHeader[$i]");
        };
        //填充表格信息
        foreach ($data as $k => $v) {
            if ($v['t_status'] == 1) {
                $v['t_status'] = '已确认';
            } elseif ($v['t_status'] == 2) {
                $v['t_status'] = '已入账';
            }

            $k += 2;
            $objActSheet->setCellValue('A' . $k, $v['id']);
            $objActSheet->setCellValue('B' . $k, $v['user_id']);
            $objActSheet->setCellValue('C' . $k, $v['username']);
            $objActSheet->setCellValue('D' . $k, $v['name']);
            $objActSheet->setCellValue('E' . $k, $v['num']);
            $objActSheet->setCellValue('F' . $k, $v['amount']);
            $objActSheet->setCellValue('G' . $k, $v['balance']);
            $objActSheet->setCellValue('H' . $k, $v['t_status']);
            $objActSheet->setCellValue('I' . $k, $v['addtime']);
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);
        }

        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth('15');
        $objActSheet->getColumnDimension('B')->setWidth('15');
        $objActSheet->getColumnDimension('C')->setWidth('15');
        $objActSheet->getColumnDimension('D')->setWidth('15');
        $objActSheet->getColumnDimension('E')->setWidth('15');
        $objActSheet->getColumnDimension('F')->setWidth('15');
        $objActSheet->getColumnDimension('G')->setWidth('15');
        $objActSheet->getColumnDimension('H')->setWidth('15');
        $objActSheet->getColumnDimension('I')->setWidth('20');

        $outfile = "SHT充值日志" . date("YmdHis") . ".xls";
        ob_end_clean();
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outfile . '"');
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
    }

    //导出usdt充值记录
    public function export_trx_usdt()
    {
        $where['name'] = 'USDT';
        $where['union'] = 'trx_usdt';
        $key = input('key');
        if ($key != '') {
            $where['l.user_id|u.username'] = $key;
        }

        $status1 = input('status1');
        if ($status1 != '') {
            $where['t.status'] = $status1;
        }

        $sldate = input('date', '') ? input('date', '') : '';
        if ($sldate != '') {
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = strtotime($arr[0]);
                $arrdatetwo = strtotime($arr[1]);
                $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        $data = db('log_coin')->alias('l')
            ->join('coin c', 'l.coin_id=c.coin_id')
            ->join('users u', 'u.user_id=l.user_id')
            ->join('trx_usdt t', 't.id=l.union_id')
            ->where($where)
            ->field('l.id,l.user_id,l.num,l.amount,l.balance,from_unixtime(l.addtime) as addtime,l.union_id,c.name,u.username,t.status t_status')
            ->order('l.id desc')
            ->select();

        Vendor('phpexcel.PHPExcel');//调用类库,路径是基于vendor文件夹的
        Vendor('phpexcel.PHPExcel.Worksheet.Drawing');
        Vendor('phpexcel.PHPExcel.Writer.Excel2007');
        $objExcel = new \PHPExcel();
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');

        $objActSheet = $objExcel->getActiveSheet();
        $letter = explode(',', "A,B,C,D,E,F,G,H,I,J");
        $arrHeader = array('编号', '会员id', '会员名', '通证名称', '充值数量', '充值前数量', '充值后数量', '状态', '充值时间');;
        //填充表头信息
        $lenth = count($arrHeader);
        for ($i = 0; $i < $lenth; $i++) {
            $objActSheet->setCellValue("$letter[$i]1", "$arrHeader[$i]");
        };
        //填充表格信息
        foreach ($data as $k => $v) {
            if ($v['t_status'] == 1) {
                $v['t_status'] = '已确认';
            } elseif ($v['t_status'] == 2) {
                $v['t_status'] = '已入账';
            }

            $k += 2;
            $objActSheet->setCellValue('A' . $k, $v['id']);
            $objActSheet->setCellValue('B' . $k, $v['user_id']);
            $objActSheet->setCellValue('C' . $k, $v['username']);
            $objActSheet->setCellValue('D' . $k, $v['name']);
            $objActSheet->setCellValue('E' . $k, $v['num']);
            $objActSheet->setCellValue('F' . $k, $v['amount']);
            $objActSheet->setCellValue('G' . $k, $v['balance']);
            $objActSheet->setCellValue('H' . $k, $v['t_status']);
            $objActSheet->setCellValue('I' . $k, $v['addtime']);
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);
        }

        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth('15');
        $objActSheet->getColumnDimension('B')->setWidth('15');
        $objActSheet->getColumnDimension('C')->setWidth('15');
        $objActSheet->getColumnDimension('D')->setWidth('15');
        $objActSheet->getColumnDimension('E')->setWidth('15');
        $objActSheet->getColumnDimension('F')->setWidth('15');
        $objActSheet->getColumnDimension('G')->setWidth('15');
        $objActSheet->getColumnDimension('H')->setWidth('15');
        $objActSheet->getColumnDimension('I')->setWidth('20');

        $outfile = "USDT充值日志" . date("YmdHis") . ".xls";
        ob_end_clean();
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outfile . '"');
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
    }

    public function log_cash()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['lus.user_cash_id|lus.admin_name|uc.username|u.number'] = $key;
            }
            /*            $type = input('post.type');
                        if ($type != '') {
                            $where['lus.type'] = $type;
                        }*/
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_cash')->alias('lus')//设置数据库别名
            ->join('user_cash uc', 'lus.user_cash_id = uc.id', 'left')
                ->join('users u', 'u.user_id = uc.user_id', 'left')//要关联的表名 关联条件 关联类型
                ->where($where)
                ->field('lus.*,FROM_UNIXTIME(lus.audit_time) audit_time,uc.user_id,uc.username,u.number')//指定一个表的某些字段查询
                ->order('lus.user_cash_id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            /*            foreach($list['data'] as $k=>$v){
                            $list['data'][$k]['type'] = config('log_user_seting')[$v['type']];
                        }*/
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    //币币交易日K日志
    public function log_trade_day()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['ltd.id|ltd.date'] = $key;
            }
            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['ltd.exch_coin_id'] = $coin_id;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_trade_day')->alias('ltd')//设置数据库别名
            ->join('coin', 'ltd.exch_coin_id=coin.coin_id', 'left')
                ->where($where)
                ->field('ltd.*,coin.name,coin.short')//指定一个表的某些字段查询
                ->order('ltd.id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            foreach ($list['data'] as $k => $v) {

                if ($v['addtime'] != 0) {
                    $list['data'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                } else {
                    $list['data'][$k]['addtime'] = null;
                }
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    public function log_trade_day_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                db('log_trade_day')->update($data);
                $result['msg'] = '编辑成功!';
                $result['url'] = url('log_trade_day');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $map['id'] = input('param.id');
            $info = db('log_trade_day')->where($map)->find();
            $this->assign('title', lang('edit'));
            $this->assign('info', json_encode($info, true));
            return $this->fetch('log_trade_day_edit');
        }
    }

    public function log_trade()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['lt.user_id|lt.id'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_trade')->alias('lt')//设置数据库别名
            //   ->join('users', 'lus.user_id = users.user_id', 'left')//要关联的表名 关联条件 关联类型
            ->where($where)
                ->field('lt.*,FROM_UNIXTIME(lt.addtime) addtime')//指定一个表的某些字段查询
                ->order('lt.log_trade_id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['status'] = config('log_trade')[$v['status']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();

    }

    public function savings()
    {

        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.number|ls.id|users.username'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_savings')->alias('ls')//设置数据库别名
            ->join('users', 'ls.user_id = users.user_id', 'left')//要关联的表名 关联条件 关联类型
            ->where($where)
                ->field('ls.*,FROM_UNIXTIME(ls.addtime) addtime,users.username,users.number')//指定一个表的某些字段查询
                ->order('ls.id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('savings')[$v['type']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    public function log_mutual()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['u.number|u.username|us.username|us.number'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_mutual')->alias('a')
                ->join('users u', 'a.turn_user_id=u.user_id')
                ->join('users us', 'a.receive_user_id=us.user_id')
                ->where($where)
                ->field('u.number as turn_number,u.username as turn_username,us.username as receive_username,a.log_mutual_id,a.num,a.fee,us.number as receive_number,from_unixtime(a.addtime) as addtime')
                ->order('a.log_mutual_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    public function log_crowd()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.number|lc.id|users.username'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_crowd')->alias('lc')//设置数据库别名
            ->join('users', 'lc.user_id = users.user_id', 'left')//要关联的表名 关联条件 关联类型
            ->where($where)
                ->field('lc.*,FROM_UNIXTIME(lc.addtime) addtime,users.username,users.number')//指定一个表的某些字段查询
                ->order('lc.id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            $type = [1 => '后台添加', 2 => '提取'];
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = $type[$v['type']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    //交易不良记录展示
    public function user_warn()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.number|users.username'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_user_warn')->alias('luw')//设置数据库别名
            ->join('users', 'luw.user_id = users.user_id', 'left')//要关//联的表名 关联条件 关联类型
            ->where($where)
                ->field('luw.*,users.username,users.number,FROM_UNIXTIME(luw.addtime) addtime')//指定一个表的某些字段查询
                ->order('luw.warn_id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('user_warn')[$v['type']];

            }
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['status'] = config('user_warn1')[$v['status']];

            }

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();

    }

//支付方式修改日志
    public function log_change_payment()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['u.number|u.username'] = $key;
            }

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_change_payment')
                ->alias('l')
                ->join('users u', 'u.user_id=l.user_id', 'left')
                ->where($where)
                ->field('l.id,l.user_id,l.intro,l.old,l.new,l.type,from_unixtime(l.addtime) as addtime,u.username,u.number')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            //  var_dump($list);die;
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type'] = config('log_change_payment')[$v['type']];
            }

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    public function cash_pool_trade()
    {
        if (request()->isPost()) {
            $where = [];

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }

            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['a.exch_coin_id'] = $coin_id;
            }


            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('cash_pool_trade')->alias('a')
                ->join('coin c', 'a.exch_coin_id=c.coin_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) as addtime,c.name')
                ->order('a.addtime desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        $sum = db('cash_pool_trade')->sum('`total`');
        $this->assign('sum', $sum);
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    public function cash_pool_market()
    {
        if (request()->isPost()) {
            $where = [];

            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['a.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }

            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['a.exch_coin_id'] = $coin_id;
            }

            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('cash_pool_market')->alias('a')
                ->join('coin c', 'a.exch_coin_id=c.coin_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) as addtime,c.name')
                ->order('a.addtime desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        $sum = db('cash_pool_market')->sum('`total`');
        $this->assign('sum', $sum);
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    public function root_balance()
    {
        try {
            if (request()->isPost()) {
                $bitcoind_USDT = new \Denpa\Bitcoin\Client([
                    'scheme' => 'http',
                    // optional, default http
                    'host' => config('USDT.host'),
                    // optional, default localhost
                    'port' => config('USDT.port'),
                    // optional, default 8332
                    'user' => config('USDT.user'),
                    // required
                    'pass' => config('USDT.password'),
                    // required
                ]);

                $list['data'][0]['usdt_balance'] = $bitcoind_USDT->request('omni_getbalance', [config('USDT.root'), 31])->get()['balance'];

                return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => 1, 'rel' => 1];
            }
            return $this->fetch();
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
}