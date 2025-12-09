<?php
/**
 * Created by PhpStorm.
 * User: feisha
 * Date: 2018/4/21
 * Time: 上午10:46
 */

namespace app\common\controller;

use think\Controller;
use think\Config;
use think\Exception;
use think\Input;
use AliyunSms\SmsApi;
use think\Db;


class Base extends Controller
{

    /**
     * initialize
     */
    protected function _initialize()
    {
        $this->date_time = date("Ym", time());
        $this->date_time = date("Ym", time()) . '_1';
        if (date('d') > 8 && date('d') <= 16) {
            $this->date_time = date("Ym", time()) . '_2';
        } elseif (date('d') > 16 && date('d') <= 24) {
            $this->date_time = date("Ym", time()) . '_3';
        } elseif (date('d') > 24) {
            $this->date_time = date("Ym", time()) . '_4';
        }
    }

    /**
     * 短信类型
     * @var
     */
    protected $msgType;
    /**
     * 短信模板
     * @var
     */
    protected $msgCode;

    protected $msgTemp;

    protected $date_time;

    /**
     * * 阿里短信发送
     * @param $mobile
     * @param $smsCode
     * @param array $data
     * @return \stdClass
     */
    public function alisms($mobile, $smsCode, $data = [])
    {
        $sms = new SmsApi(config('SMS_CONFIG')['AccessKey_ID'], config('SMS_CONFIG')['Access_Key_Secret']);
        $response = $sms->sendSms(
            config('SMS_CONFIG')['sms_sign'], // 短信签名
            $smsCode, // 短信模板编号
            $mobile, // 短信接收者
            $data,// 短信模板中字段的值
            ""   // 流水号,选填
        );
        return $response;
    }

    public function ChuanglanSms($temp, $data = [])
    {
        require_once APP_PATH . '../extend/ChuanglanSmsHelper/ChuanglanSmsApi.php';
        $clapi = new \ChuanglanSmsApi();
        $msg = $temp;
        $params = implode(',', $data);
        $result = $clapi->sendVariableSMS($msg, $params);
        return $result;
    }

    /**
     * *发送验证码  国内
     * @param $mobile
     * @param int $type
     * @param array $param
     * @return string
     */
//    public function sendSMS($mobile, $type = 1, $param = [])
//    {
//
//        if (!is_mobile($mobile)) { // 验证手机号
//            return 'phone_error';
//        }
//
//        $data = session('sms_' . $this->msgType[$type] . '_' . $mobile);
//        if ($data) {
//            $data = json_decode($data, true);
//            // 如果已发送过，验证发送的间隔时间
//            if ($data['time'] + config('sms_expire_time') > time() && in_array($type, [1, 2, 3, 6, 7, 8])) {
//                return 'exist'; // sms_code_wait
//            }
//        }
//
//        if (TEST_MODE) {
//            $val = json_encode(array('code' => '1122', 'time' => time()));
//            session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);
//            return 'success';
//        }
//
//        $code = rand(100000, 999999); // 默认短信验证码
//        if (empty($param)) {
//            $param = ['code' => (string)$code];
//        }
//
//        $res = $this->alisms($mobile, $this->msgCode[$type], $param);
//        //var_dump($res);exit;
//        if ($res->Code == 'OK') {
//            $val = json_encode(array('code' => $code, 'time' => time()));
//            session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);
//            return 'success';
//        }
//
//        return 'sms_send_fail';
//    }

    /**
     * *发送验证码  国内
     * @param $mobile
     * @param int $type
     * @param array $param
     * @return string
     */
    public function sendSMS($mobile, $type = 1, $param = [])
    {

        if (!is_mobile($mobile)) { // 验证手机号
            return 'phone_error';
        }

        $data = session('sms_' . $this->msgType[$type] . '_' . $mobile);
        if ($data) {
            $data = json_decode($data, true);
            // 如果已发送过，验证发送的间隔时间
            if ($data['time'] + config('sms_expire_time') > time() && in_array($type, [1, 2, 3, 6, 7, 8])) {
                return 'exist'; // sms_code_wait
            }
        }

        if (TEST_MODE) {
            $val = json_encode(array('code' => '1122', 'time' => time()));
            session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);
            return 'success';
        }

        $code = rand(100000, 999999); // 默认短信验证码
        $_param['mobile'] = $mobile;

