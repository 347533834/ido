<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/8
 * Time: 20:53
 */

namespace app\api\job;

use think\Db;
use think\Exception;
use think\Log;
use think\Queue;
use think\queue\Job;

class UserRela {

    /**
     * @param Job $job
     * @param array $data
     */
    public function fire(Job $job, array $data) {
        echo 1;die;
        $user_id = $data['user_id'];
        $pid = $data['pid'];
        print("user_id: {$data['user_id']}\n");
        try {
            $inviter = Db::connect(config('cli_db'))->name('user_rela')->where(['user_id' => $pid])->field('`user_id`,`lft`,`rgt`,`depth`')->find();
            Db::connect(config('cli_db'))->name('user_rela')->where('lft', '>', $inviter['rgt'])->setInc('lft', 2);
            Db::connect(config('cli_db'))->name('user_rela')->where('rgt', '>=', $inviter['rgt'])->setInc('rgt', 2);
            Db::connect(config('cli_db'))->name('user_rela')->where(['user_id'=> $user_id])->update(['lft'=>$inviter['rgt'],'rgt'=>$inviter['rgt']+1,'depth'=>$inviter['depth']+1]);
            Db::connect(config('cli_db'))->name('user_rela')->where(['lft'=>['lt', $inviter['rgt']],'rgt'=>['gt',$inviter['rgt']+1]])->setInc('team');
            $job->delete();
            return;
        }catch (Exception $e) {
            $job->release(2);
            return;
        }
    }

    private function failed() {
    }

    /**
     * 日志方法
     * @param $file
     * @param $e
     */
    private function log($file, $e) {
        file_put_contents(ROOT_PATH . 'runtime/queue/' . $file . '_' . date('YmdHis') . '.log', date('Y-m-d H:i:s') . "\n" . print_r($e, true) . "\n", FILE_APPEND);
    }
}