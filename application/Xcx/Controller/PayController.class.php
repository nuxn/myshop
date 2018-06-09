<?php
namespace Api\Controller;

use Common\Controller\ApibaseController;

/**支付接口
 * 扫码支付、条码支付、刷卡支付
 * Class PayController
 * @package Api\Controller
 */
class  PayController extends ApibaseController
{
    public $id;
    const brand = 'YPT';
    private $pay_model;

    public function __construct()
    {
        parent::__construct();
        $this->checkLogin();
        $this->id = $this->userId;
        $this->pay_model = M('pay');
    }
    
    //扫码收款【被扫】
    public function get_card()
    {
        vendor("phpqrcode.phpqrcode");
        $checker_id = I("checker", '0');
        $price = I("price");
        if ($price == 0) $this->ajaxReturn(array("code" => "error", "msg" => "价格不能为空!"));
        if ($checker_id == $this->id) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->id;
        }

        $merchant_id = M("merchants")->where("uid=$u_id")->getField("id");
        $cate_id = M("merchants_cate")->where("merchant_id =$merchant_id")->getField("id");

        $no_number = $this->create_no_number($cate_id);//每张二维码唯一标识
        $value = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?g=Pay&m=Barcode&a=qrcode&type=0|" . $no_number . "&id=" . $cate_id . "&price=" . $price . "&checker_id=" . $checker_id;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $value));
    }

    /**生成no_number
     * @param $cate_id
     * @return string
     */
    private function create_no_number($cate_id)
    {
        $no_number = $this->pay_model->where(array("cate_id" => $cate_id))->order("id desc")->getField('no_number');
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

        if ($checker_id == $this->id) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->id;
        }
        $merchant_id = M("merchants")->where("uid=$u_id")->getField("id");
        $time_end = I("time") - 3;
        $where['merchant_id'] = $merchant_id;
        $where['checker_id'] = $checker_id;
        $where['mode'] = 1;
        $where['status'] = 0;
        $where['paytime'] = array('between', array($time_end, time()));
        $pay_none = $this->pay_model->where($where)->field("price,paystyle_id,mode,remark,paytime,status")->find();
        if ($pay_none) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay_none));
        } else {
            $where['status'] = 1;
            $pay_now = $this->pay_model->where($where)->field("price,paystyle_id,mode,remark,paytime,status")->find();
            if ($pay_now) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $pay_now));
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("price" => "", "paystyle_id" => "", "remark" => "", "mode" => "", "paytime" => "", "status" => "")));
    }


    //刷卡收款【主扫】
    public function barcode_pay()
    {
        $price = trim(I("price"));
        $code = trim(I("code"));
        $checker_id = trim(I("checker", '0'));
        $number = substr($code, 0, 2);

        if ($checker_id == $this->id) {
            $u_id = M("merchants_users")->where("id=$checker_id")->getField("pid");
        } else {
            $u_id = $this->id;
        }
        $id = M("merchants")->where("uid=$u_id")->getField("id");

        if ($number == "11" || $number == "12" || $number == "13" || $number == "14" || $number == "15" && strlen($code) == 18) {//微信支付
            $message = A("Pay/Barcode")->wz_micropay($id, $price, $code, $checker_id);
            if ($message['code'] == "error") {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "支付失败")));
            }
            if ($message['code'] == "success") {
                $pay = $this->pay_model->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
            }
        } else if ($number == '28') {//支付宝支付
            $message = A("Pay/Barcode")->ali_barcode_pay($id, $price, $code, $checker_id);
            if ($message['flag']) {
                $pay = $this->pay_model->where("merchant_id=$id And price=$price And status=1")->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status")->find();
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
            } else
                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));

        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "error", "msg" => "失败", "data" => "请扫微信或支付宝支付")));
        }

    }
}
