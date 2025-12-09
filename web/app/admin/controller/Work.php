<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;

class Work extends Common
{
   public function work()
   {
       if (request()->isPost()) {
           $where = [];
           $key = input('post.key');
           if ($key != '') {
               $where['a.user_id|users.username'] = $key;
           }

           $page = input('page') ? input('page') : 1;
           $pageSize = input('limit') ? input('limit') : config('pageSize');
           $list = db('work')->alias('a')//设置数据库别名
          ->join('users','a.user_id=users.user_id','left')//要关联的表名 关联条件 关联类型
           ->where($where)
               ->field('a.*,users.username,FROM_UNIXTIME(a.addtime) addtime')//指定一个表的某些字段查询
               ->order('a.addtime desc')//用于对操作的结果排序。
               ->paginate(array('list_rows' => $pageSize, 'page' => $page))//分页方法
               ->toArray();
//           foreach($list['data'] as $k=>$v){
//               $list['data'][$k]['status'] = config('work')[$v['status']];
//               $list['data'][$k]['status_two'] = config('work1')[$v['status_two']];
//           }
           return $result = ['code' => 0, 'msg' => '获取成功!', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
       }
       return $this->fetch();
   }

    //公告富文本图片上传
    public function uploads()
    {
        $id = Db('log_work')->order('log_work_id desc')->value('log_work_id');
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
        $object = 'log_work/'.date('Ym') .'/'. $name;//想要保存文件的名称
        $ossClient->uploadFile($bucket, $object, $new_file);
//        unlink($new_file);

        if ($info) {
            $result['code'] = 0;
            $result['msg'] = '图片上传成功!';
            $result['data']['src'] = config('OSS_GLOBAL')['oss_url'] .'/'. $object . '?' . time();
            return json_encode($result);
        } else {
            // 上传失败获取错误信息
            $result['code'] = 1;
            $result['msg'] = '图片上传失败!';
            json_encode($result);
        }

    }

   public function work_edit()
   {
       if (request()->isPost()) {
           $data = input('post.');
           //var_dump($_SESSION['session']);die;

           Db::startTrans();
           try {
               $status=db('work')->where('work_id','=',$data['work_id'])->value('status');
               $status_two=db('work')->where('work_id','=',$data['work_id'])->value('status_two');
               if ($status==0 && $status_two==1) {
                   db('work')->where('work_id','=',$data['work_id'])->setField('status','1');
                   db('work')->where('work_id','=',$data['work_id'])->setField('status_two','0');
                  // 添加到log_work中
                db('log_work')->insert(array('name'=>'管理员','content'=>$data['content'],'type'=>0,'addtime'=>time(),'work_id'=>$data['work_id'],'admin_id'=>$_SESSION['session']['aid']));
                   $result['msg'] = '回复成功!';
                   $result['url'] = url('work');
                   $result['code'] = 1;
                   Db::commit();
                   return $result;
               }else{
                   db('work')->where('work_id','=',$data['work_id'])->update(['status'=>1,'status_two'=>1]);
                   // 添加到log_work中
                   db('log_work')->insert(array('name'=>'管理员','content'=>$data['content'],'type'=>0,'addtime'=>time(),'work_id'=>$data['work_id'],'admin_id'=>$_SESSION['session']['aid']));
                   $result['msg'] = '回复成功!';
                   $result['url'] = url('work');
                   $result['code'] = 1;
                   Db::commit();
                   return $result;

               }

           } catch (Exception $e) {
               Log::error($e->getMessage());
               Db::rollback();
               return ['code' => 0, 'msg' => '操作失败！', $e->getMessage()];
           }
       } else {
           $map['work_id'] = input('param.work_id');
           $info = db('work')
              // ->join('log_work','log_work.work_id=work.work_id','left')
               ->where($map)
               ->find();
          // var_dump($info);die;
           $info_two=db('log_work')
               ->where($map)
               ->select();
/*           echo '<pre>';
           var_dump($info_two);die;*/
           $info['img'] = $info['img'] ? config('OSS_GLOBAL')['oss_url'] . '/' . $info['img'] . '?' . $info['addtime'] : '';
           //var_dump($info['content']);die;
           $this->assign('info_two', $info_two);
           $this->assign('infos', $info);
           $this->assign('title', '问题回复');
           $this->assign('info', json_encode($info, true));

           return $this->fetch('work_edit');

       }
   }


}
