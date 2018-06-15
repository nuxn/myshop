<?php
namespace Customer\Controller;

use Common\Controller\AdminbaseController;

class DetailadminController extends AdminbaseController
{

    protected $pay;
    protected $merchant;
    protected $user;
    protected $merchant_user;
    protected $cates;

    public function _initialize()
    {
        parent::_initialize();
        $this->pay = M('pay');
        $this->merchant = M('merchants');
        $this->user = M('users');
        $this->merchant_user = M('merchants_users');
        $this->cates = M("merchants_cate");
    }

    public function index()
    {
        ini_set('memory_limit', '1000M');
        $id = session('ADMIN_ID');
        F("merchant",null);
        F("de",null);
        F("total",null);
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        $status = I('status');
        $mode=I("mode");
        $remark = I('remark');
        $paystyle_id = I('paystyle');
        $timestyle_id=I('timestyle')?I('timestyle'):"";
        $checker=I("checker");
        $cate=I("cate")?I("cate"):"";
        if ($start_time > $end_time) {
            $this->error("开始时间不能小于结束时间");
        }
        if ($paystyle_id) {
            $map['p.paystyle_id'] = $paystyle_id;
        }
        $map['p.status']=array("in",array(1,2));
        if($mode!=""){
            $map['p.mode']=$mode;
        }
        if ($remark !=="") {
            $map['p.remark'] = $remark;
        }
        if(!$timestyle_id){
            if($start_time&&$end_time){
                $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
            }
        }else{
            $map['paytime'] =array("between",$this->type_time($timestyle_id));
        }
        if($cate){$map['p.mode'] =0;$map['p.cate_id'] =$cate;}
//        根据天下的商户的id找到对应的用户里面的电话
        $user = $this->user->where(array("id"=>$id))->find();
        $user_phone = $user['mobile'];
        if ($user_phone) {
//        根据电话找到商户的id
            $merchant_user = $this->merchant_user->where(array("user_phone"=>$user_phone))->find();
            if(!$merchant_user)exit("非有关人员");
            $uid = $merchant_user['id'];
//        根据商户的id找到商户的具体信息
            $map['uid'] = $uid;
            $mid=M("merchants")->where(array("uid"=>$uid))->getField("id");
            $cates = $this->cates->where(array("merchant_id"=>$mid))->field("id")->select();
            if($checker){
                if($checker==$uid) {
                    $map['p.merchant_id']=$mid;
                    $map['p.checker_id']=0;
                }else{
                    $map['p.checker_id']=$checker;
                }
            }
            $merchant = $this->merchant
                ->alias("m")
                ->join("right join __PAY__ p on m.id=p.merchant_id")
                ->field('p.cate_id,p.jmt_remark,p.id,p.checker_id,p.merchant_id,p.mode,p.paystyle_id,p.remark,p.price,p.paytime,p.status,m.uid')
                ->order("id desc")
                ->where($map)
                ->select();
            $total=0;
            $de=0;
            foreach ($merchant as $k =>&$v){
                if($v['status'] == 1){$total+=$v['price'];}
                if($v['status'] == 2){$de+=$v['price'];}
//                    if($v['cherck_name']=="")$v['cherck_name']="商家自己";
                $v['cherck_name']=$this->get_cherck_name($v['checker_id']);
                $v['paystyle_id'] = $this->paystyle($v['paystyle_id']);
                $v['status'] = $this->pay_status($v['status']);
                if($v['mode'] !=0 )$v['cate_id']="非台卡";
                $v['mode'] = $this->numberstyle($v['mode']);
            }
            $total = sprintf("%.2f",$total);
            $de = sprintf("%.2f",$de);
            F("merchant",$merchant);
            F("de",$de);
            F("total",$total);
            $checkers=$this->get_all_cherck($uid);
            $count=count($merchant);
            $page = $this->page($count, 20);
            $list=array_slice($merchant,$page->firstRow,$page->listRows);
            $this->assign("page", $page->show('Admin'));
            $this->assign("formget", array_merge($_GET, $_POST));
            $this->assign("merchant",$list);
            $this->assign("de",$de);
            $this->assign("cates",$cates);
            $this->assign("checkers",$checkers);
            $this->assign("total",$total);
        }

        $this->display();
    }

