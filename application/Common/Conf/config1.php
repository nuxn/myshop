<?php
if(file_exists("data/conf/db.php")){
	$db=include "data/conf/db.php";
}else{
	$db=array();
}
if(file_exists("data/conf/config.php")){
	$runtime_config=include "data/conf/config.php";
}else{
    $runtime_config=array();
}

if (file_exists("data/conf/route.php")) {
    $routes = include 'data/conf/route.php';
} else {
    $routes = array();
}

$configs= array(
        "LOAD_EXT_FILE"=>"extend",
        'UPLOADPATH' => 'data/upload/',
        //'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息
        'SHOW_PAGE_TRACE'		=> false,
        'TMPL_STRIP_SPACE'		=> true,// 是否去除模板文件里面的html空格与换行
        'THIRD_UDER_ACCESS'		=> false, //第三方用户是否有全部权限，没有则需绑定本地账号
        /* 标签库 */
        'TAGLIB_BUILD_IN' => THINKCMF_CORE_TAGLIBS,
        'MODULE_ALLOW_LIST'  => array('Admin','Portal','Asset','Api','User','Wx','Comment','Qiushi','Tpl','Topic','Install','Bug','Better','Pay','Message','Merchants','Apiscreen','App','Xcx','Post'),
        'TMPL_DETECT_THEME'     => false,       // 自动侦测模板主题
        'TMPL_TEMPLATE_SUFFIX'  => '.html',     // 默认模板文件后缀
        'DEFAULT_MODULE'        =>  'Admin',  // 默认模块
        'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
        'DEFAULT_ACTION'        =>  'index', // 默认操作名称
        'DEFAULT_M_LAYER'       =>  'Model', // 默认的模型层名称
        'DEFAULT_C_LAYER'       =>  'Controller', // 默认的控制器层名称
        
        'DEFAULT_FILTER'        =>  'htmlspecialchars', // 默认参数过滤方法 用于I函数...htmlspecialchars
        
        'LANG_SWITCH_ON'        =>  true,   // 开启语言包功能
        'DEFAULT_LANG'          =>  'zh-cn', // 默认语言
        'LANG_LIST'				=>  'zh-cn,en-us,zh-tw',
        'LANG_AUTO_DETECT'		=>  true,
        'ADMIN_LANG_SWITCH_ON'        =>  false,   // 后台开启语言包功能
        
        'VAR_MODULE'            =>  'g',     // 默认模块获取变量
        'VAR_CONTROLLER'        =>  'm',    // 默认控制器获取变量
        'VAR_ACTION'            =>  'a',    // 默认操作获取变量
        
        'APP_USE_NAMESPACE'     =>   true, // 关闭应用的命名空间定义
        'APP_AUTOLOAD_LAYER'    =>  'Controller,Model', // 模块自动加载的类库后缀
        
        'SP_TMPL_PATH'     		=> 'themes/',       // 前台模板文件根目录
        'SP_DEFAULT_THEME'		=> 'simplebootx',       // 前台模板文件
        'SP_TMPL_ACTION_ERROR' 	=> 'error', // 默认错误跳转对应的模板文件,注：相对于前台模板路径
        'SP_TMPL_ACTION_SUCCESS' 	=> 'success', // 默认成功跳转对应的模板文件,注：相对于前台模板路径
        'SP_ADMIN_STYLE'		=> 'flat',
        'SP_ADMIN_TMPL_PATH'    => 'admin/themes/',       // 各个项目后台模板文件根目录
        'SP_ADMIN_DEFAULT_THEME'=> 'simplebootx',       // 各个项目后台模板文件
        'SP_ADMIN_TMPL_ACTION_ERROR' 	=> 'Admin/error.html', // 默认错误跳转对应的模板文件,注：相对于后台模板路径
        'SP_ADMIN_TMPL_ACTION_SUCCESS' 	=> 'Admin/success.html', // 默认成功跳转对应的模板文件,注：相对于后台模板路径
        'TMPL_EXCEPTION_FILE'   => SITE_PATH.'public/exception.html',

        'AUTOLOAD_NAMESPACE' => array('plugins' => './plugins/'), //扩展模块列表
        
        'ERROR_PAGE'            =>'',//不要设置，否则会让404变302
        
        'VAR_SESSION_ID'        => 'session_id',
        
        "UCENTER_ENABLED"		=>0, //UCenter 开启1, 关闭0
        "COMMENT_NEED_CHECK"	=>0, //评论是否需审核 审核1，不审核0
        "COMMENT_TIME_INTERVAL"	=>60, //评论时间间隔 单位s

        /* URL设置 */
        'URL_CASE_INSENSITIVE'  => true,   // 默认false 表示URL区分大小写 true则表示不区分大小写
        'URL_MODEL'             => 1,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
        // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式，提供最好的用户体验和SEO支持
        'URL_PATHINFO_DEPR'     => '/',	// PATHINFO模式下，各参数之间的分割符号
        'URL_HTML_SUFFIX'       => '',  // URL伪静态后缀设置
        
        'VAR_PAGE'				=>"p",
        
        'URL_ROUTER_ON'			=> true,
        'URL_ROUTE_RULES'       => $routes,
        		
        /*性能优化*/
        'OUTPUT_ENCODE'			=>true,// 页面压缩输出
        
        'HTML_CACHE_ON'         =>    false, // 开启静态缓存
        'HTML_CACHE_TIME'       =>    60,   // 全局静态缓存有效期（秒）
        'HTML_FILE_SUFFIX'      =>    '.html', // 设置静态缓存文件后缀
        
        'TMPL_PARSE_STRING'=>array(
        	'__UPLOAD__' => __ROOT__.'/data/upload/',
        	'__STATICS__' => __ROOT__.'/statics/',
            '__WEB_ROOT__'=>__ROOT__
        ),

        'ADMIN_PAGE_ROWS'=>20, //每页条数
        '_WEB_UPLOAD_'=>'./data/upload/', // 后台上传的文件的位置
	
	'DB_PARAMS'=>array(
            'PDO::ATTR_CASE'=>'PDO::CASE_NATURAL',  //数据库区分大小写
        ),

        //配置发短信
        'SMS_CONFIG'=>array(
            'accountSid'=> '8a48b55152f73add0152f74db90a0032',
            'accountToken'=> '0295a66afe07483e9242027856648e01', //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            //'appId'=>'aaf98f8953cadc690153d13ff6d93348', //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID ，在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
            'appId'=>'8aaf07085a6ec238015a92e5ac8211f7', //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID ，在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
            'serverIP'=>'app.cloopen.com',//请求地址 沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com ,生产环境（用户应用上线使用）：app.cloopen.com
            'serverPort'=>'8883',//请求端口，生产环境和沙盒环境一致
            'softVersion'=>'2013-12-26', //REST版本号，在官网文档REST介绍中获得。
            'M_appId'=>'aaf98f895376c19601537e3fb8280b4b', //应用Id,后台消息管理专用
            'RegTemplateId'=>'157689', //注册短信模板Id,
            'PwdTemplateId'=>'157589', //修改密码短信模板Id,
            'ChangePhoneTemplateId'=>'180395', //更换手机号码模板id
            'setPayPwdTemplateId'=>'188595', //设置支付密码
        ),
        //合众微信配置文件
        'WEIXINPAY_CONFIG'       => array(
            'APPID'              => 'wx3fa82ee7deaa4a21', // 微信支付APPID
            'MCHID'              => '', // 微信支付MCHID 商户收款账号
            'KEY'                => 'hZ543sJhz797985jielshfykwk85jlwo', // 微信支付KEY
            'APPSECRET'          => '6b6a7b6994c220b5d2484e7735c0605a',  //公众帐号secert
            'NOTIFY_URL'         => '', // 接收支付状态的连接
        )
        //正式服微信配置文件
//        'WEIXINPAY_CONFIG'       => array(
//        'APPID'              => 'wx8b17740e4ea78bf5', // 微信支付APPID
//        'MCHID'              => '', // 微信支付MCHID 商户收款账号
//        'KEY'                => 'aRC0JdlKsdjakbvu4fwXngBXjROyAuB092X0TZJutoy', // 微信支付KEY
//        'APPSECRET'          => 'bbd06a32bdefc1a00536760eddd1721d',  //公众帐号secert
//        'NOTIFY_URL'         => '', // 接收支付状态的连接
//    )
//    $appId = 'wx8b17740e4ea78bf5';
//$appSecret = 'bbd06a32bdefc1a00536760eddd1721d';

);

return  array_merge($configs,$db,$runtime_config);
