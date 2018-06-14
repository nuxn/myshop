<?php

namespace Wechat\Controller;

use Think\Controller;

/**
 * Class MessageController
 * @package Wechat\Controller
 */
class MessageController extends Controller
{

    private $open_id;
    private $send_url;
    private $parameters;
    private $template_id = '';

    public function __construct()
    {
        parent::__construct();
        header("Content-type: Application/json; charset=utf-8");
        $token = get_weixin_token();
        $this->send_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
    }

    public function setType($type)
    {
        switch ($type) {
            case 1:    // use_balance 使用余额支付消息推送模板ID
                $this->template_id = "IAoDpHbEcB68WtCvLv2_kHnZ1FBxVcr_-b3mdm11Z34";
                break;
            case 2:    // recharge 充值成功消息推送模板ID
                $this->template_id = "vvnjYzm0GNrBhlylYSoAo_2HC7mMxjr708kuCtrYx14";
                break;
            case 3:    // refund 退款成功消息推送模板ID
                $this->template_id = "sGKQcAB0Hp9zNqDbGCyRT7fG_gf-2jDBrynMLy9XNY8";
                break;
            default:
                return false;
                break;
        }
    }

    public function setOpenid($open_id)
    {
        $this->open_id = $open_id;
    }

    public function setParameters($key, $val)
    {
        $this->parameters[$key] = array('value' => urlencode($val));
    }

    public function sendMessage()
    {
        if (!$this->template_id || !$this->open_id || !$this->parameters) {
            return false;
        }
        $data['touser'] = $this->open_id;
        $data['template_id'] = $this->template_id;
        $data['data'] = $this->parameters;

        return request_post($this->send_url, urldecode(json_encode($data)));
    }


    /**
     * 使用余额支付消息推送
     * @param $open_id      推送用户的open_id
     * @param $card_code    会员卡号
     * @param $use          消费金额
     * @param $mch_name     消费门店
     * @param $yue          当前余额
     * @return mixed
     */
    public function use_balance($open_id,$card_code,$use,$mch_name,$yue)
    {
        $data['touser'] = $open_id;
        $data['template_id'] = 'IAoDpHbEcB68WtCvLv2_kHnZ1FBxVcr_-b3mdm11Z34';
        $data['data'] = array(
            'first' => array('value' => urlencode('会员卡消费成功')),
            'keyword1' => array('value' => urlencode("$card_code")),
            'keyword2' => array('value' => urlencode("$use 元")),
            'keyword3' => array('value' => urlencode(date('Y年m月d日 H:i'))),  // 消费时间
            'keyword4' => array('value' => urlencode($mch_name)),
            'keyword5' => array('value' => urlencode("$yue 元")),
            'remark' => array('value' => urlencode('如有疑问，请联系商家处理！')),
        );
        return request_post($this->send_url, urldecode(json_encode($data)));
    }

    /**
     * 充值成功消息推送
     * @param $open_id  推送用户的open_id
     * @param $amount   充值金额
     * @param $send     赠送金额
     * @param $mch_name 充值门店
     * @param $yue      当前余额
     * @return mixed
     */
    public function recharge($open_id,$amount,$send,$mch_name,$yue)
    {
        $data['touser'] = $open_id;
        $data['template_id'] = 'vvnjYzm0GNrBhlylYSoAo_2HC7mMxjr708kuCtrYx14';
        $data['data'] = array(
            'first' => array('value' => urlencode('会员卡充值成功！')),
            'keyword1' => array('value' => urlencode("$amount 元")),
            'keyword2' => array('value' => urlencode("$send 元")),
            'keyword3' => array('value' => urlencode($mch_name)),
            'keyword4' => array('value' => urlencode("$yue 元")),
            'remark' => array('value' => urlencode('如有疑问，请联系商家处理！')),
        );
        return request_post($this->send_url, urldecode(json_encode($data)));
    }

    /**
     * 退款成功消息推送
     * @param $open_id  推送用户的open_id
     * @param $amount   退款金额
     * @param $mch_name 门店名称
     * @return mixed
     */
    public function refund($open_id,$amount,$mch_name)
    {
        $data['touser'] = $open_id;
        $data['template_id'] = 'sGKQcAB0Hp9zNqDbGCyRT7fG_gf-2jDBrynMLy9XNY8';
        $data['data'] = array(
            'first' => array('value' => urlencode('会员卡退款成功')),
            'keyword1' => array('value' => urlencode(date('Y年m月d日 H:i'))),
            'keyword2' => array('value' => urlencode("$amount 元")),
            'keyword3' => array('value' => urlencode($mch_name)),
            'remark' => array('value' => urlencode('如有疑问，请联系商家处理！')),
        );
        return request_post($this->send_url, urldecode(json_encode($data)));
    }
}