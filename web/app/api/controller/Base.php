<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;
use think\Input;
use Web3\Personal;

class Base extends Common
{

    /**
     * 用户id
     * @var
     */
    protected $user_id;
    /**
     * 用户信息
     * @var
     */
    protected $user;

    protected $date_time;

    /**
     * initialize
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->user_id = $this->get_user_id();
        $this->user = $this->get_userinfo();
    }

    /**
     * 判断TOKEN
     * @return mixed
     */
    public function get_user_id()
    {
        $token = input('token');
        if (!$token)
            $this->apiResp(null, 'fail');

        try {
            $arr = explode('+', authcode($token, 'DECODE'));
            if (!$arr[0])
                $this->apiResp(null, 'token_error');

            $session_id = $this->redis->hGet('user:' . $arr[0], 'session');

            if (input('post.session_id')) {
                if (md5(md5($session_id)) != input('request.session_id')) $this->apiResp(null, 'login_timeout');
            } else {
                if ($session_id != session('session_id')) {
                    if (CONTROLLER_NAME == 'Matrix' && ACTION_NAME == 'refresh') {
                        $this->apiReply(2, '没有登陆!|No landing!');
                    } else {
                        $this->apiResp(null, 'login_timeout');
                    }
                }
                $session = session($arr[0] . '.' . $arr[1]);
                if (!$session)
                    $this->apiResp(null, 'login_timeout');
                $user_lock_time = $this->redis->zScore('user_lock', $arr[0] . ':' . $arr[1]) - time();
                if ($user_lock_time >= 0) {
                    $this->apiResp(null, 'login_timeout');
                }
                session($arr[0] . '.' . $arr[1], time());
            }
            return $arr[0];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $this->apiResp(null, 'fail');
        }
    }

    /**
     * 加载用户信息
     * @return mixed
     */
    public function get_userinfo($refresh = false)
    {
        if (!$refresh)
            $this->user = cache('cache_userinfo_' . $this->user_id);

        if ($this->user) return $this->user;

        $this->user = $this->loadUser($this->user_id);
        cache('cache_userinfo_' . $this->user_id, $this->user);
        return $this->user;
    }

    /**
     * 更新会员信息
     * @param $token
     */
    public function refresh_user($token)
    {

        $this->user = $this->loadUser($this->user_id);
        cache('cache_userinfo_' . $this->user_id, $this->user);
        $userInfo = $this->user;
        $userInfo['token'] = $token;
        $this->apiResp($userInfo);
    }

    /**
     * 判断是否实名认证
     */
    public function isVerifyId()
    {
        $this->user['id_status'] = Db('idaudit')->where('user_id', $this->user_id)->value('status');
        if ($this->user['id_status'] != 1) {
            $this->apiReply(9, '请先实名认证');
        }
    }

    /**
     * 判断是否设置支付方式
     */
    public function isSetPayment()
    {
        if (!$this->user['wechat_code'] && !$this->user['alipay_code'] && !$this->user['user_card']) {
            $this->apiReply(8, '请先设置收款信息');
        }
    }

    /**
     * 判断钱包地址是否设置
     */
    public function isSetWallet()
    {
        if (!count($this->user['wallet_out'])) {
            $this->apiReply(7, '请先设置钱包地址！');
        }
    }


