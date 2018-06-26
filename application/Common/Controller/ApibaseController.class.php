<?php
/**
 * App接口基类控制器
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/9
 * Time: 17:26
 */

namespace Common\Controller;

use Think\Controller;

/**
 * Class ApibaseController
 * @package Common\Controller
 */
class ApibaseController extends Controller
{
    public $userId = 0;//用户id
    public $token = '';//接口令牌
    public $client;//客户端类型(来源)android or ios
    public $userInfo;//用户信息
    public $saltKey = '26F33A61FAFF49A46475C6C25BD2D561';//盐值
    public $version;//app版本号
    private $userModel;//用户模型
    private $rule;//当前URL请求的模块/控制器/方法
    public $is_pos = 0; // 判断是否是POS机登录
    //存储无需验证签名的控制器
    protected $no_need_check_sign = array(
        "Public",
        "Addagent",
        "Agentnews",
        "Shopnews",
        "Member",
        "Pay",
        "Wxpay",
        "App",
        "Goods",
        "Employee",
        "Xcx",
        "Message",
        "Cloud",
        "Barcode",
        "PushMsg",
        "Product",
        "Integral",
        "Test",
        "card_pull",
        "Shopping",
        "PopPay",
        "Wghl",
        "Wechat",
        "Merchants",
        "Addstore"
    );
    //存储无需验证签名的模块
    protected $no_need_check_sign_module = array(
        "Pay"
    );

