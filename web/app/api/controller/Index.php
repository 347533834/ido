<?php
/**
 * Created by PhpStorm.
 * User: ray
 * Date: 2017/12/17
 * Time: 18:37
 */

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

class Index extends Common
{
    function index()
    {
        return 'hi.';
    }


    /**
     * 交易专区
     */
    public function LoadCoin()
    {
        $data['coin'] = db('coin')->where('is_c2c', '1')->field('coin_id,name')->order('sort')->select();
        $data['fee'] = $this->config['market_fee'];
        $this->apiResp($data);
    }


    /**
     * 买单列表
     * @param $coin_id
     * @param $type
     * @param $sort
     * @param $page
     * @param $pageSize
     */
    public function MarketList($coin_id, $type = 1, $sort = 'm.price desc', $page = 1, $pageSize = 5)
    {
        if ($type == 2) $sort = 'm.price desc';

        $data = Db('market')->alias('m')->join('user_coin u', 'm.user_id = u.user_id and m.coin_id = u.coin_id')->where(['m.status' => ['in', [0, 1]], 'm.coin_id' => $coin_id, 'm.type' => $type])
            ->field('m.market_id,m.user_id,m.username,m.coin_id,m.price,m.num,m.deal_num,m.type,(m.price*m.num)as total,u.market_total')
            ->order($sort, 'm.market_id asc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
        $this->apiReply(1, '操作成功', $data);
    }


    public function trade_candle($coin_id)
    {
        $list = Db('log_trade_day')->field("UNIX_TIMESTAMP(date) as Date,open_price as OpeningPrice,close_price as ClosingPrice,high_price as HighestPrice,low_price as LowestPrice,num as Volume,(close_price-open_price) as ChangePrice,(close_price-open_price)/open_price as ChangeRate")
            ->where(array('exch_coin_id' => $coin_id))->select();

        $data['KLineList'] = $list;

        $this->apiResp($data);

    }

    /**
     * 买家不付款自动撤销订单
     */
    public function buy_revoke()
    {
        $market_time = $this->config['market_time'] * 60;

        $logMarkets = db('log_market')->field('log_market_id,market_id,buyer_id,seller_id,num,coin_id,status')->where(['status' => ['in', [1, 11]]])->where('addtime', '<', time() - $market_time)->order('addtime ASC')->select();

        if (!$logMarkets) $this->apiReply(1, 'empty');

        $auto_revoke = $this->redis->get('auto_revoke');
        if (time() - $auto_revoke < 30) {
            $this->apiReply(1);
        }

        $this->redis->set('auto_revoke', time());
        foreach ($logMarkets as $log_market) {
            //给买单加锁
            $key = 'log:market:' . $log_market['log_market_id'];
            $this->redis->lock($key);

            Db::startTrans();
            try {

                //开始给用户警告信息
                $warn_1 = db('log_user_warn')->where(['user_id' => $log_market['buyer_id'], 'type' => 1, 'status' => 1])->count();

                db('log_user_warn')->insert([
                    'user_id' => $log_market['buyer_id'],
                    'type' => 1,
                    'status' => 1,
                    'log_market_id' => $log_market['log_market_id'],
                    'addtime' => time(),
                    'remarks' => '警告' . ($warn_1 + 1) . '次',
                ]);
                if ($warn_1 >= 2) {
                    //警告达到三次 变成严重警告
                    db('log_user_warn')->where(['user_id' => $log_market['buyer_id'], 'type' => 1, 'status' => 1])->setfield('status', 0);
                    //会员封号
                    $warn_2 = db('log_user_warn')->where(['user_id' => $log_market['buyer_id'], 'type' => 2, 'status' => 1])->count();
                    db('log_user_warn')->insert([
                        'user_id' => $log_market['buyer_id'],
                        'type' => 2,
                        'status' => 1,
                        'log_market_id' => $log_market['log_market_id'],
                        'addtime' => time(),
                        'remarks' => '累计三次警告，变成严重警告' . ($warn_2 + 1) . '次',
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
                    db('users')->where(['user_id' => $log_market['buyer_id']])->update([
                        'freeze_time' => time() + $warn_time,
                        'status' => 0,
                    ]);
                    //这里执行删除当前用户的cache
                    cache('cache_userinfo_' . $log_market['buyer_id'], null);
                }
                // 修改记录状态
//        Db('log_market')->where('log_market_id', $log_market['log_market_id'])->update(['is_punish' =>1]);

                // 修改记录状态
                Db('log_market')->where('log_market_id', $log_market['log_market_id'])->update(['status' => -1, 'end_time' => time()]);


                $balance = Db('user_coin')->where(['user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']])->lock(true)->value('balance');

                //计算手续费
                $fee = bcmul($this->config['market_fee'], $log_market['num'], 4);
                $total = bcadd($log_market['num'], $fee, 4);

                Db('user_coin')->where(['user_id' => $log_market['seller_id'], 'coin_id' => $log_market['coin_id']])->update(['balance' => bcadd($balance, $total, 4)]);

                Db('log_coin')->insert([
                    'user_id' => $log_market['seller_id'],
                    'coin_id' => $log_market['coin_id'],
                    'num' => $total,
                    'amount' => $balance,
                    'balance' => bcadd($balance, $total, 4),
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'log_market',
                    'union_id' => $log_market['log_market_id'],
                    'remark' => '买家长时间没有付款，订单自动撤销，订单数量:' . $log_market['num'] . '手续费:' . $fee,
                    'type' => 54
                ]);

                Db::commit();
                $this->redis->unlock($key);
                $this->apiReply(1, '交易超时！', ['status' => -1, 'status_text' => '交易超时，买家记录严重警告一次！']);
            } catch (Exception $e) {
                Db::rollback();
                $this->redis->unlock($key);
                Log::error($e->getMessage());
                $this->apiReply(2, '操作失败');
            }
        }

        $this->apiReply(1, '操作成功');
    }

    public function to_cancel_trade()
    {

        $trades = db('trade')->where(['status' => array('in', '0,1')])->field('trade_id,user_id,addtime,num,price,total,deal_num,(num-deal_num) left_num,status')->select();

        foreach ($trades as $trade) {

            Db::startTrans();

            try {

                if (!$trade) {
                    Db::rollback();
                    $this->apiReply(2, '订单异常！');
                }

                $usdt_coin_id = 2;
                $old_buy_usdt_balance = Db('user_coin')->where(['user_id' => $trade['user_id'], 'coin_id' => $usdt_coin_id])->lock(true)->value('balance');//操作前usdt余额

                //未成交的求购数量
                $old_left_num = bcsub($trade['num'], $trade['deal_num'], 4);
                $back_total = bcmul($old_left_num, $trade['price'], 4);//退还的usdt
                $new_buy_usdt_balance = bcadd($old_buy_usdt_balance, $back_total, 4);//撤销后usdt余额

                //user_coin表更新
                Db('user_coin')->where(['user_id' => $trade['user_id'], 'coin_id' => $usdt_coin_id])->update(['balance' => $new_buy_usdt_balance]);
                $time = time();
                //trade表更新
                Db('trade')->where('trade_id', $trade['trade_id'])->update([
                    'status' => -1,
                    'uptime' => $time,
                ]);
                //log_coin表增加记录
                Db('log_coin')->insertGetId([
                    'user_id' => $trade['user_id'],
                    'coin_id' => $usdt_coin_id,
                    'type' => 63,
                    'num' => $back_total,
                    'amount' => $old_buy_usdt_balance,
                    'balance' => $new_buy_usdt_balance,
                    'addtime' => $time,
                    'status' => 1,
                    'union' => 'trade',
                    'union_id' => $trade['trade_id'],
                    'remark' => '币币交易撤销返还' . $back_total . 'USDT',
                ]);

                Db::commit();
                $this->apiReply(1, '操作成功！', ['total' => $back_total]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                $this->apiReply(2, '操作失败', $e->getMessage());
            }

        }

    }

    public function add_trade_num($num = null)
    {

        if (!$num) $num = 100;

        Db('log_trade_day')->where(['date' => date('Ymd')])->setInc('num', $num);

        echo "<h1>币币交易增加交易量：$num</h1>";

    }

    public function config()
    {
        $data = $this->redis->hGetAll('config');
        $this->apiResp($data);
    }


    /**
     * 引导页
     */
    public function first_picture()
    {
        $data = Db('first_picture')->where(['type' => 7, 'status' => 1])->order('sorting asc')->field('url,addtime,jump_url,title')->select();
        $this->apiResp($data);

    }

    public function coin_trade()
    {
        $usdt_cny = db('coin')->where('coin_id', 2)->cache(60)->value('price_cny');

//        $ido = Db('log_trade_day')->where(['date' => date("Ymd"), 'exch_coin_id' => 1])->field('num,open_price,close_price,cny_price')->order('id desc')->find();
        $ido = Db('log_trade_day')->where(['exch_coin_id' => 1])->field('num,open_price,close_price,cny_price')->order('id desc')->find();
        $data['ido_open_price'] = $ido['close_price'];
        $data['ido_open_price_old'] = $ido['open_price'];
        $data['ido_volume'] = $ido['num'];
        $data['ido_open_price_cny'] = $ido['cny_price'];

//        $ach = Db('log_trade_day')->where(['date' => date("Ymd"), 'exch_coin_id' => 23])->field('num,open_price,close_price,cny_price')->order('id desc')->find();
        $ach = Db('log_trade_day')->where(['exch_coin_id' => 23])->field('num,open_price,close_price,cny_price')->order('id desc')->find();
        $data['ach_open_price'] = $ach['close_price'];
        $data['ach_open_price_old'] = $ach['open_price'];
        $data['ach_volume'] = $ach['num'];
        $data['ach_open_price_cny'] = $ach['cny_price'];

        $fid = Db('log_trade_day')->where(['exch_coin_id' => 24])->field('num,open_price,close_price,cny_price')->order('id desc')->find();
        $data['fid_open_price'] = $fid['close_price'];
        $data['fid_open_price_old'] = $fid['open_price'];
        $data['fid_volume'] = $fid['num'];
        $data['fid_open_price_cny'] = $fid['cny_price'];

        $data['data'] = Db('coin_trade')->alias('a')
            ->join('coin c', 'c.coin_id=a.exch_coin_id')
            ->where(['a.status' => 1])
            ->order('a.sort asc')
            ->field('a.trade_name,a.main_coin_name,a.exch_coin_name,a.status,c.logo,c.addtime,c.open_price,c.price,c.volume,c.price*' . $usdt_cny . ' cny')
            ->cache(60)
            ->select();
        $data['usdt_cny'] = $usdt_cny;
        if ($data) {
            $this->apiResp($data);
        }
    }

    public function coin_trade_show()
    {
        $data['data'] = Db('coin_trade')->alias('a')
            ->join('coin c', 'c.coin_id=a.exch_coin_id')
            ->where('a.status=1')
            ->order('a.sort asc')
            ->field('a.trade_name,a.main_coin_name,a.exch_coin_name,c.logo,c.addtime,c.is_trade')
            ->cache(60)
            ->select();
        if ($data) {
            $this->apiResp($data);
        }
    }

    public function load_notice()
    {
        $data = db('notice')->where('status', 0)->order('id desc')->value('title');
        $this->apiResp($data);
    }

}