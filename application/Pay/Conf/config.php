<?php

$runtime_home_config= array();
$configs= array(
    'PHONE_API_CALL_URL'    => 'https://sy.youngport.com.cn/index.php?s=Pay/QianFangPay/qianFangPay',   //  钱方支付扫APP
    'TWO_API_CALL_URL'      => 'https://sy.youngport.com.cn/index.php?s=Pay/QianFangPay/twoWxpay',   //  钱方支付双屏
    'JS_API_CALL_URL'       => 'https://sy.youngport.com.cn/index.php?s=Pay/QianFangPay/wxpay',   //  钱方支付扫台签
    'APP_CODE'              => '78DBF8432BC04F92B9FDCC32BAEAAD63',        //正式账号
    'KEY'                   => 'DF18A8A8E65842C68683A766DFFB72E4',
    'HOST'                  => 'https://openapi.qfpay.com',
//    'APP_CODE'              => '1FC313D7DF494B9E8CE9A6C28C190EBB',          //测试账号
//    'KEY'                   => '7AB7F12D1A374208BA9A9E29E337BAEE',
//    'HOST'                  => 'https://openapi-test.qfpay.com',

    'H5_PAY'                => "https://o2.qfpay.com/q/direct",  // 钱方 H5界面 测试用
    'QF_RETURN'             => "https://sy.youngport.com.cn/index.php?s=Pay/QianFangPay/returnpage",
);

return  array_merge($configs,$runtime_home_config);
