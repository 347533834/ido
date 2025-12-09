<?php
/**
 * Created by PhpStorm.
 * User: spook
 * Date: 2017/12/5
 * Time: 17:05
 */
/**
 * 加密算法
 */
function encryption($text) {
    return md5(md5($text) . '%^&*#$');
}