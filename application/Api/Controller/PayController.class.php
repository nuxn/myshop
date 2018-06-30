<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Common\Lib\Subtable;

/**支付接口
 * 扫码支付、条码支付、刷卡支付
 * Class PayController
 * @package Api\Controller
 */
class  PayController extends ApibaseController
{
    public $uid;
    const brand = 'YPT';
    protected $pays;
    protected $merchants;
    protected $cates;
    protected $payBack;

    public function __construct()
    {
        parent::__construct();
        $this->checkLogin();
        $this->pays = M(Subtable::getSubTableName('pay'));
        $this->merchants = M("merchants");
        $this->cates = M("merchants_cate");
        $this->payBack = M("pay_back");
        $this->uid = $this->userId;
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/';
    }

    //扫码收款【被扫】  
    public function get_card()
    {
        vendor("phpqrcode.phpqrcode");
        $checker_id = I("checker", '0');
        $price = I("price");
        $jmt_remark = trim(I("jmt_remark"));
        if ($price == 0) $this->ajaxReturn(array("code" => "error", "msg" => "价格不能为空!"));
        if ($checker_id == $this->uid) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->uid;
        }

//        $role_id= M("merchants_role_users")->where("uid=$this->uid")->getField('role_id');
//        if($role_id == 3){
//            $u_id= $this->uid;
//        }else{
//            $u_id=M("merchants_role_users")->where("id=$this->uid")->getField("pid");
//        }

        $merchant_id = $this->merchants->where("uid=$u_id")->getField("id");
        $cate_id = M("merchants_cate")->where(array("merchant_id" => $merchant_id, 'status' => 1))->getField("id");
        if (!$cate_id) {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未绑定台签"));
        }
        $no_number = $this->create_no_number($cate_id);//每张二维码唯一标识
        $value = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id . "&price=" . $price . "&checker_id=" . $checker_id;
        if ($jmt_remark) {
            $value = $value . "&jmt_remark=" . $jmt_remark;
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $value));
    }


    /**生成no_number
     * @param $cate_id
     * @return string
     */
    private function create_no_number($cate_id)
    {
        $no_number = $this->pays->where(array("cate_id" => $cate_id))->order("id desc")->getField('no_number');
        $no_number = substr($no_number, -7) + 1;
        $seven = "000000" . $no_number;
        $cate_name = 'SJ';
        $no_number = self::brand . $cate_name . substr($seven, -7);
        return $no_number;
    }

    //扫码支付查询
    public function find_pay()
    {
        $time_start = I("time");
        $checker_id = I("checker");

        if ($checker_id == $this->uid) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->uid;
        }
        $merchant_id = $this->merchants->where("uid=$u_id")->getField("id");
        $time_end = I("time") - 30;
        $where['merchant_id'] = $merchant_id;
        $where['checker_id'] = $checker_id;
