<?php
set_time_limit(30);
error_reporting(E_ALL);
ini_set('display_errors', '1');
//定位参数未传，返回失败
if( !isset($_REQUEST['mod']) || empty($_REQUEST['mod']) || !is_numeric($_REQUEST['mod']) )
{
	die('error');
}
if( !isset($_REQUEST['act']) || empty($_REQUEST['act']) || !is_numeric($_REQUEST['act']) )
{
	die('error');
}
define('APP',dirname(dirname(__FILE__)).'/application');
define('CONFIG',dirname(dirname(__FILE__)).'/config');
define('LIB',dirname(dirname(__FILE__)).'/libary');
define('HTTP_URL','http://sy.youngport.com.cn/application/Alipay/application/notify/notify.php');
//define('URL','http://aop.koolyun.cn:8080/apmp/rest/v2');
define('URL','https://aop.koolyun.com:443/apmp/rest/v2');
//测试x-appkey
//define('APPKEY','YPT17001');
//正式x-appkey
define('APPKEY', 'YPT17001P');
//define('APPKEY','11111000');
//测试私钥
// $private_key="-----BEGIN RSA PRIVATE KEY-----
// MIICXQIBAAKBgQDZcfK4VpSmB+eAWk7i/I0bl7bLLu869ODuir2Q08yMnLwKxd6I
// TFHIBSEOGviZbzeUtbKJJsS0yj6+Ma0usUZ2lLyFtal4eUl78KQRBz6QqB16j+cz
// TH7cQNjB+JLT3ygbVqaCiz9g7CeBKRka9+MD9wq3IWG45WMhnWOZbzHGxwIDAQAB
// AoGAL8wydH7jshNuufIgARlO00/oKIWqpKULhKQOw3UrM4WIeD3Ciudr2rH18CnR
// l7iw2QmPs0JIXw1N+XTmAquJN0POHI4i1XubBZSTbnAYKpRHMay7FJE7l8zZ7Tlo
// 06VowJ31FwexpL0+3pKZwsKlm/KpVBh87BoqE5EzstHHyFECQQD7kJiNBKTkaj/r
// /1XeklpcaugXttIbE+Xq1ac9intSnM20MEYtqIAaWETGgJk+JFn7JdoVP4W5hEhv
// 8splkvgTAkEA3UdbgiV0iaru1K5YOMwmuJAZNK8GGLbSyY9Yy+neZCmsH/YhIKAB
// JVZUWVll0Z8g+DXrH/J+ZE5YLWsM/o50/QJBAItWj+isBdkusLE7AIkDb2F5JYzd
// CotM/jCQns2LgrtDdvyzMGvhxPLSqWV5nWe6IszlLmJOiPc0uhqn1EtmmFkCQQCT
// I88StMtQe+yCWkhpxD7/PTq1kKjSKEf0JbDbL3FlU1yUiDsxEZSRel1uaIbPJCxt
// QJVP0hT/qCT0Vpn2b04VAkA2zO1+jxfgx2vcdmDxFmGIok4W0xkztZjsQbdlehrh
// apxhxSGtznmWab+0lvmg80xhJq7QcqsUZEnd1qXic7tC
// -----END RSA PRIVATE KEY-----
// ";
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
require_once(CONFIG.'/config.application.php');//配置代码和程序块 的 路径 类名 方法名 的对应关系
require_once(CONFIG.'/config.public.php');
$data[0] = $_REQUEST['act'];
$data[1] = $_REQUEST['mod'];
$data[2] = isset($_REQUEST['data'])?$_REQUEST['data']:"{}";
$_cur_config=$app_config[$data[0]][$data[1]];
$__folder=$_cur_config[0];//application下的目录
$__class=$_cur_config[1];//类名
$__method=$_cur_config[2];//方法名
if(!file_exists(APP.'/'.$__folder.'/'.$__class.'.php'))
{
	die('error');
}
require_once(CONFIG.'/config.mysql.php');//数据库配置
require_once(LIB.'/mysqli.php');//数据库配置
$db = new mysql();
require_once(APP.'/'.$__folder.'/'.$__class.'.php');
$app = new $__class($db);
$ret = $app->$__method(json_decode(stripslashes($data[2]),1));
echo $ret;
?>