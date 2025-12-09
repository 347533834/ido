<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/19
 * Time: 12:43
 */

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;


class Upload extends Base
{

    public function _initialize()
    {
        parent::_initialize();
        header("Access-Control-Allow-Origin:*");
    }

    /**
     * 实名信息提交
     * @param $token
     * @return \think\response\Json
     */
    public function idadd($token)
    {
        $idname = replaceSpecialChar(input('post.name'));
        $idcard = replaceSpecialChar(input('post.card'));
        if (empty($idcard)) {
            return json(array("code" => "2", "msg" => '身份证号码不能为空！|Id number cannot be empty!'));
        }

        if (!$this->user['mobile']) {
            return json(array("code" => "2", "msg" => '请先绑定手机号码|Please bind the mobile phone number first'));
        }

        if (empty($idname)) {
            return json(array("code" => "2", "msg" => '姓名不能为空！|The name cannot be empty!'));
        }

        $imgs = json_decode(input('post.imgs'), true);

        if ($this->user['code'] == '86') {
            //处理身份证姓名 以及 身份证号码
            $IdCard = "/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i";
            $IdName = "/^[\x{4e00}-\x{9fa5}\·]{2,20}$/u";

            if (!preg_match($IdCard, $idcard) || !preg_match($IdName, $idname)) {
                return json(array("code" => "2", "msg" => '请填写正确的身份证姓名和账号！|Please fill in the correct id card name and account number!'));
            }
        }

        $user_idaudit = Db('idaudit')->where(['user_id' => $this->user_id])->field('status,front_attach_id,back_attach_id,hand_attach_id')->find();
        if (isset($user_idaudit) && $user_idaudit['status'] >= 0) {
            return json(array("code" => "2", "msg" => '已有审核或待审核信息！|Audited or unaudited information!'));
        } else if ($user_idaudit['status'] == -1) {
            if (count($imgs) < 1) {
                return json(array("code" => "2", "msg" => '请完整上传身份证照片！|Please upload full id photo!'));
            }
        } else {
            if (count($imgs) != 3) {
                return json(array("code" => "2", "msg" => '请完整上传身份证照片！|Please upload full id card photo'));
            }
        }

        // 验证重复绑定
        $id_check = db('idaudit')->where(['id_card' => $idcard, 'user_id' => ['neq', $this->user_id]])->count();
        if ($id_check) {
            return json(array("code" => "2", "msg" => '该身份证号码已被使用！|The id card number has been used'));
        }

        //身份证信息验证
        $is_this = Db('idcard')->where(['idcard' => $idcard, 'idname' => $idname])->count();
        if (!$is_this) { //身份证信息接口验证
            if ($this->user['code'] == '86') {
                // 身份证合法正则筛选
                if (!isCreditNo($idcard)) {
                    return json(array("code" => "2", "msg" => '身份证不合法！|Illegal identity card'));
                }

//                $paramstring = http_build_query(["idcard" => $idcard,//身份证号码
//                    "realname" => $idname,//真实姓名
//                    "key" => '86d6462603f3452d63837569d20a46d1',]);
//                $content = juhecurl("https://op.juhe.cn/idcard/query", $paramstring);
//                $result = json_decode($content, true);
//                if ($result) {
//                    if ($result['error_code'] == '0') {
//                        if ($result['result']['res'] != '1') {
//                            return json(array("code" => "2", "msg" => '身份证号码和真实姓名不一致！|The id card number is inconsistent with the real name'));
//                        }
//                    } else {
//                        return json(array("code" => "2", "msg" => '无此身份证记录！|No record of this id card'));
//                    }
//                } else {
//                    return json(array("code" => "2", "msg" => '网络延时，请重试！|Network delay, please try again!'));
//                }
                //写入身份证审核表
                Db('idcard')->insert(['idcard' => $idcard, 'idname' => $idname]);
            }
        }

        //图片上传
        $img_data = $this->upload($imgs);
        if (!$img_data['code']) {
            return json($img_data);
        }

        //开始处理数据
        Db::startTrans();
        try {

            $id_data['id_name'] = $idname;
            $id_data['id_card'] = $idcard;
            $id_data['addtime'] = time();
            $id_data['auditor'] = '';
            $id_data['audit_time'] = '';
            $id_data['status'] = 0;
            $id_data['remark'] = '';
            if ($user_idaudit['status'] != -1) {
                foreach ($img_data['data'] as $k => $v) {
                    $id = Db('attach')->insertGetId(['url' => $v, 'addtime' => time()]);
                    $id_data[$k . '_attach_id'] = $id;
                }
                $id_data['user_id'] = $this->user_id;
                Db('idaudit')->insert($id_data);
            } else {
                //更新时间
                foreach ($img_data['data'] as $k => $v) {
                    Db('attach')->where('attach_id', $user_idaudit[$k . '_attach_id'])->update(['addtime' => time()]);
                }
                Db('idaudit')->where('user_id', $this->user_id)->update($id_data);
            }

            Db::commit();
            return json(array("code" => "1", "msg" => '上传成功！|Uploaded successfully'));
        } catch (Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            return json(array("code" => "2", "msg" => '上传失败请重试！|Upload failed please try again', 'data' => $e->getMessage()));
        }
    }


