<?php

return  array(
    // 会员卡设置
    "MEMCARD" => array(
//        "level_url"         => "http://sy.youngport.com.cn/index.php?s=Api/Member/getMemberLevel",      // 会员等级URL
        "level_url"         => "http://sy.youngport.com.cn/index.php?s=Api/Member/memberLevel",      // 会员等级URL
        "discount_url"      => "http://sy.youngport.com.cn/index.php?s=Api/Member/getMemberDiscount",   // 会员折扣URL
        //储值
        "balance_url"       => "https://sy.youngport.com.cn/index.php?s=api/cz/info",          // 会员储值URL
        "balance_rules"     => "可用于对应门店消费",   //储值说明
        //营销场景的自定义入口名称
//        'custom_url_name'=> '适用门店',
//        'custom_url'     => 'http://m.hz41319.com/wei/index.php',
//        'custom_url_sub_title' => '500米',         // 右侧提示6汉字，18字节
        //自定义跳转外链的入口
//        'promotion_url_sub_title'=> '充100送1000',   // 右侧提示6汉字，18字节
        'promotion_url_name'=> '会员卡充值',
        'promotion_url'     => 'https://sy.youngport.com.cn/index.php?s=api/cz/index',
        // 快速买单
        "center_url"        => "https://sy.youngport.com.cn/index.php?s=api/base/quick_buy",             // 快速买单URL
        'center_title'      => '快速买单',              // string（18）
        //'center_sub_title'  => '买单可享积分抵扣',       // string（24）
        'center_sub_title'  => '',       // string（24）

        'notice'            => '使用时向服务员出示',         //卡券使用提醒
        'prerogative'       => '',       //会员卡特权说明,限制1024汉字
    ),

);
