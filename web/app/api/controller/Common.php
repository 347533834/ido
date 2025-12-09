<?php

namespace app\api\controller;

use app\common\controller\Base;
use think\Input;
use think\Db;

class Common extends Base
{
    // Request实例
    protected $lang;

    public $redis;
    public $coins;
    public $config;

    protected function stop_cc()
    {
        empty($_SERVER['HTTP_VIA']) or exit('Access Denied');  // 代理IP直接退出

        $seconds = 3; // 时间间隔
        $refresh = 5; // 防止快速刷新 刷新次数 设置监控变量
        $cur_time = time();

        $key = $this->request->module() . '_' . $this->request->controller() . '_' . $this->request->action() . '_';
        $key_last_time = $key . 'last_time';
        $key_refresh = $key . 'refresh_times';

        $last_time = session($key_last_time);
        $times = session($key_refresh);
        if (isset($last_time)) {
            $times++;
            session($key_refresh, $times);
        } else {
            $times = 1;
            session($key_refresh, 1);
            $last_time = $cur_time;
            session($key_last_time, $last_time);
        }

        // 处理监控结果
        if ($cur_time - $last_time < $seconds) {
            if ($times >= $refresh) {
                // 跳转至攻击者服务器地址
                header(sprintf('Location:%s', 'http://127.0.0.1'));
                exit('Access Denied');
            }
        } else {
            session($key_refresh, 0);
            session($key_last_time, $cur_time);
        }
    }

    protected function _initialize()
    {
        header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");

        parent::_initialize();

        if (!defined('MODULE_NAME')) {
            define('MODULE_NAME', $this->request->module());
        }
        if (!defined('CONTROLLER_NAME')) {
            define('CONTROLLER_NAME', $this->request->controller());
        }
        if (!defined('ACTION_NAME')) {
            define('ACTION_NAME', $this->request->action());
        }

        if (!defined('__ROOT__')) {
            $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
            define('__ROOT__', (('/' == $_root || '\\' == $_root) ? '' : $_root));
        }

        $this->stop_cc();

        $token = input('token');
        $arr = explode('+', authcode($token, 'DECODE'));

//    $this->apiReply(110, '系统维护中...');
//    if (isset($arr[0]) && !gt_data($arr[0])) {
//        $this->apiReply(110, '系统升级中...');
//    }

        $this->msgType = config('SMS_TYPE');
        $this->msgCode = config('SMS_CODE');
        $this->msgTemp = config('SMS_TEMP');

        $this->redis = \RedisHelper::instance();
        $this->init();
        $this->get_coins();
        $this->config = $this->redis->hGetAll('config');
    }


    //空操作
    public function _empty()
    {
        $this->error(lang('operation not valid'));
    }


    /**
     * 日志
     * @param $file
     * @param $e
     */
    protected function log($file, $e)
    {
        $path = ROOT_PATH . 'runtime/api/' . $file . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . $file . '_' . date('Ymd') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
    }

