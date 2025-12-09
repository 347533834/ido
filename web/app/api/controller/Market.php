<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * C2C交易
 * Class User
 * @package app\api\controller
 */
class Market extends Base
{
  /**
   * 求购挂买
   * @param $coin_id
   * @param $price
   * @param $num
   * @param $token
   */
  public function Buy($token,$coin_id,$num,$price)
  {

    $this->isVerifyId();
    $this->isSetPayment();

    if(time() < strtotime(date('Ymd').' 06:00:00') || time() > strtotime(date('Ymd').' 21:00:00')) $this->apiReply(2, '交易开放时间为06:00到21:00！');
    $market_buy_mini = $this->config['market_buy_mini'];
    $market_buy_max = $this->config['market_buy_max'];
    $buy_price_mini = $this->config['buy_price_mini'];
    $buy_price_max = $this->config['buy_price_max'];

    $num = floatval ($num);
    $price = floatval ($price);
    if (bccomp($num,$market_buy_mini,4)==-1) $this->apiReply(2, '求购数量最少 ' . $market_buy_mini . ' 个！');
    if (bccomp($market_buy_max,$num,4)==-1) $this->apiReply(2, '求购数量最多 ' . $market_buy_max . ' 个！');
    if (bccomp($price,$buy_price_mini,2)==-1) $this->apiReply(2, '求购价格最低 ' . $market_buy_mini . ' CNY！');
    if (bccomp($buy_price_max,$price,2)==-1) $this->apiReply(2, '求购价格最高 ' . $market_buy_max . ' CNY！');

    Db::startTrans();
    try {

      // 是否有未付款的订单
      $LogCount = Db('log_market')->where(['buyer_id|seller_id' => $this->user_id,'type'=>1,'status' => ['in', [1,2]]])->count();

      $MarketCount = Db('market')->where(['user_id' => $this->user_id,'type'=>1,'status' => ['in', [0,1]]])->count();
      $buy_num = $this->config['buy_num'];
      $Count = $LogCount + $MarketCount;
      if ($Count >= $buy_num) {
        $this->apiReply(2, '有'.$buy_num.'单交易未完成，请先完成订单！');
      }

      $market_fee = $this->config['market_fee'];

      $market_id = Db('market')->insertGetId([
        'user_id' => $this->user_id,
        'username' => $this->user['username'],
        'coin_id' => $coin_id,
        'price' => $price,
        'num' => $num,
        'deal_num' => 0,
        'fee'=>bcmul($market_fee,$num,4),
        'deal_fee' => 0,
        'type' => 1,
        'status' => 0,
        'addtime' => time(),
      ]);

      Db::commit();
      $this->apiReply(1, '操作成功', $market_id);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '操作失败');
    }
  }

  /**
   * 求购 卖家出售，加载订单信息
   * @param $token
   * @param $market_id
   */
  public function LoadMarketDetails($token, $market_id)
  {
    $Market = Db('market')->where(['market_id' => $market_id,'status'=>['in',[0,1]],'type'=>1])->field('market_id,price,(num-deal_num) total,coin_id,username')->find();

    if (!$Market) $this->apiReply(2, '订单异常！');

    $balance = Db('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$Market['coin_id']])->value('balance');

    $Market['balance'] = isset($balance)?$balance:0;

    $Market['name'] = $this->coins[$Market['coin_id']]['name'];

    $Market['fee'] = $this->config['market_fee'];

    $Market['idcard'] = Db('idaudit')->where(['user_id'=>$this->user_id,'status'=>1])->count();

    $this->apiReply(1, '操作成功',$Market );
  }

  /**
   *  求购 出售
   * @param $token
   * @param $market_id
   * @param $num
   * @param $password
   */
  public function Sell($token,$market_id,$num,$password)
  {
    $this->isVerifyId();
    $this->isSetPayment();

    if(time() < strtotime(date('Ymd').' 06:00:00') || time() > strtotime(date('Ymd').' 21:00:00')) $this->apiReply(2, '交易开放时间为06:00到21:00！');

    $user_paypass = Db('users')->where(['user_id' => $this->user_id, 'status' => 1])->value('paypass');
    if (!$user_paypass) $this->apiReply(888, '请先设置交易密码');
    if (md5($password . config('password_str')) != $user_paypass) {
      $text = $this->login_log(-5, $this->user['mobile'], '交易密码不正确！', $this->user_id);
      $this->apiReply(3, $text);
    }

    Db::startTrans();
    try {

      //查询订单
      $market = Db('market')->where(['market_id' => $market_id,'type'=>1,'status' => ['in',[0,1]]])->field('user_id,username,coin_id,price,num,deal_num,deal_fee')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交，请交易其它订单！');
      }

        $user_status = Db('users')->where(['user_id'=>$market['user_id']])->value('status');
        if ($user_status<1) {
            Db::rollback();
            $this->apiReply(2, '该买家账号异常，订单暂停交易！');
        }

      if ($this->user_id == $market['user_id']) {
        Db::rollback();
        $this->apiReply(2, '不能交易自己的挂单！');
      }

      //计算未成交数量
      $total_num = bcsub($market['num'],$market['deal_num'],4);

      if(bccomp($total_num,$num,4)==-1){
        Db::rollback();
        $this->apiReply(2, '订单余额不足！');
      }

      //查询最小出售数量
      $min = $this->config['market_sell_mini'];

      //计算成交后剩余数量
      $over_num = bcsub($total_num,$num,4);

      if($over_num > 0 && bccomp($over_num,$min,4)==-1){
        Db::rollback();
        $this->apiReply(2, '交易完成后,剩余订单数量小于最小出售数量'.$min.'请重新输入数量');
      }

      // 是否有未付款的订单
      $LogCount = Db('log_market')->where(['buyer_id|seller_id' => $this->user_id,'type'=>1,'status' => ['in', [1,2]]])->count();
      $MarketCount = Db('market')->where(['user_id' => $this->user_id, 'status' => ['in', [0,1]],'type'=>1])->count();
      $buy_num = $this->config['buy_num'];
      $Count = $LogCount + $MarketCount;
      if ($Count >= $buy_num) {
        $this->apiReply(2, '有'.$buy_num.'单交易未完成，请先完成订单！');
      }

      //计算出售总数量 数量+手续费
      $market_fee = $this->config['market_fee'];
      $fee = bcmul($market_fee,$num,4);
      $Total = bcadd($num,$fee, 4);

      //查询卖家用户账户余额
      $Balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->lock(true)->value('balance');

      if (bccomp($Balance, $Total, 4) == -1) {
        Db::rollback();
        $this->apiReply(2, '您当前的余额不足，无法进行此交易！');
      }

      if($market['num'] <= bcadd($market['deal_num'],$num,4)){
        //修改订单状态
        Db('market')->where(['market_id' => $market_id])->update(['status' => 2,'deal_num' => bcadd($market['deal_num'],$num,4)]);
      }else{
        //修改订单状态
        Db('market')->where(['market_id' => $market_id])->update(['status' => 1,'deal_num' => bcadd($market['deal_num'],$num,4)]);
      }

      //扣除卖家账户余额
      Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->setField('balance', bcsub($Balance, $Total, 4));


      //生成交易日志
      $log_market_id = Db('log_market')->insertGetId([
        'market_id' => $market_id,
        'type' => 1,
        'coin_id' => $market['coin_id'],
        'buyer_id' => $market['user_id'],
        'buyer_username' => $market['username'],
        'seller_id' => $this->user_id,
        'seller_username' => $this->user['username'],
        'price' => $market['price'],
        'num' => $num,
        'addtime' => time(),
        'status' => 1,
      ]);

      //添加日志
      Db('log_coin')->insert([
        'user_id' => $this->user_id,
        'coin_id' => $market['coin_id'],
        'num' => $Total,
        'amount' => $Balance,
        'balance' => bcsub($Balance, $Total, 4),
        'addtime' => time(),
        'status' => 1,
        'union' => 'log_market',
        'union_id' => $log_market_id,
        'remark' => '出售订单ID：' . $market_id . '减少余额 ' . $Total.' 手续费:'.$fee,
        'type' => 1
      ]);

      // 发短信提醒买家
      $BuyMobile = Db('users')->where(['user_id' => $market['user_id'], 'status' => 1])->field('`code`,`mobile`')->find();
      $send_msg = '';
      if ($BuyMobile) {
        $type = 4;
        $send_msg = '系统已短信通知买家，请耐心等待买家确认付款！';
        if ($BuyMobile['code'] == 86) {
          $result = $this->sendSMS($BuyMobile['mobile'], $type, ['msg'=>$this->coins[$market['coin_id']]['name'],'time'=>$this->config['market_time'].'分钟']);
        } else {
          $result = $this->sendSMS_Global($this->user['mobile'], $type, $BuyMobile['code'], ['msg'=>$this->coins[$market['coin_id']]['name'],'time'=>$this->config['market_time'].'分钟']);
        }

        if ($result !== 'success') {
          $send_msg = '系统短信发送失败，请您主动和买家联系一下吧！';
        }
      }

      Db::commit();
      $this->apiReply(1, '出售成功！' . $send_msg, ['num'=>$Total,'name'=>$this->coins[$market['coin_id']]['name'],'log_market_id'=>$log_market_id]);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '出售失败');
    }
  }

