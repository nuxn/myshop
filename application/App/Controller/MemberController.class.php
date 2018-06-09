<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/27
 * Time: 14:10
 */

namespace App\Controller;

use Think\Controller;

/**会员、会员卡控制器
 * 接收微信事件推送后处理
 * Class MemberController
 * @package Api\Controller
 */
class MemberController extends Controller
{
    public $memcardModel;
    public $memberModel;
    public $memcard_use_Model;
    public $host;
    public $userId;
    public $path;

    public function __construct()
    {
        parent::__construct();
        $this->memcardModel = M("screen_memcard");//会员卡表
        $this->memberModel = M("screen_mem");//会员表
        $this->memcard_use_Model = M("screen_memcard_use");//会员卡领取表
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];//域名
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
        $this->userId = 0;
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
        if ($info['card_amount'] > $info['level1'] && $info['card_amount'] <= $info['level3']) {
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
     * 购物奖励积分
     *
     * @param string $order_sn
     * @return string
     */
    public function add_membercard_integral($order_sn = '20170516201314240622')
    {
        if (!$order_sn) return '';

        //获取订单信息、会员卡信息,判断该商家是否发卡，是否投放
        $payModel = M("pay p");
        $payModel->where(array("remark" => $order_sn, "cardstatus" => "4"));
        $payModel->field("sm.card_id,sm.expense_credits,sm.expense,p.price,p.customer_id,sm.card_id,p.id");
        $payModel->join("LEFT JOIN __MERCHANTS__ m ON p.merchant_id = m.id");
        $payModel->join("LEFT JOIN __SCREEN_MEMCARD__ sm ON m.uid = sm.mid");
        $info = $payModel->find();
        if (!$info) return '';
        if (M("memcard_user")->where(array("pay_id" => $info['id']))->getField("id")) return '';
        //获取会员领卡信息，判断是否领取是否激活
        $memcard_use_info = $this->memcard_use_Model->where(array("card_id" => $info['card_id'], "fromname" => $info['customer_id'], "status" => "1"))->field("memid,card_code")->find();
        if (!$memcard_use_info || $info['price'] < $info['expense'] || !$info['card_id'] || !$memcard_use_info['memid']) return '';

        //可奖励积分
        $integral = ceil($info['price'] / $info['expense']);
        $card_id = $info['card_id'];
        $memid = $memcard_use_info['memid'];

        //更新微信第三方
        $this->update_membercard($card_id, $memcard_use_info['card_code'], $integral, "支付后积分奖励");
        //更新领取表
        $this->memcard_use_Model->where(array("card_code" => $memcard_use_info['card_code']))->setInc('card_amount', $integral);
        $this->memcard_use_Model->where(array("card_code" => $memcard_use_info['card_code']))->setInc('card_balance', $integral);
        //插入积分记录
        $credits_arr = array(
            "pay_id" => $info['id'],
            "memid" => $memid,
            "point" => $integral,
            "cardid" => $card_id,
            "add_time" => time(),
            "status" => "1",
        );

        //插入积分记录
        $this->writeLog("jifen.log","【购物奖励】插入积分记录".json_encode($credits_arr));
        M("memcard_user")->add($credits_arr);
        return $integral;
    }


    /**支付抵扣积分
     * @param $order_sn
     */
    public function reduce_membercard_integral($order_sn)
    {
        if (!$order_sn) return;
        $info = M("order")->where(array("order_sn" => $order_sn))->field("integral,card_code")->find();
        if (!$info) return;
        $memcard_use_info = $this->memcard_use_Model->where(array("card_code" => $info['card_code']))->field("card_id,memid")->find();
        $card_id = $memcard_use_info['card_id'];
        $memid = $memcard_use_info['memid'];
        if (!$card_id) return;
        //更新微信第三方
        $this->update_membercard($card_id, $info['card_code'], -$info['integral'], "支付时积分抵扣");
        //更新领取表
        $this->memcard_use_Model->where(array("card_code" => $info['card_code']))->setDec('card_balance', $info['integral']);
        //插入积分记录
        $credits_arr = array(
            "memid" => $memid,
            "point" => $info['integral'],
            "cardid" => $card_id,
            "add_time" => time(),
            "status" => "2",
        );

        //插入积分记录
        $this->writeLog("jifen.log","【购物奖励】插入积分记录".json_encode($credits_arr));
        M("memcard_user")->add($credits_arr);

    }

    /**
     * 更新会员信息
     * 用于支付后回调页面根据消费金额奖励用户积分+
     * 用于双屏收银支付使用会员卡积分后的积分抵扣-
     *
     */
    public function update_membercard($card_id, $code, $add_bonus, $tag)
    {
        if ($_REQUEST) {
            $token = get_weixin_token();
            $arr = array(
                "code" => "$code",
                "card_id" => "$card_id",
                "record_bonus" => urlencode($tag),
                "add_bonus" => "$add_bonus",
                "add_balance" => "0",
                "record_balance" => urlencode($tag),
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
            $result = json_decode($result, true);
            get_date_dir($this->path,'member','更新会员卡信息',__FILE__.json_encode($result));
            if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
                //$this->ajaxReturn(array("code" => "success", "msg" => "更新会员卡信息成功", "data" => $result));
            } else {
                //$this->ajaxReturn(array("code" => "error", "msg" => "更新会员卡信息失败", "data" => $result));
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
            $openid = I("openid");
            $mid = I("mid");
            $price = (int)I('price') / 100;
            if (!$mid) exit("编号不能为空");
            $now = time();
            $list = array();
            $card = $this->memcardModel->alias('sm')
                ->field('card_id,logoimg as thumb_url')
                ->join('join ypt_merchants m on sm.mid=m.uid')
                ->join('join ypt_screen_cardset sc on sm.id=sc.c_id')
                ->where("m.id=$mid and (cardnum-drawnum)>0 and cardstatus=4 and delivery_rules=1 and delivery_cash<=$price")
                ->find();
            if($card){
                if($this->_has_memcard($openid,$card['card_id'])){
                    $card=array();
                    $count=5;
                }else{
                    $count=4;
                }
            }else{
                $count=5;
            }
            $agent_card = $this->_get_agent_card($mid,$price);
            if($agent_card){
                if($this->_has_memcard($openid,$agent_card['card_id'])){
                    $agent_card=array();
                    $count -= 0;
                }else{
                    $count -= 1;
                }
            }else{
                $count -= 0;
            }
            $coupon = M('screen_coupons')
                ->where("card_type='GENERAL_COUPON' and mid=$mid and auto_price<=$price and quantity>0 and status=3 and is_auto=2 and begin_timestamp<=$now and end_timestamp>=$now")
                ->field('card_id,base_url as thumb_url')
                ->order("end_timestamp ASC")
                ->limit("$count")
                ->select();
            if($coupon){
                //判断是否已经领取过
                foreach($coupon as $k => $v){
                    if($v['thumb_url'])$coupon[$k]['thumb_url'] = $this->host . $v['thumb_url'];
                    $map['card_id'] = $v['card_id'];
                    $map['fromname'] = $openid;
                    $is_use = M('screen_user_coupons')->where($map)->count();
                    if($is_use>0){
                        unset($coupon[$k]);
                    }else{
                        array_push($list, $coupon[$k]);
                    }
                }
            }
            if ($card) {
                array_push($list, $card);
            }
            if ($agent_card) {
                array_push($list, $agent_card);
            }
            $token = get_weixin_token();
            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("洋仆淘"),//地址栏标题
                "can_share" => false,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" =>$list
            );
            $this->writeLog("landingpage.log","领取卡券货架-Param：".json_encode($arr));
            $mem_card_query_url = "https://api.weixin.qq.com/card/landingpage/create?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
            $this->writeLog('landingpage.log',"领取卡券货架-结果：".$result);
            $result = json_decode($result, true);
            redirect($result['url']);
        }
    }
    private function writeLog($fileName,$data)
    {
        $path = $this->get_date_dir();
        file_put_contents($path . $fileName, date("H:i:s") . $data . PHP_EOL. PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/member/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        $d = $Y . '/' . date("Y-m-d");
        if (!file_exists($Y)) mkdir($Y, 0777, true);
        if (!file_exists($d)) mkdir($d, 0777);

        return $d . '/';
    }

    //判断该用户是否领过该商户的会员卡
    private function _has_memcard($openid,$card_id)
    {
        $data = M('screen_memcard_use')
            ->where("fromname='$openid' and card_id='$card_id'")
            ->find();
        return $data;
    }

    //获取代理商联名卡
    public function _get_agent_card($mid, $price)
    {
        $uid = M('merchants')->where(array('id'=>$mid))->getField('uid');
        $agent_id = M('merchants')->alias('m')
            ->join('join __MERCHANTS_USERS__ u on m.uid=u.id')
            ->where(array('m.id'=>$mid))
            ->getField('agent_id');
        if($agent_id==0){
            return false;
        }else{
            $card = M('screen_memcard')->alias('sm')
                ->field('card_id,logoimg as thumb_url,use_merchants')
                ->join('join ypt_screen_cardset sc on sm.id=sc.c_id')
                ->where("sm.mid=$agent_id and (cardnum-drawnum)>0 and cardstatus=4 and delivery_rules=1 and delivery_cash<=$price")
                ->find();
            if($card){
                $use_merchants = explode(',',$card['use_merchants']);
                if(in_array($uid,$use_merchants)){
                    unset($card['use_merchants']);
                    return $card;
                }else{
                    return false;
                }
            }else{
                return false;
            }

        }
    }

    /**
     * 创建货架
     * 跳转会员卡领取的第三方页面
     * 用于支付后回调页面领取会员卡链接
     */
    public function create_shelves2()
    {
        if ($_REQUEST) {
            $mid = I("mid");
            $price = (int)I('price') / 100;
            if (!$mid) exit("编号不能为空");
            $coupon = M('screen_coupons')->where("mid=$mid and up_price<=$price and quantity>0 and status=3")->field('card_id,base_url as thumb_url')->limit(1)->select();
            //echo  M('screen_coupons')->_sql();

//            $coupon=array(
//                array(
//                    'card_id'=>'pyaFdwDrxh6KZtSLlcyAxr2-HJo4',
//                    'thumb_url'=>'/data/upload/coupons/2017-05-12/5915cbdbb956e.png',
//                )
//            );
            foreach ($coupon as $k => &$v) {
                $v['thumb_url'] = $this->host . $v['thumb_url'];
            }
            $card = $this->memcardModel->alias('sm')->field('card_id,logoimg as thumb_url')->join('join ypt_merchants m on sm.mid=m.uid')->where("m.id=$mid and (cardnum-drawnum)>0")->find();

            if ($card) {
                array_push($coupon, $card);
            }
            $token = get_weixin_token();
            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("洋仆淘"),//地址栏标题
                "can_share" => true,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" =>
                    $coupon
            );

            $mem_card_query_url = "https://api.weixin.qq.com/card/landingpage/create?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
            $result = json_decode($result, true);
            echo '<pre/>';
            print_r($result);
            exit;
            redirect($result['url']);
        }
    }

    /**
     * 创建货架
     * 跳转会员卡领取的第三方页面
     * 用于支付后回调页面领取会员卡链接
     */
    public function create_shelves3()
    {
        if ($_REQUEST) {
            $mid = I("mid");
            $price = (int)I('price') / 100;
            if (!$mid) exit("编号不能为空");
            $coupon = M('screen_coupons')->where("mid=$mid and up_price<=$price and quantity>0 and status=3")->field('card_id,base_url as thumb_url')->limit(1)->select();
            //echo  M('screen_coupons')->_sql();
            $card_id = 'pyaFdwPlrMEBoxgRKHkW4CFLVvZk';
            $coupon = array(
                array(
                    'card_id' => "$card_id",
                    'thumb_url' => '/data/upload/coupons/2017-05-12/5915cbdbb956e.png',
                )
            );
            foreach ($coupon as $k => &$v) {
                $v['thumb_url'] = $this->host . $v['thumb_url'];
            }
            $card = $this->memcardModel->alias('sm')->field('card_id,logoimg as thumb_url')->join('join ypt_merchants m on sm.mid=m.uid')->where("m.id=$mid and (cardnum-drawnum)>0")->find();

            if ($card) array_push($coupon, $card);

            //$token = get_weixin_token();
            $token = 'y4B9R-X8urAkFaBIk3yEy19hWsIQK1Z7QPhsx05rNfGaJiHhLTDl83mAMze761amhrOmrivDt2Iq3NicW6rUVR9uTfRZEkKKYpKvnAvY7NYVrUrCVnX_0Uyriha0D27PRERaAIAXKX';
            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("ypt"),//地址栏标题
                "can_share" => true,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" => $coupon
            );

            $arr = array(
                "banner" => "http://sy.youngport.com.cn/themes/simplebootx/Public/pay/img/img1.jpg",//货架背景图
                "page_title" => urlencode("test"),
                "can_share" => true,
                "scene" => 'SCENE_NEAR_BY',
                "card_list" => array(array("card_id"=>$card_id,"thumb_url"=>$arr['card_list'][0]['thumb_url'])),
            );

            $mem_card_query_url = "http://api.weixin.qq.com/card/landingpage/create?access_token=$token";
            $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));

