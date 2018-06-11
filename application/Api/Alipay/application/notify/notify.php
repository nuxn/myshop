<?php
require_once('../../libary/mysqli.php');//数据库配置
require_once('../../config/config.mysql.php');//数据库配置
$db = new mysql();
date_default_timezone_set('Asia/Shanghai');
$fileName="notify/".date("Y-m-d",time()).".logs";
if(!file_exists($fileName)) {
 	@fopen($fileName, "w");
}
file_put_contents($fileName,date("Y-m-d H:i:s",time())."--".var_export($_POST['sign'],true)."\r\n",FILE_APPEND | LOCK_EX);
file_put_contents($fileName,date("Y-m-d H:i:s",time())."--".$_POST['body']."\r\n",FILE_APPEND | LOCK_EX);
$str=stripslashes($_POST['body']);
$data=json_decode($str,true);
$order_sn=$data['orderId'];
$transId=$data['transId'];
$orderData=$db->query("select * from ypt_pay where remark='$order_sn'");
if($orderData['status']==0){
	$sql="update ypt_pay set status=1,transId='$transId' where remark='".$order_sn."' AND status='0'";
	$fileName="sql/".date("Y-m-d",time()).".logs";
	if(!file_exists($fileName)) {
 		@fopen($fileName, "w");
	}
	file_put_contents($fileName,date("Y-m-d H:i:s",time())."--".$order_sn."--".$sql."\r\n",FILE_APPEND | LOCK_EX);
	$db->query($sql);
}else{
	$fileName="recode/".date("Y-m-d",time()).".logs";
	if(!file_exists($fileName)) {
	 	@fopen($fileName, "w");
	}
	file_put_contents($fileName,date("Y-m-d H:i:s",time())."--重复付款"."\r\n",FILE_APPEND | LOCK_EX);
}