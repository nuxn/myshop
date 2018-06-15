<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**
 * 用户微信用户界面显示
 * Class MemberController
 * @package Api\Controller
 */
class WechatController extends ApibaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getcode()
    {
        if (IS_POST) {
            if (IS_AJAX) {
                $phone = I("post.phone", '', 'trim');
                if (empty($phone)) {
                    $this->ajaxReturn(array("code" => "4", "msg" => "手机号码为空"));
                }
                Vendor("SMS.CCPRestSmsSDK");
                $config_arr = C('SMS_CONFIG'); // 读取短信配置
                $rest = new \REST($config_arr['serverIP'], $config_arr['serverPort'], $config_arr['softVersion']);
                $rest->setAccount($config_arr['accountSid'], $config_arr['accountToken']);
                $rest->setAppId($config_arr['appId']);

                $sms_msg = rand(1000, 9999); //生成短信信息
                S($phone, $sms_msg, 600);// 缓存$str数据3600秒过期
                $result = $rest->sendTemplateSMS($phone, array($sms_msg, '5'), '245484'); // 发送模板短信
                if ($result == NULL) {
                    $this->ajaxReturn(array("code" => '0', "msg" => "result error!"));
                }
                if ($result->statusCode != 0) { // 错误
                    $this->ajaxReturn(array("code" => $result->statusCode, "msg" => $result->statusMsg));
                } else {
                    $this->ajaxReturn(array("code" => "1", "msg" => "短信发送成功"));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "3", "msg" => "参数错误"));
        }
    }

    public function have_card()
    {
        $card_id = I('card_id');
        $card_code = I('encrypt_code','','trim');
        $card_code = str_replace(' ','+',$card_code);
        $card_code = $this->decrypt_code($card_code);
        $this->assign('card_code', $card_code);
        $this->assign('card_id', $card_id);
        $this->display();
        die;
    }

    public function activate()
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
        if(IS_POST){
            $input = I('');
            $this->phone = $input['phone'];
            $verify = $input['verify'];
            $this->real_code = $input['real_code'];
            $this->card_code = $input['card_code'];
            $this->CardId = $input['card_id'];
            if (empty($this->phone)) $this->ajaxReturn(array("code" => "4", "msg" => "手机号码为空"));
            if (empty($verify)) $this->ajaxReturn(array("code" => "4", "msg" => "验证码为空"));
            if (empty($this->real_code)) $this->ajaxReturn(array("code" => "4", "msg" => "实体卡号为空"));
            if(S($this->phone) != $verify) $this->ajaxReturn(array("code" => "4", "msg" => "验证码错误"));
            $this->get_real_data();  // 获取实体卡的积分储值信息
            $res = $this->wxactivate();
            if($res){
                M()->commit();
                $this->ajaxReturn(array("code" => "1", "msg" => "激活成功"));
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "4", "msg" => "激活失败！"));
            }
        }
    }

    public function get_real_data()
    {
        $use_model = M('screen_memcard_use');
        $mem_model = M('screen_mem');
        # 实体卡领取表数据
        $real_use = $use_model->where(array('entity_card_code'=> $this->real_code))->find();
        # 未查询到数据
        if(!$real_use)$this->ajaxReturn(array("code" => "4", "msg" => "实体卡号错误"));
        # 如果实体卡号已绑定其他微信或者还未开卡，提示：实体卡号错误！
        if($real_use['e_status'] == 0 || !$real_use['memid'])$this->ajaxReturn(array("code" => "4", "msg" => "实体卡号错误!"));

        # 实体卡会员信息
        $real_mem = $mem_model->where(array('id' => $real_use['memid']))->find();
        # 未查询到数据
        if(!$real_mem)$this->ajaxReturn(array("code" => "4", "msg" => "实体卡号错误！"));
        # 如果实体卡号的手机号，和微信这里输入的手机号不一致，无法绑定，提示：手机号错误！
        if($this->phone != $real_mem['memphone'])$this->ajaxReturn(array("code" => "4", "msg" => "手机号错误！"));

        # 微信会员卡领取数据
        $wx_use = $use_model->where(array('card_code'=> $this->card_code))->find();
        $wx_mem = $mem_model->where(array('id' => $wx_use['memid']))->find();
        if ($wx_use['id'] == $real_use['id']) {
            # code...
            $this->ajaxReturn(array("code" => "4", "msg" => "已绑定"));
        }
        get_date_dir($this->path,'entity_card_code','实体卡use数据', json_encode($real_use));
        get_date_dir($this->path,'entity_card_code','实体卡mem数据', json_encode($real_mem));
        get_date_dir($this->path,'entity_card_code','wx卡use数据', json_encode($wx_use));
        get_date_dir($this->path,'entity_card_code','wx卡mem数据', json_encode($wx_mem));
        get_date_dir($this->path,'entity_card_code','微信会员卡数据', "微信会员卡use表id({$wx_use[id]}),mem表id({$wx_mem[id]})");

        # 领取表需要更改同步的数据
        $use_change_data['entity_card_code'] = $real_use['entity_card_code'];
        $use_change_data['card_amount'] = $real_use['card_amount'];
        $use_change_data['card_balance'] = $real_use['card_balance'];
        $use_change_data['yue'] = $real_use['yue'];
        $use_change_data['pay_pass'] = $real_use['pay_pass'];
        $use_change_data['level'] = $real_use['level'];
        $use_change_data['status'] = 1;
        $use_change_data['e_status'] = 1;
        $this->card_balance = $real_use['card_balance'];
        $this->yue = $real_use['yue'];
        # 操作
        M()->startTrans();
        $use_res = $use_model->where(array('id'=> $wx_use['id']))->save($use_change_data);
        get_date_dir($this->path,'entity_card_code','更新use表', json_encode($use_change_data));

        # 会员信息表需要同步的数据
        $mem_change_data['memphone'] = $real_mem['memphone'];
        $mem_change_data['realname'] = $real_mem['realname'];
        $mem_change_data['levelid'] = $real_mem['levelid'];
        $mem_change_data['sex'] = $real_mem['sex'];
        $mem_change_data['status'] = 1;
        $mem_res = $mem_model->where(array('id' => $wx_mem['id']))->save($mem_change_data);
        get_date_dir($this->path,'entity_card_code','更新mem表', json_encode($mem_change_data));

        if($use_res !== false && $mem_res !== false){
            $use_del = $use_model->where(array('id'=>$real_use['id']))->delete();
            $mem_del = $mem_model->where(array('id'=>$real_mem['id']))->delete();
            get_date_dir($this->path,'entity_card_code','删除老数据', "use表：{$use_del},mem表：{$mem_del}");
        } else {
            M()->rollback();
            $this->ajaxReturn(array("code" => "4", "msg" => "激活失败！"));
        }
    }

    /**
     * 请求微信激活接口
     */
    public function wxactivate()
    {
        $token = get_weixin_token();
        $arr = array(
            "init_bonus" => "$this->card_balance",//初始积分
            "init_bonus_record" => urlencode("旧积分同步"),
            "membership_number" => "$this->card_code",//会员卡编号
            "card_id" => "$this->CardId",
            "code" => "$this->card_code",
            "init_custom_field_value1" => "$this->yue"
        );

        $mem_card_query_url = "https://api.weixin.qq.com/card/membercard/activate?access_token=$token";
        get_date_dir($this->path,'entity_card_code','请求微信激活', urldecode(json_encode($arr)));
        $result = request_post($mem_card_query_url, urldecode(json_encode($arr)));
        get_date_dir($this->path,'entity_card_code','微信返回', $result);
        $result = json_decode($result, true);
        if($result['errcode'] == 0 && $result['errmsg'] == 'ok'){
            return true;
        } else {
            return false;
        }
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