    function _initialize()
    {
        $this->userModel = M("merchants_users");
        $this->token = I('token');
        $this->client = I("client");
        $type = in_array($this->client, array('ios', 'android')) ? 1 : null;
        $param = I("");

        $this->rule = MODULE_NAME . CONTROLLER_NAME . ACTION_NAME;
        $sign = $param['sign'];//签名
        if (!in_array(CONTROLLER_NAME, $this->no_need_check_sign) && !in_array(MODULE_NAME, $this->no_need_check_sign_module)) {
            if (!$this->_getSign($param, $sign)) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => 'Signature error'));
            }
        }

        // pos机使用
        $pos = M("post_token")->where(array('token' => $this->token))->find();
        
        if ($pos) {
            $this->userInfo = json_decode($pos['value'], true);
            $this->userId = $this->userInfo['uid'];
            $this->version = '1.3';
            $this->is_pos = 1;
        } else {
            $result = $this->getTokenInfo($this->token, $type);
            if ($result) {
                $u_id = $result['uid'];
                $mid = M("merchants")->where(array('uid' => $u_id))->getField("mid");
                $info = M("token")->where(array("token" => $this->token))->find();
                $this->version = $info['version'];
                if ($mid == 0 && $info["userinfo"] != "") {
                    $this->userInfo = json_decode($info["userinfo"], true);
                    $this->userId = (int)$this->userInfo['uid'];
                } else {
                    $this->userInfo = $result;
                    $this->userId = $this->userInfo['uid'];
                }

            }
            $two = M("twotoken")->where(array('token' => $this->token))->find();
            if ($two) {
                $this->userInfo = json_decode($two['value'], true);
                $this->userId = $this->userInfo['uid'];
            }
        }

        if (!empty($this->userId)) {//验证权限
            if (!$this->check_access($this->userId)) {
                $this->ajaxReturn(array("code" => "error", "msg" => "您没有访问权限!"));
            }

        }
    }

    /**获取koken里面对应的用户信息
     * @param $token
     * @param $type
     * @return mixed
     */
    protected function getTokenInfo($token, $type)
    {
        $user_info = M("token")->where(array("token" => $token))->getField("value");
        if (!$user_info) {
//            $msg = $this->checkSso();
//            if ($msg) $this->ajaxReturn(array("code" => "error", "msg" => $msg));
            return false;
        } else {
            return json_decode($user_info, true);
        }

    }

    /**单点登录检测
     * @return bool|string
     */
    protected function checkSso()
    {
        Vendor('Cache.MyRedis');
        $redis = new \MyRedis();
        $token_info = $redis->get($this->token);
        $token_info = json_decode($token_info, true);
        $str = '';
        if ($token_info['login_ip'] == get_client_ip()) $str = '其他地点';
        $msg = "对不起!您被迫下线，您的帐号被发现在" . $token_info['address'] . $str . "登录，登陆时间:" . $token_info['login_time'] . ",登录IP:" . $token_info['login_ip'] . ",请确认是否您本人登录，为了您的账号安全，请重新登录并修改登录密码!";
        if ($token_info) return $msg;
        else  return false;
    }

    /**渲染视图
     * @param string $templateFile
     * @param string $charset
     * @param string $contentType
     * @param string $content
     * @param string $prefix
     */
    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content, $prefix);
    }

    /**
     * 自动定位模板文件
     * @access protected
     * @param string $template 模板文件规则
     * @return string
     */
    public function parseTemplate($template = '')
    {
        $tmpl_path = C("SP_TMPL_PATH");
        define("SP_TMPL_PATH", $tmpl_path);
        if ($this->theme) { // 指定模板主题
            $theme = $this->theme;
        } else {
            // 获取当前主题名称
            $theme = C('SP_DEFAULT_THEME');
            if (C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
                $t = C('VAR_TEMPLATE');
                if (isset($_GET[$t])) {
                    $theme = $_GET[$t];
                } elseif (cookie('think_template')) {
                    $theme = cookie('think_template');
                }
                if (!file_exists($tmpl_path . "/" . $theme)) {
                    $theme = C('SP_DEFAULT_THEME');
                }
                cookie('think_template', $theme, 864000);
            }
        }

        $theme_suffix = "";

        if (C('MOBILE_TPL_ENABLED') && sp_is_mobile()) {//开启手机模板支持

            if (C('LANG_SWITCH_ON', null, false)) {
                if (file_exists($tmpl_path . "/" . $theme . "_mobile_" . LANG_SET)) {//优先级最高
                    $theme_suffix = "_mobile_" . LANG_SET;
                } elseif (file_exists($tmpl_path . "/" . $theme . "_mobile")) {
                    $theme_suffix = "_mobile";
                } elseif (file_exists($tmpl_path . "/" . $theme . "_" . LANG_SET)) {
                    $theme_suffix = "_" . LANG_SET;
                }
            } else {
                if (file_exists($tmpl_path . "/" . $theme . "_mobile")) {
                    $theme_suffix = "_mobile";
                }
            }
        } else {
            $lang_suffix = "_" . LANG_SET;
            if (C('LANG_SWITCH_ON', null, false) && file_exists($tmpl_path . "/" . $theme . $lang_suffix)) {
                $theme_suffix = $lang_suffix;
            }
        }

        $theme = $theme . $theme_suffix;

        C('SP_DEFAULT_THEME', $theme);

        $current_tmpl_path = $tmpl_path . $theme . "/";
        // 获取当前主题的模版路径
        define('THEME_PATH', $current_tmpl_path);

        $cdn_settings = sp_get_option('cdn_settings');
        if (!empty($cdn_settings['cdn_static_root'])) {
            $cdn_static_root = rtrim($cdn_settings['cdn_static_root'], '/');
            C("TMPL_PARSE_STRING.__TMPL__", $cdn_static_root . "/" . $current_tmpl_path);
            C("TMPL_PARSE_STRING.__PUBLIC__", $cdn_static_root . "/public");
            C("TMPL_PARSE_STRING.__WEB_ROOT__", $cdn_static_root);
        } else {
            C("TMPL_PARSE_STRING.__TMPL__", __ROOT__ . "/" . $current_tmpl_path);
        }


        C('SP_VIEW_PATH', $tmpl_path);
        C('DEFAULT_THEME', $theme);

        define("SP_CURRENT_THEME", $theme);

        if (is_file($template)) {
            return $template;
        }
        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);

        // 获取当前模块
        $module = MODULE_NAME;
        if (strpos($template, '@')) { // 跨模块调用模版文件
            list($module, $template) = explode('@', $template);
        }

        $module = $module . "/";

        // 分析模板文件规则
        if ('' == $template) {
            // 如果模板文件名为空 按照默认规则定位
            $template = CONTROLLER_NAME . $depr . ACTION_NAME;
        } elseif (false === strpos($template, '/')) {
            $template = CONTROLLER_NAME . $depr . $template;
        }

        $file = sp_add_template_file_suffix($current_tmpl_path . $module . $template);
        $file = str_replace("//", '/', $file);
        if (!file_exists_case($file)) E(L('_TEMPLATE_NOT_EXIST_') . ':' . $file);
        return $file;
    }

    /**
     * 检查用户登陆状态
     */
    protected function checkLogin()
    {
        if (!$this->userId) $this->ajaxReturn(array("code" => "error", "msg" => '未登录'));
    }

    /**
     *  检查接口用户访问权限
     * @param int $uid 前台用户id
     * @return boolean 检查通过返回true
     */
    private function check_access($uid)
    {
        $value = M("token")->where(array("uid" => $uid))->getField('value');
        $value = json_decode($value, true);
        $href_url = MODULE_NAME . "/" . CONTROLLER_NAME . "/" . ACTION_NAME;
        $module = M('nav')->where(array('href' => $href_url))->getField('module');
        if ($value[$module] == '1' || $value['role_id'] == '77') return true;
        else  return sp_auth_check($uid, '', '', MODULE_NAME);
    }

    /**
     * 验证接口签名
     * @param $param
     * @param $sign
     * @return bool
     */
    protected function _getSign($param, $sign)
    {
        if (is_array($param) && !empty($param) && $sign != '' && $param['timestamp'] != '') {
            unset($param['sign']);
            //if ($param['timestamp'] + 300 < time()) $this->ajaxReturn(array("code" => "error", "msg" => "url已过期!"));
            ksort($param);
            $str = '';
            foreach ($param as $k => $v) {
                $str .= $k . '=' . $v . '&';
            }
            $str .= "key=" . $this->saltKey;
            $str = strtoupper(md5($str));//比较接收的sign与(参数+固定盐值)加密后的签名是否一致
            return $str === $sign;
        } else {
            return false;
        }
    }

    /**生成全局唯一TOKEN
     * @param array $arr
     * @return mixed
     */
    protected function build_token($arr = array())
    {
        $arr['salt'] = build_order_no();
        $arr['time'] = time();
        sort($arr);
        $String = implode($arr);
        $result_ = sha1($String);
        $TOKEN = strtoupper($result_);
        return $TOKEN;
    }

    public function write_log($desc = '',$object_id='')
    {
        $type = in_array($this->client, array('ios', 'android')) ? 2: 3;
        if (!empty($this->userId)) {//写入日志
            $add_data= array(
                'app'=>MODULE_NAME,
                'model'=>CONTROLLER_NAME,
                'action'=>ACTION_NAME,
                'desc' => $desc,
                'add_time'=> time(),
                'uid' => $this->userId,
                'object_id' => $object_id,
                'type' => $type

            );
            M('handle_log')->add($add_data);//添加日志


        }
    }

}