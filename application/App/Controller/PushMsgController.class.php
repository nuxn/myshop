<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/2/22
 * Time: 18:06
 */
namespace App\Controller;

use Common\Controller\HomebaseController;

/**
 * 推送消息
 * Class PushMsgController
 * @package App\Controller
 */
class PushMsgController extends HomebaseController
{

    protected $appModel;
    private $pay_model;

    function _initialize()
    {
        $this->pay_model = M('pay');
    }

    /**
     * 消息推送
     * @param $phone
     * @param $uid
     * @param $massage
     * @param $status
     * @param $remark
     * @param $device_tag
     * @param $role_tag
     */
    public function push_msg($phone, $uid, $massage, $status, $remark, $device_tag, $role_tag)
    {
        //声明推送消息日志路径
        $path = get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/message/') . date("Y_m_d_");
        $rs = M("token")->where(array('uid' => $uid))->getField("uid");
        if ($rs) {
            $RegistrationId = $device_tag ? $device_tag : $phone;
            if ($device_tag) A("Message/adminpush")->api_push_msg($massage, "$remark", "ok", "$RegistrationId");//1.3
            else  A("Message/adminpush")->adminpush($massage, $remark, "ok", "$RegistrationId");//1.2
            file_put_contents($path . 'pay_message.log', date("Y-m-d H:i:s") . ',发送信息给' . $role_tag . ': ' . $phone . "___" . $status . "____" . $massage . ",pay表ID:" . "$remark"."($RegistrationId)" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($path . 'pay_message.log', date("Y-m-d H:i:s") . '未发送' . $phone . "未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }


    /**
     * 支付后推送信息
     * @param int $remark
     */
    public function push_pay_message($remark = 0)
    {
        $pay = $this->pay_model->where("remark='$remark'")->find();
        if (!$pay) return;
        A("Api/Cloud")->printer($remark);
        $mid = $pay['merchant_id'];//商户id
        $checker = $pay['checker_id'];//收银员id
        $status = $pay['status'];//支付状态
        $price = $pay['price'];//支付金额
        $mode = $pay['mode'];//支付场景

        $pay_style = array(
            '0' => '台签',
            '1' => 'App扫码支付',
            '2' => 'App刷卡支付',
            '3' => '收银扫码支付',
            '4' => '收银现金支付',
            '5' => '其他支付',
        );

        if ($status == 0) {
            $massage = "收款失败";
        } else if ($status == 1) {
            //$massage = "[" . $pay_style[$mode] . "]" . "来钱啦,收款" . $price . "元！";
            $massage =  "来钱啦,收款" . $price . "元！";
        } else{
            $massage = '';
        }
		$remark = $pay['id'];

        //有收银员的情况下,将信息发给收银员
        if ($checker) {
            $userInfo = D('Api/MerchantsUsers')->get_userOne($checker, 'user_phone,device_tag');
            if ($userInfo['user_phone']) $this->push_msg($userInfo['user_phone'], $checker, $massage, $status, $remark, $userInfo['device_tag'], '收银员');
        }

        //当前商户
        $merchants_info = M("merchants")->where(array('id' => $mid))->field("uid,mid")->find();
        $uid = $merchants_info['uid'];
        $userInfo = D('Api/MerchantsUsers')->get_userOne($uid, 'user_phone,device_tag');
        if ($userInfo['user_phone']) $this->push_msg($userInfo['user_phone'], $uid, $massage, $status, $remark, $userInfo['device_tag'], '商户');

        //多门店大商户
        if ($merchants_info['mid'] > 0) {
            $big_uid = M("merchants")->where(array('id' => $merchants_info['mid']))->getField("uid");
            $userInfo = D('Api/MerchantsUsers')->get_userOne($big_uid, 'user_phone,device_tag');
            if ($userInfo['user_phone']) $this->push_msg($userInfo['user_phone'], $big_uid, $massage, $status, $remark, $userInfo['device_tag'], '多门店商户');
        }

    }


}
