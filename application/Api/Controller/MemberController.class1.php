<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/27
 * Time: 14:10
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Common\Lib\Subtable;

//load('Screen/function');

/**会员、会员卡接口
 * Class MemberController 
 * @package Api\Controller 
 */
class MemberController extends ApibaseController
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
        $this->pay = M(Subtable::getSubTableName('pay'));
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);
    }


    /**返回消费金额
     * @param $merchants_id
     * @param $openid
     * @return string
     */
    private function get_expense_count($merchants_id, $usr_arr)
    {
        $res = M(Subtable::getSubTableName('pay'))->where(array("merchant_id" => $merchants_id, "customer_id" => array('in', $usr_arr), "status" => "1"))
            ->field("sum(price) expense,COUNT(id) expense_count,MAX(paytime) last_expense")
            ->find();
        $return['expense'] = $res['expense'] ? $res['expense'] : "0";   // 累积消费金额
        $return['expense_count'] = $res['expense_count'] ? $res['expense_count'] : "0"; // 累积消费次数
        $return['last_expense'] = $res['last_expense'] ? $res['last_expense'] : ""; // 最近消费

        return $return;
    }

    /**
     * 会员列表
     */
    public function index()
    {
        $per_page = 20;
        $page = intval(I("page", 0));
        $keywords = I("keywords");
        $level_id = intval(I("level_id", 0));
        // 累积消费金额
        $left_expense = I("left_expense", 0);
        $right_expense = I("right_expense", 0);
        // 累积消费次数
        $left_expense_count = intval(I("left_expense_count", 0));
        $right_expense_count = intval(I("right_expense_count", 0));
        // 最近消费时间
        $last_expense = I('last_expense', 0);
        $now = time();

        if ($right_expense != 0 && $left_expense > $right_expense) {
            $this->ajaxReturn(array("code" => "error", "msg" => "起始金额大于结束金额"));
            exit;
        }
        if ($right_expense_count != 0 && $left_expense_count > $right_expense_count) {
            $this->ajaxReturn(array("code" => "error", "msg" => "起始消费次数大于结束消费次数"));
            exit;
        }
        // 会员等级筛选条件
        if ($level_id) $map['levelid'] = $level_id;
        // 电话号码或昵称
        if ($keywords) $map['_string'] = '(m.memphone like "%' . $keywords . '%")  OR (m.nickname like "%' . $keywords . '%") OR (m.realname like "%' . $keywords . '%")';
        //$this->userId = 21;
        $map['m.userid'] = $this->userId;
        $map['m.status'] = '1';
        $map['m.delete'] = '0';
        $data_lists = $this->memberModel
            ->alias("m")
            ->join('ypt_screen_memcard_use u on u.memid=m.id')
            ->where($map)
            ->limit($page * $per_page, $per_page)
            ->order("m.id DESC")
            ->field('m.id,m.memimg,m.realname as nickname,m.memphone,m.openid,m.status')
            ->select();
        $merchants_id = M("merchants")->where(array("uid" => $this->userId))->getField("id");
        // 循环结果，然后判断是否满足筛选条件
        foreach ($data_lists as $k => $v) {
            if (!$v['memimg']) $data_lists[$k]['memimg'] = '';
            // 获取累积消费情况
            $expense_info = $this->get_expense_count($merchants_id, array($v['openid'], $v['id']));
            $data_lists[$k]['expense'] = $expense_info['expense'];
            $data_lists[$k]['expense_count'] = $expense_info['expense_count'];
            $last_expense_time = $expense_info['last_expense'];
            // 删除不需要的数据
            unset($data_lists[$k]['openid']);
            //unset($data_lists[$k]['status']);
//            unset($data_lists[$k]['expense_count']);   2017/7/20 彭鼎修改

            // 判断累积消费金额是否满足筛选条件，不满足则删除
            if ($left_expense != 0 && $data_lists[$k]['expense'] < $left_expense) {
                unset($data_lists[$k]);
            }
            if ($right_expense != 0 && $data_lists[$k]['expense'] > $right_expense) {
                unset($data_lists[$k]);
            }

            // 判断累积消费次数是否满足筛选条件
            if ($left_expense_count != 0 && $data_lists[$k]['expense_count'] < $left_expense_count) {
                unset($data_lists[$k]);
            }
            if ($right_expense_count != 0 && $data_lists[$k]['expense_count'] > $right_expense_count) {
                unset($data_lists[$k]);
            }

            // 判断最近消费
            switch ($last_expense) {
                case 0: // 不限
                    break;
                case 1: // 近3天
                    if (!$last_expense_time || $now - $last_expense_time > 60 * 60 * 24 * 3) unset($data_lists[$k]);
                    break;
                case 2: // 近7天
                    if (!$last_expense_time || $now - $last_expense_time > 60 * 60 * 24 * 7) unset($data_lists[$k]);
                    break;
                case 3: // 近30天
                    if (!$last_expense_time || $now - $last_expense_time > 60 * 60 * 24 * 30) unset($data_lists[$k]);
                    break;
                case 4: // 近一年
                    if (!$last_expense_time || $now - $last_expense_time > 60 * 60 * 24 * 365) unset($data_lists[$k]);
                    break;
                case 5: // 从未消费
                    if ($last_expense_time) unset($data_lists[$k]);
                    break;
            }
        }
        $count = count($data_lists);//总条数
        $total = ceil($count / $per_page);//总页数
        //昨日新增
        $beginYesterday = strtotime(date('Ymd',strtotime("-1 day")));
        $endYesterday = strtotime(date('Ymd'));
        $arr = array(
            "m.add_time" => array('between', array($beginYesterday, $endYesterday)),
            "m.userid" => $this->userId,
            "m.status" => "1"
        );
        $new_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where($arr)->count();
        //总会员
        $total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where(array("m.userid" => $this->userId, "m.status" => "1"))->count();
        //已激活总会员
        $active_total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where(array("m.userid" => $this->userId, "m.status" => "1"))->count();
        // 数组索引重建
        $data_lists = array_values($data_lists);
        $this->ajaxReturn(
            array(
                "code" => "success",
                "msg" => "成功",
                "data" => array(
                    "total" => strval($total),
                    "data" => $data_lists,
                    "new_member" => $new_member,
                    "total_member" => $total_member,
                    "activate_member_total" => $active_total_member
                )
            )
        );
        exit;
    }


    /**
     * 获取会员卡
     */
    public function get_memcard()
    {
        $status_arr = array(
            "1" => "审核中",
            "2" => "审核失败",
            "3" => "审核成功",
            "4" => "已投放",
        );
        $map['mid'] = $this->userId;
        $res = $this->memcardModel->where($map)->field("cardnum,drawnum,cardstatus,color,show_qrcode_url,card_id,id,logoimg")->find();
        if ($res) {
            $res['desc'] = $status_arr[$res['cardstatus']];
            if (!$res['show_qrcode_url']) $res['show_qrcode_url'] = '';
            $res['remain'] = strval($res['cardnum'] - $res['drawnum']);
            $res['activate_num'] = $this->memcard_use_Model->where(array("card_id" => $res['card_id'], "status" => "1"))->count();
        } else
            $res = (object)null;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
    }


    /**
     * 会员卡详情
     */
    public function get_memcard_info()
    {
        $id = I("id");
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "编号为空"));
        $map['id'] = $id;
        $res = $this->memcardModel->where($map)->field("*")->find();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
    }


    /**
     * 会员等级
     */
    public function get_member_level()
    {
        $card_id = I("card_id");
        $openid = I("openid");
        $where = array(
            "smu.card_id" => $card_id,
            "smu.fromname" => $openid,
            "smu.status" => "1",
        );

        $this->memcard_use_Model
            ->alias("smu")
            ->where($where);
        $field = 'smu.card_amount,smu.card_balance,sm.level1,sm.level2,sm.level3,sm.level4,sm.level5,sm.level6';
        $this->memcard_use_Model->field($field);
        $this->memcard_use_Model->join(" JOIN __SCREEN_MEMCARD__ sm ON smu.card_id = sm.card_id");
        $info = $this->memcard_use_Model->find();
        if ($info['card_amount'] >= $info['level1'] && $info['card_amount'] <= $info['level3']) {
            $info['level'] = 1;
        } else if ($info['card_amount'] > $info['level2'] && $info['card_amount'] <= $info['level5']) {
            $info['level'] = 2;
        } else if ($info['card_amount'] > $info['level4']) {
            $info['level'] = 3;
        }

        $this->assign("info", $info);
        $this->display();
    }


    /**创建会员卡添加json字符串
     * @param $post
     * @return mixed
     */
    private function create_jsonstr($post)
    {
        $post['expense'] = $post['expense'] * 100;
        $post['credits_discount'] = $post['credits_discount'] * 100;
        $curl_datas = array(
            "card" => array(
                "card_type" => "MEMBER_CARD",
                "member_card" => array(
                    "base_info" => array(
                        "logo_url" => urlencode($post['logoimg']),
                        "brand_name" => urlencode($post['merchant_name']),
                        "code_type" => "CODE_TYPE_TEXT",
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
                        "location_id_list" => array(
                            123,
                            12321,
                            345345),
//                        "custom_url_name" => urlencode($post['custom_url_name']),
//                        "custom_url" => urlencode($post['custom_url']),
//                        "custom_url_sub_title" => urlencode($post['custom_url_sub_title']),
                        "promotion_url_name" => urlencode($post['promotion_url_name']),
                        "promotion_url" => urlencode($post['promotion_url']),
                        "need_push_on_view" => true
                    ),
                    "supply_bonus" => true,
                    "supply_balance" => false,
                    "prerogative" => urlencode($post['prerogative']),
                    "wx_activate" => true,
                    "custom_field1" => array(
                        "name_type" => "FIELD_NAME_TYPE_LEVEL",
                        "url" => urlencode($post['url']),
                    ),
                    //"activate_url" => "http://www.xxx.com",
//                    "custom_cell1" => array(
//                        "name" => urlencode($post['name']),
//                        "tips" => urlencode($post['tips']),
//                        "url" => "http://www.xxx.com"
//                    ),
                    "bonus_rule" => array(
                        "cost_money_unit" => urlencode($post['expense']),
                        "increase_bonus" => urlencode($post['expense_credits']),
                        "max_increase_bonus" => urlencode($post['max_increase_bonus']),
                        "init_increase_bonus" => urlencode($post['activate_credits']),
                        "cost_bonus_unit" => urlencode($post['credits_use']),
                        "reduce_money" => urlencode($post['credits_discount']),
                        "least_money_to_use_bonus" => urlencode($post['expense']),
                        "max_reduce_bonus" => urlencode($post['max_reduce_bonus']),
                    ),
                    // "discount" => 10
                )
            )
        );

        return urldecode(json_encode($curl_datas));
    }


    /**
     * 图片上传
     */
    private function upload_pic()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'memcard/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
        }
        $img = $info['logoimg'] ? $pic_root_path . $info['logoimg']['savepath'] . $info['logoimg']['savename'] : '';
        return $img;
    }

    /**
     * 微信会员卡添加
     */
    public function add_memcard()
    {
        if (IS_POST) {
            $post = I("");
            $img = $this->upload_pic();
            $post['logoimg'] = $img ? $this->host . $img : 'http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png';
            //if (!$post['merchant_name']) $post['merchant_name'] = '洋仆淘商城' . date("is");
            $jianchen = M("merchants_cate mc")->where(array("m.uid" => $this->userId))->join("LEFT JOIN __MERCHANTS__ m ON mc.merchant_id= m.id")->getField("jianchen");
            $post['merchant_name'] = $jianchen ? $jianchen : mb_substr($post['merchant_name'], 0, 10, 'utf-8');
            if (!$post['cardname']) $post['cardname'] = '洋仆淘会员卡';
            if (mb_strlen($post['cardname'], 'utf8') > 9) $this->ajaxReturn(array("code" => "error", "msg" => "会员卡名称不能超过9个汉字"));
            if (mb_strlen($post['merchant_name'], 'utf8') > 12) $this->ajaxReturn(array("code" => "error", "msg" => "商家简称不能超过12个汉字"));
            if (!$post['color']) $post['color'] = 'Color010';
            if ($this->memcardModel->where(array("cardname" => $post['cardname'], "mid" => $this->userId))->getField("id")) {
                $this->ajaxReturn(array("code" => "error", "msg" => "会员卡不能重复创建"));
            }
            if (!$post['service_phone']) $post['service_phone'] = '400-888-3658';
            if (!$post['description']) $post['description'] = '1.会员卡仅限申请者本人使用,不可转让与他人;\n2.会员结账时,请主动提供会员卡号或注册手机号。';

            $post['custom_url_name'] = '立即使用';
            $post['custom_url'] = 'http://m.hz41319.com/wei/index.php';
            $post['custom_url_sub_title'] = '点击激活';

            $post['promotion_url_name'] = '更多推荐';
            $post['promotion_url'] = 'http://m.hz41319.com/wei/index.php';
            $post['url'] = $this->host . '/index.php?s=Api/Member/get_member_level';//会员等级

            if (!$post['prerogative']) $post['prerogative'] = '领卡后会员享专属优惠!';
            if (!$post['cardnum']) $post['cardnum'] = '100000';//发卡总量
            if (!$post['expense']) $post['expense'] = '10';//消费10元
            if (!$post['expense_credits']) $post['expense_credits'] = '1';//消费10元送1积分
            if (!$post['activate_credits']) $post['activate_credits'] = '10';//激活送10积分
            if (!$post['credits_use']) $post['credits_use'] = '10';//使用10积分
            if (!$post['credits_discount']) $post['credits_discount'] = '1';//使用10积分抵扣1块钱
            $post['max_reduce_bonus'] = '10000';//单笔最多使用xx积分
            $post['max_increase_bonus'] = '10000';//单次赠送最大积分

            if (!$post['level1']) $post['level1'] = 0;
            if (!$post['level2']) $post['level2'] = 100;
            if (!$post['level3']) $post['level3'] = 101;
            if (!$post['level4']) $post['level4'] = 1000;
            if (!$post['level5']) $post['level5'] = 1001;
            if (!$post['level6']) $post['level6'] = 10000;
            if ($post['level1'] >= $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "银卡的积分上限值必须大于下限值"));
            if ($post['level3'] - 1 != $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分下限值必须等于银卡积分上限值+1"));
            if ($post['level3'] >= $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分上限值必须大于下限值"));
            if ($post['level5'] - 1 != $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分下限值必须等于金卡积分上限值+1"));
            if ($post['level5'] >= $post['level6']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分上限值必须大于下限值"));
            $curl_datas = $this->create_jsonstr($post);
            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/create?access_token=$token";
            $result = request_post($create_card_url, $curl_datas);
            $result = object2array(json_decode($result));
            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $post['card_id'] = $result['card_id'];
                $post['add_time'] = time();
                $post['update_time'] = time();
                $post['mid'] = $this->userId;
                if (!$this->memcardModel->where(array("card_id" => $post['card_id']))->getField("id")) {
                    $res = $this->memcardModel->add($post);
                    $this->memcard_query($post['card_id']);
                    $this->activateuserform($post);
                    if (!$res) $this->ajaxReturn(array("code" => "error", "msg" => "创建会员卡失败"));
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "创建会员卡成功"));
            } else {
                file_put_contents('./data/log/member/weixin.log', date("Y-m-d H:i:s") . '创建会员卡失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn(array("code" => "error", "msg" => "创建会员卡失败"));
            }

        }
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
            "required_form" => array(
                "common_field_id_list" => array(
                    "USER_FORM_INFO_FLAG_MOBILE",
                    "USER_FORM_INFO_FLAG_NAME",
                    "USER_FORM_INFO_FLAG_BIRTHDAY"
                )
            )
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode($arr));
        $result = json_decode($result, true);
        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '创建会员卡时一键开卡' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**查询微信会员卡是否创建成功
     * @param string $card_id
     */
    public function memcard_query($card_id)
    {
        $card_id = $card_id ? $card_id : I("card_id");
        $status_arr = array(
            "CARD_STATUS_NOT_VERIFY" => 1,
            "CARD_STATUS_VERIFY_FALL" => 2,
            "CARD_STATUS_VERIFY_OK" => 3,
            "CARD_STATUS_USER_DELETE" => 5,
            "CARD_STATUS_USER_DISPATCH" => 6,
        );
        if (!$card_id) $this->ajaxReturn(array("code" => "error", "msg" => "card_id为空"));
        $token = get_weixin_token();
        $mem_card_query_url = "https://api.weixin.qq.com/card/get?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode(array("card_id" => $card_id)));
        $result = json_decode($result, true);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            $status = $status_arr[$result['card']['member_card']['base_info']['status']];
            if (!$status) $status = 1;
            $this->memcardModel->where(array("card_id" => $card_id))->save(array("cardstatus" => 4));
        } else {
            file_put_contents('./data/log/member/weixin.log', date("Y-m-d H:i:s") . '查询会员卡失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }


    /**
     * 微信会员卡投放
     * 获取微信会员卡二维码台签
     */
    public function get_memcard_barcode()
    {
        if ($_REQUEST) {
            $id = I("id");
            if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "ID不能为空"));
            $card_id = $this->memcardModel->where(array("id" => $id))->getField("card_id");
            if (!$card_id) $this->ajaxReturn(array("code" => "error", "msg" => "card_id不能为空"));
            $token = get_weixin_token();
            $arr = array(
                "action_name" => "QR_CARD",
                "action_info" => array(
                    "card" => array(
                        "card_id" => "$card_id",
                        //"code" => "198374613512",
                        "is_unique_code" => false,
                        "outer_id" => 1
                    )
                )
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/qrcode/create?access_token=$token";
            $result = request_post($mem_card_query_url, json_encode($arr));
            $result = json_decode($result, true);

            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $this->memcardModel->where(array("id" => $id))->save(array("show_qrcode_url" => $result['show_qrcode_url'], "cardstatus" => "4"));
                $this->ajaxReturn(array("code" => "success", "msg" => "获取会员卡二维码成功", "data" => $result['show_qrcode_url']));
            } else {
                file_put_contents('./data/log/member/weixin.log', date("Y-m-d H:i:s") . '获取会员卡二维码失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn(array("code" => "error", "msg" => "获取会员卡二维码失败"));
            }
        }


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

            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '更新会员卡信息' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $this->ajaxReturn(array("code" => "success", "msg" => "更新会员卡信息成功", "data" => $result));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "更新会员卡信息失败", "data" => $result));
            }
        }
    }


    /**
     * 创建货架
     * 跳转会员卡领取的第三方页面
     * 用于支付后回调页面领取会员卡链接
     */
    public function create_shelves()
    {
        if ($_REQUEST) {
            $token = get_weixin_token();
            $card_id = 'pyaFdwJ-m1uf9S2P2MXcSvo2xX1Y';
            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("洋仆淘"),//地址栏标题
                "can_share" => true,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" => array(
                    array(
                        "card_id" => $card_id,
                        "thumb_url" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png",//列表logo
                    )
                )
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/landingpage/create?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
            $result = json_decode($result, true);
            redirect($result['url']);
        }
    }


    /**
     * 获取会员信息
     */
    public function get_userinfo($card_id, $code)
    {
        $token = get_weixin_token();
        $arr = array(
            "code" => "$code",
            "card_id" => "$card_id",
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/userinfo/get?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode($arr));
        $result = json_decode($result, true);
        $data = array(
            "openid" => $result['openid'],
            "nickname" => $result['nickname'],
            "membership_number" => $result['membership_number'],
            "bonus" => $result['bonus'],
            "sex" => $result['sex'],
            "realname" => '',
            "birthday" => '',
            "memphone" => '',
        );

        foreach ($result['user_info']['common_field_list'] as $k => $v) {
            if ($v['name'] == 'USER_FORM_INFO_FLAG_MOBILE') {
                $data['memphone'] = $v['value'];
            } else if ($v['name'] == 'USER_FORM_INFO_FLAG_BIRTHDAY') {
                $data['birthday'] = $v['value'];
            } else if ($v['name'] == 'USER_FORM_INFO_FLAG_NAME') {
                $data['realname'] = $v['value'];
            }
        }
        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '用户信息' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $data;
    }

    /**
     * 接收用户领取会员卡后的事件推送处理
     * 更新会员卡表库存
     * 插入领取表记录
     * 插入会员信息
     */
    public function activate_memcard($object)
    {
        $memcardModel = M("screen_memcard");
        $memcard_use_Model = M("screen_memcard_use");

        $data = array(
            "card_id" => "$object->CardId",
            "toname" => "$object->ToUserName",
            "fromname" => "$object->FromUserName",
            "create_time" => time(),
            "friendname" => "$object->FriendUserName",
            "card_code" => "$object->UserCardCode",
            "outerid" => "$object->OuterId",
            "status" => "0",
        );

        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '领取会员卡参数:' . json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
        //判断是否已领取
        $receive = $memcard_use_Model->where(array("card_id" => $data['card_id'], "fromname" => $data['fromname']))->field("card_id")->find();
        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '会员卡领取信息:' . json_encode($receive) . PHP_EOL, FILE_APPEND | LOCK_EX);
        //发卡信息
        $info = $memcardModel->where(array("card_id" => $data['card_id']))->field("activate_credits,mid")->find();
        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '会员卡发卡信息:' . json_encode($info) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if (!$receive) {//判断是否已领取
            $sql2 = "INSERT INTO `ypt_screen_memcard_use` (`card_id`, `toname`, `fromname`, `friendname`, `byfriend`, `card_code`, `old_card_code`, `status`, `create_time`) VALUES ('" . $data["card_id"] . "', '" . $data["toname"] . "',  '" . $data["fromname"] . "','" . $data["friendname"] . "', '" . $data["byfriend"] . "', '" . $data["card_code"] . "', '" . $data["old_card_code"] . "', '0', '" . $data["create_time"] . "')";
            //$res = $memcard_use_Model->data($data)->add();
            $res = M()->execute($sql2);
            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '添加领取表:' . $sql2 . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($res) {
                //更新库存
                $memcardModel->where(array("card_id" => $data['card_id']))->setInc('drawnum');
                $userinfo = $this->get_wx_user_info($data['fromname']);

                //插入会员表信息
                $usr_arr = array(
                    "openid" => $data['fromname'],
                    "add_time" => time(),
                    "userid" => $info['mid'],
                    "memimg" => $userinfo['headimgurl'],
                    "nickname" => $userinfo['nickname'],
                );
                file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '插入会员表信息:' . json_encode($usr_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                M("screen_mem")->add($usr_arr);
                file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '插入会员表sql:' . M("screen_mem")->_sql() . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

        }

    }

    /**
     * 根据openid获取微信用户信息
     * @param string $openid
     * @return mixed
     */
    public function get_wx_user_info($openid = 'oyaFdwGG6w5U-RGyeh1yWOMoj5fM')
    {
        $token = get_weixin_token();
        $user_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
        $result = request_post($user_url);
        $result = json_decode($result, true);
        return $result;
    }


    /**
     * 会员卡用户提交资料推送
     * 激活会员卡，注册会员
     */
    public function activate_member($object)
    {
        $memcardModel = M("screen_memcard");
        $memcard_use_Model = M("screen_memcard_use");

        $data = array(
            "card_id" => "$object->CardId",
            "toname" => "$object->ToUserName",
            "fromname" => "$object->FromUserName",
            "create_time" => time(),
            "friendname" => "$object->FriendUserName",
            "card_code" => "$object->UserCardCode",
            "outerid" => "$object->OuterId",
            "status" => "0",
        );

        //发卡信息
        $info = $memcardModel->where(array("card_id" => $data['card_id']))->field("activate_credits,mid")->find();
        $token = get_weixin_token();
      
        $arr = array(
            "init_bonus" => $info['activate_credits'],//初始积分
            "init_balance" => "0",//初始余额
            "membership_number" => "$object->UserCardCode",//会员卡编号
            "card_id" => "$object->CardId",
            "code" => "$object->UserCardCode",
        );
        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activate?access_token=$token";
        $result = request_post($mem_card_query_url, json_encode($arr));
        $result = json_decode($result, true);
        file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '激活会员卡结果:' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            //更新会员卡总积分
//            $memcard_use_Model->where(array("card_code" => $data['card_code']))->setInc('card_amount', $info['activate_credits']);
//            $memcard_use_Model->where(array("card_code" => $data['card_code']))->setInc('card_balance', $info['activate_credits']);
            $memcard_use_Model->where(array("card_code" => $data['card_code']))->save(array("status" => 1));
            //更新会员表信息
            $user_info = $this->get_userinfo($data['card_id'], $data['card_code']);
            $usr_arr = array(
                "realname" => $user_info['realname'],
                "birthday" => $user_info['birthday'],
                "memphone" => $user_info['memphone'],
            );
            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '更新会员表信息:' . json_encode($usr_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $memid = M("screen_mem")->where(array("openid" => $data['fromname'], "userid" => $info['mid']))->save($usr_arr);
            $sql_m = M("screen_mem")->_sql();
            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '更新会员表sql:' . $sql_m . PHP_EOL, FILE_APPEND | LOCK_EX);
            $credits_arr = array(
                "memid" => $memid,
                "point" => $info['activate_credits'],
                "cardid" => $data['card_id'],
                "add_time" => time(),
            );
            //插入积分记录
            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '插入积分记录:' . json_encode($credits_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
            M("memcard_user")->add($credits_arr);
            file_put_contents('./data/log/member/huiyuanka.log', date("Y-m-d H:i:s") . '插入积分记录sql:' . M("memcard_user")->_sql() . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }


    /**
     * 修改会员卡
     * @return mixed
     */
    public function edit_memcard()
    {
        if (IS_POST) {
            $post = I("");
            $img = $this->upload_pic();
            $post['logoimg'] = $img ? $this->host . $img : 'http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png';

            if (!$post['cardname']) $post['cardname'] = '洋仆淘';
            if ($post['merchant_name']) unset($post['merchant_name']);
            if (!$post['id']) $this->ajaxReturn(array("code" => "error", "msg" => "会员卡编号不能为空!"));
            if (!$post['color']) $post['color'] = 'Color100';
            if (!$post['service_phone']) $post['service_phone'] = '400-888-36586';
            if (!$post['description']) $post['description'] = '1.会员卡仅限申请者本人使用,不可转让与他人;\n2.会员结账时,请主动提供会员卡号或注册手机号。';

            $post['custom_url_name'] = '立即使用';
            $post['custom_url'] = 'http://m.hz41319.com/wei/index.php';
            $post['custom_url_sub_title'] = '点击激活';

            $post['promotion_url_name'] = '更多推荐';
            $post['promotion_url'] = 'http://m.hz41319.com/wei/index.php';
            $post['url'] = $this->host . '/index.php?s=Api/Member/get_member_level';//会员等级

            if (!$post['prerogative']) $post['prerogative'] = '领卡后会员享专属优惠!';
            if (!$post['cardnum']) $post['cardnum'] = '100000';//发卡总量
            if (!$post['expense']) $post['expense'] = '10';//消费10元
            if (!$post['expense_credits']) $post['expense_credits'] = '1';//消费10元送1积分
            if (!$post['activate_credits']) $post['activate_credits'] = '10';//激活送10积分
            if (!$post['credits_use']) $post['credits_use'] = '10';//使用10积分
            if (!$post['credits_discount']) $post['credits_discount'] = '1';//使用10积分抵扣1块钱
            $post['max_reduce_bonus'] = '10000';//单笔最多使用xx积分
            $post['max_increase_bonus'] = '10000';//单次赠送最大积分

            if (!$post['level1']) $post['level1'] = 0;
            if (!$post['level2']) $post['level2'] = 100;
            if (!$post['level3']) $post['level3'] = 101;
            if (!$post['level4']) $post['level4'] = 1000;
            if (!$post['level5']) $post['level5'] = 1001;
            if (!$post['level6']) $post['level6'] = 10000;
            if ($post['level1'] >= $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "银卡的积分上限值必须大于下限值"));
            if ($post['level3'] - 1 != $post['level2']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分下限值必须等于银卡积分上限值+1"));
            if ($post['level3'] >= $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "金卡的积分上限值必须大于下限值"));
            if ($post['level5'] - 1 != $post['level4']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分下限值必须等于金卡积分上限值+1"));
            if ($post['level5'] >= $post['level6']) $this->ajaxReturn(array("code" => "error", "msg" => "白金卡的积分上限值必须大于下限值"));

            $post['update_time'] = time();
            $card_info = $this->memcardModel->where(array("id" => $post['id']))->field("card_id")->find();
            $post['card_id'] = $card_info['card_id'];
            if (!$post['card_id']) $this->ajaxReturn(array("code" => "error", "msg" => "会员卡不存在"));

            $curl_datas = $this->edit_jsonstr($post);

            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
            $result = request_post($create_card_url, $curl_datas);
            $result = object2array(json_decode($result));
            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                $this->memcardModel->where(array("id" => $post['id']))->save($post);
                //echo $this->memcardModel->_sql();
                $this->memcard_query($post['card_id']);
                $this->activateuserform($post);
                $this->ajaxReturn(array("code" => "success", "msg" => "修改会员卡成功"));
            } else {
                //print_r($result);
                file_put_contents('./data/log/member/weixin.log', date("Y-m-d H:i:s") . '修改会员卡失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn(array("code" => "error", "msg" => "修改会员卡失败"));
            }

        }
    }


    /**修改会员卡添加json字符串
     * @param $post
     * @return mixed
     */
    private function edit_jsonstr($post)
    {
        $post['expense'] = $post['expense'] * 100;
        $post['credits_discount'] = $post['credits_discount'] * 100;
        $curl_datas = array(
            "card_id" => urlencode($post['card_id']),
            "member_card" => array(
                "base_info" => array(
                    "logo_url" => urlencode($post['logoimg']),
                    "color" => urlencode($post['color']),
                    "notice" => urlencode($post['notice']),
                    "service_phone" => urlencode($post['service_phone']),
                    "description" => urlencode($post['description']),
                    "code_type" => "CODE_TYPE_TEXT",
                    "title" => urlencode($post['cardname']),
                    "promotion_url_name" => urlencode($post['promotion_url_name']),
                    "promotion_url" => urlencode($post['promotion_url']),
                ),

                "prerogative" => urlencode($post['prerogative']),
                "custom_field1" => array(
                    "name_type" => "FIELD_NAME_TYPE_LEVEL",
                    "url" => urlencode($post['url']),
                ),

                "bonus_rule" => array(
                    "cost_money_unit" => urlencode($post['expense']),
                    "increase_bonus" => urlencode($post['expense_credits']),
                    "max_increase_bonus" => urlencode($post['max_increase_bonus']),
                    "init_increase_bonus" => urlencode($post['activate_credits']),
                    "cost_bonus_unit" => urlencode($post['credits_use']),
                    "reduce_money" => urlencode($post['credits_discount']),
                    "least_money_to_use_bonus" => urlencode($post['expense']),
                    "max_reduce_bonus" => urlencode($post['max_reduce_bonus']),
                ),
            )
        );

        return urldecode(json_encode($curl_datas));
    }

    /**查看会员信息
     * @param id
     * @return data
     */
    public function mem_info()
    {
        $id = I('id');
        if(!$id){
            $this->ajaxReturn(array("code" => "error", "msg" => "id为空"));
        }
        $map['m.userid'] = $this->userId;
        $map['mu.memid'] = $id;
        $map['m.status'] = 1;
        $data = $this->memcard_use_Model->alias('mu')
            ->join("join __SCREEN_MEM__ m on mu.memid = m.id")
            ->join("left join __SCREEN_MEMCARD__ sm on mu.memcard_id=sm.id")
            ->join("left join __SCREEN_MEMCARD_LEVEL__ ml on ml.c_id=sm.id")
            ->field('m.memimg,m.sex,m.nickname,m.nickname,m.realname,m.memphone,mu.card_code,mu.memid,mu.card_balance,mu.card_amount,mu.yue,sm.level_set,sm.id')
            ->where($map)
            ->find();
        if ($data) {
            if ($data['level_set'] == 0) {
                $data['level_name'] = '无';
            } else {
                $data['level_name'] = M('screen_memcard_level')->where("c_id=$data[id] and level_integral<=$data[card_amount]")->order('level desc')->getField('level_name');
            }
			if(!$data['level_name']){$data['level_name']='无';}
            $this->ajaxReturn(array("code" => "success", "msg" => "获取会员信息成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "获取会员信息失败"));
        }
    }

    /**查看会员优惠券信息
     * @param id
     * @return data
     */
    public function mem_coupons()
    {
        $id = I('id');
        $mid = M('merchants')->where(array('uid' => $this->userId))->getField('id');
        $map['mem.id'] = $id;
        $map['mem.userid'] = $this->userId;
        $map['uc.status'] = 1;
        $map['c.end_timestamp'] = array("EGT", time());
        $map['m.id'] = $mid;

        $data = $this->user_coupons->alias('uc')
            ->join("left join __SCREEN_COUPONS__ c on uc.coupon_id=c.id")
            ->join("left join __MERCHANTS__ m on c.mid=m.id")
            //->join("join __SCREEN_MEM__ mem on m.uid=mem.userid")
            ->join("join __SCREEN_MEM__ mem on uc.unionid=mem.unionid")
            ->field("m.merchant_name,m.logo_url,c.color,c.total_price,c.de_price,c.end_timestamp")
            ->order("c.end_timestamp asc")
            ->where($map)
            ->select();
        foreach ($data as $k => $v) {
            $isMatched = preg_match('/^((https|http|ftp|rtsp|mms)?:\/\/)[^\s]+/', $v['logo_url']);
            if (!$isMatched) {
                $data[$k]['logo_url'] = "http://sy.youngport.com.cn" . $v['logo_url'];
            }
        }
        if ($data) {
            $this->ajaxReturn(array("code" => "success", "msg" => "获取会员优惠券信息成功", "data" => $data));
        } else {
            //$this->ajaxReturn(array("code" => "error", "msg" => "暂无优惠券"));
            $this->ajaxReturn(array("code" => "success", "msg" => "暂无优惠券","data"=>array()));
        }
    }

    /**查看会员消费记录
     * @param id
     * @return data
     */
    public function mem_pay()
    {
    	
        $id = I('id');
        $mid = $this->userId;
        $uid = M('merchants')->where(array('uid'=>$mid))->getField('id');
        $openid = M('screen_mem')->where(array('id'=>$id))->getField('openid');
//      $map['p.merchant_id'] =  $uid;
//      $map['p.customer_id'] = $id;
//      $map['p.status'] = 1;
		
        $map = 'p.merchant_id = '.$uid.' and (p.customer_id = '.$id.' or p.customer_id = "'.$openid.'") and p.status=1';
        
         add_log($map);
        $data = $this->pay->alias('p')
            ->field("p.paytime,p.price,p.paystyle_id,p.status,p.add_time")
            ->order("paytime desc")
            ->where($map)
            ->select();
        if ($data) {
            $total = '';
            foreach ($data as $k => $v) {
                $total += $v['price'];
                if(!$data[$k]['add_time']) $data[$k]['add_time']=$data[$k]['paytime'];
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "获取会员消费记录成功", "data" => $data, "total_price" => $total));
        } else {
            //$this->ajaxReturn(array("code" => "error", "msg" => "暂无消费记录"));
            $this->ajaxReturn(array("code" => "success", "msg" => "暂无消费记录","data"=>array(),"total_price" => '0'));
        }
    }

    /**查看会员充值记录
     * @param id
     * @return data
     */
    public function mem_recharge_log()
    {
        $id = I('id');

        $map['mem.userid'] = $this->userId;
        $map['r.uid'] = $id;
        $map['r.status'] = 1;

        $data = M('user_recharge')->alias('r')
            ->join("join __SCREEN_MEM__ mem on r.uid=mem.id")
            ->field("r.price,r.total_price,r.add_time")
            ->order("add_time desc")
            ->where($map)
            ->select();
        if ($data) {
            $this->ajaxReturn(array("code" => "success", "msg" => "获取会员充值记录成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "暂无充值记录"));
        }
    }

    /*******************************************************************************************************************
     * 以下为收银1.3新改版会员卡操作
     * 创建和修改会员卡
     *******************************************************************************************************************/
    public function addCommonMemcard()
    {
        if (IS_POST) {
            $this->checkLogin();
            $c_id = I('id');
            $post = I("");
            file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card_params.log', date("Y-m-d H:i:s") . '会员卡' . json_encode($post) . PHP_EOL, FILE_APPEND | LOCK_EX);

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
                    $this->memcard_query($post['card_id']);
                    $this->activateuserform($post);
                    if ($post['level_set']) {
                        $this->levelSet($post);
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "会员卡修改成功"));
                } else {
                    file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '会员卡修改失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->ajaxReturn(array("code" => "error", "msg" => "会员卡修改失败", 'data' => json_encode($result)));
                }
            } else {
                $token = get_weixin_token();
                $create_card_url = "https://api.weixin.qq.com/card/create?access_token=$token";
                $curl_datas = $this->createAddJson($post);
                file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '会员卡[cnhu]' . $curl_datas . PHP_EOL, FILE_APPEND | LOCK_EX);

                $result = request_post($create_card_url, $curl_datas);
                $result = object2array(json_decode($result));

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
                        $this->memcard_query($post['card_id']);
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
                    file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '会员卡创建成功' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    if ($ress['errmsg'] == 'ok' && $ress['errcode'] == 0) {
                        $this->memcardModel->where(array("id" => $res))->save(array("show_qrcode_url" => $ress['show_qrcode_url'], "cardstatus" => "4"));
                        file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '二维码获取成功' . json_encode($ress) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        $this->ajaxReturn(array("code" => "success", "msg" => "会员卡创建成功"));
                    } else {
                        file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '二维码获取失败' . json_encode($ress) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
                    }
                    ////////////
                } else {
                    file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_card.log', date("Y-m-d H:i:s") . '会员卡创建失败' . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败"));
                }
            }
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

    public function weupdate()
    {
        if(IS_POST){
            $cardid = I('card_id');
            if(empty($cardid)){
                exit("-");
            }
            $curl_datas = array(
                "card_id" => $cardid,
                "member_card" => array(
                    "base_info" => array(
                        "code_type" => "CODE_TYPE_QRCODE", //CODE_TYPE_TEXT,CODE_TYPE_BARCODE,CODE_TYPE_QRCODE
                    ),
                    "custom_field1" => array(
                        "name_type" => "FIELD_NAME_TYPE_LEVEL",
                        "name" => urlencode("等级"),
                        "url" => "",
                    ),
                )
            );

            $curl_datas = urldecode(json_encode($curl_datas));

            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
            $result = request_post($create_card_url, $curl_datas);
            // 将返回数据转化为数组
            $result = object2array(json_decode($result));

            dump($result);
        } else {
            exit;
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

    /**
     * 投放规则
     */
    public function deliveryRules()
    {
        if (IS_POST) {
            $this->checkLogin();
            $type = I('type');
            $data = I('data');
            $c_id = I('id');
            // 修改设置
            if (!$c_id) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => 'No Id'));
            }
            $arr = array($type => $data);
            M('screen_cardset')->where(array('c_id' => $c_id))->save($arr);
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK'));
        }
    }
	
	/**
     * 验证会员卡储值支付密码
     */
	public function checkPayPass()
    {
        $pass = I('password');
        $code = I('card_code');
        $pwd = $this->memcard_use_Model->where("card_code='$code'")->getField('pay_pass');
        if($pwd==md5($pass.'tiancaijing')){
            //插入order表
            $jmt_remark= trim(I("jmt_remark"));
            $order_info = array();
            $order_sn = date('YmdHis').mt_rand(10000,99999);//流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"]  = $order_amount;//应收金额
            $order_info["pay_status"]  = 1;//支付状态为1
            $order_info["type"]  = "0";//0为收银订单
            $order_info["order_status"]  = "5";//1.待付款，5.交易成功
            $order_info['integral']=I('dikoufen');//该订单使用积分
            $order_info['integral_money']=I('dikoujin');//该订单使用积分抵扣金额
            $coupon_code  = I("coupon_code","");
            $order_info["coupon_code"]  = $coupon_code;//优惠券ID
            $order_info["coupon_price"]  = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = 0;//商品数量为0
            $order_info["total_amount"]  = I("total_amount");//订单总价
            $order_info["user_money"]  = I("yue");//使用余额
            $user_id  = I('uid')?I('uid'):$this->userId;
            $order_info["user_id"]  = $user_id;
            $order_info["add_time"]  = I("timestamp");
            $order_info["discount"]  = I("discount") * 100;//整单折扣
            $order_info["order_benefit"]  = I("order_benefit");//整单优惠金额
            $order_info["card_code"]= $code;//会员卡号
            $order = M('order');
            $role_id  = M('merchants_role_users')->where(array('uid'=>$user_id))->getField('role_id');
            if($role_id=='7'){
                $pid =  M('merchants_users')->where(array('id'=>$user_id))->getField('pid');
                $merchant_id  =  M('merchants')->where(array('uid'=>$pid))->getField('id');
                $checker_id=$this->userId;
            }else{
                $merchant_id = M('merchants')->where(array('uid'=>$user_id))->getField('id');
                $checker_id='0';
            }
            $order_add = $order->add($order_info);

            //插入pay表
            $pay_info=array(
                "remark"=>$order_sn,
                "mode"=>14,
                "merchant_id" =>$merchant_id,
                "checker_id" =>$checker_id,
                "paystyle_id" => 1,
                "price"=>0,
                "status"=>1,
                "cate_id"=>1,
				"bill_date"=>date('Ymd'),
                "paytime" =>time(),
                "jmt_remark"=>$jmt_remark
            );
            $pay = M(Subtable::getSubTableName('pay'));
            $pay_add = $pay->add($pay_info);

            if($order_add && $pay_add){
                $this->ajaxReturn(array('code' => 'success', 'msg' => '支付成功','data'=>array('order_sn'=>$order_sn,'pay_id'=>$pay_add)));
            }else{

                $this->ajaxReturn(array('code' => 'error', 'msg' => '网络请求失败'));
            }
        }else{
            $this->ajaxReturn(array('code' => 'error', 'msg' => '支付密码错误'));
        }
    }

    /**
     * 获取投放及充值设置
     */
    public function getCardSet()
    {
        if (IS_POST) {
            $this->checkLogin();
            $c_id = I('id');
            if (!$c_id) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => 'No Id'));
            }
            $data = M('screen_cardset')->where(array('c_id' => $c_id))->find();

            if($data['recharge_sen_range']){
                $range = explode(';',rtrim($data['recharge_sen_range'],';'));
                $arr = array();
                foreach($range as &$v){
                    $arr[] = explode(',',$v);
                }
                $data['recharge_sen_range'] = $arr;
            }else{
                $data['recharge_sen_range'] = array();
            }
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK', 'data' => $data));
        }
    }

    /**
     * 充值设置
     */
    public function rechargeSet()
    {
        if (IS_POST) {
            $this->checkLogin();
            $c_id = I('id');
            if (!$c_id) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => 'No Id'));
            }
            $post = I('post.');
            $data['recharge_1'] = $post['recharge_1'];
            $data['recharge_2'] = $post['recharge_2'];
            $data['recharge_3'] = $post['recharge_3'];
            $data['recharge_4'] = $post['recharge_4'];
            $data['recharge_5'] = $post['recharge_5'];
            $data['recharge_send_integral'] = (int)$post['recharge_send_integral'];
            $data['recharge_send_cash'] = (int)$post['recharge_send_cash'];
            $data['recharge_sen_start'] = (int)$post['recharge_sen_start'];
            $data['recharge_sen_end'] = (int)$post['recharge_sen_end'];
            $data['recharge_custom'] = (int)$post['recharge_custom'];
            if($data['recharge_send_cash'] && $data['recharge_send_cash'] == 1 && $data['recharge_sen_end'] <= $data['recharge_sen_start']){
                $this->ajaxReturn(array('code' => 'error', 'msg' => '开始时间大于结束时间'));
                exit;
            }
            if(isset($post['recharge_custom']) && (!isset($post['recharge_1']) || !isset($post['recharge_2']) || !isset($post['recharge_3']))){
                $this->ajaxReturn(array('code' => 'error', 'msg' => '充值推荐1,2,3必填'));
            }
            //$data['recharge_min'] = isset($post['recharge_min']) ? $post['recharge_min'] : 0;
            //$data['recharge_sen_percent'] = isset($post['recharge_sen_percent']) ? $post['recharge_sen_percent'] : 0;
            $data['recharge_min'] = $post['recharge_min'];
            $data['recharge_sen_percent'] = $post['recharge_sen_percent'];
            $data['recharge_sen_range'] = rtrim($post['recharge_sen_range'],';');
            file_put_contents('./data/log/member/' . date("Y_m_") . 'mem_cardset.log', date("Y-m-d H:i:s") . "卡充值设置参数：$c_id" . json_encode($post) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $res = M('screen_cardset')->where(array('c_id' => $c_id))->save($data);
            if ($res != false) {
                $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '保存失败'));
            }
        }
    }
