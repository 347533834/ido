<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Log;

/**
 * 我的团队
 * Class Invite
 * @package app\api\controller
 */
class Invite extends Base
{
    /**
     * 我的直推
     * @param $token
     * @param int $page
     * @param int $pageSize
     */
    public function LoadFriend($token, $page = 1, $pageSize = 10)
    {
        $data = Db('user_rela')->alias('a')
            ->join('users b', 'a.user_id=b.user_id')
//            ->join('node_level c', 'a.node_level_id=c.node_level_id')
            ->join('idaudit i', 'i.user_id = a.user_id', 'left')
            ->where(['a.pid' => $this->user_id])
            ->field("a.addtime,b.mobile,b.username,i.id_name,i.status")
            // ->field("a.addtime,b.mobile,b.username,if(vip_node=1,'超级节点',c.name) as name")
//            ->group('a.user_id')
            ->order('a.addtime desc')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $data['team_total'] = db('user_rela')->where(['user_id' => $this->user_id])->value('team_total');
        $this->apiResp($data);
    }

    //我的团队
    public function LoadTeam($token, $page = 1, $pageSize = 10)
    {
        $user = db('user_rela')->where(['user_id' => $this->user_id])->find();
        $data = Db('user_rela')->alias('a')
            ->join('users b', 'a.user_id=b.user_id')
            ->join('idaudit i', 'i.user_id = a.user_id', 'left')
            ->where(['a.lft' => ['egt', $user['lft']], 'a.rgt' => ['elt', $user['rgt']], 'a.depth' => ['egt', $user['depth']], 'a.depth' => ['gt', 0]])
            ->field("a.addtime,b.mobile,b.username,i.id_name,i.status,a.buy_total,a.depth-{$user['depth']} depth")
            ->order('a.user_id')
            ->paginate(array('list_rows' => $pageSize, 'page' => $page))
            ->toArray();
        $data['direct'] = db('user_rela')->where(['pid' => $this->user_id])->count();
        $data['indirect'] = db('user_rela')->where(['lft' => ['gt', $user['lft']], 'rgt' => ['lt', $user['rgt']], 'depth' => ['gt', $user['depth']], 'pid' => ['neq', $this->user_id]])->count();
        $data['team_total'] = db('user_rela')->where(['user_id' => $this->user_id])->value('team_total');
        $data['user_level'] = db('user_rela')->where(['user_id' => $this->user_id])->value('final_level');
        $this->apiResp($data);
    }

    /**
     * 分享
     * @param $token
     */
    public function LoadInvite($token, $lang = 'cn')
    {
        //判断是否已经生成过
        $url = db('invite_bg')->where(['user_id' => $this->user_id, 'lang' => $lang])->value('url');
        if ($url) {
            $this->apiReply(1, '操作成功！|Successful operation!', $url);
        } else {
            $user = $this->user;
            ob_end_clean();
            $image = imagecreatefrompng('./share/base/invite1.png');
            $image_msg = getimagesize('./share/base/invite1.png');
            //判断生成验证码
            $this->check_qrcode();

            //验证码缩放 水印
            $qrcode = imagecreatefromstring(file_get_contents(config('OSS_GLOBAL')['oss_url'] . '/share/' . $this->user_id . '/code_' . $this->user_id . '.png'));
            $qrcode_msg = getimagesize(config('OSS_GLOBAL')['oss_url'] . '/share/' . $this->user_id . '/code_' . $this->user_id . '.png');
            $new_width = 390;
            $new_height = 390;
            $qrcode_new = imagecreatetruecolor($new_width, $new_height);
            imagecopyresized($qrcode_new, $qrcode, 0, 0, 0, 0, $new_width, $new_height, $qrcode_msg[0], $qrcode_msg[1]);

            imagecopy($image, $qrcode_new, 180, 540, 0, 0, $new_width, $new_height);

            $text_color = imagecolorallocate($image, 255, 255, 255);
            $fontfile = './share/font/msyh.ttc';

            //添加文字
            if ($lang == 'cn') {
                $text = '我的邀请码';
            } else {
                $text = "My invite code";
            }
            //计算文字位子
            $fontSize = 24;
            $text_arr = imagettfbbox($fontSize, 0, $fontfile, $text);//获取文子虚拟框数据
            $x = ceil(($image_msg[0] - ($text_arr[2] - $text_arr[0])) / 2); //计算文字的水平位置
            imagettftext($image, $fontSize, 0, $x, 250, $text_color, $fontfile, $text);//写入文字

            //添加文字
            $text = $user['invite'];
            //计算文字位子
            $fontSize = 36;
            $text_arr = imagettfbbox($fontSize, 0, $fontfile, $text);//获取文子虚拟框数据
            $x = ceil(($image_msg[0] - ($text_arr[2] - $text_arr[0])) / 2); //计算文字的水平位置
            imagettftext($image, $fontSize, 0, $x, 340, $text_color, $fontfile, $text);//写入文字

            //添加文字
            if ($lang == 'cn') {
                $text = '我是All Win永久居民 ' . $user['username'];
            } else {
                $text = "I am " . $user['username'] . ", a resident of All Win.";
            }
            //计算文字位子
            $fontSize = 24;
            $text_arr = imagettfbbox($fontSize, 0, $fontfile, $text);//获取文子虚拟框数据
            $x = ceil(($image_msg[0] - ($text_arr[2] - $text_arr[0])) / 2); //计算文字的水平位置
            imagettftext($image, $fontSize, 0, $x, 440, $text_color, $fontfile, $text);//写入文字

            //添加文字
            if ($lang == 'cn') {
                $text = '邀请你入驻All Win一起见证奇迹';
            } else {
                $text = 'I invite you to witness the miracle!';
            }
            $fontSize = 24;
            //计算文字位子
            $text_arr = imagettfbbox($fontSize, 0, $fontfile, $text);//获取文子虚拟框数据
            $x = ceil(($image_msg[0] - ($text_arr[2] - $text_arr[0])) / 2); //计算文字的水平位置
            imagettftext($image, $fontSize, 0, $x, 485, $text_color, $fontfile, $text);//写入文字

            //添加文字
            if ($lang == 'cn') {
                $text = '立即扫码，下载注册';
            } else {
                $text = 'Scan registration immediately';
            }
            //计算文字位子
            $fontSize = 36;
            $text_arr = imagettfbbox($fontSize, 0, $fontfile, $text);//获取文子虚拟框数据
            $x = ceil(($image_msg[0] - ($text_arr[2] - $text_arr[0])) / 2); //计算文字的水平位置
            imagettftext($image, $fontSize, 0, $x, 1020, $text_color, $fontfile, $text);//写入文字

            //输出图片
            header("Content-type: image/png");
            imagepng($image, './share/' . $lang . '_' . $this->user_id . '.png');
            imagedestroy($qrcode);
            imagedestroy($qrcode_new);
            imagedestroy($image);
            $this->share_upload($lang . '_' . $this->user_id . '.png');
            unlink('./share/' . $lang . '_' . $this->user_id . '.png');
            $url = config('OSS_GLOBAL')['oss_url'] . '/share/' . $this->user_id . '/' . $lang . '_' . $this->user_id . '.png?' . time();
            db('invite_bg')->insert(['user_id' => $this->user_id, 'lang' => $lang, 'url' => $url, 'addtime' => time()]);
            $this->apiReply(1, '操作成功！|Successful operation!', $url);
        }
    }

