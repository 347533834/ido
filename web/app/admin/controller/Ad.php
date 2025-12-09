<?php

namespace app\admin\controller;

use think\Db;
use think\Request;
use think\Controller;

class Ad extends Common
{
  public function _initialize()
  {
    parent::_initialize();
  }

  //广告列表
  public function index()
  {
    if (request()->isPost()) {
      $key = input('post.key');
      $this->assign('testkey', $key);
      $page = input('page') ? input('page') : 1;
      $pageSize = input('limit') ? input('limit') : config('paginate.list_rows');
      $list = Db::name('ad')->alias('a')
        ->join(config('database.prefix') . 'ad_type at', 'a.type_id = at.type_id', 'left')
        ->field('a.*,at.name as typename')
        ->where('a.name', 'like', "%" . $key . "%")
        ->order('a.sort')
        ->paginate(array('list_rows' => $pageSize, 'page' => $page))
        ->toArray();
      foreach ($list['data'] as $k => $v) {
        $list['data'][$k]['addtime'] = date('Y-m-d H:s', $v['addtime']);
      }
      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
    }
    return $this->fetch();
  }

  public function add()
  {
    if (request()->isPost()) {
      //构建数组
      $data = input('post.');
      $data['addtime'] = time();
      $typeId = explode(':', $data['type_id']);
      $data['type_id'] = $typeId[1];
      db('ad')->insert($data);
      $result['code'] = 1;
      $result['msg'] = '广告添加成功!';
      cache('adList', NULL);
      $result['url'] = url('index');
      return $result;
    } else {
      $adtypeList = db('ad_type')->order('sort')->select();
      $this->assign('adtypeList', json_encode($adtypeList, true));

      $this->assign('title', lang('add') . lang('ad'));
      $this->assign('info', 'null');
      return $this->fetch('form');
    }
  }

  public function edit()
  {
    if (request()->isPost()) {
      $data = input('post.');
      $typeId = explode(':', $data['type_id']);
      $data['type_id'] = $typeId[1];
      db('ad')->update($data);
      $result['code'] = 1;
      $result['msg'] = '广告修改成功!';
      cache('adList', NULL);
      $result['url'] = url('index');
      return $result;
    } else {
      $adtypeList = db('ad_type')->order('sort')->select();
      $ad_id = input('ad_id');
      $adInfo = db('ad')->where(array('ad_id' => $ad_id))->find();
      $this->assign('adtypeList', json_encode($adtypeList, true));
      $this->assign('info', json_encode($adInfo, true));
      $this->assign('title', lang('edit') . lang('ad'));
      return $this->fetch('form');
    }
  }

  //设置广告状态
  public function editState()
  {
    $id = input('post.id');
    $open = input('post.open');
    if (db('ad')->where('ad_id=' . $id)->update(['open' => $open]) !== false) {
      return ['status' => 1, 'msg' => '设置成功!'];
    } else {
      return ['status' => 0, 'msg' => '设置失败!'];
    }
  }

  public function adOrder()
  {
    $ad = db('ad');
    $data = input('post.');
    if ($ad->update($data) !== false) {
      cache('adList', NULL);
      return $result = ['msg' => '操作成功！', 'url' => url('index'), 'code' => 1];
    } else {
      return $result = ['code' => 0, 'msg' => '操作失败！'];
    }
  }

  public function del()
  {
    db('ad')->where(array('ad_id' => input('ad_id')))->delete();
    cache('adList', NULL);
    return ['code' => 1, 'msg' => '删除成功！'];
  }

  public function delall()
  {
    $map['ad_id'] = array('in', input('param.ids/a'));
    db('ad')->where($map)->delete();
    cache('adList', NULL);
    $result['msg'] = '删除成功！';
    $result['code'] = 1;
    $result['url'] = url('index');
    return $result;
  }