/*********************************************************************************/

  /**
   * 卖单卖出
   * @param $coin_id
   * @param $price
   * @param $num
   * @param $token
   */
  public function Sell_Out($token,$num,$price,$coin_id,$password)
  {

    if(time() < strtotime(date('Ymd').' 06:00:00') || time() > strtotime(date('Ymd').' 21:00:00')) $this->apiReply(2, '交易开放时间为06:00到21:00！');

    $this->isVerifyId();
    $this->isSetPayment();

    $market_sell_mini = $this->config['market_sell_mini'];
    $market_sell_max = $this->config['market_sell_max'];
    $sell_price_mini = $this->config['sell_price_mini'];
    $sell_price_max = $this->config['sell_price_max'];

    $num = floatval ($num);
    $price = floatval ($price);
    if (bccomp($num,$market_sell_mini,4)==-1) $this->apiReply(2, '卖出数量最少 ' . $market_sell_mini . ' 个！');
    if (bccomp($market_sell_max,$num,4)==-1) $this->apiReply(2, '卖出数量最多 ' . $market_sell_max . ' 个！');
    if (bccomp($price,$sell_price_mini,2)==-1) $this->apiReply(2, '卖出价格最低 ' . $sell_price_mini . ' CNY！');
    if (bccomp($sell_price_max,$price,2)==-1) $this->apiReply(2, '卖出价格最高 ' . $sell_price_max . ' CNY！');

    $user_paypass = Db('users')->where(['user_id' => $this->user_id, 'status' => 1])->value('paypass');
    if (!$user_paypass) $this->apiReply(888, '请先设置交易密码?');
    if (md5($password . config('password_str')) != $user_paypass) {
      $text = $this->login_log(-5, $this->user['mobile'], '交易密码不正确！', $this->user_id);
      $this->apiReply(3, $text);
    }

    Db::startTrans();
    try {

      // 是否有未付款的订单
      $LogCount = Db('log_market')->where(['buyer_id|seller_id' => $this->user_id,'type'=>2,'status' => ['in', [11,12]]])->count();

      $MarketCount = Db('market')->where(['user_id' => $this->user_id,'type'=>2,'status' => ['in', [0,1]]])->count();
      $buy_num = $this->config['buy_num'];
      $Count = $LogCount + $MarketCount;
      if ($Count >= $buy_num) {
        $this->apiReply(2, '有'.$buy_num.'单交易未完成，请先完成订单！');
      }

      $market_fee = $this->config['market_fee'];
      $fee = bcmul($num,$market_fee,4);
      $total = bcadd($num,$fee,4);

      //查询卖家用户账户余额
      $Balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $coin_id])->lock(true)->value('balance');

      if (bccomp($Balance, $total, 4) == -1) {
        Db::rollback();
        $this->apiReply(2, '您当前的余额不足，无法进行此交易！');
      }

      Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $coin_id])->update(['balance'=>bcsub($Balance,$total,4)]);


      $market_id = Db('market')->insertGetId([
        'user_id' => $this->user_id,
        'username' => $this->user['username'],
        'coin_id' => $coin_id,
        'price' => $price,
        'num' => $num,
        'deal_num' => 0,
        'fee'=> $fee,
        'deal_fee' => 0,
        'type' => 2,
        'status' => 0,
        'addtime' => time(),
      ]);

      //添加日志
      Db('log_coin')->insert([
        'user_id' => $this->user_id,
        'coin_id' => $coin_id,
        'num' => $total,
        'amount' => $Balance,
        'balance' => bcsub($Balance,$total,4),
        'addtime' => time(),
        'status' => 1,
        'union' => 'market',
        'union_id' => $market_id,
        'remark' => '出售订单ID：' . $market_id . '减少余额 ' . $total.' 手续费:'.$fee,
        'type' => 2
      ]);

      Db::commit();
      $this->apiReply(1, '操作成功', ['num'=>$total,'coin_name'=>$this->coins[$coin_id]['name']]);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '操作失败');
    }
  }

  /**
   * 卖出 买家购买，加载订单信息
   * @param $token
   * @param $market_id
   */
  public function LoadSellReveal($token, $market_id)
  {
    $Market = Db('market')->where(['market_id' => $market_id,'status'=>['in',[0,1]],'type'=>2])->field('market_id,user_id,price,(num-deal_num) total,coin_id,username')->find();

    if (!$Market) $this->apiReply(2, '订单异常！');

    $Market['name'] = $this->coins[$Market['coin_id']]['name'];

    $Market['idcard'] = Db('idaudit')->where(['user_id'=>$this->user_id,'status'=>1])->count();

    $Market['alipay'] = Db('user_payment')->where(['user_id' => $Market['user_id'], 'type' => 1, 'deleted' => 0])->count()?:0;

    $Market['wechat'] = Db('user_payment')->where(['user_id' => $Market['user_id'], 'type' => 2, 'deleted' => 0])->count()?:0;

    $Market['card'] = Db('user_payment')->where(['user_id' => $Market['user_id'], 'type' => 3, 'deleted' => 0])->count()?:0;

    $this->apiReply(1, '操作成功',$Market );
  }

  /**
   * 卖单 买入
   * @param $market_id
   * @param $num
   * @param $token
   */
  public function To_Buy($token,$market_id,$num)
  {

    $this->isVerifyId();
    //买入不需要自己一定绑定收款方式
//    $this->isSetPayment();

    if(time() < strtotime(date('Ymd').' 06:00:00') || time() > strtotime(date('Ymd').' 21:00:00')) $this->apiReply(2, '交易开放时间为06:00到21:00！');

    Db::startTrans();
    try {

      //查询订单
      $market = Db('market')->where(['market_id' => $market_id,'type'=>2,'status' => ['in',[0,1]]])->field('user_id,username,coin_id,price,num,deal_num,deal_fee')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交，请交易其它订单！');
      }

      $user_status = Db('users')->where(['user_id'=>$market['user_id']])->value('status');
        if ($user_status<1) {
            Db::rollback();
            $this->apiReply(2, '该卖家账号异常，订单暂停交易！');
        }

      if ($this->user_id == $market['user_id']) {
        Db::rollback();
        $this->apiReply(2, '不能交易自己的挂单！');
      }

      // 是否有未付款的订单
      $LogCount = Db('log_market')->where(['buyer_id|seller_id' => $this->user_id,'type'=>2,'status' => ['in', [11,12]]])->count();

      $MarketCount = Db('market')->where(['user_id' => $this->user_id,'type'=>2,'status' => ['in', [0,1]]])->count();
      $buy_num = $this->config['buy_num'];
      $Count = $LogCount + $MarketCount;
      if ($Count >= $buy_num) {
        $this->apiReply(2, '有'.$buy_num.'单交易未完成，请先完成订单！');
      }

      //查询最小购买数量
      $min = $this->config['market_buy_mini'];

      //计算未成交数量
      $total_num = bcsub($market['num'],$market['deal_num'],4);

      if(bccomp($total_num,$num,4)==-1){
        Db::rollback();
        $this->apiReply(2, '订单余额不足！');
      }

      //计算成交后剩余数量
      $over_num = bcsub($total_num,$num,4);

      if($over_num > 0 && bccomp($over_num,$min,4)==-1){
        Db::rollback();
        $this->apiReply(2, '交易完成后,剩余订单数量小于最小求购数量'.$min.'请重新输入数量');
      }

      //修改订单信息,

      if($total_num <= $num){
        //修改订单状态
        Db('market')->where(['market_id' => $market_id])->update(['status' => 2,'deal_num' => bcadd($market['deal_num'],$num,4)]);
      }else{
        //修改订单状态
        Db('market')->where(['market_id' => $market_id])->update(['status' => 1,'deal_num' => bcadd($market['deal_num'],$num,4)]);
      }

      //添加订单日志
      $log_market_id = Db('log_market')->insertGetId([
        'market_id' => $market_id,
        'type' => 2,
        'coin_id' => $market['coin_id'],
        'buyer_id' => $this->user_id,
        'buyer_username' => $this->user['username'],
        'seller_id' => $market['user_id'],
        'seller_username' => $market['username'],
        'price' => $market['price'],
        'num' => $num,
        'addtime' => time(),
        'status' => 11,
      ]);

      Db::commit();
      $this->apiReply(1, '下单成功', $log_market_id);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '操作失败');
    }
  }

  /******************************************************************************************/

  /**
   * 我的挂单列表
   * @param $token
   * @param $coin_id
   * @param $page
   * @param $pageSize
   */
  public function MyMarket($token,$coin_id,$page = 1, $pageSize = 200)
  {
    $data = Db('market')
      ->field('addtime,num,price,status,(num*price) as total,if(type>1,"挂卖","挂买") as types,market_id,type')
      ->where(['user_id' => $this->user_id,'coin_id'=>$coin_id])
      ->order('market_id desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();

    $this->apiReply(1, '操作成功', $data);
  }

  /**
   * 挂卖详情
   * @param $token
   * @param $market_id
   * @param $type
   */
  public function MyBuyDetails($token, $market_id,$type=1)
  {
    $data = Db('market')->where(['market_id' => $market_id,'user_id'=>$this->user_id,'type'=>$type])->field('market_id,num,price,(num-deal_num) total,coin_id,(num*price) as price_total,addtime,fee,status')->find();

    $data['name'] = $this->coins[$data['coin_id']]['name'];

    $this->apiReply(1, '操作成功',$data );
  }

  /**
   * 挂卖撤销
   * @param $market_id
   * @param $token
   */
  public function SellRevoke($token,$market_id)
  {

    Db::startTrans();
    try {

      //查询订单
      $market = Db('market')->where(['market_id' => $market_id,'user_id'=>$this->user_id,'type'=>2,'status' => ['in',[0,1]]])->field('user_id,username,coin_id,price,num,deal_num,deal_fee,status')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交或已撤销！');
      }

      //计算未成交数量
      $total_num = bcsub($market['num'],$market['deal_num'],4);
      //计算剩余数量的手续费
      $fee = $this->config['market_fee'];
      $to_fee = bcmul($total_num,$fee,4);
      $total = bcadd($total_num,$to_fee,4);

      //修改订单状态
      Db('market')->where(['market_id' => $market_id])->update(['status' => -1]);
      //增加用户余额
      //查询卖家用户账户余额
      $Balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->lock(true)->value('balance');

      Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->update(['balance'=>bcadd($Balance,$total,4)]);

      //添加日志
      Db('log_coin')->insert([
        'user_id' => $this->user_id,
        'coin_id' => $market['coin_id'],
        'num' => $total,
        'amount' => $Balance,
        'balance' => bcadd($Balance,$total,4),
        'addtime' => time(),
        'status' => 1,
        'union' => 'market',
        'union_id' => $market_id,
        'remark' => '撤销订单ID：' . $market_id . '增加余额 ' . $total_num.' 手续费:'.$to_fee,
        'type' => 51
      ]);

      Db::commit();
      $this->apiReply(1, '撤销成功', ['status'=>$market['status'],'num'=>$total]);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '撤销失败');
    }
  }

  /**
   * 我的挂单列表
   * @param $token
   * @param $market_id
   * @param $page
   * @param $pageSize
   */
  public function MyBuyList($token,$market_id,$page = 1, $pageSize = 10000000000)
  {
    $data = Db('log_market')
      ->field('buyer_username,num,(price*num) as total,status,addtime')
      ->where(['seller_id' => $this->user_id,'market_id'=>$market_id])
      ->order('addtime desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();

    $this->apiReply(1, '操作成功', $data);
  }

  /**
   * 我的挂单列表
   * @param $token
   * @param $market_id
   * @param $page
   * @param $pageSize
   */
  public function MySellList($token,$market_id,$page = 1, $pageSize = 10000000000)
  {
    $data = Db('log_market')
      ->field('seller_username,num,(price*num) as total,status,addtime')
      ->where(['buyer_id' => $this->user_id,'market_id'=>$market_id])
      ->order('addtime desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
    $this->apiReply(1, '操作成功', $data);
  }

  /**
   * 挂买撤销
   * @param $market_id
   * @param $token
   */
  public function BuyRevoke($token,$market_id)
  {

    Db::startTrans();
    try {

      //查询订单
      $market = Db('market')->where(['market_id' => $market_id,'user_id'=>$this->user_id,'type'=>1,'status' => ['in',[0,1]]])->field('(num-deal_num) as total,status')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交或已撤销！');
      }

      //修改订单状态
      Db('market')->where(['market_id' => $market_id])->update(['status' => -1]);

      Db::commit();
      $this->apiReply(1, '已撤销',['num'=>$market['total']]);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '撤销失败');
    }
  }


  /**
   * 买入 卖方支付方式詳情
   * @param $token
   * @param $type
   * @param $market_id
   * @param $types
   */
  public function MyPay($token,$type,$market_id,$types)
  {

    if($types=='market'){
      $market = Db('market')->where(['market_id'=>$market_id])->field('user_id,coin_id')->find();
      $user_id = $market['user_id'];
    }elseif ($types=='log_market'){
      $log_market = Db('log_market')->where(['log_market_id'=>$market_id])->field('seller_id,coin_id')->find();
      $user_id = $log_market['seller_id'];
    }

    $data = Db('user_payment')->where(['type'=>$type,'user_id'=>$user_id])->field('payment_account,payment_nickname,payment_qrcode,bank_name,bank_sub_name,bank_number,bank_username,mobile,addtime')->find();

    $data['type'] = $type;

    $this->apiReply(1, '操作成功', $data);
  }


  /**
   * 历史记录
   * @param $token
   * @param $coin_id
   * @param $page
   * @param $pageSize
   */
  public function MyLogMarket($token,$coin_id,$page = 1, $pageSize = 10)
  {
    $data = Db('log_market')
      ->field('addtime,type,num,price,status,(num*price) as total,log_market_id,type,buyer_id,seller_id')
      ->where(['buyer_id|seller_id' => $this->user_id,'coin_id'=>$coin_id])
      ->order('log_market_id desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();

    $this->apiReply(1, '操作成功', $data);
  }


  /**
   * 挂买  买入方显示页面
   * @param $token
   * @param $log_market_id
   */
  public function LoadLogMarket($token,$log_market_id)
  {
    $data = Db('log_market')->where(['log_market_id'=>$log_market_id,'buyer_id'=>$this->user_id,'type'=>1])->field('log_market_id,num,price,(price*num) as total,seller_id,seller_username,addtime,pay_time,pay_type,pay_img,status,coin_id')->find();

    $data['name'] = $this->coins[$data['coin_id']]['name'];

    $data['official_WeChat'] = $this->config['official_WeChat'];

    $data['alipay'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 1, 'deleted' => 0])->count()?:0;

    $data['wechat'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 2, 'deleted' => 0])->count()?:0;

    $data['card'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 3, 'deleted' => 0])->count()?:0;

    $data['nowtime'] = time() * 1000;

    $data['market_time'] = $this->config['market_time'];

    if($data['status'] == 1){

      $times = $data['addtime'] + $data['market_time'] * 60;
    }else if($data['status']==2){

      $times =$data['pay_time'] + $data['market_time'] * 60;
    }else{
      $times = 0;
    }

    if($times>=time()){
      $data['times'] = 1;
    }else{
      $data['times'] = 0;
    }

    $this->apiReply(1, '操作成功', $data);
  }


  /**
   * 挂买 出售方显示页面
   * @param $token
   * @param $log_market_id
   */
  public function history_sel_details($token,$log_market_id)
  {

    $data = Db('log_market')->where(['log_market_id'=>$log_market_id,'seller_id'=>$this->user_id,'type'=>1])->field('log_market_id,num,price,(price*num) as total,addtime,buyer_username,pay_time,pay_type,pay_img,status,coin_id')->find();

    $market_fee = $this->config['market_fee'];

    $data['fee'] = bcmul($data['num'],$market_fee,4);

    $data['name'] = $this->coins[$data['coin_id']]['name'];

    $data['official_WeChat'] = $this->config['official_WeChat'];

    $data['nowtime'] = time() * 1000;

    $data['market_time'] = $this->config['market_time'];

    if($data['status'] == 1){

      $times = $data['addtime'] + $data['market_time'] * 60;
    }else if($data['status']==2){

      $times =$data['pay_time'] + $data['market_time'] * 60;
    }else{
      $times = 0;
    }

    if($times>=time()){
      $data['times'] = 1;
    }else{
      $data['times'] = 0;
    }

    $this->apiReply(1, '操作成功', $data);
  }


  /**
   * 挂买  卖方确认收款完成订单
   * @param $log_market_id
   * @param $token
   */
  public function MySellConfirm($token,$log_market_id)
  {

    Db::startTrans();
    try {

      //查询订单
      $market = Db('log_market')->where(['log_market_id' => $log_market_id,'seller_id'=>$this->user_id,'type'=>1,'status' =>2])->field('buyer_id,num,price,coin_id')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交或已撤销！');
      }

      //修改订单状态
      Db('log_market')->where(['log_market_id' => $log_market_id,'seller_id'=>$this->user_id,'type'=>1,'status' =>2])->update(['status' => 3,'end_time'=>time()]);

      //查询卖家用户账户余额
      $Balance = Db('user_coin')->where(['user_id' => $market['buyer_id'], 'coin_id' => $market['coin_id']])->lock(true)->field('balance,market_total')->find();
      Db('user_coin')->where(['user_id' => $market['buyer_id'], 'coin_id' => $market['coin_id']])->update(['balance'=>bcadd($Balance['balance'],$market['num'],4),'market_total'=>bcadd($Balance['market_total'],$market['num'],4)]);

      // //查询买家用户账户余额,增加成交总量
      $user_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->lock(true)->value('market_total');
      Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->update(['market_total'=>bcadd($user_balance,$market['num'],4)]);

      //更新实时价格
      Db('coin')->where(['status'=>1])->update(['price_cny'=>$market['price']]);

      //添加日志
      Db('log_coin')->insert([
        'user_id' => $market['buyer_id'],
        'coin_id' => $market['coin_id'],
        'num' => $market['num'],
        'amount' => $Balance['balance'],
        'balance' => bcadd($Balance['balance'],$market['num'],4),
        'addtime' => time(),
        'status' => 1,
        'union' => 'log_market',
        'union_id' => $log_market_id,
        'remark' => '购入订单ID：' . $log_market_id . '增加余额 ' . $market['num'],
        'type' => 52
      ]);

      Db::commit();
      $this->apiReply(1, '订单已确认');
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '确认失败,请重试或联系客服');
    }
  }


  /**
   * 挂卖  买入方显示页面
   * @param $token
   * @param $log_market_id
   */
  public function LoadLogMarketList($token,$log_market_id)
  {
    $data = Db('log_market')->where(['log_market_id'=>$log_market_id,'buyer_id'=>$this->user_id,'type'=>2])->field('log_market_id,num,price,(price*num) as total,seller_id,seller_username,addtime,pay_time,pay_type,pay_img,status,coin_id')->find();

    $data['name'] = $this->coins[$data['coin_id']]['name'];

    $data['official_WeChat'] = $this->config['official_WeChat'];

    $data['alipay'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 1, 'deleted' => 0])->count()?:0;

    $data['wechat'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 2, 'deleted' => 0])->count()?:0;

    $data['card'] = Db('user_payment')->where(['user_id' => $data['seller_id'], 'type' => 3, 'deleted' => 0])->count()?:0;

    $data['nowtime'] = time() * 1000;

    $data['market_time'] = $this->config['market_time'];

    if($data['status'] == 11){

      $times = $data['addtime'] + $data['market_time'] * 60;
    }else if($data['status']==12){

      $times =$data['pay_time'] + $data['market_time'] * 60;
    }else{
      $times = 0;
    }

    if($times>=time()){
      $data['times'] = 1;
    }else{
      $data['times'] = 0;
    }

    $this->apiReply(1, '操作成功', $data);
  }

  /**
   * 挂卖 出售方显示页面
   * @param $token
   * @param $log_market_id
   */
  public function history_details_sale($token,$log_market_id)
  {

    $data = Db('log_market')->where(['log_market_id'=>$log_market_id,'seller_id'=>$this->user_id,'type'=>2])->field('log_market_id,num,price,(price*num) as total,addtime,buyer_username,pay_time,pay_type,pay_img,status,coin_id')->find();

    $market_fee = $this->config['market_fee'];

    $data['fee'] = bcmul($data['num'],$market_fee,4);

    $data['name'] = $this->coins[$data['coin_id']]['name'];

    $data['official_WeChat'] = $this->config['official_WeChat'];

    $data['nowtime'] = time() * 1000;

    $data['market_time'] = $this->config['market_time'];

    if($data['status'] == 11){

      $times = $data['addtime'] + $data['market_time'] * 60;
    }else if($data['status']==12){

      $times =$data['pay_time'] + $data['market_time'] * 60;
    }else{
      $times = 0;
    }

    if($times>=time()){
      $data['times'] = 1;
    }else{
      $data['times'] = 0;
    }

    $this->apiReply(1, '操作成功', $data);
  }

  /**
   * 挂卖  卖方确认收款完成订单
   * @param $log_market_id
   * @param $token
   */
  public function MyBuyConfirm($token,$log_market_id)
  {

    Db::startTrans();
    try {

      //查询订单
      $market = Db('log_market')->where(['log_market_id' => $log_market_id,'seller_id'=>$this->user_id,'type'=>2,'status' =>12])->field('buyer_id,num,price,coin_id')->lock(true)->find();

      if (!$market) {
        Db::rollback();
        $this->apiReply(2, '订单已成交或已撤销！');
      }

      //修改订单状态
      Db('log_market')->where(['log_market_id' => $log_market_id,'seller_id'=>$this->user_id,'type'=>2,'status' =>12])->update(['status' => 13,'end_time'=>time()]);

      //查询卖家用户账户余额
      $Balance = Db('user_coin')->where(['user_id' => $market['buyer_id'], 'coin_id' => $market['coin_id']])->lock(true)->field('balance,market_total')->find();
      Db('user_coin')->where(['user_id' => $market['buyer_id'], 'coin_id' => $market['coin_id']])->update(['balance'=>bcadd($Balance['balance'],$market['num'],4),'market_total'=>bcadd($Balance['market_total'],$market['num'],4)]);

      // //查询买家用户账户余额,增加成交总量
      $user_balance = Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->lock(true)->value('market_total');
      Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $market['coin_id']])->update(['market_total'=>bcadd($user_balance,$market['num'],4)]);

      //更新实时价格
      Db('coin')->where(['status'=>1])->update(['price_cny'=>$market['price']]);

      //添加日志
      Db('log_coin')->insert([
        'user_id' => $market['buyer_id'],
        'coin_id' => $market['coin_id'],
        'num' => $market['num'],
        'amount' => $Balance['balance'],
        'balance' => bcadd($Balance['balance'],$market['num'],4),
        'addtime' => time(),
        'status' => 1,
        'union' => 'log_market',
        'union_id' => $log_market_id,
        'remark' => '购入订单ID：' . $log_market_id . '增加余额 ' . $market['num'],
        'type' => 53
      ]);

      Db::commit();
      $this->apiReply(1, '订单已确认');
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '确认失败,请重试或联系客服');
    }
  }

}