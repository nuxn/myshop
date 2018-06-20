<?php
/**
 * 分表
 * 检查当前表是否存在
 * 存在返回表名，不存在插入返回表名
 */

namespace Common\Lib;

define('TABLE_ID', '1807');

class Subtable
{

    /**
     * 根据条件返回表名
     * @param string $tableName 原始表名
     * @param array $param 条件
     * @param null $tablePrefix 前缀
     * @return string
     */
    static public function getSubTableName($tableName = '', $param = array(), $tablePrefix = null)
    {

        if ($tablePrefix === null) $tablePrefix = '';# 默认M方法调用不要前缀
        else $tablePrefix = $tablePrefix ?: C('DB_PREFIX');# 非null时采用默认前缀或传递前缀

        if (!empty($param['order_sn'])) $SubTableName = $tablePrefix . $tableName . '_' . substr($param['order_sn'], 2, 6);# 根据单号返分表名
        else if (date("ym", time()) < TABLE_ID) $SubTableName = $tablePrefix . $tableName;# 分表开始日期前返回原有表名
        else $SubTableName = $tablePrefix . $tableName . '_' . date("ym", time());# 返回按月分表
        $tableName = strtolower($tableName);
        self::$tableName($tablePrefix ?: C('DB_PREFIX') . $SubTableName);# 判断创建

        return $SubTableName;
    }

    /**
     * 创建表
     * @param $tableName
     * @param $sql
     */
    static public function createTable($tableName = '', $sql = '')
    {
        try {
            $check_tables_sql = "show tables like '" . $tableName . "'";
            $check_tables_result = M()->query($check_tables_sql);

            #  按月生成，没有插入生成
            if (!$check_tables_result) {
                // 创建表时更新自增ID
                if (strstr($tableName, TABLE_ID)) {# 包含常量说明是起始月份
                    $last_table_name = mb_substr($tableName, -20, -5);
                } else {
                    $ym = mb_substr($tableName, -4);# 表的年月序号
                    $ym -= 1;
                    if (mb_substr($ym, -2) == '00') $ym = (mb_substr($ym, 2) - 1) . '12';
                    $last_table_name = mb_substr($tableName, -20, -5) . $ym; # 获得上月该表表ID
                }

                $end_sql = "select max(id)end_id from `" . $last_table_name . "`";# 查询上个月该表的结束ID

                $rs = M()->query($end_sql);
                $end_id = !empty($rs[0]['end_id']) ? $rs[0]['end_id'] : '0';
                $end_id += 1;
                $sql = str_replace("AUTO_INCREMENT=1", "AUTO_INCREMENT=$end_id", "$sql");# 将自增ID替换为上月该表结束ID+1
                $sql = "CREATE TABLE `" . $tableName . "`" . $sql;
                M()->query($sql);
            }
        } catch (\Exception $e) {
            // echo $e->getMessage();
        }
    }

    /**
     * 支付成功表
     * @param string $tableName
     */
    public static function pay($tableName = '')
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        self::createTable($tableName, $sql);
    }

    /**
     * 订单表
     * @param string $tableName
     */
    public static function order($tableName = '')
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        self::createTable($tableName, $sql);
    }
}