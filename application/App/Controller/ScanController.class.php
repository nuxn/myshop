<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2018/1/24
 * Time: 11:01
 */

namespace App\Controller;

use Common\Controller\HomebaseController;

/**
 * 扫一扫
 * Class ScanController
 * @package App\Controller
 */
class ScanController extends HomebaseController
{
    //默认页面
    public function index()
    {
        $info = $this->getConfig();
        $this->assign('parm', $info);
        $this->display('Member/scan');
    }

    //获取wx.config配置参数
    public function getConfig()
    {
        $token = get_weixin_token();
        $wxModel = M("weixin_token");
        $appid = 'wx3fa82ee7deaa4a21';
        $wxInfo = $wxModel->where(array("type" => "1"))->find();
        $ticket = !empty($wxInfo['tickets']) ? $wxInfo['tickets'] : '';

        if ($wxInfo['t_time'] + 7000 < time() || empty($wxInfo['tickets'])) {
            //  获取ticket
            $ticket_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token . '&type=jsapi';
            $result = request_post($ticket_url);
            $res = json_decode($result, true);
            if (!empty($res['ticket']) && $res['errcode'] == 0) {
                $ticket = $res['ticket'];
                $wxModel->where(array("type" => "1"))->save(array('tickets' => $ticket, 't_time' => time()));
            }
        }
        $http_type = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $param = array(
            'redirect_url' => $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'appid' => $appid,
            'jsapi_ticket' => $ticket,
        );
        $rs = $this->get_wx_config($param);
        return $rs;
    }

    //生成配置参数
    public function get_wx_config($param = array())
    {
        $timestamp = time();
        $nonceStr = rand(100000, 999999);
        $Parameters = array();
        //===============下面数组 生成SING 使用=====================
        $Parameters['url'] = $param['redirect_url'];
        $Parameters['timestamp'] = "$timestamp";
        $Parameters['noncestr'] = "$nonceStr";
        $Parameters['jsapi_ticket'] = $param['jsapi_ticket'];
        // 生成 SING
        $addrSign = $this->genSha1Sign($Parameters);
        $retArr = array();
        $retArr['appid'] = $param['appid'];
        $retArr['timestamp'] = "$timestamp";
        $retArr['noncestr'] = "$nonceStr";
        $retArr['signature'] = "$addrSign";
        return $retArr;
    }

    //创建签名SHA1
    public function genSha1Sign($Parameters)
    {
        $signPars = '';
        ksort($Parameters);
        foreach ($Parameters as $k => $v) {
            if ("" != $v && "sign" != $k) {
                if ($signPars == '')
                    $signPars .= $k . "=" . $v;
                else
                    $signPars .= "&" . $k . "=" . $v;
            }
        }
        //$signPars = http_build_query($Parameters);
        $sign = SHA1($signPars);
        $Parameters['sign'] = $sign;
        return $sign;
    }

}