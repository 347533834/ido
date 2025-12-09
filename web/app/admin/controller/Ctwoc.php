<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Input;

class Ctwoc extends Common
{
    public function market()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['m.username|m.user_id|m.market_id|users.mobile|id.id_name'] = $key;
            }
            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['m.coin_id'] = $coin_id;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('market')->alias('m')
                ->join('users', 'm.user_id=users.user_id')
                ->join('coin', 'm.coin_id=coin.coin_id')
                ->join('idaudit id', 'm.user_id=id.user_id', 'left')
                ->where($where)
                ->field('id.id_name,m.*,coin.name,from_unixtime(m.addtime) as addtime,users.mobile,m.price*m.num total,m.price*m.deal_num deal_total')
                ->order('m.market_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $coins = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }


    public function log_market()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['us.username|m.buyer_id|u.username|m.seller_id|m.market_id'] = $key;
            }
            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['m.coin_id'] = $coin_id;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');

            $list = db('log_market')->alias('m')
                ->join('coin', 'm.coin_id=coin.coin_id', 'left')
                ->join('users us', 'us.user_id = m.buyer_id')
                ->join('users u', 'u.user_id = m.seller_id')
                ->join('idaudit id', 'm.buyer_id=id.user_id', 'left')
                ->join('idaudit ida', 'm.seller_id=ida.user_id', 'left')
                ->where($where)
                ->field('ida.id_name seller_id_name,u.mobile seller_mobile,id.id_name buyer_id_name,us.mobile buyer_mobile,m.*,coin.name,us.username as buyer_username,u.username as seller_username,from_unixtime(m.addtime) as addtime')
                ->order('m.log_market_id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            $coins = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();

    }

    public function warn($user_id, $market_id)
    {
        //会员封号
        $warn_2 = db('log_user_warn')->where(['user_id' => $user_id, 'type' => 2, 'status' => 1])->count();
        db('log_user_warn')->insert([
            'user_id' => $user_id,
            'type' => 2,
            'status' => 1,
            'log_market_id' => $market_id,
            'addtime' => time(),
            'remarks' => '严重警告' . ($warn_2 + 1) . '次',
        ]);
        switch ($warn_2) {
            case 0:
                $warn_time = 7 * 24 * 3600;
                break;
            case 1:
                $warn_time = 30 * 24 * 3600;
                break;
            default:
                $warn_time = 365 * 24 * 3600;
        }
        //添加冻结时间
        db('users')->where(['user_id' => $user_id])->update([
            'freeze_time' => time() + $warn_time,
            'status' => 0,
        ]);
        //这里执行删除当前用户的cache
        cache('cache_userinfo_' . $user_id, null);
    }

