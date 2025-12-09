<?php
/**
 * Created by PhpStorm.
 * User: feisha
 * Date: 2018/7/4
 * Time: 下午5:31
 */

namespace app\push\controller;

use Workerman\Worker;
use GatewayWorker\Register;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;

class Run
{
  /**
   * 构造函数
   * @access public
   */
  public function __construct()
  {
    // 由于是手动添加，因此需要注册命名空间，方便自动加载，具体代码路径以实际情况为准
    \think\Loader::addNamespace([
      'Workerman' => VENDOR_PATH . 'Workerman/workerman',
      'GatewayWorker' => VENDOR_PATH . 'Workerman/gateway-worker/src',
    ]);

    // 初始化各个GatewayWorker

    // 初始化register
    new Register('text://0.0.0.0:1238');

    //初始化 bussinessWorker 进程
    $worker = new BusinessWorker();
    $worker->name = 'WebIMBusinessWorker';
    $worker->count = 4;
    $worker->registerAddress = '127.0.0.1:1238';

    // 设置处理业务的类,此处制定Events的命名空间
    $worker->eventHandler = '\app\push\controller\Events';

    // 初始化 gateway 进程
    $context = array(
      // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
      'ssl' => array(
        // 请使用绝对路径
        'local_cert' => '/home/cert/ssl.pem', // 也可以是crt文件
        'local_pk' => '/home/cert/ssl.key',
        'verify_peer' => false,
        // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
      )
    );
    $gateway = new Gateway("websocket://0.0.0.0:8282", $context);
    $gateway->name = 'WebIMGateway';
    $gateway->count = 4;
    $gateway->lanIp = '127.0.0.1';
//        $gateway->startPort = 2900;
    $gateway->transport = 'ssl';
    $gateway->registerAddress = '127.0.0.1:1238';

    //运行所有Worker;
    Worker::runAll();
  }
}