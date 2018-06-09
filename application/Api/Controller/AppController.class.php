<?php
/**
 * 我的
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/9
 * Time: 17:26
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**
 * Class AppController
 * @package Api\Controller
 */
class AppController extends ApibaseController

{

    /**
     * 常见问题.
     */
    public function question_a()
    {
        $this->assign('tel', '4008883658');
        $this->display("question2");
    }
    /**
     * 常见问题
     */
    public function question_ayl()
    {
        $this->assign('tel', '13802237314');
        $this->display("question2");
    }

    public function question_m()
    {
        $this->assign('company', '洋仆淘');
        $this->assign('tel', '4008883658');
        $this->display("question");
    }

    public function question_yl()
    {
        $this->assign('company', '云来智付');
        $this->assign('tel', '13802237314');
        $this->display("question");
    }

    public function question_hd()
    {
        $this->display("question3");
    }

    public function level()
    {
        $this->display('level');
    }

    /**
     *app更新
     */
    public function app_update()
    {
        $client = I('post.client');
        $appModel = M('app_version');
        $client = isset($client) && $client == 'ios' ? 2 : 1;
		$map['client'] = $client;
        $map['app_company']  = 1;
        $info = $appModel->where($map)->order('id desc')->find();
        //$info = $appModel->where(array("client" => $client))->order('id desc')->find();
        $info['change_log'] = $client == 1 ? strip_tags(htmlspecialchars_decode($info['change_log'])) : htmlspecialchars_decode($info['change_log']);

        $version = array(
            "versionCode" => $info['version_code'],
            "change_log" => $info['change_log'],
            "versionName" => $info['version_name'],
            "app_name" => $info['app_name'],
            "apk_url" => $info['apk_url']
        );
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array($version)));
    }
	
	//卡券核销
    public function cou_to_men()
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/';
        $code = I("code");
        $price = I("price");
        $mch_uid = $this->get_merchant_uid($this->userId);
		$agent_id = M('merchants_users')->where(array('id'=>$mch_uid))->getField('agent_id');
		if($agent_id=='0') $agent_id = '-1';
        $now = time();
        //优惠券
        get_date_dir($this->path,'App_cou_to_men','获取参数',json_encode($_POST));
        if($data = M("screen_user_coupons")->where(array("usercard"=>$code,"status"=>1))->find()){
			$code_type = '1';//卡类型
            $map = array("c.id" => $data['coupon_id'],'c.card_type'=>'GENERAL_COUPON');
            $res = M('screen_coupons')->alias('c')
                ->join('join ypt_merchants m on m.id=c.mid')
                ->join('join ypt_merchants_users mu on mu.id=m.uid')
                ->where($map)
                ->field('c.total_price,c.de_price,c.status,c.begin_timestamp,c.end_timestamp,mu.id')
                ->find();
            if(!$res) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券不可使用"));
            if($res['id']!=$mch_uid) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券不是本店优惠券"));
            if($res['total_price']>$price) $this->ajaxReturn(array("code" => "error","msg"=>"消费金额未达到优惠券需求金额！"));
            if($res['status']==5) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券已失效"));
            if($now<$res['begin_timestamp']||$now>$res['end_timestamp']) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券不在使用时间范围"));
            $res['memid'] = M('screen_mem')->where(array('unionid'=>$data['unionid'],'userid'=>$mch_uid))->getField('id');
			if($res['memid']){
                $map = array("u.memid" => $res['memid'],"m.mid"=>$mch_uid);
                $card = M('screen_memcard')->alias('m')
                    ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                    ->where($map)
                    ->field('u.yue,u.card_code,u.entity_card_code')
                    ->find();
				if(!$card && $agent_id>0){
                    $map['m.mid'] = $agent_id;
                    $card = M('screen_memcard')->alias('m')
                        ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
						->join('join ypt_screen_cardset s on s.c_id=m.id')
                        ->where($map)
                        ->field('u.yue,u.card_code,u.entity_card_code')
                        ->find();
					if($card){
                        $arr = explode(',',$card['use_merchants']);
                        if(!in_array($mch_uid,$arr)){
                            $card['card_code']='';
                            $card['yue']='';
                        }
                    }
                }
				$memId = $res['memid'];
            }else{
                $card['card_code']='';
                $card['yue']='';
				$memId = '';
            }
            $res['code'] = $code;
            $result = array(
                'coupon_code'=>$code,
                'coupon_price'=>$res['de_price'],
				'card_code'=>strval($card['card_code']),
				'yue'=>strval($card['yue']),
                'memid'=>$memId,
                'total_de_price'=>$res['de_price'],
				'code_type'=>strval($code_type)
            );
            $this->ajaxReturn(array("code" => "success","msg"=>"成功","data"=>$result));
            //会员卡 "status=1 and (card_code='$code' or entity_card_code='$code')"
        }elseif($d = M("screen_memcard_use")->where("status=1 and (card_code='$code' or entity_card_code='$code')")->find()){
			$code_type = '2';//卡类型1
            $map['u.card_code'] = $code;
            $map['u.entity_card_code'] = $code;
            $map['_logic'] = 'OR';
            $res = M('screen_memcard')->alias('m')
                ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                ->where($map)
                ->field('u.card_amount,u.memid,u.yue,u.level,u.card_id,u.card_balance,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.level_set')
                ->find();
            get_date_dir($this->path,'App_cou_to_men','获取会员卡',M()->_sql());
            if($res['mid']!=$mch_uid && $res['mid']!=$agent_id) $this->ajaxReturn(array("code" => "error","msg"=>"该会员卡不能在本店使用"));
			if($res['mid']==$agent_id){
                $use_merchants = M('screen_memcard')->alias('m')
					->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                    ->join('join ypt_screen_cardset s on s.c_id=m.id')
                    ->where($map)
                    ->getField('s.use_merchants');
                $arr = explode(',',$use_merchants);
                if(!in_array($mch_uid,$arr)) $this->ajaxReturn(array("code" => "error","msg"=>"该联名会员卡不能在本店使用"));
            }
            //1算折扣
			if($res['level_set']=='1'){
                $d['discount'] = M('screen_memcard_level')->where(array('c_id'=>$res['id'],'level'=>$res['level']))->getField('level_discount')*0.1;
            }elseif($res['discount_set']==0 || $res['discount']==0 || !$res['discount']){
                $d['discount']='1';
            }else{
                $d['discount']=$res['discount'] * 0.1;
            }
            $new_price = $price * $d['discount'];
			//dump($new_price);
            $discount_price = $price-$new_price;
			//2算优惠券
            $where = array('c.card_type'=>'GENERAL_COUPON','m.uid'=>$mch_uid,'mem.id'=>$res['memid'],'uc.status'=>1,'c.total_price'=>array('ELT',$new_price),'c.begin_timestamp'=>array('ELT',time()),'c.end_timestamp'=>array('EGT',time()));
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->join('join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.de_price,uc.usercard')
                ->where($where)
                ->order('c.de_price DESC')
                ->find();
			if($coupon){
				$new_price2 = $new_price - $coupon['de_price'];
			}else{
				$new_price2 = $new_price;
			}
            //dump($new_price2);
			//3算积分
            if($res['integral_dikou']==0){
				$data=array('card_de_price'=>'0','jifen_use'=>'0');
			}else{
				if($res['card_balance']<$res['max_reduce_bonus']){
					$p = floor($res['card_balance']/$res['credits_use'])*$res['credits_discount'];
				}else{
					$p = floor($res['max_reduce_bonus']/$res['credits_use'])*$res['credits_discount'];
				}
				if($p<$new_price2){
					$data['card_de_price'] = "$p";
					$data['jifen_use'] = $p/$res['credits_discount']*$res['credits_use'];
				}else{
					$data['jifen_use'] = floor($new_price2/$res['credits_discount'])*$res['credits_use'];
					$data['card_de_price'] = ($data['jifen_use']/$res['credits_use'])*$res['credits_discount'];
				}
			}
			//dump($data['card_de_price']);
            if($res && $coupon){
                $result = array(
                    'card_code'=>strval($code),
                    'dikoufen'=>strval($data['jifen_use']),
                    'dikoujin'=>strval($data['card_de_price']),
                    'coupon_code'=>strval($coupon['usercard']),
                    'coupon_price'=>strval($coupon['de_price']),
                    'discount'=>strval($d['discount']),
                    'discount_price'=>strval($discount_price),
                    'total_de_price'=>strval($data['card_de_price']+$coupon['de_price']+$discount_price),
					'yue'=>strval($res['yue']),
					'code_type'=>strval($code_type)
                );
            }else{
                $result = array(
                    'card_code'=>strval($code),
                    'dikoufen'=>strval($data['jifen_use']),
                    'dikoujin'=>strval($data['card_de_price']),
                    'coupon_code'=>'',
                    'coupon_price'=>'',
                    'discount'=>strval($d['discount']),
                    'discount_price'=>strval($discount_price),
                    'total_de_price'=>strval($data['card_de_price']+$discount_price),
					'yue'=>strval($res['yue']),
					'code_type'=>strval($code_type)
                );
            }
            $result['memid'] = $res['memid'];
            $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$result));
        }else{
            get_date_dir($this->path,'App_cou_to_men','获取会员卡',M()->_sql());
            $this->ajaxReturn(array("code" => "error","msg"=>"无效卡号"));
        }
    }

    #1.7版本卡券核销（先输入卡号再输入金额）
    public function cancel_card()
    {
        $code = I('code','','trim');
        $mch_uid = $this->get_merchant_uid($this->userId);
        $m_id = M('merchants')->where(array('uid'=>$mch_uid))->getField('id');
        $agent_id = M('merchants_users')->where(array('id'=>$mch_uid))->getField('agent_id');
        if($agent_id=='0') $agent_id = '-1';
        //券
        if($data = M("screen_user_coupons")->where(array("usercard"=>$code,"status"=>1))->find()){
            $map = array("c.id" => $data['coupon_id']);
            $res = M('screen_coupons')->alias('c')
                ->where($map)
                ->field('c.mid,c.agent_id,c.title,c.brand_name,c.base_url,c.card_type,c.total_price,c.de_price,c.type,c.status,c.begin_timestamp,c.end_timestamp,c.fixed_term,c.use_merchant')
                ->find();
            if(!$res) $this->ajaxReturn(array("code" => "error","msg"=>"该券不可使用"));
            if($res['mid']>0){
                $res['id'] = M('merchants')->where(array('id'=>$res['mid']))->getField('uid');
            }
            // if($res['status']==5) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券已失效"));
            if($res['type'] == 'DATE_TYPE_FIX_TIME_RANGE' && (time()<$res['begin_timestamp'] || time()>$res['end_timestamp'])){
                $this->ajaxReturn(array("code" => "error","msg"=>"该券不在使用时间范围"));
            }elseif($res['type'] == 'DATE_TYPE_FIX_TERM' && $data['create_time']+($res['fixed_term']*86400)<time()){
                $this->ajaxReturn(array("code" => "error","msg"=>"该券已过期"));
            }
            if($res['card_type']=='GENERAL_COUPON'){
                $code_type = '1';//券类型，优惠券
                if($res['id']!=$mch_uid) $this->ajaxReturn(array("code" => "error","msg"=>"该优惠券不是本店优惠券"));
            }elseif($res['card_type']=='GIFT'){
                $code_type = '3';//券类型，积分券
                if($res['mid']>0){
                    //商户创建的积分券
                    if($res['mid']!=$m_id) $this->ajaxReturn(array("code" => "error","msg"=>"该积分券不能在本店使用(01)"));
                }else{
                    //代理商创建的积分券
                    /*if($agent_id != $res['agent_id']){
                        $this->ajaxReturn(array("code" => "error","msg"=>"该积分券不能在本店使用(02)"));
                    }*/
                    if($res['use_merchant'] > 0 && $res['use_merchant']!=$m_id){
                        //代理商下所有商户均可兑换
                        $this->ajaxReturn(array("code" => "error","msg"=>"该积分券不能在本店使用(03)"));
                    } elseif ($res['use_merchant']==0){
                        $agent_uid = M('merchants_agent')->where(array('agent_id'=>$agent_id))->getField('uid');
                        $uids = M('screen_cardset c')->join('left join ypt_screen_memcard m on m.id=c.c_id')->where(array('m.mid'=>$agent_uid))->getField('use_merchants');
                        if($uids){
                            $uids_array = explode(',',$uids);
                            if(!in_array($this->userId,$uids_array)){
                                $this->ajaxReturn(array("code" => "error","msg"=>"该积分券不能在本店使用(04)"));
                            }
                        }else{
                            $this->ajaxReturn(array("code" => "error","msg"=>"该积分券不能在本店使用(05)"));
                        }
                    }
                }
                $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
                $use_coupon = request_post($url, json_encode(array('code'=>$code)));
                $coupon_result = json_decode($use_coupon,true);
                if ($coupon_result['errmsg'] == "ok") {
                    M("screen_user_coupons")->where("usercard=$code")->setField(array('status'=>0,'update_time'=>time(),'use_mid'=>$m_id));
                    $this->ajaxReturn(array("code" => "success","msg"=>"积分券核销成功","data"=>array('code'=>$code,'code_type'=>$code_type,'title'=>$res['title'],'brand_name'=>$res['brand_name'],'logo_url'=>'http://agent.youngport.com.cn'.$res['base_url'])));
                }else{
                    $this->ajaxReturn(array("code" => "error","msg"=>"该积分券核销失败"));
                }
            }
            $result = array(
                'code'=>$code,
                'coupon_de_price'=>$res['de_price'],
                'coupon_total_price'=>$res['total_price'],
                'code_type'=>$code_type
            );
            $this->ajaxReturn(array("code" => "success","msg"=>"成功","data"=>$result));
            //会员卡
        }elseif(M("screen_memcard_use")->where(array("card_code|entity_card_code"=>$code,"status"=>1))->find()){
            $code_type = '2';//卡类型1
            $res = M('screen_memcard')->alias('m')
                ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                ->where(array("u.card_code|u.entity_card_code" => $code))
                ->field('u.card_amount,u.memid,u.yue,u.level,u.card_id,u.card_balance,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.level_set')
                ->find();
            if($res['mid']!=$mch_uid && $res['mid']!=$agent_id) $this->ajaxReturn(array("code" => "error","msg"=>"该会员卡不能在本店使用"));
            if($res['mid']==$agent_id){
                $use_merchants = M('screen_memcard')->alias('m')
                    ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                    ->join('join ypt_screen_cardset s on s.c_id=m.id')
                    ->where(array("u.card_code|u.entity_card_code" => $code))
                    ->getField('s.use_merchants');
                $arr = explode(',',$use_merchants);
                if(!in_array($mch_uid,$arr)) $this->ajaxReturn(array("code" => "error","msg"=>"该联名会员卡不能在本店使用"));
            }
            $result = array(
                'code'=>$code,
                'code_type'=>$code_type
            );
            $this->ajaxReturn(array("code" => "success","msg"=>"成功","data"=>$result));
        }else{
            $this->ajaxReturn(array("code" => "error","msg"=>"无效卡号"));
        }
    }

	//点击查看（折扣详情）
    public function zk_detail()
    {
        $memid = I('memid');
        $price = I('price');
		$coupon_code = I('coupon_code');//优惠券code
		if(!$memid && !$coupon_code) $this->ajaxReturn(array("code"=>"error","msg"=>"无优惠信息"));
        $mch_uid = $this->get_merchant_uid($this->userId);
		$agent_id = M('merchants_users')->where(array('uid'=>$mch_uid))->getField('agent_id');
        if($agent_id=='0'){$agent_id = '-1';}
        $map = array("u.memid" => $memid,"m.mid"=>$mch_uid);
        $res = M('screen_memcard')->alias('m')
            ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
            ->where($map)
            ->field('u.card_amount,u.memid,u.card_id,u.card_balance,u.card_code,u.yue,u.level,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.balance_set,m.level_set')
            ->find();
		$where = array("u.memid" => $memid,"m.mid"=>$agent_id);
        $res_agent = M('screen_memcard')->alias('m')
            ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
            ->where($where)
            ->field('u.card_amount,u.memid,u.card_id,u.card_balance,u.card_code,u.yue,u.level,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.balance_set,m.level_set')
            ->find();
        if(!$res) $res = $res_agent;
        if($res){
			//1算折扣
            if($res['level_set']=='1'){
                $d['discount'] = M('screen_memcard_level')->where(array('c_id'=>$res['id'],'level'=>$res['level']))->getField('level_discount')*0.1;
            }elseif($res['discount_set']==0 || $res['discount']==0 || !$res['discount']){
                $d['discount']='1';
            }else{
                $d['discount']=$res['discount'] * 0.1;
            }
            $new_price = $price * $d['discount'];
            $discount_price = $price-$new_price;
			//2算优惠券
			if($coupon_code){
				$coupon_de_price = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->where(array('usercard'=>$coupon_code))
                ->getField('de_price');
				$new_price2 = $new_price - $coupon_de_price;
			}else{
				$new_price2 = $new_price;
			}
			//3算积分
			//判断积分抵扣开关是否打开
            if($res['integral_dikou']==0){
                $data=array('card_de_price'=>'0','jifen_use'=>'0');
				$jifen_rule=array('credits_use'=>'0','credits_discount'=>'0','max_reduce_bonus'=>'0');
            }else{
				$jifen_rule=array('credits_use'=>$res['credits_use'],'credits_discount'=>$res['credits_discount'],'max_reduce_bonus'=>$res['max_reduce_bonus']);
                if($res['card_balance']<$res['max_reduce_bonus']){
                    $p = floor($res['card_balance']/$res['credits_use'])*$res['credits_discount'];
                }else{
                    $p = floor($res['max_reduce_bonus']/$res['credits_use'])*$res['credits_discount'];
                }
				/*
                if($p<$new_price2){
                    $data['card_de_price'] = "$p";
                    $data['jifen_use'] = $res['card_balance'];
                }else{
                    $data['card_de_price'] = "$new_price2";
                    $data['jifen_use'] = floor("$new_price2"/$res['credits_discount'])*$res['credits_use'];
                }*/
				if($p<$new_price2){
					$data['card_de_price'] = "$p";
					$data['jifen_use'] = $p/$res['credits_discount']*$res['credits_use'];
				}else{
					$data['jifen_use'] = floor($new_price2/$res['credits_discount'])*$res['credits_use'];
					$data['card_de_price'] = ($data['jifen_use']/$res['credits_use'])*$res['credits_discount'];
				}
            }
			//判断储值抵扣开关是否打开
            if($res['balance_set']==0){
                $data['yue'] = '0';
            }else{
                $data['yue'] = strval($res['yue']);
            }
        }else{
            $d['discount']='1';
            $discount_price = '0';
            $data['card_de_price'] = '';
            $data['jifen_use'] = '';
			$jifen_rule=array('credits_use'=>'0','credits_discount'=>'0','max_reduce_bonus'=>'0');
        }
        if($memid){
            $mem = M('screen_mem')->where("id=$memid")->field('memimg,memphone')->find();
            $where = array('m.uid'=>$mch_uid,'mem.id'=>$memid,'uc.status'=>'1','c.begin_timestamp'=>array('ELT',time()),'c.end_timestamp'=>array('EGT',time()));
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->join('join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.total_price,c.de_price as de_price,uc.usercard as coupon_code,c.title')
                ->where($where)
                ->order('c.de_price DESC')
                ->select();
        }else{
            $openid = M('screen_user_coupons')->where(array('usercard'=>$coupon_code))->getField('fromname');
            $where = array('m.uid'=>$mch_uid,'uc.fromname'=>$openid,'uc.status'=>'1','c.begin_timestamp'=>array('ELT',time()),'c.end_timestamp'=>array('EGT',time()));
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                //->join('join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.total_price,c.de_price as de_price,uc.usercard as coupon_code,c.title')
                ->where($where)
                ->order('c.de_price DESC')
                ->select();
			$mem['memimg']='';
			$mem['memphone']='';
        }
        if($res && $coupon){
            $result = array(
                'memimg'=>$mem['memimg'],
                'memphone'=>$mem['memphone'],
                'card_code'=>$res['card_code'],
                'dikoufen'=>strval($data['jifen_use']),
                'dikoujin'=>strval($data['card_de_price']),
                'coupon_list'=>$coupon,
                'discount'=>strval($d['discount']),
                'discount_price'=>strval($discount_price),
				'yue'=>$data['yue'],
				'card_balance'=>$res['card_balance'],
				'credits_use'=>strval($jifen_rule['credits_use']),
				'credits_discount'=>strval($jifen_rule['credits_discount']),
				'max_reduce_bonus'=>strval($jifen_rule['max_reduce_bonus']),
            );
        }elseif($res && !$coupon){
            $result = array(
                'memimg'=>$mem['memimg'],
                'memphone'=>$mem['memphone'],
                'card_code'=>$res['card_code'],
                'dikoufen'=>strval($data['jifen_use']),
                'dikoujin'=>strval($data['card_de_price']),
                'coupon_list'=>array(),
                'discount'=>strval($d['discount']),
                'discount_price'=>strval($discount_price),
				'yue'=>$data['yue'],
				'card_balance'=>$res['card_balance'],
				'credits_use'=>strval($jifen_rule['credits_use']),
				'credits_discount'=>strval($jifen_rule['credits_discount']),
				'max_reduce_bonus'=>strval($jifen_rule['max_reduce_bonus']),
            );
        }elseif (!$res && $coupon){
            $result = array(
                'card_code'=>'',
                'dikoufen'=>strval($data['jifen_use']),
                'dikoujin'=>strval($data['card_de_price']),
                'coupon_list'=>$coupon,
                'discount'=>strval($d['discount']),
                'discount_price'=>strval($discount_price),
				'yue'=>'',
				'card_balance'=>'0',
				'credits_use'=>strval($jifen_rule['credits_use']),
				'credits_discount'=>strval($jifen_rule['credits_discount']),
				'max_reduce_bonus'=>strval($jifen_rule['max_reduce_bonus']),
            );
        }
        $result['memid'] = $memid;
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$result));
    }
	
	//1.3.5折扣详情
	public function zk_detail1()
    {
        $memid = I('memid','');
		$mem_info = M('screen_mem')->where(array('id'=>$memid))->field('openid,unionid')->find();
        $price = I('price');
        $coupon_code = I('coupon_code',I('conpon_code'));//优惠券code
        if(!$memid && !$coupon_code) $this->ajaxReturn(array("code"=>"error","msg"=>"无优惠信息"));
        $mch_uid = $this->get_merchant_uid($this->userId);
        $agent_id = M('merchants_users')->where(array('id'=>$mch_uid))->getField('agent_id');
		if($agent_id=='0') $agent_id = '-1';
        //查找本店会员卡
        $res = M('screen_memcard')->alias('m')
            ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
            ->where(array("u.fromname" => $mem_info['openid'] ,"unionid"=>$mem_info['unionid'] ,"m.mid"=>$mch_uid))
            ->field('u.card_amount,u.memid,u.card_id,u.card_balance,u.card_code,u.entity_card_code,u.yue,u.level,m.merchant_name,m.cardname,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.balance_set,m.level_set')
            ->find();
        //查找联名会员卡
        if($agent_id>0){
            $res_agent = M('screen_memcard')->alias('m')
                ->join('join ypt_screen_memcard_use u on m.id=u.memcard_id')
                ->join('join ypt_screen_cardset s on s.c_id=m.id')
                ->where(array("u.fromname" => $mem_info['openid'] ,"unionid"=>$mem_info['unionid'] ,"m.mid"=>$agent_id))
                ->field('s.use_merchants,u.card_amount,u.memid,u.card_id,u.card_balance,u.card_code,u.entity_card_code,u.yue,u.level,m.merchant_name,m.cardname,m.id,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount,m.discount_set,m.discount,m.mid,m.balance_set,m.level_set')
                ->find();
            $arr = explode(',',$res_agent['use_merchants']);
            if(!in_array($mch_uid,$arr)){
                $res_agent = false;
            }
        }
        //本店卡
        if($res) $card_list[] = $this->card_calc($res,$coupon_code,$price);
        //联名卡
        if($res_agent) $card_list[] = $this->card_calc($res_agent,$coupon_code,$price);
        if(!$res && !$res_agent) $card_list = array();
        if($memid){
            $mem = M('screen_mem')->where("id=$memid")->field('memimg,memphone')->find();
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->join('join ypt_screen_mem mem on mem.unionid=uc.unionid')
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.total_price,c.de_price as de_price,uc.usercard as coupon_code,c.title')
                ->where(array('c.card_type'=>'GENERAL_COUPON','m.uid'=>$mch_uid,'mem.id'=>$memid,'uc.status'=>'1','c.begin_timestamp'=>array('ELT',time()),'c.end_timestamp'=>array('EGT',time())))
                ->order('c.de_price DESC')
                ->select();
        }else{
            $openid = M('screen_user_coupons')->where(array('usercard'=>$coupon_code))->getField('fromname');
            $coupon = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                //->join('join ypt_screen_mem mem on mem.unionid=uc.unionid') 
                ->join('left join ypt_merchants m on m.id=c.mid')
                ->field('c.total_price,c.de_price as de_price,uc.usercard as coupon_code,c.title')
                ->where(array('c.card_type'=>'GENERAL_COUPON','m.uid'=>$mch_uid,'uc.fromname'=>$openid,'uc.status'=>'1','c.begin_timestamp'=>array('ELT',time()),'c.end_timestamp'=>array('EGT',time())))
                ->order('c.de_price DESC')
                ->select();
			$mem['memimg']='';
			$mem['memphone']='';
        }
        if(!$coupon){$coupon = array();}
        $result = array(
			'memid' => $memid,
            'memimg'=>$mem['memimg'],
            'memphone'=>$mem['memphone'],
            'coupon_list'=>$coupon,
            'card_list'=>$card_list
        );
        
        $this->ajaxReturn(array("code"=>"success","msg"=>"成功","data"=>$result));
    }

    //计算会员卡
    public function card_calc($res,$coupon_code,$price){
        //1算折扣
        if($res['level_set']=='1'){
            $card = M('screen_memcard_level')->where(array('c_id'=>$res['id'],'level'=>$res['level']))->field('level_name,level_discount*0.1 as discount')->find();
        }elseif($res['discount_set']==0 || $res['discount']==0 || !$res['discount']){
            $card['discount']='1';
            $card['level_name']='';
        }else{
            $card['discount']=$res['discount'] * 0.1;
            $card['level_name']='';
        }
        $new_price = $price * $card['discount'];
        $card['discount_price'] = $price-$new_price;
        //2算优惠券
        if($coupon_code){
            $coupon_de_price = M('screen_user_coupons')->alias('uc')
                ->join('join ypt_screen_coupons c on uc.coupon_id=c.id')
                ->where(array('usercard'=>$coupon_code))
                ->getField('de_price');
            $new_price2 = $new_price - $coupon_de_price;
        }else{
            $new_price2 = $new_price;
        }
        //3算积分
        //判断积分抵扣开关是否打开
        if($res['integral_dikou']==0){
            $card['integral_dikou']='0';
            $card['dikoujin']='0';
            $card['dikoufen']='0';
            //$jifen_rule=array('credits_use'=>'0','credits_discount'=>'0','max_reduce_bonus'=>'0');
            $card['credits_use'] = '0';
            $card['credits_discount'] = '0';
            $card['max_reduce_bonus'] = '0';
        }else{
            //$jifen_rule=array('credits_use'=>$res['credits_use'],'credits_discount'=>$res['credits_discount'],'max_reduce_bonus'=>$res['max_reduce_bonus']);
            $card['integral_dikou']='1';
            $card['credits_use'] = $res['credits_use'];
            $card['credits_discount'] = $res['credits_discount'];
            $card['max_reduce_bonus'] = $res['max_reduce_bonus'];
            if($res['card_balance']<$res['max_reduce_bonus']){
                $p = floor($res['card_balance']/$res['credits_use'])*$res['credits_discount'];
            }else{
                $p = floor($res['max_reduce_bonus']/$res['credits_use'])*$res['credits_discount'];
            }
            if($p<$new_price2){
                $card['dikoujin'] = $p;
                $card['dikoufen'] = $p/$res['credits_discount']*$res['credits_use'];
            }else{
                $card['dikoufen'] = floor($new_price2/$res['credits_discount'])*$res['credits_use'];
                $card['dikoujin'] = ($card['dikoufen']/$res['credits_use'])*$res['credits_discount'];
            }
        }
        //判断储值抵扣开关是否打开111
        if($res['balance_set']==0){
            $card['yue'] = '0';
        }else{
            $card['yue'] = $res['yue'];
        }

        $card['card_code'] = $res['card_code']?$res['card_code']:$res['entity_card_code'];
        $card['card_balance'] = $res['card_balance'];
        $card['cardname'] = $res['cardname'];
        $card['merchant_name'] = $res['merchant_name'];
		foreach($card as $k => $v){
            $card[$k] = strval($v);
        }
        return $card;
    }
	
	//app启动页
	public function app_start()
    {
        $now = time();
        $map['start_time'] = array('ELT',$now);
        $map['end_time'] = array('EGT',$now);
        $map['status'] = 1;
        $url = M('app_start')->where($map)->order('add_time DESC')->getField('thumb');
        if(!$url){
            $this->ajaxReturn(array("code" => "error", "msg" => "未设置启动页"));
        }else{
            $data = 'https://' . $_SERVER['HTTP_HOST'].$url;
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        }
    }

    /**
     * 语音设置
     */
    public function voice_set()
    {
        $this->checkLogin();
        $voice_open = I('post.voice_open');//0关闭,1开启
        $data = array("voice_open" => $voice_open);

        if (!$voice_open && $voice_open != '0') $this->ajaxReturn(array("code" => "error", "msg" => "缺少参数voice_open"));
        if ($voice_open == '1') {
            M("merchants_users")->where(array("id" => $this->userId, "voice_open" => "0"))->save($data);
        } else if ($voice_open == '0') {
            M("merchants_users")->where(array("id" => $this->userId, "voice_open" => "1"))->save($data);
        }
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['voice_open'] = $voice_open;
        M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }
    /**
     * 云打印设置
     */
    public function cloud_print()
    {
        $this->checkLogin();
        $cloud_print= I('post.cloud_print');//0关闭,1开启
        $data = array("cloud_print" => $cloud_print);

        if (!$cloud_print && $cloud_print != '0') $this->ajaxReturn(array("code" => "error", "msg" => "缺少参数cloud_print"));
        if ($cloud_print == '1') {
            M("merchants_users")->where(array("id" => $this->userId, "cloud_print" => "0"))->save($data);
        } else if ($cloud_print == '0') {
            M("merchants_users")->where(array("id" => $this->userId, "cloud_print" => "1"))->save($data);
        }
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['cloud_print'] = $cloud_print;
        M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }

    /**
     * 云语音
     */
    public function cloud_voice()
    {
        $this->checkLogin();
        $cloud_voice= I('post.cloud_voice');//0关闭,1开启
        $data = array("cloud_voice" => $cloud_voice);

        if (!$cloud_voice && $cloud_voice != '0') $this->ajaxReturn(array("code" => "error", "msg" => "缺少参数cloud_voice"));
        if ($cloud_voice == '1') {
            M("merchants_users")->where(array("id" => $this->userId, "cloud_voice" => "0"))->save($data);
        } else if ($cloud_voice == '0') {
            M("merchants_users")->where(array("id" => $this->userId, "cloud_voice" => "1"))->save($data);
        }
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['cloud_voice'] = $cloud_voice;
        M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }

    /**
     * 现金收款
     */
    public function cash_pay()
    {
        $this->checkLogin();
        $cash_pay= I('post.cash_pay');//0关闭,1开启
        $data = array("cash_pay" => $cash_pay);

        if (!$cash_pay && $cash_pay != '0') $this->ajaxReturn(array("code" => "error", "msg" => "缺少参数cash_pay"));
        if ($cash_pay == '1') {
            M("merchants_users")->where(array("id" => $this->userId, "cash_pay" => "0"))->save($data);
        } else if ($cash_pay == '0') {
            M("merchants_users")->where(array("id" => $this->userId, "cash_pay" => "1"))->save($data);
        }
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['cash_pay'] = $cash_pay;
        M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }

    public function kefu()
    {
        $this->display("kefu");
    }


    /**
     * 绑定手机设备id
     * 用于根据设备id推送消息
     */
    public function bound_device_tag()
    {
        $this->checkLogin();
        $device_tag = I('device_tag');
        $res = M("merchants_users")->where(array("id" => $this->userId))->save(array('device_tag' => $device_tag));
        if ($res) {
            $user_info = M("token")->where(array("token" => $this->token))->getField("value");
            $user_info = json_decode($user_info, true);
            $user_info['device_tag'] = $device_tag;
            M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        }

        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }


    /**
     * 手动推消息
     */
    public function push_message()
    {
        $this->checkLogin();
        $res = M("merchants_users")->where(array("id" => $this->userId))->find();
        $msg = "test caizhuoyue " . date("Y-m-d H:i:s");
        $RegistrationId = $res['device_tag'] ? $res['device_tag'] : '13065ffa4e39c3a140a';
        A("Message/adminpush")->adminpush($msg, "RegistrationId $RegistrationId", "ok", "$RegistrationId");
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }

    public function huiyuanka()
    {
        $parm = I('');
      	
        $this->assign('type', $parm['level_set']);//等级
        $this->assign('jifen', $parm['integral_dikou']);//积分
        $this->assign('zhekou', $parm['discount_set']);//折扣
        $this->assign('chuzhi', $parm['balance_set']);//余额
        $info = M("merchants")->where(array('uid'=>$parm['uid']))->field("merchant_name,base_url")->find();
        $this->assign('merchant_name', $info['merchant_name'] ? $info['merchant_name'] : $parm['brand_name']);
        $this->assign('cardname', $parm['cardname'] ? $parm['cardname'] : '洋仆淘会员卡');
        $this->assign('logoimg', $info['base_url'] ? $info['base_url'] : 'http://sy.youngport.com.cn/themes/simplebootx/Public/pay/images/smalllogo.png');
    	
        $this->assign('color', $parm['color'] ? $this->get_color($parm['color']) : 'red');
        $this->display();
    }

    public function coupon()
    {
        $uid = I("uid");
        $jianchen = M("merchants_users")->where(array('id'=>$uid))->getField("user_name");
        $url_logo = M("merchants")->where(array('uid'=>$uid))->getField("base_url");
        $time = date("Y.m.d",I("start_time"))."-".date("Y.m.d",I("end_time"));
        $this->assign("jianchen",$jianchen);
        $this->assign("url_logo",$url_logo);
        $this->assign("time",$time);

        $this->display();
    }

    // 获得卡卷的颜色
    function get_color($color)
    {
        switch ($color){
            case "Color010":
                return "#63b359";
            case "Color020":
                return "#2c9f67";
            case "Color030":
                return "#509fc9";
            case "Color040":
                return "#5885cf";
            case "Color050":
                return "#9062c0";
            case "Color060":
                return "#d09a45";
            case "Color070":
                return "#e4b138";
            case "Color080":
                return "#ee903c";
            case "Color090":
                return "#dd6549";
            case "Color100":
                return "#cc463d";
        }
    }
	
	//获取商户uid
	private function get_merchant_uid($uid)
    {
        $role_id = M("merchants_role_users")->where("uid='$uid'")->getField('role_id');
        if($role_id == 3){
            $m_uid = $uid;
        }else{
            $user = M("merchants_users")->where("id='$uid'")->find();
            $m_uid = $user['pid'];
        }
        return $m_uid;
    }
	
	public function add_users()
    {
        $data = M('merchants_agent')->alias('ma')
            ->join('left join __MERCHANTS_USERS__ mu on mu.id=ma.uid')
            ->field('mu.user_phone,ma.uid')
            ->select();
        $num = 0;
        foreach($data as $k => $v){
            //dump($v['user_phone']);
            $add['user_login'] = $v['user_phone'];
            $add['user_nicename'] = $v['user_phone'];
            $add['create_time'] = '2017-10-14 18:17:25';
            $add['mobile'] = $v['user_phone'];
            $add['muid'] = $v['uid'];
            $add['platform'] = 2;
            $add['user_pass'] = '###d3ff2d14f6182abfe1631bd1d345929b';
            $id = M('users')->add($add);
            $add_role['role_id'] =3;
            $add_role['user_id'] =$id;
            M('role_user')->add($add_role);
            $num++;
        }
        echo $num;
    }

    public function add_mch_users()
    {
        $data = M('merchants')->alias('m')
            ->join('left join __MERCHANTS_USERS__ mu on mu.id=m.uid')
            ->field('mu.user_phone,m.uid')
            ->where('mu.user_phone > 0')
            ->select();
        //dump($data);die;
        $num = 0;
        foreach($data as $k => $v){
            $add['user_login'] = $v['user_phone'];
            $add['user_nicename'] = $v['user_phone'];
            $add['create_time'] = '2017-10-30 11:11:11';
            $add['mobile'] = $v['user_phone'];
            $add['muid'] = $v['uid'];
            $add['platform'] = 1;
            $add['user_pass'] = '###d3ff2d14f6182abfe1631bd1d345929b';
            $id = M('users')->add($add);
            $add_role['role_id'] = 4;
            $add_role['user_id'] =$id;
            M('role_user')->add($add_role);
            $num++;
        }
        echo $num;
    }
}

