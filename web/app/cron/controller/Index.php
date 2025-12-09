<?php

namespace app\cron\controller;

use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Queue;

class Index
{
    private $redis;

    /**
     * 定时执行入口
     */
    public function index()
    {
        $this->redis = \RedisHelper::instance();

        echo date('Y-m-d H:i:s') . "\n";
//        $this->up_price();
//        sleep(3);
//        $this->crowd_returns();
//        sleep(3);
//        $this->up_node();
//        sleep(3);
//        $this->vip_node_returns();
//        sleep(3);
//        $this->node_returns();
//        sleep(3);
//        $this->release_lock();
    }

    /**
     * 获取文件锁
     * @param $file
     * @return bool true:正在执行，直接路过，false:进行操作
     */
    public function getLock($file)
    {
        $file = ROOT_PATH . 'cron/data/' . $file . '.txt';
        $flag = 0;
        $times = 0;
        $return = false;
        if (file_exists($file)) {
            list($flag, $times) = explode('|', file_get_contents($file));
            $flag = intval($flag);
            $times = intval($times);
            $times++;
            $return = false;
        }

        file_put_contents($file, $flag . '|' . $times);
        return $return;
    }

    public function getUnlock($file)
    {
        $file = ROOT_PATH . 'cron/data/' . $file . '.txt';
        file_put_contents($file, '0|0');
    }

