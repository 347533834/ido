<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 理财钱包
 * Class Lock
 * @package app\api\controller
 */
class Lock extends Base
{

    /**
     * 储蓄钱包余额
     * @param $token
     */
    public function LoadSetmeal($token)
    {
        $data = db('lock')->alias('a')
            ->join('user_lock_num b', 'b.lock_id=a.lock_id and b.user_id=' . $this->user_id, 'left')
            ->field('a.lock_id,a.name,a.days,a.min,a.max,a.rate,a.remark,a.is_exp,b.now,b.all,b.returns')
            ->order('a.sort')
            ->select();
        foreach ($data as $k => $v) {
            if ($v['is_exp']) {
                $data[$k]['times'] = db('log_lock')->where(['user_id' => $this->user_id, 'lock_id' => $v['lock_id']])->count();
            } else {
                $data[$k]['times'] = 0;
            }
        }
        $this->apiResp($data);
    }

    /**
     * 购买理财
     * @param $token
     * @param $lock_id
     * @param $num
     * @param $paypass
     */
    public function to_join($token, $lock_id, $num, $paypass)
    {
        //判断是否是体验套餐
        $lock = db('lock')->where('lock_id', $lock_id)->field('min,max,is_exp,name,days,rate')->find();
        if (!$lock) $this->apiReply(2, '套餐不存在！');
        if ($lock['is_exp'] == 1) {
            $is_exp = db('log_lock')->where(['user_id' => $this->user_id, 'lock_id' => $lock_id])->count();
            if ($is_exp > 0) $this->apiReply(2, '体验次数已用完！');
        }

        $num = floatval($num);
        if (bccomp($num, 0, 4) != 1) $this->apiReply(2, '请输入正确的数量！');

        if ($num < $lock['min'] || $num > $lock['max']) {
            Db::rollback();
            $this->apiReply(2, '理财数量应为' . $lock['min'] . '至' . $lock['max']);
        }

        if (!$paypass) $this->apiReply(2, '请输入交易密码！');
        $user_paypass = Db('users')->where(['user_id' => $this->user_id, 'status' => 1])->value('paypass');
        if (!$user_paypass) $this->apiReply(888, '您还没有设置交易密码,是否立即设置?');
        if (md5($paypass . config('password_str')) != $user_paypass) {
            $text = $this->login_log(-5, $this->user['mobile'], '交易密码不正确！', $this->user_id);
            $this->apiReply(3, $text);
        }

        Db::startTrans();
        try {
            // 查询账户信息
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            $old_lock_balance = db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('lock');//理财钱包余额
            if (bccomp($old_savings_balance, $num, 4) == -1) {
                Db::rollback();
                $this->apiReply(2, '余额不足！');
            }
            $new_savings_balance = bcsub($old_savings_balance, $num, 4);
            $new_lock_balance = bcadd($old_lock_balance, $num, 4);

            //更新会员账户
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
                'lock' => $new_lock_balance,
            ]);

            $awt_coin_id = 1;
            $time = time();
            //user_lock表添加记录
            $user_lock_id = Db('user_lock')->insertGetId([
                'lock_id' => $lock_id,
                'lock_name' => $lock['name'],
                'user_id' => $this->user_id,
                'coin_id' => $awt_coin_id,
                'num' => $num,
                'rate' => $lock['rate'],
                'returns' => bcmul($num, $lock['rate'], 4),
                'status' => 1,
                'remark' => '',
                'addtime' => $time,
                'endtime' => $time + $lock['days'] * 60 * 60 * 24,
                'pick_time' => '',
            ]);
            //log_savings表添加日志
            Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 12,
                'num' => $num,
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //log_lock表添加日志
            Db('log_lock')->insertGetId([
                'user_lock_id' => $user_lock_id,
                'user_id' => $this->user_id,
                'lock_id' => $lock_id,
                'type' => 1,
                'num' => $num,
                'amount' => $old_lock_balance,
                'balance' => $new_lock_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //user_lock_num表更新数据
            $user_lock_num = db('user_lock_num')->where(['user_id' => $this->user_id, 'lock_id' => $lock_id])->field('now,all,returns')->find();
            if (!$user_lock_num) {
                Db('user_lock_num')->insertGetId([
                    'user_id' => $this->user_id,
                    'lock_id' => $lock_id,
                    'now' => $num,
                    'all' => $num,
                    'returns' => 0,
                ]);
            } else {
                Db('user_lock_num')->where(['user_id' => $this->user_id, 'lock_id' => $lock_id])->update([
                    'now' => bcadd($user_lock_num['now'], $num, 4),
                    'all' => bcadd($user_lock_num['all'], $num, 4),
                ]);
            }

            Db::name('log_login')->where(['user_id' => $this->user_id, 'status' => -5])->setField('status', 2);
            Db::commit();
            $this->apiReply(1, '操作成功！', ['savings_balance' => $new_savings_balance]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！', $e->getMessage());
        }
    }

    /**
     * 活动订单
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function activity_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('user_lock')
            ->where(['user_id' => $this->user_id, 'status' => array('in', '0,1')])
            ->field('user_lock_id,addtime,lock_name,num,returns,status')
            ->order('addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $this->apiResp($data);
    }

    /**
     * 历史订单
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function history_order($token, $page = 1, $pageSize = 10)
    {
        $data = Db('user_lock')
            ->where(['user_id' => $this->user_id, 'status' => 2])
            ->field('addtime,lock_name,num,returns,status')
            ->order('addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $this->apiResp($data);
    }

    /**
     * 提取
     * @param $token
     * @param $id
     */
    public function to_pick($token, $id)
    {
        $user_lock = db('user_lock')->where(['user_lock_id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('lock_id,num,returns')->find();
        if (!$user_lock) $this->apiReply(2, '订单异常！');

        Db::startTrans();
        try {
            //查询账户信息
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            $old_lock_balance = db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('lock');//理财钱包余额

            $total = bcadd($user_lock['num'], $user_lock['returns'], 4);//本金加收益总和
            $new_savings_balance = bcadd($old_savings_balance, $total, 4);
            $new_lock_balance = bcsub($old_lock_balance, $user_lock['num'], 4);

            //更新会员账户
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
                'lock' => $new_lock_balance,
            ]);
            $time = time();
            //user_lock表更新状态
            Db('user_lock')->where(['user_lock_id' => $id, 'user_id' => $this->user_id, 'status' => 0])->update([
                'status' => 2,
                'pick_time' => $time,
            ]);
            //log_savings表添加日志
            Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 5,
                'num' => $total,
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //log_lock表添加日志
            Db('log_lock')->insertGetId([
                'user_lock_id' => $id,
                'user_id' => $this->user_id,
                'lock_id' => $user_lock['lock_id'],
                'type' => 2,
                'num' => $user_lock['num'],
                'amount' => $old_lock_balance,
                'balance' => $new_lock_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //user_lock_num表更新数据
            $user_lock_num = db('user_lock_num')->where(['user_id' => $this->user_id, 'lock_id' => $user_lock['lock_id']])->field('now,returns')->find();
            Db('user_lock_num')->where(['user_id' => $this->user_id, 'lock_id' => $user_lock['lock_id']])->update([
                'now' => bcsub($user_lock_num['now'], $user_lock['num'], 4),
                'returns' => bcadd($user_lock_num['returns'], $user_lock['returns'], 4),
            ]);

            Db::commit();
            $this->apiReply(1, '操作成功！', ['savings_balance' => $new_savings_balance]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！', $e->getMessage());
        }

    }


}