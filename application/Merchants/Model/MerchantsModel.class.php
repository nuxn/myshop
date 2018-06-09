<?php
namespace Merchants\Model;

use Common\Model\CommonModel;

class MerchantsModel extends CommonModel
{

    /**
     * 自动验证规则
     */
    protected $_validate = array(
        array('merchant_name', 'require', '商户名称不能为空!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('province', 'require', '省市区不能为空!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('address', 'require', '具体地址不能为空!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('industry', 'require', '行业不能为空!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('operator_name', 'require', '经营者不能为空!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('id_number', 'checkIdCard', '身份证号码不正确', self::EXISTS_VALIDATE, 'callback', self::MODEL_BOTH),
        array('account_name', 'require', '请输入开户名称!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('bank_account', 'require', '请输入开户银行!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('branch_account', 'require', '请输入开户支行!', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('bank_account_no', 'checkBankAccount', '银行卡号不正确!', self::EXISTS_VALIDATE, 'callback', self::MODEL_BOTH),
        array('positive_id_card_img', 'require', '请上传身份证照片', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('header_interior_img', 'require', '请上传商户证件', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('business_license', 'require', '请上传商户证件', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        array('referrer', '/^1[34578]\d{9}$/', '请输入有效的推荐人手机号码', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
        array('referrer', 'checkReferrer', '推荐人不能填写自己!', self::VALUE_VALIDATE, 'callback', self::MODEL_BOTH),
    );


    // 插入数据前的回调方法
    protected function _before_insert(&$data, $options)
    {
        $area = explode(',', $data['province']);
        $idcard = explode(',', $data['positive_id_card_img']);
        $data['account_type'] = $data['account_type'] == '个人账户' ? 0 : 1;
        $data['id_number'] = encrypt($data['id_number']);
        $data['bank_account_no'] = encrypt($data['bank_account_no']);
        $data['status'] = 0;
        $data['add_time'] = time();
        //身份证正面
        $data['positive_id_card_img'] = $idcard[0];
        //身份证反面
        $data['id_card_img'] = $idcard[1];
        //直辖市
        if (count($area) == 2) {
            $data['province'] = $area[0];
            $data['city'] = $area[1];
        } else {
            $data['province'] = $area[0];
            $data['city'] = $area[1];
            $data['county'] = $area[2];
        }
    }

    // 插入成功后的回调方法
    protected function _after_insert($data, $options)
    {
        //删除session里面的数据
        session('positive_id_card_img',null);
        session('header_interior_img',null);
        session('business_license',null);
        session('isdoor_header',null);
    }


    // 计算身份证校验码，根据国家标准GB 11643-1999
    private function getVerifyBit($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;

        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];

        return $verify_number;

    }

    /**
     * 身份证验证
     * @param $idcard
     * @return bool
     */
    protected function checkIdCard($idcard)
    {

        $City = array(11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古", 21 => "辽 宁", 22 => "吉林", 23 => "黑龙江", 31 => "上海", 32 => "江苏", 33 => "浙江", 34 => " 安徽", 35 => "福建", 36 => "江西", 37 => "山东", 41 => "河南", 42 => "湖北", 43 => " 湖南", 44 => "广东", 45 => "广西", 46 => "海南", 50 => "重庆", 51 => "四川", 52 => " 贵州", 53 => "云南", 54 => "西藏", 61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => " 宁夏", 65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外");

        $iSum = 0;
        $idCardLength = strlen($idcard);
        //长度验证
        if (!preg_match('/^\d{17}(\d|x)$/i', $idcard) and !preg_match('/^\d{15}$/i', $idcard)) {
            return false;
        }

        //地区验证
        if (!array_key_exists(intval(substr($idcard, 0, 2)), $City)) {
            return false;
        }

        // 15位身份证验证生日，转换为18位
        if ($idCardLength == 15) {
            $sBirthday = '19' . substr($idcard, 6, 2) . '-' . substr($idcard, 8, 2) . '-' . substr($idcard, 10, 2);
            $d = new \DateTime($sBirthday);
            $dd = $d->format('Y-m-d');
            if ($sBirthday != $dd) {
                return false;
            }

            $idcard = substr($idcard, 0, 6) . "19" . substr($idcard, 6, 9);//15to18
            $Bit18 = $this->getVerifyBit($idcard);//算出第18位校验码
            $idcard = $idcard . $Bit18;
        }

        // 判断是否大于2078年，小于1900年
        $year = substr($idcard, 6, 4);
        if ($year < 1900 || $year > 2078) {
            return false;
        }


        //18位身份证处理
        $sBirthday = substr($idcard, 6, 4) . '-' . substr($idcard, 10, 2) . '-' . substr($idcard, 12, 2);
        $d = new \DateTime($sBirthday);
        $dd = $d->format('Y-m-d');
        if ($sBirthday != $dd) {
            return false;
        }

        //身份证编码规范验证
        $idcard_base = substr($idcard, 0, 17);
        if (strtoupper(substr($idcard, 17, 1)) != $this->getVerifyBit($idcard_base)) {
            return false;
        }

        return true;

    }

    /**
     * 验证银行卡
     */
    protected function checkBankAccount($bank_no)
    {
        $arr_no = str_split($bank_no);
        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $x = 10 - ($total % 10);
        if ($x == $last_n) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $referrer
     * @return bool
     * 检查用户和推荐人是否一样
     */
    protected function checkReferrer($referrer){
        $data= $this->where(array('id'=>session('uid')))->find();
        if($data['user_phone']== $referrer){
            return false;
        }else{
            return true;
        }
    }

    public function addDefaultRole($mch_uid)
    {
        $data['app_auth'] = 'Api/Pay/auth_taiqian;Api/Pay/get_card;Api/Pay/pay_back;Api/Agentnews/coin;Api/Agentnews/coin_detail;Api/Agentnews/customer;Api/Agentnews/customer_detail;Api/Agentnews/merchant_detail;Api/Shopnews/coin;Api/Shopnews/coin_detail;Api/Member/index;Api/Pay/auth_bill_single;Api/bill/index;Api/bill/detail;Api/dyj/dyj;Api/index/data;Api/member/card_off';
        $data['role_name'] = '默认角色';
        $data['role_desc'] = '初始默认角色';
        $data['add_time'] = time();
        $data['pid'] = 3;
        $data['mu_id'] = $mch_uid;
        M('merchants_role')->add($data);
    }
}