    public function upload_excel()
    {
//        $start_time = strtotime(I('start_time'));
//        $end_time = strtotime(I('end_time'));
//        $status = I('status');
//        $remark = I('remark');
//        $mode=I("mode");
//        $paystyle_id = I('paystyle');
//        $checker=I("checker");
//        if ($start_time > $end_time) {
//            $this->error("开始时间不能小于结束时间");
//        }
//        if ($paystyle_id) {
//            $map['p.paystyle_id'] = $paystyle_id;
//        }
//        if($status!=""){
//            $map['p.status']=$status;
//        }
//        if($mode!=""){
//            $map['p.mode']=$mode;
//        }
//        if ($remark !=="") {
//            $map['p.remark'] = $remark;
//        }
//        if ($start_time && $end_time) {
//            $map['p.paytime'] = array(array('EGT', $start_time), array('ELT', $end_time));
//        }
//        $id = session('ADMIN_ID');
////        根据天下的商户的id找到对应的用户里面的电话
//        $user = $this->user->where("id=$id")->find();
//        $user_phone = $user['mobile'];
//        if ($user_phone) {
//            $merchant_user = $this->merchant_user->where("user_phone=$user_phone")->find();
//            $uid = $merchant_user['id'];
////        根据商户的id找到商户的具体信息
//            $map['uid'] = $uid;
//            if($checker){
//                if($checker==$uid) {
//                    $mid=M("merchants")->where("uid=$uid")->getField("id");
//                    $map['p.merchant_id']=$mid;
//                    $map['p.checker_id']=0;
//                }else{
//                    $map['p.checker_id']=$checker;
//                }
//            }
//            $excel = $this->merchant
//                ->alias("m")
//                ->join("right join __PAY__ p on m.id=p.merchant_id")
//                ->field('p.jmt_remark,p.id,p.checker_id,p.merchant_id,p.mode,p.paystyle_id,p.remark,p.price,p.paytime,p.status,m.uid')
//                ->where($map)
//                ->select();
//            $total=0;
//            $de=0;
//            foreach ($excel as $k =>&$v){
//                if($v['status'] == 1){$total+=$v['price'];}
//                if($v['status'] == 2){$de+=$v['price'];}
//                $v['cherck_name']=$this->get_cherck_name($v['checker_id']);
//                $v['paystyle_id']=$this->paystyle($v['paystyle_id']);
//                $v['status']=$this->pay_status($v['status']);
//                $v['mode']=$this->numberstyle($v['mode']);
//                $v['paytime']=date("Y-m-d H:i:s",$v['paytime']);
//                $v['remark']="YPT__".$v['remark'];
//            }
//            unset($v);
//            $total=sprintf("%.2f", $total)."元";
//            $de=sprintf("%.2f", $de)."元";
            $excel = F("merchant");
            $total = F("total");
            $de = F("de");
            Vendor("PHPExcel.PHPExcel");
            $number=3000;
            $number_total=count($excel);
            $n=ceil($number_total/$number);
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
            for($a=0;$a <$n;$a++) {

                $i = 2;
                if ($a != 0) {
                    $objPHPExcel->createSheet();
                    // 给表格添加数据
                    $objPHPExcel->setactivesheetindex($a)
                        ->setCellValue('A1', 'ID')
                        ->setCellValue('B1', '支付类型')
                        ->setCellValue('C1', '支付样式')
                        ->setCellValue('D1', '台卡号')
                        ->setCellValue('E1', '支付金额(元)')
                        ->setCellValue('F1', '收银员')
                        ->setCellValue('G1', '流水号')
                        ->setCellValue('H1', '订单号')
                        ->setCellValue('I1', '支付状态')
                        ->setCellValue('J1', '支付时间');
                    $excel_canshu = $objPHPExcel->getActiveSheet();

                    $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                    $excel_canshu->getColumnDimension('A')->setWidth(20);
                    $excel_canshu->getColumnDimension('B')->setWidth(20);
                    $excel_canshu->getColumnDimension('C')->setWidth(20);
                    $excel_canshu->getColumnDimension('D')->setWidth(20);
                    $excel_canshu->getColumnDimension('E')->setWidth(20);
                    $excel_canshu->getColumnDimension('F')->setWidth(30);
                    $excel_canshu->getColumnDimension('G')->setWidth(30);
                    $excel_canshu->getColumnDimension('H')->setWidth(30);
                    $excel_canshu->getColumnDimension('I')->setWidth(30);
                    $excel_canshu->getColumnDimension('J')->setWidth(40);
                    $excel_canshu->getColumnDimension('K')->setWidth(40);
                    $excel_canshu->getColumnDimension('L')->setWidth(40);

                }else{
                    // 给表格添加数据
                    $this_biao = $objPHPExcel->setActiveSheetIndex();             //设置第一个内置表（一个xls文件里可以有多个表）为活动的
                    $excel_canshu = $objPHPExcel->getActiveSheet();

                    $excel_canshu->getDefaultRowDimension()->setRowHeight(30);
                    $excel_canshu->getColumnDimension('A')->setWidth(20);
                    $excel_canshu->getColumnDimension('B')->setWidth(20);
                    $excel_canshu->getColumnDimension('C')->setWidth(20);
                    $excel_canshu->getColumnDimension('D')->setWidth(20);
                    $excel_canshu->getColumnDimension('E')->setWidth(20);
                    $excel_canshu->getColumnDimension('F')->setWidth(30);
                    $excel_canshu->getColumnDimension('G')->setWidth(30);
                    $excel_canshu->getColumnDimension('H')->setWidth(30);
                    $excel_canshu->getColumnDimension('I')->setWidth(30);
                    $excel_canshu->getColumnDimension('J')->setWidth(40);
                    $excel_canshu->getColumnDimension('K')->setWidth(40);
                    $excel_canshu->getColumnDimension('L')->setWidth(40);

                    $this_biao->setCellValue('A1', 'ID')
                        ->setCellValue('B1', '支付类型')
                        ->setCellValue('C1', '支付样式')
                        ->setCellValue('D1', '台卡号')
                        ->setCellValue('E1', '支付金额(元)')
                        ->setCellValue('F1', '收银员')
                        ->setCellValue('G1', '流水号')
                        ->setCellValue('H1', '订单号')
                        ->setCellValue('I1', '支付状态')
                        ->setCellValue('J1', '支付时间')
                        ->setCellValue('K1', '支付成功:' . $total)
                        ->setCellValue('L1', '退款成功:' . $de);
                }
                $ab =array_slice($excel,$a*$number,$number);
                foreach ($ab as $k => $v) {

                    //设置税收
                    //            $tax = taxcount($v['id'],1);

                    // 图片生成

                    //$objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                    //            $objDrawing[$k]->setPath('../'.$v['img']);
                    // 设置宽度高度
                    //            $objDrawing[$k]->setHeight(50);//照片高度
                    //            $objDrawing[$k]->setWidth(50); //照片宽度
                    /*设置图片要插入的单元格*/
                    //            $objDrawing[$k]->setCoordinates('B'.$i);
                    // 图片偏移距离
                    //            $objDrawing[$k]->setOffsetX(12);
                    //            $objDrawing[$k]->setOffsetY(12);
                    //$objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
//                    if ($number * $a <= $k && $k < $number * ($a + 1)) {
                        $objPHPExcel->setActiveSheetIndex($a)
                            ->setCellValue('A' . ($i), $v['id'])
                            ->setCellValue('B' . ($i), $v['paystyle_id'])
                            ->setCellValue('C' . ($i), $v['mode'])
                            ->setCellValue('D' . ($i), $v['cate_id'])
                            ->setCellValue('E' . ($i), $v['price'])
                            ->setCellValue('F' . ($i), $v['cherck_name'])
                            ->setCellValue('G' . ($i), "F" . $v['remark'])
                            ->setCellValue('H' . ($i), $v['jmt_remark'])
                            ->setCellValue('I' . ($i), $v['status'])
                            ->setCellValue('J' . ($i), date("Y-m-d H:s:i",$v['paytime']));
//                    }

                    //                ->setCellValue('D'.$i,$v['guige'])
                    //                ->setCellValue('E'.$i,$v['pcode'])
                    //                ->setCellValueExplicit('F'.$i,$v['goods_tiaoxm'],\PHPExcel_Cell_DataType::TYPE_STRING)
                    //                ->setCellValue('G'.$i,fencheng($v['id']))//调用自定义函数计算价格
                    //                ->setCellValue('H'.$i,$tax);
                    $i++;
                }
                //得到当前活动的表,注意下文教程中会经常用到$objActSheet
                $objActSheet = $objPHPExcel->getActiveSheet();
                // 位置bbb  *为下文代码位置提供锚
                // 给当前活动的表设置名称
                $objActSheet->setTitle('洋仆淘对账表'.$a);

            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="洋仆淘对账表.xls"');
            header('Cache-Control: max-age=0');

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        $this->success("导出excel表格成功",U('index'));
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
            default:
                break;
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
            case 5:
                return "现金支付";
            default:
                return "其他方式";
                break;
        }
    }

// 支付方式
    function pay_status($status){
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
    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     */
    function type_time($type){
        switch ($type){
            case 0:
                return ;
            case 1:
                //  今天
                $beginToday= mktime(0,0,0,date('m'),date('d'),date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                return array($beginToday,$endToday);
            case 2:
                //昨天
                $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                return array($beginYesterday,$endYesterday);
            case 3:
                //        本周
                $beginThisweek=mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

//                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek,$endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
                $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

//                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth,$endToday);
            case 5:
                //上周
                $beginLastweek=mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
                $endLastweek=mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
                return array($beginLastweek,$endLastweek);
            case 6:
                //上月
                $beginLastmonth =  mktime(0, 0 , 0,date("m")-1,1,date("Y"));
                $endLastmonth =  mktime(23,59,59,date("m") ,0,date("Y"));
                return array($beginLastmonth,$endLastmonth);
        }
    }

//    得到所有收银员的内容
    function get_all_cherck($uid)
    {
        $users=M("merchants_users");
        $merchant_name=$users->where("id=$uid")->getField("user_name");
        $cherck=array();
        $cherck[]=array('id'=>$uid,'name'=>$merchant_name);
        $uids =$users->where("pid='$uid'")->field("id")->order("id asc")->select();
        foreach ($uids as $k =>$v){
            $ab=$v['id'];
            $user_name=$users->where("id=$ab")->getField("user_name");
            if($user_name)$cherck[]=array("id"=>$ab,"name"=>$user_name);
        }
        return $cherck;
    }

    function get_cherck_name($checker_id)
    {
        if($checker_id ==0)
        {
            $cherck_name="商家自己";
        }else{
            $cherck_name=M("merchants_users")->where("id=$checker_id")->getField("user_name");
        }
        return $cherck_name;
    }
}