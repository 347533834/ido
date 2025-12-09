<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Invite extends Common
{
   public function invite_bg()
   {
       if (request()->isPost()) {
           $where = [];
           $key = input('post.key');
           if ($key != '') {
               $where[''] = $key;
           }

           $page = input('page') ? input('page') : 1;
           $pageSize = input('limit') ? input('limit') : config('pageSize');

           $list = db('invite_bg')->alias('i')
               ->join('users','i.user_id=users.user_id','left')
               ->where($where)
               ->field('i.*,users.username')
               ->order('i.invite_bg_id desc')
               ->paginate(array('list_rows' => $pageSize, 'page' => $page))
               ->toArray();


           return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
       }
       return $this->fetch();
   }


}
