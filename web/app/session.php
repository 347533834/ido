<?php
return array(
// +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'session',
        // 驱动方式 支持redis memcache memcached
        'type'           => 'redis',
        //'host'           => '172.31.99.3',//线下
      'host' => '127.0.0.1',
      // 是否自动开启 SESSION
        'auto_start'     => true,
        'select'         => '15',
        'expire' => 86400,
    ],
);