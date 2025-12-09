<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 币币交易
 * Class Trade
 * @package app\api\controller
 */
class Trade extends Base
{
    /**
     * 显示上午场
     * @param $token
     */
    public function show_morning($token, $trade_name)
    {
        $trade = db('coin_trade')->where('trade_name', $trade_name)->field('main_coin_id,exch_coin_id')->find();
        $price = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('price');
        if (!$price) $this->apiReply(2, '获取价格失败请联系管理员');
        $data['price'] = $price;
        $data['cny_price'] = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('cny_price');
        $data['exch_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['exch_coin_id'], 'user_id' => $this->user_id])->value('balance');
        $data['main_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->value('balance');

        if (!$data['exch_coin_balance']) $data['exch_coin_balance'] = 0;
        if (!$data['main_coin_balance']) $data['main_coin_balance'] = 0;

        $data['fee_sell'] = db('config')->where(['name' => 'trade_fee_sell'])->value('value');
        $data['fee_buy'] = db('config')->where(['name' => 'trade_fee_buy'])->value('value');
        $data['usdt_price'] = db('coin')->where(['coin_id' => $trade['main_coin_id']])->value('price_cny');

        $data['trade_buy_mini'] = db('config')->where(['name' => 'trade_buy_mini'])->value('value');
        $data['trade_sell_mini'] = db('config')->where(['name' => 'trade_sell_mini'])->value('value');

        $data['morning_start'] = db('config')->where(['name' => 'morning_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $data['morning_end'] = db('config')->where(['name' => 'morning_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        //$data['is_idaudit'] = Db('idaudit')->where(['user_id' => $this->user_id, 'status' => 1])->count();

        $this->apiResp($data);
    }

    /**
     * 显示下午场
     * @param $token
     */
    public function show_afternoon($token, $trade_name)
    {
        if ($trade_name == 'FIDUSDT') $this->apiReply(2, '该交易对暂不支持下午场');
        $trade = db('coin_trade')->where('trade_name', $trade_name)->field('main_coin_id,exch_coin_id')->find();
        $price = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('price');
        if (!$price) $this->apiReply(2, '获取价格失败请联系管理员');
        $data['price'] = bcmul($price, 1.05, 4);
        $price_cny = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('cny_price');
        $data['price_cny'] = bcmul($price_cny, 1.05, 4);
        $data['change_rate'] = 5.00;
        $data['open_price'] = $price;
        $data['last_price'] = db('coin')->where(['coin_id' => $trade['exch_coin_id']])->value('price');
        $data['exch_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['exch_coin_id'], 'user_id' => $this->user_id])->value('balance');
        $data['main_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->value('balance');

        if (!$data['exch_coin_balance']) $data['exch_coin_balance'] = 0;
        if (!$data['main_coin_balance']) $data['main_coin_balance'] = 0;


        $data['fee'] = db('config')->where(['name' => 'trade_fee_sell'])->value('value');
        $data['usdt_price'] = db('coin')->where(['coin_id' => $trade['main_coin_id']])->value('price_cny');

        $data['market_sell_mini'] = db('config')->where(['name' => 'market_sell_mini'])->value('value');

        $data['afternoon_start'] = db('config')->where(['name' => 'afternoon_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $data['afternoon_end'] = db('config')->where(['name' => 'afternoon_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        //$data['is_idaudit'] = Db('idaudit')->where(['user_id' => $this->user_id, 'status' => 1])->count();

        $this->apiResp($data);
    }

    /**
     * 上午场挂买
     * @param $trade_id
     * @param $num
     * @param $token
     */
    public function trade_buy($token, $num, $trade_name)
    {
        $this->isVerifyId();
        if ((date('w') == 6) || (date('w') == 0)) $this->apiReply(2, '周末休市！');

        $trade = db('coin_trade')->where(['trade_name' => $trade_name, 'status' => 1])->field('main_coin_id,main_coin_name,exch_coin_id,exch_coin_name')->find();
        if (!$trade) $this->apiReply(2, '交易对错误！');

        $morning_start = db('config')->where(['name' => 'morning_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $morning_end = db('config')->where(['name' => 'morning_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        if (time() < strtotime(date('Ymd') . $morning_start) || time() > strtotime(date('Ymd') . $morning_end)) $this->apiReply(2, '上午场时间为' . $morning_start . '到' . $morning_end . '！');

        $num = round(floatval($num), 2);
        if (bccomp($num, 0, 8) == -1) $this->apiReply(2, '请输入正确的数量！');

        $trade_buy_mini = db('config')->where(['name' => 'trade_buy_mini'])->value('value');
        $trade_buy_max = db('config')->where(['name' => 'trade_buy_max'])->value('value');
        if ($num < $trade_buy_mini || $num > $trade_buy_max) $this->apiReply(2, '数量应为' . $trade_buy_mini . '至' . $trade_buy_max . '！');

        $fee_rate = db('config')->where(['name' => 'trade_fee_buy'])->value('value');

        //只能挂一单
        $count = db('trade')->where(['user_id' => $this->user_id, 'date' => date('Ymd'), 'type' => 1, 'status' => 0, 'exch_coin_id' => $trade['exch_coin_id']])->count();
        if ($count) $this->apiReply(2, '您上午场有未完成的买单，可将其撤销后重新挂买！');

        Db::startTrans();
        try {
            //查询余额
            $old_main_coin_balance = floatval(db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->lock(true)->value('balance'));

            //查询价格
            $price = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('price');
            if (!$price) {
                Db::rollback();
                $this->apiReply(2, '获取价格失败请联系管理员');
            }

            $amount = bcmul($num, $price, 8);
            $fee = bcmul($amount, $fee_rate, 8);
            $total = floatval(bcadd($amount, $fee, 8));

            if ($total > $old_main_coin_balance) {
                Db::rollback();
                $this->apiReply(2, $trade['main_coin_name'] . '余额不足');
            }

            $new_main_coin_balance = bcsub($old_main_coin_balance, $total, 8);

            //user_coin表更新
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => 2])->update(['balance' => $new_main_coin_balance]);
            //trade表添加记录
            $trade_id = db('trade')->insertGetId([
                'user_id' => $this->user_id,
                'pid' => $this->user['pid'],
                'main_coin_id' => $trade['main_coin_id'],
                'exch_coin_id' => $trade['exch_coin_id'],
                'price' => $price,
                'num' => $num,
                'total' => $amount,
                'deal_num' => 0,
                'fee' => $fee,
                'deal_fee' => 0,
                'status' => 0,
                'addtime' => time(),
                'date' => date('Ymd'),
                'type' => 1,
            ]);
            //log_coin表增加记录
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $trade['main_coin_id'],
                'type' => 11,
                'num' => $total,
                'amount' => $old_main_coin_balance,
                'balance' => $new_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'trade',
                'union_id' => $trade_id,
                //'remark' => '上午场挂买' . $num . 'IDO',
            ]);

            //更新用户级别
//            if ($this->user['user_level'] < 3) {
//                db('users')->where(array('user_id' => $this->user_id))->update(['user_level' => 3]);
//                $this->user['user_level'] = 3;
//                cache('cache_userinfo_' . $this->user_id, $this->user);
//            }

            Db::commit();
            $this->apiReply(1, '操作成功');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 上午场挂卖
     * @param $num
     * @param $token
     */
    public function trade_sell($token, $num, $trade_name)
    {
        $this->isVerifyId();
        if ((date('w') == 6) || (date('w') == 0)) $this->apiReply(2, '周末休市！');

        $trade = db('coin_trade')->where(['trade_name' => $trade_name, 'status' => 1])->field('main_coin_id,main_coin_name,exch_coin_id,exch_coin_name')->find();
        if (!$trade) $this->apiReply(2, '交易对错误！');

        $morning_start = db('config')->where(['name' => 'morning_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $morning_end = db('config')->where(['name' => 'morning_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        if (time() < strtotime(date('Ymd') . $morning_start) || time() > strtotime(date('Ymd') . $morning_end)) $this->apiReply(2, '上午场时间为' . $morning_start . '到' . $morning_end . '！');

        $num = round(floatval($num), 2);
        if (bccomp($num, 0, 8) == -1) $this->apiReply(2, '请输入正确的数量！');

        $trade_sell_mini = db('config')->where(['name' => 'trade_sell_mini'])->value('value');
        $trade_sell_max = db('config')->where(['name' => 'trade_sell_max'])->value('value');
        if ($num < $trade_sell_mini || $num > $trade_sell_max) $this->apiReply(2, '数量应为' . $trade_sell_mini . '至' . $trade_sell_max . '！');

        $fee_rate = db('config')->where(['name' => 'trade_fee_sell'])->value('value');

        //只能挂一单
        $count = db('trade')->where(['user_id' => $this->user_id, 'date' => date('Ymd'), 'type' => 2, 'status' => 0, 'exch_coin_id' => $trade['exch_coin_id']])->count();
        if ($count) $this->apiReply(2, '您上午场有未完成的卖单，可将其撤销后重新挂卖！');

        Db::startTrans();
        try {
            //查询余额
            $old_exch_coin_balance = floatval(db('user_coin')->where(['coin_id' => $trade['exch_coin_id'], 'user_id' => $this->user_id])->lock(true)->value('balance'));
            $old_main_coin_balance = floatval(db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->lock(true)->value('balance'));

            if ($num > $old_exch_coin_balance) $this->apiReply(2, $trade['exch_coin_name'] . '余额不足');

            //查询价格
            $price = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('price');
            if (!$price) {
                Db::rollback();
                $this->apiReply(2, '获取价格失败请联系管理员');
            }

            $amount = bcmul($num, $price, 8);
            $fee = bcmul($amount, $fee_rate, 8);
            //$total = floatval(bcadd($amount, $fee, 8));

            if ($fee > $old_main_coin_balance) {
                Db::rollback();
                $this->apiReply(2, $trade['main_coin_name'] . '余额不足');
            }

            $new_main_coin_balance = bcsub($old_main_coin_balance, $fee, 8);
            $new_exch_coin_balance = bcsub($old_exch_coin_balance, $num, 8);

            //user_coin表更新
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['exch_coin_id']])->update(['balance' => $new_exch_coin_balance]);
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['main_coin_id']])->update(['balance' => $new_main_coin_balance]);

            $team_total = Db('user_rela')->where(['user_id' => $this->user_id])->value('team_total');
            $fact_level = Db('matching')->where(['min' => ['elt', $team_total], 'max' => ['egt', $team_total]])->value('matching_id');
            Db('users')->where('user_id', $this->user_id)->update(['fact_match_level' => $fact_level]);
            $admin_level = Db('users')->where('user_id', $this->user_id)->value('matching_level');
            if ($admin_level == 0) {
                $final_level = $fact_level;
            } else {
                $final_level = $admin_level;
            }
            //trade表添加记录
            $trade_id = db('trade')->insertGetId([
                'user_id' => $this->user_id,
                'pid' => $this->user['pid'],
                'main_coin_id' => $trade['main_coin_id'],
                'exch_coin_id' => $trade['exch_coin_id'],
                'price' => $price,
                'num' => $num,
                'total' => $amount,
                'deal_num' => 0,
                'fee' => $fee,
                'deal_fee' => 0,
                'status' => 0,
                'addtime' => time(),
                'date' => date('Ymd'),
                'type' => 2,
                'fact_level' => $fact_level,
                'admin_level' => $admin_level,
                'final_level' => $final_level,
            ]);
            //log_coin表增加记录
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $trade['exch_coin_id'],
                'type' => 12,
                'num' => $num,
                'amount' => $old_exch_coin_balance,
                'balance' => $new_exch_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'trade',
                'union_id' => $trade_id,
                //'remark' => '上午场挂卖' . $num . 'IDO',
            ]);
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $trade['main_coin_id'],
                'type' => 12,
                'num' => $fee,
                'amount' => $old_main_coin_balance,
                'balance' => $new_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'trade',
                'union_id' => $trade_id,
                //'remark' => '上午场挂卖' . $num . 'IDO',
            ]);

            Db::commit();
            $this->apiReply(1, '操作成功');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 下午场卖出
     * @param $coin_id
     * @param $price
     * @param $num
     * @param $token
     */
    public function market_sell($token, $num, $price, $trade_name)
    {
        $this->isVerifyId();
        if ((date('w') == 6) || (date('w') == 0)) $this->apiReply(2, '周末休市！');

        $trade = db('coin_trade')->where(['trade_name' => $trade_name, 'status' => 1])->field('main_coin_id,main_coin_name,exch_coin_id,exch_coin_name')->find();
        if (!$trade) $this->apiReply(2, '交易对错误！');

        $afternoon_start = db('config')->where(['name' => 'afternoon_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $afternoon_end = db('config')->where(['name' => 'afternoon_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        if (time() < strtotime(date('Ymd') . $afternoon_start) || time() > strtotime(date('Ymd') . $afternoon_end)) $this->apiReply(2, '下午场时间为' . $afternoon_start . '到' . $afternoon_end . '！');

        $num = round(floatval($num), 2);
        if (bccomp($num, 0, 8) == -1) $this->apiReply(2, '请输入正确的数量！');

        $market_sell_mini = db('config')->where(['name' => 'market_sell_mini'])->value('value');
        $market_sell_max = db('config')->where(['name' => 'market_sell_max'])->value('value');
        if ($num < $market_sell_mini || $num > $market_sell_max) $this->apiReply(2, '数量应为' . $market_sell_mini . '至' . $market_sell_max . '！');

        $price = round(floatval($price), 4);

        $moring_price = Db('price')->where(['date' => date('Ymd'), 'coin_id' => $trade['exch_coin_id']])->value('price');
        if (!$moring_price) $this->apiReply(2, '获取价格失败请联系管理员');
        $mini_price = bcmul($moring_price, 1.05, 4);

        if ($price < $mini_price) $this->apiReply(2, '价格不能低于' . $mini_price);

        $fee_rate = db('config')->where(['name' => 'trade_fee_sell'])->value('value');

        //只能挂一单
        $count = db('market')->where(['user_id' => $this->user_id, 'date' => date('Ymd'), 'status' => ['in', [0, 1]]])->count();
        if ($count) $this->apiReply(2, '您有未完成的订单！');

        Db::startTrans();
        try {
            //查询余额
            $old_exch_coin_balance = floatval(db('user_coin')->where(['coin_id' => $trade['exch_coin_id'], 'user_id' => $this->user_id])->lock(true)->value('balance'));
            $old_main_coin_balance = floatval(db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->lock(true)->value('balance'));

            if ($num > $old_exch_coin_balance) {
                Db::rollback();
                $this->apiReply(2, $trade['exch_coin_name'] . '余额不足');
            }

            //查询价格
            $amount = bcmul($num, $price, 8);
            $fee = bcmul($amount, $fee_rate, 8);
            //$total = floatval(bcadd($amount, $fee, 8));

            if ($fee > $old_main_coin_balance) {
                Db::rollback();
                $this->apiReply(2, $trade['main_coin_name'] . '余额不足');
            }

            $new_main_coin_balance = bcsub($old_main_coin_balance, $fee, 8);
            $new_exch_coin_balance = bcsub($old_exch_coin_balance, $num, 8);

            //user_coin表更新
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['exch_coin_id']])->update(['balance' => $new_exch_coin_balance]);
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['main_coin_id']])->update(['balance' => $new_main_coin_balance]);

            //market表添加记录
            $market_id = Db('market')->insertGetId([
                'user_id' => $this->user_id,
                'date' => date('Ymd'),
                'username' => $this->user['username'],
                'coin_id' => $trade['exch_coin_id'],
                'price' => $price,
                'num' => $num,
                'deal_num' => 0,
                'fee' => $fee,
                'deal_fee' => 0,
                'type' => 2,
                'status' => 0,
                'addtime' => time(),
            ]);
            //log_coin表增加记录
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $trade['exch_coin_id'],
                'type' => 14,
                'num' => $num,
                'amount' => $old_exch_coin_balance,
                'balance' => $new_exch_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $market_id,
                //'remark' => '下午场挂卖' . $num . 'IDO',
            ]);
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $trade['main_coin_id'],
                'type' => 14,
                'num' => $fee,
                'amount' => $old_main_coin_balance,
                'balance' => $new_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $market_id,
                //'remark' => '下午场挂卖' . $num . 'IDO',
            ]);

