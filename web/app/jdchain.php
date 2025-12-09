<?php
return array(
    'sms_expire_time' => 10 * 60,//短信超时时间

    'temp_lock_mini' => 1,  //临时钱包锁仓最小数量
    'temp_lock_max' => 10000000,//临时钱包锁仓最大数量

    'transfer_mini' => 1, //转账最小数量

    'node_rate' => 0.05,  //节点分红比率

    'active_lock_mini' => 1,//活动钱包转出到锁仓钱包最小数量
    'active_lock_max' => 10000000,//活动钱包转出到锁仓钱包最大数量
    'active_lock_rate' => 3,//活动钱包锁仓倍数

    'active_temp_mini' => 1,//活动钱包转出到临时钱包最小数量
    'active_temp_max' => 10000000,//活动钱包转出到临时钱包最大数量
    'active_temp_fee' => 0.01,//活动钱包转出到临时钱包手续费
    'active_temp_fee_show' => '1%',//活动钱包转出到临时钱包手续费页面显示

    'active_exchange_mini' => 1,//活动钱包兑换通证最小MRC数量
    'active_exchange_max' => 10000000,//活动钱包兑换通证最大MRC数量
    'active_exchange_fee' => 0.1,//活动钱包兑换通证手续费
    'active_exchange_fee_show' => '10%',//活动钱包兑换通证手续费页面显示


);