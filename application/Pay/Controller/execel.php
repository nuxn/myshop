<?php
$title = 'pay_user_'.date('ymd');
header("Content-type:application/octet-stream");
header("Accept-Ranges:bytes");
header("Content-type:applicationnd.ms-excel");
header("Content-Disposition:attachment;filename={$title}.xls");
header("Pragma: no-cache");
header("Expires: 0");
$str1 = '';
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
echo iconv('UTF-8', 'GBK', $str1);

$style  = "style='text-align:center;background:#6FB3E0;'";
$header = "<tr><td colspan=7 style='color:red;font-size:20px;text-align:center;'>联讯通客户收入详情表</td></tr>
                    <tr>
                    <td {$style} rowspan='2'>序号</td>
                    <td {$style} rowspan='2'>客户名</td>
                    <td {$style} rowspan='2'>客户手机号</td>
                    <td {$style} rowspan='2'>推荐人</td>
                    <td style='background:#6FB3E0;' rowspan='2'>收入总额（账户余额+冻结金额+提现总额）</td>
                    <td {$style} rowspan='2'>账户余额</td>
                    <td {$style} rowspan='2'>冻结金额</td>
                    <td {$style} rowspan='2'>提现总额</td>
                    <td {$style} colspan='4'>每天提现记录</td>
                    </tr>

                    <tr>
                    <td {$style} >订单号</td>
                    <td {$style} >提现金额</td>
                    <td {$style} >提现状态</td>
                    <td {$style} >提现时间</td>
                    </tr>

                    ";
$arr = array();
$wallet = M('merchant_wallet', 'pay_', 'DB_CONFIG_PAY')->getField('tenant_id,balance,total_balance,total_withdraw');
$user = M('merchant', 'pay_', 'DB_CONFIG_PAY')->getField('tenant_id,real_name,phone,maker_user');
$withdraw = M('merchant_withdraw', 'pay_', 'DB_CONFIG_PAY')->select();
foreach($withdraw as $k=>$v){
    $arr[$v['tenant_id']][] = $v;
}

/* echo "<pre/>";
 print_r($user);exit;*/
$str = '';
$i = 1;
foreach($arr as $k=>$v){
    if(is_array($v) && count($v) > 0){
        $j = 1;
        $num = count($v);
        foreach($v as $value){

            $status = array(1=>'提现中',2=>'成功',3=>'失败');
            if($j == 1){
                $str .= '<tr>
                                <td rowspan="'.$num.'">'.$i.'</td>
                                <td rowspan="'.$num.'">'.$user[$k]['real_name'].'</td>
                                <td rowspan="'.$num.'">'.$user[$k]['phone'].'</td>
                                <td rowspan="'.$num.'">'.$user[$k]['maker_user'].'</td>
                                <td rowspan="'.$num.'">'.$wallet[$k]['total_balance'].'</td>
                                <td rowspan="'.$num.'">'.$wallet[$k]['balance'].'</td>
                                <td rowspan="'.$num.'">'.$wallet[$k]['freeze_money'].'</td>
                                <td rowspan="'.$num.'">'.$wallet[$k]['total_withdraw'].'</td>

                                <td style="vnd.ms-excel.numberformat:@">'.$value['order_no'].'</td>
                                <td>'.$value['amount'].'</td>
                                <td>'.$status[$value['status']].'</td>
                                <td>'.$value['create_time'].'</td>
                             </tr>
                            ';
            }else{
                $str .= '<tr>
                                <td style="vnd.ms-excel.numberformat:@">'.$value['order_no'].'</td>
                                <td>'.$value['amount'].'</td>
                                <td>'.$status[$value['status']].'</td>
                                <td>'.$value['create_time'].'</td>
                             </tr>
                            ';
            }
            $j++;
        }
    }
    $i++;
}

$content = iconv('UTF-8', 'GBK', $header) . iconv('UTF-8', 'GBK', $str); // $header.$str;
echo '<table>' . $content . '</table>';
exit;
?>
