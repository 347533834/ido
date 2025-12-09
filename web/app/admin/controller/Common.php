<?php

namespace app\admin\controller;

use app\common\controller\Base;
use think\Db;

class Common extends Base
{
  protected $mod, $role, $system, $nav, $menudata, $cache_model, $categorys, $module, $moduleid, $adminRules, $HrefId;

  public function _initialize()
  {
    parent::_initialize();
    // 判断管理员是否登录
    if (!session('aid')) {
      $this->redirect('login/index');
    }

    define('MODULE_NAME', strtolower(request()->controller()));
    define('ACTION_NAME', strtolower(request()->action()));

    //权限管理
    //当前操作权限ID
    if (session('aid') != 1) {
      $this->HrefId = db('auth_rule')->where('href', MODULE_NAME . '/' . ACTION_NAME)->value('id');
      //当前管理员权限
      $map['a.admin_id'] = session('aid');
      $rules = Db::name('admin')->alias('a')
        ->join(config('database.prefix') . 'auth_group ag', 'a.group_id = ag.group_id', 'left')
        ->where($map)
        ->value('ag.rules');
      $this->adminRules = explode(',', $rules);
      if ($this->HrefId) {
        if (!in_array($this->HrefId, $this->adminRules)) {
          $this->error('您无此操作权限', 'index');
        }
      }
    }
//    $this->system = F('System');
//    $this->categorys = F('Category');
//    $this->module = F('Module');
//    $this->mod = F('Mod');
//    $this->role = F('Role');
//    $this->cache_model = array('Module', 'Role', 'Category', 'Posid', 'Field', 'System');
//    if (empty($this->system)) {
//      foreach ($this->cache_model as $r) {
//        savecache($r);
//      }
//    }
    $this->redis = \RedisHelper::instance();
  }

  //空操作
  public function _empty()
  {
    return $this->error('空操作，返回上次访问页面中...');
  }

  /**
   * 用户文件上传到oss
   */
  protected function upload($imgs,$base_url=''){
    try{
      foreach ($imgs as $k=>$v){
        //正则匹配
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $v, $result)) {
          //生成本地文件
          $type = $result[2];
          $new_file = "./uploads/";
          $new_file .= 'ad_'.$k.".{$type}";
          file_put_contents($new_file, base64_decode(str_replace($result[1], '', $v)));
          //上传oss
          require_once APP_PATH.'../extend/aliyun-oss-php-sdk/autoload.php';
          $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//去阿里云后台获取秘钥
          $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//去阿里云后台获取秘钥
          $endpoint = config('OSS_GLOBAL')['endpoint'];//你的阿里云OSS地址
          $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
          $bucket= config('OSS_GLOBAL')['bucket'];//oss中的文件上传空间
          $object = $base_url.$k.".{$type}";//想要保存文件的名称
          //添加图片路径数组
          $file = $new_file;//文件路径，必须是本地的。
          $ossClient->uploadFile($bucket,$object,$file);
          unlink($new_file);
          $img_data[$k] = $object;
        }
      }
      return ["code" => "1", "msg" => '上传成功！','data'=>$img_data];
    }catch (Exception $e){
      Log::error($e->getMessage());
      return ["code" => "0", "msg" => 'oss上传失败请重试！','data'=>$e->getMessage()];
    }
  }

  //清除富文本内容的宽、高、样式
  function filterHTML($html)
  {
    $html = preg_replace('/width=".*?"/', '', $html);
    $html = preg_replace('/height=".*?"/', '', $html);
    $html = preg_replace('/style=".*?"/', '', $html);
    return $html;
  }
}
