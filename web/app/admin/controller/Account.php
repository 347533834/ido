<?php

namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\Log;


class Account extends Common
{
  public function index()
  {
    if (request()->isPost()) {
      $where = [];
      $key1 = input('post.key1');
      if ($key1 != '') {
        $where['address'] = $key1;
      }
      $key2 = input('post.key2');
      if ($key2 != '') {
        $where['asset'] = $key2;
      }
      $key3 = input('post.key3');
      if ($key3 != '') {
        $where['asset_address'] = $key3;
      }

      $sldate = input('date', '');
      if ($sldate != '') {
        $arr = explode(" - ", $sldate);
        if (count($arr) == 2) {
          $arrdateone = strtotime($arr[0]);
          $arrdatetwo = strtotime($arr[1]);
          $where['timestamp'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
        }
      }

      $page = input('page') ? input('page') : 1;
      $pageSize = input('limit') ? input('limit') : config('paginate.list_rows');
      $list = Db('map_accounts')
        ->where($where)
        ->field('id,address,asset,asset_address,transfer_in,transfer_out,FROM_UNIXTIME(timestamp) timestamp')
        ->paginate(array('list_rows' => $pageSize, 'page' => $page))
        ->toArray();
      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
    }
    return $this->fetch();
  }
}