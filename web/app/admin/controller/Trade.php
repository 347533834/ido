<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Input;

class Trade extends Common
{
    //挂买记录列表
    public function tradeList()
    {
        //平台当天各交易对入金量
        $today = Db('trade')->where(['type' => 1, 'date' => date('Ymd'), 'status' => ['in', [0, 1]]])->field('main_coin_id,exch_coin_id,sum(total) total,sum(num) num')->group('main_coin_id,exch_coin_id')->select();
        foreach ($today as $k => $v) {
            //各等级匹配数量和挂卖数量
            $matching = Db('matching')->field('matching_id,rate_' . $v['exch_coin_id'])->select();
            $other_order_rate = Db('config')->where(['name' => 'other_order_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
            foreach ($matching as $vv) {
                $match = $other_order_rate * $v['total'] * $vv['rate_' . $v['exch_coin_id']] / 100;//总入金量的20%
                $num = Db('trade')->where(['type' => 2, 'date' => date('Ymd'), 'final_level' => $vv['matching_id'], 'exch_coin_id' => $v['exch_coin_id'], 'status' => ['in', [0, 1]]])->count();
                if ($num != 0) {
                    $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = bcdiv($match, $num, 8);
                } else {
                    $level[$v['exch_coin_id']][$vv['matching_id']]['average'] = 0;
                }
            }
        }
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.username|users.user_id|users.mobile|id.id_name'] = $key;
            }
            $status = input('post.status');
            if ($status != '') {
                $where['t.status'] = $status;
            }
            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['t.exch_coin_id'] = $coin_id;
            }
            $type = input('post.type');
            if ($type != '') {
                $where['t.type'] = $type;
            }
            $final_level = input('post.final_level');
            if ($final_level != '') {
                $where['t.final_level'] = $final_level;
            }
            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['t.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('trade')->alias('t')
                ->join('users', 't.user_id=users.user_id')
                ->join('user_rela ur', 't.user_id=ur.user_id')
                ->join('coin c', 't.main_coin_id=c.coin_id')
                ->join('coin co', 't.exch_coin_id=co.coin_id')
                ->join('idaudit id', 't.user_id=id.user_id', 'left')
                ->where($where)
                ->field('id.id_name,t.* ,c.name,users.user_id,users.username,users.mobile,co.name as coin_name,from_unixtime(t.addtime) as addtime,users.matching_level,ur.team_total,users.user_level')
                ->order('t.addtime desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();

            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $dircet_buy_rate = Db('config')->where(['name' => 'dircet_buy_rate', 'coin_id' => $v['exch_coin_id']])->value('value');
                    if ($v['date'] == date('Ymd') && $v['type'] == 2 && $v['status'] != -1) {
                        $list['data'][$k]['amount1'] = Db('trade')->where(['type' => 1, 'pid' => $v['user_id'], 'date' => date('Ymd'), 'exch_coin_id' => $v['exch_coin_id'], 'status' => ['in', [0, 1]]])->sum('total') * $dircet_buy_rate;
                        $list['data'][$k]['amount2'] = $level[$v['exch_coin_id']][$v['final_level']]['average'];
                        $list['data'][$k]['amount'] = bcadd($list['data'][$k]['amount1'], $list['data'][$k]['amount2'], 8);
                        $deal_num = bcdiv($list['data'][$k]['amount'], $v['price'], 4);
                        if ($deal_num >= $v['num']) {
                            $list['data'][$k]['deal_num'] = $v['num'];
                        } else {
                            $list['data'][$k]['deal_num'] = $deal_num;
                        }
                    } else {
                        $list['data'][$k]['amount1'] = 0;
                        $list['data'][$k]['amount2'] = 0;
                        $list['data'][$k]['amount'] = 0;
                        $list['data'][$k]['deal_num'] = 0;
                    }
                }

            }


            //print_r($list);
            //exit();
            $coins = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }

        $buy = db('trade')->alias('a')
            ->join('coin b', 'a.exch_coin_id=b.coin_id')
            ->where(['a.date' => date('Ymd'), 'a.type' => 1, 'a.status' => ['in', [0, 1]]])
            ->field('sum(a.num) num,sum(a.total) total,a.exch_coin_id,b.name,count(*) people')
            ->order('a.exch_coin_id')
            ->group('a.exch_coin_id')
            ->select();

