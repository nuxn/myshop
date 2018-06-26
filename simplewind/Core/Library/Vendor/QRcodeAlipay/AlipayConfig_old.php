<?php

/**
 * 微众——支付宝——配置信息
 * Created by PhpStorm.
 * User: zgf
 * Date: 2017/1/13
 * Time: 13:46
 */
class AlipayConfig
{
    //=======【测试】=====================================
    //【证书路径】,注意应该填写绝对路径
//    const SSLCERT_PATH = '/alidata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/test_cert/apiclient_cert.pem';
//
//    const SSLKEY_PATH = '/alidata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/test_cert/apiclient_key.pem';
//    //商户入驻
//    const REGMCH_RUL = 'https://l.test-svrapi.webank.com/api/acq/server/alipay/regmch';
//    //获取access_token
//    const ACCESS_TOKEN = 'https://l.test-svrapi.webank.com/api/oauth2/access_token';
//    //获取api_ticket
//    const TICKETS = 'https://l.test-svrapi.webank.com/api/oauth2/api_ticket';
//    //扫码支付
//    const PAY_TO = 'https://l.test-svrapi.webank.com/api/acq/server/alipay/precreatetrade';
//    //支付订单查询
//    const QUERY_TRADE = 'https://l.test-svrapi.webank.com/api/acq/server/alipay/querytrade';
//    //条码支付
//    const PAY_BARCODE = 'https://l.test-svrapi.webank.com/api/acq/server/alipay/pay';
//    //渠道号
//    const APP_ID = 'W9816632';
//    //密匙
//    const SECRET = '3Bb5UBtEZQCdzrKg9y3FZjjPj7Ik64p8ncGyu07hjgAraqgGHymCYSet4pOCuSVM';
//    //商户号,商户入驻所得
//    const  MERCHANTID = '103584000030004';
    
    //=======【正式】=====================================
    //【证书路径】,注意应该填写绝对路径
    const SSLCERT_PATH = '/alidata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_cert.pem';

    const SSLKEY_PATH = '/alidata/www/youngshop/simplewind/Core/Library/Vendor/Wzpay/cert/apiclient_key.pem';
    //商户入驻
    const REGMCH_RUL = 'https://svrapi.webank.com/api/acq/server/alipay/regmch';
    //获取access_token
    const ACCESS_TOKEN = 'https://svrapi.webank.com/api/oauth2/access_token';
    //获取api_ticket
    const TICKETS = 'https://svrapi.webank.com/api/oauth2/api_ticket';
    //扫码支付
    const PAY_TO = 'https://svrapi.webank.com/api/acq/server/alipay/precreatetrade';
    //退款
    const RE_FUND ='https://svrapi.webank.com/api/acq/server/alipay/refund';

    //支付订单查询
    const QUERY_TRADE = 'https://svrapi.webank.com/api/acq/server/alipay/querytrade';

    //条码支付
    const PAY_BARCODE = 'https://svrapi.webank.com/api/acq/server/alipay/pay';

    //取消订单
    const PAY_CANCEL = 'https://svrapi.webank.com/api/acq/server/alipay/cancel';

    //渠道号
    const APP_ID = 'W9816632';
    //密匙
    const SECRET = 'mDKffFxEXE5n9Np8OWDsU3Hdx33iFqzvUP9UyQY5yWghcUJcE3E4Bc4Qwtfm87lu';
    //商户号,商户入驻所得
    const  MERCHANTID = '103584000030000';

    //版本号
    const VERSION = '1.0.0';
    //类型
    const GRANT_TYPE = 'client_credential';


