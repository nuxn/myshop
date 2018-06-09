<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/28
 * Time: 10:19
 */
namespace Api\Model;

use Think\Model;

/**
 * 处理微信会员
 * Class ScreenMemModel
 * @package Api\Model
 */
class ScreenMemModel extends Model
{
    public $memcardModel, $memberModel, $path;

    public function _initialize()
    {
        $this->memcardModel = M("screen_memcard");
        $this->memberModel = M("screen_mem");
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/';
    }


    /**
     * 【微信】扫码后添加会员到会员表
     * 调用案例 $mem_id=D("Api/ScreenMem")->add_member("$openid","$merchant_id");
     * @param string $openid 消费者(顾客)openid
     * @param string $merchant_id 用户表主键
     * @return mixed 插入的会员ID
     */
    public function add_member($openid = '', $merchant_id = '')
    {
        //根据openid获取用户微信信息
        $userinfo = A("App/Member")->get_wx_user_info("$openid");
        //获取merchants表主键即用户id
        $uid = M("merchants")->where(array('id' => $merchant_id))->getField('uid');
        if (!$userinfo['openid'] || !$uid) return $openid;
        //插入会员表信息
        $usr_arr = array(
            "openid" => $openid,
            "add_time" => time(),
            "userid" => $uid,
            "memimg" => $userinfo['headimgurl'] ? $userinfo['headimgurl'] : '',
            "nickname" => $userinfo['nickname'] ? $userinfo['nickname'] : '',
            "unionid" => $userinfo['unionid']
        );
        get_date_dir($this->path . 'member/','member','ScreenMemModel支付插入会员表信息',json_encode($usr_arr));

        if ($id = $this->memberModel->where(array('unionid' => $userinfo['unionid'], 'userid' => $uid))->getField('id')) {
            return $id;
        } else {
            return $this->memberModel->add($usr_arr);
        }

    }

    /**
     * 【支付宝】扫码后添加会员到会员表
     * 调用案例 $mem_id=D("Api/ScreenMem")->increase_member("$user_info","$merchant_id");
     * @param string $openid 消费者(顾客)openid
     * @param string $merchant_id 用户表主键
     * @return mixed 插入的会员ID
     */
    public function increase_member($user_info, $merchant_id, $user_type = 'alipay')
    {
        if ($user_type != 'alipay') return '';
        $uid = M("merchants")->where(array('id' => $merchant_id))->getField('uid');
        if (!$user_info || !$uid) return 'alipay';
        //插入会员表信息
        $usr_arr = array(
            "openid" => $user_info['alipay_user_id'] ? $user_info['alipay_user_id'] : '',
            "add_time" => time(),
            "userid" => $uid,
            "memimg" => $user_info['avatar'] ? $user_info['avatar'] : '',
            "nickname" => $user_info['nick_name'] ? $user_info['nick_name'] : '',
            "gender" => $user_info['gender'] == 'm' ? '1' : '0',
            "unionid" => $user_info['user_id'] ? $user_info['user_id'] : '',
            "type" => 'alipay',
        );

        get_date_dir($this->path . 'member/','member','【支付宝】支付插入会员表信息',__FILE__.json_encode($usr_arr));

        if ($id = $this->memberModel->where(array('unionid' => $user_info['user_id'], 'userid' => $uid))->getField('id')) {
            return $id;
        } else {
            return $this->memberModel->add($usr_arr);
        }

    }


}