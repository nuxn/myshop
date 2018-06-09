<?php

namespace Common\Controller;

use Common\Controller\AppframeController;

class HomebaseController extends AppframeController
{

    public function __construct()
    {
        $this->set_action_success_error_tpl();
        parent::__construct();
    }

    function _initialize()
    {
        parent::_initialize();
        defined('TMPL_PATH') or define("TMPL_PATH", C("SP_TMPL_PATH"));
        $site_options = get_site_options();
        $this->assign($site_options);
        $ucenter_syn = C("UCENTER_ENABLED");
        if ($ucenter_syn) {
            $session_user = session('user');
            if (empty($session_user)) {
                if (!empty($_COOKIE['thinkcmf_auth']) && $_COOKIE['thinkcmf_auth'] != "logout") {
                    $thinkcmf_auth = sp_authcode($_COOKIE['thinkcmf_auth'], "DECODE");
                    $thinkcmf_auth = explode("\t", $thinkcmf_auth);
                    $auth_username = $thinkcmf_auth[1];
                    $users_model = M('Users');
                    $where['user_login'] = $auth_username;
                    $user = $users_model->where($where)->find();
                    if (!empty($user)) {
                        $is_login = true;
                        session('user', $user);
                    }
                }
            } else {
            }
        }

        if (sp_is_user_login()) {
            $this->assign("user", sp_get_current_user());
        }

    }

    public function _empty()
    {
        redirect("http://www.youngport.cn/");
    }

    /**
     * 检查用户登录
     */
    protected function check_login()
    {
        $session_user = session('user');
        if (empty($session_user)) {
            $this->error('您还没有登录！', leuu('user/login/index', array('redirect' => base64_encode($_SERVER['HTTP_REFERER']))));
        }

    }

    /**
     * 检查用户状态
     */
    protected function check_user()
    {
        $user_status = M('Users')->where(array("id" => sp_get_current_userid()))->getField("user_status");
        if ($user_status == 2) {
            $this->error('您还没有激活账号，请激活后再使用！', U("user/login/active"));
        }

        if ($user_status == 0) {
            $this->error('此账号已经被禁止使用，请联系管理员！', __ROOT__ . "/");
        }
    }