    public function con($partner)
    {
        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
        $alipay_config['partner'] = $partner;

        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        $alipay_config['seller_id'] = $alipay_config['partner'];

        //商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
        $alipay_config['private_key'] = 'MIICXAIBAAKBgQCt1pFGD7oqDrrv+a/zroP5gcR1EXr5DKVQoYmPdECCsd71x3m6kLE/KN9JwT5E1t0lfq6Atemc4zNPuWraR+wtBx08nozbLnIITjl2WfyyY24HxzeNSboOXr71g4Wa3A+bfMchMnmrDS8I2hN4Ns7+Cv18TyV9luyXt7Lo+6Y3HQIDAQABAoGAMZFQBRkw7RMcLJcWm7Y0PW3KFdNxLBh1/uLAtZ3hUyLiv1QsmoztbWP7Hy2x0rEth6ZynZLBVRHXrLDjDfCaH9f5yp5Dh+PWEgz0AMRMeJjW9dCif0LqY9rwDhHyJikCX4v9YN5p/yj/m+wmGr2JGvcp57UqrHTWU3CxIi/WDSUCQQDl9ypzD7EHKIIEIOTbhwM9/ib1mGg7JR82wNRKKruerIDOsKIkvHDQLtfHVscvSJGApzdLQP25aFxi8FbKNMnnAkEAwYS5tlt5z2uHJvTUneH+JW3shg07+GhDgy1IavwZL5knf2bGyVAMpZk6degJrtLmkhQzBua10fGMg2YFE4++WwJAM6poOxmXaEhNjafmQvv+WnszPZJUOJWKgb6o81DOfkO7XLSKeT5tChi8GekBLzpallD7N0kOuA0eVIwys5NQmQJAUv6kU0Q6Iq4gIaIBCdFhmRXiyb8lSC0XP0wNcey6tII/wVEH0lDli6QCwYyJkpPa1S2akMwjkG3C8Juxc6lDXwJBAKFKGrfBol1WbhFye2XIV5CGkrGqJfP4J0MEzUrPjwzXjJ6kkjaYWrESiAGuXeh606EGyQdpz6hmvoyJ512mKxI=';


        //支付宝的公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
        $alipay_config['alipay_public_key'] = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCt1pFGD7oqDrrv+a/zroP5gcR1EXr5DKVQoYmPdECCsd71x3m6kLE/KN9JwT5E1t0lfq6Atemc4zNPuWraR+wtBx08nozbLnIITjl2WfyyY24HxzeNSboOXr71g4Wa3A+bfMchMnmrDS8I2hN4Ns7+Cv18TyV9luyXt7Lo+6Y3HQIDAQAB';

        // 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $alipay_config['notify_url'] = "http://www.ypt5566.com/index.php?s=/Common/qrcode_alipay_notify";

        // 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        $alipay_config['return_url'] = "http://www.ypt5566.com/index.php?s=/Common/qrcode_alipay_return/partenr/" . $partner;

        //签名方式
        $alipay_config['sign_type'] = strtoupper('RSA');

        //字符编码格式 目前支持utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = getcwd() . '\\cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';

        // 支付类型 ，无需修改
        $alipay_config['payment_type'] = "1";

        // 产品类型，无需修改
        $alipay_config['service'] = "alipay.wap.create.direct.pay.by.user";


        //↑↑↑↑↑↑↑↑↑↑请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //微众支付——支付宝配置【用于商户入住】

        $alipay_config['wz'] = array(

            'productType' => '003',//1支付类型*

            'registerType' => '01',//2普通模式商户有代理商填写“01”商户无代理商(商户直连模式)填写“02”*

            'merchantInfo' => array(

                //'agencyId' => '1070755003',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//
                'agencyId' => '1035840005',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//1035840005

                'appId' => \AlipayConfig::APP_ID,//3.2渠道号，微众银行提供*

                'idType' => '01',//3.3商户法人的证件类型（如：身份证，军人军官证)，*

                'idNo' => '142431197406040055',//3.4商户法人证件号码*

                'merchantName' => '深圳前海洋仆淘电子商务有限公司',//3.5商户名称*

                'legalRepresent' => '郭卫栋',//3.6法人代表*

                'licenceNo' => '91440300360065211Y',//3.7营业执照编号*

                'licenceBeginTime' => '',//3.8执照开始时间，格式“2012-12-12”

                'licenceEndTime' => '',//3.9执照结束时间，格式“2015-12-12”

                'taxRegisterNo' => '91440300360065211Y',//3.10税务登记号*

                'positionCode' => '0',//3.11单位代码，如果没有填“0”*

                'contactName' => '蒋莉芬',//3.12联系人姓名*

                'contactPhoneNo' => '13912341234',//3.13联系人电话， 格 式“13912341234”*

                'mainBusiness' => '电子商务',//3.14主营业务*

                'businessRange' => '在网上从事商贸活动；母婴用品、化妆品、玩具、文具用品、日用品、成人用品、健身器材、体育用品、珠宝首饰、工艺礼品、电脑及配件、家用电器、服装、鞋帽、针纺织品、箱包、厨房和卫生间用具、包装材料的销售；国内贸易、经营进出口业务；从事广告业务（以上根据法律、行政法规、国务院决定等规定需要审批的，依法取得相关审批文件后方可经营）。^婴儿辅食、乳粉、乳制品（含婴幼儿配方奶粉）、保健食品、预包装食品的销售。',//3.15经营范围*

                'registerAddr' => '深圳市前海深港合作区前湾一路1号A栋201室（入驻深圳市前海商务秘书有限公司）',//3.16注册地址*

                'merchantTypeCode' => '0003',//3.17添加商户类别码（经营类目，填类目号，根据类目标填写），*

                'merchantLevel' => '1',//3.18默认填 1*

                'parentMerchantId' => '',//3.19可不填

                'merchantNature' => '私营企业',//3.20商户性质（国有企业，三资企业，私营企业，集体企业)*

                'contractNo' => '',//3.21合同编号

                'openYear' => '',//3.22商户开业时间，格式“2012-12-12”

                'categoryId' => '0003',//3.23类目（支付宝，见数据字典）*

            ),
            'merchantAccount' => array(

                'accountNo' => '755929903810201',//4.1商户银行账号*

                'accountOpbankNo' => '308584000013',//4.2账户开户行号*

                'accountName' => '深圳前海洋仆淘电子商务有限公司',//4.3开户户名*

                'accountOpbank' => '招商银行',//4.4开户行*

                'accountSubbranchOpbank' => '',//4.5开户支行

                'accountOpbankAddr' => '',//4.6开户地址

                'acctType' => '01',//4.7账户类型（01 对公，02 对私）*

                'settlementCycle' => '1',//4.8清算周期（默认填为 1）
            ),
            'merchantRateList' => array(

                array(
                    'paymentType' => '20',//5.1支付类型不允许重复填写*

                    'settlementType' => '01',//5.2结算方式（默认 01）*

                    'chargeType' => '02',//5.3计费算法:01 固定金额、02 固定费率（默认填写 02）*

                    'commissionRate' => 0.8,//5.4回拥费率（chargeType 为 02时必填）（0.6%代表千分之六）

//                    'commissionAmount' => '',//5.5回佣金额（chargeType 为 01时必填）
//
//                    'minAmount' => '',//5.6保底费用（默认不填）
//
//                    'maxAmount' => '',//5.7封顶费用（默认不填）
//
//                    'attachAmount' => '',//5.8附加金额（默认不填）
//
//                    'attachRate' => '',//5.9附加费率（默认不填）
//
//                    'marginDeposit' => '',//5.10保证金（默认不填）
//
//                    'projectImplCost' => '',//5.11项目实施费（默认不填）
//
//                    'sysUseCharge' => '',//5.12系统使用服务年费（默认不 填）
                ),
            ),
            'aliasName' => '洋仆淘跨境商城',//6商户简称（ registerType 为“02”时必填）

            'servicePhone' => '075566607274',//7客服电话（ registerType 为“02”时必填）

            'contactPhone' => '075566607274',//8联系人座机（registerType 为“02”时选填）

            'contactEmail' => '',//9联系人邮箱（registerType 为“02”时选填）

            'memo' => '',//10备注信息（ registerType 为“02”时选填）

            'externalInfo' => '',//11扩展信息（ registerType 为“02”时选填）

            'acquirerId' => '',//12微众在支付宝的商户号，由微众提供（registerType 为“02”时选填）

            'district' => '5840',//13地区号，请参考数据字典（如深圳：0755）*
        );

        return $alipay_config;
    }

}
