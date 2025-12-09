<?php
/**
 * Created by PhpStorm.
 * User: feisha
 * Date: 2018/7/4
 * Time: 下午5:32
 */

namespace app\push\controller;

use GatewayWorker\Lib\Gateway;
use RedisHelper;
use think\cache\driver\Redis;
use think\Db;
use think\Exception;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
  /**
   * @var RedisHelper
   */
  private static $redis;

//  /**
//   * Events constructor.
//   */
//  function __construct()
//  {
//    self::$redis = RedisHelper::instance();
//  }

  /**
   * 当客户端发来消息时触发
   * @param int $client_id 连接id
   * @param mixed $data 具体消息
   * @return bool|void
   * @throws Exception
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  public static function onMessage($client_id, $data)
  {
    echo "client: {$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']}\n";
    echo "gateway: {$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}\n";
    echo "client_id: $client_id session: " . json_encode($_SESSION) . "\n";
    echo "onMessage: " . $data . "\n";

    $message = json_decode($data, true);
    if (!$message) {
      echo "onMessage data is null.";
      return;
    }

    Db::clear();
    self::$redis = RedisHelper::instance();

    if ($message['type'] == 'init') {
      if (!self::chkToken($message['token'], $message['session_id'])) {
//        Gateway::sendToCurrentClient(['message_type' => 'init', 'code' => 0, 'msg' => '登录超时']);
//        return;
        return Gateway::sendToCurrentClient(['message_type' => 'logout']);
      }

      // uid
      $uid = $message['id'];
      // 设置 session
      $_SESSION = [
        'id' => $uid,
        'username' => $message['username'],
        'avatar' => $message['avatar'],
        'sign' => $message['sign']
      ];
//        session('id', $uid);
//        session('username', $message['username']);
//        session('avatar', $message['avatar']);
//        session('sign', $message['sign']);

      $message['online'] = 1;
      self::$redis->hMset('user:' . $uid, $message);

      // 将当前链接与uid绑定
      Gateway::bindUid($client_id, $uid);

      // 通知当前客户端初始化
      $init_message = array(
        'id' => $uid,
        'message_type' => 'init',
      );
      Gateway::sendToCurrentClient(json_encode($init_message));

      // 查询最近1周有无需要推送的离线信息
      $time = time() - 7 * 3600 * 24;
      $logs = Db::name('log_chat')->where(['uid' => $uid, 'addtime' => ['>', $time], 'type' => 'friend', 'offline' => 1])->field('id, from_id, from_name, from_avatar, content, addtime')->order('id ASC')->select();
      echo Db::name('log_chat')->getLastSql();
      if ($logs) {
        foreach ($logs as $k => $v) {
          $log = ['message_type' => 'logMessage',
            'data' => [
              'id' => $v['from_id'],
              'username' => $v['from_name'],
              'avatar' => $v['from_avatar'],
              'type' => 'friend',
              'content' => $v['content'],
              'timestamp' => $v['addtime'] * 1000
            ]
          ];
          Gateway::sendToCurrentClient(json_encode($log));

          Db::name('log_chat')->where('id', $v['id'])->update(['offline' => 0]);
        }
      }

      $groups = Db::name('group_user')->where(['user_id' => $uid, 'status' => 1])->field('group_id')->select();
      foreach ($groups as $k => $v) {
        Gateway::joinGroup($client_id, $v['group_id']);
      }
      unset($groups);
    }

    if (!$_SESSION['id']) return Gateway::sendToCurrentClient(['message_type' => 'logout']);

    switch ($message['type']) {
//      case 'init':
//
//        break;
      case 'chatChange':
//        $uid = $_SESSION['id']; // session('id'); //
//        $tid = $message['tid'];
//        if ($message['message_type'] == 'friend') {
//          $user = self::getFriend($uid, $tid);
//          if (!$user) {
//            echo("{$uid} and {$tid} are NOT FRIEND.");
//            return;
//          }
//
//          return Gateway::sendToCurrentClient(json_encode($message));
//          // Gateway::sendToUid($tid, json_encode(['type' => $message['type'], ['client_id' => $client_id, 'client_name' => $_SESSION['username'], 'time' => time()]]));
//        } else if ($message['message_type'] == 'group') {
//          $group = self::getUserGroup($tid, $uid);
//          if (!$group) {
//            echo("YOU HAVE NOT JOINED GROUP({$tid})");
//            return;
//          }
//
//          Gateway::joinGroup($client_id, $tid);
////          if (!$_SESSION['group_' . $tid]) { // session('group_' . $tid)
////            Gateway::joinGroup($client_id, $tid);
////            $_SESSION['group_' . $tid] = true;
//////            session('group_' . $tid, true);
////          }
//          return Gateway::sendToCurrentClient(json_encode($message));
//          // Gateway::sendToGroup($tid, json_encode(['type' => $message['type'], 'client_id' => $client_id, 'client_name' => $_SESSION['username'], 'time' => time()]));
//        }
        break;
      case 'chatMessage':
        // {"type":"chatMessage","message_type":"friend","content":"1","tid":"121286"}
        // 聊天消息
        $message_type = $message['message_type'];
        $tid = $message['tid'];

        $chat_message = [
          'message_type' => 'chatMessage',
          'data' => [
            'id' => ($message_type == 'friend' ? $_SESSION['id'] : $tid), // session('id'), //
            'username' => $_SESSION['username'], // session('username'), //
            'avatar' => $_SESSION['avatar'], // session('avatar'), //
            'type' => $message_type,
            'content' => htmlspecialchars($message['content']),
            'timestamp' => time() * 1000,
          ]
        ];

        // 聊天记录数组
        $param = [
          'uid' => $tid,
          'from_id' => $_SESSION['id'], // session('id'), //
          'from_name' => $_SESSION['username'], // session('username'), //
          'from_avatar' => $_SESSION['avatar'], // session('avatar'), //
          'content' => htmlspecialchars($message['content']),
          'addtime' => time(),
          'offline' => 0
        ];

        switch ($message_type) {
          // 私聊
          case 'friend':
            // 插入
            if (empty(Gateway::getClientIdByUid($tid))) {
              $param['offline'] = 1;  // 用户不在线, 标记此消息推送
            }
            $param['type'] = 'friend';
            Db::name('log_chat')->insert($param);

            echo "chatMessage: friend:" . json_encode($chat_message) . "\n";
            return Gateway::sendToUid($tid, json_encode($chat_message));
          // 群聊
          case 'group':
            $group = self::getGroup($tid);
            if (!$group) return Gateway::sendToCurrentClient(['message_type' => 'fail', 'data' => 'Group ' . $tid . ' NOT exists.']);

//            unset($chat_message['data']['username']);
            $chat_message['data']['group_avatar'] = $group['avatar'] ? config('OSS_GLOBAL.oss_url') . $group['avatar'] : 'assets/img/portrait/group_default.png';
            $chat_message['data']['groupname'] = $group['groupname'];
            $param['type'] = 'group';
            Db::name('log_chat')->insert($param);

            echo "chatMessage: group:" . json_encode($chat_message) . "\n";
            return Gateway::sendToGroup($tid, json_encode($chat_message), $client_id); //
        }
        break;

//      case 'addUser':
//        // 添加用户
//        $add_message = [
//          'message_type' => 'addUser',
//          'data' => [
//            'type' => 'friend',
//            'avatar' => $message['data']['avatar'],
//            'username' => $message['data']['username'],
//            'groupid' => $message['data']['groupid'],
//            'id' => $message['data']['id'],
//            'sign' => $message['data']['sign']
//          ]
//        ];
//        Gateway::sendToAll(json_encode($add_message), null, $client_id);
//        break;
//      case 'delUser' :
//        //删除用户
//        $del_message = [
//          'message_type' => 'delUser',
//          'data' => [
//            'type' => 'friend',
//            'id' => $message['data']['id']
//          ]
//        ];
//        Gateway::sendToAll(json_encode($del_message), null, $client_id);
//        break;
//      case 'addGroup':
//        //添加群组
//        $uids = explode(',', $message['data']['uids']);
//        $client_id_array = [];
//        foreach ($uids as $vo) {
//          $ret = Gateway::getClientIdByUid($vo);  //当前组中在线的client_id
//          if (!empty($ret)) {
//            $client_id_array[] = $ret['0'];
//
//            Gateway::joinGroup($ret['0'], $message['data']['id']);  //将这些用户加入群组
//          }
//        }
//        unset($ret, $uids);
//
//        $add_message = [
//          'message_type' => 'addGroup',
//          'data' => [
//            'type' => 'group',
//            'avatar' => $message['data']['avatar'],
//            'id' => $message['data']['id'],
//            'groupname' => $message['data']['groupname']
//          ]
//        ];
//        Gateway::sendToAll(json_encode($add_message), $client_id_array, $client_id);
//        break;
//      case 'joinGroup':
//        //加入群组
//        $uid = $message['data']['uid'];
//        $ret = Gateway::getClientIdByUid($uid); //若在线实时推送
//        if (!empty($ret)) {
//          Gateway::joinGroup($ret['0'], $message['data']['id']);  //将该用户加入群组
//
//          $add_message = [
//            'message_type' => 'addGroup',
//            'data' => [
//              'type' => 'group',
//              'avatar' => $message['data']['avatar'],
//              'id' => $message['data']['id'],
//              'groupname' => $message['data']['groupname']
//            ]
//          ];
//          Gateway::sendToAll(json_encode($add_message), [$ret['0']], $client_id);  //推送群组信息
//        }
//        break;
//      case 'addMember':
//        //添加群组成员
//        $uids = explode(',', $message['data']['uid']);
//        $client_id_array = [];
//        foreach ($uids as $vo) {
//          $ret = Gateway::getClientIdByUid($vo);  //当前组中在线的client_id
//          if (!empty($ret)) {
//            $client_id_array[] = $ret['0'];
//
//            Gateway::joinGroup($ret['0'], $message['data']['id']);  //将这些用户加入群组
//          }
//        }
//        unset($ret, $uids);
//
//        $add_message = [
//          'message_type' => 'addGroup',
//          'data' => [
//            'type' => 'group',
//            'avatar' => $message['data']['avatar'],
//            'id' => $message['data']['id'],
//            'groupname' => $message['data']['groupname']
//          ]
//        ];
//        Gateway::sendToAll(json_encode($add_message), $client_id_array, $client_id);  //推送群组信息
//        break;
//      case 'removeMember':
//        //将移除群组的成员的群信息移除，并从讨论组移除
//        $ret = Gateway::getClientIdByUid($message['data']['uid']);
//        if (!empty($ret)) {
//
//          Gateway::leaveGroup($ret['0'], $message['data']['id']);
//
//          $del_message = [
//            'message_type' => 'delGroup',
//            'data' => [
//              'type' => 'group',
//              'id' => $message['data']['id']
//            ]
//          ];
//          Gateway::sendToAll(json_encode($del_message), [$ret['0']], $client_id);
//        }
//        break;
//      case 'delGroup':
//        //删除群组
//        $del_message = [
//          'message_type' => 'delGroup',
//          'data' => [
//            'type' => 'group',
//            'id' => $message['data']['id']
//          ]
//        ];
//        Gateway::sendToAll(json_encode($del_message), null, $client_id);
//        break;
//      case 'hide':
//      case 'online':
//        $status_message = [
//          'message_type' => $message['type'],
//          'id' => $_SESSION['id'],
//        ];
//        $_SESSION['online'] = $message['type'];
//        Gateway::sendToAll(json_encode($status_message));
//        break;
      case 'ping':
        $session = $_SESSION;
        $session['timestamp'] = time();
        return Gateway::sendToCurrentClient(json_encode(['message_type' => 'ping', 'data' => $session]));
        break;
      default:
        echo("unknown message $data");
        return;
    }
  }

  /**
   * 判断登录状态
   * @param $token
   * @param $sid
   * @return bool
   * @throws Exception
   */
  private static function chkToken($token, $sid)
  {
    try {
      $arr = explode('+', authcode($token, 'DECODE'));
      if (!$arr[0])
        throw new Exception('error token.');

      $session_id = self::$redis->hGet('user:' . $arr[0], 'session');

      return md5(md5($session_id)) == $sid;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * @param $uid
   * @return array|false|\PDOStatement|string|\think\Model
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  private static function getUser($uid)
  {
    $user = self::$redis->hMget('user:' . $uid);
    if (!$user) {
      $user = Db::name('users')->field('`id`,`username`,`avatar`,`sign`,`online`')->where(['status' => 1, 'deleted' => 0, 'id' => $uid])->find();
      if (!$user) {
        return false;
      }
//      self::$redis->hMset('user:' . tid, $user);
    }

    return $user;
  }

  /**
   * @param $uid
   * @param $tid
   * @return array|false|\PDOStatement|string|\think\Model
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  private static function getFriend($uid, $tid)
  {
    $isFriend = self::$redis->sIsMember('user:friend:' . $uid, $tid);
    if ($isFriend) return self::getUser($tid);

    $user = Db::name('user_friend')->alias('uf')->join('users u', 'uf.fid = u.id')->where("uf.user_id = $uid and uf.fid = $tid and uf.status = 1")->field('u.id, u.username, u.mobile, u.avatar, u.sign, u.addtime, uf.note_name')->find();
    if (!$user) return false;

    // 缓存好友关系
    self::$redis->set('user:friend:' . $uid, $tid);
    // 缓存用户
//    self::$redis->hMSet('user:' . $tid, $user);

    return $user;
  }

  /**
   * @param $gid
   * @return array|false|\PDOStatement|string|\think\Model
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  private static function getGroup($gid)
  {
    $group = self::$redis->hMget('group:' . $gid);
    if ($group) return $group;

    $group = Db::name('group')->field('`id`, `avatar`, `groupname`, `addtime`')->where(['id' => $gid, 'status' => 1])->find();
    if (!$group) return false;

    self::$redis->hMset('group:' . $gid, $group);

    return $group;
  }

  /**
   * @param $gid
   * @param $uid
   * @return array|false|\PDOStatement|string|\think\Model
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  private static function getUserGroup($gid, $uid)
  {
    $inGroup = self::$redis->sIsMember('group:user:' . $gid, $uid);
    if ($inGroup) return self::getGroup($gid);

    $group = Db::name('group')->alias('g')->join('group_user gu', 'g.id = gu.group_id')->where("g.id = $gid AND gu.user_id = $uid AND gu.status = 1")->field('g.`id`, g.`groupname`, g.`avatar`, gu.`note_name`, gu.`addtime`')->find();
    if (!$group) return false;

    // 缓存群组
    self::$redis->hMset('group:' . $gid, $group);

    return $group;
  }

  /**
   * 当客户端连接时触发
   * 如果业务不需此回调可以删除onConnect
   *
   * @param int $client_id 连接id
   */
  public static function onConnect($client_id)
  {
  }

  /**
   * 当连接断开时触发的回调函数
   * @param $client_id
   * @throws \Exception
   */
  static function onClose($client_id)
  {
//    session_destroy();
//    $_SESSION['id'] = null;
    $logout_message = [
      'message_type' => 'close',
      'id' => $_SESSION['id'] // session('id') //
    ];
    Gateway::sendToAll(json_encode($logout_message));
  }

  /**
   * 当客户端的连接上发生错误时触发
   * @param $connection
   * @param $code
   * @param $msg
   */
  static function onError($client_id, $code, $msg)
  {
    echo "error $code $msg\n";
  }

  /**
   * 每个进程启动
   * @param $worker
   */
  public static function onWorkerStart($worker)
  {
//    echo var_export($worker, true) . " started.";
  }
}
