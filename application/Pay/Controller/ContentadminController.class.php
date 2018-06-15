<?php
namespace Pay\Controller;

use Common\Controller\AdminbaseController;

class ContentadminController extends AdminbaseController
{
    protected $pay;

    function __construct()
    {
        parent::__construct();
        $this->pay = M('pay');
    }

    public function test_rao()
    {
        $filePath = './public/1.xls';
        vendor("PHPExcel.PHPExcel");
        $PHPExcel = new \PHPExcel();
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        $PHPExcel = $PHPReader->load($filePath);
        $currentSheet = $PHPExcel->getSheet(0);  //读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        $i=0;
        $data =array();
        for($currentRow = 2;$currentRow <= $allRow;$currentRow++){

            for($currentColumn= 'A';$currentColumn<= $allColumn; $currentColumn++){
                $data[$i][]= $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65,$currentRow)->getValue();

            }
            $i++;
        }
        var_dump($data);
        exit;

        $model = M("weixin_token");
        $result = $model->where(array("type" => "1"))->find();
        $check_info = request_post("https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $result['access_token'] . "&openid=oyaFdwGG6w5U-RGyeh1yWOMoj5fM&lang=zh_CN");
        $check_info = json_decode($check_info, true);
        if ($result['a_time'] + 3000 < time() || !$result['access_token'] || !$check_info['openid']) {//判断是否存
            echo 4;
            exit;

        } else {
            echo 2;
            exit;
        }
    }

    /*
     * 支付信息详情页面
     * */
    public function index()
    {
        ini_set('memory_limit', '1000M');
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        $status = I('status');
        $user_phone = (int)I('user_phone', "", "trim");
        $remark = trim(I('remark'));
        $paystyle_id = I('paystyle');
        $timestyle_id = I('timestyle') ? I('timestyle') : "";
        $merchant_name = trim(I('merchant_name'));
        $bank = I("bank") ? I("bank") : "";
        if ($start_time > $end_time) {
            $this->error("开始时间不能小于结束时间");
        }
        if ($bank) $map['bank'] = $bank;
        if ($paystyle_id) {
            $map['paystyle_id'] = $paystyle_id;
        }
        if ($merchant_name) {
            $map['m.merchant_name'] = array('LIKE', "%$merchant_name%");
        }
        if ($status !== "-1" && $status != "") {
            if ($status == "0") {
                $map['a.status'] = 0;
            }
            $map['a.status'] = $status;
        }
        if ($user_phone) {
            $map['user_phone'] = $user_phone;
        }
        if ($remark) {
            $map['remark'] = $remark;
        }
        if (I("mode")!= "") {
            if(I('mode')=='all_pos'){
                $map['mode'] = array('in','5,6,7,8,9,19');
            }else{
                $map['mode'] = I("mode");
            }
        }
        if (!$timestyle_id) {
            if ($start_time && $end_time) {
                $map['paytime'] = array(array('EGT', $start_time), array('ELT', $end_time));
            }
        } else {
            $map['paytime'] = array("between", $this->type_time($timestyle_id));
        }
        $map['brash'] = 1;
        $pays = $this->pay->alias('a')
            ->join("left join __MERCHANTS__ m on m.id=a.merchant_id")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->field("u.id as u_id,u.user_phone,m.merchant_name,a.*")
            ->where($map);
//        echo $this->pay->getLastSql();exit;
        $count = $pays->count();
        /*
         * 查询sql语句
         * echo $Pays->where($map)->_sql();
         * */
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));

//        join方法将数组进行变换了，得重新定义join
        $pay_selct = $this->pay->alias('a')
            ->join("left join __MERCHANTS__ m on m.id=a.merchant_id")
            ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
            ->field("u.id as u_id,u.user_phone,m.merchant_name,a.*")
            ->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->order("id desc")
            ->select();
        if (F("map") !== $map) {
            $this->data_cache_delete();
            $this->data_cache_add($count, $map);
        }
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("pays", $pay_selct);
        $this->display();
    }

