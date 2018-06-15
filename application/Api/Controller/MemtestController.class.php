<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/27
 * Time: 14:10
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Controller;

//load('Screen/function');

/**会员、会员卡接口
 * Class MemberController 
 * @package Api\Controller 
 */
class MemtestController extends Controller
{
    public $memcardModel;
    public $memberModel;
    public $memcard_use_Model;
    public $user_coupons;
    public $coupons;
    public $pay;
    public $host;

    public function __construct()
    {
        parent::__construct();
        $this->memcardModel = M("screen_memcard");
        $this->memberModel = M("screen_mem");
        $this->memcard_use_Model = M("screen_memcard_use");
        $this->user_coupons = M("screen_user_coupons");
        $this->coupons = M("screen_coupons");
        $this->pay = M('pay');
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
//        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);
    }

    public function index()
    {
        echo 1;
    }

    /**会员卡一键开卡
     * @param array $param
     */
    public function activateuserform($param = array())
    {
        if (!$param["card_id"]) $param["card_id"] = 'pyaFdwHr69B3DJjAe8VAvN8F8jwY';
        if (!$param) $this->ajaxReturn(array("code" => "error", "msg" => "ID不能为空"));
        $token = get_weixin_token();
        $arr = array(
            "card_id" => $param["card_id"],
            "bind_old_card" => array (
                "name" => urlencode("老会员绑定"),
                "url" => "https://sy.youngport.com.cn/index.php?s=Api/Memtest/index"
            ),
            "required_form" => array(
                "common_field_id_list" => array(
                    "USER_FORM_INFO_FLAG_MOBILE",
                    "USER_FORM_INFO_FLAG_NAME",
                    "USER_FORM_INFO_FLAG_BIRTHDAY"
                )
            )
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=$token";
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        $result = json_decode($result, true);
        $this->writeLog('activateuser.log','一键开卡',$result);
    }

    /**
     * 更新会员信息
     * 用于支付后、激活、奖励积分
     */
    public function update_membercard()
    {
        if ($_REQUEST) {
            $token = get_weixin_token();
            $card_id = 'pyaFdwJ-m1uf9S2P2MXcSvo2xX1Y';
            $code = '999424030004';
            $add_bonus = '-2';

            $arr = array(
                "code" => "$code",
                "card_id" => "$card_id",
                "record_bonus" => "积分变更",
                "add_bonus" => $add_bonus,
                "add_balance" => "0",
                "record_balance" => "积分变更",
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
            $result = request_post($mem_card_query_url, json_encode($arr));
            $result = json_decode($result, true);

            $this->writeLog('update_member.log','更新会员',$result);
            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $this->ajaxReturn(array("code" => "success", "msg" => "更新会员卡信息成功", "data" => $result));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "更新会员卡信息失败", "data" => $result));
            }
        }
    }

    /*******************************************************************************************************************
     * 以下为收银1.3新改版会员卡操作
     * 创建和修改会员卡
     *******************************************************************************************************************/
    public function addCommonMemcard()
    {
        $this->userId = 115;
        $this->userInfo = array('user_phone' => '15773001191');
        $casnhu = '{"service_phone":"15019254994","discount":"5","expense_credits":"10","token":"D9349CD5EDEC75991ED7E24E8245042AD41FF776","credits_set":"1","discount_params":"","timestamp":"1515653196","cardname":"\u516c\u53f8\u6d4b\u8bd5","expense":"10","credits_discount":"10","id":"","discount_set":"1","color":"Color050","integral_dikou":"1","is_agent":"0","max_reduce_bonus":"100","brand_name":"\u8fc5\u6377\u654f\u8fbe","level_params":"","cost_bonus_unit":"10","sign":"75E293B1786629DEC2196679656CA824","expense_credits_max":"100","balance_set":"1","logoimg":"https:\/\/sy.youngport.com.cn\/data\/upload\/memcard\/2018-01-11\/5a5707d235381.jpg","description":"\u6d4b\u8bd5\u4e13\u7528\uff01\u5982\u82e5\u9886\u53d6\u8bf7\u5ffd\u7565\uff01","level_set":"0"}';
        $post = json_decode($casnhu,true);
//        dump($post);die;
        $this->writeLog('mem_card_params.log','参数',$post);

        if ($post['level_set']) {
            $leveldata = json_decode(htmlspecialchars_decode($post['level_params']), true);
            foreach($leveldata as $k=>$v){
                if(strlen($v['level_name']) > 12){
                    $this->ajaxReturn(array("code" => "error", "msg" => "等级名称不超过4个汉字"));
                    exit;
                }
            }
        }
        if ($this->memcardModel->where(array("mid" => $this->userId))->getField("id") && empty($c_id)) {
            $this->ajaxReturn(array("code" => "error", "msg" => "一个商户只能创建一张会员卡"));
        }
        if (!$post['cardname']) $this->ajaxReturn(array("code" => "error", "msg" => "未填写卡名"));
//            if (!$post['$brand_name']) $this->ajaxReturn(array("code" => "error", "msg" => "未填写标题"));
        if(mb_strlen($post['cardname']) > 27) $this->ajaxReturn(array("code" => "error", "msg" => "卡名超过9个汉字"));
        if (!$post['color']) $this->ajaxReturn(array("code" => "error", "msg" => "no color"));
        if (!$post['service_phone']) $this->ajaxReturn(array("code" => "error", "msg" => "未填写客服电话"));
        if (!$post['description']) $this->ajaxReturn(array("code" => "error", "msg" => "未填写描述"));
        if (!$post['cardnum']) $post['cardnum'] = 10000;

        // 入库数据
        $post_data = $this->createPost();
        $post = array_merge($post, $post_data);
        // 若有id 表示修改会员卡
        if (!empty($c_id)) {
            // 获取会员卡信息
            $card_data = $this->memcardModel->field('card_id,balance_set,drawnum')->where(array('id' => $c_id))->find();
            // 储值（功能打开后，若有领取，则无法关闭)
            if ($card_data['balance_set'] == 1 && $post['balance_set'] == 0) {
                ($card_data['drawnum'] == 0) ?: $this->ajaxReturn(array('code' => 'error', 'msg' => '已有客户领取，储值无法关闭'));
            }
            $post['card_id'] = $card_data['card_id'];  // 获取会员卡微信编号
            $post['cardstatus'] = 4;
            // 获取token 准备请求
            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
            $curl_datas = $this->createEditJson($post);
            $result = request_post($create_card_url, $curl_datas);
            // 将返回数据转化为数组
            $result = object2array(json_decode($result));

            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $post['update_time'] = time();
                $this->memcardModel->where(array("id" => $post['id']))->save($post);
//                    $this->memcard_query($post['card_id']);
                $this->activateuserform($post);
                if ($post['level_set']) {
                    $this->levelSet($post);
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "会员卡修改成功"));
            } else {
                $this->writeLog('mem_card.log','会员卡修改',$result);
                $this->ajaxReturn(array("code" => "error", "msg" => "会员卡修改失败", 'data' => json_encode($result)));
            }
        } else {
            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/create?access_token=$token";
            $curl_datas = $this->createAddJson($post);
            $this->writeLog('mem_card.log','会员卡创建',$curl_datas,0);

            $result = request_post($create_card_url, $curl_datas);
            $result = object2array(json_decode($result));
            $this->writeLog('mem_card.log','会员卡创建结果',$result);

            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $post['card_id'] = $result['card_id'];
                $post['add_time'] = time();
                if (!$this->memcardModel->where(array("card_id" => $post['card_id']))->getField("id")) {
                    // 是否有积分设置
                    if (!$post['credits_set']) {
                        unset($post['max_increase_bonus']);
                        unset($post['expense_credits']);
                    }
                    if (!$post['integral_dikou']) {
                        unset($post['max_reduce_bonus']);
                        unset($post['credits_discount']);

                    } else {
                        $tag = $post['max_reduce_bonus'] % 10;
                        if($tag != 0){
                            $this->ajaxReturn(array("code" => "error", "msg" => "单笔最多使用积分为10的倍数"));
                        }
                    }
                    $post['cardstatus'] = 4;
                    $post['discount'] = $post['discount']?:10;
                    $res = $this->memcardModel->add($post);
//                        $this->memcard_query($post['card_id']);
                    $this->activateuserform($post);
                    $this->quickPay($res);

                    M('screen_cardset')->add(array('c_id' => $res));
                    if (!$res) $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
                    if ($post['level_set']) {
                        $this->levelSet($post);
                    }
                }
                ////////// 获取二维码
                $token = get_weixin_token();
                $arr = array(
                    "action_name" => "QR_CARD",
                    "action_info" => array(
                        "card" => array(
                            "card_id" => $result['card_id'],
                            "is_unique_code" => false,
                            "outer_id" => 1
                        )
                    )
                );

                $mem_card_query_url = "https://api.weixin.qq.com/card/qrcode/create?access_token=$token";
                $ress = request_post($mem_card_query_url, json_encode($arr));
                $ress = json_decode($ress, true);
                $this->writeLog('mem_card.log','会员卡创建成功',$result);
                if ($ress['errmsg'] == 'ok' && $ress['errcode'] == 0) {
                    $this->memcardModel->where(array("id" => $res))->save(array("show_qrcode_url" => $ress['show_qrcode_url'], "cardstatus" => "4"));
                    $this->writeLog('mem_card.log','二维码获取成功',$ress);
                    $this->ajaxReturn(array("code" => "success", "msg" => "会员卡创建成功"));
                } else {
                    $this->writeLog('mem_card.log','二维码获取失败',$ress);
                    $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
                }
                ////////////
            } else {
                $this->writeLog('mem_card.log','会员卡创建失败',$result);
                $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
            }
        }

    }
    /**
     * 等级设置
     */
    private function levelSet($post)
    {
        $flag = false;
        $card_id = $post['card_id'];
        $c_id = $this->memcardModel->where(array('card_id' => $card_id))->getField('id');
        $levelModel = M('screen_memcard_level');
        $level_params = json_decode(htmlspecialchars_decode($post['level_params']), true);

        if ($post['discount_set']) {
            if(empty($post['discount_params'])){
                $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
                exit;
            }
            $flag = true;
            $discount_params = json_decode(htmlspecialchars_decode($post['discount_params']), true);
        }

        $data = array();
        for ($i = 1; $i <= count($level_params); $i++) {
            if ($flag) {
                $disc = $discount_params[$i - 1]['level_discount'];
                if(empty($disc) || $disc == 0){
                    $disc = 10;
                }
                $data[] = array(
                    'c_id' => $c_id,
                    'level' => $i,
                    'level_name' => $level_params[$i - 1]['level_name'],
                    'level_expense' => $level_params[$i - 1]['level_expense'],
                    'level_integral' => $level_params[$i - 1]['level_integral'],
                    'level_discount' => $disc,
                );
            } else {
                $data[] = array(
                    'c_id' => $c_id,
                    'level' => $i,
                    'level_name' => $level_params[$i - 1]['level_name'],
                    'level_expense' => $level_params[$i - 1]['level_expense'],
                    'level_integral' => $level_params[$i - 1]['level_integral'],
                );
            }
        }
        $levelModel->where(array('c_id' => $c_id))->delete();
        $levelModel->addAll($data);
    }

    /**查询微信会员卡是否创建成功
     * @param string $card_id
     */
    public function memcard_query($card_id)
    {
        $card_id = $card_id ? $card_id : I("card_id");
//        $status_arr = array(
//            "CARD_STATUS_NOT_VERIFY" => 1,
//            "CARD_STATUS_VERIFY_FALL" => 2,
//            "CARD_STATUS_VERIFY_OK" => 3,
//            "CARD_STATUS_USER_DELETE" => 5,
//            "CARD_STATUS_USER_DISPATCH" => 6,
//        );
        if (!$card_id) $this->ajaxReturn(array("code" => "error", "msg" => "card_id为空"));
        $token = get_weixin_token();
        $mem_card_query_url = "https://api.weixin.qq.com/card/get?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode(array("card_id" => $card_id)));
        $result = json_decode($result, true);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            $this->writeLog('card_query.log','QUERY-SUCC',$result);
//            $status = $status_arr[$result['card']['member_card']['base_info']['status']];
//            if (!$status) $status = 1;
//            $this->memcardModel->where(array("card_id" => $card_id))->save(array("cardstatus" => 4));
        } else {
            $this->writeLog('card_query.log','QUERY-FIAL',$result);
        }
    }

    /**
     * 开通快速买单
     */
    public function quickPay($id)
    {
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "ID不能为空"));
        $card_id = $this->memcardModel->where(array("id" => $id))->getField("card_id");
        if (!$card_id) $this->ajaxReturn(array("code" => "error", "msg" => "card_id不能为空"));

        $arr = array('card_id' => $card_id, 'is_open' => true);
        $curl_datas = json_encode($arr);
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/paycell/set?access_token=$token";
        $result = request_post($create_card_url, $curl_datas);
        $result = json_decode($result, true);
        if ($result['errcode'] === 0) {
            return true;
        } else {
            return false;
        }
    }

    public function createPost()
    {
        $default_url = "http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyxauxZN5FX44pnSMj3ZEHSV2k7fOPJIj9VA6T61VzCRhHgkGNlicM8RKmnx5du1ibiaV0L8SA5lsRWsQ/0";
        $img_url = $_POST['logoimg'];
        if(empty($img_url)){
            $img_url = M('merchants')->where(array('uid' => $this->userId))->getField('logo_url');
            $img_url = $img_url ? : $default_url;
        }
        $brand_name = I('brand_name');
        if(empty($brand_name)){
            $brand_name = M('merchants')->where(array('uid' => $this->userId))->getField('merchant_jiancheng');
            if(empty($brand_name)){
                $brand_name = M('merchants_users')->where(array('id' => $this->userId))->getField('user_name');
                if(empty($brand_name)){
                    $brand_name = '会员卡';
                }
            }
        }

        $config = C('MEMCARD');
        $post = array(
            'logoimg' => $img_url,
            'merchant_name' => $brand_name,
            'userphone' => $this->userInfo['user_phone'],
            'mid' => $this->userId,
            'name_activate' => 1,
            'tel_activate' => 1,
            'bir_activate' => 1,
            'credits_use' => 10,
            'expense' => 10,
            'level1' => 0,
            'level2' => 0,
            'level3' => 0,
            'level4' => 0,
            'level5' => 0,
            'level6' => 0,
            'notice' => $config["notice"],
            'prerogative' => $config["prerogative"],
            'custom_url_sub_title' => $config["custom_url_sub_title"],
            'custom_url_name' => $config["custom_url_name"],
            'custom_url' => $config["custom_url"],
            'center_url' => $config["center_url"],
            'center_sub_title' => $config["center_sub_title"],
            'center_title' => $config["center_title"],
            'promotion_url_sub_title' => $config["promotion_url_sub_title"],
            'promotion_url_name' => $config["promotion_url_name"],
            'promotion_url' => $config["promotion_url"],
            'balance_rules' => $config["balance_rules"],
            'balance_url' => $config["balance_url"],
            'level_url' => $config["level_url"],
            'discount_url' => $config["discount_url"],
        );

        return $post;
    }

    /**
     * @param $post
     * @return string
     */
    private function createAddJson(&$post)
    {
        $expense = $post['expense'] * 100;
        $credits_discount = $post['credits_discount'] * 100;
        if(empty($post['merchant_name'])){
            $post['merchant_name'] = M('merchants_agent')->where(array('uid' => $this->userId))->getField('agent_name');
        }
        if(empty($post['logoimg'])){
            $post['logoimg'] = 'http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyxauxZN5FX44pnSMj3ZEHSV2k7fOPJIj9VA6T61VzCRhHgkGNlicM8RKmnx5du1ibiaV0L8SA5lsRWsQ/0';
        }
        $curl_datas = array(
            "card" => array(
                "card_type" => "MEMBER_CARD",
                "member_card" => array(
                    "base_info" => array(
                        "logo_url" => urlencode($post['logoimg']),
                        "brand_name" => urlencode($post['merchant_name']),
                        "code_type" => "CODE_TYPE_QRCODE", //CODE_TYPE_TEXT,CODE_TYPE_BARCODE,CODE_TYPE_QRCODE
                        "title" => urlencode($post['cardname']),
                        "color" => urlencode($post['color']),
                        "notice" => urlencode($post['notice']),
                        "service_phone" => urlencode($post['service_phone']),
                        "description" => urlencode($post['description']),
                        "date_info" => array(
                            "type" => "DATE_TYPE_PERMANENT"
                        ),
                        "sku" => array(
                            "quantity" => urlencode($post['cardnum']),
                        ),
                        "get_limit" => 1,
                        "use_custom_code" => false,
                        "can_give_friend" => false,
//                        "location_id_list" => array(33788392),
//                        "use_all_locations" => true,
                        "need_push_on_view" => true,
//                        "custom_url_sub_title" => urlencode($post['custom_url_sub_title']),
//                        "custom_url_name" => urlencode($post['custom_url_name']),
//                        "custom_url" => urlencode($post['custom_url']),
                        "center_url" => urlencode($post['center_url']),
                        "center_title" => urlencode($post['center_title']),
                        "center_sub_title" => urlencode($post['center_sub_title']),
                    ),
                    "supply_bonus" => true,
                    "supply_balance" => false,
//                    "prerogative" => urlencode($post['prerogative']),
                    "wx_activate" => true,
                )
            )
        );
        // 是否有积分设置
        if ($post['credits_set']) {
            $curl_datas['card']['member_card']['bonus_rule'] = array(
                'max_increase_bonus' => urlencode($post['expense_credits_max']),
                'cost_money_unit' => 1000,
                'increase_bonus' => urlencode($post['expense_credits']),
//                "init_increase_bonus" => urlencode($post['activate_credits']),//初始设置积分。
            );
            $post['prerogative'] .= "每消费10元，赠送{$post['expense_credits']}积分;\n";
            $post['prerogative'] .= "每次赠送上限{$post['expense_credits_max']}积分;\n";
        }
        // 是否设置储值
        if ($post['balance_set']) {
//            $curl_datas['card']['member_card']['supply_balance'] = true;
//            $curl_datas['card']['member_card']['balance_url'] = urlencode($post['balance_url']);
//            $curl_datas['card']['member_card']['balance_rules'] = urlencode($post['balance_rules']);
            $curl_datas['card']['member_card']['custom_field1'] = array(
                "name_type" => "FIELD_NAME_TYPE_DISCOUNT",
                "name" => urlencode("储值"),
                "url" => urlencode($post['balance_url']),
            );
            $curl_datas['card']['member_card']['base_info']['promotion_url_name'] = urlencode($post['promotion_url_name']);
            $curl_datas['card']['member_card']['base_info']['promotion_url'] = urlencode($post['promotion_url']);

        }
        // 是否有等级设置
        if ($post['level_set']) {
            $curl_datas['card']['member_card']['custom_field2'] = array(
                "name_type" => "FIELD_NAME_TYPE_LEVEL",
                "name" => urlencode("等级"),
//                "url" => urlencode($post['level_url']),
            );
            // 特权说明
            if ($post['discount_set']) {
                $flag = true;
                $discount_params = json_decode(htmlspecialchars_decode($post['discount_params']), true);
            }
            $level_params = json_decode(htmlspecialchars_decode($post['level_params']), true);
            $post['prerogative'] .= "等级说明：\n";
            foreach($level_params as $k => $v){
                $post['prerogative'] .= $k+1 . "、购物累积满{$v['level_expense']}元或积分达到{$v['level_integral']}分，即可成为本店{$v['level_name']};";
                if($flag){
                    $post['prerogative'] .= "享受商品{$discount_params[$k]['level_discount']}折优惠;";
                }
                $post['prerogative'] .= "\n";
            }
        }
        // 是否有折扣设置
        if ($post['discount_set']) {
            $curl_datas['card']['member_card']['discount'] = urlencode($post['discount']);
            if(!$post['level_set']){
                $post['prerogative'] .= "享受商品{$post['discount']}折优惠;\n";
            }
        }
        // 是否有抵扣设置
        if ($post['integral_dikou']) {
            $curl_datas['card']['member_card']['bonus_rule'] = array(
                'max_reduce_bonus' => urlencode($post['max_reduce_bonus']),   //抵扣条件，单笔最多使用xx积分。
                'cost_bonus_unit' => 10, // 每使用10积分。
                'reduce_money' => urlencode($credits_discount), //抵扣xx元，（这里以分为单位）
//                "least_money_to_use_bonus" => urlencode($post['expense']), // 抵扣条件，满xx元（这里以分为单位）可用
            );
        }

        // 特权说明
        $curl_datas['card']['member_card']['prerogative'] = urlencode($post['prerogative']);

        return urldecode(json_encode($curl_datas));
    }

    /**
     * @param $post
     * @return string
     */
    private function createEditJson($post)
    {
        $expense = $post['expense'] * 100;
        $credits_discount = $post['credits_discount'] * 100;
        if(empty($post['logoimg'])){
            $post['logoimg'] = 'http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyxauxZN5FX44pnSMj3ZEHSV2k7fOPJIj9VA6T61VzCRhHgkGNlicM8RKmnx5du1ibiaV0L8SA5lsRWsQ/0';
        }
        $curl_datas = array(
            "card_id" => urlencode($post['card_id']),
            "member_card" => array(
                "base_info" => array(
                    "code_type" => "CODE_TYPE_QRCODE", //CODE_TYPE_TEXT,CODE_TYPE_BARCODE,CODE_TYPE_QRCODE
                    "logo_url" => urlencode($post['logoimg']),
                    "title" => urlencode($post['cardname']),
                    "color" => urlencode($post['color']),
                    "notice" => urlencode($post['notice']),
                    "service_phone" => urlencode($post['service_phone']),
                    "description" => urlencode($post['description']),
                    "center_url" => urlencode($post['center_url']),
                    "center_title" => urlencode($post['center_title']),
                    "center_sub_title" => urlencode($post['center_sub_title']),
//                    "location_id_list" => array(33788392),
//                    "use_all_locations" => true,
//                    "custom_url_name" => urlencode($post['custom_url_name']),
//                    "custom_url" => urlencode($post['custom_url']),
//                    "custom_url_sub_title" => urlencode($post['custom_url_sub_title']),
                ),
//                "prerogative" => urlencode($post['prerogative']),
            )
        );
        // 是否有积分设置
        if ($post['credits_set']) {
            $curl_datas['member_card']['bonus_rule'] = array(
                'max_increase_bonus' => urlencode($post['expense_credits_max']),
                'cost_money_unit' => 1000,
                'increase_bonus' => urlencode($post['expense_credits']),
//                "init_increase_bonus" => urlencode($post['activate_credits']),//初始设置积分。
            );
            $post['prerogative'] .= "每消费10元，赠送{$post['expense_credits']}积分;\n";
            $post['prerogative'] .= "每次赠送上限{$post['expense_credits_max']}积分;\n";
        }
        // 是否设置储值
        if ($post['balance_set']) {
//            $curl_datas['member_card']['supply_balance'] = true;
//            $curl_datas['member_card']['balance_url'] = urlencode($post['balance_url']);
//            $curl_datas['member_card']['balance_rules'] = urlencode($post['balance_rules']);
            $curl_datas['member_card']['custom_field1'] = array(
                "name_type" => "FIELD_NAME_TYPE_DISCOUNT",
                "name" => urlencode("储值"),
                "url" => urlencode($post['balance_url']),
            );
            $curl_datas['member_card']['base_info']['promotion_url_name'] = urlencode($post['promotion_url_name']);
            $curl_datas['member_card']['base_info']['promotion_url'] = urlencode($post['promotion_url']);
        }
        // 是否有等级设置
        if ($post['level_set']) {
            $curl_datas['member_card']['custom_field2'] = array(
                "name_type" => "FIELD_NAME_TYPE_LEVEL",
                "name" => urlencode("等级"),
//                "url" => urlencode($post['level_url']),
            );
            // 特权说明
            if ($post['discount_set']) {
                $flag = true;
                $discount_params = json_decode(htmlspecialchars_decode($post['discount_params']), true);
            }
            $level_params = json_decode(htmlspecialchars_decode($post['level_params']), true);
            $post['prerogative'] .= "等级说明：\n";
            foreach($level_params as $k => $v){
                $post['prerogative'] .= $k+1 . "、购物累积满{$v['level_expense']}元或积分达到{$v['level_integral']}分，即可成为本店{$v['level_name']};";
                if($flag){
                    $post['prerogative'] .= "享受商品{$discount_params[$k]['level_discount']}折优惠;";
                }
                $post['prerogative'] .= "\n";
            }
        }
        // 是否有抵扣设置
        if ($post['integral_dikou']) {
            $curl_datas['member_card']['bonus_rule'] = array(
                'max_reduce_bonus' => urlencode($post['max_reduce_bonus']),   //抵扣条件，单笔最多使用xx积分。
                'cost_bonus_unit' => 10, // 每使用5积分。
                'reduce_money' => urlencode($credits_discount), //抵扣xx元，（这里以分为单位）
//                "least_money_to_use_bonus" => urlencode($post['expense']), // 抵扣条件，满xx元（这里以分为单位）可用
            );
        }
        // 是否有折扣设置
        if ($post['discount_set']) {
            $curl_datas['member_card']['discount'] = urlencode($post['discount']);
            if(!$post['level_set']){
                $post['prerogative'] .= "享受商品{$post['discount']}折优惠;\n";
            }
        }
        $curl_datas['member_card']['prerogative'] = urlencode($post['prerogative']);

        return urldecode(json_encode($curl_datas));
    }


    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param);
        }
        file_put_contents($path . date("Y_m_") . $file_name, date("Y-m-d H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/test/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
//        echo $Y;
        if (file_exists($Y)) {
//            echo '存在';
        } else {
            mkdir($Y, 0777, true);
        }

        return $Y . '/';
    }
}