        if (in_array($type, [1, 2, 8, 9])) {
            $_param['code'] = $code;
        }
        if (in_array($type, [4, 5])) {
            $_param = array_merge($_param, $param);
        }

        $result = $this->ChuanglanSms($this->msgTemp[$type], $_param);
        if (!is_null(json_decode($result))) {
            $output = json_decode($result, true);
            if (isset($output['code']) && $output['code'] == '0') {
                $val = json_encode(array('code' => $code, 'time' => time()));
                session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);
                return 'success';
            } else {
//                echo $output['errorMsg'];
            }
        } else {
//            echo $result;
        }


        //        写错误日志
//        file_put_contents('./sms_error.txt', print_r('时间：' . date('Y-m-d H:i:s'), true) . PHP_EOL, FILE_APPEND);
//        file_put_contents('./sms_error.txt', print_r(json_encode($res), true) . PHP_EOL, FILE_APPEND);

        return 'sms_send_fail';
    }

    /**
     * 发送短信验证码   国外
     * @param $mobile
     * @param int $type
     * @param string $phone_code
     * @return string
     */
    public function sendSMS_Global($mobile, $type = 1, $phone_code = '86', $content = '')
    {

        if (!$mobile) { // 验证手机号
            return 'phone_error';
        }

        $data = session('sms_' . $this->msgType[$type] . '_' . $mobile);
        if ($data) {
            //如果已发送过，验证发送的间隔时间
            $data = json_decode($data, true);
            if ($data['time'] + config('sms_expire_time') > time()) {
                return 'exist'; // sms_code_wait
            }
        }

        if (TEST_MODE) {
            $val = json_encode(array('code' => '1122', 'time' => time()));
            session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);
            return 'success';
        }

        $smsGlobal = config('SMS_GLOBAL');

        $code = rand(1000, 9999); // 默认短信验证码
        if ($content == '') {
            $content = "您的验证码是{$code}。请在页面中提交验证码完成验证。";
        } else {
            $content = $content[0] . $code . $content[1];
        }

        // 要post的数据
        $argv = array(
            'sn' => $smsGlobal['sms_serial'], // 替换成您自己的序列号
            'pwd' => strtoupper(md5($smsGlobal['sms_serial'] . $smsGlobal['sms_pwd'])), //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
            'mobile' => '00' . $phone_code . $mobile,//手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
            'content' => $smsGlobal['sms_sign'] . $content,//短信内容
            'ext' => '',
            'rrid' => '',// 默认空 如果空返回系统生成的标识串 如果传值保证值唯一 成功则返回传入的值
            'stime' => ''// 定时时间 格式为2011-6-29 11:09:21
        );

        // 构造要post的字符串
        $params = '';
        foreach ($argv as $key => $value) {
            $params .= ($params == '' ? '' : '&') . $key . "=" . urlencode($value);
        }

        $url = $smsGlobal['sms_url'] . $params;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
