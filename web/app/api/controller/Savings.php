<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 储蓄钱包
 * Class Savings
 * @package app\api\controller
 */
class Savings extends Base
{

    /**
     * 储蓄钱包余额
     * @param $token
     */
    public function LoadBalance($token)
    {
        $awt_coin_id = 1;
        $data['savings_balance'] = db('user_account')->where(['user_id' => $this->user_id])->value('savings');//储蓄钱包余额
        $data['coin_balance'] = db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $awt_coin_id])->value('balance');//user_coin余额
        $data['price'] = db('coin')->where('coin_id', $awt_coin_id)->value('price');
        $data['is_crowd'] = db('users')->where('user_id', $this->user_id)->value('is_crowd');
        $data['mutual_fee'] = $this->config['mutual_fee'];
        $data['mutual_min'] = $this->config['mutual_min'];
        $data['mutual_max'] = $this->config['mutual_max'];
        $this->apiResp($data);
    }

    /**
     * 转入
     * @param $token
     * @param $num
     * @param $paypass
     */
    public function to_turn_in($token, $num, $paypass)
    {
        $num = floatval($num);
        if (bccomp($num, 0, 4) != 1) $this->apiReply(2, '请输入正确的数量！');

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
            $awt_coin_id = 1;
            $old_coin_balance = db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $awt_coin_id])->lock(true)->value('balance');//user_coin余额
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            if (bccomp($old_coin_balance, $num, 4) == -1) {
                Db::rollback();
                $this->apiReply(2, '余额不足！');
            }
            $new_coin_balance = bcsub($old_coin_balance, $num, 4);
            $new_savings_balance = bcadd($old_savings_balance, $num, 4);

            //更新会员账户
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $awt_coin_id])->update([
                'balance' => $new_coin_balance,
            ]);
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
            ]);

            //log_savings表添加日志
            $time = time();
            $log_savings_id = Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 1,
                'num' => $num,
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //log_coin表添加数据
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $awt_coin_id,
                'type' => 21,
                'num' => $num,
                'amount' => $old_coin_balance,
                'balance' => $new_coin_balance,
                'addtime' => $time,
                'status' => 1,
                'union' => 'log_savings',
                'union_id' => $log_savings_id,
                'remark' => '用户资产转入' . $num . 'AWT至储蓄钱包',
            ]);
            Db::name('log_login')->where(['user_id' => $this->user_id, 'status' => -5])->setField('status', 2);
            Db::commit();
            $this->apiReply(1, '操作成功！');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！', $e->getMessage());
        }
    }

    /**
     * 转出
     * @param $token
     * @param $num
     * @param $paypass
     */
    public function to_turn_out($token, $num, $paypass)
    {
        $num = floatval($num);
        if (bccomp($num, 0, 4) != 1) $this->apiReply(2, '请输入正确的数量！');

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
            $awt_coin_id = 1;
            $old_coin_balance = db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $awt_coin_id])->lock(true)->value('balance');//user_coin余额
            $old_savings_balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额
            if (bccomp($old_savings_balance, $num, 4) == -1) {
                Db::rollback();
                $this->apiReply(2, '余额不足！');
            }
            $new_coin_balance = bcadd($old_coin_balance, $num, 4);
            $new_savings_balance = bcsub($old_savings_balance, $num, 4);

            //更新会员账户
            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $awt_coin_id])->update([
                'balance' => $new_coin_balance,
            ]);
            Db('user_account')->where(['user_id' => $this->user_id])->update([
                'savings' => $new_savings_balance,
            ]);

            //log_savings表添加日志
            $time = time();
            $log_savings_id = Db('log_savings')->insertGetId([
                'user_id' => $this->user_id,
                'type' => 11,
                'num' => $num,
                'amount' => $old_savings_balance,
                'balance' => $new_savings_balance,
                'addtime' => $time,
                'status' => 1,
                'remark' => '',
            ]);
            //log_coin表添加数据
            Db('log_coin')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => $awt_coin_id,
                'type' => 71,
                'num' => $num,
                'amount' => $old_coin_balance,
                'balance' => $new_coin_balance,
                'addtime' => $time,
                'status' => 1,
                'union' => 'log_savings',
                'union_id' => $log_savings_id,
                'remark' => '储蓄钱包转出' . $num . 'AWT至用户资产',
            ]);
            Db::name('log_login')->where(['user_id' => $this->user_id, 'status' => -5])->setField('status', 2);
            Db::commit();
            $this->apiReply(1, '操作成功！');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！', $e->getMessage());
        }
    }


  /**
   * 会员对充
   * @param $token
   * @param $num
   * @param $number
   * @param $mobile
   * @param $paypass
   */
  public function to_mutual($token, $num,$number,$mobile,$paypass)
  {

    $this->isVerifyId();

    $num = floatval($num);
    if (bccomp($num, 0, 4) != 1) $this->apiReply(2, '请输入正确的数量！');

    if (!$paypass) $this->apiReply(2, '请输入交易密码！');
    $user_paypass = Db('users')->where(['user_id' => $this->user_id, 'status' => 1])->value('paypass');
    if (!$user_paypass) $this->apiReply(888, '您还没有设置交易密码,是否立即设置?');
    if (md5($paypass . config('password_str')) != $user_paypass) {
      $text = $this->login_log(-5, $this->user['mobile'], '交易密码不正确！', $this->user_id);
      $this->apiReply(3, $text);
    }

    $mutual_fee = $this->config['mutual_fee'];
    $mutual_min = $this->config['mutual_min'];
    $mutual_max = $this->config['mutual_max'];

    if(bccomp($num,$mutual_min,4)==-1){
      $this->apiReply(2, '最小转出金额为'.$mutual_min);
    }
    if(bccomp($mutual_max,$num,4)==-1){
      $this->apiReply(2, '最大转出金额为'.$mutual_max);
    }

    $fee = bcmul($num,$mutual_fee,4);

    Db::startTrans();
    try {
      // 查询账户信息

      $balance = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('savings');//储蓄钱包余额

      if (bccomp($balance, bcadd($num,$fee,4), 4) == -1) {
        Db::rollback();
        $this->apiReply(2, '余额不足！');
      }

      $user_id = Db('users')->where(['mobile'=>$mobile,'number'=>$number])->value('user_id');

      if($user_id == $this->user_id){
        $this->apiReply(2, '自己不能给自己转账');
      }

      if(!$user_id){
        $this->apiReply(2, '请填写正确的收款人信息');
      }


      Db('user_account')->where(['user_id' => $this->user_id])->update([
        'savings' => bcsub($balance,bcadd($num,$fee,4),4),
      ]);

      $user_balance = Db('user_account')->where(['user_id' => $user_id])->lock(true)->value('savings');//储蓄钱包余额

      Db('user_account')->where(['user_id' => $user_id])->update([
        'savings' => bcadd($user_balance,$num,4),
      ]);

      //log_savings表添加日志
      $time = time();

      $id = Db('log_mutual')->insertGetId([
        'turn_user_id'=>$this->user_id,
        'receive_user_id'=>$user_id,
        'num'=>$num,
        'fee'=>$fee,
        'addtime'=>$time,
      ]);

      Db('log_savings')->insertGetId([
        'user_id' => $this->user_id,
        'type' => 17,
        'num' => bcadd($num, $fee, 4),
        'amount' => $balance,
        'balance' => bcsub($balance, bcadd($num, $fee, 4), 4),
        'addtime' => $time,
        'status' => 1,
        'remark' => '会员互转到' . $user_id . '数量:' . $num . '手续费:' . $fee,
        'union' => 'log_mutual',
        'union_id' => $id,
      ]);

      Db('log_savings')->insertGetId([
        'user_id' => $user_id,
        'type' => 7,
        'num' => $num,
        'amount' => $user_balance,
        'balance' => bcadd($user_balance, $num, 4),
        'addtime' => $time,
        'status' => 1,
        'remark' => '会员互转收到到' . $this->user_id . '数量:' . $num,
        'union' => 'log_mutual',
        'union_id' => $id,
      ]);

      Db::name('log_login')->where(['user_id' => $this->user_id, 'status' => -5])->setField('status', 2);
      Db::commit();
      $this->apiReply(1, '操作成功！');
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '操作失败！', $e->getMessage());
    }
  }


    /**
     * 历史记录
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function savings_history($token, $page = 1, $pageSize = 10)
    {
        $data = Db('log_savings')
            ->where(['user_id' => $this->user_id])
            ->field('addtime,num,balance,type')
            ->order('addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $data['type_name'] = config('savings');
        $this->apiResp($data);

    }


}