            Db::commit();
            $this->apiReply(1, '操作成功');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 下午场买入
     * @param $coin_id
     * @param $price
     * @param $num
     * @param $token
     */
    public function market_buy($token, $id, $num)
    {
        $this->isVerifyId();
        if ((date('w') == 6) || (date('w') == 0)) $this->apiReply(2, '周末休市！');

        $market = db('market')->where(['market_id' => $id, 'status' => ['in', [0, 1]]])->field('user_id,num,deal_num,num-deal_num left_num,fee,deal_fee,price,coin_id')->find();

        if (!$market) $this->apiReply(2, '订单不存在！');

        $afternoon_start = db('config')->where(['name' => 'afternoon_start', 'coin_id' => $market['coin_id']])->value('value');
        $afternoon_end = db('config')->where(['name' => 'afternoon_end', 'coin_id' => $market['coin_id']])->value('value');

        if (time() < strtotime(date('Ymd') . $afternoon_start) || time() > strtotime(date('Ymd') . $afternoon_end)) $this->apiReply(2, '下午场时间为' . $afternoon_start . '到' . $afternoon_end . '！');

        $num = round(floatval($num), 2);
        if (bccomp($num, 0, 8) == -1) $this->apiReply(2, '请输入正确的数量！');

        $market_buy_mini = db('config')->where(['name' => 'market_buy_mini'])->value('value');
        $market_buy_max = db('config')->where(['name' => 'market_buy_max'])->value('value');
        if ($num < $market_buy_mini || $num > $market_buy_max) $this->apiReply(2, '数量应为' . $market_buy_mini . '至' . $market_buy_max . '！');

        if ($market['user_id'] == $this->user_id) $this->apiReply(2, '请不要购买自己的订单！');

        if ($num > $market['left_num']) $this->apiReply(2, '请输入正确的数量！');

        $price = $market['price'];

        $fee_rate = db('config')->where(['name' => 'trade_fee_buy'])->value('value');

        Db::startTrans();
        try {
            $market_fee = Db('config')->where(['name' => 'market_fee'])->value('value');
            $exch_coin_id = $market['coin_id'];
            $main_coin_id = 2;
            //查询余额
            $old_buyer_exch_coin_balance = floatval(db('user_coin')->where(['coin_id' => $exch_coin_id, 'user_id' => $this->user_id])->lock(true)->value('balance'));
            $old_buyer_main_coin_balance = floatval(db('user_coin')->where(['coin_id' => $main_coin_id, 'user_id' => $this->user_id])->lock(true)->value('balance'));

            $old_seller_main_coin_balance = floatval(db('user_coin')->where(['coin_id' => $main_coin_id, 'user_id' => $market['user_id']])->lock(true)->value('balance'));

            $total = bcmul($num, $price, 8);
            $fee = bcmul($total, $fee_rate, 8);

            $sum = bcadd($total, $fee, 8);

            if ($old_buyer_main_coin_balance < $sum) {
                Db::rollback();
                $this->apiReply(2, 'USDT余额不足');
            }

            $new_buyer_main_coin_balance = bcsub($old_buyer_main_coin_balance, $sum, 8);
            $new_buyer_exch_coin_balance = bcadd($old_buyer_exch_coin_balance, $num, 8);

            $new_seller_main_coin_balance = bcadd($old_seller_main_coin_balance, $total * (1 - $market_fee), 8);

            //更新买家余额
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $exch_coin_id])->update(['balance' => $new_buyer_exch_coin_balance]);
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $main_coin_id])->update(['balance' => $new_buyer_main_coin_balance]);

            //更新卖家余额
            Db('user_coin')->where(['user_id' => $market['user_id'], 'coin_id' => $main_coin_id])->update(['balance' => $new_seller_main_coin_balance]);

            //更新market表
            $deal_num = bcadd($market['deal_num'], $num, 8);
            Db('market')->where(['market_id' => $id])->update([
                'deal_num' => $deal_num,
                'deal_fee' => bcmul($market['price'], $deal_num * $fee_rate, 8),
                'status' => ($deal_num == $market['num'] ? 2 : 1),
            ]);
            //log_market添加日志
            $log_market_id = Db('log_market')->insertGetId([
                'coin_id' => $market['coin_id'],
                'market_id' => $id,
                'type' => 1,
                'buyer_id' => $this->user_id,
                'seller_id' => $market['user_id'],
                'price' => $market['price'],
                'num' => $num,
                'total' => $total,
                'fact_total' => $total * (1 - $market_fee),
                'addtime' => time(),
            ]);

            //log_coin表增加记录
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $exch_coin_id,
                'type' => 59,
                'num' => $num,
                'amount' => $old_buyer_exch_coin_balance,
                'balance' => $new_buyer_exch_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $log_market_id,
            ]);
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $main_coin_id,
                'type' => 13,
                'num' => $sum,
                'amount' => $old_buyer_main_coin_balance,
                'balance' => $new_buyer_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $log_market_id,
            ]);
            Db('log_coin')->insertGetId([
                'user_id' => $market['user_id'],
                'coin_id' => $main_coin_id,
                'type' => 60,
                'num' => $total * (1 - $market_fee),
                'amount' => $old_seller_main_coin_balance,
                'balance' => $new_seller_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'log_market',
                'union_id' => $log_market_id,
            ]);

            //返给用户上级30%手续费
            if ($this->user['pid']) {
                //查询上级余额
                $old_parent_usdt_balance = floatval(db('user_coin')->where(['coin_id' => $main_coin_id, 'user_id' => $this->user['pid']])->lock(true)->value('balance'));
                $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $fee * 0.3, 8);
                //更新上级余额
                Db('user_coin')->where(['user_id' => $this->user['pid'], 'coin_id' => $main_coin_id])->update(['balance' => $new_parent_usdt_balance]);

                Db('log_coin')->insertGetId([
                    'user_id' => $this->user['pid'],
                    'coin_id' => $main_coin_id,
                    'type' => 67,
                    'num' => $fee * 0.3,
                    'amount' => $old_parent_usdt_balance,
                    'balance' => $new_parent_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_market',
                    'union_id' => $log_market_id,
                ]);
            }
            //返给卖家上级30%手续费
            $seller_pid = db('user_rela')->where('user_id', $market['user_id'])->value('pid');
            if ($seller_pid) {
                //查询上级余额
                $old_parent_usdt_balance = floatval(db('user_coin')->where(['coin_id' => $main_coin_id, 'user_id' => $seller_pid])->lock(true)->value('balance'));
                $new_parent_usdt_balance = bcadd($old_parent_usdt_balance, $fee * 0.3, 8);
                //更新上级余额
                Db('user_coin')->where(['user_id' => $seller_pid, 'coin_id' => $main_coin_id])->update(['balance' => $new_parent_usdt_balance]);

                Db('log_coin')->insertGetId([
                    'user_id' => $seller_pid,
                    'coin_id' => $main_coin_id,
                    'type' => 67,
                    'num' => $fee * 0.3,
                    'amount' => $old_parent_usdt_balance,
                    'balance' => $new_parent_usdt_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_market',
                    'union_id' => $log_market_id,
                ]);
            }

            //更新团队业绩和用户级别
            $user_rela = Db('user_rela')->where(['user_id' => $this->user_id])->field('pid,lft,rgt,depth,buy_total,team_total')->find();
            Db('user_rela')->where(['user_id' => $this->user_id])->setInc('buy_total', $total);
            Db()->execute("update tp_user_rela set `team_total`=(`team_total`+{$total}) where `lft`<={$user_rela['lft']} and `rgt`>={$user_rela['rgt']} and `depth`<={$user_rela['depth']} ");

            //更新价格
            db('coin')->where('coin_id', $market['coin_id'])->update(['price' => $market['price']]);

            //生成分红记录
            //$this->get_bonus($this->user_id, $total, $main_coin_id, 'USDT', 'log_market', $log_market_id);

            //更新用户级别
