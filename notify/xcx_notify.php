<?php
if (ini_get('magic_quotes_gpc')) {
    function stripslashesRecursive(array $array){
        foreach ($array as $k => $v) {
            if (is_string($v)){
                $array[$k] = stripslashes($v);
            } else if (is_array($v)){
                $array[$k] = stripslashesRecursive($v);
            }
        }
        return $array;
    }
    $_GET = stripslashesRecursive($_GET);
    $_POST = stripslashesRecursive($_POST);
}

$_GET['g'] = 'xcx';
$_GET['m'] = 'Merchants';
$_GET['a'] = 'zfb_notify_url';
//��������ģʽ
define("APP_DEBUG", false);
//��վ��ǰ·��
define('SITE_PATH', dirname(__FILE__)."/../");
//��Ŀ·�������ɸ���
define('APP_PATH', SITE_PATH . 'application/');
//��Ŀ���·�������ɸ���
define('SPAPP_PATH',   SITE_PATH.'simplewind/');
//
define('SPAPP',   './application/');
//��Ŀ��ԴĿ¼�����ɸ���
define('SPSTATIC',   SITE_PATH.'statics/');
//���建����·��
define("RUNTIME_PATH", SITE_PATH . "data/runtime/");
//��̬����Ŀ¼
define("HTML_PATH", SITE_PATH . "data/runtime/Html/");
//�汾��
define("THINKCMF_VERSION", 'X2.2.3');

define("THINKCMF_CORE_TAGLIBS", 'cx,Common\Lib\Taglib\TagLibSpadmin,Common\Lib\Taglib\TagLibHome');

//uc client root
define("UC_CLIENT_ROOT", './api/uc_client/');

if(file_exists(UC_CLIENT_ROOT."config.inc.php")){
    include UC_CLIENT_ROOT."config.inc.php";
}

//�����ܺ����ļ�
require SPAPP_PATH.'Core/ThinkPHP.php';


