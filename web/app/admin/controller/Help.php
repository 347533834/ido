<?php
namespace app\admin\controller;
use clt\Tree;
use think\Db;
use think\Log;
use think\Exception;
class Help extends Common
{
    public function index(){
        if(request()->isPost()){
            $where = [];
            $key=input('post.key');
            if ($key != '') {
                $where['help_id|title'] = $key;
            }
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list=db('help')
                ->where($where)
                ->order('sort')
                ->order('id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();

            foreach ($list['data'] as $k=>$v){

            }

            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    public function helpOrder(){
        $obj=db('help');
        $map['id'] = input('id');
        $data['sort'] = input('sort');
        if($obj->where($map)->update($data)!==false){
            return $result = ['msg' => '操作成功！','url'=>url('index'), 'code' =>1];
        }else{
            return $result = ['code'=>0,'msg'=>'操作失败！'];
        }
    }

    public function addHelp()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['question_en'] = $this->filterHTML($data['question_en']);
            $data['answer_en'] = $this->filterHTML($data['answer_en']);
            if($data['answer']=='') {
                return ['code' => 0, 'msg' => '答案不能为空！'];
            }
            $data['question'] = str_replace("'",'‘',$data['question']);
            $data['answer'] = str_replace("'",'‘',$data['answer']);
            $data['question_en'] = str_replace("'",'‘',$data['question_en']);
            $data['answer_en'] = str_replace("'",'‘',$data['answer_en']);
            Db::startTrans();
            try {
                $id = db('help')->insertGetId($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '添加帮助:'.$id, 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'help', 'union_id' => $id, 'type' => 261));
                Db::commit();
                $result['msg'] = '添加成功!';
                $result['url'] = url('index');
                $result['code'] = 1;
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $this->assign('title', lang('add') . "帮助");
            $this->assign('info', 'null');
            return $this->fetch();
        }
    }

    public function editHelp()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['question_en'] = $this->filterHTML($data['question_en']);
            $data['answer_en'] = $this->filterHTML($data['answer_en']);
            if($data['answer']=='') {
                return ['code' => 0, 'msg' => '答案不能为空！'];
            }
            $data['question'] = str_replace("'",'‘',$data['question']);
            $data['answer'] = str_replace("'",'‘',$data['answer']);
            $data['question_en'] = str_replace("'",'‘',$data['question_en']);
            $data['answer_en'] = str_replace("'",'‘',$data['answer_en']);
            Db::startTrans();
            try {
                db('help')->update($data);
                //添加操作日志
                db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '修改帮助:'.$data['id'], 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'help', 'union_id' => $data['id'], 'type' => 262));
                $result['msg'] = '修改成功!';
                $result['url'] = url('index');
                $result['code'] = 1;
                Db::commit();
                return $result;
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Db::rollback();
                return ['code' => 0, 'msg' => '操作失败！'];
            }
        } else {
            $map['id'] = input('param.id');
            $info = db('help')->where($map)->find();
            $this->assign('title', lang('edit') . "帮助");
            $this->assign('info', json_encode($info, true));
            return $this->fetch('addHelp');
        }
    }

    public function delHelp(){
        $map['id'] = input('id');
        Db::startTrans();
        try {
            db('help')->where($map)->delete();
            //添加操作日志
            db('log_action')->insert(array('admin_name' => $_SESSION['session']['username'], 'object' => '删除帮助:'.input('id'), 'last_time' => time(), 'ip' => request()->ip(1), 'union' => 'help', 'union_id' => input('id'), 'type' => 263));
            Db::commit();
            $result['msg'] = '删除成功！';
            $result['code'] = 1;
            $result['url'] = url('Help/index');
            return $result;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            return ['code' => 1, 'msg' => '删除失败！', 'data' => $e->getMessage()];
        }

    }

    public function uploads()
    {
        // 获取上传文件表单字段名
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件
        $file = request()->file($fileKey['0']);
        $name = time() . '_' . $_FILES['file']['name'];

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads', $name);

        $new_file = ROOT_PATH . 'public' . DS . 'uploads' . DS . str_replace('\\', '/', $info->getSaveName());
        //    var_dump($new_file);die;
        //上传oss
        require_once APP_PATH . '../extend/aliyun-oss-php-sdk/autoload.php';
        $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//去阿里云后台获取秘钥
        $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//去阿里云后台获取秘钥
        $endpoint = config('OSS_GLOBAL')['endpoint'];//你的阿里云OSS地址
        $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = config('OSS_GLOBAL')['bucket'];//oss中的文件上传空间
        $object = 'coin/' . date('Ym') . '/' . $name;//想要保存文件的名称
        $ossClient->uploadFile($bucket, $object, $new_file);

        if ($info) {
            $result['code'] = 0;
            $result['msg'] = '图片上传成功!';
            $result['data']['src'] = config('OSS_GLOBAL')['oss_url'] . '/' . $object . '?' . time();
            return json_encode($result);
        } else {
            // 上传失败获取错误信息
            $result['code'] = 1;
            $result['msg'] = '图片上传失败!';
            json_encode($result);
        }
    }


}