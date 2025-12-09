<?php
namespace app\admin\controller;

class Returns extends Common{

    /**
     * @return 每日收益
     */
    public function returns_lock()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['a.user_id|b.username'] = $key;
        }
        $sldate = input('date', '')?input('date', ''):'';
        if($sldate !=''){
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = $arr[0];
                $arrdatetwo = $arr[1];
                $where['a.date'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if(request()->isPost()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list = db('returns_lock')->alias('a')
                ->join('users b','a.user_id=b.user_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) addtime,from_unixtime(a.starttime) starttime,from_unixtime(a.endtime) endtime,b.username')
                ->order('id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['lock'] = number_format($v['lock'],4,".","");
                $list['data'][$k]['lock_rate'] = number_format($v['lock_rate'],4,".","");
                $list['data'][$k]['team_lock'] = number_format($v['team_lock'],4,".","");
                $list['data'][$k]['team_rate'] = number_format($v['team_rate'],4,".","");
                $list['data'][$k]['lock_returns'] = number_format($v['lock_returns'],4,".","");
                $list['data'][$k]['team_returns'] = number_format($v['team_returns'],4,".","");
                $list['data'][$k]['returns'] = number_format($v['returns'],4,".","");
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    /**
     * @return 节点释放
     */
    public function returns_node()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['b.number|b.username|a.user_id'] = $key;
        }
        $sldate = input('date', '')?input('date', ''):'';
        if($sldate !=''){
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = $arr[0];
                $arrdatetwo = $arr[1];
                $where['a.date'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if(request()->isPost()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list = db('returns_node')->alias('a')
                ->join('users b','a.user_id=b.user_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) addtime,b.username,b.number')
                ->order('id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            $node_level = db('node_level')->field('node_level_id,name')->select();
            $node_level_label = [];
            foreach ($node_level as $item){
                $node_level_label[$item['node_level_id']] = $item['name'];
            }
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['returns'] = number_format($v['returns'],4,".","");
                $list['data'][$k]['node_level_label'] = $node_level_label[$v['node_level_id']];
                $list['data'][$k]['pick_time'] = $v['pick_time']>0?date("Y-m-d H:i:s",$v['pick_time']):'';
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    /**
     * @return 超级节点释放
     */
    public function returns_vip()
    {
        $where = [];
        $key = input('post.key');
        if ($key != '') {
            $where['a.user_id|b.number|b.username'] = $key;
        }
        $sldate = input('date', '')?input('date', ''):'';
        if($sldate !=''){
            $arr = explode(" - ", $sldate);
            if (count($arr) == 2) {
                $arrdateone = $arr[0];
                $arrdatetwo = $arr[1];
                $where['a.date'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
        }

        if(request()->isPost()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list = db('returns_vip')->alias('a')
                ->join('users b','a.user_id=b.user_id')
                ->where($where)
                ->field('a.*,from_unixtime(a.addtime) addtime,from_unixtime(a.pick_time) pick_time,b.username,b.number')
                ->order('id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['returns'] = number_format($v['returns'],4,".","");
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    public function node_item()
    {
        if(request()->isPost()){
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where[''] = $key;
            }

            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');

            $list=db('returns_node_item')->alias('r')
                ->join('users','r.user_id=users.user_id','left')
                ->where($where)
                ->field('r.*,users.username,from_unixtime(r.addtime) addtime')
                ->order('r.id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    public function returns_crowd()
    {
        if(request()->isPost()){
            $where = [];
            $key = input('post.key');
            if ($key != '') {
                $where['r.user_id|u.number|u.username'] = $key;
            }

            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');

            $list=db('returns_crowd')->alias('r')
                ->join('users u','r.user_id=u.user_id','left')
                ->where($where)
                ->field('r.*,u.username,u.number,from_unixtime(r.addtime) addtime')
                ->order('r.id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();

            foreach ($list['data'] as $k => $v) {
                if($v['pick_time']!=0){
                    $list['data'][$k]['pick_time'] = date('Y-m-d H:i:s',$v['pick_time']);
                }else{
                    $list['data'][$k]['pick_time'] = null;
                }
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

}