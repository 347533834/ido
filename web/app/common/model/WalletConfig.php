<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 17:44
 */

namespace app\common\model;

use \think\Model;

class WalletConfig extends Model
{

    protected $key = [
        'total_coin' => 1,
        'use_coin' => 2,
        'price' => 3,
    ];

    public function get_config($field_arr)
    {
        $ids = [];
        foreach ($field_arr as $k) {
            $id = $this->key[$k];
            if (!$id) continue;
            $ids[] = $id;
        }
        return $this->where(['id' => ['in', $ids]])->column('key,value');
    }

    public function set_config($value_arr)
    {
        foreach ($value_arr as $k => $v) {
            $this->where('id', $this->key[$k])->update(['value'=> $v]);
        }
    }
}