    /**
     * 支付宝信息绑定
     * @param $token
     * @param $alipay
     * @return \think\response\Json
     */
    public function alipay($token, $alipay)
    {
        if (empty($alipay)) {
            return json(array("code" => "2", "msg" => '支付宝账户不能为空！'));
        }
        $mail_test = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        $mobile_test = "/^1[34578]\d{9}$/";
        if ($alipay) {
            if (!preg_match($mail_test, $alipay) && !preg_match($mobile_test, $alipay)) {
                return json(array("code" => "2", "msg" => '请填写正确的支付宝账号！'));
            }
        }

        $imgs = json_decode(input('post.imgs'), true);
        if (count($imgs) != 1) {
            $data = ["code" => "2", "msg" => '请选择上传的支付宝收款码！'];
            return json($data);
        }

        $img_data = $this->upload($imgs);
        if (!$img_data['code']) {
            return json($img_data);
        }

        //开始处理数据
        Db::startTrans();
        try {
            $alipay_data['alipay'] = $alipay;
            foreach ($img_data['data'] as $k => $v) {
                $attach_id = Db('users')->where(['user_id' => $this->user_id])->value('alipay_code');
                if (!$attach_id) {
                    $alipay_data['alipay_code'] = Db('attach')->insertGetId(['url' => $v, 'addtime' => time()]);
                    Db('users')->where(['user_id' => $this->user_id])->update($alipay_data);
                } else {
                    Db('attach')->where('attach_id', $attach_id)->update(['addtime' => time()]);
                    Db('users')->where(['user_id' => $this->user_id])->update($alipay_data);
                }
            }

            //日志
            Db::name('log_change_payment')->insert(['user_id' => $this->user_id, 'type' => 419, 'intro' => '修改支付宝', 'old' => $this->user['alipay'], 'new' => $alipay, 'addtime' => time()]);

            Db::commit();

            $this->user['alipay'] = $alipay;
            $this->user['alipay_code'] = $v;
            $this->user['alipay_addtime'] = time();
            cache('cache_userinfo_' . $this->user_id, $this->user);

            return json(array("code" => "1", "msg" => '上传成功！', 'data' => ['alipay' => $alipay, 'alipay_code' => $v, 'alipay_addtime' => time()]));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return json(array("code" => "2", "msg" => '上传失败请重试！', 'data' => $e->getMessage()));
        }
    }

    /**
     * 微信信息绑定
     * @param $token
     * @param $wechat
     * @return \think\response\Json
     */
    public function wechat($token, $wechat)
    {
        if (empty($wechat)) {
            return json(array("code" => "2", "msg" => '微信账户不能为空！'));
        }
        $mobile_test = "/^1[34578]\d{9}$/";
        if ($wechat) {
            if (!preg_match($mobile_test, $wechat)) {
                return json(array("code" => "2", "msg" => '请填写正确的微信手机号！'));
            }
        }

        $imgs = json_decode(input('post.imgs'), true);
        if (count($imgs) != 1) {

        }
//        return json(["code" => "2", "msg" => '请选择上传的微信收款码！','data'=>$imgs]);
        $img_data = $this->upload($imgs);
        if (!$img_data['code']) {
            return json($img_data);
        }

        //开始处理数据
        Db::startTrans();
        try {
            $wechat_data['wechat'] = $wechat;
            foreach ($img_data['data'] as $k => $v) {
                $attach_id = Db('users')->where(['user_id' => $this->user_id])->value('wechat_code');
                if (!$attach_id) {
                    $wechat_data['wechat_code'] = Db('attach')->insertGetId(['url' => $v, 'addtime' => time()]);
                    Db('users')->where(['user_id' => $this->user_id])->update($wechat_data);
                } else {
                    Db('attach')->where('attach_id', $attach_id)->update(['addtime' => time()]);
                    Db('users')->where(['user_id' => $this->user_id])->update($wechat_data);
                }
            }
            //日志
            Db::name('log_change_payment')->insert(['user_id' => $this->user_id, 'type' => 418, 'intro' => '修改微信', 'old' => $this->user['wechat'], 'new' => $wechat, 'addtime' => time()]);

            Db::commit();

            $this->user['wechat'] = $wechat;
            $this->user['wechat_code'] = $v;
            $this->user['wechat_addtime'] = time();
            cache('cache_userinfo_' . $this->user_id, $this->user);

            return json(array("code" => "1", "msg" => '上传成功！', 'data' => ['wechat' => $wechat, 'wechat_code' => $v, 'wechat_addtime' => time()]));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return json(array("code" => "2", "msg" => '上传失败请重试！', 'data' => $e->getMessage()));
        }
    }