//        var_dump($output);
        $output_array = (array)simplexml_load_string($output, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($output_array[0] < 1) {
            return 'sys_busy';
        } else {
            $val = json_encode(array('code' => $code, 'time' => time()));
            session('sms_' . $this->msgType[$type] . '_' . $mobile, $val);

            return 'success';
        }
    }

    /**
     *验证码验证
     */
    public function validateSMS($mobile, $type = 1, $code, $phone_code = '86')
    {
        if ($phone_code == '86') {
            if (!is_mobile($mobile)) {
                return 'phone_error';
            }
        }

        $data = session('sms_' . $this->msgType[$type] . '_' . $mobile);
        if (!$data) {
            return 'sms_code_expired';
        }

        $data = json_decode($data, true);
        if ($data['code'] != $code) {
            return 'sms_code_error';
        }

        session('sms_' . $this->msgType[$type] . '_' . $mobile, null); // 验证通过即过期
        return 'success';
    }

    /**
     * 通过配置输出数组键输出结果
     * @param null $data
     * @param string $key
     */
    protected function apiResp($data = null, $key = 'success')
    {
        if (Config::has('RESULT_CODE.' . $key)) {
            $arr = Config::get('RESULT_CODE.' . $key);

            $this->result($data, $arr['code'], $arr['msg']);
        }
        $this->result($data, 0, '操作失敗！');
    }

    /**
     * 直接输出错误代码和信息
     * @param int $code
     * @param string $msg
     * @param array $data
     */
    protected function apiReply($code = 0, $msg = '操作失败！', $data = [])
    {
        $this->result($data, $code, $msg);
    }

    /**
     * * 获取接口的签名
     * @param $para_temp 数组
     * @return string 签名验证结果
     */
    function getSignVeryfy($para_temp)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkString($para_sort);

        $prestr .= '|' . Config('API_SAFE_KEY');

        $signature = strtoupper(md5($prestr));

        return $signature;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para)
    {
        $para_filter = array();
        /*while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type") continue;
            else    $para_filter[$key] = $para[$key];
        }*/
        foreach ($para as $key => $val) {
            if ($key == "sign" || $key == "sign_type") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    function createLinkString($para)
    {
        $arg = [];
        /*while (list ($key, $val) = each($para)) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $arg .= $key . "[{$k}]=" . $v . "&";
                }
            } else {
                $arg .= $key . "=" . $val . "&";
            }
        }*/
        foreach ($para as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $arg[] = $key . "[{$k}]=" . $v;
                }
            } else {
                $arg[] = $key . "=" . $val;
            }
        }
        //去掉最后一个&字符
//        $arg = substr($arg, 0, count($arg) - 2);
        $arg = implode('&', $arg);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return mixed 排序后的数组
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    function httpPost($url, $post)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        //post提交方式
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    /**
     * 写能源日志方法
     * @param $user_id
     * @param $change  当前能源值
     * @param $type
     * @param $msg
     * @param $status
     * @param int $force 实时能源值，改变之后
     * @param int $operation_change 实际改变值，绝对值
     */
    public function force_log($user_id, $change, $type, $msg, $status, $force = 0, $operation_change = 0)
    {
        Db('log_force_' . $this->date_time)->insert([
            'user_id' => $user_id,
            'num' => $change,
            'status' => $status,
            'remark' => $msg,
            'type' => $type,
            'force' => $force,
            'operation_change' => $operation_change,
            'addtime' => time(),
        ]);
    }

    /**
     * @param $validate //获得验证码二次校验数据
     * @param null $user_id
     * @return bool
     */
    function slider_validate($validate, $user_id = null)
    {

        require_once APP_PATH . '../extend/Captcha/NECaptchaVerifier.class.php';
        require_once APP_PATH . '../extend/Captcha/SecretPair.class.php';

        define("YIDUN_CAPTCHA_ID", "6e327266e7014cd095dca58d6a2c186f"); // 验证码id
//        define("YIDUN_CAPTCHA_ID", "017d6b643630458fb659bfd2b7c25a0f"); // 验证码id
        define("YIDUN_CAPTCHA_SECRET_ID", "eab823aaf832184e88b176e00c8c897a");   // 验证码密钥对id
        define("YIDUN_CAPTCHA_SECRET_KEY", "f8f8d1d5a5baa379decb79733b000847"); // 验证码密钥对key

        session_start();
        $verifier = new \NECaptchaVerifier(YIDUN_CAPTCHA_ID, new \SecretPair(YIDUN_CAPTCHA_SECRET_ID, YIDUN_CAPTCHA_SECRET_KEY));

        if (get_magic_quotes_gpc()) {// PHP 5.4之前默认会将参数值里的 \ 转义成 \\，这里做一下反转义
            $validate = stripcslashes($validate);
        }
        // $user_id当前用户信息，值可为空
        $result = $verifier->verify($validate, $user_id);
        if ($result) {
            return true;
        } else {
            $this->apiReply(2, '验证错误，请重试！');
        }
    }

    /**
     * 日志
     * @param $file
     * @param $e
     */
    protected function eth_log($e, $file = 'error')
    {
        $path = ROOT_PATH . 'runtime/withdraw/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . $file . '_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
    }

    private $eth;
    private $personal;
    private $contractObj;

    /**
     * @param $to
     * @param $value
     * @return null
     */
    function eth_withdraw($to, $value)
    {
        $this->eth_log('transfer ' . $value, 'eth_balance');
        $value = gmp_mul('' . floatval($value) * 100000000, '10000000000');
        $this->eth_log('transfer ' . $value . ' ETH to ' . $to, 'eth_balance');

        $web3 = new Web3('http://' . config('ETH.host') . ':' . config('ETH.port'));
        $this->eth = $web3->getEth();
        $this->personal = $web3->getPersonal();

        $account = config('ETH.root');

        $this->personal->unlockAccount($account, config('ETH.passwd')
            , function ($e, $data) {
                if ($e !== null) $this->eth_log('unlock root account fail: ' . $e->getMessage());

                $this->eth_log('unlock root account. ', 'eth_balance');
            });

        $this->eth->getBalance($account
            , function ($e, $data) {
                if ($e !== null)
                    $this->eth_log('Get ether balance fail: ' . $e->getMessage());

                $this->eth_log('eth: ' . $data->value, 'eth_balance');
            });

        $txid = null;
        $this->eth->sendTransaction([
            'from' => $account,
            'to' => $to,
            'value' => '0x' . base_convert($value, 10, 16),
            'gas' => '0x' . base_convert(100000, 10, 16),
//      'data' => $web3->toHex('eth withdraw')
        ], function ($e, $data) use (&$txid) {
            if ($e !== null)
                $this->eth_log('Send Transaction fail: ' . $e->getMessage());

            $this->eth_log('send success: ' . $data, 'eth_balance');
            $txid = $data;
        });

        return $txid;
    }

    public function up_node($user_id)
    {

        $user_account = Db::connect(config('cli_db'))->name('user_account')->alias('ua')->join('user_coin cc', 'ua.user_id=cc.user_id and cc.coin_id=1')->where(['ua.user_id' => $user_id])->field('ua.savings,ua.lock,ua.crowd,cc.balance')->find();

        $num = $user_account['savings'] + $user_account['lock'] + $user_account['crowd'] + $user_account['balance'];
        if ($num > 0) {
            $user_rela = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $user_id])->field('user_id,pid,lft,rgt,depth,node_level_id')->find();

            $node_levels = Db::connect(config('cli_db'))->name('node_level')->where(['status' => 1])->field('node_level_id,min,rate,name')->select();

            $node_level_num = [];
            $node_level_ids = [];
            foreach ($node_levels as $node_level) {
                $node_level_num[] = $node_level['min'];
                $node_level_ids[$node_level['node_level_id']] = $node_level;
            }
            $node_level_num[count($node_level_num) + 1] = $num;
            sort($node_level_num);
            $key = array_search($num, $node_level_num);

            if ($key != $user_rela['node_level_id']) {
                $direct_num = 0;
                if ($key > 2) {
                    do {

                        $key--;
                        $direct_num = Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user_id, 'node_level_id' => ['egt', $key - 1], 'vip_node' => 0])->count();
                        $vip_node_num = Db::connect(config('cli_db'))->name('user_rela')->where(['pid' => $user_id, 'vip_node' => 1])->count();
                        $direct_num += $vip_node_num;

                    } while ($direct_num < 2 && $key > 2);
                }
                if ($key != $user_rela['node_level_id']) {
                    Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $user_id])->update(['node_level_id' => $key]);
                    Db::connect(config('cli_db'))->name('log_node')->insert([
                        'user_id' => $user_id,
                        'node_level_id' => $key,
                        'node_before' => $user_rela['node_level_id'],
                        'num' => $num,
                        'active' => $direct_num,
                        'active_lable' => $node_level_ids[$key - 1]['name'],
                        'addtime' => time(),
                        'remark' => '会员节点更新',
                    ]);
                    $parents = Db::connect(config('cli_db'))->name('user_rela')->where(['lft' => ['lt', $user_rela['lft']], 'rgt' => ['gt', $user_rela['rgt']], 'depth' => ['lt', $user_rela['depth']]])->field('user_id')->order('user_id desc')->select();
                    foreach ($parents as $parent) {
                        $this->up_node($parent['user_id']);
                    }
                }
            }
        }

    }


    /**
     * @param $id
     * @param $num
     * @param $wallet
     * @param $contract
     */
    public function USDT_out($num, $wallet)
    {
        $bitcoind = new \Denpa\Bitcoin\Client([
            'scheme' => 'http',
            'host' => config('USDT.host'),
            'port' => config('USDT.port'),
            'user' => config('USDT.user'),
            'pass' => config('USDT.password'),
            // required
        ]);
        //$btc = $bitcoind->request('getbalance', [config('USDT.account')])->get();
        //print_r($btc);die;

//        $unspents = $bitcoind->request('listunspent')->get();
//        $feeaddress = '';
//        foreach ($unspents as $unspent) {
//            if ($unspent['amount'] < (0.0002)) continue;
//            $feeaddress = $unspent['address'];
//            $amount = $unspent['amount'];
//            break;
//        }
//        print_r($feeaddress);
//        print_r($amount);
//        $hash = $bitcoind->request('sendfrom', [$feeaddress, '1PFA3jfvnpfzfwbpfKvmEpDLhoZRgP7BZ7', '0.002'])->get();
//        print_r($hash);
//        die;

        $redis = \RedisHelper::instance();
        $btc_balance = $redis->hGetAll('btc_balance');

        //print_r($btc_balance);

//        if (!$btc_balance || $btc_balance['time'] < time() - 600) {
//            // 检测手续费 BTC 余额
//            $btc = $bitcoind->request('getbalance', [config('USDT.account')])->get();
//            $redis->hMset('btc_balance', ['time' => time(), 'num' => $btc]);
//            if (!$btc || bccomp($btc, 0.0002, 8) == -1) {
//                $this->eth_log('`USDT` transfer fee `BTC` insufficient.', 'out');
//                return ['code' => 0, 'msg' => 'BTC手续费不足'];
//            }
//        } else {
//            if (!$btc_balance['num'] || bccomp($btc_balance['num'], 0.0002, 8) == -1) {
//                $this->eth_log('`USDT` transfer fee `BTC` insufficient.', 'out');
//                return ['code' => 0, 'msg' => 'BTC手续费不足'];
//            }
//        }

        $usdt = $bitcoind->request('omni_getbalance', [config('USDT.root'), 31])['balance'];

        if (!$usdt || $usdt < $num) {
            $this->eth_log('`USDT` balance insufficient.', 'out');
            return ['code' => 0, 'msg' => 'USDT余额不足'];
        }

        $hash = $bitcoind->request('omni_send', [config('USDT.root'), $wallet, 31, strval($num)])->get();

        if (!$hash) {
            $this->eth_log('`USDT` transfer fail.', 'out');
            return ['code' => 0, 'msg' => '转账失败'];
        }
        $redis->hMset('btc_balance', ['time' => $btc_balance['time'], 'num' => bcsub($btc_balance['num'], 0.0002, 8)]);
        return ['code' => 1, 'data' => $hash];

    }