//            if ($this->user['user_level'] < 3) {
//                db('users')->where(array('user_id' => $this->user_id))->update(['user_level' => 3]);
//                $this->user['user_level'] = 3;
//                cache('cache_userinfo_' . $this->user_id, $this->user);
//            }

            Db::commit();
            $this->apiReply(1, '操作成功');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 上午场当前委托
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function load_morning_current_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('trade')->alias('a')
            ->join('coin b', 'a.main_coin_id=b.coin_id')
            ->join('coin c', 'a.exch_coin_id=c.coin_id')
            ->where(['a.user_id' => $this->user_id, 'a.status' => 0])
            ->field('a.trade_id,a.addtime,a.num,a.price,a.total,a.status,a.type,a.fee,b.name main_coin_name,c.name exch_coin_name')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();

        if ($data['data']) {
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }

    /**
     * 上午场历史挂单
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function load_morning_history_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('log_trade')->alias('a')
            ->join('coin b', 'a.main_coin_id=b.coin_id')
            ->join('coin c', 'a.exch_coin_id=c.coin_id')
            ->where(['a.user_id' => $this->user_id])
            ->field('a.id,a.addtime,a.num,a.price,a.total,a.status,a.type,a.fee,a.deal_fee,a.deal_num,a.num-a.deal_num left_num,b.name main_coin_name,c.name exch_coin_name,a.price*(a.num-a.deal_num) left_total,a.price*a.deal_num deal_total')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();

        if ($data['data']) {
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }

    /**
     * 下午场历史挂单
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function load_afternoon_history_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('market')->alias('a')
            ->join('coin b', 'a.coin_id=b.coin_id')
            ->where(['a.user_id' => $this->user_id, 'a.status' => ['neq', 0]])
            ->field('a.market_id,a.addtime,a.num,a.price,a.num*a.price total,a.status,a.type,a.fee,a.deal_fee,a.deal_num,a.num-a.deal_num left_num,b.name,a.price*(a.num-a.deal_num) left_total,a.price*a.deal_num deal_total')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();

        if ($data['data']) {
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }


    /**
     * 撤销订单
     * @param $token
     * @param $id
     */
    public function to_cancel_trade($token, $id)
    {
        //查询订单
        $trade = db('trade')->where(['trade_id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('num,total,fee,type,main_coin_id,exch_coin_id')->find();
        if (!$trade) $this->apiReply(2, '订单异常！');

        $morning_start = db('config')->where(['name' => 'morning_start', 'coin_id' => $trade['exch_coin_id']])->value('value');
        $morning_end = db('config')->where(['name' => 'morning_end', 'coin_id' => $trade['exch_coin_id']])->value('value');

        if (time() < strtotime(date('Ymd') . $morning_start) || time() > strtotime(date('Ymd') . $morning_end)) $this->apiReply(2, '上午场时间为' . $morning_start . '到' . $morning_end . '！');

        Db::startTrans();
        try {
            //更新状态
            Db('trade')->where(['trade_id' => $id])->update([
                'status' => -1,
            ]);

            //查询余额
            $old_main_coin_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['main_coin_id']])->lock(true)->value('balance');
            $old_exch_coin_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['exch_coin_id']])->lock(true)->value('balance');

            if ($trade['type'] == 2) {
                $new_main_coin_balance = bcadd($old_main_coin_balance, $trade['fee'], 8);
                $new_exch_coin_balance = bcadd($old_exch_coin_balance, $trade['num'], 8);
                //更新余额
                Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['main_coin_id']])->update([
                    'balance' => $new_main_coin_balance,
                ]);
                Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['exch_coin_id']])->update([
                    'balance' => $new_exch_coin_balance,
                ]);
                //log_coin日志
                Db('log_coin')->insertGetId([
                    'user_id' => $this->user_id,
                    'coin_id' => $trade['main_coin_id'],
                    'type' => 64,
                    'num' => $trade['fee'],
                    'amount' => $old_main_coin_balance,
                    'balance' => $new_main_coin_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trade',
                    'union_id' => $id,
                ]);
                Db('log_coin')->insertGetId([
                    'user_id' => $this->user_id,
                    'coin_id' => $trade['exch_coin_id'],
                    'type' => 63,
                    'num' => $trade['num'],
                    'amount' => $old_exch_coin_balance,
                    'balance' => $new_exch_coin_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trade',
                    'union_id' => $id,
                ]);
            } else {
                $new_main_coin_balance = bcadd($old_main_coin_balance, $trade['fee'] + $trade['total'], 8);
                //更新余额
                Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $trade['main_coin_id']])->update([
                    'balance' => $new_main_coin_balance,
                ]);
                //log_coin日志
                Db('log_coin')->insertGetId([
                    'user_id' => $this->user_id,
                    'coin_id' => $trade['main_coin_id'],
                    'type' => 64,
                    'num' => $trade['fee'] + $trade['total'],
                    'amount' => $old_main_coin_balance,
                    'balance' => $new_main_coin_balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trade',
                    'union_id' => $id,
                ]);
            }

            Db::commit();
            $this->apiReply(1, '操作成功！');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 撤销订单
     * @param $token
     * @param $id
     */
    public function to_cancel_market($token, $id)
    {
        //$afternoon_start = db('config')->where(['name' => 'afternoon_start'])->value('value');
        //$afternoon_end = db('config')->where(['name' => 'afternoon_end'])->value('value');

        //if (time() < strtotime(date('Ymd') . $afternoon_start) || time() > strtotime(date('Ymd') . $afternoon_end)) $this->apiReply(2, '下午场时间为' . $afternoon_start . '到' . $afternoon_end . '！');

        Db::startTrans();
        try {
            //查询订单
            $market = db('market')->where(['market_id' => $id, 'user_id' => $this->user_id, 'status' => ['in', [0, 1]]])->field('num,deal_num,fee,deal_fee,coin_id')->find();
            if (!$market) {
                Db::rollback();
                $this->apiReply(2, '订单异常！');
            }

            //更新状态
            Db('market')->where(['market_id' => $id])->update([
                'status' => -1,
            ]);

            //查询余额
            $old_main_coin_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => 2])->lock(true)->value('balance');
            $old_exch_coin_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->lock(true)->value('balance');

            $new_main_coin_balance = bcadd($old_main_coin_balance, bcsub($market['fee'], $market['deal_fee'], 8), 8);
            $new_exch_coin_balance = bcadd($old_exch_coin_balance, bcsub($market['num'], $market['deal_num'], 8), 8);
            //更新余额
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => 2])->update([
                'balance' => $new_main_coin_balance,
            ]);
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->update([
                'balance' => $new_exch_coin_balance,
            ]);
            //log_coin日志
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => 2,
                'type' => 66,
                'num' => bcsub($market['fee'], $market['deal_fee'], 8),
                'amount' => $old_main_coin_balance,
                'balance' => $new_main_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $id,
            ]);
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $market['coin_id'],
                'type' => 65,
                'num' => bcsub($market['num'], $market['deal_num'], 8),
                'amount' => $old_exch_coin_balance,
                'balance' => $new_exch_coin_balance,
                'addtime' => time(),
                'status' => 1,
                'union' => 'market',
                'union_id' => $id,
            ]);

            Db::commit();
            $this->apiReply(1, '操作成功！');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败', $e->getMessage());
        }
    }

    /**
     * 上午场当前委托
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function load_afternoon_current_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('market')->alias('a')
            ->join('coin b', 'a.coin_id=b.coin_id')
            ->where(['a.user_id' => $this->user_id, 'a.status' => ['in', [0, 1]]])
            ->field('a.market_id,a.addtime,a.num,a.price,a.status,a.type,a.fee,a.deal_num,a.num-a.deal_num left_num,a.price*a.num total,b.name')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();

        if ($data['data']) {
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }

    /**
     * 上午场当前委托
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function market_lists($token, $page = 1, $pageSize = 10)
    {
        $data = Db('market')->alias('a')
            ->join('coin b', 'a.coin_id=b.coin_id')
            ->where(['a.status' => ['in', [0, 1]]])
            ->field('a.market_id,a.addtime,a.num,a.price,a.status,a.type,a.fee,a.deal_num,a.num-a.deal_num left_num,a.price*a.num total,b.name')
            ->order('addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();

        if ($data['data']) {
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }

    /**
     * 下午场订单详情
     * @param $token
     * @param int $pageSize
     */
    public function market_detail($token, $id)
    {
        $data = Db('market')->alias('a')
            ->join('coin b', 'a.coin_id=b.coin_id')
            ->where(['a.market_id' => $id])
            ->field('a.market_id,a.addtime,a.num,a.price,a.status,a.type,a.fee,a.deal_num,a.num-a.deal_num left_num,a.price*a.num total,b.name')
            ->find();

        if ($data) {
            $data['fee_rate'] = db('config')->where(['name' => 'trade_fee_buy'])->value('value');
            $data['usdt_balance'] = db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => 2])->value('balance');
            $this->apiResp($data);
        } else {
            $this->apiReply(2, '暂无记录');
        }
    }

    public function load_depth($token, $trade)
    {
        $data = http('http://api.zb.cn/data/v1/depth?market=' . $trade . '&size=7');
        $data['usdt_cny'] = db('coin')->where('coin_id', 2)->value('price_cny');
        $this->apiResp($data);
    }

    public function LoadAsset($token, $trade_name)
    {
        if (!$trade_name) $this->apiReply(2, '缺少参数！|missing parameter！');
        $trade = db('coin_trade')->where('trade_name', $trade_name)->field('main_coin_id,exch_coin_id')->find();

        $data['main_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['main_coin_id'], 'user_id' => $this->user_id])->value('balance');
        $data['exch_coin_balance'] = db('user_coin')->where(['coin_id' => $trade['exch_coin_id'], 'user_id' => $this->user_id])->value('balance');

        $data['num_min'] = db('coin_trade')->where('trade_name', $trade_name)->value('num_min');

        $this->apiResp($data);
    }


}