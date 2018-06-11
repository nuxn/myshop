<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/27
 * Time: 14:10
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

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
        $this->pay = M('pay');
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);
    }

    /**
     * 返回消费金额
     * @param $id
     * @return mixed
     */
    private function get_expense_count1($id)
    {
        $mem = M('screen_mem mem')
            ->join('ypt_screen_memcard_use u on u.memid=mem.id','left')
            ->join('ypt_screen_memcard c on u.memcard_id=c.id','left')
            ->where(array('mem.id'=>$id))
            ->field('u.card_code,u.entity_card_code,u.id as memcard_id')
            ->find();
        $order_where['order_status'] = 5;
        if($mem['card_code'] && $mem['entity_card_code']){
            $order_where['card_code'] = array('in',array($mem['card_code'],$mem['entity_card_code']));
        }elseif($mem['card_code']){
            $order_where['card_code'] = $mem['card_code'];
        }else{
            $order_where['card_code'] = $mem['entity_card_code'];
        }
        $order = M('order')->field('ifnull(sum(order_amount+user_money),0) as total_money,ifnull(count(order_id),0) as total_count,ifnull(MAX(pay_time),0) last_expense')->where($order_where)->find();
        $quick = M('quick_pay')->field('ifnull(sum(price+yue_price),0) as total_money,ifnull(count(id),0) as total_count,ifnull(MAX(update_time),0) last_expense')->where(array('memcard_id'=>$mem['memcard_id'],'status'=>1))->find();

        $data['total_money'] = $order['total_money'] + $quick['total_money'];
        $data['total_count'] = $order['total_count'] + $quick['total_count'];
        $return['expense'] = $data['total_money'];   // 累积消费金额
        $return['expense_count'] = $data['total_count']; // 累积消费次数
        $return['last_expense'] = $order['last_expense']>$quick['last_expense'] ? $order['last_expense'] : $quick['last_expense']; // 最近消费
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
            if (!$v['memimg']){
                $data_lists[$k]['memimg'] = 'http://sy.youngport.com.cn/public/images/headerimg.png';
            }/*else{
                $res = A('App/Member')->get_wx_user_info("$data_lists[$k]['openid']");
                //该会员未关注公众号
                if($res['subscribe']!=1){
                    //未关注
                    $header_img='';
                    $data_lists[$k]['memimg'] = 'http://sy.youngport.com.cn/public/images/headerimg.png';
                }else{
                    //已关注
                    $header_img = $data_lists[$k]['memimg'] = $res['headimgurl'];
                }
                M('screen_mem')->where(array('id'=>$data_lists[$k]['id']))->setField('memimg',$header_img);
            }*/
            // 获取累积消费情况
            //$expense_info = $this->get_expense_count($merchants_id, array($v['openid'], $v['id']));
            $expense_info = $this->get_expense_count1($v['id']);
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
        //$total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where(array("m.userid" => $this->userId, "m.status" => "1"))->count();
        $total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where($map)->count();
        //已激活总会员
        //$active_total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where(array("m.userid" => $this->userId, "m.status" => "1"))->count();
        $active_total_member = $this->memberModel->alias('m')->join('ypt_screen_memcard_use u on u.memid=m.id')->where($map)->count();
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
        if($this->userId == '115'){
            //"name": "老会员绑定",
//            "url": "https://www.qq.com"
            $arr['bind_old_card']['name'] = urlencode('有实体卡会员');
            $arr['bind_old_card']['url'] = 'https://sy.youngport.com.cn/index.php?s=api/wechat/have_card';
        }

        $this->writeLog("create_card.log","一键开卡数据",$arr);
        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=$token";
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        $result = json_decode($result, true);
        $this->writeLog("create_card.log","一键开卡SUCC",$result);
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
            $this->writeLog("create_card.log","查询会员卡SUCC",$result);
            $status = $status_arr[$result['card']['member_card']['base_info']['status']];
            if (!$status) {

            }
        } else {
            $this->writeLog("create_card.log","查询会员卡FAIL",$result);
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
                $this->memcardModel->where(array("id" => $id))->save(array("show_qrcode_url" => $result['show_qrcode_url']));
                $this->ajaxReturn(array("code" => "success", "msg" => "获取会员卡二维码成功", "data" => $result['show_qrcode_url']));
            } else {
                $this->writeLog("old.log","获取会员卡二维码失败",$result);
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
            $card_id = 'pyaFdwB-55bkJ1X5iUb1M1sakj6c';
            $code = '682437100276';
            $add_bonus = '100';

            $arr = array(
                "code" => "$code",
                "card_id" => "$card_id",
                "record_bonus" => urlencode("积分变更"),
                "add_bonus" => $add_bonus,
                "add_balance" => "0",
                "custom_field_value1" => "10",
                "record_balance" => urlencode("积分变更"),
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
            $result = request_post($mem_card_query_url, json_encode($arr));
            $result = json_decode($result, true);

            $this->writeLog("old.log","更新会员信息",$result);
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
            ->field('m.memimg,m.sex,m.nickname,m.nickname,m.realname,m.memphone,mu.card_code,mu.memid,mu.card_balance,mu.card_amount,mu.yue,mu.level,sm.level_set,sm.id')
            ->where($map)
            ->find();
        if ($data) {
            if ($data['level_set'] == 0) {
                $data['level_name'] = '无';
            } else {
                $data['level_name'] = M('screen_memcard_level')->where(array('c_id'=>$data['id'],'level'=>$data['level']))->getField('level_name');
            }
			if(!$data['level_name']) $data['level_name']='无';
			if(!$data['memimg']) $data['memimg']='http://sy.youngport.com.cn/public/images/headerimg.png';
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
        $map['c.card_type'] = 'GENERAL_COUPON';
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
        $mem_data = M('screen_mem mem')
            ->join('ypt_screen_memcard_use u on u.memid=mem.id','left')
            ->where(array('mem.id'=>$id))
            ->field('u.card_code,u.entity_card_code,u.id as memcard_id')
            ->find();
        $order_where['order_status'] = 5;
        if($mem_data['card_code'] && $mem_data['entity_card_code']){
            $order_where['card_code'] = array('in',array($mem_data['card_code'],$mem_data['entity_card_code']));
        }elseif($mem_data['card_code']){
            $order_where['card_code'] = $mem_data['card_code'];
        }else{
            $order_where['card_code'] = $mem_data['entity_card_code'];
        }
        $order = M('order o')
            ->join('ypt_pay p on p.remark=o.order_sn','left')
            ->field('(o.order_amount+o.user_money) as price,o.add_time,o.pay_time as paytime,p.paystyle_id,p.status')
            ->where($order_where)
            ->select();
        $quick = M('quick_pay')
            ->field('(price+yue_price) as price,update_time as paytime,add_time,1 as paystyle_id,status')
            ->where(array('memcard_id'=>$mem_data['memcard_id'],'status'=>1))
            ->select();
        $data = array_merge($order,$quick);
        array_multisort(array_column($data,'pay_time'),SORT_DESC,$data);
        if ($data) {
            $total = '0';
            foreach ($data as $k => $v) {
                $total += $v['price'];
                if(!$data[$k]['add_time']) $data[$k]['add_time']=$data[$k]['paytime'];
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "获取会员消费记录成功", "data" => $data, "total_price" => $total));
        } else {
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

        $recharge = M('user_recharge')
            ->field("price,total_price,add_time")
            ->where(array('uid'=>$id,'status'=>1))
            ->select();
        $cdk = M('screen_memcard_cdk_log l')
            ->join('ypt_screen_memcard_cdk c on c.id=l.cdk_id','left')
            ->join('ypt_screen_memcard_use u on u.id=l.memid','left')
            ->join('ypt_screen_mem m on m.id=u.memid','left')
            ->field('c.price,c.price as total_price,l.use_time as add_time')
            ->where(array('m.id'=>$id,'c.is_use'=>2))
            ->select();
        $data = array_merge($recharge,$cdk);
        array_multisort(array_column($data,'add_time'),SORT_DESC,$data);
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
            $paramlog = $post;
            $this->writeLog("create_card.log","接收APP参数",$paramlog);
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
            if (!$post['description']) $this->ajaxReturn(array("code" => "error", "msg" => "请填写使用须知"));
            if (!$post['cardnum']) $post['cardnum'] = 10000;

            // 入库数据
            M()->startTrans();
            $post_data = $this->createPost($post);
            $post = array_merge($post, $post_data);
            // 若有id 表示修改会员卡
            $token = get_weixin_token();
            if (!empty($c_id)) {
                $this->writeLog("create_card.log","接收APP修改参数",$paramlog);
                // 获取会员卡信息
                $card_data = $this->memcardModel->field('id,card_id,balance_set,drawnum')->where(array('id' => $c_id))->find();
                // 储值（功能打开后，若有领取，则无法关闭)
                if ($card_data['balance_set'] == 1 && $post['balance_set'] == 0) {
                    M()->rollback();
                    ($card_data['drawnum'] == 0) ?: $this->ajaxReturn(array('code' => 'error', 'msg' => '已有客户领取，储值无法关闭'));
                }
                $post['card_id'] = $card_data['card_id'];  // 获取会员卡微信编号

                # 设置积分商城开关
                $this->setIntegralMall($card_data['id'],$post['integral_mall']);

                # 发送修改会员卡数据
                $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
                $curl_datas = $this->createEditJson($post);
                $result = request_post($create_card_url, $curl_datas);
                $this->writeLog("create_card.log","修改会员卡请求",$curl_datas,0);
                // 将返回数据转化为数组
                $result = object2array(json_decode($result));
                if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                    $this->writeLog("create_card.log","修改成功",$result);
                    $post['update_time'] = time();
                    $this->memcardModel->where(array("id" => $post['id']))->save($post);
                    # 修改等级设置
                    if ($post['level_set']) {
                        $this->levelSet($post);
                    }
                    M()->commit();
                    $this->ajaxReturn(array("code" => "success", "msg" => "会员卡修改成功"));
                } else {
                    M()->rollback();
                    $this->writeLog("create_card.log","修改失败",$result);
                    $this->ajaxReturn(array("code" => "error", "msg" => "会员卡修改失败", 'data' => json_encode($result)));
                }
            } else {
                $this->writeLog("create_card.log","接收APP创建参数",$paramlog);
                # 判断代理商是否有创建会员卡权限
                if($post['is_agent']==1){
                    $card_auth = M('merchants_agent')->where(array('uid'=>$this->userId))->getField('card_auth');
                    if($card_auth==0){
                        M()->rollback();
                        $this->ajaxReturn(array("code" => "error", "msg" => "您未开通创建会员卡权限，请联系客服开通"));
                    }
                }
                # 发送创建会员卡参数
                $create_card_url = "https://api.weixin.qq.com/card/create?access_token=$token";
                $curl_datas = $this->createAddJson($post);
                $result = request_post($create_card_url, $curl_datas);

                $this->writeLog("create_card.log","创建卡请求参数",$curl_datas,0);
                # 判断返回
                $result = object2array(json_decode($result));
                if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                    $this->writeLog("create_card.log","创建成功",$result);
                    $post['card_id'] = $result['card_id'];
                    $post['add_time'] = time();
                    if (!$this->memcardModel->where(array("card_id" => $post['card_id']))->getField("id")) {
                        // 是否有积分设置
                        if (!$post['credits_set']) {
                            unset($post['expense_credits_max']);
                            unset($post['expense_credits']);
                        }
                        if (!$post['integral_dikou']) {
                            unset($post['max_reduce_bonus']);
                            unset($post['credits_discount']);

                        }
                        if (!$post['recharge_send_integral']) {
                            unset($post['recharge_send']);
                            unset($post['recharge_send_max']);
                        }
                        $post['discount'] = $post['discount']?:10;
                        # 数据存入数据库
                        $res = $this->memcardModel->add($post);
                        // 查询会员卡状态
                        $this->memcard_query($post['card_id']);
                        // 会员卡一键开卡
                        $this->activateuserform($post);
                        // 开通快速买单
//                        $this->quickPay($res);
                        $set_res =  M('screen_cardset')->add(array('c_id' => $res));
                        $this->setIntegralMall($res, $post['integral_mall']);
                        if ($post['level_set']) {
                            $lev_res = $this->levelSet($post);
                            if(!$lev_res){
                                M()->rollback();
                                $this->ajaxReturn(array("code" => "error", "msg" => "等级设置失败"));
                            }
                        }
                        if (!$res || !$set_res){
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败(1001)"));
                        }
                    }
                    // 获取二维码
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
                    $this->writeLog("create_card.log","获取二维码参数",$arr);
                    # 发送获取二维码请求
                    $mem_card_query_url = "https://api.weixin.qq.com/card/qrcode/create?access_token=$token";
                    $ress = request_post($mem_card_query_url, json_encode($arr));
                    $ress = json_decode($ress, true);
                    if ($ress['errmsg'] == 'ok' && $ress['errcode'] == 0) {
                        $this->memcardModel->where(array("id" => $res))->save(array("show_qrcode_url" => $ress['show_qrcode_url'], "cardstatus" => "4"));
                        $this->writeLog("create_card.log","获取二维码成功",$ress);
                        M()->commit();
                        $this->ajaxReturn(array("code" => "success", "msg" => "会员卡创建成功"));
                    } else {
                        M()->rollback();
                        $this->writeLog("create_card.log","获取二维码失败",$ress);
                        $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败(1002)"));
                    }
                } else {
                    M()->rollback();
                    $this->writeLog("create_card.log","创建失败FAIL",$result);
                    $this->ajaxReturn(array("code" => "error", "msg" => "会员卡创建失败(1003)"));
                }
            }
        }
    }

    public function setIntegralMall($c_id,$set)
    {
        M('screen_cardset')->where(array('c_id'=>$c_id))->save(array('integral_mall'=>intval($set)));
    }

    public function createPost($params)
    {
        $default_url = "http://mmbiz.qpic.cn/mmbiz_png/XgCbCud1UyxauxZN5FX44pnSMj3ZEHSV2k7fOPJIj9VA6T61VzCRhHgkGNlicM8RKmnx5du1ibiaV0L8SA5lsRWsQ/0";
        $img_url = $params['logoimg'];
        $mch_info = M('merchants')->where(array('uid' => $this->userId))->find();
        $user_info = M('merchants_users')->where(array('id' => $this->userId))->find();
        if(empty($img_url)){
            $img_url = $mch_info['logo_url'];
            $img_url = $img_url ? : $default_url;
        }
        $brand_name = $params['brand_name'];
        if(empty($brand_name) || strlen($brand_name)>12){
            $brand_name = $mch_info['merchant_jiancheng'];
            if(empty($brand_name)){
                $brand_name = $user_info['user_name'];
                if(empty($brand_name)){
                    $brand_name = '洋仆淘商户';
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
            'credits_use' => $params['cost_bonus_unit'],
            'expense' => $params['expense'],
            'recharge' => $params['recharge'],
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
        if($mch_info['is_miniapp'] == 2){
            if($mch_info['mini_type'] == 1){
                $post['xcx_id'] = "gh_cb3798cd14c7@app";
                $post['xcx_url'] = "/pages/index/index?store_id=".$this->userId;
            }
            if($mch_info['mini_type'] == 2){
                $post['xcx_id'] = "gh_c0960e761971@app";
                $post['xcx_url'] = "/pages/store/index?store_id=".$this->userId;
            }
        } if($params['is_agent']) {
            $post['xcx_id'] = "gh_cb3798cd14c7@app";
            $post['xcx_url'] = "/pages/recommend/recommend";
        }

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

                    ),
                    "supply_bonus" => true,
                    "supply_balance" => false,
                    "wx_activate" => true,
                )
            )
        );
        if($post['integral_mall']){
            $curl_datas['card']['member_card']['base_info']['custom_url'] = 'https://sy.youngport.com.cn/index.php?g=Api&m=Integral&a=index';
            $curl_datas['card']['member_card']['base_info']['custom_url_name'] = urlencode('积分商城');
        }
        switch($post['center_type']){
            case 1: # 展示快速买单按钮
                $curl_datas['card']['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['card']['member_card']['base_info']['center_title']     = urlencode($post['center_title']);
                $curl_datas['card']['member_card']['base_info']['center_sub_title'] = urlencode($post['center_sub_title']);
                break;
            case 2: # 隐藏按钮
                break;
            case 3: # 微信支付按钮
                $curl_datas['card']['member_card']['base_info']['pay_info'] = array('swipe_card' => array('is_swipe_card' => true));
                break;
            case 4: # 小程序按钮
                $curl_datas['card']['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['card']['member_card']['base_info']['center_title']     = urlencode("小程序");
//                $curl_datas['card']['member_card']['base_info']['center_sub_title'] = urlencode($post['center_sub_title']);
                $curl_datas['card']['member_card']['base_info']['center_app_brand_user_name'] = urlencode($post['xcx_id']);
                $curl_datas['card']['member_card']['base_info']['center_app_brand_pass'] = urlencode($post['xcx_url']);
                break;
            default:
                $curl_datas['card']['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['card']['member_card']['base_info']['center_title']     = urlencode($post['center_title']);
                $curl_datas['card']['member_card']['base_info']['center_sub_title'] = urlencode($post['center_sub_title']);
                break;
        }
        $bonus_rule = array();
        $post['prerogative'] = '';
        // 是否有积分设置
        if ($post['credits_set']) {
            $bonus_rule = array_merge($bonus_rule,array(
                'max_increase_bonus' => urlencode($post['expense_credits_max']),
                'cost_money_unit' => urldecode($expense),
                'increase_bonus' => urlencode($post['expense_credits']),
            ));
        }
        // 是否有抵扣设置
        if ($post['integral_dikou']) {
            $bonus_rule = array_merge($bonus_rule,array(
                'max_reduce_bonus' => urlencode($post['max_reduce_bonus']),   //抵扣条件，单笔最多使用xx积分。
                'cost_bonus_unit' => urlencode($post['cost_bonus_unit']), // 每使用xx积分。
                'reduce_money' => urlencode($credits_discount), //抵扣xx元，（这里以分为单位）
//                "least_money_to_use_bonus" => urlencode($post['expense']), // 抵扣条件，满xx元（这里以分为单位）可用
            ));
        }
        if(!empty($bonus_rule)){
            $curl_datas['card']['member_card']['bonus_rule'] = $bonus_rule;
        }
        if ($post['recharge_send_integral']) {
            $post['prerogative'] .= "每充值".$post['recharge']."元，赠送{$post['recharge_send']}积分;\n";
            $post['prerogative'] .= "每次赠送上限{$post['recharge_send_max']}积分;\n";
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
                "url" => urlencode($post['level_url']),
            );
            // 特权说明
            if ($post['discount_set']) {
                $flag = true;
                //$discount_params = json_decode(htmlspecialchars_decode($post['discount_params']), true);
            }
            $level_params = json_decode(htmlspecialchars_decode($post['level_params']), true);
            $post['prerogative'] .= "等级说明：\n";
            foreach($level_params as $k => $v){
                if($v['level']>0){
                    if($v['level_up_type']){
                        $post['prerogative'] .= $k+1 . $this->get_level_prerogative($v);
                    }else{
                        $post['prerogative'] .= $k+1 . "、购物累积满{$v['level_expense']}元或积分达到{$v['level_integral']}分，即可成为本店{$v['level_name']};";
                    }
                    if($flag){
                        $post['prerogative'] .= "享受商品{$level_params[$k]['level_discount']}折优惠;";
                    }
                    $post['prerogative'] .= "\n";
                }
            }
        }
        // 是否有折扣设置
        if ($post['discount_set']) {
            if(!$post['level_set']){
            	$curl_datas['card']['member_card']['discount'] = urlencode((10-$post['discount'])*10);
                $post['prerogative'] .= "享受商品{$post['discount']}折优惠;\n";
            }
        }
        if(empty($post['prerogative'])){
            $post['prerogative'] = '成为会员享受优惠！';
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
//                    "location_id_list" => array(33788392),
//                    "use_all_locations" => true,
//                    "custom_url_name" => urlencode($post['custom_url_name']),
//                    "custom_url" => urlencode($post['custom_url']),
//                    "custom_url_sub_title" => urlencode($post['custom_url_sub_title']),
                ),
//                "prerogative" => urlencode($post['prerogative']),
            )
        );
        if($post['integral_mall']){
            $curl_datas['member_card']['base_info']['custom_url'] = 'https://sy.youngport.com.cn/index.php?g=Api&m=Integral&a=index';
            $curl_datas['member_card']['base_info']['custom_url_name'] = urlencode('积分商城');
        } else {
            $curl_datas['member_card']['base_info']['custom_url'] = '';
            $curl_datas['member_card']['base_info']['custom_url_name'] = '';
        }
        switch($post['center_type']){
            case 1: # 展示快速买单按钮
                $curl_datas['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['member_card']['base_info']['center_title']     = urlencode($post['center_title']);
                $curl_datas['member_card']['base_info']['center_sub_title'] = urlencode($post['center_sub_title']);
                $curl_datas['member_card']['base_info']['center_app_brand_user_name'] = '';
                $curl_datas['member_card']['base_info']['center_app_brand_pass'] = '';
                $curl_datas['member_card']['base_info']['pay_info'] = array('swipe_card' => array('is_swipe_card' => false));
                break;
            case 2: # 隐藏按钮
                $curl_datas['member_card']['base_info']['center_url']       = '';
                $curl_datas['member_card']['base_info']['center_title']     = '';
                $curl_datas['member_card']['base_info']['center_sub_title'] = '';
                $curl_datas['member_card']['base_info']['center_app_brand_user_name'] = '';
                $curl_datas['member_card']['base_info']['center_app_brand_pass'] = '';
                $curl_datas['member_card']['base_info']['pay_info'] = array('swipe_card' => array('is_swipe_card' => false));
                break;
            case 3: # 微信支付按钮
                $curl_datas['member_card']['base_info']['center_url']       = '';
                $curl_datas['member_card']['base_info']['center_title']     = '';
                $curl_datas['member_card']['base_info']['center_sub_title'] = '';
                $curl_datas['member_card']['base_info']['center_app_brand_user_name'] = '';
                $curl_datas['member_card']['base_info']['center_app_brand_pass'] = '';
                $curl_datas['member_card']['base_info']['pay_info'] = array('swipe_card' => array('is_swipe_card' => true));
                break;
            case 4: # 小程序按钮
                $curl_datas['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['member_card']['base_info']['center_title']     = urlencode("小程序");
                $curl_datas['member_card']['base_info']['center_app_brand_user_name'] = urlencode($post['xcx_id']);
                $curl_datas['member_card']['base_info']['center_app_brand_pass'] = urlencode($post['xcx_url']);
                $curl_datas['member_card']['base_info']['pay_info'] = array('swipe_card' => array('is_swipe_card' => false));
                break;
            default:
                $curl_datas['member_card']['base_info']['center_url']       = urlencode($post['center_url']);
                $curl_datas['member_card']['base_info']['center_title']     = urlencode($post['center_title']);
                $curl_datas['member_card']['base_info']['center_sub_title'] = urlencode($post['center_sub_title']);
                break;
        }
        // 是否有积分设置
        $bonus_rule = array();
        if ($post['credits_set']) {
            $bonus_rule = array_merge($bonus_rule,array(
                'max_increase_bonus' => urlencode($post['expense_credits_max']),
                'cost_money_unit' => urldecode($expense),
                'increase_bonus' => urlencode($post['expense_credits']),
//                "init_increase_bonus" => urlencode($post['activate_credits']),//初始设置积分。
            ));
        }
        // 是否有抵扣设置
        if ($post['integral_dikou']) {
            $bonus_rule = array_merge($bonus_rule,array(
                'max_reduce_bonus' => urlencode($post['max_reduce_bonus']),   //抵扣条件，单笔最多使用xx积分。
                'cost_bonus_unit' => urlencode($post['cost_bonus_unit']), // 每使用xx积分。
                'reduce_money' => urlencode($credits_discount), //抵扣xx元，（这里以分为单位）
//                "least_money_to_use_bonus" => urlencode($post['expense']), // 抵扣条件，满xx元（这里以分为单位）可用
            ));
        }else{
            $bonus_rule = array_merge($bonus_rule,array(
                'max_reduce_bonus' => 0,   //抵扣条件，单笔最多使用xx积分。
                'cost_bonus_unit' => 0, // 每使用xx积分。
                'reduce_money' => 0, //抵扣xx元，（这里以分为单位）
//                "least_money_to_use_bonus" => urlencode($post['expense']), // 抵扣条件，满xx元（这里以分为单位）可用
            ));
        }
        $curl_datas['member_card']['bonus_rule'] = $bonus_rule;
        if ($post['recharge_send_integral']) {
            $post['prerogative'] .= "每充值".$post['recharge']."元，赠送{$post['recharge_send']}积分;\n";
            $post['prerogative'] .= "每次赠送上限{$post['recharge_send_max']}积分;\n";
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
                "url" => urlencode($post['level_url']),
            );
            // 特权说明
            if ($post['discount_set']) {
                $flag = true;
                //$discount_params = json_decode(htmlspecialchars_decode($post['discount_params']), true);
            }
            $level_params = json_decode(htmlspecialchars_decode($post['level_params']), true);
            $post['prerogative'] .= "等级说明：\n";
            foreach($level_params as $k => $v){
                if($v['level']>0){
                    if($v['level_up_type']){
                        $post['prerogative'] .= $k+1 . $this->get_level_prerogative($v);
                    }else{
                        $post['prerogative'] .= $k+1 . "、购物累积满{$v['level_expense']}元或积分达到{$v['level_integral']}分，即可成为本店{$v['level_name']};";
                    }
                    if($flag){
                        $post['prerogative'] .= "享受商品{$level_params[$k]['level_discount']}折优惠;";
                    }
                    $post['prerogative'] .= "\n";
                }
            }
        }
        // 是否有折扣设置
        if ($post['discount_set']) {
            if(!$post['level_set']){
                $curl_datas['member_card']['discount'] = urlencode((10-$post['discount'])*10);
                $post['prerogative'] .= "享受商品{$post['discount']}折优惠;\n";
            }
        }
        if(empty($post['prerogative'])){
            $post['prerogative'] = '成为会员享受优惠！';
        }
        $curl_datas['member_card']['prerogative'] = urlencode($post['prerogative']);

        return urldecode(json_encode($curl_datas));
    }
    #等级说明
    private function get_level_prerogative($param)
    {
        $pre = '';
        $type = explode(',',$param['level_up_type']);
        foreach($type as &$v){
            $pre .= $this->get_level_up_type($v,$param);
        }
        if($pre){
            $prerogative = '、'.rtrim($pre,'或')."，即可成为本店{$param['level_name']};";
            return $prerogative;
        }else{
            return '';
        }

    }

    private function get_level_up_type($type,$param)
    {
        switch ($type) {
            case 1:
                return "单次充值满{$param['level_recharge_single']}元或";
            case 2:
                return "累计充值满{$param['level_recharge']}元或";
            case 3:
                return "单次消费满{$param['level_expense_single']}元或";
            case 4:
                return "累计消费满{$param['level_expense']}元或";
            case 5:
                return "累计积分达到{$param['level_integral']}分或";
            default:
                return '';
        }
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
                $post['discount_params']=$post['level_params'];
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
                    'level_up_type' => $level_params[$i - 1]['level_up_type'],
                    'level_recharge_single' => $level_params[$i - 1]['level_recharge_single'],
                    'level_recharge' => $level_params[$i - 1]['level_recharge'],
                    'level_expense_single' => $level_params[$i - 1]['level_expense_single'],
                    'level_expense' => $level_params[$i - 1]['level_expense'],
                    'level_integral' => $level_params[$i - 1]['level_integral'],
                    'level_discount' => $disc,
                );
            } else {
                $data[] = array(
                    'c_id' => $c_id,
                    'level' => $i,
                    'level_name' => $level_params[$i - 1]['level_name'],
                    'level_up_type' => $level_params[$i - 1]['level_up_type'],
                    'level_recharge_single' => $level_params[$i - 1]['level_recharge_single'],
                    'level_recharge' => $level_params[$i - 1]['level_recharge'],
                    'level_expense_single' => $level_params[$i - 1]['level_expense_single'],
                    'level_expense' => $level_params[$i - 1]['level_expense'],
                    'level_integral' => $level_params[$i - 1]['level_integral'],
                );
            }
        }
        $levelModel->where(array('c_id' => $c_id))->delete();
        return $levelModel->addAll($data);
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
        $code = I('card_code','');
        $two_type = I('two_type',1);   //行业类别   1=便利店  2=餐饮

        $jmt_remark= trim(I("jmt_remark"));
        $pwd = $this->memcard_use_Model->where(array("card_code|entity_card_code"=>$code))->getField('pay_pass');
        if($pwd==md5($pass.'tiancaijing') || $pass=='1111111' || $pass=='2222222'){//双屏pass默认传1111111,pos机2222222
            //插入order表
            $order_info = array();
            $this->check_preferential($code,I('yue',0),I('dikoufen',0),I("coupon_code",""));
            $order_sn = date('YmdHis').mt_rand(100000,999999);//流水号
            $order_info["order_sn"] = $order_sn;
            //$order_amount = I("order_amount");
            $order_info["order_amount"]  = 0;//应收金额
            $order_info["pay_status"]  = 1;//支付状态为1
            $order_info["type"]  = 0;//0为收银订单
            $order_info["order_status"]  = 5;//1.待付款，5.交易成功
            $order_info['integral']=I('dikoufen','0');//该订单使用积分
            $order_info['integral_money']=I('dikoujin','0');//该订单使用积分抵扣金额
            $coupon_code  = I("coupon_code","");
            $order_info["coupon_code"]  = $coupon_code;//优惠券ID
            $order_info["coupon_price"]  = I("coupon_price",'0');//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = 0;//商品数量为0
            $order_info["total_amount"]  = I("total_amount",'0');//订单总价
            $order_info["user_money"]  = I("yue");//使用余额
            $user_id  = I('uid',$this->userId);
            $order_info["user_id"]  = $user_id;
            $order_info["add_time"]  = I("timestamp");
            $order_info["discount"]  = I("discount") * 100;//整单折扣
            $order_info["order_benefit"]  = I("order_benefit",'0');//整单优惠金额
            $order_info["card_code"]= $code;//会员卡号
            $order = M('order');
            $order_id = $order->add($order_info);


          if ($order_id){
             $order_goods = array();
                $goods       = M("order_goods");
                $bar_code    = explode(",", I("bar_code"));
                $goods_num   = explode(",", I("goods_num"));
                $goods_name  = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount    = explode(",", I("goods_discount"));
                $group_id    = explode(",", I("group_id"));
                $sku         = explode(",", I("sku"));
                $goods_id    = explode(",", I("goods_id"));
                $goods_weight    = explode(",", I("goods_weight"));
                if ($two_type==2) {
                    foreach ($goods_id as $key => $val) {
                        $order_goods[$key]['order_id']    = $order_id;
                        $order_goods[$key]["goods_id"]    = $val;
                        $order_goods[$key]["goods_name"]  = $goods_name[$key];
                        $order_goods[$key]["goods_num"]   = $goods_num[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"]    = $discount[$key];
                        $order_goods[$key]["group_id"]    = $group_id[$key];
                        $order_goods[$key]["sku"]         = $sku[$key];//规格编号

                    }
                }else{
                    foreach ($bar_code as $key => $val) {
                        $order_goods[$key]['order_id']    = $order_id;
                        $order_goods[$key]["bar_code"]    = $val;
                        $order_goods[$key]["goods_name"]  = $goods_name[$key];
                        $order_goods[$key]["goods_num"]   = $goods_num[$key];
                        $order_goods[$key]["goods_weight"]   = $goods_weight[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"]    = $discount[$key];
                        $order_goods[$key]["group_id"]    = $group_id[$key];
                        $order_goods[$key]["sku"]         = $sku[$key];//规格编号
                        $order_goods[$key]['goods_id']=M('goods')->where(array('mid'=>$user_id,'bar_code'=>$val))->getField('goods_id');


                     /*   //扣减库存
                        if (!$val || !$sku[$key]) {
                            M()->rollback();
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));

                        }
                        $stock_flag = $this->decrease_stock($val, $sku[$key], $goods_num[$key]);
                        if ($stock_flag['code'] == 'error') {
                            M()->rollback();
                            $this->ajaxReturn($stock_flag);

                        }*/

                    }  
                }


                        $result = $goods->addAll($order_goods);

            }




            $role_id  = M('merchants_role_users')->where(array('uid'=>$user_id))->getField('role_id');
            if($role_id=='7'){
                $pid =  M('merchants_users')->where(array('id'=>$user_id))->getField('pid');
                $merchant_id  =  M('merchants')->where(array('uid'=>$pid))->getField('id');
                $checker_id=$this->userId;
            }else{
                $merchant_id = M('merchants')->where(array('uid'=>$user_id))->getField('id');
                $checker_id='0';
            }
            if($pass=='1111111'){
                $mode = 18;
            }elseif($pass=='2222222'){
                $mode = 19;
            }else{
                $mode = 14;
            }
            //插入pay表
            $pay_info=array(
                "remark"=>$order_sn,
                "mode"=>$mode,
                "order_id"=>$order_id,
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
            $pay_add = $this->pay->add($pay_info);

             A("Pay/barcode")->decrease_stock($order_id);


            if($order_id && $pay_add){
                $this->ajaxReturn(array('code' => 'success', 'msg' => '支付成功','data'=>array('order_sn'=>$order_sn,'pay_id'=>$pay_add)));
            }else{
                $this->ajaxReturn(array('code' => 'error', 'msg' => '网络请求失败'));
            }
        }else{
            $this->ajaxReturn(array('code' => 'error', 'msg' => '支付密码错误'));
        }
    }
    #检查该笔订单使用的储值、积分是否充足，是否有优惠券
    public function check_preferential($card_code,$yue,$integral,$coupon_code)
    {
        #会员卡
        if($card_code>0){
            $card_info = M('screen_memcard_use')->where(array("card_code|entity_card_code"=>$card_code))->field('yue,card_balance')->find();
            if($yue>0){
                if($yue>$card_info['yue']){
                    $this->ajaxReturn(array('code'=>'error','msg'=>'储值不足'));
                }
            }
            if($integral>0){
                if($integral>$card_info['card_balance']){
                    $this->ajaxReturn(array('code'=>'error','msg'=>'积分不足'));
                }
            }
        }
        #优惠券
        if($coupon_code>0){
            $coupon_status = M('screen_user_coupons')->where(array('usercard'=>$coupon_code))->getField('status');
            if($coupon_status==0){
                $this->ajaxReturn(array('code'=>'error','msg'=>'优惠券已被使用'));
            }
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
            $data['recharge_4'] = $post['recharge_4']?:0;
            $data['recharge_5'] = $post['recharge_5']?:0;
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
            $data['recharge_min'] = $post['recharge_min']?:0;
            $data['recharge_sen_percent'] = $post['recharge_sen_percent']?:0;
            $data['recharge_sen_range'] = rtrim($post['recharge_sen_range'],';');
            $this->writeLog("cardset.log","卡充值设置参数",$c_id . json_encode($post),0);
            $res = M('screen_cardset')->where(array('c_id' => $c_id))->save($data);
            if ($res !== false) {
                $this->memcardModel->where(array('id'=>$c_id))->setField(array('recharge_send_integral'=>$data['recharge_send_integral']));
                $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '保存失败'));
            }
        }
    }

    /**
     * 卡列表
     */
    public function cardList()
    {
        if (IS_POST) {
            $this->checkLogin();
            $mch_uid = get_mch_uid($this->userId);
            if(M('merchants_wxstore')->where(array('mu_id'=>$mch_uid))->getField('id')){
                $have_wxstore = '1';
            } else {
                $have_wxstore = '0';
            }
            $card_info = $this->memcardModel
                ->field('id,is_agent,card_id,cardname,merchant_name,color,cardstatus,level_up,show_qrcode_url,logoimg,cardnum,drawnum,credits_set,expense,expense_credits,expense_credits_max,integral_dikou,credits_use as cost_bonus_unit,credits_discount,max_reduce_bonus,recharge_send_integral,recharge,recharge_send,recharge_send_max,discount_set,discount,balance_set,level_set,service_phone,description,center_type')
                ->where(array('mid' => $this->userId))
                ->find();
            # 判断商户代理商是否有会员卡 代理商会员卡是否对商户开通了
            $agent_card = $this->memcardModel
                ->field('c.id,c.is_agent,card_id,cardname,merchant_name,color,cardstatus,level_up,show_qrcode_url,logoimg,cardnum,drawnum,credits_set,expense,expense_credits,expense_credits_max,integral_dikou,credits_use as cost_bonus_unit,credits_discount,max_reduce_bonus,recharge_send_integral,recharge,recharge_send,recharge_send_max,discount_set,discount,balance_set,level_set,service_phone,description,center_type')
                ->where(array('u.id' => $this->userId))
                ->join('c left join __MERCHANTS_USERS__ u on u.agent_id=c.mid')
                ->find();
            if (!$card_info && !$agent_card) {
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'No Card', 'data' => (object)null, 'agent_data' => (object)null, 'have_wxstore'=>$have_wxstore));
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
                        //$data['recharge_send_integral'] = 0;
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
                            $data['delivery_data'] = array();
                        }
                        $data['delivery_data'] = $data['delivery_data'];

                        $data['recharge_send_cash'] = $set['recharge_send_cash'];
                        $data['recharge_data']['recharge_min'] = $set['recharge_min'];
                        $data['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                        if(($set['recharge_sen_range'] && $this->version>='1.6') || $_POST['is_pos']){
                            $range = explode(';',rtrim($set['recharge_sen_range'],';'));
                            $arr = array();
                            foreach($range as $k => &$v){
                                $arr[] = explode(',',$v);
                                foreach ($arr as $key => $val){
                                    $array[$key]['start'] = $val[0];
                                    $array[$key]['end'] = $val[1];
                                    $array[$key]['send'] = $val[2];
                                }
                            }
                            $data['recharge_data']['recharge_sen_range'] = $array;
                        }elseif($this->version<'1.6'){
                            $data['recharge_data']['recharge_sen_range'] = '';
                        }else{
                            $data['recharge_data']['recharge_sen_range'] = array();
                        }
                        $data['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                        $data['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];
                        $data['recharge_data']['recharge_custom'] = $set['recharge_custom'];
                        $data['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'],$set['recharge_4']?:0,$set['recharge_5']?:0);
                        //$data['recharge_send_integral'] = $set['recharge_send_integral'];

                    }
                    $agent_card = array_merge($agent_card, $data, array('active_card' => $active_card));
                    $this->ajaxReturn(array('code' => 'success', 'msg' => 'agent Card', 'data' => (object)null, 'agent_data' => $agent_card, 'have_wxstore'=>$have_wxstore));
                } else {
                    $this->ajaxReturn(array('code' => 'success', 'msg' => 'No Card', 'data' => (object)null, 'agent_data' => (object)null, 'have_wxstore'=>$have_wxstore));
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
                //$data['recharge_send_integral'] = 0;
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
                    $data['delivery_data'] = array();
                }
                $data['delivery_data'] = $data['delivery_data'];

                $data['recharge_send_cash'] = $set['recharge_send_cash'];
                $data['recharge_data']['recharge_min'] = $set['recharge_min'];
                $data['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                if(($set['recharge_sen_range'] && $this->version>='1.6') || $_POST['is_pos']){
                    $range = explode(';',rtrim($set['recharge_sen_range'],';'));
                    $arr = array();
                    foreach($range as $k => &$v){
                        $arr[] = explode(',',$v);
                        foreach ($arr as $key => $val){
                            $array[$key]['start'] = $val[0];
                            $array[$key]['end'] = $val[1];
                            $array[$key]['send'] = $val[2];
                        }
                    }
                    $data['recharge_data']['recharge_sen_range'] = $array;
                }elseif($this->version<'1.6'){
                    $data['recharge_data']['recharge_sen_range'] = '';
                }else{
                    $data['recharge_data']['recharge_sen_range'] = array();
                }
                $data['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                $data['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];
                $data['recharge_data']['recharge_custom'] = $set['recharge_custom'];
                $data['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'], $set['recharge_4']?:0, $set['recharge_5']?:0);
                //$data['recharge_send_integral'] = $set['recharge_send_integral'];

            }
            $card_info['description'] = $card_info['description'];
            $datas = array_merge($card_info, $data, array('active_card' => $active_card));
            $datas['brand_name'] = $datas['merchant_name'];
            $datas['integral_mall'] = $set['integral_mall'];
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
                        //$data_agent['recharge_send_integral'] = 0;
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
                            $data['delivery_data'] = array();
                        }
                        $data_agent['delivery_data'] = $data_agent['delivery_data'];

                        $data_agent['recharge_send_cash'] = $set['recharge_send_cash'];
                        $data_agent['recharge_data']['recharge_min'] = $set['recharge_min'];
                        $data_agent['recharge_data']['recharge_sen_percent'] = $set['recharge_sen_percent'];
                        if(($set['recharge_sen_range'] && $this->version>='1.6') || $_POST['is_pos']){
                            $range = explode(';',rtrim($set['recharge_sen_range'],';'));
                            $agent_arr = array();
                            foreach($range as $k => &$v){
                                $agent_arr[] = explode(',',$v);
                                foreach ($agent_arr as $key => $val){
                                    $agent_array[$key]['start'] = $val[0];
                                    $agent_array[$key]['end'] = $val[1];
                                    $agent_array[$key]['send'] = $val[2];
                                }
                            }
                            $data_agent['recharge_data']['recharge_sen_range'] = $agent_array;
                        }elseif($this->version<'1.6'){
                            $data_agent['recharge_data']['recharge_sen_range'] = '';
                        }else{
                            $data_agent['recharge_data']['recharge_sen_range'] = array();
                        }
                        $data_agent['recharge_data']['recharge_sen_start'] = $set['recharge_sen_start'];
                        $data_agent['recharge_data']['recharge_sen_end'] = $set['recharge_sen_end'];
                        $data_agent['recharge_data']['recharge_custom'] = $set['recharge_custom'];
                        $data_agent['recharge_tuijian'] = array($set['recharge_1'], $set['recharge_2'], $set['recharge_3'], $set['recharge_4']?:0, $set['recharge_5']?:0);
                        //$data_agent['recharge_send_integral'] = $set['recharge_send_integral'];

                    }
                    $agent_card = array_merge($agent_card, $data_agent, array('active_card' => $active_card));
                    $agent_card['integral_mall'] = $set['integral_mall'];
                    echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => $agent_card, 'have_wxstore'=>$have_wxstore)));
                    exit;
                } else {
                    echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => (object)null, 'have_wxstore'=>$have_wxstore)));
                    exit;
                }
            } else {
                echo str_replace('\n', 'n', json_encode(array('code' => 'success', 'msg' => 'OK', 'data' => $datas, 'agent_data' => (object)null, 'have_wxstore'=>$have_wxstore)));
                exit;
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
     * 获取会员等级1.6.0
     */
    public function MemberLevel()
    {
        ($card_id = I('card_id')) || $this->alert('card_id is empty');
        ($openid = I('openid')) || $this->alert('openid is empty');
        ($encrypt_code = I('encrypt_code','','trim')) || $this->alert('encrypt_code is empty');
        $encrypt_code = str_replace(' ','+',$encrypt_code);
        #查看会员卡的基本信息
        $memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,mid')->find();
        /**会员信息*start*/
        $card_code = $this->decrypt_code($encrypt_code);
        if($card_code){
            $memcard_use = M('screen_memcard_use')->where(array('card_code' => $card_code))->find();
            $mem = M('screen_mem')->where(array('id' => $memcard_use['memid']))->find();
        }else{
            ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $memcard['mid']))->find()) || $this->alert($openid.' member is not find '.$memcard['mid']);
            #会员的会员卡基本信息
            $memcard_use = M('screen_memcard_use')->where(array('memcard_id' => $memcard['id'], 'memid' => $mem['id']))->find();
        }
        #会员基本信息

        /**会员信息*end*/
        #总等级信息
        $level_info = M('screen_memcard_level')->where(array('c_id'=>$memcard['id']))->select();
        foreach($level_info as &$v){
            if($v['level']<=$memcard_use['level']){
                $v['make_it'] = 'on';
                if($v['level']==$memcard_use['level']){
                    $current_level_name=$v['level_name'];
                    $current_level_discount=$v['level_discount'];
                }
            }else{
                $v['make_it'] = 'off';
            }
        }
        #获取下一等级信息
        $pre = $this->get_next_level_pre($memcard['id'],$memcard_use['level']+1);
        $this->assign('mem',$mem);
        $this->assign('level_info',$level_info);
        $this->assign('current_level',$memcard_use['level']);
        $this->assign('current_level_name',$current_level_name);
        $this->assign('current_level_discount',$current_level_discount);
        $this->assign('best_level',count($level_info));
        $this->assign('pre',$pre);
        $this->display('level1');

    }
    #获取下一等级信息,$c_id会员卡id,$level获取信息的等级
    public function get_next_level_pre($c_id,$level)
    {
        $level_info = M('screen_memcard_level')->where(array('c_id'=>$c_id,'level'=>$level))->find();
        #未查到证明已经是最高等级
        if(!$level_info){
            $pre = '大吉大利，已经是最高会员等级！';
        }else{
            $up_type = explode(',',$level_info['level_up_type']);
            $p = '';
            foreach($up_type as &$val){
                $p .= $this->get_level_up_type($val,$level_info);
            }
            $pre = rtrim($p,'或').",升级后享受买单{$level_info['level_discount']}折。";
        }
        return $pre;
    }

    public function MemberLevel1()
    {
        ($card_id = I('card_id')) || $this->alert('card_id is empty');
        ($openid = I('openid')) || $this->alert('openid is empty');

        //查看会员卡的基本信息
        $screen_memcard = M('screen_memcard')->where(array('card_id' => $card_id))->field('id,mid')->find();

        /**会员信息*start*/
        #会员基本信息
        ($mem = M('screen_mem')->where(array('openid' => $openid, 'userid' => $screen_memcard['mid']))->find()) || $this->alert($openid.' member is not find '.$screen_memcard['mid']);
        #会员的会员卡基本信息
        $info = M('screen_memcard_use')->where(array('memcard_id' => $screen_memcard['id'], 'memid' => $mem['id']))->find();
        #消费和积分信息，expense累计消费，expense_single单次消费最大金额，card_amount累计积分
        $field = 'ifnull(sum(order_amount),0) as expense,ifnull(max(order_amount),0) as expense_single';
        $order_status['order_status'] = 5;
        if($info['card_code'] && $info['entity_card_code']){
            $order_where['card_code'] = array('in',array($info['card_code'],$info['entity_card_code']));
        }elseif($info['card_code']){
            $order_where['card_code'] = $info['card_code'];
        }else{
            $order_where['card_code'] = $info['entity_card_code'];
        }
        $mem['up_info'] = M('order')->where($order_status)->field($field)->find();
        $mem['up_info']['card_amount'] = $info['card_amount'];
        #充值记录信息，recharge累计充值金额，recharge_single单次充值最大金额
        $f = 'ifnull(sum(real_price),0) as recharge,ifnull(max(real_price),0) as recharge_single';
        $recharge_info = M('user_recharge')->where(array('memcard_id'=>$screen_memcard['id'],'uid'=>$mem['id'],'status'=>1))->field($f)->find();
        $mem['up_info'] = array_merge($mem['up_info'],$recharge_info);
        /**会员信息*end*/
        #总等级信息
        $level_info = M('screen_memcard_level')->where(array('c_id'=>$screen_memcard['id']))->select();

        foreach($level_info as &$value){
            $type = explode(',',$value['level_up_type']);
            $value['pre'] = '';
            $value['make_it'] = 'off';
            foreach($type as &$val){
                $value['pre'] .= $this->get_level_up_type($val,$value);
                #会员当前等级信息，
                #current_level当前等级
                #current_level_name当前等级名称
                #current_level_discount当前等级折扣
                #nex_level下一等级
                $level = $this->getlevel($val,$mem['up_info'],$value);
                if($level){
                    $mem['current_level'] = $level['current_level'];
                    $mem['current_level_name'] = $level['current_level_name'];
                    $mem['current_level_discount'] = $level['current_level_discount'];
                    $mem['next_level'] = $level['current_level']+1>count($level_info)?0:$level['current_level']+1;
                }
            }
            if($info['level']>=$value['level']){
                #make_it该会员是否达到该等级
                $value['make_it'] = 'on';
            }
            $value['pre'] = rtrim($value['pre'],'或').",升级后享受买单{$value['level_discount']}折。";
            if($mem['next_level']==0){
                $mem['next_level_pre'] = '大吉大利，已经是最高会员等级！';
            }elseif($mem['next_level']==$value['level']){
                $mem['next_level_pre'] = $value['pre'];
            }
        }
        $this->assign('best_level',count($level_info));
        $this->assign('mem',$mem);
        $this->assign('level_info',$level_info);
        $this->display('level1');

    }

    //获取会员当前等级信息
    private function getlevel($type,$up_info,$level_info)
    {
        switch ($type) {
            case 1:
                if($up_info['recharge_single'] >= $level_info['level_recharge_single']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 2:
                if($up_info['recharge'] >= $level_info['level_recharge']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 3:
                if($up_info['expense_single'] >= $level_info['level_expense_single']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 4:
                if($up_info['expense'] >= $level_info['level_expense']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 5:
                if($up_info['card_amount'] >= $level_info['level_integral']){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            default:
                if($type=='' && $level_info['level']==1){
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                }else{
                    $level = null;
                }
                return $level;
        }
    }

    /**
     * 开通快速买单
     */
    private function quickPay($id)
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
            $this->writeLog("create_card.log","快速买单SUCC",$result);
            return true;
        } else {
            $this->writeLog("create_card.log","快速买单FAIL",$result);
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
                $this->ajaxReturn(array('code' => 'error', 'msg' => '该卡已有用户，无法删除'));
            }
            $card_id = $this->memcardModel->where(array('id' => $c_id))->getField('card_id');
            $this->memcardModel->where(array('id' => $c_id))->delete();
            M('screen_memcard_level')->where(array('c_id' => $c_id))->delete();
            M('screen_cardset')->where(array('c_id' => $c_id))->delete();
            $this->deleteWx($card_id);
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'OK'));
        }
    }

    private function deleteWx($card_id)
    {
        $token = get_weixin_token();
        $url = "https://api.weixin.qq.com/card/delete?access_token=$token";
        $data['card_id'] = $card_id;
        $res = request_post($url, json_encode($data));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/member/','delete','参数', json_encode($data));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/member/','delete','结果', $res);
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
        exit;
    }

    public function memupdate()
    {
        header('Content-Type:application/json; charset=utf-8');
        $send_arr = array(
            "code" => I('code'),
            "card_id" => I('card_id'),
			//'record_bonus'=>urlencode("积分变更"),
//			'bonus'=>100,
			//'add_bonus'=> I('jifen'),
			'custom_field_value1'=>I('yue')
        );
        $curl_datas = urldecode(json_encode($send_arr));
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
        $result = request_post($create_card_url, $curl_datas);
        M('screen_memcard_use')->where(array('card_code'=>I('code')))->setField('yue',I('yue'));
		echo $result;
		exit;
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

    }

    public function uploadImg()
    {
        $arr=array();
//        $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$data['base_url'];
        $arr['buffer']='@'.I('url');
        $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
        $result = request_post($url_getlog, $arr);
        $this->writeLog("uploadImg.log","上传图片",$result,0);
        $result = json_decode($result, true);
        dump($result);
    }

    public function testlog()
    {
        $this->writeLog("11.log",'111','111');
    }

    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("H:i:s") . ':'.$title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/member/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("Y-m-d");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }

    public function decrypt_code($encrypt_code)
    {
        $token = get_weixin_token();
        $data = json_encode(array('encrypt_code' => $encrypt_code));
        $msg = request_post('https://api.weixin.qq.com/card/code/decrypt?access_token=' . $token, $data);
        $res = json_decode($msg,true);
        if($res['errcode']==0 && $res['errmsg']=='ok'){
            return $res['code'];
        }else{
            return false;
        }
    }

}