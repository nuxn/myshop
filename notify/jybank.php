<?php

$data=$_POST;
file_put_contents('1.txt',date("Y-m-d H:i:s").'--'.json_encode($data).'订单号' . PHP_EOL, FILE_APPEND | LOCK_EX);

//var_dump($a);
//die;
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',True);

// 定义应用目录
define('APP_PATH','../../shanpay/Application/');
$_GET['m'] = 'Api';
$_GET['c'] = 'Notify';
$_GET['a'] = 'hf_notify';
var_dump($_GET);
// 引入ThinkPHP入口文件
require '../../shanpay/ThinkPHP/ThinkPHP.php';
 