        $sell = db('trade')->alias('a')
            ->join('coin b', 'a.exch_coin_id=b.coin_id')
            ->where(['a.date' => date('Ymd'), 'a.type' => 2, 'a.status' => ['in', [0, 1]]])
            ->field('sum(a.num) num,sum(a.total) total,a.exch_coin_id,b.name,count(*) people')
            ->order('a.exch_coin_id')
            ->group('a.exch_coin_id')
            ->select();

        $this->assign('buy', $buy);
        $this->assign('sell', $sell);

        $ido_people = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'exch_coin_id' => 1, 'status' => ['in', [0, 1]]])->field('count(*) count,final_level')->group('final_level')->select();
        $ach_people = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'exch_coin_id' => 23, 'status' => ['in', [0, 1]]])->field('count(*) count,final_level')->group('final_level')->select();
        $fid_people = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'exch_coin_id' => 24, 'status' => ['in', [0, 1]]])->field('count(*) count,final_level')->group('final_level')->select();

        $this->assign('ido_people', $ido_people);
        $this->assign('ach_people', $ach_people);
        $this->assign('fid_people', $fid_people);

//        $buy_num_ido = db('trade')->where(['date' => date('Ymd'), 'type' => 1, 'status' => 0, 'exch_coin_id' => 1])->sum('`num`');
//        $this->assign('buy_num_ido', $buy_num_ido);
//        $buy_totle_ido = db('trade')->where(['date' => date('Ymd'), 'type' => 1, 'status' => 0, 'exch_coin_id' => 1])->sum('`total`');
//        $this->assign('buy_totle_ido', $buy_totle_ido);
//        $sell_totle_ido = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'status' => 0, 'exch_coin_id' => 1])->sum('`total`');
//        $this->assign('sell_totle_ido', $sell_totle_ido);
//        $sell_num_ido = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'status' => 0, 'exch_coin_id' => 1])->sum('`num`');
//        $this->assign('sell_num_ido', $sell_num_ido);
//
//        $buy_num_ach = db('trade')->where(['date' => date('Ymd'), 'type' => 1, 'status' => 0, 'exch_coin_id' => 23])->sum('`num`');
//        $this->assign('buy_num_ach', $buy_num_ach);
//        $buy_totle_ach = db('trade')->where(['date' => date('Ymd'), 'type' => 1, 'status' => 0, 'exch_coin_id' => 23])->sum('`total`');
//        $this->assign('buy_totle_ach', $buy_totle_ach);
//        $sell_totle_ach = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'status' => 0, 'exch_coin_id' => 23])->sum('`total`');
//        $this->assign('sell_totle_ach', $sell_totle_ach);
//        $sell_num_ach = db('trade')->where(['date' => date('Ymd'), 'type' => 2, 'status' => 0, 'exch_coin_id' => 23])->sum('`num`');
//        $this->assign('sell_num_ach', $sell_num_ach);

        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }

    //成交记录列表
    public function logTradeList()
    {
        if (request()->isPost()) {

            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['u.username|lt.user_id|u.mobile|id.id_name'] = $key;
            }
            $coin_id = input('post.coin_id');
            if ($coin_id != '') {
                $where['lt.exch_coin_id'] = $coin_id;
            }
            $type = input('post.type');
            if ($type != '') {
                $where['lt.type'] = $type;
            }
            $sldate = input('date', '') ? input('date', '') : '';
            if ($sldate != '') {
                $arr = explode(" - ", $sldate);
                if (count($arr) == 2) {
                    $arrdateone = strtotime($arr[0]);
                    $arrdatetwo = strtotime($arr[1]);
                    $where['lt.addtime'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
                }
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('log_trade')->alias('lt')
                ->join('users u', 'lt.user_id=u.user_id')
                ->join('coin c', 'lt.exch_coin_id=c.coin_id')
                ->join('idaudit id', 'lt.user_id=id.user_id', 'left')
                ->where($where)
                ->field('id.id_name,lt.*,lt.price*lt.deal_num deal_total,u.username,u.mobile,from_unixtime(lt.addtime) as addtime,c.name coin_name')
                ->order('addtime desc')//用于对操作的结果排序。
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
                ->toArray();

            $coins = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1, 'coin' => $coins];
        }
        $cat = Db('coin')->where('is_trade', 0)->field('coin_id,name')->select();
        $this->assign('cat', $cat);
        return $this->fetch();
    }
}