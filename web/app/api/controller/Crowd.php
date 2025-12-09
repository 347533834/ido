<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 众筹钱包
 * Class Crowd
 * @package app\api\controller
 */
class Crowd extends Base
{

    /**
     * 众筹钱包余额
     * @param $token
     */
    public function LoadCrowd($token)
    {
        $data = db('user_account')->where('user_id', $this->user_id)->field('crowd,release_total')->find();
        $data['yesterday'] = db('returns_crowd')->where(['user_id' => $this->user_id, 'date' => date("Ymd", strtotime("-1 day"))])->value('returns');
        $data['yesterday'] = $data['yesterday'] ? $data['yesterday'] : "0";
        $data['crowd_day_rate'] = $this->config['crowd_day_rate'];
        $this->apiResp($data);
    }

    /**
     * 历史记录
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function crowd_history($token, $page = 1, $pageSize = 10)
    {
        $data = Db('returns_crowd')
            ->where(['user_id' => $this->user_id])
            ->field('id,addtime,balance,returns,status')
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
    public function to_pick_crowd($token, $id)
    {
        $returns_crowd = db('returns_crowd')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('returns,amount,balance,addtime')->find();
        if (!$returns_crowd) $this->apiReply(2, '数据异常！');

//        if ($returns_crowd['addtime']>=strtotime('2018-10-23 14:34:07')) $this->apiReply(2, '数据维护！');

        Db::startTrans();
        try {
            //查询账户信息
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            //$old_crowd_balance = db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('lock');//众筹钱包余额

            $new_savings_balance = bcadd($old_savings_balance, $returns_crowd['returns'], 4);
            //$new_crowd_balance = bcsub($old_crowd_balance, $returns_crowd['num'], 4);

            //更新会员账户
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
                //'lock' => $new_crowd_balance,
            ]);
            $time = time();
            //returns_crowd表更新状态
            Db('returns_crowd')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->update([
                'status' => 1,
                'pick_time' => $time,
            ]);
            //log_savings表添加日志
            Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 2,
                'num' => $returns_crowd['returns'],
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //log_crowd表添加日志
            Db('log_crowd')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 2,
                'num' => $returns_crowd['returns'],
                'amount' => $returns_crowd['amount'],
                'balance' => $returns_crowd['balance'],
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
                'union' => 'returns_crowd',
                'union_id' => $id,
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