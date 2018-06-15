<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;

/**测试接口封装
 * Class TestapiController
 * @package Pay\Controller
 */
class TestapiController extends HomebaseController
{

    /**
     * 微信jspay支付
     */
    public function wxjspay()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/leShua/','testapi',':日志', json_encode($_REQUEST));
        header("Content-type:text/html;charset=utf-8");
        $returnData = $_REQUEST['payInfo'];
        $return_url = $_REQUEST['return_url'];
        if (!$returnData) $returnData = '{"appId":"wx30e7c3a68ab20c6d","timeStamp":"1502679790","signType":"MD5","package":"prepay_id=wx20170814110310a3aed91a5c0595949700","nonceStr":"2a43a96e41544ac0b0617ca53d22ac4c","paySign":"2064486B12418FAC32FD8A340630CAE5"}';
        $this->assign('jsApiParameters', $returnData);
        $this->assign('return_url', $return_url);
        $this->display();
    }

    public function alijspay()
    {
        header("Content-type:text/html;charset=utf-8");
        $tradeNO = $_REQUEST['payInfo'];
        $this->assign('tradeNO', $tradeNO);
        $this->display();
    }
}



