<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/28
 * Time: 15:20
 */

return array(
  'default_return_type' => 'jsonp',

  'preg_username' => '/^[\x80-\xff\w]{4,20}$/', // 用户名
  'preg_name' => '/^[\x80-\xff\w]{4,20}$/', // 姓名验证
  'preg_phone' => '/^1[123456789][0-9]{9}$/', // 手机号码正则
  'preg_telephone' => '/^(0\d{2,3})?\d{7,8}$/', // 座机正则
  'preg_email' => "/^\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/", // 邮箱正则

  'RESULT_CODE' => array(
    'fail' => array('code' => 0, 'msg' => '操作失败！|Operation failure!'),
    'system_action' => array('code' => 1111, 'msg' => '00:00到00:30为系统拆分时间，请稍后再进行操作！'),
    'success' => array('code' => 1, 'msg' => '操作成功'),
    'error' => ['code' => 2, 'msg' => '操作失败，请刷新后重试！'],
    'forbid' => ['code' => 2, 'msg' => '不允许的操作！'],
    'sys_busy' => array('code' => 3, 'msg' => '系统繁忙，请重试或联系客服处理！'),
    'slowdown' => ['code' => 2, 'msg' => '操作太快啦！'],

    'sms_send_fail' => array('code' => 4, 'msg' => '短信发送失败！'),

    'lawless' => array('code' => 10, 'msg' => '非法请求'),
    'token_error' => array('code' => 11, 'msg' => '登录标志错误，请重新登录！'),
    'token_error_app' => array('code' => 11, 'msg' => '登录超时，请退出app后重新登录！'),
    'login_timeout' => array('code' => 12, 'msg' => '登录超时'),

    'no_data' => array('code' => 20, 'msg' => '暂无数据'),
    'no_more_data' => array('code' => 2, 'msg' => '暂无更多数据'),

    // 手机短信发送验证
    'sms_code_error' => array('code' => 100, 'msg' => '短信验证码错误'),
    'sms_code_expired' => array('code' => 101, 'msg' => '短信验证码超时'),
    'sms_code_wait' => array('code' => 102, 'msg' => '您刚才已经发送过短信了，请耐心等待几秒'),

    'pay_pass_error' => array('code' => 103, 'msg' => '交易密码输入不正确'),

    // 注册验证
    'username_error' => array('code' => 10002, 'msg' => '用户名错误'),
    'username_not_exists' => array('code' => 10002, 'msg' => '用户名不存在！|User name do not exists'),
    'username_used' => array('code' => 10002, 'msg' => '用户名已注册'),
    'name_error' => array('code' => 10002, 'msg' => '姓名格式错误'),
    'wechat_error' => array('code' => 10002, 'msg' => '请输入微信号'),
    'alipay_error' => array('code' => 10002, 'msg' => '请输入支付宝'),
    'password_error' => array('code' => 10003, 'msg' => '密码错误，超过５次账户将被冻结！'),
    'password_diff' => array('code' => 10004, 'msg' => '两次密码输入不一致'),
    'phone_error_reg' => array('code' => 10000, 'msg' => '手机号码格式错误'),
    'phone_error' => array('code' => 10000, 'msg' => '您的账号数据有异常或用户被禁用，请联系客服'),
    'phone_used' => array('code' => 10005, 'msg' => '该手机号已注册'),
    'invite_error' => array('code' => 10006, 'msg' => '邀请码错误！'),
    'code_expired' => array('code' => 10007, 'msg' => '验证码已过期,请重新获取!'),
    'password_error_num' => array('code' => 10003, 'msg' => '密码错误５次，账号被冻结，请联系客服！'),

    'invite_code_error' => array('code' => 10008, 'msg' => '邀请码错误或不存在'),
    'recommend_error' => array('code' => 10009, 'msg' => '推荐人用户错误'),

    //登录验证
    'login_failed' => array('code' => 20000, 'msg' => '密码有误，请重新输入'),
    'login_success' => array('code' => 20001, 'msg' => '登录成功！'),
    'user_not_exist' => array('code' => 20002, 'msg' => '此账号不存在！'),
    'user_not_fill' => array('code' => 20003, 'msg' => '未填写相关信息，请继续填写'),
    'user_error' => array('code' => 20004, 'msg' => '账号输入不正确！'),
    'pwd_error' => array('code' => 20005, 'msg' => '密码错误！'),
    'unactive' => array('code' => 20006, 'msg' => '账号未激活！'),

    'num_error' => array('code' => 1001, 'msg' => '数量错误！'),
    'diamond_less' => array('code' => 1002, 'msg' => '钻石余额不足，请充值！'),
    'gold_less' => array('code' => 1003, 'msg' => '金币余额不足！'),
    'prop_less' => array('code' => 1004, 'msg' => '道具数量不足！'),
    'level_less' => array('code' => 1005, 'msg' => '等级限制，无法操作！'),
    'repeat_buy' => array('code' => 1006, 'msg' => '请勿重复购买！'),

    // shop
    'shop_id_error' => array('code' => 21000, 'msg' => '商品不存在！'),
    'understock' => array('code' => 21000, 'msg' => '库存不足！'),

    'no_egg' => ['code' => 30000, 'msg' => '您没有神秘的蛋，请购买！'],
    'egg_less' => ['code' => 30000, 'msg' => '神秘的蛋数量不足，请购买！'],
    'buy_ok' => ['code' => 30000, 'msg' => '购买成功！'],

    'signed' => array('code' => 70008, 'msg' => '今天已经签过到'),
  ),
);