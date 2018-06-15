<?php
//B扫C 
//参数
//pay_type:1支付宝2微信3qq
//order_id 订单ｉｄ
$app_config[1][1]=array('index','index','C_b_pay');
//C扫B
//参数
//pay_type:1支付宝2微信3qq
//order_id 订单ｉｄ
//walletAuthCode 用户条码
$app_config[1][2]=array('index','index','B_c_pay');
//h5
//参数
//pay_type:1支付宝2微信3qq
//order_id 订单ｉｄ
//uuid  客户openid
$app_config[1][3]=array('index','index','js_pay');
//查询接口
$app_config[1][4]=array('index','index','alifun');
//撤销接口
$app_config[1][5]=array('index','index','check');
$app_config[2][1]=array('login','login','login');
//商家查询
$app_config[2][2]=array('login','login','check');
$app_config[2][3]=array('login','login','into');
//撤销
$app_config[3][1]=array('paylog','index','descpay');
//退款
$app_config[3][2]=array('paylog','index','refund');
//
$app_config[3][3]=array('paylog','index','check');
$app_config[3][4]=array('paylog','index','bill_download');








//
$app_config[4][1]=array('pulg','index','province');
$app_config[4][2]=array('pulg','index','city');
$app_config[4][3]=array('pulg','index','bank');
$app_config[4][4]=array('pulg','index','subbranch');
$app_config[4][5]=array('pulg','index','qwp');
$app_config[4][6]=array('pulg','index','qwpchild');