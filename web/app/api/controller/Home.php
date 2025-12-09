<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

class Home extends Common
{

    /**
     * 求购队列
     */
    public function LoadList()
    {
        $data['price'] = db('coin')->where(['coin_id' => 1])->value('price');
        $data['data'] = db('trade')->where(['status' => array('in', '0,1')])->field('trade_id,username,deal_num,(num-deal_num) buy_num,total,addtime')->order('price desc')->limit(5)->select();
        $this->apiResp($data);
    }

    public function LoadNotice()
    {
        $data = Db('notice')->field('title,addtime,id')->where(['status' => 0])->order('id desc')->field('id,title')->limit(1)->find();
        $this->apiResp($data);
    }

}
