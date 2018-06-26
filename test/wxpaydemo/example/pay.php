<?php

        header("Content-type:text/html;charset=utf-8");
        //vendor('Wzpay.Wzpay');
        include('Wzpay.php');
		//var_dump(333);
        $wzPay = new \Wzpay();
        $sub_openid = 'oyaFdwKf7Hg-uK9efnS8KojGaXW8';
         $remark=date("YmdHis").rand(10000,99999);
//       ֧�������ύ�����ݽ���
        $mchid = '107584002080010';
        //ʹ��ͳһ֧���ӿ�()
        $wzPay->setParameter('sub_openid', $sub_openid);
        $wzPay->setParameter('mch_id', $mchid);
        $wzPay->setParameter('body', 'pay');
        $wzPay->setParameter('out_trade_no', $remark);
        $wzPay->setParameter('goods_tag', 1213);
        $wzPay->setParameter('total_fee', 10);
        $jsApiParameters = $wzPay->getParameters();
        $jsApiParameters='{"appId":"wx30e7c3a68ab20c6d","timeStamp":"1498135500","signType":"MD5","package":"prepay_id=wx20170622204500433a 3ebe960414571148","nonceStr":"b68a313c02c2482eb17ce7a0300909f6","paySign":"5215EDFCE0AFD4CB8B721DC171918644"}';

?>

<html>
<head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>微信支付样例-支付</title>
        <script type="text/javascript">
                //调用微信JS api 支付
                function jsApiCall()
                {
                        WeixinJSBridge.invoke(
                            'getBrandWCPayRequest',
                            <?php echo $jsApiParameters; ?>,
                            function(res){
                                    WeixinJSBridge.log(res.err_msg);
                                    alert(res.err_code+res.err_desc+res.err_msg);
                            }
                        );
                }

                function callpay()
                {
                        if (typeof WeixinJSBridge == "undefined"){
                                if( document.addEventListener ){
                                        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                                }else if (document.attachEvent){
                                        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                                        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                                }
                        }else{
                                jsApiCall();
                        }
                }
        </script>
</head>
<body>
<br/>
<font color="#9ACD32"><b>该笔订单支付金额为<span style="color:#f00;font-size:50px">1分</span>钱</b></font><br/><br/>
<div align="center">
        <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
</div>
</body>
</html>

	