//强制撤销
    public function repeal()
    {
        $id = input('id');
        $type = input('type');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '订单ID不存在！'];
        }
        Db::startTrans();
        try {
            //查询订单情况
            $market = db('market')->field('user_id,username,num,deal_num,fee,deal_fee,coin_id,price,status')->where(array('market_id' => $id, 'status' => array('in', '0,1')))->lock(true)->find();
            if (!$market) {
                Db::rollback();
                return ['code' => 0, 'msg' => '订单状态异常！'];
            }
            if ($type == '挂买') {
                //改变订单状态
                db('market')->where(array('market_id' => $id))->setField(['status' => -1]);

            } else {
                //改变订单状态
                db('market')->where(array('market_id' => $id))->setField(['status' => -1]);
                //查询卖家账户余额

                $account = db('user_coin')->where(array('user_id' => $market['user_id'], 'coin_id' => $market['coin_id']))->lock(true)->value('balance');
                //  var_dump($account);die;
                if ($market['status' == 0]) {
                    //增加用户账户余额
                    $sum = bcadd($market['num'], $market['fee'], 6);
                } else {
                    //增加用户账户余额
                    $su_num = $market['num'] - $market['deal_num'];//剩余数量
                    $su_fee = $market['fee'] - $market['deal_fee'];//剩余手续费
                    $sum = bcadd($su_num, $su_fee, 6);
                }

                db('user_coin')->where(array('user_id' => $market['user_id']))->update(['balance' => bcadd($account, $sum, 6)]);
                //添加日志
                $data = array(
                    'user_id' => $market['user_id'],
                    'coin_id' => $market['coin_id'],
                    'num' => $sum,
                    'amount' => $account,
                    'balance' => bcadd($account, $sum, 6),
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'market',
                    'union_id' => $id,
                    'remark' => '强制撤销挂单ID:' . $id . '增加账户余额:' . $sum,
                    'type' => 51
                );
                db('log_coin')->insert($data);

                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '强制撤销挂单ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 289, 'union' => 'market', 'union_id' => $id));

            }

            Db::commit();
            return ['code' => 1, 'msg' => '撤销挂单成功！'];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 1, 'msg' => '撤销挂单失败！', 'data' => $e->getMessage()];
            //var_dump('data');die;
        }
    }

    //交易记录强制交易
    public function pass()
    {
        $id = input('id');
        //  $coin_id=input('coin_id');
        $type = input('type');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '交易订单ID不存在！'];
        }
        Db::startTrans();
        try {
            //查询订单情况
            $log_market = db('log_market')->field('log_market_id,buyer_id,buyer_username,seller_id,seller_username,num,price,coin_id')->where(array('log_market_id' => $id, 'status' => array('in', '2,12')))->lock(true)->find();

            if (!$log_market) {
                Db::rollback();
                return ['code' => 0, 'msg' => '交易订单状态异常'];
            }
            if ($type == '挂买-卖出') {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->update(['status' => 3]);

                //查询买家账户余额
                $account = db('user_coin')->where(array('user_id' => $log_market['buyer_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                /*            var_dump($account);
                            Db::rollback();
                            die;*/

                //增加买家账户余额
                $sum = bcadd($account, $log_market['num'], 8);


                db('user_coin')->where(array('user_id' => $log_market['buyer_id'], 'coin_id' => $log_market['coin_id']))->update(['balance' => $sum]);
                $this->warn($log_market['seller_id'], $id);
            } else {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->update(['status' => 13]);

                //查询买家账户余额
                $account = db('user_coin')->where(array('user_id' => $log_market['buyer_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                /*            var_dump($account);
                            Db::rollback();
                            die;*/

                //增加买家账户余额
                $sum = bcadd($account, $log_market['num'], 8);


                db('user_coin')->where(array('user_id' => $log_market['buyer_id'], 'coin_id' => $log_market['coin_id']))->update(['balance' => $sum]);
                $this->warn($log_market['seller_id'], $id);
            }


            //添加资产日志
            $data = array(
                'user_id' => $log_market['buyer_id'],
                'coin_id' => $log_market['coin_id'],
                'num' => $log_market['num'],
                'amount' => $account,
                'balance' => $sum,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $id,
                'remark' => '强制交易订单ID:' . $id . '增加账户余额:' . $log_market['num'],
                'type' => 56,
            );

            db('log_coin')->insert($data);

            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '强制交易订单ID:' . $id, 'union' => 'log_market', 'union_id' => $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 288));
            // var_dump($log_action);die;

            Db::commit();
            return ['code' => 1, 'msg' => '成功通过'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            //echo '<pre>';
            //  var_dump($e);
            Db::rollback();
            return ['code' => 0, 'msg' => '通过失败'];
        }
    }

    //交易记录强制撤销
    public function repeal1()
    {
        $id = input('id');
        $type = input('type');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '交易订单ID不存在！'];
        }
        Db::startTrans();
        try {
            //查询订单情况
            $log_market = db('log_market')->field('log_market_id,buyer_id,buyer_username,seller_id,seller_username,num,price,coin_id')->where(array('log_market_id' => $id, 'status' => array('in', '1,11')))->lock(true)->find();
            if (!$log_market) {
                Db::rollback();
                return ['code' => 0, 'msg' => '交易订单状态异常！'];
            }

            if ($type == '挂买-卖出') {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->setField(['status' => -1]);
                //查询卖家账户余额

                $account = db('user_coin')->where(array('user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                //  var_dump($account);die;
                //增加卖家用户账户余额
                $fee = $log_market['num'] * 0.002;
                $sum = bcadd($log_market['num'], $fee, 6);
                db('user_coin')->where(array('user_id' => $log_market['seller_id']))->update(['balance' => bcadd($account, $sum, 6)]);
                $this->warn($log_market['buyer_id'], $id);
            } else {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->setField(['status' => -1]);
                //查询卖家账户余额

                $account = db('user_coin')->where(array('user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                //  var_dump($account);die;
                //增加卖家用户账户余额
                $fee = $log_market['num'] * 0.002;
                $sum = bcadd($log_market['num'], $fee, 6);
                db('user_coin')->where(array('user_id' => $log_market['seller_id']))->update(['balance' => bcadd($account, $sum, 6)]);
                $this->warn($log_market['buyer_id'], $id);
            }

            //添加日志
            $data = array(
                'user_id' => $log_market['seller_id'],
                'coin_id' => $log_market['coin_id'],
                'num' => $sum,
                'amount' => $account,
                'balance' => bcadd($account, $sum, 6),
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $id,
                'remark' => '强制撤销订单ID:' . $id . '增加账户余额:' . $sum,
                'type' => 55
            );
            db('log_coin')->insert($data);

            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '强制撤销订单ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 289, 'union' => 'log_market', 'union_id' => $id));

            Db::commit();
            return ['code' => 1, 'msg' => '强制撤销成功！'];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 1, 'msg' => '强制撤销失败！', 'data' => $e->getMessage()];
            //var_dump('data');die;
        }
    }

    public function repeal2()
    {
        $id = input('id');
        $type = input('type');
        if (empty($id)) {
            return ['code' => 0, 'msg' => '交易订单ID不存在！'];
        }
        Db::startTrans();
        try {
            //查询订单情况
            $log_market = db('log_market')->field('log_market_id,buyer_id,buyer_username,seller_id,seller_username,num,price,coin_id')->where(array('log_market_id' => $id, 'status' => array('in', '2,12')))->lock(true)->find();
            if (!$log_market) {
                Db::rollback();
                return ['code' => 0, 'msg' => '交易订单状态异常！'];
            }

            if ($type == '挂买-卖出') {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->setField(['status' => -1]);
                //查询卖家账户余额

                $account = db('user_coin')->where(array('user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                //  var_dump($account);die;
                //增加卖家用户账户余额
                $fee = $log_market['num'] * 0.002;
                $sum = bcadd($log_market['num'], $fee, 6);
                db('user_coin')->where(array('user_id' => $log_market['seller_id']))->update(['balance' => bcadd($account, $sum, 6)]);
                $this->warn($log_market['buyer_id'], $id);
            } else {
                //改变订单状态
                db('log_market')->where(array('log_market_id' => $id))->setField(['status' => -1]);
                //查询卖家账户余额

                $account = db('user_coin')->where(array('user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']))->lock(true)->value('balance');
                //  var_dump($account);die;
                //增加卖家用户账户余额
                $fee = $log_market['num'] * 0.002;
                $sum = bcadd($log_market['num'], $fee, 6);
                db('user_coin')->where(array('user_id' => $log_market['seller_id']))->update(['balance' => bcadd($account, $sum, 6)]);
                $this->warn($log_market['buyer_id'], $id);
            }

            //添加日志
            $data = array(
                'user_id' => $log_market['seller_id'],
                'coin_id' => $log_market['coin_id'],
                'num' => $sum,
                'amount' => $account,
                'balance' => bcadd($account, $sum, 6),
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $id,
                'remark' => '强制撤销订单ID:' . $id . '增加账户余额:' . $sum,
                'type' => 55
            );
            db('log_coin')->insert($data);

            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '强制撤销订单ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 289, 'union' => 'log_market', 'union_id' => $id));

            Db::commit();
            return ['code' => 1, 'msg' => '强制撤销成功！'];

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 1, 'msg' => '强制撤销失败！', 'data' => $e->getMessage()];
            //var_dump('data');die;
        }
    }

}