    protected function check_qrcode()
    {
        Vendor('phpqrcode');
        $url = 'http://comc.jiduobao.cc/#register?invite=' . $this->user['invite'];                  //二维码内容
        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 5;           //生成图片大小
        $margin = 1;
        //生成二维码图片
        $qrcodeObj = new \QRcode();
        $QR = './share/code_' . $this->user_id . '.png';
        $qrcodeObj::png($url, $QR, $errorCorrectionLevel, $matrixPointSize, $margin);
        $this->share_upload('code_' . $this->user_id . '.png');
        unlink('./share/code_' . $this->user_id . '.png');
    }

    /**
     * 用户文件上传到oss
     */
    protected function share_upload($new_file)
    {
        try {
            //上传oss
            require_once APP_PATH . '../extend/aliyun-oss-php-sdk/autoload.php';
            $accessKeyId = config('OSS_GLOBAL')['accessKeyId'];//"LTAIsiVDsgSzuMK6";//去阿里云后台获取秘钥
            $accessKeySecret = config('OSS_GLOBAL')['accessKeySecret'];//"73qeOgr47GeVtLsM97ofQKp03k0MSp";//去阿里云后台获取秘钥
            $endpoint = config('OSS_GLOBAL')['endpoint'];//"http://oss-cn-hangzhou.aliyuncs.com";//你的阿里云OSS地址
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $bucket = config('OSS_GLOBAL')['bucket'];//"matrixcdn";//oss中的文件上传空间
            $object = 'share/' . $this->user_id . '/' . $new_file;//想要保存文件的名称
            //添加图片路径数组
            $file = './share/' . $new_file;//文件路径，必须是本地的。
            $ossClient->uploadFile($bucket, $object, $file);
            unlink($new_file);
            return ["code" => "1", "msg" => '上传成功！|Upload successful!', 'data' => $object];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ["code" => "2", "msg" => 'oss上传失败请重试！|Oss upload failed please try again!', 'data' => $e->getMessage()];
        }
    }

    public function load_rule()
    {
        $data = '<li>
                <i></i>
                <span>
                    扫码注册并实名认证通过，交易所送3枚USDT，邀请用户注册送0.5枚USDT
                </span>
            </li>
            <!--<li>
                <i></i>
                <span>
                    币币交易上午场，推荐用户在交易区进行交易后，推荐者交易时享受优先交易权。推荐越多交易匹配越多
                </span>
            </li>-->
            <li>
                <i></i>
                <span>
                    推荐用户注册，享受交易手续费30%分红
                </span>
            </li>
            <li>
                <i></i>
                <span>
                    更多福利，敬请期待……
                </span>
            </li>';

        $this->apiReply(1, '操作成功！', $data);
    }


}