    /**
     * 检查输入的数据是否合法
     * @param $float
     * @param $min
     * @param $max
     * @return float|string
     */
    public function checkFloat($float, $min, $max)
    {
        $num = floatval($float);
        $num = bcadd($num, 0, strlen(strstr($min, '.')) - 1);
        if (bccomp((string)$num, (string)$min, 6) == -1 || $num <= 0) $this->apiReply(2, "最小单位为" . $min . ",请重新输入！");
        if ($num > $max) $this->apiReply(2, "最高数量" . $max . "个JDC！");
        return $num;
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
    public function get_bonus($user_id, $num, $coin_id, $name, $union, $union_id)
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

//    /**
//     * 清理用户信息缓存
//     */
//    public function clear_user()
//    {
//        cache('cache_user_' . $this->user_id, null);
//    }

//    /**
//     * 退出登录
//     * @param $token
//     */
//    public function logout($token)
//    {
//        if (!$token)
//            $this->apiResp();
//
//        try {
//            $arr = explode('+', base64_decode($token));
//            if (!$arr[0])
//                $this->apiResp(null, 'fail');
//
//            session($arr[0] . '.' . $arr[1], null);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//        }
//        $this->apiResp();
//    }


    /**
     * 接收转入记录
     * @param $token
     * @param $wallet  钱包地址
     * @param $coin_id  通证ID
     */
    public function trans_log($token, $wallet, $coin_id)
    {
//        $this->isVerifyId();
        //排除地址为空的时抓取到的区块的记录
        if (!$wallet) $this->apiReply(1, '操作成功');

        // 排除不可以充值转账的coin
        if (!in_array($coin_id, [4])) $this->apiReply(1, '操作成功');

        // 排除传过来的地址和当前会员矩阵地址不一致
        $user_account = Db::name('user_account')->where(['user_id' => $this->user_id, 'coin_id' => $coin_id])->field('serc_wallet,serc_secret')->find();
        if ($wallet != $user_account['serc_wallet']) $this->apiReply(1, '操作成功');

        // 获取当前地址的交易记录
        $url = config('serc_gate') . "api/transactions?recipientId={$wallet}&orderBy=t_timestamp:desc&limit=100";
        $list = http($url);

        foreach ($list['transactions'] as $k => $item) {

            $log = db('log_wallet_trans')->where(['user_id' => $this->user_id, 'transactions_id' => $item['id']])->field('id,status,is_trans')->find();
            $item['amount'] = $item['amount'] / config('serc_rate');
            $item['fee'] = $item['fee'] / config('serc_rate');

            if (!$log) {

                $item['user_id'] = $this->user_id;
                $item['coin_id'] = $coin_id;
                $item['transactions_id'] = $item['id'];
                $item['addtime'] = time();
                unset($item['id']);
                unset($item['asset']);

                Db::name('log_wallet_trans')->insert($item);

                $this->apiReply(1, '操作成功');

            } else {
                //排除非此地址的区块上交易记录
                if ($item['recipientId'] != $wallet) continue;

                if ($log['status'] == 1) {
                    if ($log['is_trans'] == 0) {
                        $amount_list[$k]['amount'] = $item['amount'];
                        $amount_list[$k]['lid'] = $log['id'];
                    }
                    continue;
                }

                if ($log['status'] == 0 && $item['confirmations'] > 2) {
                    Db::startTrans();
                    try {
                        $balance = Db::name('user_account')->where(['user_id' => $this->user_id, 'coin_id' => $coin_id])->lock(true)->value('balance');

                        //判断是否有该币子
                        if ($balance) {
                            $amount = (float)bcadd($balance, $item['amount'], 6);
                            Db::name('user_account')->where(['user_id' => $this->user_id, 'coin_id' => $coin_id])->setField('balance', $amount);
                        } else {
                            Db::name('user_account')->insert([
                                'user_id' => $this->user_id,
                                'coin_id' => $coin_id,
                                'balance' => $item['amount'],
                            ]);
                            $balance = 0;
                        }

                        // 添加账户余额日志
                        Db::name('log_coin')->insert([
                            'user_id' => $this->user_id,
                            'num' => $item['amount'],
                            'amount' => $balance,
                            'balance' => bcadd($balance, $item['amount'], 6),
                            'status' => 1,
                            'addtime' => time(),
                            'union' => 'log_wallet_trans',
                            'union_id' => $log['id'],
                            'remark' => '收款ID:' . $log['id'] . '获得' . $item['amount'] . '个' . $this->coins[1]['name'],
                            'coin_id' => 4,
                            'type' => 80,
                        ]);

                        $item['id'] = $log['id'];
                        $item['status'] = 1;

                        // 更新充值记录
                        Db::name('log_wallet_trans')->update($item);

                        Db::commit();

                        $amount_list[$k]['amount'] = $item['amount'];
                        $amount_list[$k]['lid'] = $log['id'];

                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                        Db::rollback();
                        $this->apiReply(2, $e->getMessage());
                        continue;
                    }
                }
            }
        }
        $this->apiReply(1, '操作成功', ['amount_list' => $amount_list]);
    }

    /**
     * 交易
     * @param $token
     * @param $transaction  交易参数
     * @param $lid  收款交易ID
     * @param $type 1收款转到主节点，2转账
     */
    public function trans($token, $transaction, $lid, $type)
    {

        $this->isVerifyId();

        if ($type == 1) {

            //判断传过来的转账记录是否已经进行过区块转账
            $wallet_trans = Db::name('log_wallet_trans')->where(['id' => $lid, 'is_trans' => 0])->field('id,amount,fee,recipientId')->find();
            if (!$wallet_trans) {
                $this->apiReply(2, '操作失败');
            }

            //判断传过来的交易参数和数据库中的交易金额是否一致
            $transaction_arr = json_decode($transaction, true);
            if (($transaction_arr['amount'] + $transaction_arr['fee']) != $wallet_trans['amount'] * config('serc_rate') || $transaction_arr['recipientId'] != config('serc_wallet')) {
                $this->apiReply(2, '操作失败');
            }

        } elseif ($type == 2) {
            if (!Db::name('user_draw')->where(['draw_id' => $lid, 'is_trans' => 0])->value('draw_id')) {
                $this->apiReply(2, '操作失败');
            }
        } else {
            $this->apiReply(2, '操作失败');
        }

        $header = array(
            "version" => config('serc_version'),
            "magic" => config('serc_magic'),
            "Content-Type" => config('serc_content_type')
        );
        $url = config('serc_gate') . "peer/transactions";
        $res = http($url, '{"transaction":' . $transaction . '}', $header);

        if ($res['success']) {
            if ($type == 1) {
                $res = Db::name('log_wallet_trans')->where(['id' => $lid])->update(['is_trans' => 1]);
            } elseif ($type == 2) {
                $res = Db::name('user_draw')->where(['draw_id' => $lid])->update(['is_trans' => 1]);
            }
            $this->apiReply(1, '转账成功', $res);
        } else {
            $this->apiReply(2, '操作失败', $res);
        }

    }

    /**
     * 服务端转账
     * @param $token
     * @param $wallet_address  转入到的钱包地址
     * @param $wallet_num      转入的金额
     */
    public function trans_to($token, $wallet_address, $wallet_num)
    {
        $this->isVerifyId();

        $data['to'] = $wallet_address;
        $data['amount'] = $wallet_num;
        $data['from'] = config('serc_from');
        $url = config('serc_gate') . "peer/matrix";
        $header = array(
            "version" => config('serc_version'),
            "magic" => config('serc_magic'),
            "Content-Type" => config('serc_content_type')
        );
        $res = http($url, json_encode($data), $header);
        if ($res['success']) {
            $this->apiReply(1, '转账成功', $res);
        } else {
            $this->apiReply(2, '操作失败', $res);
        }
    }

    /**
     * @param $wallet
     * @return int
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function USDT_trs($wallet)
    {
        $this->USDT_get_trs($wallet); // 更新交易记录

        $trs = Db::name("trx_usdt")->where(['status' => 1, 'to' => $wallet['wallet']])->field('id, txid, amount, block')->select();

        $count = 0;
        foreach ($trs as $k => $tx) {
            try {
                Db::startTrans();

                $wallet['balance'] = Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->lock(true)->value('balance');
                $value = floatval($tx['amount']);
                $balance = bcadd($wallet['balance'], $value, 8);

                // 增加余额
                Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->update(['balance' => $balance]);

                // 更新状态
                Db::name('trx_usdt')->where('txid', $tx['txid'])->update(['user_id' => $this->user_id, 'status' => 2]);

                // 增加日志
                Db::name('log_coin')->insert([
                    'user_id' => $this->user_id,
                    'coin_id' => $wallet['coin_id'],
                    'num' => $value,
                    'amount' => $wallet['balance'],
                    'balance' => $balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trx_usdt',
                    'union_id' => $tx['id'],
                    'remark' => '',
                    'type' => 88
                ]);

                Db::commit();
                $count++;
            } catch (\Exception $e) {
                Db::rollback();
                file_put_contents(ROOT_PATH . 'runtime/withdraw/error.txt', var_export($e, true), FILE_APPEND);
                continue;
            }
        }

        return $count;
    }

    /**
     * @param $wallet
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function USDT_get_trs($wallet)
    {
        $path = ROOT_PATH . 'runtime/trx/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $txids = Db::name("trx_usdt")->where('status', 0)->field('`id`, `txid`')->limit(10)->order('`id` DESC')->select();
        //print_r($txids);
        if (!$txids) return 0;

        $bitcoind = new \Denpa\Bitcoin\Client([
            'scheme' => 'http',                 // optional, default http
            'host' => config('USDT.host'),            // optional, default localhost
            'port' => config('USDT.port'),                   // optional, default 8332
            'user' => config('USDT.user'),              // required
            'pass' => config('USDT.password'),          // required
        ]);

        $count = 0;
        foreach ($txids as $trx) {
            $txid = $trx['txid'];
            //file_put_contents($path . 'trans_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($txid, true) . "\n", FILE_APPEND);

            try {
                $tx = $bitcoind->request('omni_gettransaction', $txid)->get();
                //file_put_contents($path . 'trans_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($tx, true) . "\n", FILE_APPEND);
            } catch (\Exception $e) {
                continue;
            }

            if (!$tx) continue;
            if (!$tx['valid']) continue;
            if (intval($tx['confirmations']) < 3) continue; // 节点未确认

            Db::startTrans();
            try {
                $data = ['from' => $tx['sendingaddress'], 'to' => $tx['referenceaddress'], 'amount' => $tx['amount'], 'fee' => $tx['fee'], 'propid' => $tx['propertyid'], 'block' => $tx['block'], 'blocktime' => $tx['blocktime'], 'confirms' => $tx['confirmations'], 'status' => 1];

                if ($tx['referenceaddress'] == $wallet['wallet']) {
                    $data['user_id'] = $this->user_id;
                    $data['status'] = 2;

                    $wallet['balance'] = Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->lock(true)->value('balance');
                    $value = floatval($tx['amount']);
                    $balance = bcadd($wallet['balance'], $value, 8);
                    // 增加余额
                    Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->update(['balance' => $balance]);

                    Db::name('log_coin')->insert([
                        'user_id' => $this->user_id,
                        'coin_id' => $wallet['coin_id'],
                        'num' => $value,
                        'amount' => $wallet['balance'],
                        'balance' => $balance,
                        'addtime' => time(),
                        'status' => 1,
                        'union' => 'trx_usdt',
                        'union_id' => $trx['id'],
                        'remark' => '',
                        'type' => 88
                    ]);
                }

                Db::name('trx_usdt')->where('txid', $txid)->update($data);

                Db::commit();
                $count++;
            } catch (Exception $e) {
                Db::rollback();
                continue;
            }
        }

        return $count;
    }



    /*//    $wallet = $this->$func($coin['coin_id']);
  //    if ($wallet['code']) {
  //      $this->apiReply(1, '', ['wallet' => $wallet['wallet'], 'coin_id' => $coin_id, 'logo' => $coin['logo'], 'logo_time' => $coin['addtime'], 'name' => $coin['name']]);
  //    } else {
  //      $this->apiReply(2, $wallet['msg'], $wallet['data']);
  //    }*/

    /**
     * @param $coin
     * @return array
     * @throws \Exception
     */
    protected function USDT_address($coin)
    {
        $user_coin = [
            'user_id' => $this->user_id,
            'coin_id' => $coin['coin_id'],
            'balance' => 0
        ];

        $bitcoind = new \Denpa\Bitcoin\Client([
            'scheme' => 'http',                 // optional, default http
            'host' => config('USDT.host'),            // optional, default localhost
            'port' => config('USDT.port'),                   // optional, default 8332
            'user' => config('USDT.user'),              // required
            'pass' => config('USDT.password'),          // required
        ]);
        $wallets = $bitcoind->request('getaddressesbyaccount', [$this->user_id])->get();
        if (count($wallets)) {
            $user_coin['wallet'] = $wallets[0];
        } else {
            $user_coin['wallet'] = $bitcoind->request('getnewaddress', [$this->user_id])->get();
            if (!$user_coin['wallet'])
//        return ['code' => 0, 'msg' => '创建USDT账号失败！|create USDT account fail.', 'data' => []];
                $this->apiReply(2, '创建USDT账号失败！|create USDT account fail.', '');
        }

        Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $coin['coin_id']])->update(['wallet' => $user_coin['wallet']]);

//    return ['code' => 1, 'wallet' => $user_coin['wallet']];
        $this->apiReply(1, '', ['wallet' => $user_coin['wallet'], 'coin_id' => $coin['coin_id'], 'logo' => $coin['logo'], 'logo_time' => $coin['addtime'], 'name' => $coin['name']]);
    }

    /**
     * @param $coin
     * @return array
     */
    protected function ETH_address($coin)
    {

        $user_coin = [
            'user_id' => $this->user_id,
            'coin_id' => $coin['coin_id'],
            'balance' => 0
        ];

        $personal = new Personal('http://' . config('ETH.host') . ':' . config('ETH.port'));
        $personal->newAccount(md5($user_coin['user_id'] . '.' . config('ETH.salt')), function ($e, $data) use ($user_coin, $coin) {
            if ($e) {

                $this->apiReply(2, '创建ETH账号失败！|create ETH account fail.', $e->getMessage());
            }

            $user_coin['wallet'] = $data;
            $user_coin['pwd'] = md5($user_coin['user_id'] . '.' . config('ETH.salt'));

            Db('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $user_coin['coin_id']])->update(['wallet' => $user_coin['wallet'], 'pwd' => $user_coin['pwd']]);

            $this->apiReply(1, '', ['wallet' => $data, 'coin_id' => $coin['coin_id'], 'logo' => $coin['logo'], 'logo_time' => $coin['addtime'], 'name' => $coin['name']]);
        });
    }

    /**
     * @param $wallet
     * @return int|string
     */
    protected function ETH_trs($wallet)
    {
        $startBlock = Db::name('trx_coin')->where(['to' => $wallet['wallet']])->max('block');
        if (!$startBlock) $startBlock = 6100000;

        $trs = http('http://api.etherscan.io/api?module=account&action=txlist&address=' . $wallet['wallet'] . '&startblock=' . $startBlock . '&endblock=99999999&sort=asc&apikey=XY2X8WJW4CEISRCPRII3ETA32RZRKXA5F7');
        if (!$trs['status']) return false;

        $trs = $trs['result'];
        $count = 0;
        foreach ($trs as $k => $v) {
            if ($v['to'] != $wallet['wallet']) continue; // 不是收款，不处理

            $value = floatval($v['value']);
            if (!$value) continue;
            if ($v['confirmations'] < 5) continue;
            $value = $value / pow(10, 18);

            Db::startTrans();
            try {
                $id = Db::name('trx_coin')->insertGetId([
                    'user_id' => $this->user_id,
                    'txid' => $v['hash'],
                    'from' => $v['from'],
                    'to' => $v['to'],
                    'amount' => $value,
                    'coin_id' => $wallet['coin_id'],
                    'addtime' => $v['timeStamp'],
                    'block' => $v['blockNumber'],
                    'confirms' => $v['confirmations'],
                    'status' => 2,
                    'contract' => $v['blockNumber'],

                ]);

                // 增加余额
//        $this->exchApi = new \ExchApi();
//        $name = db('coin')->where('coin_id', $wallet['coin_id'])->value('name');
//        if ($this->exchApi->balance_update($this->user_id, $name, 'recharge', $id, $value)['result']['status'] != 'success') {
//          Db::rollback();
//          $this->apiReply(2, '操作失败，请重试！|Operation failed. Please try again！');
//        }

                $wallet['balance'] = Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->lock(true)->value('balance');
                $balance = bcadd($wallet['balance'], $value, 8);
                // 增加余额
                Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->update(['balance' => $balance]);
                // 日志
                Db::name('log_coin')->insert([
                    'user_id' => $this->user_id,
                    'coin_id' => $wallet['coin_id'],
                    'num' => $value,
                    'amount' => $wallet['balance'],
                    'balance' => $balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trx_coin',
                    'union_id' => $id,
                    'remark' => '',
                    'type' => 88
                ]);

                Db::commit();
                $count++;
            } catch (Exception $e) {
                Db::rollback();
                continue;
            }
        }

        return $count;
    }


    protected function AWT_address($coin_id)
    {
        return $this->ETH_address($coin_id);
    }

    /**
     * @param $wallet
     * @return int|string
     */
    protected function AWT_trs($wallet)
    {
        $startBlock = Db::name('trx_coin')->where(['to' => $wallet['wallet']])->max('block');
        if (!$startBlock) $startBlock = 6100000;

        $contractAddress = '0xe0c3d01744435b87eec98f01d58424b3bafbea22';

        $trs = http('https://api.etherscan.io/api?module=account&action=tokentx&contractaddress=' . $contractAddress . '&address=' . $wallet['wallet'] . '&page=1&offset=100&sort=asc&apikey=XY2X8WJW4CEISRCPRII3ETA32RZRKXA5F7');

        if (!$trs['status']) return false;

        $trs = $trs['result'];
        $count = 0;
        foreach ($trs as $k => $v) {
            if ($v['to'] != $wallet['wallet']) continue; // 不是收款，不处理

            $value = floatval($v['value']);
            if (!$value) continue;
            if ($v['confirmations'] < 5) continue;
            $value = $value / pow(10, 18);

            Db::startTrans();
            try {
                $id = Db::name('trx_coin')->insertGetId([
                    'user_id' => $this->user_id,
                    'txid' => $v['hash'],
                    'from' => $v['from'],
                    'to' => $v['to'],
                    'amount' => $value,
                    'coin_id' => $wallet['coin_id'],
                    'addtime' => $v['timeStamp'],
                    'block' => $v['blockNumber'],
                    'confirms' => $v['confirmations'],
                    'status' => 2,
                    'contract' => $v['blockNumber'],
                ]);
//        // 增加余额
//        $this->exchApi = new \ExchApi();
//        $name = db('coin')->where('coin_id', $wallet['coin_id'])->value('name');
//        if ($this->exchApi->balance_update($this->user_id, $name, 'recharge', $id, $value)['result']['status'] != 'success') {
//          Db::rollback();
//          $this->apiReply(2, '操作失败，请重试！|Operation failed. Please try again！');
//        }

                $wallet['balance'] = Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->lock(true)->value('balance');
                $balance = bcadd($wallet['balance'], $value, 8);
                // 增加余额
                Db::name('user_coin')->where(['user_id' => $this->user_id, 'coin_id' => $wallet['coin_id']])->update(['balance' => $balance]);
                // 日志
                Db::name('log_coin')->insert([
                    'user_id' => $this->user_id,
                    'coin_id' => $wallet['coin_id'],
                    'num' => $value,
                    'amount' => $wallet['balance'],
                    'balance' => $balance,
                    'addtime' => time(),
                    'status' => 1,
                    'union' => 'trx_coin',
                    'union_id' => $id,
                    'remark' => '',
                    'type' => 88
                ]);

                Db::commit();
                $this->up_node($this->user_id);
                $count++;
            } catch (Exception $e) {
                Db::rollback();
                continue;
            }
        }

        return $count;
    }

}