//    分布缓存添加
    public function data_cache_add($count, $map)
    {
        $n = ceil($count / 3000);
        F("n", $n);
        F("map", $map);
        for ($i = 1; $i <= $n; $i++) {
            unset($pay);
            $left = 3000 * ($i - 1);
            $right = 3000;
            $pay = $this->pay->alias('a')
                ->join("left join __MERCHANTS__ m on m.id=a.merchant_id")
                ->join('left join __MERCHANTS_USERS__ u on m.uid = u.id')
                ->field("u.id as u_id,u.user_phone,m.merchant_name,a.id,a.paystyle_id,a.price,a.mode,a.remark,a.jmt_remark,a.status,a.status,a.paytime,a.cost_rate")
                ->where($map)
                ->order("paytime desc")
                ->limit($left, $right)
                ->select();
            $name = "pay_" . $i;
            F("$name", $pay);
        }
    }

//    分布缓存删除
    public function data_cache_delete()
    {
        $n = F("n");
        for ($i = 1; $i <= $n; $i++) {
            $name = "pay_" . $i;
            F("$name", null);
        }
        F("n", null);
        F("map", null);
    }


    public function add()
    {
        $this->display();
    }

    /*
     * 支付删除
     * */
    public function delete()
    {

        if ($_POST) {
            $ids = I("ids");
            foreach ($ids as $k => $v) {
                $this->pay->where("id=$v")->delete();
            }
            $this->success("恭喜你删除成功");
        }
        if ($_GET) {
            $id = I("id");
            $this->pay->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }
    /*
     *  改变支付状态
     * */
//    function change_status()
//    {
//        $pay = $this->pay;
//        $id 	= intval($_REQUEST['id']);
//        $status  =I("post.status") == 1 ? 0:1 ;
//        $this->ajaxReturn($status);
//        $this->Pay->where("id=$id")->setField('status', $status);
//    }
    public function change_status()
    {
        $id = I('post.id');
        $cate = $this->pay->find($id);
        $status = $cate['status'] == 0 ? 1 : 0;
        echo $status;
        $this->pay->where("id=$id")->setField('status', $status);
    }

    /**
     * 获得查询订单结果
     * @param $paystyle_id //支付工具，支付宝、微信
     * @param $out_trade_no //订单号
     * @param $mchid //商户ID
     * @return array         //结果信息
     */
    public function get_check_pay_result($paystyle_id, $out_trade_no, $mchid, $alipay_partner)
    {
        if ($paystyle_id == '1') {//微信
            $result = A("Pay/Barcode")->wz_query_order($out_trade_no, $mchid);
            $status_tag = $result['trade_state'];
        } else if ($paystyle_id == '2') {//支付宝
            $result = A("Pay/Barcode")->wzali_query_order($out_trade_no, $alipay_partner);
            $status_tag = $result['tradeStatus'];
        } else {
            $status_tag = '';
            $result = '';
        }
        return array('data' => $result, 'pay_status' => $status_tag);
    }

    public function test()
    {
        $pay_one = M()->query('SELECT * FROM `ypt_pay` where `status`=-2 and confirm_status=2 and paystyle_id=2 and bank=1 ORDER BY id DESC limit 10');
        if (!$pay_one) exit('end');
        foreach ($pay_one as $k => $v) {
            $this->check_order_pay($v);
        }

    }

    public function check_order_pay($pay_one = array())
    {
        $mch_info = M("merchants_cate")->where(array("merchant_id" => $pay_one['merchant_id']))->field("wx_mchid,alipay_partner")->find();
        $out_trade_no = $pay_one['remark'];

        if (!$mch_info['wx_mchid'] || !$mch_info['alipay_partner'] || !$out_trade_no) {
            echo "订单号信息不符合<br/>";
            file_put_contents('./data/log/wz/check_order_pay.log', date("Y-m-d H:i:s") . '订单号信息不符合' . $out_trade_no . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }
        $list = $this->get_check_pay_result($pay_one['paystyle_id'], $out_trade_no, $mch_info['wx_mchid'], $mch_info['alipay_partner']);
        $result = $list['data'];
        $status_tag = $list['pay_status'];
        file_put_contents('./data/log/wz/check_pay.log', date("Y-m-d H:i:s") . "订单支付检查结果" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if (in_array($status_tag, array('SUCCESS', '01'))) {
            $status = '1';
        } else if (in_array($status_tag, array('REFUND', 'NOTPAY', 'REVERSE', 'CLOSED', 'REVOK', '00', '03', '04', '05'))) {
            $status = '0';
        } else {
            $status = $pay_one['status'] > 0 ? $pay_one['status'] : '-2';
        }
        $res = $this->pay->where(array('id' => $pay_one['id']))->save(array('status' => $status, 'confirm_status' => '2'));
        echo '<pre/>';
        print_r($list['data']['msg']);
        print_r($res);
    }

    /**
     * 检查支付结果
     */
    public function check_pay()
    {
        $id = I('id', '0', 'intval');
        $pay_one = $this->pay->where("id=$id")->find();
        if (!$pay_one) $this->error("该订单不存在");
        if ($pay_one['bank'])
            //获取商户号
            $mch_info = M("merchants_cate")->where(array("merchant_id" => $pay_one['merchant_id']))->field("wx_mchid,alipay_partner")->find();
        $out_trade_no = $pay_one['remark'];
        if (!$mch_info || !$out_trade_no) $this->error("订单号信息不符合");
        //获取支付订单查询结果
        if ($pay_one['paystyle_id'] == '1') {//微信
            $result = A("Pay/Barcode")->wz_query_order($out_trade_no, $mch_info['wx_mchid']);
            $status_tag = $result['trade_state'];
        } else if ($pay_one['paystyle_id'] == '2') {//支付宝
            $result = A("Pay/Barcode")->wzali_query_order($out_trade_no, $mch_info['alipay_partner']);
            $status_tag = $result['tradeStatus'];
        } else {
            $status_tag = '';
            $result = '';
        }

        file_put_contents('./data/log/wz/check_pay.log', date("Y-m-d H:i:s") . "订单支付检查结果" . json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);

        //根据返回结果的支付状态，业务处理
        if (in_array($status_tag, array('SUCCESS', '01'))) {
            if ($pay_one['status'] == '0') $this->pay->where(array('id' => $pay_one['id']))->save(array('status' => 1, 'confirm_status' => '2'));
            $this->success("该订单支付成功", U('Contentadmin/index'));
        } else {
            if (in_array($status_tag, array('REFUND', 'NOTPAY', 'REVERSE', 'CLOSED', 'REVOK', '00', '03', '04', '05'))) $status = '0';
            else  $status = $pay_one['status'] > 0 ? $pay_one['status'] : '-2';
            //更新查询状态,2代表已经查询
            $this->pay->where(array('id' => $id))->save(array('status' => $status, 'confirm_status' => '2'));
            $res = $this->get_status_desc($status_tag, $pay_one['paystyle_id']);
            $result['msg'] = '【' . $result['msg'] . '】';
            if ($result['msg'] = '【订单不存在】') $result['msg'] = $result['msg'] . '。可能是支付接口出错,导致消费者下单失败,';
            $result['status'] = $res[0] ? $res[0] : '状态未知';
            $result['reason'] = $res[1] ? $result['msg'] . $res[1] : '原因未知';
            $result['order_id'] = $out_trade_no;
            $result['price'] = $pay_one['price'];
            $result['price_gold'] = $pay_one['price_gold'];
            $this->assign("result", $result);
            $this->display();
        }
    }


    /**
     * 解析支付状态，并给出解决方案
     * @param int $status
     * @param int $pay_type
     * @return string
     */
    public function get_status_desc($status = 0, $pay_type = 1)
    {
        $reason = '可能存在银行卡余额不足、注销、冻结,密码锁定等情况;网络延迟、信用卡余额不足或风控限制,用户取消支付等等。建议重新发起支付!';//失败原因
        $paying = '请联系相关技术人员核对是否支付成功';//失败解决方法
        $other = ',请联系相关技术人员!';//解决方法
        if ($pay_type == '1') {
            switch ($status) {
                case 'SUCCESS':
                    return array("支付成功", 'ok');
                    break;
                case 'REFUND':
                    return array("转入退款", $other);
                    break;
                case 'NOTPAY':
                    return array("未支付", $reason);
                    break;
                case 'REVERSE':
                    return array("已关闭", $other);
                    break;
                case 'CLOSED':
                    return array("已冲正", $other);
                    break;
                case 'REVOK':
                    return array("已撤销", $other);
                    break;
                default:
                    return array("支付失败", $other);
            }
        } else
            switch ($status) {
                case '00':
                    return array("交易创建，等待买家付款", $reason);
                    break;
                case '01':
                    return array("交易支付成功", '');
                    break;
                case '02':
                    return array("交易失败", $paying);
                    break;
                case '03':
                    return array("交易创建，等待买家付款", $reason);
                    break;
                case '04':
                    return array("未付款交易超时关闭，或支付完成后全额退款", $other);
                    break;
                case '05':
                    return array("交易结束，不可退款", $other);
                    break;
                default:
                    return array("支付失败", $other);
            }

    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

//                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    public function upload_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1000M');
//        $excel = F("pays");
        Vendor("PHPExcel.PHPExcel");
//        $number=3000;
//        $number_total=count($excel);
//        $n=ceil($number_total/$number);
        $n = F("n");
        //引入phpexcel类文件
        $objPHPExcel = new \PHPExcel();
        // 设置文件的一些属性，在xls文件——>属性——>详细信息里可以看到这些值，xml表格里是没有这些值的
        $objPHPExcel
            ->getProperties()//获得文件属性对象，给下文提供设置资源
            ->setCreator("Maarten Balliauw")//设置文件的创建者
            ->setLastModifiedBy("Maarten Balliauw")//设置最后修改者
            ->setTitle("Office 2007 XLSX Test Document")//设置标题
            ->setSubject("Office 2007 XLSX Test Document")//设置主题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")//设置备注
            ->setKeywords("office 2007 openxml php")//设置标记
            ->setCategory("Test result file");                //设置类别
        // 位置aaa  *为下文代码位置提供锚
        for ($a = 0; $a < $n; $a++) {
            $name = "pay_" . ($a + 1);
            if ($a != 0) {
                $objPHPExcel->createSheet();
                // 给表格添加数据
                $objPHPExcel->setactivesheetindex($a)
                    ->setCellValue('A1', 'ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付方式')
                    ->setCellValue('E1', '支付金额(元)')
                    ->setCellValue('F1', '商户费率')
                    ->setCellValue('G1', '支付样式')
                    ->setCellValue('H1', '流水号')
                    ->setCellValue('I1', '商户订单号')
                    ->setCellValue('J1', '支付状态')
                    ->setCellValue('K1', '支付时间');
                $excel_canshu = $objPHPExcel->getActiveSheet();

                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(20);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                $excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);

            } else {
                // 给表格添加数据
                $this_biao = $objPHPExcel->setActiveSheetIndex();             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
                $excel_canshu = $objPHPExcel->getActiveSheet();

                $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                $excel_canshu->getColumnDimension('A')->setWidth(20);
                $excel_canshu->getColumnDimension('B')->setWidth(20);
                $excel_canshu->getColumnDimension('C')->setWidth(20);
                $excel_canshu->getColumnDimension('D')->setWidth(20);
                $excel_canshu->getColumnDimension('E')->setWidth(30);
                $excel_canshu->getColumnDimension('F')->setWidth(30);
                $excel_canshu->getColumnDimension('G')->setWidth(30);
                $excel_canshu->getColumnDimension('H')->setWidth(30);
                $excel_canshu->getColumnDimension('I')->setWidth(40);
                $excel_canshu->getColumnDimension('J')->setWidth(40);
                $excel_canshu->getColumnDimension('K')->setWidth(40);

                $this_biao->setCellValue('A1', 'ID')
                    ->setCellValue('B1', '商户电话')
                    ->setCellValue('C1', '商户的名称')
                    ->setCellValue('D1', '支付方式')
                    ->setCellValue('E1', '支付金额(元)')
                    ->setCellValue('F1', '商户费率')
                    ->setCellValue('G1', '支付样式')
                    ->setCellValue('H1', '流水号')
                    ->setCellValue('I1', '商户订单号')
                    ->setCellValue('J1', '支付状态')
                    ->setCellValue('K1', '支付时间');
            }
            unset($excel);
            $excel = F("$name");
            $i = 2;
            foreach ($excel as $k => $v) {
                $objPHPExcel->setActiveSheetIndex($a)
                    ->setCellValue('A' . ($i), $v['id'])
                    ->setCellValue('B' . ($i), $v['user_phone'])
                    ->setCellValue('C' . ($i), $v['merchant_name'])
                    ->setCellValue('D' . ($i), $this->paystyle($v['paystyle_id']))
                    ->setCellValue('E' . ($i), $v['price'])
                    ->setCellValue('F' . ($i), $v['cost_rate'])
                    ->setCellValue('G' . ($i), $this->numberstyle($v['mode']))
                    ->setCellValue('H' . ($i), "F" . $v['remark'])
                    ->setCellValue('I' . ($i), $v['jmt_remark'])
                    ->setCellValue('J' . ($i), $this->pay_status($v['status']))
                    ->setCellValue('K' . ($i), date("Y-m-d H:s:i", $v['paytime']));
                $i++;
            }
            //得到当前活动的表,注意下文教程中会经常用到$objActSheet
            $objActSheet = $objPHPExcel->getActiveSheet();
            // 位置bbb  *为下文代码位置提供锚
            // 给当前活动的表设置名称
            $objActSheet->setTitle('洋仆淘对账表' . $a);

        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="洋仆淘对账表.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        $this->success("导出excel表格成功", U('index'));
    }


// 支付方式
    function pay_status($status)
    {
        switch ($status) {
            case -1:
                return "支付中";
            case 0:
                return "支付失败";
            case 1:
                return "支付成功";
            case 2:
                return "退款成功";
            case 3:
                return "退款失败";
            case 4:
                return "退款中";
            default:
                return "其他方式";
        }
    }

//支付方式判断
    function paystyle($paystyle_id)
    {
        switch ($paystyle_id) {
            case 1:
                return "微信支付";
            case 2:
                return "支付宝支付";
            case 3:
                return "银联钱包支付";
            case 4:
                return "京东支付";
            case 5:
                return "现金支付";
            default:
                return "其他方式";
        }
    }

//支付样式判断
    function numberstyle($number)
    {
        switch ($number) {
            case 0:
                return "台签";
            case 1:
                return "App扫码支付";
            case 2:
                return "App刷卡支付";
            case 3:
                return "收银扫码支付";
            case 4:
                return "收银现金支付";
            case 5:
                return "pos机主扫";
            case 6:
                return "pos机被扫";
            case 7:
                return "pos机现金支付";
            case 8:
                return "pos机其他支付";
            case 9:
                return "pos机刷银行卡";
            case 10:
                return "快速支付";
            case 11:
                return "小程序支付";
            case 12:
                return "会员充值";
            case 13:
                return "收银APP现金支付";
            case 14:
                return "收银APP余额支付";
            case 15:
                return "小白盒支付";
            case 16:
                return "台签余额支付";
            case 17:
                return "双屏用户付款码";
            case 18:
                return "双屏余额";
            case 19:
                return "pos余额";
            case 20:
                return "电子立牌支付";
            default:
                break;
        }
    }


}