  /***************************位置*****************************/
  //位置
  public function type()
  {
    if (request()->isPost()) {
      $key = input('key');
      $this->assign('testkey', $key);
      $list = db('ad_type')->where('name', 'like', "%" . $key . "%")->order('sort')->select();
      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list, 'rel' => 1];
    }
    return $this->fetch();
  }

  public function typeOrder()
  {
    $ad_type = db('ad_type');
    $data = input('post.');
    if ($ad_type->update($data) !== false) {
      return $result = ['msg' => '操作成功！', 'url' => url('type'), 'code' => 1];
    } else {
      return $result = ['code' => 0, 'msg' => '操作失败！'];
    }
  }

  public function addType()
  {
    if (request()->isPost()) {
      db('ad_type')->insert(input('post.'));
      $result['code'] = 1;
      $result['msg'] = '广告位保存成功!';
      $result['url'] = url('type');
      return $result;
    } else {
      $this->assign('title', lang('add') . lang('ad') . '位');
      $this->assign('info', 'null');
      return $this->fetch('typeForm');
    }
  }

  public function editType()
  {
    if (request()->isPost()) {
      db('ad_type')->update(input('post.'));
      $result['code'] = 1;
      $result['msg'] = '广告位修改成功!';
      $result['url'] = url('type');
      return $result;
    } else {
      $type_id = input('param.type_id');
      $info = db('ad_type')->where('type_id', $type_id)->find();
      $this->assign('title', lang('edit') . lang('ad') . '位');
      $this->assign('info', json_encode($info, true));
      return $this->fetch('typeForm');
    }
  }

  public function delType()
  {
    $map['type_id'] = input('param.type_id');
    db('ad_type')->where($map)->delete();//删除广告位
    db('ad')->where($map)->delete();//删除该广告位所有广告
    return ['code' => 1, 'msg' => '删除成功！'];
  }

  //首页广告图列表
  public function picture()
  {
    if (request()->isPost()) {
      $page = input('page') ? input('page') : 1;
      $pageSize = input('limit') ? input('limit') : config('pageSize');
      $list = Db('first_picture')->alias('f')->join('first_cat c', 'f.type = c.first_id')
        ->field('f.id,f.url,f.status,f.type,f.operation,f.jump_url,f.title,f.sorting,FROM_UNIXTIME(f.addtime) as addtime,c.name')
        ->order('f.id desc')
        ->paginate(array('list_rows' => $pageSize, 'page' => $page))
        ->toArray();
      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
    }
    return $this->fetch();
  }

  //首页广告图删除
  public function picture_del()
  {
    $map['id'] = input('param.id');
    Db::startTrans();
    try {
      db('first_picture')->where($map)->delete();
      //添加操作日志
      db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '首页广告图删除ID:' . $map['id'], 'type' => 206, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'first_picture', 'union_id' => $map['id']));
      Db::commit();
      $result['msg'] = '删除成功！';
      $result['code'] = 1;
      $result['url'] = url('picture');
      return $result;
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      return ['code' => 1, 'msg' => '删除失败！', 'data' => $e->getMessage()];
    }
  }

  //首页广告图新增
  public function picture_add()
  {
    if (request()->isPost()) {
      $data = input('post.');
      $data['addtime'] = time();

      if ($data['status'] == '') {
        $data['status'] = 0;
      }

      if (isset($data['imgs']) && count($data['imgs']) < 1) {
        return ["code" => 0, "msg" => '请上传图片！'];
      }

      Db::startTrans();
      try {
        $id = Db('first_picture')->insertGetId(['status' => $data['status'], 'type' => $data['type'], 'operation' => $_SESSION['session']['username'], 'addtime' => time(), 'title' => $data['title'], 'jump_url' => $data['jump_url']]);
        $imgs['first_picture_' . $id] = $data['imgs']['logo'];

        //图片上传
        $img_data = $this->upload($imgs, 'assets/img/first_picture/');

        if (!$img_data['code']) {
          return $img_data;
        }

        $data['url'] = $img_data['data']['first_picture_' . $id];


        Db('first_picture')->where(['id'=>$id])->update(['url'=>$data['url']]);

        //添加操作日志'
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 204, 'union' => 'first_picture', 'union_id' => $id, 'object' => '新增广告图ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '广告图添加成功!';
        $result['url'] = url('picture');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '添加失败！','data'=>$e->getMessage()];
      }
    } else {
      $cat = Db('first_cat')->field('first_id,name')->select();
      $this->assign('cat', $cat);
      $info['logo'] = '';
      $this->assign('title', lang('add') . "首页广告图");
      $this->assign('info', 'null');
      $this->assign('info', json_encode($info, true));
      return $this->fetch('picture_add');
    }
  }

  /**
   * 修改首页广告图
   */
  public function picture_edit()
  {
    if (request()->isPost()) {
      $data = input('post.');
      $data['id'] = input('param.id');
      $data['addtime'] = time();
      if (isset($data['imgs']) && count($data['imgs']) == 1) {

        $imgs['first_picture_' . $data['id']] = $data['imgs']['logo'];

        //图片上传
        $img_data = $this->upload($imgs, 'assets/img/first_picture/');
        if (!$img_data['code']) {
            return ['code' => 0, 'msg' => '修改失败！',$img_data];
        }

        $data['logo'] = $img_data['data']['first_picture_' . $data['id']];
        $data['url'] = $data['logo'];
      }

      Db::startTrans();
      try {

        unset($data['logo']);
        unset($data['imgs']);
        db('first_picture')->update($data);

        //添加操作日志
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 205, 'union' => 'first_picture', 'union_id' => $data['id'], 'object' => '修改首页广告图ID:' . $data['id'], 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '首页广告图修改成功!';
        $result['url'] = url('picture');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '修改失败！'];
      }
    } else {
      $map['id'] = input('param.id');

      $info = db('first_picture')->where($map)->find();
      $cat = Db('first_cat')->field('first_id,name')->select();
      $this->assign('cat', $cat);
      $info['logo'] = config('OSS_GLOBAL')['oss_url'] .'/'. $info['url'] . '?' . $info['addtime'];
      $this->assign('title', lang('edit') . "首页广告图");
      $this->assign('info', json_encode($info, true));
      $this->assign('infos', $info);
      return $this->fetch('picture_add');
    }
  }

  /**
   * 首页广告图状态修改
   */
  public function picture_state()
  {
    $id = input('post.id');
    $status = db('first_picture')->where(array('id' => $id))->value('status');//判断当前状态情况
    if ($status == 1) {
      Db::startTrans();
      try {
        $data['status'] = 0;
        db('first_picture')->where(array('id' => $id))->setField($data);
        //添加操作日志
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => ' 首页广告图禁用' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 207, 'union' => 'first_picture', 'union_id' => $id));
        Db::commit();
        $result['status'] = 0;
        $result['code'] = 1;
        return $result;

      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        $result['status'] = 1;
        $result['code'] = 0;
        return $result;
      }


    } else {
      Db::startTrans();
      try {
        $data['status'] = 1;
        db('first_picture')->where(array('id' => $id))->setField($data);
        //添加操作日志
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '首页广告图开启' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 207, 'union' => 'first_picture', 'union_id' => $id));

        Db::commit();
        $result['status'] = 1;
        $result['code'] = 1;
        return $result;

      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        $result['status'] = 1;
        $result['code'] = 0;
        return $result;
      }
    }

  }


  //广告图类型删除
  public function first_cat_del()
  {
    $map['first_id'] = input('param.first_id');
    Db::startTrans();
    try {
      db('first_cat')->where($map)->delete();
      //添加操作日志
      db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '广告图类型删除ID:' . $map['first_id'], 'type' => 203, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'first_cat', 'union_id' => $map['first_id']));
      Db::commit();
      $result['msg'] = '删除成功！';
      $result['code'] = 1;
      $result['url'] = url('first_cat');

      return $result;
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      return ['code' => 1, 'msg' => '删除失败！', 'data' => $e->getMessage()];
    }
  }

  //广告图类型新增
  public function first_cat_add()
  {

    if (request()->isPost()) {
      $data = input('post.');
      $data['addtime'] = time();
      Db::startTrans();
      try {

        $id = Db('first_cat')->insertGetId($data);
        //添加操作日志'
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 201, 'union' => 'first_cat', 'union_id' => $id, 'object' => '添加广告图类型ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '广告图类型添加成功!';
        $result['url'] = url('first_cat');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '添加失败！'];
      }
    } else {
      $this->assign('title', lang('add') . "广告图类型");
      $this->assign('info', 'null');
      return $this->fetch('first_cat_add');
    }
  }

  //广告图类型修改
  public function first_cat_edit()
  {
    if (request()->isPost()) {
      $data = input('post.');
      $data['first_id'] = input('param.first_id');
      $data['addtime'] = time();
      Db::startTrans();
      try {

        Db('first_cat')->update($data);
        //添加操作日志'
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 202, 'union' => 'first_cat', 'union_id' => $data['first_id'], 'object' => '修改广告图类型ID:' . $data['first_id'], 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '修改成功!';
        $result['url'] = url('first_cat');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '修改失败！'];
      }
    } else {
      $map['first_id'] = input('param.first_id');
      $info = db('first_cat')->where($map)->find();
      $this->assign('title', lang('edit') . "广告图类型");
      $this->assign('info', json_encode($info, true));
      return $this->fetch('first_cat_add');
    }
  }

  //广告图类型列表
  public function first_cat()
  {
    if (request()->isPost()) {
      $key = input('post.key');
      $this->assign('testkey', $key);
      $page = input('page') ? input('page') : 1;
      $pageSize = input('limit') ? input('limit') : config('pageSize');
      $list = db('first_cat')->where('name', 'like', "%" . $key . "%")
        ->order('first_id desc')
        ->paginate(array('list_rows' => $pageSize, 'page' => $page))
        ->toArray();
      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
    }
    return $this->fetch();
  }

  //公告列表
  public function notice()
  {
    if (request()->isPost()) {
      $key = input('post.key');
      $this->assign('testkey', $key);
      $page = input('page') ? input('page') : 1;
      $pageSize = input('limit') ? input('limit') : config('pageSize');
      $list = db('notice')->where('title', 'like', "%" . $key . "%")
          ->field('*,FROM_UNIXTIME(addtime) as addtime')
          ->order('id desc')
          ->paginate(array('list_rows' => $pageSize, 'page' => $page))
          ->toArray();


      return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
    }
    return $this->fetch();
  }

  //公告新增
  public function noticeadd()
  {

    if (request()->isPost()) {
      $data = input('post.');
      $data['addtime'] = time();
      $data['content'] = $this->filterHTML($data['content']);
      $data['content_en'] = $this->filterHTML($data['content2']);
      if($data['content']==''){
          return ['code' => 0, 'msg' => '内容不能为空！'];
      }
        $data['content'] = str_replace("'",'&#39;',$data['content']);
        $data['content_en'] = str_replace("'",'&#39;',$data['content_en']);
        $data['title'] = str_replace("'",'&#39;',$data['title']);
        $data['title_en'] = str_replace("'",'&#39;',$data['title_en']);
      unset($data['id']);
      unset($data['file']);
      Db::startTrans();
      try {

        $id = Db('notice')->insertGetId($data);
        //添加操作日志'
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 95, 'union' => 'notice', 'union_id' => $id, 'object' => '新增公告ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '公告添加成功!';
        $result['url'] = url('notice');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '添加失败！'];
      }
    } else {
      $this->assign('title', lang('add') . "公告");
      $this->assign('info', 'null');
      return $this->fetch('noticeForm');
    }
  }

  //公告修改
  public function noticeedit()
  {
    if (request()->isPost()) {
      $data = input('post.');
//      $data['addtime'] = time();
      $data['content'] = $this->filterHTML($data['content']);
      $data['content_en'] = $this->filterHTML($data['content2']);
        $data['content'] = str_replace("'",'&#39;',$data['content']);
        $data['content_en'] = str_replace("'",'&#39;',$data['content_en']);
        $data['title'] = str_replace("'",'&#39;',$data['title']);
        $data['title_en'] = str_replace("'",'&#39;',$data['title_en']);
      unset($data['file']);
      Db::startTrans();
      try {
        $id = Db('notice')->update($data);
        //添加操作日志'
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'type' => 96, 'union' => 'notice', 'union_id' => $id, 'object' => '修改公告ID:' . $id, 'last_time' => time(), 'ip' => request()->ip(1)));
        Db::commit();
        $result['msg'] = '修改成功!';
        $result['url'] = url('notice');
        $result['code'] = 1;
        return $result;
      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        return ['code' => 0, 'msg' => '修改失败！'];
      }
    } else {
      $map['id'] = input('param.id');
      $info = db('notice')->where($map)->find();
      $this->assign('title', lang('edit') . "公告");
      $this->assign('info', json_encode($info, true));
      return $this->fetch('noticeForm');
    }
  }

  //公告状态修改
  public function noticeState()
  {
    $id = input('post.id');
    $status = db('notice')->where(array('id' => $id))->value('status');//判断当前状态情况
    if ($status == 1) {
      Db::startTrans();
      try {
        $data['status'] = 0;
        db('notice')->where(array('id' => $id))->setField($data);
        //添加操作日志
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => ' 公告开启' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 97, 'union' => 'notice', 'union_id' => $id));
        Db::commit();
        $result['status'] = 0;
        $result['code'] = 1;
        return $result;

      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        $result['status'] = 1;
        $result['code'] = 0;
        return $result;
      }


    } else {
      Db::startTrans();
      try {
        $data['status'] = 1;
        db('notice')->where(array('id' => $id))->setField($data);
        //添加操作日志
        db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '公告禁用' . $id, 'last_time' => time(), 'ip' => request()->ip(1), 'type' => 97, 'union' => 'notice', 'union_id' => $id));

        Db::commit();
        $result['status'] = 1;
        $result['code'] = 1;
        return $result;

      } catch (Exception $e) {
        Log::error($e->getMessage());
        Db::rollback();
        $result['status'] = 0;
        $result['code'] = 0;
        return $result;
      }
    }
  }

  //公告删除
  public function noticedel()
  {
    $map['id'] = input('param.id');
    Db::startTrans();
    try {
      db('notice')->where($map)->delete();
      //添加操作日志
      db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '公告删除ID:' . $map['id'], 'type' => 98, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'notice', 'union_id' => $map['id']));
      Db::commit();
      $result['msg'] = '删除成功！';
      $result['code'] = 1;
      $result['url'] = url('association');
      return $result;
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      return ['code' => 1, 'msg' => '删除失败！', 'data' => $e->getMessage()];
    }
  }

  //公告富文本图片上传
  public function uploads()
  {
    $id = Db('notice')->order('addtime desc')->value('id');
    if (!$id) {
      $id = 1;
    } else {
      $id = $id + 1;
    }
    // 获取上传文件表单字段名
    $fileKey = array_keys(request()->file());
    // 获取表单上传文件
    $file = request()->file($fileKey['0']);
    $name = $id . '_' . time() . '_' . $_FILES['file']['name'];

    // 移动到框架应用根目录/public/uploads/ 目录下
    $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads', $name);

    $new_file = ROOT_PATH . 'public' . DS . 'uploads' . DS . str_replace('\\', '/', $info->getSaveName());

    //上传oss
    require_once APP_PATH . '../extend/aliyun-oss-php-sdk/autoload.php';
    $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//去阿里云后台获取秘钥
    $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//去阿里云后台获取秘钥
    $endpoint = config('OSS_GLOBAL')['endpoint'];//你的阿里云OSS地址
    $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $bucket= config('OSS_GLOBAL')['bucket'];//oss中的文件上传空间

    $object = 'notice/' . $name;//想要保存文件的名称
    $ossClient->uploadFile($bucket, $object, $new_file);
//        unlink($new_file);

    if ($info) {
      $result['code'] = 0;
      $result['msg'] = '图片上传成功!';
      $result['data']['src'] = config('OSS_GLOBAL')['oss_url'] .'/' . $object . '?' . time();
      return json_encode($result);
    } else {
      // 上传失败获取错误信息
      $result['code'] = 1;
      $result['msg'] = '图片上传失败!';
      json_encode($result);
    }

  }
}