    /**
     * 上传支付凭证 (挂买)
     * @param $token
     * @param $log_market_id
     * @param $pay_type
     * @return \think\response\Json
     */
    public function BuyPayment($token, $log_market_id, $pay_type)
    {
        Db::startTrans();
        try {
            $log_market = Db('log_market')->where(['buyer_id' => $this->user_id, 'log_market_id' => $log_market_id, 'status' => 1, 'type' => 1])->lock(true)->field('seller_id,coin_id,num')->find();

            if (!$log_market) {
                Db::rollback();
                return json(array("code" => "2", "msg" => '该订单不存在！|The order does not exist!'));
            }

            $imgs = json_decode(input('post.imgs'), true);
            if (count($imgs) != 1) {
                Db::rollback();
                return json(["code" => "2", "msg" => '请上传支付凭证！|Please upload payment voucher!']);
            }

            $url = 'uploads/log_market/' . $log_market_id . '_' . $this->user_id;//想要保存文件的名称
            $img_data = $this->upload($imgs, $url);
            if (!$img_data['code']) {
                Db::rollback();
                return json($img_data);
            }

            foreach ($img_data['data'] as $k => $v) {
                Db('log_market')->where(['buyer_id' => $this->user_id, 'log_market_id' => $log_market_id, 'status' => 1, 'type' => 1])->update([
                    'pay_img' => $v,
                    'pay_time' => time(),
                    'pay_type' => $pay_type,
                    'status' => 2,
                ]);
            }

            // 发短信提醒卖家
            $seller = db('users')->where(['user_id' => $log_market['seller_id'], 'status' => 1])->field('`code`,`mobile`')->find();
            $send_msg = [];
            if ($seller) {
                $type = 5;
                $send_msg['cn'] = '系统已短信通知卖家，请耐心等待卖家确认收款！';
                $send_msg['en'] = 'The system has sent a message to the seller, please wait for the seller to confirm receipt!';
                if ($seller['code'] == 86) {
                    $result = $this->sendSMS($seller['mobile'], $type, ['msg' => $this->coins[$log_market['coin_id']]['name'], 'time' => $this->config['market_time'] . '分钟']);
                } else {
                    $result = $this->sendSMS_Global($this->user['mobile'], $type, $seller['code'], ['msg' => $this->coins[$log_market['coin_id']]['name'], 'time' => $this->config['market_time'] . '分钟']);
                }

                if ($result !== 'success') {
                    $send_msg['cn'] = '系统短信发送失败，请您主动和卖家联系一下吧！';
                    $send_msg['en'] = 'System SMS sending failed, please take the initiative to contact the seller!';
                }
            }

            Db::commit();
            return json(array("code" => "1", "msg" => '上传成功！' . $send_msg['cn'] . '|UPLOAD COMPLETE!' . $send_msg['en']));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return json(array("code" => "2", "msg" => '上传失败请重试！|Upload failed please try again!', 'data' => $e->getMessage()));
        }
    }

