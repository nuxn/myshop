<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * 获取微众分配的商户ID用于支付
 * Class UpwzadminController
 * @package Merchants\Controller
 */
class UpwzadminController extends AdminbaseController
{
    protected $shopcates;
    protected $merchants;
    protected $merchants_upwz;
    protected $merchants_users;

    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users = M("merchants_users");
        $this->merchants_upwz = M("merchants_upwz");
    }


    /**
     * 进件列表
     */
    public function index()
    {
        $user_phone = trim(I('user_phone'));
        $cate_id = trim(I('cate_id'));
        $merchantAlis = trim(I('merchantAlis'));
        if ($user_phone) {
            $map['user_phone'] = $user_phone;
        }
        if ($cate_id) {
            $map['cate_id'] = $cate_id;
        }
        if ($merchantAlis) {
            $map['merchantAlis'] = array('LIKE', "%$merchantAlis%");
        }
        $map['brash'] = 1;

        $upwzs = $this->merchants_upwz->alias('s')
            ->join("left join __MERCHANTS__ m on s.mid = m.id")
            ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
            ->field("s.*,u.user_phone")
            ->where($map)
            ->order("id desc")
            ->select();
        $count = count($upwzs);
        $page = $this->page($count, 20);
        $list = array_slice($upwzs, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("upwzs", $list);
        $this->display();
    }

//  微信进件信息查询
    public function wx_jinjian()
    {
        vendor("Wzpay.Wzpay");
        $upwz = new \Wzpay();
        //$xml="<xml><merchantName><![CDATA[新时代健康产业集团]]></merchantName><merchantAlis><![CDATA[国珍]]></merchantAlis><merchantArea>1210</merchantArea><bankName><![CDATA[工商银行]]></bankName><revactBankNo>102100099996</revactBankNo><bankAccoutName><![CDATA[袁亚平]]></bankAccoutName><bankAccout>6222080402004324740</bankAccout><agency>1075840014</agency><servicePhone>13785149772</servicePhone><business>0294</business><merchantNature><![CDATA[私营企业]]></merchantNature><wxCostRate>0.38</wxCostRate><companyFlag>00</companyFlag><nonce_str><![CDATA[urdarktx1wbiipi5lfq3mzgw878exjs3]]></nonce_str><sign><![CDATA[83FEFB311455A708B21EE0BC3C043C24]]></sign></xml>";
        $data = array(
            "merchantName" => "新时代健康产业集团",
            "merchantAlis" => "国珍",
            "merchantArea" => "1210",
            "bankName" => "工商银行",
            "revactBankNo" => "102100099996",
            "bankAccoutName" => "袁亚平",
            "bankAccout" => "6222080402004324740",
            "agency" => "1075840014",
            "servicePhone" => "13785149772",
            "business" => "0294",
            "merchantNature" => "私营企业",
            "companyFlag" => "00",
            "wxCostRate" => "0.38",
        );
        $result = $upwz->apply($data);
        var_dump($result);
    }

//    wx进件信息查询
    public function wx_message()
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('Wzpay.Wzcommon');
        vendor("WzPay.pub.config.php");
        $wzPay = new \Wzcommon();
        $url = "https://svrapi.webank.com/wbap-bbfront/SelectMrch";
        $mch_id = "107584000030001";
        $agency = "1075840001";
        $serialNo = "F2017061610425101000000000264646";
        $wzPay->setParameter('mchi_id', $mch_id);
        $wzPay->setParameter('agency', $agency);
        $wzPay->setParameter('serialNo', $serialNo);
        $returnData = $wzPay->getParameters($url, $mch_id);
        var_dump($returnData);
    }

    /**
     * 添加进件
     */
    public function add()
    {
        header("content-type:text/html;charset=utf-8");
        if ($_POST) {
            $select = I("");
            $mid = $select['mid'];
            if (!$this->merchants->where("id=$mid")->find()) $this->error("商户id不存在");
            if (!$select['merchantName']) $this->error("未填写商户名称");
            if (!$select['merchantAlis']) $this->error("未填写商户简称");
            if (!$select['idNo']) $this->error("未填写商户法人证件号码");
            if (!$select['legalRepresent']) $this->error("未填写法人代表");
            if (!$select['licenceNo']) $this->error("未填写营业执照编号");
            if (!$select['categoryId']) $this->error("未填写支付宝经营类目");
            if (!$select['merchantTypeCode']) $this->error("未填写支付宝商户类别码");
            if (!$select['contactName']) $this->error("未填写联系人姓名");
            if (!$select['contactPhoneNo']) $this->error("未填写联系人电话");
            if (!$select['mainBusiness']) $this->error("未填写主营业务");
            if (!$select['businessRange']) $this->error("未填写经营范围");
            if (mb_strlen($select['businessRange']) > 700) $this->error("经营范围最多700个字节");
            if (!$select['registerAddr']) $this->error("未填写注册地址");
            if (!$select['contactPhone']) $this->error("未填写联系人座机");
            if (!$select['merchantArea']) $this->error("未填写地区码");
            if (!$select['bankName']) $this->error("未填写开户行");
            if (!$select['revactBankNo']) $this->error("未填写开户行号");
            if (!$select['bankAccoutName']) $this->error("未填写户名");
            if (!$select['bankAccout']) $this->error("未填写银行号");
            if (!$select['servicePhone']) $this->error("未填写客服电话");
            if (!$select['business']) $this->error("未填写经营类目");
            if (!$select['merchantNature']) $this->error("未填写商户性质");
            if (!$select['wxCostRate']) $this->error("未填写商户扣率");
            if (!$select['companyFlag']) $this->error("未填写账号性质");
            if($select['submit']=='保存'){
               $re=$this->merchants_upwz->where(array("mid" => $select['mid']))->getField("id");
               unset($select['submit']);
               if($re){
                    
                    $this->merchants_upwz->where(array('mid'=>$select['mid']))->save($select);
                    $this->success("保存成功");
               }else{
                    $this->merchants_upwz->add($select);
                    $this->success("保存成功");
               }
            }else if($select['submit']=='进件'){
                unset($select['submit']);
                $select['agency'] = "1075840014";
                $select['serialNo'] = "Wzgo" . time() . rand(1000, 9999);
                vendor("Wzpay.Wzpay");
                $upwz = new \Wzpay();
                if ($this->merchants_upwz->where(array("mid" => $select['mid']))->getField("id")){
                    $rs = false;
        //            微信
                    $result = $upwz->apply($select);
                    $path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/wz/jinjian/';
                    file_put_contents(get_date_dir($path) . date("Y_m_d_") . 'jinjian.log', date("Y-m-d H:i:s") . '   ' . "支付宝进件结果" . json_encode($rs) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    file_put_contents(get_date_dir($path) . date("Y_m_d_") . 'jinjian.log', date("Y-m-d H:i:s") . '   ' . "微信进件结果" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);

                    if ($result['status'] == '0') {
                        $select['wx_mchid'] = $result['mch_id'];
                        $select['ali_mchid'] = $rs['merchantId'] ? $rs['merchantId'] : '';
                        $select['status'] = 1;
                        $select['time_start'] = time();
                        $select['update_time'] = time();
                        $this->merchants_upwz->where(array('mid'=>$select['mid']))->save($select);
                        file_put_contents(get_date_dir($path) . date("Y_m_d_") . 'jinjian.log', date("Y-m-d H:i:s") . '   ' . "商户入驻成功" . json_encode($select) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        $this->success("商户入驻微信成功", U("index"));
                    } else {
                        file_put_contents(get_date_dir($path) . date("Y_m_d_") . 'jinjian.log', date("Y-m-d H:i:s") . '   ' . "商户入驻失败" . json_encode($select) . PHP_EOL, FILE_APPEND | LOCK_EX);
                        $this->error("商户入驻失败,请与工作人员联系");
                    }
                }else{
                    $this->error('资料未保存，先保存在进件');
                }
    //           支付宝       
                //$rs = $this->regmch($select);
               
            }   

           
        } else {

            $data=M('merchants_upwz')->where(array('mid'=>$_GET['id']))->find();
            $this->assign('id',$_GET['id']);
            $this->assign('data',$data);
           $this->display('add1');


        }
    }


    public function blind_cate()
    {
        $id = I("id");
        $cate_id = I("cate_id",'0','intval')||$this->error("台签ID不能为空!");
        if ($this->shopcates->where(array("id" => $cate_id, 'status' => 1))->find()) $this->error("该台签已投入使用");
        $upwz = $this->merchants_upwz->where("id=" . $id)->find();
        if ($upwz) $this->merchants_upwz->where("id=" . $id)->save(array("cate_id" => $cate_id));
        $data = array(
            'merchant_id' => $upwz['mid'],
            'jianchen' => $upwz['merchantalis'],
            'name' => $upwz['merchantname'],
            'wx_name' => $upwz['merchantname'],
            'wx_key' => "youngPort4a21",
            'wx_mchid' => $upwz['wx_mchid'],
            'alipay_partner' => $upwz['ali_mchid']
        );
        $this->shopcates->where("id=" . $cate_id)->save($data);
        $this->success("绑定成功", U("index"));
    }

    public function test1()
    {
//        $list = M()->query('SELECT * FROM ypt_merchants_upwz WHERE id BETWEEN 245 and 250;');
//        $aa = $list[5];
        $id = 290;
        $rs = $this->merchants_upwz->where("id=" . $id)->find();
        if ($rs['companyFlag'] == '00') $rs['companyFlag'] = '02';
        echo '<pre/>';
        print_r($rs);
        $re = $this->regmch($rs);
        print_r($re);
    }

    private function regmch($res)
    {
        Vendor('QRcodeAlipay.Wz_pay');
        $wzPay = new \Wz_pay();

        if ($res['companyFlag'] == '00') $res['companyFlag'] = '02';
        $parameter = array(

            'productType' => '003',//1支付类型*

            'registerType' => '01',//2普通模式商户有代理商填写“01”商户无代理商(商户直连模式)填写“02”*

            'merchantInfo' => array(

                //'agencyId' => '1070755003',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//1035840014
                'agencyId' => '1035840014',//3.1代理商编号，微众银行提供。（商户直连模式不用填写）*//1035840005

                'appId' => \AlipayConfig::APP_ID,//3.2渠道号，微众银行提供*

                'idType' => '01',//3.3商户法人的证件类型（如：身份证，军人军官证)，*

                'idNo' => $res['idNo'] ? $res['idNo'] : '142431197406040055',//3.4商户法人证件号码*

                'merchantName' => $res['merchantName'] ? $res['merchantName'] : '深圳前海洋仆淘电子商务有限公司',//3.5商户名称*

                'legalRepresent' => $res['legalRepresent'] ? $res['legalRepresent'] : '郭卫栋',//3.6法人代表*

                'licenceNo' => $res['licenceNo'] ? $res['licenceNo'] : '91440300360065211Y',//3.7营业执照编号*

                'licenceBeginTime' => '',//3.8执照开始时间，格式“2012-12-12”

                'licenceEndTime' => '',//3.9执照结束时间，格式“2015-12-12”

//                'taxRegisterNo' => $res['taxRegisterNo'] ? $res['taxRegisterNo'] : '91440300360065211Y',//3.10税务登记号*
                'taxRegisterNo' => $res['taxRegisterNo'] ? $res['taxRegisterNo'] : '',//3.10税务登记号*

                'positionCode' => '0',//3.11单位代码，如果没有填“0”*

                'contactName' => $res['contactName'] ? $res['contactName'] : '蒋莉芬',//3.12联系人姓名*

                'contactPhoneNo' => $res['contactPhoneNo'] ? $res['contactPhoneNo'] : '13912341234',//3.13联系人电话， 格 式“13912341234”*

                'mainBusiness' => $res['mainBusiness'] ? $res['mainBusiness'] : '电子商务',//3.14主营业务*

                'businessRange' => $res['businessRange'] ? $res['businessRange'] : '在网上从事商贸活动；母婴用品、化妆品、玩具、文具用品、日用品、成人用品、健身器材、体育用品、珠宝首饰、工艺礼品、电脑及配件、家用电器、服装、鞋帽、针纺织品、箱包、厨房和卫生间用具、包装材料的销售；国内贸易、经营进出口业务；从事广告业务（以上根据法律、行政法规、国务院决定等规定需要审批的，依法取得相关审批文件后方可经营）。^婴儿辅食、乳粉、乳制品（含婴幼儿配方奶粉）、保健食品、预包装食品的销售。',//3.15经营范围*

                'registerAddr' => $res['registerAddr'] ? $res['registerAddr'] : '深圳市前海深港合作区前湾一路1号A栋201室（入驻深圳市前海商务秘书有限公司）',//3.16注册地址*

                'merchantTypeCode' => $res['merchantTypeCode'] ? $res['merchantTypeCode'] : '0003',//3.17添加商户类别码（经营类目，填类目号，根据类目标填写），*

                'merchantLevel' => '2',//3.18默认填 1*

                'parentMerchantId' => '',//3.19可不填

                'merchantNature' => $res['merchantNature'] ? $res['merchantNature'] : '私营企业',//3.20商户性质（国有企业，三资企业，私营企业，集体企业)*

                'contractNo' => '',//3.21合同编号

                'openYear' => '',//3.22商户开业时间，格式“2012-12-12”

                'categoryId' => $res['categoryId'] ? $res['categoryId'] : '0003',//3.23类目（支付宝，见数据字典）*

            ),
            'merchantAccount' => array(

                'accountNo' => $res['bankAccout'] ? $res['bankAccout'] : '755929903810201',//4.1商户银行账号*

                'accountOpbankNo' => $res['revactBankNo'] ? $res['revactBankNo'] : '308584000013',//4.2账户开户行号*

                'accountName' => $res['bankAccoutName'] ? $res['bankAccoutName'] : '深圳前海洋仆淘电子商务有限公司',//4.3开户户名*

                'accountOpbank' => $res['bankName'] ? $res['bankName'] : '招商银行',//4.4开户行*

                'accountSubbranchOpbank' => '',//4.5开户支行

                'accountOpbankAddr' => '',//4.6开户地址

                'acctType' => $res['companyFlag'] ? $res['companyFlag'] : '01',//4.7账户类型（01 对公，02 对私）*

                'settlementCycle' => '1',//4.8清算周期（默认填为 1）
            ),
            'merchantRateList' => array(

                array(
                    'paymentType' => '20',//5.1支付类型不允许重复填写*

                    'settlementType' => '11',//5.2结算方式（默认 01）*

                    'chargeType' => '02',//5.3计费算法:01 固定金额、02 固定费率（默认填写 02）*

                    'commissionRate' => $res['wxCostRate'] ? $res['wxCostRate'] : 0.8,//5.4回拥费率（chargeType 为 02时必填）（0.6%代表千分之六）

                ),
                array(
                    'paymentType' => '21',//5.1支付类型不允许重复填写*

                    'settlementType' => '11',//5.2结算方式（默认 01）*

                    'chargeType' => '02',//5.3计费算法:01 固定金额、02 固定费率（默认填写 02）*

                    'commissionRate' => $res['wxCostRate'] ? $res['wxCostRate'] : 0.8,//5.4回拥费率（chargeType 为 02时必填）（0.6%代表千分之六）

                ),
            ),
            'aliasName' => $res['merchantAlis'] ? $res['merchantAlis'] : '洋仆淘跨境商城',//6商户简称（ registerType 为“02”时必填）

            'servicePhone' => $res['servicePhone'] ? $res['servicePhone'] : '075566607274',//7客服电话（ registerType 为“02”时必填）

            'contactPhone' => $res['contactPhone'] ? $res['contactPhone'] : '075566607274',//8联系人座机（registerType 为“02”时选填）

            'district' => $res['merchantArea'] ? $res['merchantArea'] : '5840',//13地区号，请参考数据字典（如深圳：0755）*
        );
        $rs = $wzPay->get_merchantId($parameter);
        return json_decode($rs, true);
    }

    public function get_number()
    {
        return array(
            array("number" => "102100099996", "name" => "中国工商银行"),
            array("number" => "103100000026", "name" => "中国农业银行股份有限公司"),
            array("number" => "104100000004", "name" => "中国银行总行"),
            array("number" => "105100000017", "name" => "中国建设银行股份有限公司总行"),
            array("number" => "301290000007", "name" => "交通银行"),
            array("number" => "302100011000", "name" => "中信银行股份有限公司"),
            array("number" => "303100000006", "name" => "中国光大银行"),
            array("number" => "304100040000", "name" => "华夏银行股份有限公司总行"),
            array("number" => "305100000013", "name" => "中国民生银行"),
            array("number" => "306581000003", "name" => "广发银行股份有限公司"),
            array("number" => "313584099990", "name" => "平安银行"),
            array("number" => "308584000013", "name" => "招商银行股份有限公司"),
            array("number" => "309391000011", "name" => "兴业银行总行"),
            array("number" => "310290000013", "name" => "上海浦东发展银行"),
            array("number" => "403100000004", "name" => "邮储银行"),
            array("number" => "323584000888", "name" => "深圳前海微众银行股份有限公司"),
        );
    }


    //    修改修改台签的名字
    public function change_cate()
    {
        $id = I('id');
        if ($id < 1) $this->error("台签ID不能为空!");
        $new_name = I("new_cate");
        $old_name = $this->merchants_upwz->where("id=$id")->getField("cate_id");
        if ($old_name != $new_name) {
            $data['cate_id'] = $new_name;
            $this->merchants_upwz->where("id=$id")->save($data);
        }
    }
}
