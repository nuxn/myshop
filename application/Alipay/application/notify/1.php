<?php
require_once('../../libary/mysqli.php');//数据库配置
require_once('../../config/config.mysql.php');//数据库配置
$db = new mysql();
// header("Content-Type: text/html; charset=utf-8");
// $file = fopen('1.csv','r'); 
// //$sql="INSERT INTO ypt_ms_address (id,pid,city_name,level) VALUES";
// $sql='';
// $n=0;
// while ($data = fgetcsv($file)) { //每次读取CSV里面的一行内容
// 	if($n==0){
// 		$sql=$sql."('$data[1]','$data[0]','$data[2]','$data[3]')";
// 	}else{
// 		$sql=$sql.",('$data[1]','$data[0]','$data[2]','$data[3]')";
// 	}

//  $n++;
// }
// $sql="INSERT INTO ypt_ms_address (id,pid,city_name,level) VALUES".$sql;
// $db->query($sql);

$file = fopen("1.txt", "r");
$user=array();
$keywords="行";
while(! feof($file))
{
 $user[]= fgets($file);//fgets()函数从文件指针中读取一行
}
foreach ($user as $key => $value) {
	$user[$key]=explode('|',$value);
	if($user[$key][0]!=''){
		if (strstr($user[$key][2],$keywords ) !== false ){
			$goods[]=$user[$key];
		}else{
			$brr[]=$user[$key];
		}
	}
}
var_dump($brr);
//var_dump($goods);
//var_dump($user);
// while(! feof($file))
// {
//  $user[]= fgets($file);//fgets()函数从文件指针中读取一行
//   if (strstr( $values , $keywords ) !== false )
// }

// var_dump($user);