    /**
     * 上传支付凭证 (挂卖)
     * @param $token
     * @param $log_market_id
     * @param $pay_type
     * @return \think\response\Json
     */
    public function SellPayment($token, $log_market_id, $pay_type)
    {
        Db::startTrans();
        try {
            $log_market = Db('log_market')->where(['buyer_id' => $this->user_id, 'log_market_id' => $log_market_id, 'status' => 11, 'type' => 2])->lock(true)->field('seller_id,coin_id,num')->find();

            if (!$log_market) {
                Db::rollback();
                return json(array("code" => "2", "msg" => '该订单不存在！|The order does not exist!'));
            }

            $imgs = json_decode(input('post.imgs'), true);
            if (count($imgs) != 1) {
                Db::rollback();
                return json(["code" => "2", "msg" => '请上传支付凭证！|Please upload payment voucher!']);
            }

            $url = 'uploads/log_market/' . $log_market_id . '_' . $this->user_id;//想要保存文件的名称
            $img_data = $this->upload($imgs, $url);
            if (!$img_data['code']) {
                Db::rollback();
                return json($img_data);
            }

            foreach ($img_data['data'] as $k => $v) {
                Db('log_market')->where(['buyer_id' => $this->user_id, 'log_market_id' => $log_market_id, 'status' => 11, 'type' => 2])->update([
                    'pay_img' => $v,
                    'pay_time' => time(),
                    'pay_type' => $pay_type,
                    'status' => 12,
                ]);
            }

            // 发短信提醒卖家
            $seller = db('users')->where(['user_id' => $log_market['seller_id'], 'status' => 1])->field('`code`,`mobile`')->find();
            $send_msg = [];
            if ($seller) {
                $type = 5;
                $send_msg['cn'] = '系统已短信通知卖家，请耐心等待卖家确认收款！';
                $send_msg['en'] = 'The system has sent a message to the seller, please wait for the seller to confirm receipt!';
                if ($seller['code'] == 86) {
                    $result = $this->sendSMS($seller['mobile'], $type, ['msg' => $this->coins[$log_market['coin_id']]['name'], 'time' => $this->config['market_time'] . '分钟']);
                } else {
                    $result = $this->sendSMS_Global($this->user['mobile'], $type, $seller['code'], ['msg' => $this->coins[$log_market['coin_id']]['name'], 'time' => $this->config['market_time'] . '分钟']);
                }

                if ($result !== 'success') {
                    $send_msg['cn'] = '系统短信发送失败，请您主动和卖家联系一下吧！';
                    $send_msg['en'] = 'System SMS sending failed, please take the initiative to contact the seller!';
                }
            }

            Db::commit();
            return json(array("code" => "1", "msg" => '上传成功！' . $send_msg['cn'] . '|UPLOAD COMPLETE!' . $send_msg['en']));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return json(array("code" => "2", "msg" => '上传失败请重试！|Upload failed please try again!', 'data' => $e->getMessage()));
        }
    }

    public function work($token, $title, $content, $mobile, $validate_code, $session_id)
    {
//    session('session_id',$session_id);
//    var_dump(session('ca_key'));
//    exit;
        $title = replaceSpecialChar($title);
        $content = replaceSpecialChar($content);
        $mobile = replaceSpecialChar($mobile);

        if (empty($title)) {
            return json(array("code" => "2", "msg" => '主题不能为空！|The subject cannot be empty'));
        }
        if (empty($content)) {
            return json(array("code" => "2", "msg" => '描述内容不能为空！|The description cannot be empty'));
        }
        if (empty($mobile)) {
            return json(array("code" => "2", "msg" => '联系电话不能为空！|The contact number cannot be empty'));
        }

        /*   if (!captcha_check($validate_code)) {
               return json(array("code" => "2", "msg" => '图形验证码错误，请重新输入！|Error of graphic verification code, please re input.'));

           }*/

        $imgs = json_decode(input('post.imgs'), true);

        $img_data = $this->upload($imgs);
//var_dump($img_data['data']['work']);die;
        //开始处理数据
        Db::startTrans();
        try {

            if ($img_data['data']['work']) {
                Db('work')->insert(['user_id' => $this->user_id, 'title' => $title, 'content' => $content, 'img' => $img_data['data']['work'], 'mobile' => $mobile, 'addtime' => time(), 'status' => 0, 'status_two' => 0]);

            } else {

                Db('work')->insert(['user_id' => $this->user_id, 'title' => $title, 'content' => $content, 'img' => '', 'mobile' => $mobile, 'addtime' => time(), 'status' => 0, 'status_two' => 0]);
            }

            Db::commit();
            return json(array("code" => "1", "msg" => '反馈成功！|Feedback success'));

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return json(array("code" => "2", "msg" => '反馈失败请重试！|If feedback fails, try again', 'data' => $e->getMessage()));
        }
    }

    /**
     * 用户文件上传到oss
     */
    protected function upload($imgs, $url = '')
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
                    //上传oss
                    require_once APP_PATH . '../extend/aliyun-oss-php-sdk/autoload.php';
                    $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//去阿里云后台获取秘钥
                    $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//去阿里云后台获取秘钥
                    $endpoint = config('OSS_GLOBAL')['endpoint'];//你的阿里云OSS地址
                    $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $bucket = config('OSS_GLOBAL')['bucket'];//oss中的文件上传空间
                    if ($url == '') {
                        $object = 'uploads/' . $this->user_id . '/' . $this->user_id . '_' . $k . ".{$type}";//想要保存文件的名称
                    } else {
                        $object = $url . ".{$type}";//想要保存文件的名称
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

}