<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;

class Wallet extends Common
{
    public function lock()
    {
        if (request()->isPost()) {
            $key = input('post.key');
            if ($key != '') {
                $where['l.lock_id|l.name'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('lock')->alias('l')//设置数据库别名
            //->join('users', 'l.user_id = users.user_id', 'left')//要关联的表名 关联条件 关联类型
            // ->join('wallet_plan', 'ul.plan_id=wallet_plan.plan_id', 'left')
            ->where($where)
                ->field('l.*,FROM_UNIXTIME(l.addtime) addtime')//指定一个表的某些字段查询
                ->order('l.lock_id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    public function user_wallet()
    {
        if (request()->isPost()) {
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
                    $where['l.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('user_wallet')->alias('l')//设置数据库别名
            ->join('users', 'l.user_id = users.user_id', 'left')//要关联的表名 关联条件 关联类型
             ->join('coin', 'l.coin_id=coin.coin_id', 'left')
            ->where($where)
                ->field('l.*,FROM_UNIXTIME(l.addtime) addtime,users.username,users.number,coin.name as coin_name')//指定一个表的某些字段查询
                ->order('l.wallet_id desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();

            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }


    public function lock_add()
    {
        if (request()->isPost()) {
            $data = input('post.');

            $data['addtime'] = time();
            Db::startTrans();
            try {
                $id = db('lock')->insertGetId($data);
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加理财套餐配置:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'lock', 'union_id' => $id, 'type' => 310));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('lock');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $this->assign('title', lang('add') . "配置");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    public function lock_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $map['lock_id'] = input('param.id');
            Db::startTrans();
            try {
                db('lock')->where($map)->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改套餐配置', 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'lock', 'union_id' => $map['lock_id'], 'type' => 311));
                $result['msg'] = '修改成功!';
                $result['url'] = url('lock');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', 'data' => ($e->getMessage())];
            }
        } else {
            $map['lock_id'] = input('param.id');
            $info = db('lock')->where($map)->find();
            // var_dump($info);die;
            $this->assign('title', lang('edit') . "等级");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('lock_add');
        }
    }

    public function lock_del()
    {
        $id = input('id');
        Db::startTrans();
        try {
            db('lock')->where(array('lock_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 312, 'union' => 'lock', 'union_id' => $id, 'object' => '删除理财套餐配置:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }


    public function lock_is_exp()
    {
        $id = input('post.id');
        $is_exp = db('lock')->where(array('lock_id' => $id))->value('is_exp');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_exp == 1) {
                $data['is_exp'] = 0;
                db('lock')->where(array('lock_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '套餐体验禁用ID:' . $id, 'union_id' => $id, 'union' => 'lock', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 313));
                $result['status'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_exp'] = 1;
                db('lock')->where(array('lock_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '餐体验开启ID:' . $id, 'union_id' => $id, 'union' => 'lock', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 313));
                $result['status'] = 1;
                $result['code'] = 1;
            }

            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }
}
