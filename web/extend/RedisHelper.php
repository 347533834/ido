<?php

/**
 * Redis
 */
class RedisHelper {

    private $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 12,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
    ];

    /**
     * 单例
     * @var
     */
    private static $instance;
    public $handler;

    public static function instance($options = []) {
        if (self::$instance == null) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    private function __construct($options = []) {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = $options;
        } else {
            $this->options = config('redis');
        }
        $func = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 删除key
     * @param $key
     * @return int
     */
    public function del_key($key) {
        return $this->handler->del($key);
    }

    /**
     * @param $pattern
     * @return array
     */
    public function keys($pattern) {
        return $this->handler->keys($pattern);
    }

    /**
     * STRING
     */

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name) {
        return $this->handler->get($name) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = false) {
        $value = $this->handler->get($name);
        if (is_null($value)) {
            return $default;
        }
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
        return (null === $jsonData) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param integer $expire 有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }

        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1) {
        return $this->handler->incrby($name, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1) {
        return $this->handler->decrby($name, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function del($name) {
        return $this->handler->delete($name);
    }

    /**
     * LIST
     */

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function lPush($key, $val) {
        return $this->handler->lPush($key, $val);
    }

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function rPush($key, $val) {
        return $this->handler->rPush($key, $val);
    }

    /**
     * @param $key
     */
    public function lPop($key) {
        return $this->handler->lPop($key);
    }

    /**
     * @param $key
     */
    public function rPop($key) {
        return $this->handler->rPop($key);
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return array
     */
    public function lRange($key, $start, $end) {
        return $this->handler->lRange($key, $start, $end);
    }

    /**
     * @param $key
     * @param $idx
     * @return String
     */
    public function lIndex($key, $idx) {
        return $this->handler->lIndex($key, $idx);
    }

    /**
     * SET
     */

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function sAdd($key, $val) {
        return $this->handler->sAdd($key, $val);
    }

    /**
     * @param $key
     * @param $arr
     */
    public function sAddArr($key, $arr) {
        $this->handler->sAddArray($key, $arr);
    }

    /**
     * @param $key
     * @return array
     */
    public function sMembers($key) {
        return $this->handler->sMembers($key);
    }

    /**
     * @param $key
     * @param $val
     * @return bool
     */
    public function sIsMember($key, $val) {
        return $this->handler->sIsMember($key, $val);
    }

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function sRem($key, $val) {
        return $this->handler->sRem($key, $val);
    }

    /**
     * HASH
     */

    /**
     * @param $key
     * @param $col
     * @param $val
     * @return int
     */
    public function hSet($key, $col, $val) {
        return $this->handler->hSet($key, $col, $val);
    }

    /**
     * Hash col 自增 int
     * @param $key
     * @param $col
     * @param $step
     * @return int
     */
    public function hIncrBy($key, $col, $step) {
        return $this->handler->hIncrBy($key, $col, $step);
    }

    /**
     * Hash col 自增 float
     * @param $key
     * @param $col
     * @param $step
     * @return float
     */
    public function hIncrByFloat($key, $col, $step) {
        return $this->handler->hIncrByFloat($key, $col, $step);
    }

    /**
     * 批量写入
     * @param $key
     * @param array $val
     * @param int $ttl
     * @return bool
     */
    public function hMset($key, $val = [], int $ttl = 0) {
        $val = $this->handler->hMset($key, $val);
        if ($ttl) $this->handler->expire($key, $ttl);
        return $val;
    }

    /**
     * @param $key
     * @param $col
     * @return string
     */
    public function hGet($key, $col) {
        return $this->handler->hGet($key, $col);
    }

    /**
     * 批量获取
     * @param $key
     * @param array $cols
     * @return array
     */
    public function hMget($key, $cols = []) {
        return $this->handler->hMGet($key, $cols);
    }

    /**
     * @param $key
     * @return array
     */
    public function hGetAll($key) {
        return $this->handler->hGetAll($key);
    }

    /**
     * 删除col字段
     * @param $key
     * @param $col
     * @return int
     */
    public function hDel($key, $col) {
        return $this->handler->hDel($key, $col);
    }

    /**
     * 返回$key下所有的值
     * @param $key
     * @return array
     */
    public function hKeys($key) {
        return $this->handler->hKeys($key);
    }



    /**
     * ZSET
     */

    /**
     * @param $key
     * @param $score
     * @param $col
     * @return int
     */
    public function zAdd($key, $score, $col) {
        return $this->handler->zAdd($key, $score, $col);
    }

    /**
     * 返回指定元素的score
     * @param $key
     * @param $col
     * @return float
     */
    public function zScore($key, $col) {
        return $this->handler->zScore($key, $col);
    }

    /**
     * @param $key
     * @param int $start
     * @param int $end
     * @return array
     */
    public function zRange($key, $start = 0, $end = -1, $withscores = true) {
        return $this->handler->zRange($key, $start, $end, $withscores);
    }

    /**
     * @param $key
     * @param int $start
     * @param int $end
     * @return array
     */
    public function zRevRange($key, $start = 0, $end = -1, $withscores = true) {
        return $this->handler->zRevRange($key, $start, $end, $withscores);
    }

    /**
     * 返回zSet元素总数量
     * @param $key
     * @return int
     */
    public function zCard($key) {
        return $this->handler->zCard($key);
    }

    /**
     * @param $key
     * @param int $min
     * @param int $max
     * @return array
     */
    public function zRangeByScore($key, $min = 0, $max = -1, array $option = ['withscores' => true]) {
        return $this->handler->zRangeByScore($key, $min, $max, $option);
    }

    /**
     * @param $key
     * @param int $min
     * @param int $max
     * @return array
     */
    public function zRevRangeByScore($key, $min = 0, $max = -1, array $option = ['withscores' => true]) {
        return $this->handler->zRevRangeByScore($key, $min, $max, $option);
    }

    /**
     * @param $key
     * @param $col
     * @return int
     */
    public function zRem($key, $col) {
        return $this->handler->zRem($key, $col);
    }

    /**
     * 设置Key的过期时间
     * @param $key
     * @param int $ttl 秒
     * @return bool
     */
    public function expire($key, int $ttl) {
        return $this->handler->expire($key, $ttl);
    }

    /**
     * 返回过期时间
     * @param $key
     * @return int
     */
    public function ttl($key) {
        return $this->handler->ttl($key);
    }

    /**
     * 获取锁
     * @param $key
     * @param int $expire
     * @return bool true 表示获取锁成功，可以继续执行，false 表示获取锁失败，禁止继续执行
     */
    public function lock($key, int $expire = 5) {
        $key = 'lock:' . $key;
        $isLock = $this->handler->setnx($key, time() + $expire);
        if ($isLock) {
            $lockTime = $this->handler->get($key);
            if (time() > $lockTime) {
                // $this->unlock($key);
                $this->handler->del($key);
                $isLock = $this->handler->setnx($key, time() + $expire);
            }
        }
        return $isLock ? true : false;
    }

    /**
     * 释放锁
     * @param $key
     * @return int
     */
    public function unlock($key) {
        $key = 'lock:' . $key;
        return $this->handler->del($key);
    }
}