    /**
     * 日志方法
     * @param $file
     * @param $e
     */
    private function log($file, $e)
    {
        file_put_contents(ROOT_PATH . 'cron/log/' . $file . '_' . date('YmdHis') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
    }


    //生成每日入金分红 汇总
    public function insert_bonus()
    {
        $date = date("Ymd", strtotime("-1 day"));
        $time = time();
        //    $to_date = date('Ymd');
        Db::connect(config('cli_db'))->query("insert into tp_bonus (`user_id`,`coin_id`,`date`,`num`,`returns`,`addtime`,`status`) select user_id,coin_id,{$date},sum(`num`),sum(`num`)*0.1,{$time},0 from tp_log_bonus where `date`={$date} group by user_id,coin_id ORDER BY user_id desc");

    }

    //上午场匹配
    public function match_trade()
    {
        try {
            $coinid = '1,23';
            $coin_id = [1,23];
            $date = date('Ymd');
            $time = time();
            $fee_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'trade_fee_sell'])->value('value');
            //合并用户买单和卖单至log_trade
            Db::connect(config('cli_db'))->query("insert into tp_log_trade (`type`,`match_level`,`date`,`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,`num`,`total`,`deal_num`,`fee`,`deal_fee`,`status`,`addtime`,`remark`) select `type`,`final_level`,{$date},`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,sum(`num`),sum(`total`),0,sum(`fee`),0,0,$time,'' from tp_trade where `date`={$date} and `status`=0 and exch_coin_id in ($coinid) group by `user_id`,`type`,`main_coin_id`,`exch_coin_id` ORDER BY user_id desc");

            //trade表数据修改为已匹配
            Db::connect(config('cli_db'))->execute("UPDATE `tp_trade` SET `status` = 1,`uptime` = $time WHERE `status` = 0 AND `date` = $date and exch_coin_id in ($coinid)");

            //查询用户买单
            $trade_buy = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'status' => 0, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('id,num,total,fee,user_id,pid,main_coin_id,exch_coin_id')->select();

            foreach ($trade_buy as $k => $v) {
                //更新用户级别
//                $user_level = Db::connect(config('cli_db'))->name('users')->where(array('user_id' => $v['user_id']))->value('user_level');
//                if ($user_level < 3) {
//                    Db::connect(config('cli_db'))->name('users')->where(array('user_id' => $v['user_id']))->update(['user_level' => 3]);
//                }

                $user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->field('pid,lft,rgt,depth,buy_total,team_total')->find();
                //更新团队业绩和用户级别
                Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->setInc('buy_total', $v['total']);
                Db::connect(config('cli_db'))->execute("update tp_user_rela set `team_total`=(`team_total`+{$v['total']}) where `lft`<={$user_rela['lft']} and `rgt`>={$user_rela['rgt']} and `depth`<={$user_rela['depth']} and `depth`>0");

                //查询ido余额
                $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');
                $new_ido_balance = bcadd($old_ido_balance, $v['num'], 8);
                //更新ido余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                    'balance' => $new_ido_balance,
                ]);
                //log_coin日志
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['exch_coin_id'],
                    'type' => 61,
                    'num' => $v['num'],
                    'amount' => $old_ido_balance ? $old_ido_balance : 0,
                    'balance' => $new_ido_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);

                //返给买家上级30%手续费
                if ($user_rela['pid']) {
                    //查询上级余额
                    $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $user_rela['pid']])->value('balance'));
                    $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $v['fee'] * 0.3, 8);
                    //更新上级余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $user_rela['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);

                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $user_rela['pid'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 67,
                        'num' => $v['fee'] * 0.3,
                        'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                        'balance' => $new_parent_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }

                //生成分红记录
                //$this->get_bonus($v['user_id'], $v['total'], $v['main_coin_id'], 'USDT', 'log_trade', $v['id']);
            }
            //更新所有买单状态
            Db::connect(config('cli_db'))->execute("UPDATE `tp_log_trade` SET `deal_num` = `num`,`deal_fee` = `fee`,`status` = 2 WHERE `type` = 1 AND `status` = 0 AND `date`={$date} AND exch_coin_id in ($coinid)");
            //平台当天各交易对入金量
            $today = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('main_coin_id,exch_coin_id,sum(total) total,sum(num) num')->group('main_coin_id,exch_coin_id')->select();

            //查询用户卖单
            $trade_sell = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'status' => 0, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('id,price,num,total,fee,user_id,pid,main_coin_id,exch_coin_id,match_level')->select();

            //添加当天K线
            foreach ($today as $k => $v) {
                if ($v['exch_coin_id'] == 24) {
                    $float = 100000;
                } else {
                    $float = 50000;
                }
                Db::connect(config('cli_db'))->name('log_trade_day')->where(['date' => $date, 'main_coin_id' => $v['main_coin_id'], 'exch_coin_id' => $v['exch_coin_id']])->update([
                    'num' => $v['num'] ? ($v['num'] + $float) : $float,
                    'addtime' => $time,
                ]);
                //各等级匹配数量和挂卖数量
                $matching = Db::connect(config('cli_db'))->name('matching')->field('matching_id,rate_' . $v['exch_coin_id'])->select();
                $other_order_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'other_order_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
                foreach ($matching as $vv) {
                    $match = $other_order_rate * $v['total'] * $vv['rate_' . $v['exch_coin_id']] / 100;//总入金量的20%
                    $num = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'date' => $date, 'match_level' => $vv['matching_id'], 'exch_coin_id' => $v['exch_coin_id']])->count();
                    if ($num != 0) {
                        $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = bcdiv($match, $num, 8);
                    } else {
                        $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = 0;
                    }
                }
            }

            foreach ($trade_sell as $v) {
                $dircet_buy_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'dircet_buy_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
                //下级提供的usdt撮合量(下级入金量的50%)
                $amount1 = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'pid' => $v['user_id'], 'date' => $date, 'exch_coin_id' => $v['exch_coin_id']])->sum('total') * $dircet_buy_rate;
                //平台提供的usdt撮合量
                $amount2 = $level[$v['exch_coin_id']][$v['match_level']]['average'];
                //总usdt撮合量
                $amount = bcadd($amount1, $amount2, 8);
                //记录卖家当天撮合量
                Db::connect(config('cli_db'))->name('log_trade')->where(['user_id' => $v['user_id'], 'date' => $date, 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->update([
                    'amount1' => $amount1,
                    'amount2' => $amount2 ? $amount : 0,
                    'amount' => $amount,
                ]);
                //可以撮合的数量
                $match_num = bcdiv($amount, $v['price'], 4);

                if ($match_num >= $v['num']) {
                    $status = 2;
                    $deal_num = $v['num'];
                    $total = $v['total'];
                    $deal_fee = $v['fee'];
                } else {
                    $status = 1;
                    $deal_num = $match_num;
                    $total = bcmul($match_num, $v['price'], 8);
                    $deal_fee = bcmul($total, $fee_rate, 8);
                }

                //更新状态
                Db::connect(config('cli_db'))->name('log_trade')->where(['id' => $v['id']])->update([
                    'deal_num' => $deal_num,
                    'deal_fee' => $deal_fee,
                    'status' => $status,
                ]);
                //查询余额
                $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
                $new_usdt_balance = bcadd($old_usdt_balance, $total, 8);
                //更新usdt余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                    'balance' => $new_usdt_balance,
                ]);
                //log_coin日志
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['main_coin_id'],
                    'type' => 62,
                    'num' => $total,
                    'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                    'balance' => $new_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
                //返给卖家上级30%手续费
                if ($v['pid']) {
                    //查询上级余额
                    $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $v['pid']])->value('balance'));
                    $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $deal_fee * 0.3, 8);
                    //更新上级余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['pid'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 67,
                        'num' => $deal_fee * 0.3,
                        'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                        'balance' => $new_parent_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }

                //撤销未完成的卖单
                if ($status == 1) {
                    //查询余额
                    $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
                    $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');

                    $new_usdt_balance = bcadd($old_usdt_balance, bcsub($v['fee'], $deal_fee, 8), 8);
                    $new_ido_balance = bcadd($old_ido_balance, bcsub($v['num'], $deal_num, 8), 8);
                    //更新余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                        'balance' => $new_usdt_balance,
                    ]);
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                        'balance' => $new_ido_balance,
                    ]);
                    //log_coin日志
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['user_id'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 64,
                        'num' => bcsub($v['fee'], $deal_fee, 8),
                        'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                        'balance' => $new_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['user_id'],
                        'coin_id' => $v['exch_coin_id'],
                        'type' => 63,
                        'num' => bcsub($v['num'], $deal_num, 8),
                        'amount' => $old_ido_balance ? $old_ido_balance : 0,
                        'balance' => $new_ido_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }
            }

            foreach ($today as $k => $v) {
                //计算当天资金池收益
                $sell_total = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => $date, 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_num*price');
                $fee = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => $date, 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_fee');
                Db::connect(config('cli_db'))->name('cash_pool_trade')->insert([
                    'date' => $date,
                    'main_coin_id' => $v['main_coin_id'],
                    'exch_coin_id' => $v['exch_coin_id'],
                    'buy_total' => $v['total'] ? $v['total'] : 0,
                    'sell_total' => $sell_total,
                    'left' => bcsub($v['total'], $sell_total, 8),
                    'fee' => $fee,
                    'total' => bcadd(bcsub($v['total'], $sell_total, 8), $fee, 8),
                    'addtime' => time(),
                ]);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            file_put_contents(ROOT_PATH . 'cron/log/' . 'error' . '_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
        }
    }

    //上午场匹配
    public function match_trade2()
    {
        try {
            $coinid = '24';
            $coin_id = [24];
            $date = date('Ymd');
            $time = time();
            $fee_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'trade_fee_sell'])->value('value');
            //合并用户买单和卖单至log_trade
            Db::connect(config('cli_db'))->query("insert into tp_log_trade (`type`,`match_level`,`date`,`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,`num`,`total`,`deal_num`,`fee`,`deal_fee`,`status`,`addtime`,`remark`) select `type`,`final_level`,{$date},`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,sum(`num`),sum(`total`),0,sum(`fee`),0,0,$time,'' from tp_trade where `date`={$date} and `status`=0 and exch_coin_id in ($coinid) group by `user_id`,`type`,`main_coin_id`,`exch_coin_id` ORDER BY user_id desc");

            //trade表数据修改为已匹配
            Db::connect(config('cli_db'))->execute("UPDATE `tp_trade` SET `status` = 1,`uptime` = $time WHERE `status` = 0 AND `date` = $date and exch_coin_id in ($coinid)");

            //查询用户买单
            $trade_buy = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'status' => 0, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('id,num,total,fee,user_id,pid,main_coin_id,exch_coin_id')->select();

            foreach ($trade_buy as $k => $v) {
                //更新用户级别
//                $user_level = Db::connect(config('cli_db'))->name('users')->where(array('user_id' => $v['user_id']))->value('user_level');
//                if ($user_level < 3) {
//                    Db::connect(config('cli_db'))->name('users')->where(array('user_id' => $v['user_id']))->update(['user_level' => 3]);
//                }

                $user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->field('pid,lft,rgt,depth,buy_total,team_total')->find();
                //更新团队业绩和用户级别
                Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->setInc('buy_total', $v['total']);
                Db::connect(config('cli_db'))->execute("update tp_user_rela set `team_total`=(`team_total`+{$v['total']}) where `lft`<={$user_rela['lft']} and `rgt`>={$user_rela['rgt']} and `depth`<={$user_rela['depth']} and `depth`>0");

                //查询ido余额
                $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');
                $new_ido_balance = bcadd($old_ido_balance, $v['num'], 8);
                //更新ido余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                    'balance' => $new_ido_balance,
                ]);
                //log_coin日志
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['exch_coin_id'],
                    'type' => 61,
                    'num' => $v['num'],
                    'amount' => $old_ido_balance ? $old_ido_balance : 0,
                    'balance' => $new_ido_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);

                //返给买家上级30%手续费
                if ($user_rela['pid']) {
                    //查询上级余额
                    $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $user_rela['pid']])->value('balance'));
                    $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $v['fee'] * 0.3, 8);
                    //更新上级余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $user_rela['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);

                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $user_rela['pid'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 67,
                        'num' => $v['fee'] * 0.3,
                        'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                        'balance' => $new_parent_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }

                //生成分红记录
                //$this->get_bonus($v['user_id'], $v['total'], $v['main_coin_id'], 'USDT', 'log_trade', $v['id']);

                //usdt价格
                $usdt_cny = Db::connect(config('cli_db'))->name('coin')->where(['coin_id' => 2])->value('price_cny');
                //如果超过10w usdt，返还部分usdt给用户
                if ($v['total'] >= 100000 / $usdt_cny) {
                    $old_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $v['user_id']])->value('balance');
                    if ($v['total'] < 200000 / $usdt_cny) {
                        $return = 0.05 * $v['total'];
                    }
                    if ($v['total'] >= 200000 / $usdt_cny) {
                        $return = 0.1 * $v['total'];
                    }
                    $new_balance = bcadd($old_balance, $return, 8);
                    //更新余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $v['user_id']])->update(['balance' => $new_balance]);
                    //余额变动日志
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['user_id'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 70,
                        'num' => $return,
                        'amount' => $old_balance,
                        'balance' => $new_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }
            }
            //更新所有买单状态
            Db::connect(config('cli_db'))->execute("UPDATE `tp_log_trade` SET `deal_num` = `num`,`deal_fee` = `fee`,`status` = 2 WHERE `type` = 1 AND `status` = 0 AND `date`={$date} AND exch_coin_id in ($coinid)");
            //平台当天各交易对入金量
            $today = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('main_coin_id,exch_coin_id,sum(total) total,sum(num) num')->group('main_coin_id,exch_coin_id')->select();

            //查询用户卖单
            $trade_sell = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'status' => 0, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('id,price,num,total,fee,user_id,pid,main_coin_id,exch_coin_id,match_level')->select();

            //添加当天K线
            foreach ($today as $k => $v) {
                if ($v['exch_coin_id'] == 24) {
                    $float = 100000;
                } else {
                    $float = 50000;
                }
                Db::connect(config('cli_db'))->name('log_trade_day')->where(['date' => $date, 'main_coin_id' => $v['main_coin_id'], 'exch_coin_id' => $v['exch_coin_id']])->update([
                    'num' => $v['num'] ? ($v['num'] + $float) : $float,
                    'addtime' => $time,
                ]);
                //各等级匹配数量和挂卖数量
                $matching = Db::connect(config('cli_db'))->name('matching')->field('matching_id,rate_' . $v['exch_coin_id'])->select();
                $other_order_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'other_order_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
                foreach ($matching as $vv) {
                    $match = $other_order_rate * $v['total'] * $vv['rate_' . $v['exch_coin_id']] / 100;//总入金量的20%
                    $num = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'date' => $date, 'match_level' => $vv['matching_id'], 'exch_coin_id' => $v['exch_coin_id']])->count();
                    if ($num != 0) {
                        $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = bcdiv($match, $num, 8);
                    } else {
                        $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = 0;
                    }
                }
            }

            foreach ($trade_sell as $v) {
                $dircet_buy_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'dircet_buy_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
                //下级提供的usdt撮合量(下级入金量的50%)
                $amount1 = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'pid' => $v['user_id'], 'date' => $date, 'exch_coin_id' => $v['exch_coin_id']])->sum('total') * $dircet_buy_rate;
                //平台提供的usdt撮合量
                $amount2 = $level[$v['exch_coin_id']][$v['match_level']]['average'];
                //总usdt撮合量
                $amount = bcadd($amount1, $amount2, 8);
                //记录卖家当天撮合量
                Db::connect(config('cli_db'))->name('log_trade')->where(['user_id' => $v['user_id'], 'date' => $date, 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->update([
                    'amount1' => $amount1,
                    'amount2' => $amount2 ? $amount : 0,
                    'amount' => $amount,
                ]);
                //可以撮合的数量
                $match_num = bcdiv($amount, $v['price'], 4);

                if ($match_num >= $v['num']) {
                    $status = 2;
                    $deal_num = $v['num'];
                    $total = $v['total'];
                    $deal_fee = $v['fee'];
                } else {
                    $status = 1;
                    $deal_num = $match_num;
                    $total = bcmul($match_num, $v['price'], 8);
                    $deal_fee = bcmul($total, $fee_rate, 8);
                }

                //更新状态
                Db::connect(config('cli_db'))->name('log_trade')->where(['id' => $v['id']])->update([
                    'deal_num' => $deal_num,
                    'deal_fee' => $deal_fee,
                    'status' => $status,
                ]);
                //查询余额
                $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
                $new_usdt_balance = bcadd($old_usdt_balance, $total, 8);
                //更新usdt余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                    'balance' => $new_usdt_balance,
                ]);
                //log_coin日志
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['main_coin_id'],
                    'type' => 62,
                    'num' => $total,
                    'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                    'balance' => $new_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
                //返给卖家上级30%手续费
                if ($v['pid']) {
                    //查询上级余额
                    $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $v['pid']])->value('balance'));
                    $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $deal_fee * 0.3, 8);
                    //更新上级余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['pid'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 67,
                        'num' => $deal_fee * 0.3,
                        'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                        'balance' => $new_parent_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }

                //撤销未完成的卖单
                if ($status == 1) {
                    //查询余额
                    $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
                    $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');

                    $new_usdt_balance = bcadd($old_usdt_balance, bcsub($v['fee'], $deal_fee, 8), 8);
                    $new_ido_balance = bcadd($old_ido_balance, bcsub($v['num'], $deal_num, 8), 8);
                    //更新余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                        'balance' => $new_usdt_balance,
                    ]);
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                        'balance' => $new_ido_balance,
                    ]);
                    //log_coin日志
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['user_id'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 64,
                        'num' => bcsub($v['fee'], $deal_fee, 8),
                        'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                        'balance' => $new_usdt_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $v['user_id'],
                        'coin_id' => $v['exch_coin_id'],
                        'type' => 63,
                        'num' => bcsub($v['num'], $deal_num, 8),
                        'amount' => $old_ido_balance ? $old_ido_balance : 0,
                        'balance' => $new_ido_balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                    ]);
                }
            }

            foreach ($today as $k => $v) {
                //计算当天资金池收益
                $sell_total = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => $date, 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_num*price');
                $fee = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => $date, 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_fee');
                Db::connect(config('cli_db'))->name('cash_pool_trade')->insert([
                    'date' => $date,
                    'main_coin_id' => $v['main_coin_id'],
                    'exch_coin_id' => $v['exch_coin_id'],
                    'buy_total' => $v['total'] ? $v['total'] : 0,
                    'sell_total' => $sell_total,
                    'left' => bcsub($v['total'], $sell_total, 8),
                    'fee' => $fee,
                    'total' => bcadd(bcsub($v['total'], $sell_total, 8), $fee, 8),
                    'addtime' => time(),
                ]);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            file_put_contents(ROOT_PATH . 'cron/log/' . 'error' . '_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
        }
    }

    //级差奖励
    public function buy_level_bonus()
    {
        $coinid = '24';
        $coin_id = [24];
        $date = date('Ymd');
        $time = time();

        $rate = ['0' => 0, '1' => 0.1, '2' => 0.15, '3' => 0.2];

        //查询用户买单
        $trade_buy = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => $date, 'exch_coin_id' => ['in', $coin_id]])->field('id,num,total,fee,user_id,pid,main_coin_id,exch_coin_id')->select();

        foreach ($trade_buy as $k => $v) {
            $user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->field('pid,buy_level,buy_total')->find();

            $p1_user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $user_rela['pid']])->field('pid,buy_level')->find();

            //查询p1有没有挂卖单
            $p1_trade = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'date' => $date, 'exch_coin_id' => ['in', $coin_id], 'user_id' => $user_rela['pid']])->count();
            if ($p1_trade == 0) {
                //p1的入金等级
                if ($p1_user_rela['buy_level'] > $user_rela['buy_level']) {
                    //查询余额
                    $p1_old_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $user_rela['pid'], 'coin_id' => $v['main_coin_id']])->value('balance');
                    $num = $v['total'] * ($rate[$p1_user_rela['buy_level']] - $rate[$user_rela['buy_level']]);
                    $p1_new_balance = bcadd($p1_old_balance, $num, 8);
                    //更新usdt余额
                    Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $user_rela['pid'], 'coin_id' => $v['main_coin_id']])->update([
                        'balance' => $p1_new_balance,
                    ]);
                    //log_coin日志
                    Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                        'user_id' => $user_rela['pid'],
                        'coin_id' => $v['main_coin_id'],
                        'type' => 130,
                        'num' => $num,
                        'amount' => $p1_old_balance,
                        'balance' => $p1_new_balance,
                        'addtime' => $time,
                        'status' => 1,
                        'union' => 'log_trade',
                        'union_id' => $v['id'],
                        'remark' => '1代级差奖励(' . $rate[$p1_user_rela['buy_level']] . '-' . $rate[$user_rela['buy_level']] . ')*' . $v['total'] . '=' . $num,
                    ]);
                }
            }
            if ($p1_user_rela['pid'] != 0) {
                //查询p2有没有挂卖单
                $p2_trade = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'date' => $date, 'exch_coin_id' => ['in', $coin_id], 'user_id' => $p1_user_rela['pid']])->count();
                if ($p2_trade == 0) {
                    $p2_user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $p1_user_rela['pid']])->field('pid,buy_level')->find();
                    //p2的入金等级
                    if ($p2_user_rela['buy_level'] > $p1_user_rela['buy_level'] && $p2_user_rela['buy_level'] > $user_rela['buy_level']) {
                        //查询余额
                        $p2_old_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $p1_user_rela['pid'], 'coin_id' => $v['main_coin_id']])->value('balance');
                        $num = $v['total'] * ($rate[$p2_user_rela['buy_level']] - $rate[$user_rela['buy_level']]);
                        $p2_new_balance = bcadd($p2_old_balance, $num, 8);
                        //更新usdt余额
                        Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $p1_user_rela['pid'], 'coin_id' => $v['main_coin_id']])->update([
                            'balance' => $p2_new_balance,
                        ]);
                        //log_coin日志
                        Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                            'user_id' => $p1_user_rela['pid'],
                            'coin_id' => $v['main_coin_id'],
                            'type' => 131,
                            'num' => $num,
                            'amount' => $p2_old_balance,
                            'balance' => $p2_new_balance,
                            'addtime' => $time,
                            'status' => 1,
                            'union' => 'log_trade',
                            'union_id' => $v['id'],
                            'remark' => '2代级差奖励(' . $rate[$p2_user_rela['buy_level']] . '-' . $rate[$user_rela['buy_level']] . ')*' . $v['total'] . '=' . $num,
                        ]);
                    }
                }
            }
        }
        $this->update_buy_level();
    }

    //更新用户入金等级
    public function update_buy_level()
    {
        $data = Db::connect(config('cli_db'))->name('user_rela')->where(['buy_total' => ['gt', 0]])->field('user_id,buy_total,buy_level')->select();
        foreach ($data as $k => $v) {
            $new_buy_level = 0;
            if ($v['buy_total'] >= 200) $new_buy_level = 1;
            if ($v['buy_total'] >= 2000) $new_buy_level = 2;
            if ($v['buy_total'] >= 20000) $new_buy_level = 3;
            if ($new_buy_level != $v['buy_level']) {
                Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->update(['buy_level' => $new_buy_level]);
            }
        }
    }

    /*public function match_trade()
    {
        $date = date('Ymd');
        $time = time();
        $fee_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'trade_fee_sell'])->value('value');
        $other_order_rate = Db::connect(config('cli_db'))->name('config')->where(['name' => 'other_order_rate'])->value('value');
        //合并用户买单和卖单至log_trade
        Db::connect(config('cli_db'))->query("insert into tp_log_trade (`type`,`date`,`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,`num`,`total`,`deal_num`,`fee`,`deal_fee`,`status`,`addtime`,`remark`) select `type`,{$date},`user_id`,`pid`,`main_coin_id`,`exch_coin_id`,`price`,sum(`num`),sum(`total`),0,sum(`fee`),0,0,$time,'' from tp_trade where `date`={$date} and `status`=0 group by `user_id`,`type`,`main_coin_id`,`exch_coin_id` ORDER BY user_id desc");

        //trade表数据修改为已匹配
        Db::connect(config('cli_db'))->execute("UPDATE `tp_trade` SET `status` = 1,`uptime` = $time WHERE `status` = 0 AND `date` = $date");

        //查询用户买单
        $trade_buy = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'status' => 0, 'date' => date('Ymd')])->field('id,num,total,fee,user_id,pid,main_coin_id,exch_coin_id')->select();
        foreach ($trade_buy as $k => $v) {
            $user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->field('pid,lft,rgt,depth,buy_total,team_total')->find();
            //更新团队业绩和用户级别
            Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->setInc('buy_total', $v['total']);
            Db::connect(config('cli_db'))->execute("update tp_user_rela set `team_total`=(`team_total`+{$v['total']}) where `lft`<={$user_rela['lft']} and `rgt`>={$user_rela['rgt']} and `depth`<={$user_rela['depth']} ");

            //查询ido余额
            $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');
            $new_ido_balance = bcadd($old_ido_balance, $v['num'], 8);
            //更新ido余额
            Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                'balance' => $new_ido_balance,
            ]);
            //log_coin日志
            Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                'user_id' => $v['user_id'],
                'coin_id' => $v['exch_coin_id'],
                'type' => 61,
                'num' => $v['num'],
                'amount' => $old_ido_balance ? $old_ido_balance : 0,
                'balance' => $new_ido_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_trade',
                'union_id' => $v['id'],
            ]);

            //返给买家上级30%手续费
            if ($user_rela['pid']) {
                //查询上级余额
                $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $user_rela['pid']])->value('balance'));
                $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $v['fee'] * 0.3, 8);
                //更新上级余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $user_rela['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);

                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $user_rela['pid'],
                    'coin_id' => $v['main_coin_id'],
                    'type' => 67,
                    'num' => $v['fee'] * 0.3,
                    'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                    'balance' => $new_parent_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
            }

            //生成分红记录
            $this->get_bonus($v['user_id'], $v['total'], $v['main_coin_id'], 'USDT', 'log_trade', $v['id']);
        }
        //更新所有买单状态
        Db::connect(config('cli_db'))->execute("UPDATE `tp_log_trade` SET `deal_num` = `num`,`deal_fee` = `fee`,`status` = 2 WHERE `type` = 1 AND `status` = 0 AND `date`=" . date('Ymd'));
        //平台当天各交易对入金量
        $today = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => date('Ymd')])->field('main_coin_id,exch_coin_id,sum(total) total,sum(num) num')->group('main_coin_id,exch_coin_id')->select();

        //查询用户卖单
        $trade_sell = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'status' => 0, 'date' => date('Ymd')])->field('id,price,num,total,fee,user_id,pid,main_coin_id,exch_coin_id')->select();

        //计算每个用户每天挂卖撮合量
        foreach ($trade_sell as $k => $v) {
            //查询用户等级
            $team_total = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $v['user_id']])->value('team_total');
            //查询后台设置的用户匹配级别，如后台有设置则后台设置优先
            $user_matching_id = Db::connect(config('cli_db'))->name('users')->where(['user_id' => $v['user_id']])->value('matching_level');
            if ($user_matching_id == 0) {
                $matching_id = Db::connect(config('cli_db'))->name('matching')->where(['min' => ['elt', $team_total], 'max' => ['egt', $team_total]])->value('matching_id');
            } else {
                $matching_id = $user_matching_id;
            }
            $trade_sell[$k]['match_level'] = $matching_id;
            //更新当天卖单的匹配等级
            Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'user_id' => $v['user_id'], 'date' => date('Ymd')])->update(['match_level' => $matching_id]);
        }

        //各等级匹配数量和挂卖数量
        $matching = Db::connect(config('cli_db'))->name('matching')->field('matching_id,rate')->select();
        //添加当天K线
        foreach ($today as $k => $v) {
            $price = Db::connect(config('cli_db'))->name('price')->where(['date' => date('Ymd'), 'coin_id' => $v['exch_coin_id']])->value('price');
            Db::connect(config('cli_db'))->name('log_trade_day')->insert([
                'date' => $date,
                'main_coin_id' => $v['main_coin_id'],
                'exch_coin_id' => $v['exch_coin_id'],
                'num' => $v['num'] ? $v['num'] : 0,
                'open_price' => $price,
                'close_price' => $price,
                'high_price' => $price,
                'low_price' => $price,
                'addtime' => $time,
            ]);
            foreach ($matching as $vv) {
                $level[$v['exch_coin_id']][$vv['matching_id']]['match'] = $other_order_rate * $v['total'] * $vv['rate'] / 100;//总入金量的20%
                $level[$v['exch_coin_id']][$vv['matching_id']]['sum'] = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 2, 'date' => date('Ymd'), 'match_level' => $vv['matching_id'], 'exch_coin_id' => $v['exch_coin_id']])->sum('total');
            }

            $sum[$v['exch_coin_id']]['num'] = $v['num'];
            $sum[$v['exch_coin_id']]['total'] = $v['total'];
        }

        foreach ($trade_sell as $v) {
            //下级提供的usdt撮合量(下级入金量的50%)
            $amount1 = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'pid' => $v['user_id'], 'date' => date('Ymd'), 'exch_coin_id' => $v['exch_coin_id']])->sum('total') * 0.5;
            //平台提供的usdt撮合量
            $amount2 = $v['total'] / $level[$v['exch_coin_id']][$v['match_level']]['sum'] * $level[$v['exch_coin_id']][$v['match_level']]['match'];
            //总usdt撮合量
            $amount = bcadd($amount1, $amount2, 4);
            //记录卖家当天撮合量
            Db::connect(config('cli_db'))->name('log_trade')->where(['user_id' => $v['user_id'], 'date' => date('Ymd'), 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->update([
                'amount1' => $amount1,
                'amount2' => $amount2,
                'amount' => $amount,
            ]);
            //可以撮合的数量
            $match_num = bcdiv($amount, $v['price'], 4);

            if ($match_num >= $v['num']) {
                $status = 2;
                $deal_num = $v['num'];
                $total = $v['total'];
                $deal_fee = $v['fee'];
            } else {
                $status = 1;
                $deal_num = $match_num;
                $total = bcmul($match_num, $v['price'], 8);
                $deal_fee = bcmul($total, $fee_rate, 8);
            }

            //更新状态
            Db::connect(config('cli_db'))->name('log_trade')->where(['id' => $v['id']])->update([
                'deal_num' => $deal_num,
                'deal_fee' => $deal_fee,
                'status' => $status,
            ]);
            //查询余额
            $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
            $new_usdt_balance = bcadd($old_usdt_balance, $total, 8);
            //更新usdt余额
            Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                'balance' => $new_usdt_balance,
            ]);
            //log_coin日志
            Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                'user_id' => $v['user_id'],
                'coin_id' => $v['main_coin_id'],
                'type' => 62,
                'num' => $total,
                'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                'balance' => $new_usdt_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_trade',
                'union_id' => $v['id'],
            ]);
            //返给卖家上级30%手续费
            if ($v['pid']) {
                //查询上级余额
                $old_parent_usdt_balance = floatval(Db::connect(config('cli_db'))->name('user_coin')->where(['coin_id' => $v['main_coin_id'], 'user_id' => $v['pid']])->value('balance'));
                $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $deal_fee * 0.3, 8);
                //更新上级余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['pid'], 'coin_id' => $v['main_coin_id']])->update(['balance' => $new_parent_usdt_balance]);
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['pid'],
                    'coin_id' => $v['main_coin_id'],
                    'type' => 67,
                    'num' => $deal_fee * 0.3,
                    'amount' => $old_parent_usdt_balance ? $old_parent_usdt_balance : 0,
                    'balance' => $new_parent_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
            }

            //撤销未完成的卖单
            if ($status == 1) {
                //查询余额
                $old_usdt_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->value('balance');
                $old_ido_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->value('balance');

                $new_usdt_balance = bcadd($old_usdt_balance, bcsub($v['fee'], $deal_fee, 8), 8);
                $new_ido_balance = bcadd($old_ido_balance, bcsub($v['num'], $deal_num, 8), 8);
                //更新余额
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['main_coin_id']])->update([
                    'balance' => $new_usdt_balance,
                ]);
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['exch_coin_id']])->update([
                    'balance' => $new_ido_balance,
                ]);
                //log_coin日志
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['main_coin_id'],
                    'type' => 64,
                    'num' => bcsub($v['fee'], $deal_fee, 8),
                    'amount' => $old_usdt_balance ? $old_usdt_balance : 0,
                    'balance' => $new_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
                Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                    'user_id' => $v['user_id'],
                    'coin_id' => $v['exch_coin_id'],
                    'type' => 63,
                    'num' => bcsub($v['num'], $deal_num, 8),
                    'amount' => $old_ido_balance ? $old_ido_balance : 0,
                    'balance' => $new_ido_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => $v['id'],
                ]);
            }
        }

        foreach ($today as $k => $v) {
            //计算当天资金池收益
            $sell_total = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => date('Ymd'), 'type' => 2, 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_num*price');
            $fee = Db::connect(config('cli_db'))->name('log_trade')->where(['date' => date('Ymd'), 'exch_coin_id' => $v['exch_coin_id']])->sum('deal_fee');
            Db::connect(config('cli_db'))->name('cash_pool_trade')->insert([
                'date' => date('Ymd'),
                'main_coin_id' => $v['main_coin_id'],
                'exch_coin_id' => $v['exch_coin_id'],
                'buy_total' => $v['total'] ? $v['total'] : 0,
                'sell_total' => $sell_total,
                'left' => bcsub($v['total'], $sell_total, 8),
                'fee' => $fee,
                'total' => bcadd(bcsub($v['total'], $sell_total, 8), $fee, 8),
                'addtime' => time(),
            ]);
        }
    }*/

    //自动撤销下午场未成交订单
    public function cancel_market()
    {
        $data = Db::connect(config('cli_db'))->name('market')->where(['status' => ['in', [0, 1]]])->select();
        foreach ($data as $v) {
            //更新状态
            Db::connect(config('cli_db'))->name('market')->where(['market_id' => $v['market_id']])->update([
                'status' => -1,
            ]);

            //查询余额
            $old_main_coin_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => 2])->value('balance');
            $old_exch_coin_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['coin_id']])->value('balance');

            $new_main_coin_balance = bcadd($old_main_coin_balance, bcsub($v['fee'], $v['deal_fee'], 8), 8);
            $new_exch_coin_balance = bcadd($old_exch_coin_balance, bcsub($v['num'], $v['deal_num'], 8), 8);
            //更新余额
            Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => 2])->update([
                'balance' => $new_main_coin_balance,
            ]);
            Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $v['user_id'], 'coin_id' => $v['coin_id']])->update([
                'balance' => $new_exch_coin_balance,
            ]);
            //log_coin日志
            Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                'user_id' => $v['user_id'],
                'coin_id' => 2,
                'type' => 66,
                'num' => bcsub($v['fee'], $v['deal_fee'], 8),
                'amount' => $old_main_coin_balance ? $old_main_coin_balance : 0,
                'balance' => $new_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $v['market_id'],
            ]);
            Db::connect(config('cli_db'))->name('log_coin')->insertGetId([
                'user_id' => $v['user_id'],
                'coin_id' => $v['coin_id'],
                'type' => 65,
                'num' => bcsub($v['num'], $v['deal_num'], 8),
                'amount' => $old_exch_coin_balance ? $old_exch_coin_balance : 0,
                'balance' => $new_exch_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $v['market_id'],
            ]);
        }

        //统计下午场收益
        $market = Db::connect(config('cli_db'))->name('market')->where('date', date('Ymd'))->field('coin_id,sum(deal_num*price) buy_total,sum(deal_num*price*0.6) fact_total,sum(deal_fee) fee')->group('coin_id')->select();
        $fee_rate_sell = Db::connect(config('cli_db'))->name('config')->where(['name' => 'trade_fee_sell'])->value('value');
        $fee_rate_buy = Db::connect(config('cli_db'))->name('config')->where(['name' => 'trade_fee_buy'])->value('value');
        foreach ($market as $v) {
            $fee = $v['fee'] * (1 + ($fee_rate_buy / $fee_rate_sell));
            Db::connect(config('cli_db'))->name('cash_pool_market')->insert([
                'date' => date('Ymd'),
                'exch_coin_id' => $v['coin_id'],
                'buy_total' => $v['buy_total'],
                'fact_total' => $v['fact_total'],
                'left' => bcsub($v['buy_total'], $v['fact_total'], 8),
                'fee' => $fee,
                'total' => bcadd(bcsub($v['buy_total'], $v['fact_total'], 8), $fee, 8),
                'addtime' => time(),
            ]);
        }
    }

    /**
     * 交易入金分红
     * @param $user_id 操作用户ID
     * @param $num 数量
     * @param $coin_id 通证ID
     * @param $name 通证名称
     * @param $union  关联表名
     * @param $union_id 关联ID
     * @return float|string
     */
    function get_bonus($user_id, $num, $coin_id, $name, $union, $union_id)
    {
        //查询用户左右节点
        $user_rela = Db('user_rela')->where(['user_id' => $user_id])->field('lft,rgt')->lock(true)->find();

        //查询离得最近的上级合伙人ID
        $where = 'lft<' . $user_rela['lft'] . ' and rgt>' . $user_rela['rgt'] . ' and vip_node=1 and vip_time<' . time();
        $pid = Db('user_rela')->where($where)->order('depth desc')->value('user_id');
//    if(!$pid) return;
        Db::startTrans();
        try {

            Db('log_bonus')->insert(['user_id' => $pid, 'cid' => $user_id, 'num' => $num, 'returns' => $num * 0.1, 'addtime' => time(), 'remark' => '团队成员入金分红', 'coin_id' => $coin_id, 'coin_name' => $name, 'union' => $union, 'union_id' => $union_id, 'date' => date('Ymd')]);


            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }
    }

    //定时获取价格
    public function get_price()
    {
        $data = Db::connect(config('cli_db'))->name('coin_trade')->where('status', 1)->field('trade_name,exch_coin_name')->select();
        foreach ($data as $v) {
            if ($v['trade_name'] != 'IDOUSDT' && $v['trade_name'] != 'ACHUSDT') {
                $res = http('http://api.zb.cn/data/v1/kline?market=' . $v['trade_name'] . '&type=1day')['data'];
                if ($res) {
                    $price['price'] = $res[sizeof($res) - 1][4];
                    $price['open_price'] = $res[sizeof($res) - 1][1];
                    $price['volume'] = $res[sizeof($res) - 1][5];
                    Db::connect(config('cli_db'))->name('coin')->where('name', $v['exch_coin_name'])->update($price);
                }
            }
        }
    }

    public function k_line()
    {
        $data = Db::connect(config('cli_db'))->name('coin')->where(['status' => 1, 'is_trade' => 0])->field('coin_id')->select();

        foreach ($data as $v) {
            if (Db::connect(config('cli_db'))->name('log_trade_day')->where(['exch_coin_id' => $v['coin_id'], 'date' => date('Ymd')])->count() < 1) {
                $yestoday = Db::connect(config('cli_db'))->name('log_trade_day')->where(['exch_coin_id' => $v['coin_id']])->field('num,close_price')->order('id desc')->find();
                $today_price = Db::connect(config('cli_db'))->name('price')->where(['date' => date('Ymd'), 'coin_id' => $v['coin_id']])->field('price,cny_price')->find();
                Db::connect(config('cli_db'))->name('log_trade_day')->insert([
                    'date' => date('Ymd'),
                    'main_coin_id' => 2,
                    'exch_coin_id' => $v['coin_id'],
                    'num' => $yestoday['num'] ? $yestoday['num'] : 0,
                    'cny_price' => $today_price['cny_price'],
                    'open_price' => $yestoday['close_price'] ? $yestoday['close_price'] : 0,
                    'close_price' => $today_price['price'],
                    'high_price' => $today_price['price'],
                    'low_price' => $yestoday['close_price'] ? $yestoday['close_price'] : 0,
                    'addtime' => time(),
                ]);
            }
        }
    }

    /**
     * 更新会员等级
     * @param $user_id
     * @param $depth
     * @throws \think\Exception
     */
    public function up_user_level()
    {
        //Db::connect(config('cli_db'))->query("insert into tp_user_coin (`user_id`,`coin_id`,`balance`) select user_id,24,0 from tp_users");die;

        $usdt_price = Db::connect(config('cli_db'))->name('coin')->where(['coin_id' => 2])->value('price_cny');
        $users = Db::connect(config('cli_db'))->name('user_rela')->where(['team_total' => ['egt', 1000000 / $usdt_price]])->field('user_id,team_total,user_level,admin_level')->select();

        if ($users) {
            foreach ($users as $user) {
                if ($user['team_total'] >= 20000000 / $usdt_price) {
                    if (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 2000000 / $usdt_price]])->count() >= 4) {
                        $user_level = 3;
                    } elseif (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 1000000 / $usdt_price]])->count() >= 3) {
                        $user_level = 2;
                    } elseif (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 300000 / $usdt_price]])->count() >= 2) {
                        $user_level = 1;
                    } else {
                        $user_level = 0;
                    }
                }

                if ($user['team_total'] >= 5000000 / $usdt_price && $user['team_total'] < 20000000 / $usdt_price) {
                    if (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 1000000 / $usdt_price]])->count() >= 3) {
                        $user_level = 2;
                    } elseif (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 300000 / $usdt_price]])->count() >= 2) {
                        $user_level = 1;
                    } else {
                        $user_level = 0;
                    }
                }

                if ($user['team_total'] >= 1000000 / $usdt_price && $user['team_total'] < 5000000 / $usdt_price) {
                    if (Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user['user_id'], 'team_total' => ['egt', 300000 / $usdt_price]])->count() >= 2) {
                        $user_level = 1;
                    } else {
                        $user_level = 0;
                    }
                }

                if ($user['user_level'] != $user_level) {
                    if ($user['admin_level'] != 0) {
                        $final_level = $user['admin_level'];
                    } else {
                        $final_level = $user_level;
                    }
                    Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $user['user_id']])->update(['user_level' => $user_level, 'final_level' => $final_level]);
                }
            }
        }

        //买入量
        $today = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => date('Ymd'), 'exch_coin_id' => 24, 'user_id' => ['not in', [10001, 10008]]])->field('main_coin_id,exch_coin_id,sum(total) total,sum(num) num')->group('main_coin_id,exch_coin_id')->select();
        $user_level_num = Db::connect(config('cli_db'))->name('user_rela')->where(['final_level' => ['neq', 0]])->field('count(*) count,final_level')->group('final_level')->select();
        //print_r($user_level_num);die;
        if (!$today || !$user_level_num) return;

        foreach ($today as $k => $v) {
            foreach ($user_level_num as $kk => $vv) {
                $bones[$v['exch_coin_id']][$vv['final_level']]['average'] = bcdiv($v['total'] * 0.01, $vv['count'], 8);
            }
        }
        $lists = Db::connect(config('cli_db'))->name('user_rela')->where(['final_level' => ['in', [1, 2, 3]]])->field('user_id,final_level')->select();
        foreach ($bones as $coin_id => $bone) {
            foreach ($lists as $list) {
                $old_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $list['user_id'], 'coin_id' => 2])->value('balance');
                $new_balance = bcadd($old_balance, $bones[$coin_id][$list['final_level']]['average'], 8);
                Db::connect(config('cli_db'))->name('log_coin')->insert([
                    'user_id' => $list['user_id'],
                    'coin_id' => 2,
                    'type' => 90,
                    'num' => $bones[$coin_id][$list['final_level']]['average'],
                    'amount' => $old_balance,
                    'balance' => $new_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => date('Ymd'),
                ]);
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $list['user_id'], 'coin_id' => 2])->update([
                    'balance' => $new_balance,
                ]);
            }
        }
    }

    //股东分成
    public function shareholder_rate()
    {
        $lists = Db::connect(config('cli_db'))->name('users')->where(['shareholder_rate' => ['gt', 0]])->field('user_id,shareholder_rate')->select();
        $today = Db::connect(config('cli_db'))->name('log_trade')->where(['type' => 1, 'date' => date('Ymd'), 'exch_coin_id' => 24, 'user_id' => ['not in', [10001, 10008]]])->sum('total');
        if ($today > 0) {
            foreach ($lists as $list) {
                $old_balance = Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $list['user_id'], 'coin_id' => 2])->value('balance');
                $return = bcmul($today, $list['shareholder_rate'] * 0.01, 8);
                $new_balance = bcadd($old_balance, $return, 8);

                Db::connect(config('cli_db'))->name('log_coin')->insert([
                    'user_id' => $list['user_id'],
                    'coin_id' => 2,
                    'type' => 91,
                    'num' => $return,
                    'amount' => $old_balance,
                    'balance' => $new_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_trade',
                    'union_id' => date('Ymd'),
                ]);
                Db::connect(config('cli_db'))->name('user_coin')->where(['user_id' => $list['user_id'], 'coin_id' => 2])->update([
                    'balance' => $new_balance,
                ]);
            }
        }
    }

    public function no_parent()
    {

        $list = Db::connect(config('cli_db'))->name('users')->order('user_id desc')->select();
        foreach ($list as $item) {
            if (!Db::connect(config('cli_db'))->name('users')->where(['user_id' => $item['pid']])->find()) {
                print $item['user_id'] . "\n";
            }
        }

    }
}