<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    'SMS_TYPE' => array(
        1 => 'register', // 会员注册
        2 => 'reset_pwd', // 忘记密码
        4 => 'notice_buy', // 求购  卖家出售订单后发送信息给求购者付款
        5 => 'notice_sell', // 求购  买家付款后发送信息给出售者确认
        8 => 'set_market_ps',//设置交易密码
        9 => 'coin_draw',//提现
    ),
    'SMS_CODE' => [
        1 => 'SMS_161570196', // 注册验证码
        2 => 'SMS_161570200', // 忘记密码
        4 => 'reset_pwd', // 求购  卖家出售订单后发送信息给求购者付款
        5 => 'reset_pwd', // 求购  买家付款后发送信息给出售者确认
        8 => 'SMS_161570212', // 设置交易密码
        9 => 'SMS_142151711',//提现
    ],
    'SMS_TEMP' => [
        1 => '验证码{$var}，您正在注册成为ChangeEX会员，请在页面提交验证码进行验证，此验证码10分钟内有效！',   // 会员注册
        2 => '验证码{$var}，您正在找回ChangeEX会员密码，请在页面提交验证码进行验证，此验证码10分钟内有效！',  // 忘记密码
        4 => '您好，您求购的{$var}有人出售了，请在{$var}内登录ChangeEX确认订单，感谢您的支持。', // 求购  卖家出售订单后发送信息给求购者付款
        5 => '您好，您出售的{$var}买家已经付款，请在{$var}内登录ChangeEX确认订单，感谢您的支持。', // 求购  买家付款后发送信息给出售者确认
        8 => '验证码{$var}，您正在设置ChangeEX会员交易密码，请在页面提交验证码进行验证，此验证码10分钟内有效！',//设置交易密码
        9 => '您正在ChangeEX进行提现操作，密码为{$var}，请在页面上提交密码，此密码在10分钟内有效。',   //提现
    ],
    'SMS_CONFIG' => [
        'AccessKey_ID' => 'LTAImHZ3A0Sv6DDO',
        'Access_Key_Secret' => 'j3k13dYBWjTv1mEk5eoMq6byIKaYN1',
        'sms_sign' => 'ChangeEX',
    ],
    'SMS_GLOBAL' => [
        'sms_url' => 'http://sdk2.entinfo.cn:8060/gjWebService.asmx/mdSmsSend_g?',
        'sms_sign' => 'ChangeEX',
        'sms_serial' => 'SDK-WSS-010-10606',
        'sms_pwd' => 'aC57e-a8af-'
    ],

];