            $mem_card_query_url1 = "http://api.weixin.qq.com/card/get?access_token=$token";
            $result1 = request_post($mem_card_query_url1, '{"card_id":"' . $card_id . '"}');

            echo '<pre/>';
            print_r(json_decode($result, true));
            print_r(json_decode($result1, true));
            //redirect($result['url']);
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
        $this->writeLog("activate_member.log","-获取会员信息参数".json_encode($arr));
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
        $this->writeLog("activate_member.log","-获取会员信息结果".json_encode($result));
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

        //获取微信用户信息
        $userinfo = $this->get_wx_user_info("$object->FromUserName",1);
        $data = array(
            "card_id" => "$object->CardId",
            "toname" => "$object->ToUserName",
            "fromname" => "$object->FromUserName",
            "create_time" => time(),
            "friendname" => "$object->FriendUserName",
            "card_code" => "$object->UserCardCode",
            "outerid" => "$object->OuterId",
            "status" => "0",
            "memcard_id" => $memcardModel->where(array('card_id' => "$object->CardId"))->getfield('id'),
            "unionid" => $userinfo['unionid']
        );

        //判断是否已领取,防重复
        $receive = $memcard_use_Model->where(array("card_id" => $data['card_id'], "fromname" => $data['fromname']))->field("card_id")->find();

