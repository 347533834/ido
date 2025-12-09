<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 币币交易
 * Class Trade
 * @package app\api\controller
 */

class Exchange extends Base
{

    public $per_page = 5;

    public function index($token){

        // banner图
        $imgs = Db::name('first_picture')->where(['type'=>7,'status'=>1])->field('id,url')->order('sorting asc')->select();
        $data['imgs'] = $imgs;

        // 价格
        $price = Db::name('coin')->where(['coin_id'=>1])->value('price');
        $data['price'] = sprintf('%.4f',$price);

        // 通知
        $notice = Db('notice')->field('title,addtime,id')->where(['status' => 0])->order('id desc')->field('id,title')->find();
        $data['notice'] = $notice ;

        // 兑换记录
        $per_page = $this->per_page ;
        $list = [];
//          Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->field('id,coin_id,coin_name,CAST(coin_price AS decimal(18,4)) as coin_price,CAST(coin_num AS decimal(18,4)) as coin_num,CAST(awt_price AS decimal(18,4)) as awt_price,CAST(awt_num AS decimal(18,4)) as awt_num,CAST(fee AS decimal(18,4)) as fee,type')->order('id desc')->limit('0,'.$per_page)->select();
        $data['list'] = $list ;

        $count = 0;
//          Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->count();
        $total_page = ceil($count/$per_page);
        $data['per_page'] = $per_page ;
        $data['total_page'] = $total_page ;

        $this->apiResp($data);

    }

    public function ex_balance($token,$coin_id){

        // 余额
        $balance = Db('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->value('balance');
        $data['balance'] = sprintf('%.4f',$balance);

        // 手续费
        $ex_rate = $this->config['exchange_rate'];
        $data['ex_rate'] = $ex_rate*100;

        // 最小兑换数量
        $ex_min = $this->config['exchange_min'];
        $data['ex_min'] = $ex_min;

        // 最大兑换数量
        $ex_max = $this->config['exchange_max'];
        $data['ex_max'] = $ex_max;

        $this->apiResp($data);

    }

    public function ex_ch($token,$number,$coin_name,$fee,$ex_ch_num,$pay_pass){

        $price = Db::name('coin')->where(['coin_id'=>1])->value('price');
        $price = sprintf('%.4f',$price);

        if($coin_name=='AWT'){
            $ex_rate = $this->config['exchange_rate'];
            $fee_api = sprintf('%.4f',$number*$ex_rate);
            $coin_id = 1 ;
            $coin_id_ex = 2 ;
            $ex_ch_num_api = $number*$price;
            $type = 2;
            $remark = 'AWT兑换USDT';
            $coin_num = $ex_ch_num_api;
            $awt_num = $number;
        }else{
            $ex_rate = 0;
            $fee_api = 0;
            $coin_id = 2 ;
            $coin_id_ex = 1 ;
            $ex_ch_num_api = $number/$price;
            $type = 1;
            $remark = 'USDT兑换AWT';
            $coin_num = $number;
            $awt_num = $ex_ch_num_api;
        }
        if($fee_api!=$fee||sprintf('%.4f',$ex_ch_num_api)!=$ex_ch_num){
            $this->apiReply(2,'手续费和兑换数额错误，请重新输入兑换数量');
        }

        $pwd_pay = Db::name('users')->where(['user_id'=>$this->user_id])->value('paypass');
        if($pwd_pay!=md5($pay_pass . config('password_str'))){
            $this->apiReply(2,'支付密码错误，请重新输入');
        }

        Db::startTrans();

        try{

            $balance = Db('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->lock(true)->value('balance');
            if(($number+$fee)>$balance){
                Db::rollback();
                $this->apiReply(2,'账户余额不足，请重新输入兑换数量');
            }

            $balance_ex = Db('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id_ex])->lock(true)->value('balance');

            $ex_id = Db::name('user_exchange')->insertGetId([
                'user_id' => $this->user_id,
                'coin_id' => 2,
                'coin_name' => $coin_name,
                'coin_price' => 1,
                'coin_num' => $coin_num,
                'awt_price' => $price,
                'awt_num' => $awt_num,
                'fee' => $fee,
                'fee_rate' => $ex_rate,
                'status' => 1,
                'type' => $type,
                'addtime' => time(),
                'remark' => $remark,
            ]);

            Db::name('log_coin')->insert([
                'user_id' => $this->user_id,
                'coin_id' => $coin_id,
                'type' => 31,
                'num' => $number+$fee,
                'amount' => $balance,
                'balance' => $balance-($number+$fee),
                'status' => 1,
                'union' => 'user_exchange',
                'union_id' => $ex_id,
                'addtime' => time(),
                'remark' => '兑换减少',
            ]);

            Db::name('log_coin')->insert([
                'user_id' => $this->user_id,
                'coin_id' => $coin_id_ex,
                'type' => 111,
                'num' => $ex_ch_num_api,
                'amount' => $balance_ex,
                'balance' => $balance_ex+$ex_ch_num_api,
                'status' => 1,
                'union' => 'user_exchange',
                'union_id' => $ex_id,
                'addtime' => time(),
                'remark' => '兑换增加',
            ]);

            Db::name('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id])->update(['balance'=>$balance-($number+$fee)]);
            Db::name('user_coin')->where(['user_id'=>$this->user_id,'coin_id'=>$coin_id_ex])->update(['balance'=>$balance_ex+$ex_ch_num_api]);

            Db::commit();

            // 兑换记录
            $per_page = $this->per_page ;
            $list = Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->field('id,coin_id,coin_name,CAST(coin_price AS decimal(18,4)) as coin_price,CAST(coin_num AS decimal(18,4)) as coin_num,CAST(awt_price AS decimal(18,4)) as awt_price,CAST(awt_num AS decimal(18,4)) as awt_num,CAST(fee AS decimal(18,4)) as fee,type')->order('id desc')->limit('0,'.$per_page)->select();
            $data['list'] = $list ;

            $count = Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->count();
            $total_page = ceil($count/$per_page);
            $data['per_page'] = $per_page ;
            $data['total_page'] = $total_page ;

            $this->apiReply(1,'兑换成功',$data);

        }catch (Exception $e){

            Db::rollback();
            $this->apiReply(2,$e->getMessage());

        }

    }

    public function page_list($token,$page){

        // 兑换记录
        $per_page = $this->per_page ;
        $list = Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->field('id,coin_id,coin_name,CAST(coin_price AS decimal(18,4)) as coin_price,CAST(coin_num AS decimal(18,4)) as coin_num,CAST(awt_price AS decimal(18,4)) as awt_price,CAST(awt_num AS decimal(18,4)) as awt_num,CAST(fee AS decimal(18,4)) as fee,type')->order('id desc')->limit(($page-1)*$per_page.','.$per_page)->select();
        $data['list'] = $list ;

        $count = Db('user_exchange')->where(['status'=>1,'user_id'=>$this->user_id])->count();
        $total_page = ceil($count/$per_page);
        $data['per_page'] = $per_page ;
        $data['total_page'] = $total_page ;
        $this->apiResp($data);

    }


}