<?php
/**
 *
 */

namespace Common\Model;

use Common\Model\CommonModel;

class Createsql extends CommonModel
{
    public function exc()
    {
        self::pay();
        self::sms();
        self::authent();
        self::childTask();
    }

    public static function pay()
    {
        $sql = " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_fee` decimal(10,2) DEFAULT '0.00' COMMENT '交易金额',
  `merchant_id` int(11) DEFAULT '0' COMMENT '渠道商id',
  `merchant_user_id` int(11) DEFAULT '0' COMMENT '子商户id',
  `channel_id` tinyint(3) DEFAULT '0' COMMENT '通道id',
  `business_id` tinyint(3) DEFAULT '0' COMMENT '业务id',
  `user_config_id` int(11) DEFAULT '0' COMMENT '子商户商户号id',
  `status` tinyint(3) DEFAULT '0' COMMENT '支付状态(0待支付1支付成功4支付中5支付失败 2 退款成功 3订单异常',
  `request_status` tinyint(1) DEFAULT '1' COMMENT '上游请求状态0失败1成功 成功才记账',
  `liquidation_status` tinyint(1) DEFAULT '0' COMMENT '清算状态 0待入款 1已入款 2入款失败',
  `pay_style` tinyint(3) DEFAULT '0' COMMENT '支付样式,1 套现 2消费 3.还款',
  `mer_order_sn` varchar(255) DEFAULT NULL COMMENT '商户订单号',
  `ypt_order_sn` varchar(255) DEFAULT NULL COMMENT '洋仆淘订单号',
  `party_order_sn` varchar(255) DEFAULT NULL COMMENT '第三方订单号',
  `debit_id` int(11) DEFAULT '0' COMMENT '借记卡user表主键',
  `credit_id` int(11) DEFAULT '0' COMMENT '信用卡user表主键',
  `create_time` int(11) DEFAULT '0' COMMENT '下单时间',
  `pay_time` int(11) DEFAULT '0' COMMENT '支付时间',
  `comment` varchar(255) DEFAULT NULL COMMENT '备注',
  `notify_url` varchar(255) DEFAULT NULL COMMENT '回调地址',
  `notify_status` int(11) DEFAULT '0' COMMENT '0下游未返回接受回调成功1下游返回回调成功不在推送',
  `user_rate` decimal(10,3) DEFAULT '0.000' COMMENT '子商户费率',
  `user_payment` decimal(10,3) DEFAULT '0.000' COMMENT '子商户代付费',
  `mer_rate` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道费率',
  `mer_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道代理费',
  `ypt_rate` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本费率',
  `ypt_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本代付费',
  `ypt_profit` decimal(10,2) DEFAULT '0.00' COMMENT '洋仆淘利润 （交易金额*（渠道商费率-洋仆淘费率））+渠道商代付费-洋仆淘代付费',
  `mer_profit` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商利润 （交易金额*（商户通道费率-渠道商通道费率））+商户通道代付费-渠道商通道代付费',
  PRIMARY KEY (`id`),
  KEY `debit_id` (`debit_id`,`credit_id`,`merchant_id`,`merchant_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8";
        $check_tables_sql = "show tables like 'yf_order_pay_" . date('Ym') . "'";
        $check_tables_result = Db::name('')->query($check_tables_sql);
        //对账单表按月生成，没有插入生成
        if (!$check_tables_result) {
            $sql = "CREATE TABLE `yf_order_pay_" . date('Ym') . "`" . $sql;
            Db::name('')->query($sql);
        }
    }

    public static function sms()
    {
        $sql = " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_fee` decimal(10,2) DEFAULT '0.00' COMMENT '交易金额',
  `merchant_id` int(11) DEFAULT '0' COMMENT '渠道商id',
  `channel_id` tinyint(3) DEFAULT '0' COMMENT '通道id',
  `business_id` tinyint(3) DEFAULT '0' COMMENT '业务id',
  `template` varchar(255) DEFAULT NULL COMMENT '模板',
  `paymer_message` varchar(255) DEFAULT NULL COMMENT '信息',
  `paymer_phone` varchar(255) DEFAULT NULL COMMENT '手机号',
  `status` tinyint(3) DEFAULT '0' COMMENT '发送状态0失败1成功',
  `request_status` tinyint(1) DEFAULT '1' COMMENT '上游请求状态0失败1成功 成功才记账',
  `mer_order_sn` varchar(255) DEFAULT NULL COMMENT '商户订单号',
  `ypt_order_sn` varchar(255) DEFAULT NULL COMMENT '洋仆淘订单号',
  `party_order_sn` varchar(255) DEFAULT NULL COMMENT '第三方订单号',
  `create_time` int(11) DEFAULT '0' COMMENT '下单时间',
  `update_time` int(11) DEFAULT '0' COMMENT '修改时间',
  `comment` varchar(255) DEFAULT NULL COMMENT '备注',
  `mer_rate` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道费率',
  `mer_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道代理费',
  `ypt_rate` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本费率',
  `ypt_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本代付费',
  `ypt_profit` decimal(10,2) DEFAULT '0.00' COMMENT '洋仆淘利润 （交易金额*（渠道商费率-洋仆淘费率））+渠道商代付费-洋仆淘代付费',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8";
        $check_tables_sql = "show tables like 'yf_order_sms_" . date('Ym') . "'";
        $check_tables_result = Db::name('')->query($check_tables_sql);
        //对账单表按月生成，没有插入生成
        if (!$check_tables_result) {
            $sql = "CREATE TABLE `yf_order_sms_" . date('Ym') . "`" . $sql;
            Db::name('')->query($sql);
        }
    }

    public static function authent()
    {
        $sql = " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_fee` decimal(10,2) DEFAULT '0.00' COMMENT '交易金额',
  `merchant_id` int(11) DEFAULT '0' COMMENT '渠道商id',
  `channel_id` tinyint(3) DEFAULT '0' COMMENT '通道id',
  `business_id` tinyint(3) DEFAULT '0' COMMENT '业务id',
  `paymer_name` varchar(255) DEFAULT NULL COMMENT '姓名',
  `paymer_idcard` varchar(255) DEFAULT NULL COMMENT '身份证',
  `paymer_bank_no` varchar(255) DEFAULT NULL COMMENT '银行卡号',
  `paymer_phone` varchar(255) DEFAULT NULL COMMENT '预留手机号',
  `status` tinyint(3) DEFAULT '0' COMMENT '鉴权状态0鉴权失败1鉴权成功',
  `request_status` tinyint(1) DEFAULT '1' COMMENT '上游请求状态0失败1成功  成功才记账',
  `mer_order_sn` varchar(255) DEFAULT NULL COMMENT '商户订单号',
  `ypt_order_sn` varchar(255) DEFAULT NULL COMMENT '洋仆淘订单号',
  `party_order_sn` varchar(255) DEFAULT NULL COMMENT '第三方订单号',
  `create_time` int(11) DEFAULT '0' COMMENT '下单时间',
  `update_time` int(11) DEFAULT '0' COMMENT '修改时间',
  `comment` varchar(255) DEFAULT NULL COMMENT '备注',
  `mer_rate` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道费率',
  `mer_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '渠道商通道代理费',
  `ypt_rate` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本费率',
  `ypt_paymemt` decimal(10,3) DEFAULT '0.000' COMMENT '洋仆淘通道成本代付费',
  `ypt_profit` decimal(10,2) DEFAULT '0.00' COMMENT '洋仆淘利润 （交易金额*（渠道商费率-洋仆淘费率））+渠道商代付费-洋仆淘代付费',
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $check_tables_sql = "show tables like 'yf_order_authent_" . date('Ym') . "'";
        $check_tables_result = Db::name('')->query($check_tables_sql);
        //对账单表按月生成，没有插入生成
        if (!$check_tables_result) {
            $sql = "CREATE TABLE `yf_order_authent_" . date('Ym') . "`" . $sql;
            Db::name('')->query($sql);
        }
    }

    public static function childTask()
    {
        $sql = " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) DEFAULT '0' COMMENT '任务表id',
  `pay_task_status` tinyint(3) DEFAULT '0' COMMENT '定时任务执行状态 1,未执行，2已执行，3执行失败.4执行中,5支付中,6 支付出账',
  `pay_style` tinyint(3) DEFAULT '1' COMMENT '1,消费，2还款.3异常还款',
  `total_fee` float(8,2) DEFAULT '0.00' COMMENT '金额',
  `pay_task_time` int(11) DEFAULT '0' COMMENT '定时任务执行时间',
  `msg` varchar(255) DEFAULT '' COMMENT '执行错误原因',
  `fast_count` tinyint(3) DEFAULT '0' COMMENT '发起次数默认不超过5次 超过5次默认失败',
  `ypt_order_sn` tinytext COMMENT '洋仆淘订单号 json格式',
  `mer_order_sn` tinytext COMMENT '渠道商订单号json格式',
  `notifyUrl` varchar(255) DEFAULT NULL COMMENT '回调地址',
  `notify_status` tinyint(3) DEFAULT '0' COMMENT '0下游未返回成功1下游返回成功',
  `err_status` tinyint(3) DEFAULT '0' COMMENT '0正常订单1问题订单运营处理',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `task_id` (`task_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;";
        $check_tables_sql = "show tables like 'yf_task_children_" . date('Ym') . "'";
        $check_tables_result = Db::name('')->query($check_tables_sql);
        //对子任务表按月生成，没有插入生成
        if (!$check_tables_result) {
            $sql = "CREATE TABLE `yf_task_children_" . date('Ym') . "`" . $sql;
            Db::name('')->query($sql);
        }
    }
}






