<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class ShopadminController extends AdminbaseController{
	protected $merchant;
    protected $pay;
    function _initialize() {
        parent::_initialize();
        $this->merchant = M("merchants");
        $this->pay = M('pay');

    }

	public function index(){
        ini_set('memory_limit', '2000M');
        $start_time=strtotime(I('post.start_time'));
        $end_time=strtotime(I('post.end_time'));
        $keyword=I('keyword');
        $paystyle_id=I('paystyle');
        $merchant_name=I("merchant_name");

        if($paystyle_id){
            $map['paystyle_id']=$paystyle_id;
        }
        if($keyword){
            $map['user_phone']=$keyword;
        }
        if($merchant_name){
            $map['a.merchant_name']=array('LIKE',"%$merchant_name%");
        }
        if($start_time&&$end_time){
            $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }
        $map['b.status']=1;
        $merchant = M('merchants')->alias("a")
            ->join('__PAY__ b on a.id=b.merchant_id')
            ->join('left join __MERCHANTS_USERS__ u on a.uid=u.id')
            ->field('u.user_phone,a.id,a.merchant_name,b.price,b.merchant_id,b.paytime,b.paystyle_id,b.status')
            ->order("a.id asc")->where($map)->select();
        $totals=array();
        $demo=0;
        /*
        * 引入$total 对数组进行重新组装，返回商家的流水数据
        * */
        foreach ($merchant as $k=>$v){
            if($k==0){
                $totals[$demo]['id']=$v['id'];
                $v['status'] == 1?$totals[$demo]['number']=1:$totals[$demo]['number']=0;
                $totals[$demo]['user_phone']=$v['user_phone'];
                $v['status'] == 1?$totals[$demo]['totals_price']=$v['price']:$totals[$demo]['totals_price']=0;
                $totals[$demo]['merchant_name']=$v['merchant_name'];

            }else{
                if($totals[$demo]['id'] != $v['id']){
                    $demo++;
                    $totals[$demo]['id']=$v['id'];
                    $v['status'] == 1?$totals[$demo]['number']=1:$totals[$demo]['number']=0;
                    $totals[$demo]['user_phone']=$v['user_phone'];
                    $totals[$demo]['merchant_name']=$v['merchant_name'];
                    $v['status'] == 1?$totals[$demo]['totals_price']=$v['price']:$totals[$demo]['totals_price']=0;
                }else{
                    if( $v['status'] == 1)$totals[$demo]['number']++;
                    if( $v['status'] == 1)$totals[$demo]['totals_price']+=$v['price'];
                }
            }
        }
        $count=count($totals);
        $page = $this->page($count, 20);
        $list=array_slice($totals,$page->firstRow,$page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("totals",$list);
        $this->display();
	}

    private function cachedata($file_name,$param)
    {
        $path = $this->get_date_dir();
        file_put_contents($path . $file_name, $param, LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/cache/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y");
        if (file_exists($Y)) mkdir($Y, 0777, true);

        return $Y . '/';
    }

    public function excel()
    {
        $title = '商户'.date('Ymd');
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:applicationnd.ms-excel");
        header("Content-Disposition:attachment;filename={$title}.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        $str1 = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
           xmlns:x="urn:schemas-microsoft-com:office:excel"
           xmlns="http://www.w3.org/TR/REC-html40">
        <head>
           <meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
           <meta http-equiv=Content-Type content="textml; charset=GBK">
           <!--[if gte mso 9]><xml>
           <x:ExcelWorkbook>
           <x:ExcelWorksheets>
             <x:ExcelWorksheet>
             <x:Name></x:Name>
             <x:WorksheetOptions>
               <x:DisplayGridlines/>
             </x:WorksheetOptions>
             </x:ExcelWorksheet>
           </x:ExcelWorksheets>
           </x:ExcelWorkbook>
           </xml><![endif]-->
        </head>';
        //echo iconv('UTF-8', 'GBK', $str1);
//        $_pre = array(
//            'id' => '序号',
//            'merchant_name' => '商户名称',
//            'merchant_jiancheng' => '商户简称',
//            'industry' => '行业类别',
//            'province' => '省份',
//            'city' => '城市',
//            'county' => '区/县',
//            'address' => '联系地址',
//            'operator_name' => '联系人',
//            'user_phone' => '联系电话',
//            'operator_name' => '法人/负责人姓名',
//            'id_number' => '法人／负责人身份证号码',
//            'rate' => '商户费率（‰）',
//            'account_name' => '结算账号户名',
//            'bank_account' => '结算账户开户行',
//            'bank_no' => '人行支行联行号',
//            'bank_account_no' => '结算账号',
//            'id_number' => '开户人身份证号（若为个人账号则填，若为对公账户则空）',
//            'business_license_number' => '商户营业执执照号码',
//        );
        $style  = "style='text-align:center;'";
        $header = "
            <tr>
            <td {$style} >ID</td>
            <td {$style} >商户电话</td>
            <td {$style} >商户的简称</td>
            <td {$style} >支付总额(元)</td>
            <td {$style} >支付总笔数</td>
            </tr>
        ";
        $arr = $this->total();
        $str = '';
        foreach($arr as $k=>$v){
            $str .= '
                <tr>
                <td>'.$v['id'].'</td>
                <td>'.$v['user_phone'].'</td>
                <td>'.$v['merchant_name'].'</td>
                <td>'.$v['totals_price'].'</td>
                <td>'.$v['number'].'</td>
                </tr>
            ';
        }
        $content = iconv('UTF-8', 'GBK', $header) . iconv('UTF-8', 'GBK//IGNORE', $str); // $header.$str;
        echo '<table border="1">' . $content . '</table>';
        exit;
    }

    public function total()
    {

        ini_set('memory_limit', '1000M');
        $start_time=strtotime(I('post.start_time'));
        $end_time=strtotime(I('post.end_time'));
        $keyword=I('keyword');
        $paystyle_id=I('paystyle');
        $merchant_name=I("merchant_name");

        if($paystyle_id){
            $map['paystyle_id']=$paystyle_id;
        }
        if($keyword){
            $map['user_phone']=$keyword;
        }
        if($merchant_name){
            $map['a.merchant_name']=array('LIKE',"%$merchant_name%");
        }
        if($start_time&&$end_time){
            $map['paytime'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
        }
        $map['b.status']=1;
        $this->merchant->alias("a")
            ->join('__PAY__ b on a.id=b.merchant_id')
            ->join('left join __MERCHANTS_USERS__ u on a.uid=u.id')
            ->field('u.user_phone,a.id,a.merchant_name,b.price,b.merchant_id,b.paytime,b.paystyle_id,b.status')
            ->order("a.id asc");
        $merchant=$this->merchant->where($map)->select();
        $totals=array();
        $demo=0;
        /*
        * 引入$total 对数组进行重新组装，返回商家的流水数据
        * */
        foreach ($merchant as $k=>$v){
            if($k==0){
                $totals[$demo]['id']=$v['id'];
                $v['status'] == 1?$totals[$demo]['number']=1:$totals[$demo]['number']=0;
                $totals[$demo]['user_phone']=$v['user_phone'];
                $v['status'] == 1?$totals[$demo]['totals_price']=$v['price']:$totals[$demo]['totals_price']=0;
                $totals[$demo]['merchant_name']=$v['merchant_name'];

            }else{
                if($totals[$demo]['id'] != $v['id']){
                    $demo++;
                    $totals[$demo]['id']=$v['id'];
                    $v['status'] == 1?$totals[$demo]['number']=1:$totals[$demo]['number']=0;
                    $totals[$demo]['user_phone']=$v['user_phone'];
                    $totals[$demo]['merchant_name']=$v['merchant_name'];
                    $v['status'] == 1?$totals[$demo]['totals_price']=$v['price']:$totals[$demo]['totals_price']=0;
                }else{
                    if( $v['status'] == 1)$totals[$demo]['number']++;
                    if( $v['status'] == 1)$totals[$demo]['totals_price']+=$v['price'];
                }
            }
        }

        return $totals;
    }
}