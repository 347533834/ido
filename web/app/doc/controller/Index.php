<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/13
 * Time: 15:20
 */

namespace app\doc\controller;

use think\Controller;

/**
 * Class Index
 * @package app\doc\controller
 */
class Index extends Controller {
    public function index() {
        $str = '<ul>';
        $path = '../app/api/controller/';
        $dir = opendir($path);
        while (($file = readdir($dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
            if ($file == '.' || $file == '..' || is_dir($sub_dir)) {
                continue;
            } else {    //如果是文件,直接输出
                //$file = strtolower($file);
                $str .= '<li><a href="' . url('api', array('name' => str_replace('.php', '', $file))) . '">' . $file . '</a></li>';
            }
        }
        $str .= '</ul>';

        return $str;
    }

    public function api($name) {
        $rc = new \ReflectionClass("app\\api\\controller\\{$name}");
        $list = array();

        $parser = new \DocParser();
        foreach ($rc->getMethods() as $method) {
            if (!$method->isPublic()) continue;
//            var_dump($method->class);
//            var_dump($name);
            if (!strrpos($method->class, $name)) continue;

            $comments = $parser->parse($method->getDocComment());
            if (count($comments) == 0) continue;
            if (!array_key_exists('long_description', $comments)) $comment = $comments['description'];
            else $comment = $comments['long_description'];

            $item = array('name' => $method->name, 'params' => array(), 'comment' => $comment);
            //print_r($item['comment']);
            foreach ($method->getParameters() as $parameter) {
                $item['params'][] = $parameter->name;
            }
            $list[] = $item;
        }
        $obj = array('name' => strtolower($rc->getShortName()), 'methods' => $list);
        // var_dump($obj);
        $this->assign('list', $obj);
        return view();
    }
}