    /**
     * 加载用户信息
     * @param $user_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    protected function loadUser($user_id)
    {
        $user = Db::name('users')
            ->where(['user_id' => $user_id, 'status' => 1])
            ->field('user_id, username, code,wechat,wechat_code,alipay,alipay_code, mobile, avatar, invite, pid, addtime, update, status,number,user_level')
            ->find();

        //查询图片
        if($user['alipay_code']){
            $alipay_code=Db('attach')->where('attach_id',$user['alipay_code'])->field('url,addtime')->find();
            $user['alipay_code'] = $alipay_code['url'];
            $user['alipay_addtime'] = $alipay_code['addtime'];
        }
        if($user['wechat_code']){
            $wechat_code=Db('attach')->where('attach_id',$user['wechat_code'])->field('url,addtime')->find();
            $user['wechat_code'] = $wechat_code['url'];
            $user['wechat_addtime'] = $wechat_code['addtime'];
        }
        if (!$user) $this->apiResp(null, 'token_error');//这里判断用户是否被冻结

        $user['session_id'] = md5(md5(session_id()));

        return $user;
    }

    function is_weixin()
    {

        if (strpos($_SERVER['HTTP_USER_AGENT'],

                'MicroMessenger') !== false) {

            return true;

        }
        return false;

    }


    /**
     * 登录密码，交易密码，身份认证 错误5次后冻结3小时 方法
     * @param $status
     * @param $mobile
     * @param $desc
     * @param int $user_id
     * @return string
     */
    protected function login_log($status, $mobile, $desc, $user_id = 0)
    {
        $status = intval($status);
        $ip = long2ip(request()->ip(1));
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip=" . $ip;
        $arr = json_decode(@file_get_contents($url));

        if ((string)$arr->code == '1') {
            $address = '';
        } else {
            $data = (array)$arr->data;
            if ($data['county'] = 'XX') {
                $data['county'] = '';
            }
            $address = $data['region'] . $data['city'] . $data['county'];
        }

        if ($status == 0 || $status == -5) {
            if ($status == -5) {
                $text = '交易';
            } else {
                if (CONTROLLER_NAME == 'Login') {
                    $text = '登录';
                } else {
                    $text = '原始';
                }
            }
            //查询错误次数
            $num = Db('log_login')->where(['user_id' => $user_id, 'status' => $status, 'FROM_UNIXTIME(addtime,"%Y%m%d")' => date('Ymd')])->count();
            if ($num < 4) {
                $desc = $text . '密码错误' . ($num + 1) . '次，错误5次账户将被冻结！';
                if ($status == -5) {
                    $text = '交易密码错误' . ($num + 1) . '次，错误5次账户将被冻结！|Trading password error ' . ($num + 1) . ' time, error 5 times account will be frozen！';
                } elseif ($status == 0) {
                    $text = '登陆密码错误' . ($num + 1) . '次，错误5次账户将被冻结！|Login password error ' . ($num + 1) . ' time, error 5 times account will be frozen！';
                }
                Db('log_login')->insert(array('username' => $mobile, 'user_id' => $user_id, 'addtime' => time(), 'ip' => request()->ip(1), 'status' => $status, 'desc' => $desc, 'address' => $address));
            } else {
                $desc = $text . '密码错误5次，账号被冻结3小时！';
                $text = $text . '密码错误5次，账号被冻结3小时！|Error 5 times, account frozen for 3 hours！';
                Db::startTrans();
                try {
                    // 冻结账号 清空次数
                    Db('log_login')->insert(array('username' => $mobile, 'user_id' => $user_id, 'addtime' => time(), 'ip' => request()->ip(1), 'status' => $status ? 3 : 2, 'desc' => $desc, 'address' => $address));
                    Db('log_login')->where(['user_id' => $user_id, 'status' => $status])->setField('status', $status ? 3 : 2);
                    Db('log_freeze')->insert(array('username' => $mobile, 'addtime' => time(), 'ip' => request()->ip(1), 'status' => 0, 'desc' => $desc));
                    Db::commit();

                    $this->redis->zAdd('user_lock', time() + 3600 * 3, $user_id . ':' . $mobile);
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                    Db::rollback();
                }
            }
            //$desc = $text;
            return $desc;
        } else {
            Db('log_login')->insert(array('username' => $mobile, 'user_id' => $user_id, 'addtime' => time(), 'ip' => request()->ip(1), 'status' => $status, 'desc' => $desc, 'address' => $address));
        }
    }


