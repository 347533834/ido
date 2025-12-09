<?php
return [
// 视图输出字符串内容替换
  'view_replace_str' => [
    '__PUBLIC__' => __PUBLIC__,//public目录的全局变量，在/public/home.php中定义
    '__STATIC__' => __PUBLIC__ . '/static',
    '__ADMIN__' => __PUBLIC__ . '/static/admin',
    '__HOME__' => __PUBLIC__ . '/static/home',
    '__OSS_URl__' => 'http://trade-changex-oss.oss-cn-shanghai.aliyuncs.com/',
  ],
];