        //发卡信息
        $info = $memcardModel->where(array("card_id" => $data['card_id']))->field("activate_credits,mid")->find();

        if (!$receive) {//判断是否已领取
            $res = $memcard_use_Model->data($data)->add();
            get_date_dir($this->path,'receive_card','添加到领取表-SQL',$memcard_use_Model->_sql());

            if ($res) {
                //更新会员卡表库存
                $memcardModel->where(array("card_id" => $data['card_id']))->setInc('drawnum');

                //插入会员表信息
                $usr_arr = array(
                    "openid" => $data['fromname'],
                    "add_time" => time(),
                    "userid" => $info['mid'],
                    "memimg" => $userinfo['headimgurl'] ? $userinfo['headimgurl'] : '',
                    "nickname" => $userinfo['nickname'] ? $userinfo['nickname'] : '',
                    "unionid" => $userinfo['unionid'],
                    "sex" =>$userinfo['sex'] ? $userinfo['sex'] : 0
                );

                $memid = $this->memberModel->where(array('unionid' => $userinfo['unionid'], 'userid' => $info['mid']))->getField('id');
                if ($memid) {
                    $this->memberModel->where(array('unionid' => $userinfo['unionid'], 'userid' => $info['mid']))->save($usr_arr);
                    $this->writeLog("receive_card.log","-已在小程序消费，更新会员表-sql".$this->memberModel->getLastSql());
                } else {
                    $memid = $this->memberModel->add($usr_arr);
                    $this->writeLog("receive_card.log","-插入会员表-sql".$this->memberModel->getLastSql());
                }

                //更新会员卡领取表
                $this->memcard_use_Model->where(array("card_code" => $object->UserCardCode))->save(array("memid" => $memid));
            }

        }

    }

    /**
     * 根据openid获取微信用户信息
     * @param string $openid
     * @return mixed
     */
    public function get_wx_user_info($openid = 'oyaFdwGG6w5U-RGyeh1yWOMoj5fM', $card=false)
    {
        $token = get_weixin_token();
        $user_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
        $result = request_post($user_url);
        if($card){
            $this->writeLog("receive_card.log","-openid:".$openid);
            $this->writeLog("receive_card.log","-根据openid获取用户微信信息".$result);
        }
        $this->writeLog("get_user_info.log","-openid:".$openid);
        $this->writeLog("get_user_info.log","-根据openid获取用户微信信息:".$result);
        $result = json_decode($result, true);
        return $result;
    }
	
	public function change_mem_sex()
    {
        $data = $this->memberModel->field('id,openid')->order('id ASC')->limit(8001,8875)->select();
        $aa = 0;
        foreach($data as $k => $v) {
            //sleep(5);
            $openid = $v['openid'];
            $token='RyMgEEUojGdu955TdSX0BmodP3Q4ywRp8xRfO5dIyGL8e4t4BIPeI2jzEOFnlUeptOpT7HBZFYIsw1rIpusuSPeuMSH5t8hMphGQ1wTAg5poNLLIwnCC2JLEXM-lEHjlFOXgABATCL';
            $user_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
            $result = request_post($user_url);
            $res = json_decode($result,true);
            if(!is_null($res['sex'])){
                $this->memberModel->where("id=$v[id]")->setField('sex',$res['sex']);
            }
            $aa +=1;
            //ob_flush();
            //flush();
        }
        echo $aa;
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
            //"init_bonus" => $info['activate_credits'],//初始积分
            "init_bonus" => "0",//初始积分
            "init_balance" => "0",//初始余额
            "membership_number" => "$object->UserCardCode",//会员卡编号
            "card_id" => "$object->CardId",
            "code" => "$object->UserCardCode",
            "init_custom_field_value1" => ""
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activate?access_token=$token";
        $this->writeLog("activate_member.log","-激活会参数：".json_encode($arr));
        $result = request_post($mem_card_query_url, json_encode($arr));
        $this->writeLog("activate_member.log","-结果：".$result);
        $result = json_decode($result, true);
        if ($result['errmsg'] == 'ok' && $result['errcode'] == 0) {
            //更新会员卡总积分
            //$memcard_use_Model->where(array("card_code" => $data['card_code']))->setInc('card_amount', $info['activate_credits']);
            //$memcard_use_Model->where(array("card_code" => $data['card_code']))->setInc('card_balance', $info['activate_credits']);
            $memcard_use_Model->where(array("card_code" => $data['card_code']))->save(array("status" => 1));
            //更新会员表信息
            $user_info = $this->get_userinfo($data['card_id'], $data['card_code']);
            $usr_arr = array(
                "realname" => $user_info['realname'],
                "birthday" => $user_info['birthday'],
                "memphone" => $user_info['memphone'],
                "status" => "1",
            );

            $memid = M("screen_mem")->where(array("openid" => $data['fromname'], "userid" => $info['mid']))->save($usr_arr);
            $sql_m = M("screen_mem")->_sql();
            $this->writeLog("activate_member.log","-更新会员表SQl：".$sql_m);
            $credits_arr = array(
                "memid" => $memid,
                "point" => 0,
                "cardid" => $data['card_id'],
                "add_time" => time(),
            );

            //插入积分记录
            M("memcard_user")->add($credits_arr);
            $this->writeLog("activate_member.log","-插入积分记录sql：".M("memcard_user")->_sql());
            // 设置等级初始值
            $this->setLevel($data['card_id'], $data['card_code']);
        }

    }

    /**
     * 设置等级初始值
     * @param $card_id  微信会员卡id
     * @param $code     会员卡编号
     * @return bool
     */
    public function setLevel($card_id, $code)
    {
        $c_id = M('screen_memcard')->where(array('card_id' => $card_id,'level_set'=>'1'))->getField('id');
        if(!$c_id){
            $this->writeLog("activate_member.log","-该会员卡数据不存在或非等级会员卡");
            return false;
        }
        $level_name = M('screen_memcard_level')->where(array('c_id'=>$c_id,'level'=>'1'))->getField('level_name');
        //发卡信息
        $token = get_weixin_token();
        $arr = array(
            "code" => urlencode($code),
            "card_id" => urlencode($card_id),
            "custom_field_value1" => urlencode("0.00"),
            "custom_field_value2" => urlencode($level_name),
        );
        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/updateuser?access_token=$token";
        $this->writeLog("activate_member.log","-初始参数设置".urldecode(json_encode($arr)));
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        $this->writeLog("activate_member.log","-结果".$result);
    }

}