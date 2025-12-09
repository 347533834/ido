<?php
/**
 * Created by PhpStorm.
 * User: feisha
 * Date: 2018/6/5
 * Time: 下午2:28
 */

function filterHTML($html)
{
  $html = preg_replace('/width=".*?"/', '', $html);
  $html = preg_replace('/height=".*?"/', '', $html);
  $html = preg_replace('/style=".*?"/', '', $html);
  return $html;
}