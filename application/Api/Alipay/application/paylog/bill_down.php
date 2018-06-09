<?php
ini_set('date.timezone','Asia/Shanghai');
define('URL','https://aop.koolyun.com:443/apmp/rest/v2');
//正式x-appkey
define('APPKEY', 'YPT17001P');
//正式私钥
$private_key= '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCbexvFt/rOGUOVDPbT99wWt3ChnmcqRc+lmJkEDHP98c8rd+Ih
V34VfjeA2+bhaJ66ZlN+sxJG871GIA6X9o7MOFjFsdAkXYAK+EyHiRZx4drhoaiM
LqxP+ygH3BlvvEEHUUT+ZW0lg2wgcRrzcUDHKZ0u112cQkZgo+Skivm6QQIDAQAB
AoGAS2g8wvsE9/pGzb5Y49sdciMLzEbQEC+FkvHcnJsRkoM5kAJ3uOX/L5tkfemp
I3+jJBJGwndFEQZbsOwRR+B7xoywgJ5+dlyneXEoNfbOJ4J3tP/IVoIDHr2ax8uW
3/IizcgcL8Wc6AyryaQfFb9nEBMUdTt3k3VUEZC4Ef/xccECQQDJ0dj5e3vYbS7F
yIsNlv5HBVzSK++qbxmefT0ZTrvgYPp/g+tFhY8blzOxhbJj3Cp+FxPqL9GOLg1P
hVNMYYj5AkEAxTian96ke9hQY5FjJ/e6q1fe8KzQG79/aC4q4j7rS5Z35kSuDA/Y
Pko47ta2AI5otCdQVXsvNBhFHaO3FKMViQJBAJcNK+NWS9Qpq9c2iPTL7VcEqXtY
jRG4A6m+vKsjZbTDgNlNyBqJoxmYaoVUtrbNAzTKWwptbd+HkkjRVg4V9ikCQQCX
KFkqqwQ6f4KtraLn4TFLXh/bKzid69oEyU3I9hx1ZLAk5wLW79X3d//G3v3D02Jg
obkqqy10qh1fKDmMMaqxAkB+h+DHSA3k4AmRtuKA+fQ9PoLRSbGqYiKEmGLaZvuE
WBDdsn6coSK8qlh4Jxv9dquCaymS9Y+lGzBh2o4n0jOF
-----END RSA PRIVATE KEY-----';
define('PRIVATE_KEY',$private_key);
require_once('../../config/config.application.php');//配置代码和程序块 的 路径 类名 方法名 的对应关系
require_once('../../config/config.public.php');
require_once('../../config/config.mysql.php');//数据库配置
require_once('../../libary/mysqli.php');//数据库配置
$db=new mysql();
$data['action']='mcht/bill/download';
$data['version']='2.0';
$data['coopId']='APPKEY';
$data['billDate']=date("Ymd",strtotime("-1 day"));;
$data=json_encode($data);
$data="[".$data."]";
$res=rsaSign($data,PRIVATE_KEY);
$result=httpRequst(URL,$data,$res,APPKEY);
file_put_contents('bill.logs', date("Y-m-d H:i:s").$result. PHP_EOL, FILE_APPEND | LOCK_EX);
$result=json_decode(stripslashes($result),true);
if(isset($result['body']['billUrl']) && !empty($result['body']['billUrl'])){
    $furl=$result['body']['billUrl'];
    $handle = fopen ($furl, "rb"); 
    $contents = array(); 
    while (!feof($handle)) { 
        $contents[]= fread($handle, 8192); 
    } 
    fclose($handle);
    $cont=''; 
    foreach ($contents as $key => $value) {
        $cont.=$value;
    }
    $time=date("Y-m-d",strtotime("-1 day"));
    $sql_1="select * from ypt_ms_daylogs where time='$time'";
    $re=$db->query($sql_1);
    if(!$re){
        $data=explode("\r\n",$cont);
        $sql= "insert into ypt_ms_logs  (pay_type,pay_status,order_sn,pay_time,pay_price,pay_reprice,transId,type,price,refund_time,pay_id) 
          VALUES";
        $count=count($data)-2;
        foreach ($data as $key => $value) {

            $data[$key]=explode(',',$data[$key]);
            if(isset($data[$key][1]) && !empty($data[$key][1])){
                $goods[$key]['pay_type']=$data[$key][1];
                $goods[$key]['pay_status']=$data[$key][2];
                $goods[$key]['order_sn']=$data[$key][3];
                $goods[$key]['pay_time']=$data[$key][4].$data[$key][5];
                $goods[$key]['pay_price']=$data[$key][6];
                $goods[$key]['pay_reprice']=$data[$key][7];
                $goods[$key]['transId']=$data[$key][13];
                $goods[$key]['type']=$data[$key][16];
                $goods[$key]['price']=$data[$key][20];
                $goods[$key]['refund_time']=$data[$key][14].$data[$key][15];
                $goods[$key]['pay_id']=$data[$key][18];
                if($key==$count){
                    $sql.="('".$data[$key][1]."','".$data[$key][2]."','".$data[$key][3]."','".$data[$key][4].$data[$key][5]."','".$data[$key][6]."','".$data[$key][7]."','".$data[$key][13]."','".$data[$key][16]."','".$data[$key][20]."','".$data[$key][14].$data[$key][15]."','".$data[$key][18]."')";
                }else{
                    $sql.="('".$data[$key][1]."','".$data[$key][2]."','".$data[$key][3]."','".$data[$key][4].$data[$key][5]."','".$data[$key][6]."','".$data[$key][7]."','".$data[$key][13]."','".$data[$key][16]."','".$data[$key][20]."','".$data[$key][14].$data[$key][15]."','".$data[$key][18]."'),";
                }
            }
        }
        $db->query($sql);
        $z_price=0;
        $f_price=0;
        $sxf_price=0;
        $z=0;
        $f=0;
        for($i=0;$i<=$count;$i++){
            if($goods[$i]['pay_status']==1){
                $z++;
                $z_price+=$goods[$i]['pay_price'];
            }
            if($goods[$i]['pay_status']==3){
                $f++;
                $f_price+=$goods[$i]['pay_price'];
            }
            $sxf_price+=$goods[$i]['pay_reprice'];
        }
        $price=$z_price-$f_price-$sxf_price;
        $day_sql="insert into ypt_ms_daylogs (time,z_number,f_number,z_price,f_price,price,sxf_price) VALUES ('".$time."','".$z."','".$f."','".$z_price."','".$f_price."','".$price."','".$sxf_price."')";
        $db->query($day_sql);
    }
}