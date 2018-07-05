<?php
/**
 * 我的
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/9
 * Time: 17:26
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;
use think\controller;
use think\log\driver\Test;


class TestController extends ApibaseController
{
    function t()
    {
        $a = array(
            'goods'=>array(
                array('goods_id'=>'1','num'=>'1','bar_code'=>'111','sku'=>'1111'),
                array('goods_id'=>'2','num'=>'2','bar_code'=>'222','sku'=>'2222')
            ),
            'dc_db_price'=>'1',
            'dc_ps_price'=>'2',
            'dc_ch_price'=>'3',
        );

        dump(json_encode($a));
    }

    /**
     * 读取excel数据
     */
    public function read_excel()
    {
        vendor("PHPExcel.PHPExcel");

        $inputFileName = "./test.xls";
        date_default_timezone_set('PRC');
// 读取excel文件
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);
        } catch(Exception $e) {
            die('加载文件发生错误：'."pathinfo($inputFileName,PATHINFO_BASENAME)".':'.$e->getMessage());
        }

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
// 获取一行的数据
        for ($row = 2; $row <= $highestRow; $row++){
            for ($col = 0; $col < $highestColumnIndex; $col++){
                $Data[$row][] =(string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        dump($Data);
    }
    /**
     * 获取代理下某月的交易汇总和返佣详情
     */
    private function get_merchants_maid_detail()
    {
        //所有代理uid
        $agent_uids = M('merchants_agent')->getField('uid',true);
        //一级代理uid
        $one_agent_uids = M('merchants_users')->where(array('id'=>array('in',$agent_uids),'agent_id'=>array('gt',0)))->getField('id',true);
        //dump($one_agent_uids);die;
        foreach ($one_agent_uids as &$agent_uid){
            $date_list = M('pay_month')->where(array('agent_id'=>$agent_uid))->order('date asc')->getField('date',true);
            $time = date('Y-m',M('merchants_agent')->where(array('uid'=>$agent_uid))->getField('add_time'));
            $uid = M()->query('select getchild('.$agent_uid.') as uids');
            $uids = $uid[0]['uids'];
            $mer_ids = $this->get_merchant_id($uids); //获取商户id
            $map['merchant_id'] = array('in',$mer_ids);
            $map['status'] = '1';
            if($mer_ids){
                while ($time<'2018-05'){
                    if(!in_array($time,$date_list)){
                        $info = $this->calc_maid($map,$this->get_appoint_month($time),$agent_uid);
                        if($info){
                            $add=array('agent_id'=>$agent_uid,'date'=>$time,'price'=>$info['price'],'nums'=>$info['num'],'rebate'=>$info['rebate'],'status'=>2,'add_time'=>strtotime($time)+907200);
                            M('pay_month')->add($add);
                        }
                    }
                    $time = date('Y-m',strtotime("$time +1 month"));
                }
            }
        }
    }
    //G3ERP本地客户端
    private function calc_maid($map,$time_array,$agent_uid)
    {
        $map['paytime'] = array('BETWEEN',$time_array);
        $month_pay = M('pay')->where($map)->field('price,cost_rate,paystyle_id,bank,cardtype')->select();
        $agent_rate = M('merchants_agent')->where(array('uid'=>$agent_uid))->field('wx_rate,ali_rate')->find();
        $rebate = '0';//费率总计
        $count = count($month_pay);//交易总笔数

        $price = '0';//交易总金额
        foreach ($month_pay as &$v) {
            $price += $v['price'];
            if ($v['bank'] == 11 && $v['paystyle_id'] == 3) {
                if ($v['cardtype'] == '00' || $v['cardtype'] == '03') {
                    $v['agent_rate'] = '0.41';
                } elseif ($v['cardtype'] == '01' || $v['cardtype'] == '02') {
                    $v['agent_rate'] = '0.53';
                }
                $bcdiv = $v['price'] * ($v['cost_rate'] - $v['agent_rate']);
            }else{
                if(!$v['cost_rate']){
                    $bcdiv = 0;
                }else{
                    $bcdiv = $v['price']*($v['cost_rate']-$agent_rate[$v['paystyle_id']==1?'wx_rate':'ali_rate']);
                }
            }
            $rebate = bcadd($rebate,bcdiv(($bcdiv),'100',5),5);
        }
        #条件不是数组就是商户，如果返佣为0则不显示
        if(!is_array($map['merchant_id']) && $rebate==0){
            return false;
        }else{
            return array('rebate'=>strval(round($rebate,2)),'num'=>"$count",'price'=>"$price");
        }
    }

    /** 获取指定年月的开始时间戳和结束时间戳
     * @param $y_m 年月,yyyy-mm格式
     * @return array
     */
    private function get_appoint_month($y_m)
    {
        ($start_time = strtotime( $y_m )) || $this->ajaxReturn(array('code'=>'error','msg'=>'时间格式有误'));
        $mdays = date( 't', $start_time );
        $end_time = strtotime(date( 'Y-m-' . $mdays . ' 23:59:59', $start_time ));

        return array($start_time,$end_time);
    }
    /**通过uid获取商户id
     * @param $uid 商户uid
     * @return mixed|string
     */
    private function get_merchant_id($uid){
        $where['uid'] = array('in',$uid);
        $id = M('merchants')->where($where)->getField('id',true);
        if($id){
            $id = implode(',',$id);
        }
        return $id;
    }

}