    /**
     * 发送注册激活邮件
     */
    protected function _send_to_active()
    {
        $option = M('Options')->where(array('option_name' => 'member_email_active'))->find();
        if (!$option) {
            $this->error('网站未配置账号激活信息，请联系网站管理员');
        }
        $options = json_decode($option['option_value'], true);
        //邮件标题
        $title = $options['title'];
        $uid = session('user.id');
        $username = session('user.user_login');

        $activekey = md5($uid . time() . uniqid());
        $users_model = M("Users");

        $result = $users_model->where(array("id" => $uid))->save(array("user_activation_key" => $activekey));
        if (!$result) {
            $this->error('激活码生成失败！');
        }
        //生成激活链接
        $url = U('user/register/active', array("hash" => $activekey), "", true);
        //邮件内容
        $template = $options['template'];
        $content = str_replace(array('http://#link#', '#username#'), array($url, $username), $template);

        $send_result = sp_send_email(session('user.user_email'), $title, $content);

        if ($send_result['error']) {
            $this->error('激活邮件发送失败，请尝试登录后，手动发送激活邮件！');
        }
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $content 模板输出内容
     * @return mixed
     */
    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content, $prefix);
    }

    /**
     * 获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀*
     * @return string
     */
    public function fetch($templateFile = '', $content = '', $prefix = '')
    {
        $templateFile = empty($content) ? $this->parseTemplate($templateFile) : '';
        return parent::fetch($templateFile, $content, $prefix);
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
     * 设置错误，成功跳转界面
     */
    private function set_action_success_error_tpl()
    {
        $theme = C('SP_DEFAULT_THEME');
        if (C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
            if (cookie('think_template')) {
                $theme = cookie('think_template');
            }
        }
        //by ayumi手机提示模板
        $tpl_path = '';
        if (C('MOBILE_TPL_ENABLED') && sp_is_mobile() && file_exists(C("SP_TMPL_PATH") . "/" . $theme . "_mobile")) {//开启手机模板支持
            $theme = $theme . "_mobile";
            $tpl_path = C("SP_TMPL_PATH") . $theme . "/";
        } else {
            $tpl_path = C("SP_TMPL_PATH") . $theme . "/";
        }

        //by ayumi手机提示模板
        $defaultjump = THINK_PATH . 'Tpl/dispatch_jump.tpl';
        $action_success = sp_add_template_file_suffix($tpl_path . C("SP_TMPL_ACTION_SUCCESS"));
        $action_error = sp_add_template_file_suffix($tpl_path . C("SP_TMPL_ACTION_ERROR"));
        if (file_exists_case($action_success)) {
            C("TMPL_ACTION_SUCCESS", $action_success);
        } else {
            C("TMPL_ACTION_SUCCESS", $defaultjump);
        }

        if (file_exists_case($action_error)) {
            C("TMPL_ACTION_ERROR", $action_error);
        } else {
            C("TMPL_ACTION_ERROR", $defaultjump);
        }
    }

    /**
     * 获取会员卡信息
     * @param $merchant
     * @param $openid
     */
    public function getOffer($merchant, $openid)
    {
        $this->agentflag = 0;
        $this->cardflag = 0;
        $merchant_id = $merchant['merchant_id'];
        $uid = M('merchants')->where(array('id' => $merchant_id))->getField('uid');
        // 获取联名卡数据
        $agent_data = M('screen_memcard')
            ->field('c.*')
            ->join('c left join ypt_merchants_users u on c.mid=u.agent_id')
            ->where(array('u.id' => $uid))
            ->find();
        //判断联名卡参数
        if (empty($agent_data)) {
            $this->agent_discount = '';
            $this->agent_credits = '';
            $this->agent_credits_discount = '';
        } else {
            # 参与代理异业联盟的商户
            $use_merchants = M("screen_cardset")->where(array('c_id' => $agent_data['id']))->getField("use_merchants");
            # 判断该商户是否参与代理的异业联盟
            $inarray = in_array($uid,explode(',',$use_merchants));
            $this->agent_card_code = M('screen_memcard_use')->where(array('memcard_id' => $agent_data['id'], 'fromname' => $openid))->getField("card_code");
            $agent_card_id = $agent_data['id'];
            $agent_mem_info = M('screen_memcard_use')->where(array('fromname' => $openid, 'memcard_id' => $agent_card_id,'status'=>1))->find();
            # 是否有该用户的会员信息
            if (empty($agent_mem_info) || !$inarray) {
                $this->agent_discount = '';
                $this->agent_credits = 0;
                $this->agent_credits_use = 0;
                $this->agent_credits_discount = 0;
                $this->agent_yue = '';
            } else {
                $this->agent_discount = 10;
                $this->agent_credits = 0;
                $this->agent_credits_use = 0;
                $this->agent_credits_discount = 0;
                $this->agent_yue = 0;
                $this->agent_yue = (float)$agent_mem_info['yue'];
                if($this->agent_yue < 0){
                    $this->agent_yue = 0;
                }
                # 是否开启打折优惠
                if ($agent_data['discount_set'] == 1) {
                    $this->agentflag = 1;
                    if ($agent_data['level_set'] == 1) {
                        $agent_user_integral = $agent_mem_info['card_amount'];
                        $agent_discount_data = M('screen_memcard_level')->where(array('c_id' => $agent_card_id))->select();
                        for ($i = 0; $i < count($agent_discount_data); $i++) {
                            if ($agent_user_integral >= $agent_discount_data[$i]['level_integral']) {
                                $this->agent_discount = $agent_discount_data[$i]['level_discount'];
                            }
                        }
                    } else {
                        $this->agent_discount = $agent_data['discount'];
                    }
                    if ($this->agent_discount == 0) {
                        $this->agent_discount = 10;
                    }
                }
                # 是否开启积分抵扣金额优惠
                if ($agent_data['integral_dikou'] == 1) {
                    $this->agentflag = 1;
                    $agent_max = $agent_data['max_reduce_bonus'];
                    $agent_have = $agent_mem_info['card_balance'];
                    $this->agent_credits_use = $agent_data['credits_use'];
                    if ($agent_have >= $agent_max) {
                        $this->agent_credits = $agent_max;
                    } else {
                        $this->agent_credits = $agent_have;
                    }
                    $this->agent_credits_discount = $agent_data['credits_discount'];
                }
                $this->agent_card_data = $agent_data;
                $this->agent_mem_info = $agent_mem_info;
            }
        }

        // 获取会员卡数据
        $card_data = M('screen_memcard')
            ->field('c.*')
            ->join('c left join ypt_merchants m on m.uid=c.mid')
            ->where(array('m.id' => $merchant_id))
            ->find();
        // 判断会员卡的参数
        if (empty($card_data)) {
            $this->discount = '';   // 折扣
            $this->credits = '';    // 本次可用积分
            $this->credits_discount = '';//积分可抵扣的金额
        } else {
            $this->card_code = M('screen_memcard_use')->where(array('memcard_id' => $card_data['id'], 'fromname' => $openid))->getField("card_code");
            $card_id = $card_data['id'];
            $mem_info = M('screen_memcard_use')->where(array('fromname' => $openid, 'memcard_id' => $card_id,'status'=>1))->find();
            # 是否有改用户会员信息
            if (empty($mem_info)) {
                $this->discount = '';
                $this->credits = 0;
                $this->credits_use = 0;
                $this->credits_discount = 0;
                $this->yue = '';
            } else {
                $this->discount = 10;
                $this->credits = 0;
                $this->credits_use = 0;
                $this->credits_discount = 0;
                $this->yue = 0;
                $this->yue = (float)$mem_info['yue'];
                if($this->yue < 0){
                    $this->yue = 0;
                }
                # 是否开启打折优惠
                if ($card_data['discount_set'] == 1) {
                    $this->cardflag = 1;
                    if ($card_data['level_set'] == 1) {
                        $user_integral = $mem_info['card_amount'];
                        $discount_data = M('screen_memcard_level')->where(array('c_id' => $card_id))->select();
                        for ($i = 0; $i < count($discount_data); $i++) {
                            if ($user_integral >= $discount_data[$i]['level_integral']) {
                                $this->discount = $discount_data[$i]['level_discount'];
                            }
                        }
                    } else {
                        $this->discount = $card_data['discount'];
                    }
                    if ($this->discount == 0) {
                        $this->discount = 10;
                    }
                }
                # 是否开启使用积分抵扣优惠
                if ($card_data['integral_dikou'] == 1) {
                    $this->cardflag = 1;
                    $max = $card_data['max_reduce_bonus'];
                    $have = $mem_info['card_balance'];
                    $this->credits_use = $card_data['credits_use']; // 使用多少积分抵扣的金额
                    if ($have >= $max) {
                        $this->credits = $max;
                    } else {
                        if ($have < 0) $have = 0;
                        $this->credits = $have;
                    }
                    $this->credits_discount = $card_data['credits_discount'];
                }
                $this->card_data = $card_data;
                $this->mem_info = $mem_info;
            }
        }
        if ($this->agentflag || $this->cardflag) {
            $this->setflag = 1;
        } else {
            $this->setflag = 1;
        }
        // 获取用户优惠券
        $coupon_data = M('screen_user_coupons')
            ->field('c.usercard,s.total_price,s.de_price')
            ->join('c left join ypt_screen_coupons s on c.coupon_id=s.id')
            ->where(array('c.fromname' => $openid, 's.mid' => $merchant_id, 'c.status' => '1','s.status' => '4','s.card_type' => 'GENERAL_COUPON', 's.begin_timestamp' => array('LT', time()), 's.end_timestamp' => array('GT', time())))
            ->order('de_price')
            ->select();
        $this->coupon_data = $coupon_data;
        // 判断是否存在优惠
        if (empty($this->discount) && empty($this->agent_discount) && empty($this->credits) && empty($this->agent_credits) && empty($this->coupon_data) && empty($this->agent_yue)) {
            $this->flag = 0;
        } else {
            $this->flag = 1;
        }
    }

    protected function get_costomer_id($sub_openid, $merchant_id)
    {
        $this->customer_id = D("Api/ScreenMem")->add_member($sub_openid, $merchant_id);
    }

}