public function tttt()
{
    echo 11;
}
    /**
     * 卡列表
     */
    public function cardList()
    {echo 111;die;
        if (1) {echo 222;
            //$this->checkLogin();
            $this->userId=26;
            $card_info = $this->memcardModel
                ->field('id,is_agent,card_id,cardname,merchant_name,color,cardstatus,show_qrcode_url,logoimg,cardnum,drawnum,credits_set,expense_credits,expense_credits_max,integral_dikou,max_reduce_bonus,credits_discount,discount_set,discount,balance_set,level_set,service_phone,description')
                ->where(array('mid' => $this->userId))
                ->find();
            dump($card_info);die;
            # 判断商户代理商是否有会员卡 代理商会员卡是否对商户开通了
            $agent_card = $this->memcardModel
                ->field('c.id,c.is_agent,card_id,cardname,merchant_name,color,cardstatus,show_qrcode_url,logoimg,cardnum,drawnum,credits_set,expense_credits,expense_credits_max,integral_dikou,max_reduce_bonus,credits_discount,discount_set,discount,balance_set,level_set,service_phone,description')
                ->where(array('u.id' => $this->userId))
                ->join('c left join __MERCHANTS_USERS__ u on u.agent_id=c.mid')
                ->find();
            if (!$card_info && !$agent_card) {
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'No Card', 'data' => (object)null, 'agent_data' => (object)null));
            }
            if(!$card_info && $agent_card){
                $ageng_cardid = $agent_card['id'];
                $use_merchants = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->getField('use_merchants');
                $use_arr = explode(',',$use_merchants);
                if(in_array($this->userId, $use_arr)){
                    if ($agent_card['level_set']) {
                        $level_data = M('screen_memcard_level')->where(array('c_id' => $ageng_cardid))->select();
                        $data['level_data'] = $level_data;
                        $data['discounts'] = array();
                        foreach ($level_data as $k => $v) {
                            $data['discounts'][] = array('level' => $v['level'], 'level_discount' => $v['level_discount'], 'level_name' => $v['level_name']);
                        }
                    } else {
                        $data['level_data'] = array();
                        $data['discounts'] = array();
                    }
                    $active_card = $this->memcard_use_Model->where(array('memcard_id' => $ageng_cardid, 'status' => 1))->count('id');
                    $set = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->find();
                    if (empty($set)) {
                        M('screen_cardset')->add(array('c_id' => $ageng_cardid));
                        $set = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->find();
                    }
                    if (!$set) {
                        $data['delivery_rules'] = 0;
                        $data['recharge_send_cash'] = 0;
                        $data['recharge_send_integral'] = 0;
                        $data['recharge_tuijian'] = array();
                    } else {
                        $data['delivery_rules'] = $set['delivery_rules'];
                        $data['delivery_cash'] = $set['delivery_cash'];
//                $data['delivery_data'][] = array('name' => 'pos机', 'type' => 'delivery_pos', 'delivery_pos' => $set['delivery_pos']);
                        $mch_pay = M('merchants')->field('is_miniapp,agency_business')
                            ->where(array('uid' => $this->userId))
                            ->find();
                        if($mch_pay['is_miniapp'] == 2){
                            $data['delivery_data'][] = array('name' => '小程序', 'type' => 'delivery_xcx', 'data' => $set['delivery_xcx']);
                        }
                        if($mch_pay['agency_business'] == 1){
                            $data['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                        } else if($mch_pay['agency_business'] == 2){
                            $data['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                            $data['delivery_data'][] = array('name' => '双屏支付', 'type' => 'delivery_shuangping', 'data' => $set['delivery_shuangping']);
                        } else {

                        }
                        $data['delivery_data'] = $data['delivery_data'];

                        $data['recharge_send_cash'] = $set['recharge_send_cash'];
                        $data['recharge_data']['recharge_min'] = $set['recharge_min'];
                        $data['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                        $data['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                        $data['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];

                        $data['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'],);
                        $data['recharge_send_integral'] = $set['recharge_send_integral'];

                    }
                    $agent_card = array_merge($agent_card, $data, array('active_card' => $active_card));
                    $this->ajaxReturn(array('code' => 'success', 'msg' => 'agent Card', 'data' => (object)null, 'agent_data' => $agent_card));
                } else {
                    $this->ajaxReturn(array('code' => 'success', 'msg' => 'No Card', 'data' => (object)null, 'agent_data' => (object)null));
                }
            }
            if ($card_info['level_set']) {
                $c_id = $card_info['id'];
                $level_data = M('screen_memcard_level')->where(array('c_id' => $c_id))->select();
                $data['level_data'] = $level_data;
                $data['discounts'] = array();
                foreach ($level_data as $k => $v) {
                    $data['discounts'][] = array('level' => $v['level'], 'level_discount' => $v['level_discount'], 'level_name' => $v['level_name']);
                }
            } else {
                $data['level_data'] = array();
                $data['discounts'] = array();
            }
            $c_id = $card_info['id'];
            $active_card = $this->memcard_use_Model->where(array('memcard_id' => $c_id, 'status' => 1))->count('id');
            $set = M('screen_cardset')->where(array('c_id' => $c_id))->find();
            if (empty($set)) {
                M('screen_cardset')->add(array('c_id' => $c_id));
                $set = M('screen_cardset')->where(array('c_id' => $c_id))->find();
            }
            if (!$set) {
                $data['delivery_rules'] = 0;
                $data['recharge_send_cash'] = 0;
                $data['recharge_send_integral'] = 0;
                $data['recharge_tuijian'] = array();
            } else {
                $data['delivery_rules'] = $set['delivery_rules'];
                $data['delivery_cash'] = $set['delivery_cash'];
//                $data['delivery_data'][] = array('name' => 'pos机', 'type' => 'delivery_pos', 'delivery_pos' => $set['delivery_pos']);
                $mch_pay = M('merchants')->field('is_miniapp,agency_business')
                    ->where(array('uid' => $this->userId))
                    ->find();
                if($mch_pay['is_miniapp'] == 2){
                    $data['delivery_data'][] = array('name' => '小程序', 'type' => 'delivery_xcx', 'data' => $set['delivery_xcx']);
                }
                if($mch_pay['agency_business'] == 1){
                    $data['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                } else if($mch_pay['agency_business'] == 2){
                    $data['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                    $data['delivery_data'][] = array('name' => '双屏支付', 'type' => 'delivery_shuangping', 'data' => $set['delivery_shuangping']);
                } else {

                }
                $data['delivery_data'] = $data['delivery_data'];

                $data['recharge_send_cash'] = $set['recharge_send_cash'];
                $data['recharge_data']['recharge_min'] = $set['recharge_min'];
                $data['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                $data['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                $data['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];

                $data['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'],);
                $data['recharge_send_integral'] = $set['recharge_send_integral'];

            }
            $card_info['description'] = $card_info['description'];
            $datas = array_merge($card_info, $data, array('active_card' => $active_card));
            $datas['brand_name'] = $datas['merchant_name'];
            header('Content-Type:application/json; charset=utf-8');
            if($agent_card){
                $ageng_cardid = $agent_card['id'];
                $use_merchants = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->getField('use_merchants');
                $use_arr = explode(',',$use_merchants);
                if(in_array($this->userId, $use_arr)){
                    if ($agent_card['level_set']) {
                        $level_data_agent = M('screen_memcard_level')->where(array('c_id' => $ageng_cardid))->select();
                        $data_agent['level_data'] = $level_data_agent;
                        $data_agent['discounts'] = array();
                        foreach ($level_data_agent as $k => $v) {
                            $data_agent['discounts'][] = array('level' => $v['level'], 'level_discount' => $v['level_discount'], 'level_name' => $v['level_name']);
                        }
                    } else {
                        $data_agent['level_data'] = array();
                        $data_agent['discounts'] = array();
                    }
                    $active_card = $this->memcard_use_Model->where(array('memcard_id' => $ageng_cardid, 'status' => 1))->count('id');
                    $set = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->find();
                    if (empty($set)) {
                        M('screen_cardset')->add(array('c_id' => $ageng_cardid));
                        $set = M('screen_cardset')->where(array('c_id' => $ageng_cardid))->find();
                    }
                    if (!$set) {
                        $data_agent['delivery_rules'] = 0;
                        $data_agent['recharge_send_cash'] = 0;
                        $data_agent['recharge_send_integral'] = 0;
                        $data_agent['recharge_tuijian'] = array();
                    } else {
                        $data_agent['delivery_rules'] = $set['delivery_rules'];
                        $data_agent['delivery_cash'] = $set['delivery_cash'];
//                $data['delivery_data'][] = array('name' => 'pos机', 'type' => 'delivery_pos', 'delivery_pos' => $set['delivery_pos']);
                        $mch_pay = M('merchants')->field('is_miniapp,agency_business')
                            ->where(array('uid' => $this->userId))
                            ->find();
                        if($mch_pay['is_miniapp'] == 2){
                            $data_agent['delivery_data'][] = array('name' => '小程序', 'type' => 'delivery_xcx', 'data' => $set['delivery_xcx']);
                        }
                        if($mch_pay['agency_business'] == 1){
                            $data_agent['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                        } else if($mch_pay['agency_business'] == 2){
                            $data_agent['delivery_data'][] = array('name' => '台签支付', 'type' => 'delivery_taiqian', 'data' => $set['delivery_taiqian']);
                            $data_agent['delivery_data'][] = array('name' => '双屏支付', 'type' => 'delivery_shuangping', 'data' => $set['delivery_shuangping']);
                        } else {

                        }
                        $data_agent['delivery_data'] = $data_agent['delivery_data'];

                        $data_agent['recharge_send_cash'] = $set['recharge_send_cash'];
                        $data_agent['recharge_data']['recharge_min'] = $set['recharge_min'];
                        $data_agent['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                        $data_agent['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                        $data_agent['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];

                        $data_agent['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'],);
                        $data_agent['recharge_send_integral'] = $set['recharge_send_integral'];

                    }
                    $agent_card = array_merge($agent_card, $data_agent, array('active_card' => $active_card));
                    echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => $agent_card)));
                } else {
                    echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => (object)null)));
                }
            } else {
                echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => (object)null)));
            }
        }
    }


    /**
     * 获取会员等级
     */
    public function getMemberLevel()
    {
        $card_id = I("card_id");
        $openid = I("openid");
        $where = array(
            "smu.card_id" => $card_id,
            "smu.fromname" => $openid,
            "smu.status" => "1",
        );

        $this->memcard_use_Model
            ->alias("smu")
            ->where($where);
        $field = 'smu.card_amount,smu.card_balance,sm.level1,sm.level2,sm.level3,sm.level4,sm.level5,sm.level6';
        $this->memcard_use_Model->field($field);
        $this->memcard_use_Model->join(" JOIN __SCREEN_MEMCARD__ sm ON smu.card_id = sm.card_id");
        $info = $this->memcard_use_Model->find();
        if ($info['card_amount'] >= $info['level1'] && $info['card_amount'] <= $info['level3']) {
            $info['level'] = 1;
        } else if ($info['card_amount'] > $info['level2'] && $info['card_amount'] <= $info['level5']) {
            $info['level'] = 2;
        } else if ($info['card_amount'] > $info['level4']) {
            $info['level'] = 3;
        }

        $this->assign("info", $info);
        $this->display();
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

    /**
     * 删除会员卡
     */
    public function deleteMemcard()
    {
        if (IS_POST) {
            $c_id = I('id');

            // 查询该卡是否已经有用户激活使用
            $actives = $this->memcard_use_Model->where(array('memcard_id' => $c_id, 'status' => 1))->find();
            if ($actives) {
                //$this->ajaxReturn(array('code' => 'error', 'msg' => '该卡已有用户，无法删除'));
            }
            $this->memcardModel->where(array('id' => $c_id))->delete();
            M('screen_memcard_level')->where(array('c_id' => $c_id))->delete();
            M('screen_cardset')->where(array('c_id' => $c_id))->delete();
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK'));
        }
    }

    /**
     * 查询会员卡
     */
    public function queryCard()
    {
        header('Content-Type:application/json; charset=utf-8');
        $card_id = I('card');
        $curl_datas = json_encode(array('card_id' => $card_id));
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/get?access_token=$token";
        $result = request_post($create_card_url, $curl_datas);

        echo $result;

    }

    public function memupdate()
    {
        header('Content-Type:application/json; charset=utf-8');
        $send_arr = array(
            "code" => I('code'),
            "card_id" => I('card_id'),
			'record_bonus'=>urlencode("积分变更"),
//			'bonus'=>100,
			'add_bonus'=> I('jifen'),
			'custom_field_value1'=>I('yue')
        );
        $curl_datas = urldecode(json_encode($send_arr));
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
        $result = request_post($create_card_url, $curl_datas);

		echo $result;
    }

    // 获取代理商下的商户
    public function get_agent_merchant()
    {
        $agent_id = $this->userId;
        $card_id = I('card_id');
        $data = M('merchants')->field('a.merchant_name,b.id')->where(array('agent_id' => $agent_id))->join('a left join __MERCHANTS_USERS__ b on a.uid=b.id')->select();
        if($data){
            $merchant =  M('screen_cardset')->where(array('c_id' => $card_id))->getField('use_merchants');
            if($merchant == ''){
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK','data' => array('merchants' => $data)));
            } else {
                $merchant = explode(',', $merchant);
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK','data' => array('merchants' => $data,'use_merchants' => $merchant)));
            }
        } else {
            $this->ajaxReturn(array('code' => 'success', 'msg' => '没有商户','data' => (object)null));
        }
    }

    public function set_merchant()
    {
        if (IS_POST) {
            $this->checkLogin();
            $merchants = I('use_merchants');
            $c_id = I('card_id');
            // 修改设置
            if (!$c_id) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => 'No CardId'));
            }
            $arr = array('use_merchants' => $merchants);
            M('screen_cardset')->where(array('c_id' => $c_id))->save($arr);
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK'));
        }
    }

    public function updatecard()
    {
        $post = $_REQUEST;
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
        $curl_datas = $this->privcreateEditJson($post);
        $result = request_post($create_card_url, $curl_datas);
        // 将返回数据转化为数组
        $result = object2array(json_decode($result));
        $this->ajaxReturn($result);
    }

    public function privcreateEditJson($post)
    {
        $curl_datas = array(
            "card_id" => urlencode($post['card_id']),
            "member_card" => array(
                "base_info" => array(
                    "code_type" => "CODE_TYPE_QRCODE", //CODE_TYPE_TEXT,CODE_TYPE_BARCODE,CODE_TYPE_QRCODE
                ),
            )
        );
        if(!empty($post['bg'])){
            $curl_datas['member_card']['background_pic_url'] = urlencode($post['bg']);
        }
        if(!empty($post['logoimg'])){
            $curl_datas['member_card']['base_info']['logo_url'] = urlencode($post['logoimg']);
        }

        return urldecode(json_encode($curl_datas));
    }


    /**
     * 图片上传
     */
    public function upload_logoimg()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'memcard/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
        }
        $img = $info['logoimg'] ? $pic_root_path . $info['logoimg']['savepath'] . $info['logoimg']['savename'] : '';
        if($img){
            $return = array(
                'code' => 'success',
                'msg' => 'success',
                'data' => 'https://sy.youngport.com.cn'.substr($img,1),
            );
        } else {
            $return = array(
                'code' => 'error',
                'msg' => '上传失败',
            );
        }
        $this->ajaxReturn($return);
    }

    public function errorwr()
    {
        //error_log('test'.PHP_EOL,3,'./data/tt/tset.log');
    }

    public function uploadImg()
    {
        $arr=array();
//        $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$data['base_url'];
        $arr['buffer']='@'.I('url');
        $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
        $result = request_post($url_getlog, $arr);
        file_put_contents('./data/log/member.log', date("Y-m-d H:i:s") .  '上传图片' . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        $result = json_decode($result, true);
        dump($result);
    }
}