<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 我的
 * Class User
 * @package app\api\controller
 */
class User extends Base
{

    /**
     * 退出登陆
     * @param $token
     */

    public function logout($token)
    {
        try {
            session('session_id', null);
            session($this->user_id . '.' . $this->user['mobile'], null);
            session($this->user_id . '.' . $this->user['email'], null);
            $this->redis->hDel('user:' . $this->user_id, 'session');
            cache('cache_userinfo_' . $this->user_id, null);
            $this->apiReply(1, '操作成功！');
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 获取用户信息
     * @param $token
     */
    public function user_refresh($token)
    {
      $userInfo = $this->loadUser($this->user_id);
      cache('cache_userinfo_' . $this->user_id, $this->user);
      $userInfo['token'] = $token;
      $this->apiResp($userInfo);
    }

//    /**
//     * 超级节点收益记录
//     * @param $token
//     * @param $page
//     * @param $pageSize
//     */
//    public function SuperNode($token, $page = 1, $pageSize = 10)
//    {
//        $data['list'] = Db('returns_vip')->where(['user_id' => $this->user_id])->field('id,UNIX_TIMESTAMP(`date`) as addtime,returns,status,vip_node')->order('id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
//
//        $data['vip_node'] = Db('user_rela')->where(['user_id' => $this->user_id])->value('vip_node');
//
//        $this->apiResp($data);
//    }
//
//    /**
//     * 超级节点提取
//     * @param $token
//     * @param $id
//     */
//    public function SuperExtract($token, $id)
//    {
//
//
//        Db::startTrans();
//        try {
//
//            $returns_vip = Db('returns_vip')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('user_id,returns')->find();
//
//            if (!$returns_vip) $this->apiReply(2, '请不要重复提取!|Please do not repeat the extract!');
//
//            $temp = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('temp');
//
//            Db('user_account')->where(['user_id' => $this->user_id])->update(['temp' => bcadd($temp, $returns_vip['returns'], 8)]);
//
//            Db('returns_vip')->where(['id' => $id, 'user_id' => $this->user_id])->update(['status' => 1]);
//
//            //余额变动日志
//            Db('log_temp')->insertGetId([
//                'user_id' => $this->user_id,
//                'num' => $returns_vip['returns'],
//                'amount' => $temp,
//                'balance' => bcadd($temp, $returns_vip['returns'], 8),
//                'addtime' => time(),
//                'status' => 1,
//                'type' => 890,
//                'union' => 'returns_vip',
//                'union_id' => $id,
//                'remark' => '提取超级节点'
//            ]);
//
//            Db('log_returns_lock')->insert(['id' => $id, 'user_id' => $this->user_id, 'num' => $returns_vip['returns'], 'union' => 'returns_vip']);
//
//            Db::commit();
//            $this->apiReply(1, '提取成功!|Extraction success!', ['id' => $id]);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//            Db::rollback();
//            $this->apiReply(2, '提取失败，请重试|Extract failed, please try again', ['id' => $id]);
//        }
//
//    }
//
//    /**
//     * 超级节点规则
//     * @param $token
//     */
//    public function SuperRule($token)
//    {
//        $this->apiResp(['title' => ' <li><i>1.</i><span>会员玩家分为V1、V2、V3、V4、V5五个等级;<br/>V1 等级玩家可以获取团队业绩20%的奖励;<br/>V2 等级玩家可以获取团队业绩15%的奖励;<br/>V3 等级玩家可以获取团队业绩10%的奖励;<br/>V4 等级玩家可以获取团队业绩5%的奖励;<br/></span></li><li><i>2.</i><span>会员玩家当日一级好友的锁仓总量低于或等于前一天的锁仓总量，则无法获得奖励收益。</span></li><li><i>3.</i><span>会员玩家获得的奖励收益手动提取转入活动钱包。</span></li>']);
//    }
//
//    /**
//     * 超级节点收益记录
//     * @param $token
//     * @param $page
//     * @param $pageSize
//     */
//    public function MyNode($token, $page = 1, $pageSize = 8)
//    {
//        $data = Db('returns_node')->where(['user_id' => $this->user_id])->field('id,UNIX_TIMESTAMP(`date`) as addtime,direct_lock,direct_lock_old,`status`,if(returns>0,returns,"无") as returns,if(direct_lock<=0,"否","是") as `type`')->order('id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
//
//        $this->apiResp($data);
//    }
//
//    /**
//     * 我的节点提取
//     * @param $token
//     * @param $id
//     */
//    public function MyNodeExtract($token, $id)
//    {
//
//
//        Db::startTrans();
//        try {
//
//            $returns_node = Db('returns_node')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('user_id,returns')->find();
//
//            if (!$returns_node) $this->apiReply(2, '请不要重复提取!|Please do not repeat the extract!');
//
//            $active = Db('user_account')->where(['user_id' => $this->user_id])->lock(true)->value('active');
//
//            Db('user_account')->where(['user_id' => $this->user_id])->update(['active' => bcadd($active, $returns_node['returns'], 8)]);
//
//            Db('returns_node')->where(['id' => $id, 'user_id' => $this->user_id])->update(['status' => 1]);
//
//            //余额变动日志
//            Db('log_activity')->insertGetId([
//                'user_id' => $this->user_id,
//                'num' => $returns_node['returns'],
//                'amount' => $active,
//                'balance' => bcadd($active, $returns_node['returns'], 8),
//                'addtime' => time(),
//                'status' => 1,
//                'type' => 668,
//                'union' => 'returns_vip',
//                'union_id' => $id,
//                'remark' => '提取我的节点'
//            ]);
//
//            Db('log_returns_lock')->insert(['id' => $id, 'user_id' => $this->user_id, 'num' => $returns_node['returns'], 'union' => 'returns_node']);
//
//            Db::commit();
//            $this->apiReply(1, '提取成功!|Extraction success!', ['id' => $id]);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//            Db::rollback();
//            $this->apiReply(2, '提取失败，请重试|Extract failed, please try again', ['id' => $id]);
//        }
//
//    }
//
//    /**
//     * 我的业绩
//     * @param $token
//     */
//    public function MyResults($token)
//    {
//
//        $data['lock_old'] = Db('user_account')->where(['user_id' => $this->user_id])->value('lock_old'); //昨天日业绩
//
//        $user_rela = Db('user_rela')->where(['user_id'=>$this->user_id])->field('lft,rgt,depth')->find();
//
//        $data['to_day'] = Db('user_rela')->alias('us')->join('log_temp te', 'te.user_id=us.user_id')->where(['us.lft' => ['gt',$user_rela['lft']],'us.rgt'=>['lt',$user_rela['rgt']], 'te.date' => date('Ymd'), 'te.type' => 880])->sum('te.num'); //今日业绩
//
//        $data['bonus'] = bcmul($data['to_day'], config('node_rate'), 8);
//
//        $data['to_day'] = $data['to_day'] + $data['lock_old'];
//
//        $this->apiResp($data);
//    }
//
//    /**
//     * 我的节点规则
//     * @param $token
//     */
//    public function MyRule($token)
//    {
//        $this->apiResp(['title' => ' <li><i>1.</i><span>会员玩家每日一级好友的锁仓总量高于前一天的锁仓总量，既可获得当日新增锁仓数量的5%作为奖励收益。</span></li><li><i>2.</i><span>会员玩家当日一级好友的锁仓总量低于或等于前一天的锁仓总量，则无法获得奖励收益。</span></li><li><i>3.</i><span>会员玩家获得的奖励收益需手动提取转入活动钱包。</span></li>']);
//    }
//
//    /**
//     * 节点判断
//     * @param $token
//     */
//    public function loadRela($token)
//    {
//
//        $data = Db('user_rela')->where(['user_id' => $this->user_id])->field('node,vip_node')->find();
//
//        $this->apiResp($data);
//    }

    /**
     * 帮助中心
     * @param $token
     * @param $page
     * @param $pageSize
     */
    public function LoadHelp($token, $page = 1, $pageSize = 8)
    {
        $data = Db('help')->field('question,question_en,id')->order('sort desc,id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
        $this->apiResp($data);
    }

    /**
     * 帮助详情
     * @param $id
     */
    public function LoadHelpDetails($token, $id)
    {
        $data = Db('help')->where(['id' => $id])->field('question,question_en,answer,answer_en')->cache(600)->find();
        $this->apiResp($data);
    }

    /**
     * 公告
     * @param $token
     * @param $page
     * @param $pageSize
     */
    public function LoadNotice($token, $page = 1, $pageSize = 10)
    {
        $data = Db('notice')->field('title,addtime,id')->where(['status' => 0])->order('id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
        $this->apiResp($data);
    }

    /**
     * 公告
     * @param $id
     */
    public function LoadNoticeDetails($token, $id)
    {
        $data = Db('notice')->where(['id' => $id])->field('title,addtime,id,content')->cache(600)->find();
        $this->apiResp($data);
    }


    /**
     * 我的页面
     * @param $token
     */
    public function load_User($token)
    {
        $paypass = Db('users')->where(['user_id' => $this->user_id, 'status' => 1])->value('paypass');
        if ($paypass) {
            $data['paypass'] = 1;
        } else {
            $data['paypass'] = 0;
        }

        $idcard = Db('idaudit')->where(['user_id' => $this->user_id])->field('status,addtime')->find();

        if ($idcard) {
            $data['idcard'] = $idcard['status'];
        } else {
            $data['idcard'] = 3;
        }

        $data['level'] = db('user_rela')->alias('a')
            ->join('node_level b', 'a.node_level_id=b.node_level_id')
            ->where(['user_id' => $this->user_id])
            ->field('b.name,a.vip_node')
            ->find();

        $this->apiResp($data);
    }


    /**
     * 绑定开户行
     * @param $token
     * @param $user_name
     * @param $bank_name
     * @param $card
     * @param $open_name
     * @param $mobile
     */
    public function bind_card($token, $card, $user_name, $bank_name, $open_name = '', $mobile)
    {
        $card = replaceSpecialChar($card);
        $user_name = replaceSpecialChar($user_name);
        $bank_name = replaceSpecialChar($bank_name);

        if ($card == '') $this->apiReply(2, '银行卡号不能为空|The bank card number cannot be empty');

        if ($user_name == '') $this->apiReply(2, '姓名不能为空|The name cannot be empty');

        if ($mobile == '') $this->apiReply(2, '手机号不能为空|The mobile cannot be empty');

        if ($bank_name == '') $this->apiReply(2, '开户银行名称不能为空|The bank name cannot be empty');

        Db::startTrans();
        try {

            if (Db('user_payment')->where(['user_id' => $this->user_id, 'type' => 3, 'bank_number' => $card, 'deleted' => 0, 'bank_username' => $user_name, 'bank_name' => $bank_name, 'bank_sub_name' => $open_name, 'mobile' => $mobile])->find()) {
                $this->apiReply(1, '操作成功|successfully', $card);
            }

            Db('user_payment')->where(['user_id' => $this->user_id, 'type' => 3])->update(['deleted' => 1]);

            Db('user_payment')->insert(['user_id' => $this->user_id, 'type' => 3, 'bank_number' => $card, 'deleted' => 0, 'addtime' => time(), 'bank_username' => $user_name, 'bank_name' => $bank_name, 'bank_sub_name' => $open_name, 'mobile' => $mobile]);

            Db::name('log_change_payment')->insert(['user_id' => $this->user_id, 'type' => 3, 'intro' => '修改银行卡', 'old' => $this->user['user_card'], 'new' => $card, 'addtime' => time()]);

            Db::commit();

            $this->user['user_card'] = $card;
            cache('cache_userinfo_' . $this->user_id, $this->user);
            $this->apiReply(1, '操作成功|successfully', $card);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！|Failed');
        }
    }


    /**
     * 查询用户银行卡信息
     * @param $token
     */
    public function user_card($token)
    {
        $data = Db('user_payment')->where(['user_id' => $this->user_id, 'deleted' => 0, 'type' => 3])->field('bank_number,bank_username,bank_name,bank_sub_name,mobile')->find() ?: '';
        $this->apiResp($data);
    }

    /**
     * 修改交易密码发送验证码
     * @param $token
     */
    public function marketPwd_sms($token)
    {

        // 发送验证码
        $type = 8;
        if ($this->user['code'] == 86) {
            $result = $this->sendSMS($this->user['mobile'], $type);
        } else {
            $result = $this->sendSMS_Global($this->user['mobile'], $type, $this->user['code']);
        }

        if ($result == 'success') {
            $action = array();
            $action['addtime'] = time();
            $action['type'] = $type;
            $action['data'] = $this->user['mobile'];
            session('gt_marketPwd', json_encode($action));
            $this->apiReply(1, '发送成功！', encryption(json_encode($action)));
        } else if ($result == 'exist') {
            $this->apiReply(1, '验证码还未过期，请使用刚才的验证码继续操作！|The verification code has not expired, please continue to operate with the verification code just now', encryption(session('gt_marketPwd')));
        } else {
            $this->apiReply(null, $result);
        }
    }

    /**
     * 修改交易密码接口
     * @param $token
     * @param $newpswd
     */
    public function edit_marketPwd($token, $code, $newpswd, $action)
    {
        if (empty($newpswd)) $this->apiReply(2, '数据错误！|Data error');

        $action_id = session('gt_marketPwd');
        if (!$action_id || encryption($action_id) != $action) $this->apiReply(2, '请点击获取验证码！|Please click to get the verification code');

        $user = Db('users')->where(['user_id' => $this->user_id])->field('paypass,password')->find();

        $newpswd = md5($newpswd . config('password_str'));

        if ($newpswd == $user['paypass']) {
            $this->apiReply(2, '当前密码和新密码不能相同！|The current password and the new password cannot be the same');
        }

        if ($newpswd == $user['password']) {
            $this->apiReply(2, '支付密码和登陆密码不能相同！|Payment and login passwords cannot be the same');
        }

        // 手机验证码是否正确
        $validate = $this->validateSMS($this->user['mobile'], 8, $code, $this->user['code']);
        if ($validate != 'success') {
            $this->apiResp(null, $validate);
        }

        session('gt_marketPwd', null);

        Db::startTrans();
        try {
            Db('users')->where(['user_id' => $this->user_id])->update(['paypass' => $newpswd]);
            Db('log_change_pass')->insert(['user_id' => $this->user_id, 'type' => 417, 'intro' => '修改支付密码', 'old' => $user['paypass'] ?: '', 'new' => $newpswd, 'addtime' => time(), 'ip' => request()->ip(1)]);
            Db::commit();

            $this->apiReply(1, '操作成功|The transaction password was changed successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '操作失败！|Failed to change transaction password');
        }
    }

    /**
     * 修改密码接口
     * @param $token
     * @param $password
     * @param $newpswd
     */
    public function edit_password($token, $password, $newpswd)
    {
        if ($password == $newpswd) {
            $this->apiReply(2, '当前密码和新密码不能相同！|The current password and the new password cannot be the same!');
        }

        $user = Db('users')->field('`password`,`paypass`, `mobile`')->where(['user_id' => $this->user_id])->find();

        $newpswd = md5($newpswd . config('password_str'));


        if (md5($password . config('password_str')) != $user['password']) {
            $text = $this->login_log(0, $this->user['mobile'], '修改密码错误！', $this->user_id);
            $this->apiReply(2, $text);
        }

        if ($newpswd == $user['paypass']) {
            $this->apiReply(2, '交易密码和登陆密码不能相同！|Transaction password and login password cannot be the same!');
        }

        try {
            Db('users')->where(['user_id' => $this->user_id])->update(['password' => $newpswd]);
            Db('log_change_pass')->insert(['user_id' => $this->user_id, 'type' => 416, 'intro' => '修改登录密码', 'old' => $password, 'new' => $newpswd, 'addtime' => time(), 'ip' => request()->ip(1)]);
            Db('log_login')->where(['user_id' => $this->user_id, 'status' => 0])->setField('status', 2);
            Db::commit();
            $this->apiReply(1, '修改密码成功|Password changed successfully');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Db::rollback();
            $this->apiReply(2, '修改密码失败！|Password change failed!');
        }
    }

    /**
     * 查询用户实名信息
     * @param $token
     */
    public function bind_id_card($token)
    {
        $data = Db('idaudit')->where(['user_id' => $this->user_id])->field('id_name,id_card,status,remark,front_attach_id,back_attach_id,hand_attach_id')->find();
        if ($data) {
            $front = Db('attach')->where(['attach_id' => $data['front_attach_id']])->field('url,addtime')->find();
            $back = Db('attach')->where(['attach_id' => $data['back_attach_id']])->field('url,addtime')->find();
            $hand = Db('attach')->where(['attach_id' => $data['hand_attach_id']])->field('url,addtime')->find();
            $data['front_url'] = $front['url'];
            $data['front_time'] = $front['addtime'];
            $data['back_url'] = $back['url'];
            $data['back_time'] = $back['addtime'];
            $data['hand_url'] = $hand['url'];
            $data['hand_time'] = $hand['addtime'];
        }
        $this->apiResp($data);
    }

  /**
   * 修改用户名
   * @param $token
   * @param $username
   */
  public function edit_name($token,$username)
  {

    $username = replaceSpecialChar($username);

    //验证用户名
    if (!$username || !is_username($username)) {
      $this->apiReply(2, '请输入中文2-5位或英文2-8位的用户名!|Please input the user name of 2-5 characters in Chinese or 2-8 characters in English!');
    }
    if(Db('users')->where(['username' => $username])->count()){
      $this->apiReply(2, '用户名已存在!');
    };
    Db('users')->where(['user_id'=>$this->user_id])->update(['username'=>$username]);
//    //更新提现表 用户名称
//    Db('user_cash')->where(['user_id'=>$this->user_id])->update(['username'=>$username]);
//    //更新币币交易 用户名称
//    Db('trade')->where(['user_id'=>$this->user_id])->update(['username'=>$username]);
//    Db('log_trade')->where(['buyer_id'=>$this->user_id])->update(['buyer_username'=>$username]);
//    Db('log_trade')->where(['seller_id'=>$this->user_id])->update(['seller_username'=>$username]);
//
//    //更新C2C交易 用户名称
//    Db('market')->where(['user_id'=>$this->user_id])->update(['username'=>$username]);
//    Db('log_market')->where(['buyer_id'=>$this->user_id])->update(['buyer_username'=>$username]);
//    Db('log_market')->where(['seller_id'=>$this->user_id])->update(['seller_username'=>$username]);

    //增加修改日志

    Db('log_users')->insert(['intro'=>'修改用户名称','old'=>$this->user['username'],'new'=>$username,'addtime'=>time(),'user_id'=>$this->user_id]);

    $this->user['username'] = $username;

    cache('cache_userinfo_' . $this->user_id, $this->user);
    $this->apiReply(1, '修改成功|Changed successfully',['username'=>$username]);
  }

  /**
   * 问题反馈
   * @param $token
   * @param $content
   */
  public function AddWork($token,$content){
    $content = replaceSpecialChar($content);
    if (empty($content)) {
      $this->apiReply(2, '描述内容不能为空!');
    }
    if(Db('work')->insert(['user_id' => $this->user_id, 'content' => $content, 'img' => '',  'addtime' => time(),'status' => 0, 'status_two' => 0])){
      $this->apiReply(1, '反馈成功');
    }else{
      $this->apiReply(2, '反馈失败');
    }
  }

  /**
   * 合伙人分红提取
   * @param $token
   * @param int $id
   */
  public function to_bonus($token,$id){
    $bonus = Db('bonus')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->field('returns,coin_id,id')->find();
    if (!$bonus) $this->apiReply(2, '数据异常!');
    Db::startTrans();
    try {
      $balance = Db('user_coin')->where(['user_id' => $this->user_id,'coin_id'=>$bonus['coin_id']])->lock(true)->value('balance');
      $get_balance = bcadd($balance, $bonus['returns'], 4);
      //更新会员账户
      Db('user_coin')->where(['user_id' => $this->user_id,'coin_id'=>$bonus['coin_id']])->update([
        'balance' => $get_balance,
      ]);

      Db('bonus')->where(['id' => $id, 'user_id' => $this->user_id, 'status' => 0])->update([
        'status' => 1,
        'pick_time' => time(),
      ]);

      Db('log_coin')->insert([
        'user_id' => $this->user_id,
        'coin_id' => $bonus['coin_id'],
        'type' => 68,
        'num' => $bonus['returns'],
        'amount' => $balance,
        'balance' => $get_balance,
        'addtime' => time(),
        'status' => 0,
        'union' => 'bonus',
        'union_id' => $bonus['id'],
        'remark' => '提现合伙人入金分红' . $bonus['returns']
      ]);

      Db::commit();
      $this->apiReply(1, '提取成功！', ['savings_balance' => $get_balance]);
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '操作失败！', $e->getMessage());
    }
  }

  /**
   * 合伙人分红详情列表
   * @param $token
   * @param $id
   * @param int $page
   * @param int $pageSize
   */

  public function bonusDetails($token,$id,$page = 1, $pageSize = 10)
  {

    $date = Db('bonus')->where(['id'=>$id,'user_id'=>$this->user_id])->field('date,coin_id')->find();

    $data = Db('log_bonus')->alias('l')
      ->join('users b', 'b.user_id=l.cid')
      ->join('coin c', 'l.coin_id=c.coin_id')
      ->where(['l.user_id'=>$this->user_id,'l.date'=>$date['date'],'l.coin_id'=>$date['coin_id']])
      ->field("b.username,l.num,l.returns,l.addtime,c.name")
      ->order('l.id desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))
      ->toArray();
    $this->apiResp($data);
  }

  /**
   * 合伙人分红
   * @param $token
   * @param int $page
   * @param int $pageSize
   */

  public function LoadBonus($token, $page = 1, $pageSize = 10)
  {

    $data['list'] = Db('bonus')->alias('a')
      ->join('users b', 'a.user_id=b.user_id')
      ->join('coin c', 'a.coin_id=c.coin_id')
      ->where(['a.user_id'=>$this->user_id])
      ->field("b.username,a.num,a.returns,a.addtime,a.status,a.id,c.name")
      ->order('a.id desc')
      ->paginate(array('list_rows' => $pageSize, 'page' => $page))
      ->toArray();
    $data['coin_count'] = Db('bonus')->where(['user_id'=>$this->user_id])->sum('returns');
    $this->apiResp($data);
  }

//  public function LoadBonus($token, $page = 1, $pageSize = 10)
//  {
//
//    $rela = Db('user_rela')->where(['user_id'=>$this->user_id])->field('lft,rgt')->find();
//
//    $wheres = 'lft>'.$rela['lft'].' and rgt<'.$rela['rgt'].' and vip_node=1';
//
//    $array = Db('user_rela')->where($wheres)->field('lft,rgt')->order('lft asc')->select();
//    $lft ='a.lft>'.$rela['lft'];
//    $rgt ='a.rgt<'.$rela['rgt'];
//
//    if($array){
//      $where= '';
//       foreach ($array as $k=>$v){
//         $where .= ' and a.rgt<'.$v['lft'].' and a.lft>'.$v['rgt'];
//       }
//    }
//    if($where){
//      $map = $lft.$where.' and '.$rgt;
//    }else{
//      $map =  $lft.' and '.$rgt;
//    }
//
//    $data['list'] = Db('user_rela')->alias('a')
//      ->join('users b', 'a.user_id=b.user_id')
//      ->where($map)
//      ->field("b.mobile,b.username,a.coin_count")
//      ->order('a.addtime desc')
//      ->paginate(array('list_rows' => $pageSize, 'page' => $page))
//      ->toArray();
//    $data['coin_count'] = Db('user_rela')->alias('a')->where($map)->sum('coin_count')*0.1;
//    $this->apiResp($data);
//  }

//工单日志
  public function user_work($token,$page = 1,$pageSize = 4){

    $data = Db('work')->where(['user_id'=>$this->user_id])->field('work_id,title,addtime')->order('work_id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
    if ($data['data']) {
      $data['current_page'] = $page;
      $data['pageSize'] = $pageSize;
      $data['last_page'] = ceil($data['total'] / $pageSize);
      $this->apiResp($data);
    }else {
      $this->apiReply(2, '暂无数据！|No data！');
    }
  }

  /**
   * 工单日志
   * @param $token
   * @param $work_id
   * @param $page
   * @param $pageSize
   */
  public function log_work($token,$work_id,$page = 1,$pageSize = 4){

    $data['work'] = Db('work')->where(['work_id'=>$work_id,'user_id'=>$this->user_id])->field('work_id,title,content,img,addtime')->find();

    if($data['work']){
      Db('work')->where(['work_id'=>$work_id,'user_id'=>$this->user_id])->update(['status_two'=>0]);
    }

    $data['log'] = Db('log_work')->where(['work_id'=>$data['work']['work_id']])->field('if(type<1,"管理员",name)as name,addtime,content')->order('log_work_id desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
    $data['pageSize'] = $pageSize;

    $this->apiResp($data);
  }

  /**
   * 工单回复
   * @param $token
   * @param $work_id
   * @param $content
   */
  public function reply($token,$work_id,$content){
    $content = replaceSpecialChar($content);

    if (empty($content)) {
      $this->apiReply(2, '回复内容不能为空！|Open failure!');
    }

    Db::startTrans();
    try{
      if(!Db('work')->where(['work_id'=>$work_id,'user_id'=>$this->user_id])->count()){
        $this->apiReply(2, '数据异常！|Open failure!');
      }
      Db('log_work')->insert(['name'=>$this->user['username'],'content'=>$content,'type'=>1,'addtime'=>time(),'work_id'=>$work_id]);
      Db('work')->where(['work_id'=>$work_id,'user_id'=>$this->user_id])->update(['status'=>0,'status_two'=>0]);
      Db::commit();
      $this->apiReply(1,'回复成功！|Open successfully!');
    } catch (Exception $e) {
      Log::error($e->getMessage());
      Db::rollback();
      $this->apiReply(2, '回复失败！|Open failure!');
    }

  }

  public function service($token){
    $list = Db('config')->where(['name'=>'official_eamil'])->field('name, value')->find();

    $list['num']=Db('work')->where(['user_id'=>$this->user_id,'status_two'=>1])->count();
    $this->apiResp($list);
  }

}