//        $where['mode'] = 1;
        $where['status'] = 0;
        $where['paytime'] = array('between', array($time_end, time()));
        $pay_none = $this->pays->where($where)->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
        if ($pay_none) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay_none));
        } else {
            $where['status'] = 1;
            $pay_now = $this->pays->where($where)->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
            if ($pay_now) {
                if (!$pay_now["jmt_remark"]) $pay_now["jmt_remark"] = "";
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay_now));
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("price" => "", "paystyle_id" => "", "remark" => "", "mode" => "", "paytime" => "", "status" => "")));
    }

    /**
     * @auth LXL
     * 1.3.4新版APP扫码订单查询
     */
    public function find_pay_1()
    {
        $remark = I("order_sn");

        $where['remark'] = $remark;
        $where['status'] = 1;
        $pay_now = $this->pays->where($where)->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
        if ($pay_now) {
            A("Api/Cloud")->printer($remark);
            if (!$pay_now["jmt_remark"]) $pay_now["jmt_remark"] = "";
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay_now));
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => (object)null));
    }


    //刷卡收款【主扫】【app】
    public function barcode_pay()
    {
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/','barcode_pay','参数',json_encode($_REQUEST));
        $price = trim(I("price"));
        $code = trim(I("code"));
        $checker_id = trim(I("checker", '0'));
        $number = substr($code, 0, 2);
        $jmt_remark = trim(I("jmt_remark"));
        $order_sn = trim(I("order_sn")); //pos刷卡支付
        $mode = I("mode", 2);
        #检查该笔订单使用的储值、积分是否充足，是否有优惠券
        $order_info = M('order')->where(array('order_sn' => $order_sn))->field('card_code,user_money,integral,coupon_code')->find();
        $this->check_preferential($order_info['card_code'], $order_info["user_money"], $order_info['integral'], $order_info['coupon_code']);
        if ($checker_id == $this->uid) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->uid;
        }
        $id = $this->merchants->where("uid=$u_id")->getField("id");
        $res = $this->cates->field('status,wx_bank,ali_bank,is_ypt')->where(array("merchant_id" => $id, 'status' => 1))->find();
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/','barcode_pay','res',json_encode($res));
        // dump($number);dump($res['ali_bank']);
        if ($res['status'] == 0) $this->ajaxReturn(array("code" => "error", "msg" => "失败1"));
        if ($number == "10" || $number == "11" || $number == "12" || $number == "13" || $number == "14" || $number == "15" && strlen($code) == 18) {//微信支付
            #洋仆淘预收款，1.9版本
            /*if($res['is_ypt'] == 1) {
                $message = A("Pay/Barcodexybank")->ypt_wz_micropay($id, $price, $code, $checker_id,$jmt_remark,$order_sn,$mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if(!$pay["jmt_remark"])$pay["jmt_remark"]="";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }*/
            if ($res['wx_bank'] == "1") {
                $message = A("Pay/Barcode")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            if ($res['wx_bank'] == "2") {
                $message = A("Pay/Barcodembank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }

            }
            // 微信支付
            if ($res['wx_bank'] == "3") {
                $message = A("Pay/Wxpay")->micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //招商支付
            if ($res['wx_bank'] == "4") {
                $message = A("Pay/Barcodezsbank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 钱方支付
            if ($res['wx_bank'] == "5") {
                $message = A("Pay/QianFangPay")->micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //济南民生支付
            if ($res['wx_bank'] == "6") {
                $message = A("Pay/Barcodemsday1")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //兴业银行
            if ($res['wx_bank'] == "7") {
                $message = A("Pay/Barcodexybank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 微信支付
            if ($res['wx_bank'] == "9") {
                $message = A("Pay/Szlzpay")->micropay($id, $price, $code, $checker_id, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //浦发银行
            if ($res['wx_bank'] == "10") {
                $message = A("Pay/Barcodepfbank")->wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay) {
                        $pay = array();
                    }
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //新大陆
            if ($res['wx_bank'] == "11") {
                $message = A("Pay/Barcodexdlbank")->wx_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'api-pay', ':日志', json_encode($message));
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "error"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay) {
                        if (isset($message['data'])) {
                            $pay = $this->pays->where("id=" . $message['data'])->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                        }
                    }
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'api-pay', '数据', json_encode($pay));
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 乐刷支付
            if ($res['wx_bank'] == "12") {
                $message = A("Pay/Leshuabank")->wx_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/', 'api-pay', ':日志', json_encode($message));
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay) {
                        $pay = array();
                    }
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 随行付支付通道
            if ($res['wx_bank'] == "14") {
                $message = A("Pay/Banksxf")->wx_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay) {
                        $pay = array();
                    }
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
        } else if ($number == '28') {//支付宝支付
            #洋仆淘预收款，1.9版本
            /*if($res['is_ypt'] == 1) {
                $message = A("Pay/Barcodexybank")->ypt_ali_barcode_pay($id, $price, $code, $checker_id,$jmt_remark,$order_sn,$mode);
                if ($message['code']=='success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if(!$pay["jmt_remark"])$pay["jmt_remark"]="";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }*/
            if ($res['ali_bank'] == "1") {//微众银行
                $message = A("Pay/Barcode")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['flag']) {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "2") { //民生银行
                $message = A("Pay/Barcodembank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] = "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "4") { //招商银行
                $message = A("Pay/Barcodezsbank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $mode);
                if ($message['flag']) {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "6") { //济南民生
                $message = A("Pay/Barcodemsday1")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == 'success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "7") { //兴业民生
                $message = A("Pay/Barcodexybank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == 'success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
//            // 恒丰久运
//            if($res['ali_bank'] == "8") {
//                $message = A("Pay/Barcodemsday")->ali_barcode_pay($id, $price, $code, $checker_id, 'ali');
//                if ($message['code']=='success') {
//                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
//                    if(!$pay["jmt_remark"])$pay["jmt_remark"]="";
//                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
//                } else
//                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
//            }
            if ($res['ali_bank'] == "9") {
                $message = A("Pay/Szlzpay")->ali_micropay($id, $price, $code, $checker_id, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();

                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            if ($res['ali_bank'] == "10") { //浦发
                $message = A("Pay/Barcodepfbank")->ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == 'success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            //新大陆
            if ($res['ali_bank'] == "11") {
                $message = A("Pay/Barcodexdlbank")->ali_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "error", "msg" => "error"));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }

            //乐刷支付
            if ($res['ali_bank'] == "12") {
                $message = A("Pay/Leshuabank")->ali_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }

            // 随行付支付
            if ($res['ali_bank'] == "14") {
                $message = A("Pay/Leshuabank")->ali_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, $mode);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请扫微信或支付宝支付"));
        }

    }

    //刷卡收款【主扫】【pos】
    public function barcode_pos_pay()
    {
        $price = trim(I("price"));
        $order_sn = trim(I("order_sn"));
        $code = trim(I("code"));
        $checker_id = trim(I("checker", '0'));
        $number = substr($code, 0, 2);
        $jmt_remark = I("jmt_remark") ? trim(I("jmt_remark")) : NULL;

        if ($checker_id == $this->uid) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->uid;
        }
        $id = $this->merchants->where("uid=$u_id")->getField("id");
        $res = $this->cates->field('status,wx_bank,ali_bank')->where(array("merchant_id" => $id, 'status' => 1))->find();
        if ($res['status'] == 0) $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
        if ($number == "11" || $number == "12" || $number == "13" || $number == "14" || $number == "15" && strlen($code) == 18) {//微信支付
            if ($res['wx_bank'] == "1") {
                $message = A("Pay/Barcode")->pos_wz_micropay($id, $price, $code, $checker_id, $order_sn);
                //dump($message);exit;
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败1")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            if ($res['wx_bank'] == "2") {
                $message = A("Pay/Barcodembank")->pos_wz_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败2")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }

            }
            // 微信支付
            if ($res['wx_bank'] == "3") {
                $message = A("Pay/Wxpay")->micropay($id, $price, $code, $checker_id);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败3")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //招商支付
            if ($res['wx_bank'] == "4") {
                $message = A("Pay/Barcodezsbank")->pos_wz_micropay($id, $price, $code, $checker_id, 8, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败4")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            //兴业银行
            if ($res['wx_bank'] == "7") {
                $message = A("Pay/Barcodexybank")->pos_wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 微信支付
            if ($res['wx_bank'] == "9") {
                $message = A("Pay/Szlzpay")->pos_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            if ($res['wx_bank'] == "10") {
                $message = A("Pay/Barcodepfbank")->pos_wz_micropay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 新大陆支付
            if ($res['wx_bank'] == "11") {
                $message = A("Pay/Barcodexdlbank")->pos_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            // 乐刷支付
            if ($res['wx_bank'] == "12") {
                $message = A("Pay/Leshuabank")->pos_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
        } else if ($number == '28') {//支付宝支付

            if ($res['ali_bank'] == "1") {//微众银行
                $message = A("Pay/Barcode")->pos_ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn, 2);
                if ($message['flag']) {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "2") { //民生银行
                $message = A("Pay/Barcodembank")->pos_ali_barcode_pay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] = "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "4") { //招商银行
                $message = A("Pay/Barcodezsbank")->pos_ali_barcode_pay($id, $price, $code, $checker_id, $order_sn);
                if ($message['flag']) {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "7") { //兴业
                $message = A("Pay/Barcodexybank")->pos_ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                if ($message['code'] == 'success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "9") {
                $message = A("Pay/Szlzpay")->pos_ali_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
            if ($res['ali_bank'] == "10") { //东莞中信
                $message = A("Pay/Barcodepfbank")->pos_ali_barcode_pay($id, $price, $code, $checker_id, $jmt_remark, $order_sn);
                if ($message['code'] == 'success') {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                } else
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
            if ($res['ali_bank'] == "11") {
                $message = A("Pay/Barcodexdlbank")->pos_ali_micropay($id, $price, $code, $checker_id, $order_sn);
                if ($message['code'] == "error") {
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
                }
                if ($message['code'] == "success") {
                    $pay = $this->pays->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("id,price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                    if (!$pay["jmt_remark"]) $pay["jmt_remark"] = "";
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "请扫微信或支付宝支付")));
        }
    }

    //    pos机其他支付修改mode
    public function pos_change_mode()
    {
        $remark = I("remark");
        if (!$remark) $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
        //  今天
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $time = array($beginToday, $endToday);
        $pay = $this->pays->where(array("remark" => $remark, 'paytime' => array('between', $time)))->find();
        if (!$pay) {
            $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        }
        $this->pays->where(array("remark" => $remark, 'paytime' => array('between', $time)))->save(array("mode" => 8));
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "修改成功"));
    }

//    银行卡支付成功
    public function pos_bank_pay()
    {
        $remark = I("remark");
        $price = I("price");
        $paystyle_id = I("paystyle_id");

        if ($this->pays->where(array("remark" => $remark))->find()) $this->ajaxReturn(array("code" => "error", "msg" => "订单号已存在"));
        if (!$remark) $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
        $mid = $this->get_merchant($this->uid);
        if ($mid == $this->uid) {
            $checker_id = 0;
        } else {
            $checker_id = $this->uid;
        }

        $merchant_id = $this->merchants->where("uid = $mid")->getField("id");
        if (!$merchant_id) $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));
        $data = array(
            'remark' => $remark,
            'mid' => $merchant_id,
            'checker_id' => $checker_id,
            'paystyle_id' => $paystyle_id,
            'mode' => 8,
            'price' => $price,
            'bank' => 10,
            'status' => 1,
            'paytime' => time(),
            'bill_date' => date("Ymd", time()),
        );
        $this->pays->add($data);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => "添加成功"));
    }

    //退款
    public function pay_back()
    {
        if (I('sign') == '5e022b44a15a90c0') {
            $mid = I('mid');
        } else {
            $muid = $this->get_merchant($this->uid);
            $mid = $this->merchants->where("uid=$muid")->getField("id");
        }
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/', 'pay_back', '退款参数：', json_encode(I('')));

        $style = I("style");
        $remark = I("remark");
        $price_back = I("price_back");

        $pay = $this->pays->where("remark='$remark' And merchant_id= $mid ")->find();
        if (!$pay) $this->ajaxReturn(array("code" => "error", "msg" => "未找到订单"));
        #储值订单退款
        if ($pay['mode'] == 12) {
            #该笔订单充值到会员卡实际到账的金额
            $recharge_info = M('user_recharge')->where(array('order_sn' => $remark))->field('memcard_id,total_price')->find();
            $total_yue = $recharge_info['total_price'];
            #查询会员卡的储值是否足够订单充值到账的金额
            $card = M('screen_memcard_use u')
                ->join('join ypt_screen_memcard m on m.id=u.memcard_id')
                ->where(array('u.id' => $recharge_info['memcard_id']))
                ->field('u.yue,u.card_code,u.entity_card_code,m.card_id,m.merchant_name,u.fromname')
                ->find();
            $user_yue = $card['yue'];
            if ($user_yue < $total_yue) {
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/', 'pay_back', '储值退款失败原因：用户剩余储值少于充值的金额', ',订单号：' . $remark . ',退款金额：' . $price_back . ',用户剩余储值：' . $user_yue . '，充值的储值：' . $total_yue);
                $this->ajaxReturn(array("code" => "error", "msg" => "用户剩余储值少于充值的金额"));
            } else {
                #如果储值充足退款必须退全款
                if ($price_back != $pay['price']) {
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/', 'pay_back', '储值退款失败原因：储值订单必须全额退款', ',订单号：' . $remark . ',退款金额：' . $price_back . ',订单金额：' . $pay['price']);
                    $this->ajaxReturn(array("code" => "error", "msg" => "储值订单必须全额退款"));
                }
                $final_yue = $user_yue - $total_yue;
                $refund_price = 0;
            }
        } else {
            #其他使用储值订单退款处理
            $order_info = M('order')->where(array('order_sn' => $remark))->field('card_code,user_money')->find();
            $dec_card_balance = 0;
            if ($order_info && $order_info['user_money'] > 0) {
                #如果使用了储值
                $card = M('screen_memcard_use u')
                    ->join('join ypt_screen_memcard m on m.id=u.memcard_id')
                    ->where(array('u.card_code|u.entity_card_code' => $order_info['card_code']))
                    ->field('u.yue,u.card_code,u.entity_card_code,m.card_id,m.merchant_name,u.fromname')
                    ->find();
                $final_yue = $card['yue'] + $order_info['user_money'];
                $refund_price = $order_info['user_money'];
                #判断如果是使用了代理商储值，查询商户余额够不够，把代理商余额扣除
                $is_agent = M('screen_memcard m')->join('ypt_screen_memcard_use u on u.memcard_id=m.id')->where(array('u.card_code' => $order_info['card_code']))->getField('is_agent');
                if ($is_agent) {
                    #如果是代理商会员卡
                    if ($price_back != $pay['price']) $this->ajaxReturn(array("code" => "error", "msg" => "该笔订单使用了异业联盟卡必须全额退款"));
                    #算储值折扣前的的金额是否大于商户现在的余额
                    $card_balance = M('merchants_users')->where(array('id' => $muid))->getField('card_balance/card_rate*100');
                    if ($card_balance < $order_info['user_money']) $this->ajaxReturn(array("code" => "error", "msg" => "商户余额不足"));
                    #dec_card_balance 实际扣除商户余额金额
                    $dec_card_balance = M('merchants_users')->where(array('id' => $muid))->getField("$order_info[user_money]*card_rate/100");
                }
            }
        }

        $price = $pay['price'];
        if ($price_back > $price) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Pay/', 'pay_back', '退款失败：', ',订单号：' . $remark . ',退款金额：' . $price_back . ',订单金额：' . $pay['price'] . ',失败原因：退款金额不能大于原有金额');
            $this->ajaxReturn(array("code" => "error", "msg" => "退款金额不能大于原有金额"));
        }
        if ($style == 1) { //现金退款
            if ($pay) $this->pays->where("remark='$remark'")->save(array("status" => "2", "price_back" => $price_back, "back_status" => 1));
            $back_info = $this->add_pay_back($pay, 99, $price_back);
            if ($dec_card_balance) {
                M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
            }
            if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
            $this->add_order_goods_number($remark);
            $this->ajaxReturn(array("code" => "success", "msg" => "退款成功", 'back_info' => $back_info));
        } else if ($style == 2) {
            // 使用储值全额支付
//            $pay_mode = $pay['mode'];
//            if(in_array($pay_mode,array(14,16,18,19,20,24))){
//
//            }
            // 微信支付
            if ($pay['bank'] == 3) {
                file_put_contents('./data/log/weixin/' . date("Y_m_") . 'pay_back.log', date("Y-m-d H:i:s") . ',单号：' . $remark . '-price_back-' . $price_back . '-price-' . $price . PHP_EOL, FILE_APPEND | LOCK_EX);
                
                $result = A("Pay/Wxpay")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    //$this->add_order_goods_number($remark);
                    if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                }
                file_put_contents('./data/log/weixin/' . date("Y_m_") . 'pay_back.log', date("Y-m-d H:i:s") . ',单号：' . $remark . ",result:" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn($result);
            }

            // 兴业银行
            if ($pay['bank'] == 7) {

                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    $d = M('merchants_xypay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    
                    $result = A("Pay/Barcodexybank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    $d = M('merchants_xypay')->where(array('merchant_id' => $mid))->getField('pay_style');
                    if ($d != '1') {
                        $this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));
                    }
                    
                    $result = A("Pay/Barcodexybank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 宿州李总微信支付
            if ($pay['bank'] == 9) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    
                    $result = A("Pay/Szlzpay")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        //$this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    
                    $result = A("Pay/Szlzpay")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        //$this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 浦发银行
            if ($pay['bank'] == 10) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    //$d = M('merchants_pfpay')->where(array('merchant_id'=>$mid))->getField('pay_style');
                    //if($d!='1'){$this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));}
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    
                    $result = A("Pay/Barcodepfbank")->wx_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    //$d = M('merchants_pfpay')->where(array('merchant_id'=>$mid))->getField('pay_style');
                    //if($d!='1'){$this->ajaxReturn(array("code" => "error", "msg" => "D0通道不能退款"));}
                    //if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "退款金额必须等于原有金额"));
                    
                    $result = A("Pay/Barcodepfbank")->ali_pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        $this->add_order_goods_number($remark);
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 新大陆
            if ($pay['bank'] == 11) {
                $pay_style = $pay['paystyle_id'];
                if ($pay_style == 1) {//微信原路退款
                    
                    $result = A("Pay/Barcodexdlbank")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
                if ($pay_style == 2) {//支付宝原路退款
                    
                    $result = A("Pay/Barcodexdlbank")->pay_back($remark, $price_back);
                    if ($result['code'] == "success") {
                        $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                        if ($dec_card_balance) {
                            M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                            M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                        }
                        if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                    }
                    $this->ajaxReturn($result);
                }
            }
            // 乐刷
            if ($pay['bank'] == 12) {
                if ($price_back != $price) $this->ajaxReturn(array("code" => "error", "msg" => "仅支持全额退款"));
                $result = A("Pay/Leshuabank")->refund($remark);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                }
                $this->ajaxReturn($result);
            }
            // 平安付
            if ($pay['bank'] == 13) {
                
                $result = A("Pay/Barcodepabank")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                }
                $this->ajaxReturn($result);
            }
            // 随行付
            if ($pay['bank'] == 14) {
                
                $result = A("Pay/Banksxf")->pay_back($remark, $price_back);
                if ($result['code'] == "success") {
                    $result['back_info'] = $this->add_pay_back($pay, 98, $price_back);
                    if ($dec_card_balance) {
                        M('merchants_users')->where(array('id' => $this->userId))->setDec('card_balance', $dec_card_balance);
                        M('balance_log')->add(array('price' => -$dec_card_balance, 'ori_price' => -$dec_card_balance, 'rate_price' => 0, 'order_sn' => $remark, 'add_time' => time(), 'remark' => '退款扣除余额', 'mid' => $this->userId, 'balance' => M('merchants_users')->where(array('id' => $this->userId))->getField('balance')));
                    }
                    if ($card) $this->reduce_cz($card, $final_yue, $remark, $refund_price);
                }
                $this->ajaxReturn($result);
            }
            $this->ajaxReturn(array("code" => "error", "msg" => "EOF"));

        }
    }

    /**
     * @param $uid
     * @return 获取商户id
     */
    protected function get_merchant($uid)
    {
        $role_id = M("merchants_role_users")->where("uid=$uid")->getField('role_id');
        if ($role_id == 3) {
            return $uid;
        } else {
            return M("merchants_users")->where("id=$uid")->getField("pid");
        }
    }

    /**
     * 支付成功后更新库存
     * 传入订单唯一标识$order_sn
     * @param int $order_sn
     */
    private function add_order_goods_number($remark = 0)
    {
        if (!$remark) exit();
        $new_order_sn = $this->pays->where("remark='$remark'")->getField("new_order_sn");
        if ($new_order_sn) {
            $order_sn = $remark;
            $order_id = M("order")->where(array("order_sn" => $order_sn))->getField("order_id");
            $order_goods_list = M("order_goods")->where(array("order_id" => $order_id))->field("goods_id,goods_num")->select();
            if ($order_goods_list) {
                foreach ($order_goods_list as $k => $v) {
                    if ($v['goods_id'] && $v['goods_num']) M("goods")->where(array("goods_id" => $v['goods_id']))->setInc('goods_number', $v['goods_num']); //更新库存
                }
            }
        }
    }

    //点击收款
    public function pay_order()
    {
        if (IS_POST) {
            get_date_dir($this->path, 'Pay_pay_order', '接收参数', json_encode($_POST));
            $jmt_remark = trim(I("jmt_remark"));
            $order_info = array();
            $order_sn = date('YmdHis') . mt_rand(100000, 999999);//流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["pay_status"] = 0;//支付状态为1
            $order_info["type"] = "0";
            $order_info["order_status"] = "1";//0为收银订单
            $order_info['integral'] = I('dikoufen');//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = 0;//商品数量为0
            $order_info["total_amount"] = I("total_amount");//订单总价
            $order_info["user_money"] = I("yue");//使用余额
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id;
            $order_info["add_time"] = I("timestamp");
            $order_info["discount"] = I("discount") * 100;//整单折扣
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $card_code = I("card_code", "");
            $order_info["card_code"] = $card_code;//会员卡号
            $order = M('order');
            if (!$card_code && !$code) {//未使用会员卡或者优惠券不插入order表
                $checker_id = I("checker", '0');
                $price = $order_amount;
                if ($price == 0) $this->ajaxReturn(array("code" => "error", "msg" => "价格不能为空!"));
                $role_id = M("merchants_role_users")->where("uid=$user_id")->getField('role_id');
                if ($role_id == 7) {//收银员
                    $checker_id = $user_id;
                    $u_id = M("merchants_users")->where("id=$user_id")->getField("pid");
                } elseif ($role_id == 3) {//商户
                    $checker_id = 0;
                    $u_id = $user_id;
                } else {
                    $checker_id = $user_id;
                    $u_id = M("merchants_users")->where("id=$user_id")->getField("pid");
                }
                $merchant_id = $this->merchants->where("uid=$u_id")->getField("id");
                $cate_id = M("merchants_cate")->where(array('merchant_id' => $merchant_id, 'status' => 1, 'checker_id' => $checker_id))->getField("id");
                if (!$cate_id) $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未绑定台签"));
                $no_number = $this->create_no_number($cate_id);//每张二维码唯一标识
                $value = "https://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id . "&price=" . $price . "&checker_id=" . $checker_id . "&order_sn=" . $order_sn;
                if ($jmt_remark) {
                    $value = $value . "&jmt_remark=" . $jmt_remark;
                }
                get_date_dir($this->path, 'Pay_pay_order', '没优惠二维码地址', $value);
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("url" => $value, "order_sn" => $order_sn)));
            } else {
                $res = $order->add($order_info);
                if ($res) {
                    get_date_dir($this->path, 'Pay_pay_order', '获取地址参数user_id', $user_id . ';--order_sn:' . $order_sn);
                    $value = A('Apiscreen/Pay')->two_get_card($user_id, $order_sn);
                    get_date_dir($this->path, 'Pay_pay_order', '结果', json_encode($value));
                    if ($value['code'] == 'error') {
                        $this->ajaxReturn($value);
                    } else {
                        if ($jmt_remark) $value['data'] = $value['data'] . "&jmt_remark=" . $jmt_remark;
                        get_date_dir($this->path, 'Pay_pay_order', '优惠二维码地址', $value['data']);
                        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("url" => $value['data'], "order_sn" => $order_sn)));
                    }
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //退款成功添加到退款记录表
    protected function add_pay_back($pay_back, $mode, $price_back)
    {
        $pay_back['back_pid'] = $pay_back['id'];
        $pay_back['status'] = 5;
        $pay_back['price_back'] = $price_back;
        $pay_back['paytime'] = time();
        $pay_back['mode'] = $mode;
        $pay_back['bill_date'] = date('Ymd');
        $order = M('order')->where(array('order_sn' => $pay_back['remark']))->find();
        if ($order) {
            M('order')->where(array('order_sn' => $pay_back['remark']))->save(array('order_status' => 0));
        }
        if ($order && $order['user_money']) {
            $card = M("screen_memcard_use")
                ->field('yue,card_id')
                ->where(array('card_code' => $order['card_code']))
                ->find();
            $ts["record_bonus"] = urlencode("退款返回储值");
            $ts['code'] = urlencode($order['card_code']);
            $ts['card_id'] = urlencode($card['card_id']);
            $ts['custom_field_value1'] = urlencode($card['yue'] + $order['user_money']);//会员卡余额
            $ts['notify_optional']['is_notify_custom_field1'] = true;
            $token = get_weixin_token();
            $msg = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            $info = json_decode($msg, true);
            if ($info['errmsg'] == 'ok') {
                M('screen_memcard_use')->where(array('card_code' => $order['card_code']))->setField('yue', $card['yue'] + $order['user_money']);
            }
        }
        unset($pay_back['id']);
        $id = $this->payBack->add($pay_back);
        $back_info = $this->payBack->where("id=$id")->field('id,paystyle_id,checker_id,price_back as price,remark,status,paytime,bill_date,mode')->find();
        return $back_info;
    }

    #储值订单退款撤回充值的储值
    private function reduce_cz($card, $final_yue, $remark,$refund_price)
    {
        M()->startTrans();
        $memcard_use_where = false;
        if ($card['card_code']) {
            $memcard_use_where['card_code'] = $card['card_code'];
        } elseif ($card['entity_card_code']) {
            $memcard_use_where['entity_card_code'] = $card['entity_card_code'];
        }
        if ($memcard_use_where) {
            M('screen_memcard_use')->where($memcard_use_where)->setField('yue', $final_yue);
        }

        if ($card['card_code']) {
            $token = get_weixin_token();
            $ts['code'] = urlencode($card['card_code']);//卡号
            $ts['card_id'] = urlencode($card['card_id']);//卡id
            $ts['custom_field_value1'] = urlencode($final_yue);//会员卡储值
            $res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            file_put_contents('./data/log/weixin/' . date("Y_m_") . 'card_coupon.log', date("Y-m-d H:i:s") . ',退款退储值，订单号:' . $remark . '，会员卡code:' . $card['card_code'] . ',请求参数:' . json_encode($ts) . PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents('./data/log/weixin/' . date("Y_m_") . 'card_coupon.log', date("Y-m-d H:i:s") . ',退款退储值，订单号:' . $remark . '，会员卡code:' . $card['card_code'] . ',返回结果:' . $res . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
            $result = json_decode($res, true);
            if ($result['errcode'] == 0) {
                M()->commit();
                # 使用了储值支付，需要给消费者微信推送消息
                if($card['fromname'] && $refund_price){
                    A('Wechat/Message')->refund($card['fromname'],$refund_price,$card['merchant_name']);
                }
            } else {
                M()->rollback();
            }
        } else {
            M()->commit();
        }
    }

    //1.3.4余额支付（应收金额为0）
    public function yue_pay()
    {
        if (IS_POST) {
            $jmt_remark = trim(I("jmt_remark"));
            $order_info = array();
            $order_sn = date('YmdHis') . mt_rand(100000, 999999);//流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["order_amount"] = $order_amount;//应收金额
            $order_info["pay_status"] = 1;//支付状态为1
            $order_info["type"] = "0";//0为收银订单
            $order_info["order_status"] = "5";
            $order_info['integral'] = I('dikoufen', 0);//该订单使用积分
            $order_info['integral_money'] = I('dikoujin');//该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code;//优惠券ID
            $order_info["coupon_price"] = I("coupon_price");//使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = 0;//商品数量为0
            $order_info["total_amount"] = I("total_amount");//订单总价
            $order_info["user_money"] = I("yue");//使用余额
            $user_id = I('uid', $this->userId);
            $order_info["user_id"] = $user_id;
            $order_info["add_time"] = I("timestamp");
            $order_info["discount"] = I("discount") * 100;//整单折扣
            $order_info["order_benefit"] = I("order_benefit");//整单优惠金额
            $card_code = I("card_code", "");
            $order_info["card_code"] = $card_code;//会员卡号
            $this->check_preferential($card_code, $order_info["user_money"], $order_info['integral'], $code);
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            $order = M('order');
            $res = $order->add($order_info);
            //应付金额为0支付成功
            if ($res) {
                if ($order_amount == 0) {
                    $pay_info = array(
                        "order_id" => $res,
                        "remark" => $order_sn,
                        "mode" => 14,
                        "merchant_id" => $merchant_id,
                        "checker_id" => $checker_id,
                        "paystyle_id" => 1,
                        "price" => $order_amount,
                        "status" => 1,
                        "cate_id" => 1,
                        "paytime" => time(),
                        "jmt_remark" => $jmt_remark,
                        "bill_date" => date('Ymd')
                    );
                    $pay = $this->pays;
                    $pay_id = $pay->add($pay_info);
                    $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $order_sn, 'pay_id' => $pay_id));
                } else {
                    $this->ajaxReturn(array("code" => "error", 'msg' => '应付金额不为0'));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    #检查该笔订单使用的储值、积分是否充足，是否有优惠券
    public function check_preferential($card_code, $yue, $integral, $coupon_code)
    {
        #会员卡
        if ($card_code > 0) {
            $card_info = M('screen_memcard_use')->where("card_code='$card_code' or entity_card_code='$card_code'")->field('yue,card_balance')->find();
            if ($yue > 0) {
                if ($yue > $card_info['yue']) {
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '储值余额有变动，请重新收款！'));
                }
            }
            if ($integral > 0) {
                if ($integral > $card_info['card_balance']) {
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '积分有变动，请重新收款！'));
                }
            }
        }
        #优惠券
        if ($coupon_code > 0) {
            $coupon_status = M('screen_user_coupons')->where(array('usercard' => $coupon_code))->getField('status');
            if ($coupon_status == 0) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '优惠券已被使用，请重新收款！'));
            }
        }
    }

    //1.3.4现金收款
    public function cash_order()
    {
        if (IS_POST) {
            $order_sn = I('order_sn');
            if (!$order_sn) {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败01", "data" => "参数错误"));
            } else {
                #检查该笔订单使用的储值、积分是否充足，是否有优惠券
                $order_info = M('order')->where(array('order_sn' => $order_sn))->field('card_code,user_money,integral,coupon_code')->find();
                $this->check_preferential($order_info['card_code'], $order_info["user_money"], $order_info['integral'], $order_info['coupon_code']);
            }
            $order_amount = I('order_amount');
            $jmt_remark = I('jmt_remark', '');
            $user_id = $this->userId;
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {
                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            $pay_info = array(
                "remark" => $order_sn,
                "mode" => 13,
                "merchant_id" => $merchant_id,
                "checker_id" => $checker_id,
                "paystyle_id" => 5,
                "price" => $order_amount,
                "status" => 1,
                "cate_id" => 1,
                "jmt_remark" => $jmt_remark,
                "paytime" => time()
            );
            $ab = $this->pays->add($pay_info);
            if ($ab) {
                M('order')->where("order_sn='$order_sn'")->setField('order_status', 5);
                $this->ajaxReturn(array("code" => "success", "msg" => "支付成功", 'data' => array('pay_id' => $ab)));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "网络错误！支付失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //核销优惠券、扣会员卡余额、积分
    #老版本核销，新核销没问题可删除
    public function cardOff_old()
    {
        $order_sn = I('order_sn');
        $order = M('order')->where("order_sn='$order_sn'")->find();
        // if($order['order_status']!==1){
        //     $this->ajaxReturn(array("code"=>"error", 'msg' => '已经支付了'));
        // }
        $coupon_code = $order['coupon_code'];//优惠券code
        $card_code = $order['card_code'];//会员卡code
        $price = $order['order_amount'];//订单应付金额（优惠后的价格）
        $dikoufen = $order['integral'];//会员卡使用的积分
        $yue = $order['user_money'];//会员卡使用的余额  

        $save['update_time'] = time();
        $save['pay_time'] = time();
        $save['order_status'] = '5';
        $save['pay_status'] = '1';
        $add = M('order')->where("order_sn='$order_sn'")->save($save);

        //主扫生成的订单号改成order_sn
        $remark = I('remark');
        if ($remark) {
            $orderId = M('order')->where("order_sn='$order_sn'")->getField('order_id');
            $this->pays->where("remark='$remark'")->setField('order_id', $orderId);
            $paystyle = $this->pays->where("remark='$remark'")->getField('paystyle_id');
            M('order')->where("order_sn='$order_sn'")->setField(array('order_sn' => $remark, 'paystyle' => $paystyle));
            $pay_id = $this->pays->where("remark='$remark'")->getField('id');
            //A("Api/Cloud")->printer($remark);
        } else {
            $mode = $this->pays->where("remark='$order_sn'")->getField('mode');
            if ($mode == '3') {
                $this->pays->where("remark='$order_sn'")->setField('mode', 1);
            }
            $paystyle = $this->pays->where("remark='$order_sn'")->getField('paystyle_id');
            M('order')->where("order_sn='$order_sn'")->setField(array('paystyle' => $paystyle));
            $pay_id = $this->pays->where("remark='$order_sn'")->getField('id');
            //A("Api/Cloud")->printer($order_sn);
        }

        //核销优惠券
        if ($coupon_code) {
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $data['code'] = $coupon_code;
            $use_coupon = request_post($url, json_encode($data));
            $result = json_decode($use_coupon, true);
            M("screen_user_coupons")->where("usercard=$coupon_code")->setField('status', '0');
            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($result['errmsg'] != "ok") {
                $coupon_off = false;
                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            } elseif ($result['errmsg'] == "ok") {
                $coupon_off = true;
            }
        }

        //会员卡
        if ($card_code) {
            $card = M("screen_memcard_use")->alias('u')
                ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                ->field('m.id,m.credits_set,m.expense,m.expense_credits,m.expense_credits_max,u.card_balance,u.yue,u.card_id,u.card_amount')
                ->where("u.card_code='$card_code'")
                ->find();

            //会员卡消费送积分
            if ($card['credits_set'] == 1) {
                $send = floor($price / $card['expense']) * $card['expense_credits'];
                //如果送的积分大于最多可送的分
                if ($send > $card['expense_credits_max']) {
                    $send = $card['expense_credits_max'];
                }
            }
            if ($dikoufen) {
                $data['card_balance'] = $card['card_balance'] - $dikoufen + $send;
            } else {
                $data['card_balance'] = $card['card_balance'] + $send;
            }
            if ($yue) {
                $data['yue'] = $card['yue'] - $yue;
            }
            $card_off = M("screen_memcard_use")->where("card_code='$card_code'")->save($data);
            $ts['code'] = urlencode($card_code);
            $ts['card_id'] = urlencode($card['card_id']);
            $ts['custom_field_value1'] = urlencode($card['yue'] - $yue);//会员卡余额
            $ts['custom_field_value2'] = urlencode(M('screen_memcard_level')->where("c_id=$card[id] and level_integral<=$card[card_amount]")->order('level desc')->getField('level_name'));//会员卡名称
            $ts["add_bonus"] = urlencode($send - $dikoufen);//会员卡积分
            $token = get_weixin_token();
            file_put_contents('./data/log/testcoupon.log', date("Y-m-d H:i:s") . json_encode($ts) . PHP_EOL, FILE_APPEND | LOCK_EX);
            request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
        }

        if ($coupon_off && $card_off) {
            $this->ajaxReturn(array("code" => "success", 'msg' => '核销优惠券、会员卡成功', 'pay_id' => $pay_id));
        } elseif ($coupon_off) {
            $this->ajaxReturn(array("code" => "success", 'msg' => '核销优惠券成功', 'pay_id' => $pay_id));
        } elseif ($card_off) {
            $this->ajaxReturn(array("code" => "success", 'msg' => '核销会员卡成功', 'pay_id' => $pay_id));
        } elseif ($add) {
            $this->ajaxReturn(array("code" => "success", 'msg' => '成功', 'pay_id' => $pay_id));
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '核销失败'));
        }
    }

    //核销优惠券、扣会员卡余额、积分1.6.0
    public function cardOff()
    {

        $order_sn = I('order_sn');
        $order = M('order')->where("order_sn='$order_sn'")->find();

        $coupon_code = $order['coupon_code'];//优惠券code
        $card_code = $order['card_code'];//会员卡code
        $price = $order['order_amount'];//订单应付金额（优惠后的价格）
        $dikoufen = $order['integral'];//会员卡使用的积分
        $yue = $order['user_money'];//会员卡使用的余额
        get_date_dir($this->path, 'Pay_card_off', '----参数', json_encode($_POST));

        //主扫生成的订单号改成order_sn
        $remark = I('remark');
        if ($remark) {
            $orderId = M('order')->where("order_sn='$order_sn'")->getField('order_id');
            $this->pays->where("remark='$remark'")->setField('order_id', $orderId);
            $paystyle = $this->pays->where("remark='$remark'")->getField('paystyle_id');
            M('order')->where("order_sn='$order_sn'")->setField(array('order_sn' => $remark, 'paystyle' => $paystyle));
            $pay_id = $this->pays->where("remark='$remark'")->getField('id');
        } else {
            $mode = $this->pays->where("remark='$order_sn'")->getField('mode');
            if ($mode == '3') $this->pays->where("remark='$order_sn'")->setField('mode', 1);
            $paystyle = $this->pays->where("remark='$order_sn'")->getField('paystyle_id');
            M('order')->where("order_sn='$order_sn'")->setField(array('paystyle' => $paystyle));
            $pay_id = $this->pays->where("remark='$order_sn'")->getField('id');
        }
        #判断该笔订单是否已核销
        if ($order['is_cancel'] == 1) {
            $this->ajaxReturn(array("code" => "success", 'msg' => '成功', 'pay_id' => $pay_id));
        }
        $two_mode = $this->pays->where("remark='$order_sn'")->getField('mode');
        //减库存 加销量
        if ($order['type'] == 4 && $two_mode == 17) {
            A("Pay/barcode")->decrease_stock($order['order_id']);
        }
        $save['update_time'] = time();
        $save['pay_time'] = time();
        $save['order_status'] = '5';
        $save['pay_status'] = '1';
        $save['is_cancel'] = 1;
        M('order')->where("order_sn='$order_sn'")->save($save);

        //核销优惠券
        if ($coupon_code) {
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $coupon_data['code'] = $coupon_code;
            $use_coupon = request_post($url, json_encode($coupon_data));
            $coupon_result = json_decode($use_coupon, true);
            if ($coupon_result['errmsg'] == "ok") {
                M("screen_user_coupons")->where("usercard=$coupon_code")->setField(array('status' => 0, 'update_time' => time()));
            }
            get_date_dir($this->path, 'Pay_card_off', 'API核销优惠券', '消费使用，订单号:' . $remark ? $remark : $order_sn . '，优惠券code:' . $coupon_code . ',核销结果:' . json_encode($coupon_result));
        }

        //会员卡
        if ($card_code) {
            $card = M("screen_memcard_use")->alias('u')
                ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                ->field('m.id,m.credits_set,m.expense,m.level_set,m.is_agent,m.level_up,m.expense_credits,m.expense_credits_max,m.merchant_name,u.fromname,u.id as smu_id,u.card_code,u.entity_card_code,u.card_balance,u.yue,u.card_id,u.card_amount,u.level')
                ->where(array('card_code|entity_card_code' => $card_code))
                ->find();
            get_date_dir($this->path, 'Pay_card_off', '核销前card信息-' . $card_code, json_encode($card));
            //会员卡消费送积分
            $send = 0;
            if ($card['credits_set'] == 1) {
                $send = floor(($price + $yue) / $card['expense']) * $card['expense_credits'];
                //如果送的积分大于最多可送的分，则赠送最大积分
                if ($send > $card['expense_credits_max']) {
                    $send = $card['expense_credits_max'];
                }
            }
            #如果使用联名卡，给商家加上储值
            if ($card['is_agent'] == 1) {
                $role_id = M('merchants_role_users')->where(array('uid' => $order['user_id']))->getField('role_id');
                if ($role_id == 3 && $order['user_money'] > 0) {//商家&&使用了储值支付
                    #1.8版本先扣增加余额扣掉手续费，2018.4.11
                    $card_rate = M('merchants_users')->where(array('id' => $order['user_id']))->getField('card_rate');
                    $inc_price = $order['user_money'] * $card_rate / 100;
                    M('merchants_users')->where(array('id' => $order['user_id']))->setInc('card_balance', $inc_price);
                    #余额日志
                    M('balance_log')->add(array('price' => $inc_price, 'ori_price' => $order['user_money'], 'rate_price' => $order['user_money'] - $inc_price, 'order_sn' => $remark ?: $order_sn, 'add_time' => time(), 'remark' => '核销异业联盟卡', 'mid' => $order['user_id'], 'balance' => M('merchants_users')->where(array('id' => $order['user_id']))->getField('balance')));
                }
            }
            //yue，会员卡余额
            M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->save(array('yue' => $card['yue'] - $yue));
            //获取商户的等级信息,level_set等级设置，level_up是否可升级
            if ($card['level_set'] == 1 && $card['level_up'] == 1) {
                //获取该会员的单次消费expense_single，累计消费expense，累计积分card_amount
                $field = 'ifnull(sum(order_amount),0) as expense,ifnull(max(order_amount),0) as expense_single';
                $mem_info_where['order_status'] = '5';
                if ($card['card_code'] && $card['entity_card_code']) {
                    $mem_info_where['card_code'] = array(array('eq', $card['card_code']), array('eq', $card['entity_card_code']), 'or');
                } else {
                    if ($card['card_code']) {
                        $mem_info_where['card_code'] = $card['card_code'];
                    } elseif ($card['entity_card_code']) {
                        $mem_info_where['card_code'] = $card['entity_card_code'];
                    }
                }
                $mem_info = M('order')->where($mem_info_where)->field($field)->find();
                $mem_info['card_amount'] = M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->getField('card_amount');
                //会员卡所有等级列表
                #充值记录信息，recharge累计充值金额，recharge_single单次充值最大金额
                $recharge_info = M('user_recharge')
                    ->where(array('memcard_id' => $card['smu_id'], 'status' => 1))
                    ->field('ifnull(sum(real_price),0) as recharge,ifnull(max(real_price),0) as recharge_single')
                    ->find();
                $mem_info = array_merge($mem_info, $recharge_info);
                $memcard_level = M('screen_memcard_level')->where(array('c_id' => $card['id']))->order('level asc')->select();
                foreach ($memcard_level as &$value) {
                    $type = explode(',', $value['level_up_type']);
                    foreach ($type as &$val) {
                        #会员当前等级信息,current_level当前等级,current_level_name当前等级名称
                        $level = $this->get_level($val, $mem_info, $value);
                        if ($level) {
                            $current_level = $level['current_level'];
                            $current_level_name = $level['current_level_name'];
                            break;
                        }
                    }
                }
            }
            if ($current_level && $current_level > $card['level']) {
                M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->setField(array('level' => $current_level));
                $ts['custom_field_value2'] = urlencode($current_level_name);//会员卡名称
            }
            $total_yue = $card['yue'] - $yue;//计算后的储值
            $token = get_weixin_token();
            $ts['code'] = urlencode($card_code);//卡号
            $ts['card_id'] = urlencode($card['card_id']);//卡id
            $ts['custom_field_value1'] = urlencode($total_yue);//会员卡储值
            request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
            $final_order = $remark ?: $order_sn;


            # 使用了储值支付，需要给消费者微信推送消息
            if($yue > 0 && $card['fromname']){
                A('Wechat/Message')->use_balance($card['fromname'],$card_code,$yue,$card['merchant_name'],$total_yue);
            }

            if ($dikoufen > 0) {
                M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->setDec('card_balance', $dikoufen);
                get_date_dir($this->path, 'Pay_card_off', 'SQl1', M()->_sql());
                $card_balance = M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->getField('card_balance');
                get_date_dir($this->path, 'Pay_card_off', 'SQl1_card_balance', $card_balance);
                $ts["add_bonus"] = urlencode('-' . $dikoufen);//增加的积分，负数为减
                $ts["record_bonus"] = urlencode('消费使用积分');//增加的积分，负数为减
                get_date_dir($this->path, 'Pay_card_off', '抵扣积分', "订单号{$final_order}，会员卡code:{$card_code}");
                get_date_dir($this->path, 'Pay_card_off', '扣除积分', $dikoufen);
                $wx_card_code = M('screen_memcard_use')->where(array('card_code|entity_card_code' => $card_code))->getField('card_code');
                if ($wx_card_code) {
                    $ts['code'] = urlencode($wx_card_code);//卡号
                    get_date_dir($this->path, 'Pay_card_off', '核销微信卡', '请求参数:' . json_encode($ts));
                    $dikoufen_ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    $dikoufen_ts_result = json_decode($dikoufen_ts_res, true);
                    $dikoufen_ts_result['errcode'] == 0 ? $dikoufen_ts_result_msg = 1 : $dikoufen_ts_result_msg = 0;
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => '-' . $dikoufen, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $remark ? $remark : $order_sn, 'code' => $card_code, 'ts_status' => $dikoufen_ts_result_msg, 'msg' => $dikoufen_ts_res, 'record_bonus' => '消费使用积分'));
                    get_date_dir($this->path, 'Pay_card_off', '核销微信卡', '返回结果:' . $dikoufen_ts_res);
                } else {
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => '-' . $dikoufen, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $remark ? $remark : $order_sn, 'code' => $card_code, 'ts_status' => '1', 'msg' => '', 'record_bonus' => '消费赠送积分'));
                    get_date_dir($this->path, 'Pay_card_off', '核销实体卡', '参数:' . json_encode($ts));
                }
            }
            if ($send > 0) {
                //card_balance，会员卡剩余积分
                M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->setInc('card_balance', $send);
                get_date_dir($this->path, 'Pay_card_off', 'SQl2', M()->_sql());
                //card_balance，会员卡总积分
                M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->setInc('card_amount', $send);
                get_date_dir($this->path, 'Pay_card_off', 'SQl3', M()->_sql());
                $card_balance = M("screen_memcard_use")->where(array('card_code|entity_card_code' => $card_code))->getField('card_balance');
                $ts["add_bonus"] = urlencode($send);//增加的积分，负数为减
                $ts["record_bonus"] = urlencode('消费赠送积分');//增加的积分，负数为减
                get_date_dir($this->path, 'Pay_card_off', '核销赠送积分', "订单号{$final_order}，会员卡code:{$card_code}");
                get_date_dir($this->path, 'Pay_card_off', '赠送积分', $send);
                $wx_card_code = M('screen_memcard_use')->where(array('card_code|entity_card_code' => $card_code))->getField('card_code');
                if ($wx_card_code) {
                    $ts['code'] = urlencode($wx_card_code);//卡号
                    get_date_dir($this->path, 'Pay_card_off', '核销微信卡', '请求参数:' . json_encode($ts));
                    $send_ts_res = request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token=' . $token, urldecode(json_encode($ts)));
                    $send_ts_result = json_decode($send_ts_res, true);
                    $send_ts_result['errcode'] == 0 ? $send_ts_result_msg = 1 : $send_ts_result_msg = 0;
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $send, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $remark ? $remark : $order_sn, 'code' => $card_code, 'ts_status' => $send_ts_result_msg, 'msg' => $send_ts_res, 'record_bonus' => '消费赠送积分'));
                    get_date_dir($this->path, 'Pay_card_off', '核销微信卡', '返回结果:' . $send_ts_res);
                } else {
                    M('screen_memcard_log')->add(array('add_time' => time(), 'update_time' => time(), 'value' => $send, 'balance' => $card_balance, 'ts' => json_encode($ts), 'order_sn' => $remark ? $remark : $order_sn, 'code' => $card_code, 'ts_status' => '1', 'msg' => '', 'record_bonus' => '消费赠送积分'));
                    get_date_dir($this->path, 'Pay_card_off', '核销实体卡', '参数:' . json_encode($ts));

                }
            }
        }

        $this->ajaxReturn(array("code" => "success", 'msg' => '成功', 'pay_id' => $pay_id));
    }

    //获取会员当前等级信息
    private function get_level($type, $up_info, $level_info)
    {
        switch ($type) {
            case 1:
                if ($up_info['recharge_single'] >= $level_info['level_recharge_single']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 2:
                if ($up_info['recharge'] >= $level_info['level_recharge']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 3:
                if ($up_info['expense_single'] >= $level_info['level_expense_single']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 4:
                if ($up_info['expense'] >= $level_info['level_expense']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            case 5:
                if ($up_info['card_amount'] >= $level_info['level_integral']) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                    $level['current_level_discount'] = $level_info['level_discount'];
                    return $level;
                }
                break;
            default:
                if ($level_info['level'] == 1) {
                    $level['current_level'] = $level_info['level'];
                    $level['current_level_name'] = $level_info['level_name'];
                } else {
                    $level = null;
                }
                return $level;
        }
    }

    //生成流水号
    public function get_order_sn()
    {
        $order_sn = date('YmdHis') . mt_rand(100000, 999999);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $order_sn));
    }

    //查看当日流水统计
    public function day_count()
    {
        $uid = $this->userId;
        //$uid = 163;
        $mid = get_mch_id($uid);
        if (I('start_time') && I('end_time')) {
            $start_time = I('start_time');
            $end_time = I('end_time');
        } else {
            $start_time = strtotime(I('date'));
            $end_time = $start_time + 86399;
        }

        /*$pay = $this->pays
            ->field('ifnull(sum(if(status=1,price,0)),0) as s_total_price,ifnull(sum(if(paystyle_id=1 and status=1,price,0)),0) as s_wx_price,ifnull(sum(if(paystyle_id=2 and status=1,price,0)),0) as s_ali_price,ifnull(sum(if(paystyle_id=5 and status=1,price,0)),0) as s_cash_price,ifnull(sum(if(paystyle_id=3 and status=1,price,0)),0) as s_bank_price,ifnull(sum(if(status=1,1,0)),0) as s_total_count,ifnull(sum(if(paystyle_id=1 and status=1,1,0)),0) as s_wx_count,ifnull(sum(if(paystyle_id=2 and status=1,1,0)),0) as s_ali_count,ifnull(sum(if(paystyle_id=5 and status=1,1,0)),0) as s_cash_count,ifnull(sum(if(paystyle_id=3 and status=1,1,0)),0) as s_bank_count,ifnull(sum(if(status=2,price_back,0)),0) as b_total_price,ifnull(sum(if(paystyle_id=1 and status=2,price_back,0)),0) as b_wx_price,ifnull(sum(if(paystyle_id=2 and status=2,price_back,0)),0) as b_ali_price,ifnull(sum(if(paystyle_id=5 and status=2,price_back,0)),0) as b_cash_price,ifnull(sum(if(paystyle_id=3 and status=2,price,0)),0) as b_bank_price,ifnull(sum(if(status=2,1,0)),0) as b_total_count,ifnull(sum(if(paystyle_id=1 and status=2,1,0)),0) as b_wx_count,ifnull(sum(if(paystyle_id=2 and status=2,1,0)),0) as b_ali_count,ifnull(sum(if(paystyle_id=5 and status=2,1,0)),0) as b_cash_count,ifnull(sum(if(paystyle_id=3 and status=2,1,0)),0) as b_bank_count')
            ->where(array('status'=>array('IN','1,2'),'merchant_id'=>$mid,'paytime'=>array('BETWEEN',array($start_time,$end_time))))
            ->find();*/
        $pay = $this->pays
            ->field('ifnull(sum(price),0) as s_total_price,ifnull(sum(if(paystyle_id=1,price,0)),0) as s_wx_price,ifnull(sum(if(paystyle_id=2,price,0)),0) as s_ali_price,ifnull(sum(if(paystyle_id=5,price,0)),0) as s_cash_price,ifnull(sum(if(paystyle_id=3,price,0)),0) as s_bank_price,ifnull(sum(if(status=1,1,0)),0) as s_total_count,ifnull(sum(if(paystyle_id=1,1,0)),0) as s_wx_count,ifnull(sum(if(paystyle_id=2,1,0)),0) as s_ali_count,ifnull(sum(if(paystyle_id=5,1,0)),0) as s_cash_count,ifnull(sum(if(paystyle_id=3,1,0)),0) as s_bank_count')
            ->where(array('status' => 1, 'merchant_id' => $mid, 'paytime' => array('BETWEEN', array($start_time, $end_time))))
            ->find();
        $pay_back = M('pay_back')
            ->field('ifnull(sum(price_back),0) as b_total_price,ifnull(sum(if(paystyle_id=1,price_back,0)),0) as b_wx_price,ifnull(sum(if(paystyle_id=2,price_back,0)),0) as b_ali_price,ifnull(sum(if(paystyle_id=5,price_back,0)),0) as b_cash_price,ifnull(sum(if(paystyle_id=3,price,0)),0) as b_bank_price,ifnull(sum(if(status=5,1,0)),0) as b_total_count,ifnull(sum(if(paystyle_id=1,1,0)),0) as b_wx_count,ifnull(sum(if(paystyle_id=2,1,0)),0) as b_ali_count,ifnull(sum(if(paystyle_id=5,1,0)),0) as b_cash_count,ifnull(sum(if(paystyle_id=3,1,0)),0) as b_bank_count')
            ->where(array('status' => 5, 'merchant_id' => $mid, 'paytime' => array('BETWEEN', array($start_time, $end_time))))
            ->find();
        $pay = array_merge($pay, $pay_back);
        $mch_card_code = M('screen_memcard m')->join('left join ypt_screen_memcard_use mu on mu.memcard_id=m.id')->where(array('m.mid' => $uid))->getField('card_code', true);
        if ($mch_card_code) {
            $mch_order = M('order')->field('ifnull(sum(if(order_status=5,user_money,0)),0) as s_mch_cz,ifnull(sum(if(order_status=0,user_money,0)),0) as b_mch_cz,ifnull(sum(if(order_status=5,1,0)),0) as s_mch_cz_count,ifnull(sum(if(order_status=0,1,0)),0) as b_mch_cz_count')->where(array('card_code' => array('in', $mch_card_code), 'user_id' => $uid, 'pay_time' => array('BETWEEN', array($start_time, $end_time))))->find();
        } else {
            $mch_order = array('s_mch_cz' => '0.00', 'b_mch_cz' => '0.00', 's_mch_cz_count' => '0', 'b_mch_cz_count' => '0');
        }
        $agent_id = M('merchants_users')->where('id=' . $uid)->getField('agent_id');
        $agent_card_code = M('screen_memcard m')->join('left join ypt_screen_memcard_use mu on mu.memcard_id=m.id')->where(array('m.mid' => $agent_id))->getField('card_code', true);
        if ($agent_card_code) {
            $agent_order = M('order')->field('ifnull(sum(if(order_status=5,user_money,0)),0) as s_agent_cz,ifnull(sum(if(order_status=0,user_money,0)),0) as b_agent_cz,ifnull(sum(if(order_status=5,1,0)),0) as s_agent_cz_count,ifnull(sum(if(order_status=0,1,0)),0) as b_agent_cz_count')->where(array('card_code' => array('in', $agent_card_code), 'user_id' => $uid, 'pay_time' => array('BETWEEN', array($start_time, $end_time))))->find();
        } else {
            $agent_order = array('s_agent_cz' => '0.00', 'b_agent_cz' => '0.00', 's_agent_cz_count' => '0', 'b_agent_cz_count' => '0',);
        }

        $data = array_merge($pay, $mch_order, $agent_order);
        $data['s_other_price'] = '0.00';
        $data['s_other_count'] = '0';
        $data['b_other_price'] = '0.00';
        $data['b_other_count'] = '0';
        $this->ajaxReturn(array('code' => 'success', 'msg' => '成功', "data" => $data));
    }

}