    /**
     * 用户文件上传到oss
     */
    protected function upload($imgs, $avatar_type = 0)
    {
        try {
            foreach ($imgs as $k => $v) {
                //正则匹配
                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $v, $result)) {
                    //生成本地文件
                    $type = $result[2];
                    $new_file = "./uploads/";
                    $new_file .= $this->user_id . '_' . $k . ".{$type}";
                    file_put_contents($new_file, base64_decode(str_replace($result[1], '', $v)));
                    //GD库处理 图片 截取图片得正方形图案
                    $new_file = $this->img_to_square($new_file);
                    //上传oss
                    require_once APP_PATH . '../extend/aliyun-oss-php-sdk/autoload.php';
                    $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//去阿里云后台获取秘钥
                    $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//去阿里云后台获取秘钥
                    $endpoint = config('OSS_GLOBAL')['endpoint'];//你的阿里云OSS地址
                    $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $bucket = config('OSS_GLOBAL')['bucket'];//oss中的文件上传空间
                    $object = 'uploads/idcard/' . $this->user_id . '/' . $this->user_id . '_' . $k . ".{$type}";//想要保存文件的名称
                    if ($avatar_type == 1) {
                        $object = 'assets/img/avatar/user/' . $this->user_id . '_' . $avatar_type . ".{$type}";//想要保存文件的名称
                    } else if ($avatar_type == 2) {
                        $object = 'assets/img/avatar/group/' . $this->user_id . '_' . $avatar_type . ".{$type}";//想要保存文件的名称
                    } else if ($avatar_type == 3) {
                        $object = 'assets/img/avatar/community/' . $this->user_id . '_' . $avatar_type . ".{$type}";//想要保存文件的名称
                    }
                    //添加图片路径数组
                    $file = $new_file;//文件路径，必须是本地的。
                    $ossClient->uploadFile($bucket, $object, $file);
                    unlink($new_file);
                    $img_data[$k] = $object;
                }
            }
            return ["code" => "1", "msg" => '上传成功！', 'data' => $img_data];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ["code" => "2", "msg" => 'oss上传失败请重试！', 'data' => $e->getMessage()];
        }
    }

    protected function img_to_square($url)
    {
        ob_end_clean();
        $imgstream = file_get_contents($url);
        $im = imagecreatefromstring($imgstream);
        $x = imagesx($im);//获取图片的宽
        $y = imagesy($im);//获取图片的高

        $length = $x > $y ? $y : $x;

        if ($x > $y) {
            //图片宽大于高
            $sx = abs(($y - $x) / 2);
            $sy = 0;
            $thumbw = $y;
            $thumbh = $y;
        } else {
            //图片高大于等于宽
            $sy = abs(($x - $y) / 2);
            $sx = 0;
            $thumbw = $x;
            $thumbh = $x;
        }
        if (function_exists("imagecreatetruecolor")) {
            $dim = imagecreatetruecolor($length, $length); // 创建目标图gd2
        } else {
            $dim = imagecreate($length, $length); // 创建目标图gd1
        }
        imageCopyreSampled($dim, $im, 0, 0, $sx, $sy, $length, $length, $thumbw, $thumbh);
        header("Content-type: image/png");
        imagepng($dim, $url);
        imagedestroy($dim);
        imagedestroy($im);
        return $url;
    }

    /**
     * 读取 coins 缓存方法
     */
    private function get_coins()
    {
        foreach ($this->redis->zRange('coin_list', false) as $k => $v) {
            $this->coins[$v] = $this->redis->hGetAll('coin:' . $v);
        }
    }

    /**
     * 总体 缓存方法
     */
    private function init()
    {
        if ($this->redis->get('init')) return;

        $list = Db('coin')->field('`coin_id`, `name`, `short`, `logo`, `intro`, `status`, `addtime`, `is_recharge`,`is_draw`, `price`, `sort`, `explorer_url`, `draw_max_day`, `draw_min_times`, `draw_min_fee`, `is_exchange`, `is_c2c`, `price_cny`, `draw_rate`')->where(['status' => 1])->select();

        foreach ($list as $item) {
            $this->redis->zAdd('coin_list', $item['coin_id'], $item['name']);
            $this->redis->hMset('coin:' . $item['coin_id'], $item);

        }
        $list = Db('config')->column('name, value');
        $this->redis->hMset('config', $list);

        $this->redis->set('init', '1');
    }

}