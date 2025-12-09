<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Trx extends Common
{
    public function trx_eth()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['te.id|users.username|te.user_id'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('trx_eth')->alias('te')
                ->join('users', 'te.user_id=users.user_id', 'left')
                ->field("te.id,te.txid,te.user_id,te.block,te.confirms,te.contract,te.gasUsed,te.gasPrice,te.from,te.to,te.value,te.status,FROM_UNIXTIME(te.timeStamp) as timeStamp,users.username")
                ->where($where)
                ->order('te.id desc')
                ->where('users.username|te.id', 'like', "%" . $key . "%")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['status'] = config('trx_eth')[$v['status']];
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    public function trx_usdt()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
//            if ($key != '') {
//                $where['te.id|te.txid|users.username|te.user_id'] = $key;
//            }
            $status = input('post.status');
            if ($status != '') {
                $where['te.status'] = $status;
            }

            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('trx_usdt')->alias('te')
                ->join('users', 'te.user_id=users.user_id', 'left')
                ->join('idaudit i', 'i.user_id = users.user_id', 'left')
                ->field("te.id,te.user_id,te.txid,te.fee,te.from,te.to,te.amount,te.propid,te.addtime,te.block,FROM_UNIXTIME(te.blocktime) as blocktime,te.confirms,te.status,users.mobile,i.id_name")
                ->where($where)
                ->order('te.id desc')
                ->where('te.txid|users.username|te.user_id', 'like', "%" . $key . "%")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
            foreach ($list['data'] as $k => $v) {
                //$list['data'][$k]['status'] = config('trx_eth')[$v['status']];
                if ($v['addtime'] != 0) {
                    $list['data'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                } else {
                    $list['data'][$k]['addtime'] = null;
                }
            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }

        $data['num'] = Db('trx_usdt')->where(['coin_id' => 2, 'user_id' => ['neq', 0]])->sum('amount');
        $this->assign('data', $data);

        return $this->fetch();
    }

    public function trx_coin()
    {
        if (request()->isPost()) {
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['users.username|coin.name|users.number'] = $key;
            }
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = db('trx_coin')->alias('tc')
                ->join('users', 'tc.user_id=users.user_id', 'left')
                ->join('coin', 'tc.coin_id=coin.coin_id')
                ->field('tc.*,coin.name,users.username,users.number,FROM_UNIXTIME(tc.blocktime) as blocktime,FROM_UNIXTIME(tc.addtime) as addtime')
                ->where($where)
                ->order('tc.id desc')
                // ->where('users.username|tc.id|coin.name', 'like', "%" . $key . "%")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();
//            foreach ($list['data'] as $k => $v) {
//                $list['data'][$k]['status'] = config('trx_coin')[$v['status']];
//            }
            return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();

    }


}