//    public function USDT_out($num, $wallet)
//    {
//        try {
//            $bitcoind = new \Denpa\Bitcoin\Client([
//                'scheme' => 'http',
//                'host' => config('USDT.host'),
//                'port' => config('USDT.port'),
//                'user' => config('USDT.user'),
//                'pass' => config('USDT.password'),
//                // required
//            ]);
//
//            $usdt = $bitcoind->request('omni_getbalance', [
//                config('USDT.root'),
//                31
//            ]);
//
//            if (!$usdt || $usdt < $num) {
//                $this->eth_log('`USDT` balance insufficient.', 'out');
//                return ['code' => 0, 'msg' => 'USDT余额不足'];
//            }
//
//            $unspents = $bitcoind->request('listunspent')->get();
//            $feeaddress = '';
//            foreach ($unspents as $unspent) {
//                if ($unspent['amount'] < (0.0002)) continue;
//                $feeaddress = $unspent['address'];
//                break;
//            }
//
//            if ($feeaddress) {
//                print_r($feeaddress . "\n");
//
//                $hash = $bitcoind->request('omni_funded_send', [config('USDT.root'), $wallet, 31, strval($num), $feeaddress])->get();
//                print_r($hash . "\n");
//
//                if (!$hash) {
//                    $this->eth_log('`USDT` transfer fail.', 'out');
//                    return ['code' => 0, 'msg' => '转账失败'];
//                }
//                return ['code' => 1, 'data' => $hash];
//            } else {
//                $this->eth_log('`USDT` transfer fee `BTC` insufficient.', 'out');
//                return ['code' => 0, 'msg' => 'BTC手续费不足'];
//            }
//        } catch (Exception $e) {
//            Db::rollback();
//            return ['code' => 0, 'msg' => '审核失败！', 'err_msg' => $e->getMessage()];
//        }
//    }

}