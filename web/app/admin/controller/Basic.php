<?php

namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\Log;
use app\admin\model\CoinTrade;


class Basic extends Common
{


    /**
     * @return array|mixed任务列表
     */
    public function matching()
    {
        if (request()->isPost()) {
//            $key = input('post.key');
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('matching')->alias('m')
//                ->where('name', 'like', "%" . $key . "%")
                ->field('m.*,from_unixtime(m.addtime) as addtime')
                ->order('m.matching_id asc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * @return mixed新增
     */
    public function matching_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            if (!$data['rate']) {
                return ['code' => 0, 'msg' => '比例不能为空！'];
            }
            $data['status'] = input('post.status') ? input('post.status') : 0;
            $data['addtime'] = time();
            Db::startTrans();
            try {
                $id = db('matching')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '新增匹配比例ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'matching', 'union_id' => $id, 'type' => 341));
                Db::commit();
                $result['msg'] = '比例添加成功!';
                $result['url'] = url('matching');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $cat = Db('coin')->where(['is_trade' => 0])->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $this->assign('title', lang('add') . "匹配比例");
            $this->assign('info', 'null');
            return $this->fetch('matching_add');
        }
    }

    /**
     * @return 修改
     */
    public function matching_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
//          var_dump($data);die;
            Db::startTrans();
            try {

                db('matching')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '编辑任务ID:' . $data['matching_id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'matching', 'union_id' => $data['matching_id'], 'type' => 342));
                $result['msg'] = '比例修改成功!';
                $result['url'] = url('matching');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $cat = Db('coin')->where(['is_trade' => 0])->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $map['matching_id'] = input('param.matching_id');
            $info = db('matching')->where($map)->find();
            $this->assign('title', lang('edit') . "匹配比例");
            $this->assign('info', json_encode($info, true));
            $this->assign('infos', $info);
            return $this->fetch('matching_add');
        }
    }

    /**
     * 删除单个
     */
    public function task_del()
    {
        $id = input('task_id');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '项目计划ID不存在！'];
        }
        Db::startTrans();
        try {
            db('task')->where(array('task_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 32, 'union' => 'task', 'union_id' => $id, 'object' => '删除任务ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            $this->change_task();
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    /**
     *   删除多个
     */

    public function task_delall()
    {
        $map['task_id'] = array('in', input('param.ids/a'));
        Db::startTrans();
        try {
            db('task')->where($map)->delete();
            foreach (input('param.ids/a') as $v) {
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 33, 'union' => 'task', 'union_id' => $v, 'object' => '批量删除任务ID:' . $v, 'last_time' => time(), 'ip' => request()->ip(1)));
            }
            $this->change_task();
            Db::commit();
            $result['msg'] = '删除成功！';
            $result['code'] = 1;
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    /**
     * @return mixed修改状态
     */
    public function matching_state()
    {
        $id = input('post.matching_id');
        $status = db('matching')->where(array('matching_id' => $id))->value('status');//判断当前状态情况
        Db::startTrans();
        try {
            if ($status == 1) {
                $data['status'] = 0;
                db('matching')->where(array('matching_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 339, 'union' => 'matching', 'union_id' => $id, 'object' => '关闭ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['status'] = 0;
                $result['is_lock'] = 1;
            } else {
                $data['status'] = 1;
                db('matching')->where(array('matching_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 340, 'union' => 'matching', 'union_id' => $id, 'object' => '开启ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['status'] = 1;
                $result['is_lock'] = 1;
            }
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['is_lock' => 0];
        }
    }

    /**
     * 通证配置
     */
    public function coin()
    {
        if (request()->isPost()) {
            $key = input('post.key');
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('coin')
                ->field("coin_id,name,short,logo,intro,status,is_recharge,is_draw,is_exchange,open_price,price,sort,draw_max_day,draw_min_times,draw_min_fee,FROM_UNIXTIME(addtime) as addtime,draw_rate,price_cny")
                ->order('coin_id desc')
                ->where('name|coin_id', 'like', "%" . $key . "%")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * @return mixed 新增通证
     */
    public function coin_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            $data['pubdate'] = time();

            if (isset($data['imgs']) && count($data['imgs']) == 1) {
                $imgs['coin_' . $data['name']] = $data['imgs']['logo'];
                //$imgs['coin_' . $data['name'] . '_gif'] = $data['imgs']['logo_gif'];
            } else {
                //return ['code' => 0, 'msg' => '请完整上传logo静态，动态图片！'];
                return ['code' => 0, 'msg' => '请上传logo！'];
            }
            //图片上传
            $img_data = $this->upload($imgs, 'assets/img/coins/');
            if (!$img_data['code']) {
                return $img_data;
            }

            $data['logo'] = $img_data['data']['coin_' . $data['name']];
            //$data['logo_gif'] = $img_data['data']['coin_'.$data['name'].'_gif'];

            Db::startTrans();
            try {
                $id = db('coin')->insertGetId($data);
                //添加操作日志'
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 211, 'union' => 'coin', 'union_id' => $id, 'object' => '新增通证ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                //$this->change_coin();
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('coin');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '添加失败！'];
            }
        } else {
            $this->assign('title', lang('add') . "通证");
            $this->assign('info', 'null');
            return $this->fetch('coin_add');
        }
    }

    /**
     * 修改通证
     */
    public function coin_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            $data['coin_id'] = input('get.id');

            if (isset($data['imgs']) && count($data['imgs']) == 1) {
                $imgs['coin_' . $data['name']] = $data['imgs']['logo'];
                //$imgs['coin_'.$data['name'].'_gif'] = $data['imgs']['logo_gif'];

                //图片上传
                $img_data = $this->upload($imgs, 'assets/img/coins/');
                if (!$img_data['code']) {
                    return $img_data;
                }

                $data['pubdate'] = time();
                $data['logo'] = $img_data['data']['coin_' . $data['name']];
                //$data['logo_gif'] = $img_data['data']['coin_'.$data['name'].'_gif'];
            } else if (isset($data['imgs']) && count($data['imgs']) == 1) {
                return ['code' => 0, 'msg' => '两张图片需要同时修改！'];
            } else {
                unset($data['logo']);
                //unset($data['logo_gif']);
            }

            Db::startTrans();
            try {
                $price = db('coin')->where(['coin_id' => $data['coin_id']])->value('price');
                $object = '修改币子ID:' . $data['coin_id'];
                if (sprintf('%.6f', $price) != sprintf('%.6f', $data['price'])) {
                    $object = '修改币子ID:' . $data['coin_id'] . '；price：' . $price . '--' . $data['price'];
                }
                db('coin')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 212, 'union' => 'coin', 'union_id' => $data['coin_id'], 'object' => $object, 'last_time' => time(), 'ip' => request()->ip(1)));
                //$this->change_coin();
                Db::commit();
                $result['msg'] = '修改成功!';
                $result['url'] = url('coin');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '修改失败！'];
            }
        } else {
            $map['coin_id'] = input('param.id');
            $info = db('coin')->where($map)->find();
            $info['logo'] = $info['logo'] ? config('OSS_GLOBAL')['oss_url'] . '/' . $info['logo'] . '?' . $info['pubdate'] : '';
            //$info['logo_gif'] = $info['logo_gif']?OSS_URl.$info['logo_gif'].'?'.$info['pubdate']:'';
            $this->assign('title', lang('edit') . "通证");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('coin_add');
        }
    }

    /**
     * 删除单个
     */
    public function coin_del()
    {
        $id = input('coin_id');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '矩阵不存在！'];
        }
        Db::startTrans();
        try {
            db('coin')->where(array('coin_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 213, 'union' => 'coin', 'union_id' => $id, 'object' => '删除通证ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            $this->change_coin();
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    /**
     *   通证多个删除
     */
    public function coin_delall()
    {
        $map['coin_id'] = array('in', input('param.ids/a'));
        Db::startTrans();
        try {
            db('coin')->where($map)->delete();
            foreach (input('param.ids/a') as $v) {
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 214, 'union' => 'coin', 'union_id' => $v, 'object' => '批量删除通证ID:' . $v, 'last_time' => time(), 'ip' => request()->ip(1)));
            }
            $this->change_coin();
            Db::commit();
            $result['msg'] = '删除成功！';
            $result['code'] = 1;
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '删除失败！'];
        }
    }

    public function coin_state()
    {
        $id = input('post.coin_id');
        $status = db('coin')->where(array('coin_id' => $id))->value('status');//判断当前状态情况
        Db::startTrans();
        try {
            if ($status == 1) {
                $data['status'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '通证禁用ID:' . $id, 'union_id' => $id, 'union' => 'coin', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 215));
                $result['status'] = 0;
                $result['code'] = 1;
            } else {
                $data['status'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '通证开启ID:' . $id, 'union_id' => $id, 'union' => 'coin', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 216));
                $result['status'] = 1;
                $result['code'] = 1;
            }
            //$this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_recharge()
    {
        $id = input('post.coin_id');
        $is_recharge = db('coin')->where(array('coin_id' => $id))->value('is_recharge');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_recharge == 1) {
                $data['is_recharge'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 217, 'union' => 'coin', 'union_id' => $id, 'object' => '通证禁止充值ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_recharge'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_recharge'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 218, 'union' => 'coin', 'union_id' => $id, 'object' => '通证可以充值ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['is_recharge'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_lottery()
    {
        $id = input('post.coin_id');
        $is_lottery = db('coin')->where(array('coin_id' => $id))->value('is_lottery');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_lottery == 1) {
                $data['is_lottery'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 228, 'union' => 'coin', 'union_id' => $id, 'object' => '转盘禁用ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_lottery'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_lottery'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 229, 'union' => 'coin', 'union_id' => $id, 'object' => '转盘开启ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['is_lottery'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_draw()
    {
        $id = input('post.coin_id');
        $is_draw = db('coin')->where(array('coin_id' => $id))->value('is_draw');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_draw == 1) {
                $data['is_draw'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 219, 'union' => 'coin', 'union_id' => $id, 'object' => '通证禁止提现ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_draw'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_draw'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 220, 'union' => 'coin', 'union_id' => $id, 'object' => '通证开启提现ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['is_draw'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_exchange()
    {
        $id = input('post.coin_id');
        $is_exchange = db('coin')->where(array('coin_id' => $id))->value('is_exchange');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_exchange == 1) {
                $data['is_exchange'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 209, 'union' => 'coin', 'union_id' => $id, 'object' => '通证禁止兑换ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_exchange'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_exchange'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 210, 'union' => 'coin', 'union_id' => $id, 'object' => '通证开启兑换ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['is_exchange'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_self()
    {
        $id = input('post.coin_id');
        $self = db('coin')->where(array('coin_id' => $id))->value('self');//判断当前状态情况
        Db::startTrans();
        try {
            if ($self == 1) {
                $data['self'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 221, 'union' => 'coin', 'union_id' => $id, 'object' => '通证不是自己的公链ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['self'] = 0;
                $result['code'] = 1;
            } else {
                $data['self'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 222, 'union' => 'coin', 'union_id' => $id, 'object' => '通证是自己的公链ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));

                $result['self'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_market()
    {
        $id = input('post.coin_id');
        $is_market = db('coin')->where(array('coin_id' => $id))->value('is_market');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_market == 1) {
                $data['is_market'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 223, 'union' => 'coin', 'union_id' => $id, 'object' => '通证禁止交易ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_market'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_market'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 224, 'union' => 'coin', 'union_id' => $id, 'object' => '通证开启交易ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_market'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_is_lock()
    {
        $id = input('post.coin_id');
        $is_lock = db('coin')->where(array('coin_id' => $id))->value('is_lock');//判断当前状态情况
        Db::startTrans();
        try {
            if ($is_lock == 1) {
                $data['is_lock'] = 0;
                db('coin')->where(array('coin_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 225, 'union' => 'coin', 'union_id' => $id, 'object' => '通证禁止锁仓ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_lock'] = 0;
                $result['code'] = 1;
            } else {
                $data['is_lock'] = 1;
                db('coin')->where(array('coin_id' => $id))->setField($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 226, 'union' => 'coin', 'union_id' => $id, 'object' => '通证开启锁仓ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
                $result['is_lock'] = 1;
                $result['code'] = 1;
            }
            $this->change_coin();
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }

    public function coin_default()
    {
        $id = input('post.coin_id');
        Db::startTrans();
        try {
            db('coin')->where('1=1')->update(['default' => 0]);//判断当前状态情况
            db('coin')->where(array('coin_id' => $id))->update(['default' => 1]);
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 227, 'union' => 'coin', 'union_id' => $id, 'object' => '通证默认ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            $this->change_coin();
            Db::commit();
            return ['code' => 1, 'default' => 1];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0];
        }
    }


    //修改coin redis 数据
    public function change_coin()
    {
        $redis = \RedisHelper::instance();
        $keys = $redis->keys('coin*');
        foreach ($keys as $v) {
            $redis->del_key($v);
        }
        //查询重新添加 redis

        $list = Db::name('coin')->field('`coin_id`,`name`, `short`, `logo`, `intro`,`is_recharge`,`is_draw`,`price`,`sort`')
            ->order('coin_id')->select();
        foreach ($list as $item) {
            $redis->zAdd('coin_list', $item['coin_id'], $item['name']);
            $redis->hMset('coin:' . $item['coin_id'], $item);
        }
    }

    //修改task redis 数据
    public function change_task()
    {
        $redis = \RedisHelper::instance();
        $keys = $redis->keys('task*');
        foreach ($keys as $v) {
            $redis->del_key($v);
        }
        //查询重新添加 redis

        $list = Db::name('task')->field('`task_id`, `name`, `intro`, `force`, `type`,`status`,`jump`,`code`')
            ->where('status', 1)->order('type,task_id')->select();
        foreach ($list as $item) {
            $redis->zAdd('task_list', $item['type'], $item['task_id']);
            $redis->hMset('task:' . $item['code'], $item);
            $redis->hMset('task:' . $item['task_id'], $item);
        }
    }

    /**
     * 价格列表
     */
    public function price()
    {
        if (request()->isPost()) {
            $key = input('post.coin_id');
            if (!$key) {
                $key = 1;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('price')->alias('a')
                ->join('coin b', 'a.coin_id=b.coin_id')
                ->where('a.coin_id', $key)
                ->field('a.*,from_unixtime(a.addtime) as addtime,b.name')
                ->order('a.date desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    /**
     * 添加价格
     */
    public function price_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            $data['date'] = date('Ymd', strtotime($data['date']));
            $data['coin_id'] = explode(',', $data['coin'])[0];
            $usdt_cny = db('coin')->where('coin_id', 2)->value('price_cny');
            $data['price'] = bcdiv($data['cny_price'], $usdt_cny, 4);
            Db::startTrans();
            try {
                $id = db('price')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加价格:' . $data['date'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'price', 'union_id' => $data['date'], 'type' => 221));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('price');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $this->assign('title', lang('add') . "价格");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    /**
     * 修改价格
     */
    /*public function price_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                db('price')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改价格:'.$data['date'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'price', 'union_id' => $data['date'], 'type' => 222));
                $result['msg'] = '修改成功!';
                $result['url'] = url('price');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $map['date'] = input('param.id');
            $info = db('price')->where($map)->find();
            $this->assign('title', lang('edit') . "价格");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('price_add');
        }
    }*/

    /**
     * 删除价格
     */
    public function price_del()
    {
        Db::startTrans();
        try {
            db('price')->where(['date' => input('date'), 'coin_id' => input('coin_id')])->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 223, 'union' => 'price', 'union_id' => input('date'), 'object' => '删除价格:' . input('date'), 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }


    /**
     * 锁仓钱包等级
     */
    public function lock()
    {
        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('lock')
                ->field('*,from_unixtime(addtime) as addtime')
                ->order('lock_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 添加锁仓钱包等级
     */
    public function lock_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            Db::startTrans();
            try {
                $id = db('lock')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加锁仓钱包等级:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'lock', 'union_id' => $id, 'type' => 226));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('lock');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $this->assign('title', lang('add') . "等级");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    /**
     * 修改锁仓钱包等级
     */
    public function lock_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                db('lock')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改锁仓钱包等级:' . $data['lock_id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'lock', 'union_id' => $data['lock_id'], 'type' => 227));
                $result['msg'] = '修改成功!';
                $result['url'] = url('lock');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $map['lock_id'] = input('param.id');
            $info = db('lock')->where($map)->find();
            $this->assign('title', lang('edit') . "等级");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('lock_add');
        }
    }

    /**
     * 删除锁仓钱包等级
     */
    public function lock_del()
    {
        $id = input('id');
        Db::startTrans();
        try {
            db('lock')->where(array('lock_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 228, 'union' => 'lock', 'union_id' => $id, 'object' => '删除锁仓钱包等级:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    public function config()
    {
        if (request()->isPost()) {
            $key = input('post.key');
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('config')->alias('f')->join('coin c', 'f.coin_id = c.coin_id', 'left')
                ->where('f.name|f.desc|f.value', 'like', "%" . $key . "%")
                ->field('f.*,c.name as coin_name')
                ->order('f.id')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 添加参数
     */
    public function config_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                $id = db('config')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加参数:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'config', 'union_id' => $id, 'type' => 91));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('config');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $cat = Db('coin')->where(['is_trade' => 0])->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $this->assign('title', lang('add') . "参数");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    /**
     * 修改参数
     */
    public function config_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                db('config')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改参数:' . $data['id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'config', 'union_id' => $data['id'], 'type' => 92));
                $result['msg'] = '修改成功!';
                $result['url'] = url('config');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $cat = Db('coin')->where(['is_trade' => 0])->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $map['id'] = input('param.id');
            $info = db('config')->where($map)->find();
            $this->assign('title', lang('edit') . "参数");
            $this->assign('info', json_encode($info, true));
            $this->assign('infos', $info);
            return $this->fetch('config_add');
        }
    }

    /**
     * 超级节点等级
     */
    public function vip_level()
    {
        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('node_level')
                ->field('*,from_unixtime(addtime) as addtime')
                ->order('node_level_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 添加超级节点等级
     */
    public function vip_level_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            Db::startTrans();
            try {
                $id = db('node_level')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加超级节点等级:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'node_level', 'union_id' => $id, 'type' => 231));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('vip_level');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $this->assign('title', lang('add') . "等级");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    /**
     * 修改超级节点等级
     */
    public function vip_level_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            Db::startTrans();
            try {
                db('node_level')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改超级节点等级:' . $data['node_level_id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'node_level', 'union_id' => $data['node_level_id'], 'type' => 232));
                $result['msg'] = '修改成功!';
                $result['url'] = url('vip_level');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $map['node_level_id'] = input('param.id');
            $info = db('node_level')->where($map)->find();
            $this->assign('title', lang('edit') . "等级");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('vip_level_add');
        }
    }

    /**
     * 删除超级节点等级
     */
    public function vip_level_del()
    {
        $id = input('id');
        Db::startTrans();
        try {
            db('node_level')->where(array('node_level_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 233, 'union' => 'node_level', 'union_id' => $id, 'object' => '删除超级节点等级:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    /**
     * 超级节点状态
     */
    public function vip_level_status()
    {
        $id = input('post.id');
        $status = db('node_level')->where(array('node_level_id' => $id))->value('status');//判断当前状态情况
        Db::startTrans();
        try {
            if ($status == 1) {
                $data['status'] = 0;
                db('node_level')->where(array('node_level_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '关闭超级节点等级:' . $id, 'union_id' => $id, 'union' => 'node_level', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 235));
                $result['status'] = 0;
                $result['code'] = 1;
            } else {
                $data['status'] = 1;
                db('node_level')->where(array('node_level_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '开启超级节点等级:' . $id, 'union_id' => $id, 'union' => 'node_level', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 234));
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


    /**
     * 折扣管理
     */
    public function coin_discount()
    {
        $key = input('post.key');
        if (request()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('coin_discount')
                ->where('coin_name|coin_id', 'like', "%" . $key . "%")
                ->field('*,from_unixtime(addtime) as addtime,from_unixtime(start) as start,from_unixtime(end) as end')
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    /**
     * 添加折扣
     */
    public function coin_discount_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['addtime'] = time();
            $coin_id = explode(':', $data['coin_id']);
            $data['coin_id'] = $coin_id[1];
            $data['coin_name'] = db('coin')->where('coin_id', $data['coin_id'])->value('name');
            $data['start'] = strtotime($data['start']);
            $data['end'] = strtotime($data['end']);
            $data['status'] = 1;
            Db::startTrans();
            try {
                $id = db('coin_discount')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加折扣:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'coin_discount', 'union_id' => $id, 'type' => 241));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('coin_discount');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $coin = db('coin')->field('coin_id,name')->select();
            $this->assign('coin', json_encode($coin, true));
            $this->assign('title', lang('add') . "折扣");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    /**
     * 修改折扣
     */
    public function coin_discount_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $coin_id = explode(':', $data['coin_id']);
            $data['coin_id'] = $coin_id[1];
            $data['coin_name'] = db('coin')->where('coin_id', $data['coin_id'])->value('name');
            $data['start'] = strtotime($data['start']);
            $data['end'] = strtotime($data['end']);
            Db::startTrans();
            try {
                db('coin_discount')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改折扣:' . $data['id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'coin_discount', 'union_id' => $data['id'], 'type' => 242));
                $result['msg'] = '修改成功!';
                $result['url'] = url('coin_discount');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $map['id'] = input('param.id');
            $coin = db('coin')->field('coin_id,name')->select();
            $this->assign('coin', json_encode($coin, true));
            $info = db('coin_discount')->where($map)->field('*,from_unixtime(start) start,from_unixtime(end) end')->find();
            $this->assign('title', lang('edit') . "折扣");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('coin_discount_add');
        }
    }

    /**
     * 删除折扣
     */
    public function coin_discount_del()
    {
        $id = input('id');
        Db::startTrans();
        try {
            db('coin_discount')->where(array('id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 243, 'union' => 'coin_discount', 'union_id' => $id, 'object' => '删除折扣:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }

    /**
     * 修改折扣状态
     */
    public function coin_discount_status()
    {
        $id = input('post.id');
        $status = db('coin_discount')->where(array('id' => $id))->value('status');//判断当前状态情况
        Db::startTrans();
        try {
            if ($status == 1) {
                $data['status'] = 0;
                db('coin_discount')->where(array('id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '关闭折扣:' . $id, 'union_id' => $id, 'union' => 'coin_discount', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 245));
                $result['status'] = 0;
                $result['code'] = 1;
            } else {
                $data['status'] = 1;
                db('coin_discount')->where(array('id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '开启折扣:' . $id, 'union_id' => $id, 'union' => 'coin_discount', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 244));
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

    public function country()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['name|code'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('country')
                ->field('country_id,sorting,name,code,status,from_unixtime(addtime) as addtime,remark')
                ->where($where)
                ->order('sorting asc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();

    }

    public function country_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            /*            var_dump(explode(',',$data['vip']));die;*/
            // var_dump($data);die;
            $data['addtime'] = time();
            // $data['vip_id'] = explode(',', $data['vip'])[0];
            Db::startTrans();
            try {
                $id = db('country')->insertGetId($data);
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加国家配置:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'country', 'union_id' => $id, 'type' => 297));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('country');
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

    public function country_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $map['country_id'] = input('param.id');
            Db::startTrans();
            try {
                db('country')->where($map)->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改国家配置', 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'lock', 'union_id' => $map['country_id'], 'type' => 298));
                $result['msg'] = '修改成功!';
                $result['url'] = url('country');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', 'data' => ($e->getMessage())];
            }
        } else {
            $map['country_id'] = input('param.id');
            $info = db('country')->where($map)->find();
            // var_dump($info);die;
            $this->assign('title', lang('edit') . "等级");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('country_add');
        }

    }

    public function country_del()
    {
        $id = input('id');
        // var_dump($id);die;
        Db::startTrans();
        try {
            db('country')->where(array('country_id' => $id))->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 299, 'union' => 'country', 'union_id' => $id, 'object' => '删除国家配置:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
            Db::commit();
            return ['code' => 1, 'msg' => '删除成功！'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 0, 'msg' => '操作失败！'];
        }
    }


    public function country_status()
    {
        $id = input('post.id');
        $status = db('country')->where(array('country_id' => $id))->value('status');//判断当前状态情况
        Db::startTrans();
        try {
            if ($status == 1) {
                $data['status'] = 0;
                db('country')->where(array('country_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '关闭国家配置:' . $id, 'union_id' => $id, 'union' => 'country', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 300));
                $result['status'] = 0;
                $result['code'] = 1;
            } else {
                $data['status'] = 1;
                db('country')->where(array('country_id' => $id))->setField($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '开启国家配置:' . $id, 'union_id' => $id, 'union' => 'country', 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 300));
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

    //交易对配置
    public function coin_trade()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            /*      if ($key != '') {
                    $where['main_coin_id|main_coin_name|exch_coin_id|exch_coin_name'] = $key;
                  }*/
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('coin_trade')
                ->field('*,from_unixtime(addtime) as addtime')
                ->where($where)
                ->order('id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

//配置增加
    public function coin_trade_add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            /*            var_dump(explode(',',$data['coin']));die;*/
            $data['main_coin_name'] = explode(',', $data['coin'])[1];
            $data['main_coin_id'] = explode(',', $data['coin'])[0];
            $data['exch_coin_name'] = explode(',', $data['coin1'])[1];
            $data['exch_coin_id'] = explode(',', $data['coin1'])[0];
            $data['trade_name'] = explode(',', $data['coin1'])[1] . explode(',', $data['coin'])[1];
            unset($data['coin']);
            $data['addtime'] = time();

            Db::startTrans();
            try {
                db('coin_trade')->insertGetId($data);

                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('coin_trade');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $cat = Db('coin')->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $this->assign('title', lang('add') . '配置');
            $this->assign('info', 'null');


            return $this->fetch('coin_trade_add');
        }
    }

//配置编辑
    public function coin_trade_edit()
    {
        if (request()->isPost()) {
            $data = input('post.');
            /*            var_dump(explode(',',$data['coin']));die;*/
            $data['main_coin_name'] = explode(',', $data['coin'])[1];
            $data['main_coin_id'] = explode(',', $data['coin'])[0];
            $data['exch_coin_name'] = explode(',', $data['coin1'])[1];
            $data['exch_coin_id'] = explode(',', $data['coin1'])[0];
            $data['trade_name'] = explode(',', $data['coin1'])[1] . explode(',', $data['coin'])[1];
            unset($data['coin']);
            Db::startTrans();
            try {
                db('coin_trade')->update($data);

                $result['msg'] = '修改成功!';
                $result['url'] = url('coin_trade');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
            }
        } else {
            $map['id'] = input('param.id');
            $info = db('coin_trade')->where($map)->find();
            $cat = Db('coin')->field('coin_id,name')->select();
            $this->assign('cat', $cat);
            $this->assign('title', lang('edit') . "配置");
            $this->assign('info', json_encode($info, true));
            $this->assign('infos', $info);
            return $this->fetch('coin_trade_add');
        }
    }

//配置删除
    public function coin_trade_del()
    {
        CoinTrade::destroy(['id' => input('id')]);
        return $result = ['code' => 1, 'msg' => '删除成功!'];
    }

//配置状态修改
    public function coin_trade_status()
    {
        $id = input('post.id');
        $status = input('post.status');

        db('coin_trade')->where('id' . '=' . $id)->update(['status' => $status]);
        $result['status'] = 1;
        $result['info'] = '修改成功';
        $result['url'] = url('coin_trade');
        return $result;
    }


}