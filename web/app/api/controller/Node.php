<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 超级节点、我的节点
 * Class Crowd
 * @package app\api\controller
 */
class Node extends Base
{
    /**
     * 超级节点今日收益
     * @param $token
     */
    public function today_returns($token)
    {
        $data['today_returns'] = db('returns_vip')->where(['user_id' => $this->user_id, 'date' => date('Ymd')])->value('returns');
        $data['today_returns'] = $data['today_returns'] ? $data['today_returns'] : "0";
        $this->apiResp($data);
    }

    /**
     * 超级节点收益记录
     * @param $token
     * @param $page
     * @param $pageSize
     */
    public function super_node_lists($token, $page = 1, $pageSize = 10)
    {
        $data = Db('returns_vip')
            ->where(['user_id' => $this->user_id])
            ->field('id,addtime,returns,status')
            ->order('addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $this->apiResp($data);
    }

    /**
     * 超级节点规则
     * @param $token
     */
    public function super_node_rule($token)
    {
        $data['content'] = '<p>1.储蓄钱包、理财钱包内AWT数量总和大于等于20W枚即可成为超级节点。</p>
                <p>2.超级节点可享受该节点下所有会员理财收益的10%作为超级节点收益。</p>
                <p>3.成为超级节点时不享受普通会员收益，超级节点资格取消后，享受普通会员的收益制度。</p>';
        $this->apiResp($data);
    }

    /**
     * 超级节点提取
     * @param $token
     * @param $id
     */
    public function to_pick_super($token, $id)
    {
        $returns_vip = Db('returns_vip')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('returns')->find();
        if (!$returns_vip) $this->apiReply(2, '数据异常!');
        Db::startTrans();
        try {
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            $new_savings_balance = bcadd($old_savings_balance, $returns_vip['returns'], 4);
            //更新会员账户
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
            ]);
            $time = time();
            //returns_vip表更新状态
            Db('returns_vip')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->update([
                'status' => 1,
                'pick_time' => $time,
            ]);
            //log_savings表添加日志
            Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 3,
                'num' => $returns_vip['returns'],
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);

            Db::commit();
            $this->apiReply(1, '操作成功！', ['savings_balance' => $new_savings_balance]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！', $e->getMessage());
        }
    }

    /**
     * 今日等级
     * @param $token
     */
    public function today_level($token)
    {
        $data = db('user_rela')->alias('a')
            ->join('node_level b', 'a.node_level_id=b.node_level_id')
            ->where(['user_id' => $this->user_id])
            ->field('b.name,a.vip_node')
            ->find();
        $this->apiResp($data);
    }

    /**
     * 推广收益规则
     * @param $token
     */
    public function my_node_rule($token)
    {
        $data = db('node_level')->field('name,active,remark')->select();
        $this->apiResp($data);
    }

    /**
     * 推广收益记录
     * @param $token
     * @param $page
     * @param $pageSize
     */
    public function my_node_lists($token, $page = 1, $pageSize = 10)
    {
        $data = Db('returns_node')->alias('a')
            ->join('node_level b', 'a.node_level_id=b.node_level_id')
            ->where(['user_id' => $this->user_id])
            ->field('a.id,a.addtime,a.returns,a.status,b.name')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $this->apiResp($data);
    }

    /**
     * 推广收益提取
     * @param $token
     * @param $id
     */
    public function to_pick_node($token, $id)
    {
        $returns_node = Db('returns_node')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('returns')->find();
        if (!$returns_node) $this->apiReply(2, '数据异常!');
        Db::startTrans();
        try {
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            $new_savings_balance = bcadd($old_savings_balance, $returns_node['returns'], 4);
            //更新会员账户
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
            ]);
            $time = time();
            //returns_node表更新状态
            Db('returns_node')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->update([
                'status' => 1,
                'pick_time' => $time,
            ]);
            //log_savings表添加日志
            Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 4,
                'num' => $returns_node['returns'],
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
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