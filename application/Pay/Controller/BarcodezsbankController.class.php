<?php

namespace Pay\Controller;

use Common\Controller\HomebaseController;
use Common\Lib\Subtable;

/**支付
 * Class BarcodeController
 * @package Pay\Controller
 */
class BarcodezsbankController extends HomebaseController
{
    public $path;//打印日志路径
    private $pay_model;

    function _initialize() {
        parent::_initialize();
        header("Content-type:text/html;charset=utf-8");
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/zsbank/curl/';
        $this->id =10000220719;
        $this->apikey='27c539b541d35ddc03c7951fa22248b5';
        $this->agent_ylzf_uid = "1388";     // 云来智付 user id
        $this->pay_model =M(Subtable::getSubTableName('pay'));
    }
    //商户进件接口
    public function mchinlet(){
        $uid=$_POST['uid'];
        //获取进件数据
        $data=M('merchants_zspay')->where(array('merchant_id'=>$uid))->find();
        if(empty($data)){
            $account['code']='error';
            $account['message']="";
            $this->ajaxReturn($account);
        }
        unset($data['id']);
        unset($data['merchant_id']);
        unset($data['pay_type_1']);
        unset($data['pay_type_2']);
        unset($data['pay_type_3']);
        unset($data['pay_type_4']);
        unset($data['pay_type_7']);
        unset($data['pay_type_8']);
        unset($data['pay_type_9']);
        unset($data['into_type']);
        unset($data['payment_type4']);
        unset($data['ul_mchid']);
        $data['notify_url']="http://sy.youngport.com.cn/index.php?g=Pay&m=Barcodezsbank&a=mchinlet_notify";
        $data['id_card_img_b']="@".realpath("./data/upload/".$data['id_card_img_b']);
        $data['id_card_img_f']="@".realpath("./data/upload/".$data['id_card_img_f']);
        $data['license_img']="@".realpath("./data/upload/".$data['license_img']);
        if($data['annex_img1']){
            $data['annex_img1']="@".realpath("./data/upload/".$data['annex_img1']);
        }
        if($data['annex_img2']){
            $data['annex_img2']="@".realpath("./data/upload/".$data['annex_img2']);
        }
        if($data['annex_img3']){
            $data['annex_img3']="@".realpath("./data/upload/".$data['annex_img3']);
        }
        if($data['annex_img4']){
            $data['annex_img4']="@".realpath("./data/upload/".$data['annex_img4']);
        }
        if($data['annex_img5']){
            $data['annex_img5']="@".realpath("./data/upload/".$data['annex_img5']);
        }
        //验证签名
        $length=strlen(urldecode(http_build_query($data)));
        $sign=$this->sign('POST','/v1/mchinlet',$this->gmdate(),$length);
        $header=$this->header($sign,$length);
        $url="http://ulineapi.cms.cmbxm.mbcloud.com/v1/mchinlet";
        //第一次请求获取错误码中content-length
        $result=$this->httpRequst($url,$data,$header);
        $result=json_decode($result,true);
        $Authorization=$result['content']['Authorization']['0'];
        $Authorization=explode(',',$Authorization);
        $Authorization=$Authorization[2];
        $Authorization=explode('&',$Authorization);
        $length=$Authorization[1];
        $length=(int)($length);
        $sign=$this->sign('POST','/v1/mchinlet',$this->gmdate(),$length);
        $header=$this->header($sign,$length);
        //第二次请求进件
        $result=$this->httpRequst($url,$data,$header);
        $result=json_decode($result,true);
        if($result['code']=='200'){
            file_put_contents('./data/log/zsbank/into/add_into.logs', date("Y-m-d H:i:s") . '发送信息:' .json_encode($result).PHP_EOL, FILE_APPEND | LOCK_EX);
            $ul_mchid=$result['content']['ul_mchid'];

            $sql ="UPDATE ypt_merchants_zspay SET ul_mchid='$ul_mchid' WHERE merchant_id = '$uid'";
            M("")->query($sql);
            $account['code']='200';
            $account['message']='进件成功';
            $this->ajaxReturn($account);
        }else{
            $content=$result['content'];
            $str='';
            foreach ($content as $key => $value) {
                foreach ($value as $k => $v) {
                    $str.=$v.",";
                }
            }
            $account['code']='error';
            $account['message']=$str;
            $this->ajaxReturn($account);
        }
    }
    //修改商户进件信息
    public function update(){
        $uid=$_POST['uid'];
        $merchant_list=M('merchants_zspay')->where(array('merchant_id'=>$uid))->find();
        if(empty($data)){
            $account['code']='error';
            $account['message']="";
            $this->ajaxReturn($account);
        }
        $data['mch_shortname']=$merchant_list['mch_shortname'];
        $data['city']=$merchant_list['city'];
        $data['province']=$merchant_list['province'];
        $data['address']=$merchant_list['address'];
        $data['mobile']=$merchant_list['mobile'];
        $data['email']=$merchant_list['email'];
        $data['service_phone']=$merchant_list['service_phone'];
        $data['bank_no']=$merchant_list['bank_no'];
        $data['balance_type']=$merchant_list['balance_type'];
        $data['balance_name']=$merchant_list['balance_name'];
        $data['balance_account']=$merchant_list['balance_account'];
        $data['id_card_no']=$merchant_list['id_card_no'];
        $data['contact']=$merchant_list['contact'];
        $data['payment_type1']=$merchant_list['payment_type1'];
        $data['payment_type2']=$merchant_list['payment_type2'];
        $data['payment_type3']=$merchant_list['payment_type3'];
        $data['payment_type4']=$merchant_list['payment_type4'];
        $data['payment_type7']=$merchant_list['payment_type7'];
        $data['payment_type8']=$merchant_list['payment_type8'];
        $data['payment_type9']=$merchant_list['payment_type9'];
        $data['license_num']=$merchant_list['license_num'];
        $data['license_start_date']=$merchant_list['license_start_date'];
        $data['license_period']=$merchant_list['license_period'];
        $data['license_scope']=$merchant_list['license_scope'];
        $data['notify_url']="http://sy.youngport.com.cn/index.php?g=Pay&m=Barcodezsbank&a=mchinlet_notify";
        $data['id_card_img_b']="@".realpath("./data/upload/".$merchant_list['id_card_img_b']);
        $data['id_card_img_f']="@".realpath("./data/upload/".$merchant_list['id_card_img_f']);
        $data['license_img']="@".realpath("./data/upload/".$merchant_list['license_img']);
        //验证签名
        $length=strlen(urldecode(http_build_query($data)));
        $sign=$this->sign('POST','/v1/mchinlet/update',$this->gmdate(),$length);
        $header=$this->header($sign,$length);
        $url="http://ulineapi.cms.cmbxm.mbcloud.com/v1/mchinlet/update";
        //第一次请求获取错误码中content-length
        $result=$this->httpRequst($url,$data,$header);
        $result=json_decode($result,true);
        $Authorization=$result['content']['Authorization']['0'];
        $Authorization=explode(',',$Authorization);
        $Authorization=$Authorization[2];
        $Authorization=explode('&',$Authorization);
        $length=$Authorization[1];
        $sign=$this->sign('POST','/v1/mchinlet/update',$this->gmdate(),$length);
        $header=$this->header($sign,$length);
        //第二次请求进件
        $result=$this->httpRequst($url,$data,$header);
        $result=json_decode($result,true);
        if($result['code']=='200'){
            file_put_contents('./data/log/zsbank/into/update_into.logs', date("Y-m-d H:i:s") . '发送信息:' .json_encode($result).PHP_EOL, FILE_APPEND | LOCK_EX);
            $ul_mchid=$result['content']['ul_mchid'];
            M('merchants_zspay')->where(array("merchant_id"=>$uid))->save(array('ul_mchid'=>$ul_mchid));
            $account['code']='success';
            $account['message']='修改成功';
            $this->ajaxReturn($account);
        }else{
            $content=$result['content'];
            $str='';
            foreach ($content as $key => $value) {
                foreach ($value as $k => $v) {
                    $str.=$v.",";
                }
            }
            $account['code']='error';
            $account['message']=$str;
            $this->ajaxReturn($account);
        }
    }
    //商户进件审核加激活接口
    public function mchinlet_notify(){
        //商户进件审核回调
        if($_POST['event_type']=='check'){
            file_put_contents('./data/log/zsbank/into/check.logs', date("Y-m-d H:i:s") . '发送信息:' .json_encode($_POST).PHP_EOL, FILE_APPEND | LOCK_EX);
            $getsign=$_POST['sign'];
            $para_temp['mch_id']=$_POST['mch_id'];
            $para_temp['status']=$_POST['status'];
            $para_temp['comment']=$_POST['comment'];
            $para_temp['message_id']=$_POST['message_id'];
            $sign=$this->getSignVeryfy($para_temp);
            if($getsign==$sign){
                if($para_temp['status']==1){
                    $data['into_type']=1;
                }elseif($para_temp['status']==2){
                    $data['into_type']=3;
                }
                M('merchants_zspay')->where(array('ul_mchid'=>$para_temp['mch_id']))->save();
            }
            //商户进件开通回调
        }elseif($_POST['event_type']=='active'){
            file_put_contents('./data/log/zsbank/into/active.logs', date("Y-m-d H:i:s") . '发送信息:' .json_encode($_POST).PHP_EOL, FILE_APPEND | LOCK_EX);
            $getsign=$_POST['sign'];
            $para_temp['mch_id']=$_POST['mch_id'];
            $para_temp['datas']=$_POST['datas'];
            $para_temp['message_id']=$_POST['message_id'];
            $sign=$this->getSignVeryfy($para_temp);
            //验签之后判断各个支付状态
            if($sign==$getsign){
                $data=json_decode($para_temp['datas'],true);
                foreach ($data as $key => $value) {
                    if($data[$key]['pay_type']==1){
                        if($datas[$key]['status']==true){
                            $into['pay_type_1']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_1']=1;
                        }
                    }
                    if($data[$key]['pay_type']==2){
                        if($datas[$key]['status']==true){
                            $into['pay_type_2']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_2']=1;
                        }
                    }
                    if($data[$key]['pay_type']==3){
                        if($datas[$key]['status']==true){
                            $into['pay_type_3']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_3']=1;
                        }
                    }
                    if($data[$key]['pay_type']==4){
                        if($datas[$key]['status']==true){
                            $into['pay_type_4']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_4']=1;
                        }
                    }
                    if($data[$key]['pay_type']==7){
                        if($datas[$key]['status']==true){
                            $into['pay_type_7']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_7']=1;
                        }
                    }
                    if($data[$key]['pay_type']==8){
                        if($datas[$key]['status']==true){
                            $into['pay_type_8']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_8']=1;
                        }
                    }
                    if($data[$key]['pay_type']==9){
                        if($datas[$key]['status']==true){
                            $into['pay_type_9']=2;
                        }else if($data[$key]['status']==false){
                            $into['pay_type_9']=1;
                        }
                    }
                }
                M('merchants_zspay')->where(array('ul_mchid'=>$para_temp['mch_id']))->save($into);
                $sign=$this->sign('GET','/v1/mch/mchpaykey',$this->gmdate(),0);
                $header=array(
                    "Authorization:Uline ".$this->id.":".$sign,
                    "Date:".$this->gmdate()
                );
                $mch_id=$_POST['mch_id'];
                $url="http://ulineapi.cms.cmbxm.mbcloud.com/v1/mch/mchpaykey?mch_id=".$mch_id;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $output = curl_exec($ch);
                curl_close($ch);
                $output=json_decode($output,true);
                if($output['code']=='200'){
                    file_put_contents('./data/log/msbank/pay_key.logs', date("Y-m-d H:i:s") .'发送信息:' .json_encode($output).PHP_EOL, FILE_APPEND | LOCK_EX);
                    $mch_pay_key=$output['content']['mch_pay_key'];
                    $iv=substr($mch_pay_key,0,16);
                    $str=substr($mch_pay_key,16);
                    $data['data']=$str;
                    $data['type']='aes';
                    $data['arg']="m=cbc_pad=pkcs7_block=128_p=".$this->apikey."_i=".$iv."_o=0_s=utf-8_t=1";
                    //拼接数据到ase解密网站去请求数据
                    $data=http_build_query($data);
                    $url="http://tool.chacuo.net/cryptaes?".$data;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    $output = curl_exec($ch);
                    curl_close($ch);
                    $output=json_decode($output,true);
                    $mch_pay_key=$output['data'][0];
                    $sql ="UPDATE ypt_merchants_zspay SET mch_pay_key = '$mch_pay_key'  WHERE ul_mchid='$mch_id'";
                    M("")->query($sql);
                    $this->ajaxReturn($output);
                }
            }
        }
    }
    //商户进件信息查询接口
    public function mch(){
        $mch_id=$_POST['mch_id'];
        if(!$mch_id){
            $res['code']=400;
            $res['error']='商户号不存在';
            $this->ajaxReturn($res);
        }
        $sign=$this->sign('GET','/v1/mch',$this->gmdate(),0);
        $header=array(
            "Authorization:Uline ".$this->id.":".$sign,
            "Date:".$this->gmdate()
        );
        $data=http_build_query($data);
        $url="http://ulineapi.cms.cmbxm.mbcloud.com/v1/mch?mch_id=".$mch_id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        curl_close($ch);
        $output=json_decode($output,true);
        if($output['code']=='200'){
            $this->ajaxReturn($output);
        }else{
            $re['code']=$output['code'];
            $re['error']=$output['error']['type'];
            $this->ajaxReturn($output);
        }
    }
    //获取商户支付密钥
    public function mchpaykey(){
	    $id=$_POST['id'];
        $re= M('merchants_zspay')->where(array('id'=>$id))->find();
        $mch_id=$re['ul_mchid'];
        if(!$mch_id){
            $res['code']=400;
            $res['error']='商户号不存在';
            $this->ajaxReturn($res);
        }
        $sql ="UPDATE ypt_merchants_zspay SET into_type='3'  WHERE ul_mchid='$mch_id'";
        M("")->query($sql);
        $this->ajaxReturn(array('info'=>'ok'));
    }
    //打款记录
    public function remittance_query(){
        exit('Repealed!');
        $ul_mchid_list=M('merchants_zspay')->select();
		$ul_mchid_list1=M('merchants_zspay_hd')->select();
		$ul_mchid_list = array_merge($ul_mchid_list,$ul_mchid_list1);
         $fileName="/nasdata/www/youngshop/data/log/zsbank/bill/dakuan.logs";
        file_put_contents($fileName, date("Y-m-d H:i:s").json_encode($ul_mchid_list). PHP_EOL, FILE_APPEND | LOCK_EX);
        $time=date("Ymd",strtotime("-1 day"));
        $row=M('zs_bank_logs')->where(array('need_pay_date'=>$time))->find();
        //如果当前操作日期已存在 则停止 防止重复请求
        if($row){
            return;
        }
        foreach ($ul_mchid_list as $key => $value) {
            $data['mch_id']=$value['ul_mchid'];
            $data['mch_pay_key']=$value['mch_pay_key'];
            $data['nonce_str']=time();
            $data['date']=$time;
            $result=$this->bills_remittance_query($data);
            $result=$this->xmlToArray($result);
            $result['mch_id']=$value['ul_mchid'];
            if($result['return_code']==='SUCCESS'){
                $list[]=$result;
            }
        }
        $bank_logs_sql="insert into ypt_zs_bank_logs  (mch_id,fee,content,need_pay_date,channel) VALUES";
        $length_1=strlen($bank_logs_sql);
        if($list){
            foreach ($list as $key => $value) {
                $cont=$list[$key]['remittances']['remittance']['records']['record'];
                if($cont){
                    if(count($cont)==2){
                        foreach ($cont as $k => $v) {
                            $bank_logs_sql.="('".$list[$key]['mch_id']."','".($cont[$k]['fee']/100)."','".$cont[$k]['status']."','".$cont[$k]['need_pay_date']."','".$cont[$k]['channel']."'),";
                        }
                    }else{
                        $bank_logs_sql.="('".$list[$key]['mch_id']."','".($cont['fee']/100)."','".$cont['status']."','".$cont['need_pay_date']."','".$cont['channel']."'),";
                    }
                }
            }
            $bank_logs_sql_length=strlen($bank_logs_sql);
            $bank_logs_sql=substr($bank_logs_sql,0,$bank_logs_sql_length-1);
            if($bank_logs_sql_length>$length_1+2){
                M('')->query($bank_logs_sql);
            }
        }
    }
    //招商银行对账单接口
    public function bills(){
        exit('Repealed!');
        $ul_mchid_list=M('merchants_zspay')->select();
		$ul_mchid_list1=M('merchants_zspay_hd')->select();
		$ul_mchid_list = array_merge($ul_mchid_list,$ul_mchid_list1);
        $fileName="/nasdata/www/youngshop/data/log/zsbank/bill/bill.logs";
        file_put_contents($fileName, date("Y-m-d H:i:s").json_encode($ul_mchid_list). PHP_EOL, FILE_APPEND | LOCK_EX);
        $logs_sql="insert into ypt_zs_logs  (pay_time,mch_id,zs_order_sn,re_order_sn,order_sn,member_sn,pay_type,order_type,price,zs_refund_order_sn,refund_order_sn,refund_price,body,sx_price,code_fen) VALUES";
        $length_1=strlen($logs_sql);
        $daylogs_sql="insert into ypt_zs_daylogs  (all_number,all_price,all_refund_price,price,all_sx_price,pay_time,mch_id,mch_name) VALUES";
        $length_2=strlen($daylogs_sql);
        $time=date("Ymd",strtotime("-1 day"));
        $row=M('zs_daylogs')->where(array('pay_time'=>$time))->find();
        //如果当前操作日期已存在 则停止 防止重复请求
        if($row){
            return;
        }
        foreach ($ul_mchid_list as $key => $value) {
            $data['mch_id']=$value['ul_mchid'];
            $data['mch_pay_key']=$value['mch_pay_key'];
            $data['nonce_str']=time();
            $data['bill_date']=$time;
            $result=$this->pay_bills($data);
            $data=explode("\n",$result);
            $count=count($data);
            $header=$data['0'];
            $footer=$data[$count-2];
            //拼接nto ypt_zs_daylogs  sql
            if($footer){
                $ter=explode(',',$footer);
                $daylogs_sql.="('".$ter[0]."','".($ter[1]/100)."','".($ter[2]/100)."','".($ter[3]/100)."','".($ter[4]/100)."','".$time."','".$value['ul_mchid']."','".$value['mch_name']."'),";
            }
            $los=$data[$count-3];
            unset($data['0']);
            unset($data[$count-1]);
            unset($data[$count-2]);
            unset($data[$count-3]);
            //拼接nto ypt_zs_logs  sql
            if($data){
                foreach ($data as $k => $v) {
                    $data[$k]=explode(',',$data[$k]);
                    $log_time=explode('.',$data[$k][0]);
                    $log_time=$log_time[0];
                    $logs_sql.="('".$log_time."','".$data[$k][2]."','".$data[$k][5]."','".$data[$k][6]."','".$data[$k][7]."','".$data[$k][8]."','".$data[$k][9]."','".$data[$k][10]."','".($data[$k][13]/100)."','".$data[$k][15]."','".$data[$k][16]."','".($data[$k][17]/100)."','".$data[$k][20]."','".($data[$k][22]/100)."','0.".$data[$k][23]."'),";
                }   
            }
        }
        $daylogs_sql_length=strlen($daylogs_sql);
        if($daylogs_sql_length>$length_2+2){
            $daylogs_sql=substr($daylogs_sql,0,$daylogs_sql_length-1);
            M('')->query($daylogs_sql);
            $logs_sql_length=strlen($logs_sql);
            $logs_sql=substr($logs_sql,0,$logs_sql_length-1);
            M('')->query($logs_sql);
        }
    }

     //微信支付界面跳转
    public function qr_weixipay()
    {
        //这里直接获得openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $id = I("id");
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $openid = $this->_get_openid();
            $this->getOffer($merchant, $openid);

            $this->assign('openid', $openid);
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', I('id'));
            $this->display();
        }
    }

    /**
     * 微信支付
     * 
     */
    public function wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
//        先获取openid防止 回调
        if (I("seller_id") == "") {
            $openid = $this->_get_openid();
            $sub_openid = $openid;
            $id = I("id");
            $res = M('merchants_cate')->where("id=$id and status=1")->find();
            $price = I("price");
            $data['mode'] = 1;
            $data['checker_id'] = I("checker_id");
        }
        if (I('seller_id') !== "") {
            $sub_openid = I('openid');
            $id = I('seller_id');
            $res = M('merchants_cate')->where("id=$id and status=1")->find();
            $price = I('price');
            $data['checker_id'] = $res['checker_id'];
            $data['mode'] = 0;
        }
        $data['bank'] =4;
        if(I("checker_id")){$data['checker_id'] = I("checker_id");} //app上的台签带上收银员的信息
        if (I("jmt_remark")) { //金木堂定单号
            $data['jmt_remark'] = I("jmt_remark");
        } else {
            $data['jmt_remark'] = I('memo','');
        }
        $data['bill_date'] =date("Ymd",time());
        $payModel = $this->pay_model;
        $remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        //            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        //$data['customer_id'] = $sub_openid;
        $data['customer_id'] =D("Api/ScreenMem")->add_member("$sub_openid",$res['merchant_id']);
        $data['paystyle_id'] = 1;
        $data['cost_rate']=$this->cost_rate_1($res['wx_mchid'],1);
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['new_order_sn'] =  $remark . rand(1000, 9999);
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['phone_info'] = $_SERVER['HTTP_USER_AGENT'];
        $data['paytime'] = time();
        $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
        $data['subject'] = $good_name;
        $remark_exists = $payModel->where(array('remark'=>$remark))->find();
        if(!$remark_exists){
            $payModel->add($data);
        }
        $config = C('WEIXINPAY_CONFIG');
        //拼接微信jsapi数据
        $bank['mch_id']=$res['wx_mchid'];
        $bank['sub_appid']=$config['APPID'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$good_name;
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['wx_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['time_start']=date("YmdHis");
        $bank['trade_type']='JSAPI';
        $bank['sub_openid']=$sub_openid;

        $res=$this->weixin_c_b_pay($bank);
        file_put_contents('./data/log/zsbank/common/'.date("Y-m-d").'_wenxin.logs', date("Y-m-d H:i:s") . '接收参数:' .$res.PHP_EOL, FILE_APPEND | LOCK_EX);

        //xml 转数据
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/common/'.date("Y-m-d").'_wenxin.logs', date("Y-m-d H:i:s") . '接收参数:' .json_encode($res).PHP_EOL, FILE_APPEND | LOCK_EX);
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                $body=$res['js_prepay_info'];
                $this->assign('body',$body);
                $this->assign('price',$price);
                $this->assign('openid',$sub_openid);
                $this->assign('remark',$remark);
                $this->assign('mid', $data['merchant_id']);
                $this->display("wz_pay");     
            }else{
                echo '<script type="text/javascript">alert("请求服务器错误!")</script>';
            }
        
    }

        /**
     * 双屏扫码支付
     */
    public function two_wz_pay()
    {
        header("Content-type:text/html;charset=utf-8");
//        先获取openid防止 回调
        $order_id = I("order_id");
		//file_put_contents('./data/log/test.log', date("Y-m-d H:i:s") . '---222---' . $order_id . PHP_EOL, FILE_APPEND | LOCK_EX);
        $id = I("id");
        $mode = I("mode",3);
        if ($order_id != "") {
			//$code = M('order')->where("order_id='$order_id'")->getField('coupon_code');
			//file_put_contents('./data/log/test.log', date("Y-m-d H:i:s") . '---333---' . $code . PHP_EOL, FILE_APPEND | LOCK_EX);
			//if($code){A("Apiscreen/Twocoupon")->use_card($code);}
            $openid = $this->_get_openid();
            $order = M("order");
            $remark = $order->where("order_id='$order_id'")->getField("order_sn");
            $sub_openid = $openid;
            $data['order_id'] = $order_id;
            $data['mode'] = $mode;
            $data['checker_id'] = I("checker_id");
            $order = $order->where("order_id='$order_id'")->find();
            $price = $order['order_amount'];
            $res = M('merchants_cate')->where("id=$id and status=1")->find();
//            插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];
            //$data['customer_id'] = $sub_openid;
            $data['customer_id'] =D("Api/ScreenMem")->add_member("$openid",$res['merchant_id']);
            $data['paystyle_id'] = 1;
            $data['price'] = $price;
            $data['remark'] = $remark;
            $data['status'] = 0;
            $data['cate_id'] = $res['id'];
            $data['bank'] = 4;
            if(I("jmt_remark")){ //金木堂定单号
                $data['jmt_remark'] = I("jmt_remark");
            }else {
                $data['jmt_remark'] = I('memo','');
            }
            $wzcost_rate =$this->cost_rate_1($res['wx_mchid'],1);
            if ($wzcost_rate) {
                $data['cost_rate'] = $wzcost_rate;
            };
            $data['paytime'] = time();
            $data['bill_date'] = date("Ymd", time());
            $order_sn = $remark . rand(1000, 9999);
            $data['new_order_sn'] = $order_sn;
			
			//预防pay表订单重复 
            $remark_exists = $this->pay_model->where(array('remark'=>$remark))->find();
            if(!$remark_exists){
                $this->pay_model->add($data);
            }
            //由于回调地址的原因，将id存入session中

            $good_name = "向" . $res['jianchen'] . "支付" . $price . "元";
//       支付订单提交的数据交互
            $mchid = $res['wx_mchid'];
        }
        //使用统一支付接口()
        $config = C('WEIXINPAY_CONFIG');
        //拼接微信jsapi数据
        $bank['mch_id']=$res['wx_mchid'];
        $bank['sub_appid']=$config['APPID'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$good_name;
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['wx_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['time_start']=date("YmdHis");
        $bank['trade_type']='JSAPI';
        $bank['sub_openid']=$sub_openid;
        $res=$this->weixin_c_b_pay($bank);
        //xml 转数据
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/common/'.date("Y-m-d").'_wenxin.logs', date("Y-m-d H:i:s") . '接收参数:' .json_encode($res).PHP_EOL, FILE_APPEND | LOCK_EX);
        $sign=$res['sign'];
        unset($res['sign']);
        $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
        if($sign==$resign){
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                $body=$res['js_prepay_info'];
                $this->assign('body',$body);
                $this->assign('price',$price);
                $this->assign('openid',$sub_openid);
                $this->assign('order_id',$order_id);
                $this->assign('remark',$remark);
                $this->assign('mid', $data['merchant_id']);
                $this->display("wz_pay");     
            }else{
                echo '<script type="text/javascript">alert("请求服务器错误!")</script>';
            }
        }else{
            echo '<script type="text/javascript">alert("签名失败!")</script>';
        }
    }

    // 支付宝双拼    
    public function screen_wz_alipay()
    {
        $seller_id = I('seller_id');//二维码对应的id 
        $order_id = I('order_id');
        $checker_id = I('checker_id', 0, 'intval');
        $mode = I('mode',1);
        if (!$seller_id) exit('seller_id不能为空!');
        if (!$order_id) exit('订单号不能为空!');

        $res = M('merchants_cate')->where(array("id" => $seller_id,'status'=>1))->find();
        if (!$res) exit('二维码信息不存在!');
        $checker_id = $checker_id ? $checker_id : intval($res['checker_id']);
        $orderModel = M("order");
        $order_info = $orderModel->where(array("order_id" => $order_id))->find();
        if (!$order_info['order_sn']) exit('订单不存在!');

        $pay_info = $this->pay_model->where(array("remark" => $order_info['order_sn']))->find();
        if ($pay_info) {
            $data = array(
                "merchant_id" => $pay_info['merchant_id'],
                "price" => $pay_info['price'] ? $pay_info['price'] : '0.01',
                "remark" => $pay_info['remark'],
                "subject" => $pay_info['subject'] ? $pay_info['subject'] : "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "checker_id" => $checker_id,
            );
            $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("paystyle_id" => 2));
        } else {
            $wzcost_rate = $this->cost_rate_1($res['wx_mchid'],7);
            $data = array(
                "merchant_id" => $res['merchant_id'],
                "price" => $order_info['order_amount'] ? $order_info['order_amount'] : '0.01',
                "subject" => "向" . $res['jianchen'] . "支付" . $order_info['order_amount'] . "元",
                "mode" => $mode,//双屏扫码
                "paystyle_id" => "2",//支付宝
                "order_id" => $order_id,//订单编号
                "remark" => $order_info['order_sn'],//订单号唯一
                "status" => "0",//未付款
                "paytime" => time(),
                "add_time" => time(),
                "cate_id" => $res['id'],
                "checker_id" => $checker_id,
                "bank" => 4,
                "cost_rate" => $wzcost_rate?$wzcost_rate:'',
                "jmt_remark" => I('jmt_remark')?I('jmt_remark'):'',
            );
            $this->pay_model->add($data);
        }

        $data['remark'] = $data['remark'];
        $this->pay_model->where(array("remark" => $order_info['order_sn']))->save(array("new_order_sn" => $data['remark']));
        //构造要请求的参数数组,无需改动
        $bank['mch_id']=$res['alipay_partner'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$data['subject'];
        $bank['out_trade_no']=$data['remark'];
        $bank['total_fee']=$data['price']*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['alipay_public_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $res=$this->alipay_precreate($bank);
//        xml 转数据
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/common/'.date("Y-m-d").'_alipay.logs', date("Y-m-d H:i:s") . '接收参数:' .json_encode($res).PHP_EOL, FILE_APPEND | LOCK_EX);
        $sign=$res['sign'];
         unset($res['sign']);
         $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
         if($sign==$resign){
             if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                 $url=$res['qr_code'];
                 header("Location: $url");
             }else{
                 echo '<script type="text/javascript">alert("请求服务器错误!")</script>';
             }
         }else{
          echo '<script type="text/javascript">alert("签名失败!")</script>';
         }
    }
    //支付宝手机扫码支付
     public function qr_to_alipay()
    {
        $seller_id = I('seller_id');//二维码对应的id
        $checker_id = I('checker_id', 0, 'intval');
        if (!$seller_id) exit('seller_id不能为空!');
        $type = I("type");
        file_put_contents(get_date_dir($this->path) . date("Y_m_d_") . 'post.log', date("Y-m-d H:i:s") . '  post信息:  请求参数' . json_encode(I("")) . PHP_EOL, FILE_APPEND | LOCK_EX);
        $where = array('status'=>1);
        $res = M('merchants_cate')->where('id=' . $seller_id)->where($where)->find();
        if (!$res) exit('二维码信息不存在!');
        $res['checker_id'] = $checker_id ? $checker_id : intval($res['checker_id']);
        $price = I('price');
        $res['price'] = $price ? $price : '0.01';
        $res['order_sn'] = I('order_sn');
        if ($type || $type == '0') $res['mode'] = '1';
        else $res['mode'] = '0';
        I("jmt_remark")?$res['jmt_remark']=I("jmt_remark"):$res['jmt_remark']="";
        $this->_wz_alipay($res);


    }

    private function _wz_alipay($res)
    {
        $payModel = $this->pay_model;
        $where = array(
            "merchant_id" => $res['merchant_id'],
            "paystyle_id" => "2",
            "price" => $res['price'],
            "status" => "0",
            "mode" => $res['mode'],
            "cate_id" => $res['id'],
        );
        $where['subject'] = "向" . $res['jianchen'] . "支付" . $res['price'] . "元";
        //金木堂订单号
        if($res['jmt_remark']){
            $where['jmt_remark']=$res['jmt_remark'];
        } else {
            $where['jmt_remark'] = I('memo','');
        }
        $remark = I('order_sn',date('YmdHis') . rand(100000, 999999));
        $where['remark'] = $remark;
        $where['paytime'] = time();
        $where['checker_id'] = $res['checker_id'];
        $where['bank'] = 4;
        $wzcost_rate = $this->cost_rate_1($res['wx_mchid'],7);
        if ($wzcost_rate) $where['cost_rate'] = $wzcost_rate;
        $payModel->add($where);
        //构造要请求的参数数组，无需改动
        $bank['mch_id']=$res['alipay_partner'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']= $where['subject'];
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$res['price']*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['alipay_public_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $res=$this->alipay_precreate($bank);
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/common/'.date("Y-m-d").'_alipay.logs', date("Y-m-d H:i:s") . '接收参数:' .json_encode($res).PHP_EOL, FILE_APPEND | LOCK_EX);
        $sign=$res['sign'];
        unset($res['sign']);
        $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
        if($sign==$resign){
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                $url=$res['qr_code'];
                header("Location: $url");
            }else{
                echo '<script type="text/javascript">alert("请求服务器错误!")</script>';
            }
        }else{
            echo '<script type="text/javascript">alert("签名失败!")</script>';
        }
    }

    //支付宝支付界面跳转
    public function qr_alipay()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            $id = I('id');
            $merchant = M("merchants_cate")->where("id=$id")->find();
            $this->assign("checker_id", I('checker_id'));
            $this->assign("merchant", $merchant);
            $this->assign('seller_id', $id);
                $this->display();
        }
    }

    public function wz_micropay($id, $price, $auth_code, $checker_id,$jmt_remark,$mode)
    {
        header('Content-Type:application/json; charset=utf-8');
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id and status=1")->find();
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--res--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        if ($jmt_remark) { //金木堂定单号
            $data['jmt_remark'] = $jmt_remark;
        }
        $remark = date('YmdHis') . rand(100000, 999999);
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        if ($mode) {
            $data['mode'] = $mode;
        }else{
            $data['mode'] = 2;  
        }
        $data['paytime'] = time();
        $data['bank']=4;
        $data['cost_rate']=$this->cost_rate_1($res['wx_mchid'],2);
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--data--'.json_encode($data). PHP_EOL, FILE_APPEND | LOCK_EX);
        $merchant_code = $res["wx_mchid"];
        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);
        $bank['mch_id']=$res['wx_mchid'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$product;
        $bank['detail']=$product;
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['wx_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['auth_code']=$auth_code;
        $bank['scene']='bar_code';
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--bank--'.json_encode($bank). PHP_EOL, FILE_APPEND | LOCK_EX);
        $res=$this->weixin_b_c_pay($bank);
        $res=$this->xmlToArray($res);
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--res2--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        	$customer_id=D("Api/ScreenMem")->add_member($res['sub_openid'], $data['merchant_id']);
            $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id,'transId'=>$res['transaction_id']));
           	A("App/PushMsg")->push_pay_message($remark);
            return array("code" =>"success", "msg" => "成功", "data" =>'支付成功');
        }else if($res['return_code']=='SUCCESS' && $res['result_code']="FAIL"){
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--输入密码--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id']=$bank['mch_id'];
            $payData['out_trade_no']=$remark;
            //$payData['transaction_id']=$res['transaction_id'];
            $payData['nonce_str']=time().rand(10000,99999);
            $payData['mch_pay_key']=$bank['mch_pay_key'];
            $payData['merchant_id']=$data['merchant_id'];
            return $this->pay_password($payData);
        }else{
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--招商支付失败--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" =>"error", "msg" => "失败", "data" => $res['errorMsg']);
        }
    }
	
	public function pos_wz_micropay($id, $price, $auth_code, $checker_id,$mode=8,$order_sn)
    {
        header('Content-Type:application/json; charset=utf-8');
        if (!$auth_code) {
            $this->error('参数错误!');
        }
        if (!$id) {
            return array("code" => "error", "msg" => "失败", "data" => "支付失败");
        }
        $res = M('merchants_cate')->where("merchant_id=$id and status=1")->find();
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--res--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        if ((int)$res['merchant_id'] == 0) {
            return array("code" => "error", "msg" => "失败", "data" => "还未申请支付业务");
        }
        //$remark = date('YmdHis') . rand(100000, 999999); 
        $remark = $order_sn;
//            插入数据库的数据
        $data['merchant_id'] = (int)$res['merchant_id'];
        $data['checker_id'] = $checker_id;
        $data['paystyle_id'] = 1;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['status'] = 0;
        $data['cate_id'] = $res['id'];
        $data['mode'] = $mode;
        $data['paytime'] = time();
        $data['bank']=4;
        $data['cost_rate']=$this->cost_rate_1($res['wx_mchid'],2);
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--data--'.json_encode($data). PHP_EOL, FILE_APPEND | LOCK_EX);
        $merchant_code = $res["wx_mchid"];
        $key = $res["wx_key"];
        $product = "向" . $res['jianchen'] . "支付" . $price . "元";
        $this->pay_model->add($data);
        $bank['mch_id']=$res['wx_mchid'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$product;
        $bank['detail']=$product;
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['wx_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['auth_code']=$auth_code;
        $bank['scene']='bar_code';
		file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--bank--'.json_encode($bank). PHP_EOL, FILE_APPEND | LOCK_EX);
        $res=$this->weixin_b_c_pay($bank);
        $res=$this->xmlToArray($res);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功1--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        	$customer_id=D("Api/ScreenMem")->add_member($res['sub_openid'], $data['merchant_id']);
            $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $customer_id,'transId'=>$res['transaction_id']));
           	A("App/PushMsg")->push_pay_message($remark);
            return array("code" =>"success", "msg" => "成功", "data" =>'支付成功');
        }else if($res['return_code']=='SUCCESS' && $res['result_code']="FAIL"){
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--输入密码2--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id']=$bank['mch_id'];
            $payData['out_trade_no']=$remark;
            //$payData['transaction_id']=$res['transaction_id'];
            $payData['nonce_str']=time().rand(10000,99999);
            $payData['mch_pay_key']=$bank['mch_pay_key'];
            $payData['merchant_id']=$data['merchant_id'];
            $this->pay_password($payData,$order_sn);
        }else{
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--招商支付失败3--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            return array("code" =>"error", "msg" => "失败", "data" => $res['errorMsg']);
        }
    }

    private function pay_password($payData,$order_sn=null){
        $queryTimes = 6;
        while ($queryTimes >= 0) {
            $bank['mch_pay_key']=$payData['mch_pay_key'];
            $bank['out_trade_no']=$payData['out_trade_no'];
            //$bank['transaction_id']=$payData['transaction_id'];
            $bank['nonce_str']=$payData['nonce_str'];
            $bank['mch_id']=$payData['mch_id'];
            $res=$this->wechat_query($bank);
            $res=$this->xmlToArray($res);
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付查询5--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                //如果需要等待5s后继续
                $succResult=$res['trade_state'];
                //支付成功
                if($succResult=='SUCCESS'){
                	$customer_id=D("Api/ScreenMem")->add_member($res['sub_openid'], $payData['merchant_id']);
                    $brr=array("status" => "1", "paytime" => time(), "customer_id" => $customer_id,'transId'=>$res['transaction_id']);
                    file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功6--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save($brr);
                    A("App/PushMsg")->push_pay_message($res['out_trade_no']);
                    if($order_sn){
                        $pay = $this->pay_model->where(array('remark'=>$order_sn))->order("paytime desc")->field("price,paystyle_id,mode,remark,paytime,status,jmt_remark")->find();
                        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("code" => "success", "msg" => "成功", "data" => $pay)));
                    }else{
                        return array("code" =>"success", "msg" => "成功", "data" =>'支付成功7');
                    }
                //转入退款 
                }else if($succResult=='REFUND'){
                //未支付 
                }else if($succResult=='NOTPAY'){
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" =>"error", "msg" => "失败", "data" =>'客户关闭已支付');
                //已关闭
                }else if($succResult=='CLOSED'){
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" =>"error", "msg" => "失败", "data" =>'支付失败8');
                //已冲正
                }else if($succResult=='REVERSE'){
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" =>"error", "msg" => "失败", "data" =>'支付失败9');
                //已撤销 
                }else if($succResult=='REVOKED'){
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" =>"error", "msg" => "失败", "data" =>'支付失败10');
                //用户支付中 
                }else if($succResult=='USERPAYING'){
                    if($queryTimes==0){
                        break;
                    }else{
                        sleep(5);
                        $queryTimes--;
                        continue;
                    }
                //支付失败
                }else if($succResult=='PAYERROR'){
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
                    return array("code" =>"error", "msg" => "失败", "data" =>'支付失败11');
                }
            }else{
                $this->pay_model->where(array("remark" => $payData['out_trade_no']))->save(array("status" => "-2"));
                return array("code" =>"error", "msg" => "失败", "data" =>'支付失败12');
            }
        }   
        $bank['mch_pay_key']=$payData['mch_pay_key'];
        $bank['out_trade_no']=$payData['out_trade_no'];
        //$bank['transaction_id']=$payData['transaction_id'];
        $bank['nonce_str']=$payData['nonce_str'];
        $bank['mch_id']=$payData['mch_id'];
        $res=$this->wechat_reverse($bank);
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付撤销13--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
            $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
            return array("code" =>"error", "msg" => "失败", "data" =>'交易时间过长,支付失败');;
        }
    }
     /**
     * 支付宝条码支付
     */
    public function ali_barcode_pay($id, $price, $auth_code, $checker_id,$jmt_remark="",$mode=2)
    {
        header('Content-Type:text/html; charset=utf-8');
        $payModel = $this->pay_model;

        //接收参数
        $id = $id ? $id : I('id', 0);
        $price = $price ? $price : I("price", 0);
        $auth_code = $auth_code ? $auth_code : I("auth_code");
        $checker_id = $checker_id ? $checker_id : I("checker_id");

        if (!$auth_code || !$id || $price < 0.01) $this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));

        $res = M('merchants_cate')->where("merchant_id=$id and status=1")->find();

        if (!$res['alipay_partner']) return array("flag" => false, "msg" => "未开通或未绑定支付宝支付");

        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            $remark = date('YmdHis') . rand(100000, 999999);//订单号
            //插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];//商户ID
            $data['customer_id'] = $auth_code;//买方账号ID
            $data['checker_id'] = $checker_id;//收银员的ID
            $data['paystyle_id'] = 2;//支付方式 1是微信 2是支付宝
            $data['price'] = $price;
            $data['remark'] = $remark;//订单号
            $data['status'] = 0;//待付款
            $data['cate_id'] = $res['id'];//支付样式,台签类别
            $data['mode'] = $mode;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 4;
            if($jmt_remark){ //金木堂定单号
                $data['jmt_remark'] = $jmt_remark;
            }
            $wzcost_rate =$this->cost_rate_1($res['alipay_partner'],8);
            if ($wzcost_rate) $data['cost_rate'] = $wzcost_rate;
            $payModel->add($data);
        } else
        $remark = $data['remark'];
        //拼接支付宝数据
        $bank['mch_id']=$res['alipay_partner'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$data['subject'];
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['alipay_public_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['auth_code']=$auth_code;
        $bank['scene']='bar_code';
        $res=$this->alipay_micropay($bank);
        $res=$this->xmlToArray($res);
        $sign=$res['sign'];
        unset($res['sign']);
        $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
       	if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $res['sub_openid'],'transId'=>$res['transaction_id']));
            A("App/PushMsg")->push_pay_message($remark);
            return array("flag" =>true, "msg" => "成功", "data" =>'支付成功');
        }else if($res['return_code']=='SUCCESS' && $res['result_code']="FAIL"){
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--输入密码--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id']=$bank['mch_id'];
            $payData['out_trade_no']=$remark;
            //$payData['transaction_id']=$res['transaction_id'];
            $payData['nonce_str']=time().rand(10000,99999);
            $payData['mch_pay_key']=$bank['mch_pay_key'];
            $this->ali_pay_password($payData);
        }else{
        	file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--招商支付失败--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->push_pay_message($remark);
            return array("flag" =>false, "msg" => "失败", "data" => $resign['errorMsg']);
        }
    }

    /**
     * POS机支付宝条码支付
     */
    public function pos_ali_barcode_pay($id, $price, $auth_code, $checker_id,$order_sn)
    {
        header('Content-Type:text/html; charset=utf-8');
        $payModel = $this->pay_model;

        //接收参数
        $id = $id ? $id : I('id', 0);
        $price = $price ? $price : I("price", 0);
        $auth_code = $auth_code ? $auth_code : I("auth_code");
        $checker_id = $checker_id ? $checker_id : I("checker_id");

        if (!$auth_code || !$id || $price < 0.01) $this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));

        $res = M('merchants_cate')->where("merchant_id=$id and status=1")->find();

        if (!$res['alipay_partner']) return array("flag" => false, "msg" => "未开通或未绑定支付宝支付");

        $data = $payModel->where("customer_id=$auth_code")->find();
        if (!$data) {
            //$remark = date('YmdHis') . rand(100000, 999999);//订单号
            $remark = $order_sn;//订单号
            //插入数据库的数据
            $data['merchant_id'] = (int)$res['merchant_id'];//商户ID
            $data['customer_id'] = $auth_code;//买方账号ID
            $data['checker_id'] = $checker_id;//收银员的ID
            $data['paystyle_id'] = 2;//支付方式 1是微信 2是支付宝
            $data['price'] = $price;
            $data['remark'] = $remark;//订单号
            $data['status'] = 0;//待付款
            $data['cate_id'] = $res['id'];//支付样式,台签类别
            $data['mode'] = 5;//0 为台签支付 1为扫码支付  2刷卡支付
            $data['add_time'] = time();//下单时间
            $data['subject'] = "向" . $res['jianchen'] . "支付" . $price . "元";
            $data['bank'] = 4;
            $wzcost_rate =$this->cost_rate_1($res['alipay_partner'],8);
            if ($wzcost_rate) $data['cost_rate'] = $wzcost_rate;
            $payModel->add($data);
        } else
            $remark = $data['remark'];
        //拼接支付宝数据
        $bank['mch_id']=$res['alipay_partner'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['body']=$data['subject'];
        $bank['out_trade_no']=$remark;
        $bank['total_fee']=$price*100;
        $bank['spbill_create_ip']=$_SERVER["REMOTE_ADDR"];
        $bank['mch_pay_key']=$res['alipay_public_key'];
        $bank['notify_url']="http://sy.youngport.com.cn/notify/zsbank.php";
        $bank['auth_code']=$auth_code;
        $bank['scene']='bar_code';
        $res=$this->alipay_micropay($bank);
        $res=$this->xmlToArray($res);
        $sign=$res['sign'];
        unset($res['sign']);
        $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->pay_model->where(array("remark" => $remark))->save(array("status" => "1", "paytime" => time(), "customer_id" => $res['sub_openid'],'transId'=>$res['transaction_id']));
            A("App/PushMsg")->push_pay_message($remark);
            return array("flag" =>true, "msg" => "成功", "data" =>'支付成功');
        }else if($res['return_code']=='SUCCESS' && $res['result_code']="FAIL"){
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--输入密码--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $payData['mch_id']=$bank['mch_id'];
            $payData['out_trade_no']=$remark;
            //$payData['transaction_id']=$res['transaction_id'];
            $payData['nonce_str']=time().rand(10000,99999);
            $payData['mch_pay_key']=$bank['mch_pay_key'];
            $this->ali_pay_password($payData);
        }else{
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--招商支付失败--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->push_pay_message($remark);
            return array("flag" =>false, "msg" => "失败", "data" => $res['errorMsg']);
        }
    }

    private function ali_pay_password($payData){
    	$queryTimes = 6;
        while ($queryTimes >= 0) {
            $bank['mch_pay_key']=$payData['mch_pay_key'];
            $bank['out_trade_no']=$payData['out_trade_no'];
            //$bank['transaction_id']=$payData['transaction_id'];
            $bank['nonce_str']=$payData['nonce_str'];
            $bank['mch_id']=$payData['mch_id'];
            $res=$this->alipay_query($bank);
            $res=$this->xmlToArray($res);
            file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付查询--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                //如果需要等待5s后继续
                $succResult=$res['trade_state'];
                
                //交易创建，等待买家付款
                if($succResult=='WAIT_BUYER_PAY'){
                    if($queryTimes==0){
                        break;
                    }else{
                        sleep(5);
                        $queryTimes--;
                        continue;
                    }
                //未付款交易超时关闭，或支付完成后全额退款
                }else if($succResult=='TRADE_CLOSED'){

                //支付成功
                }else if($succResult=='TRADE_SUCCESS'){
                	$brr=array("status" => "1", "paytime" => time(), "customer_id" => $res['sub_openid'],'transId'=>$res['transaction_id']);
                    file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付成功--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
                    $this->pay_model->where(array("remark" => $res['out_trade_no']))->save($brr);
                    A("App/PushMsg")->push_pay_message($res['out_trade_no']);
                    return array("flag" =>true, "msg" => "成功", "data" =>'支付成功');
                //交易结束，不可退款
                }else if($succResult=='TRADE_FINISHED'){

                }
            }else{
                $this->pay_model->where(array("remark" => $payData['out_trade_no']))->save(array("status" => "-2"));
                return array("flag" =>false, "msg" => "失败", "data" =>'支付失败');
            }
        }   
        $bank['mch_pay_key']=$payData['mch_pay_key'];
        $bank['out_trade_no']=$payData['out_trade_no'];
        //$bank['transaction_id']=$payData['transaction_id'];
        $bank['nonce_str']=$payData['nonce_str'];
        $bank['mch_id']=$payData['mch_id'];
        $res=$this->alipay_cancel($bank);
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/pay_password/pay_password.logs', date("Y-m-d H:i:s") . '--支付撤销--'.json_encode($res). PHP_EOL, FILE_APPEND | LOCK_EX);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
            $this->pay_model->where(array("remark" => $res['out_trade_no']))->save(array("status" => "-2"));
            return array("flag" =>false, "msg" => "失败", "data" =>'交易时间过长,支付失败');;
        }
    }
    //微信退借
    public function wx_pay_back($remark,$price_back)
    {
        header("Content-type:text/html;charset=utf-8");
        file_put_contents('./data/log/zsbank/tuikuan/'.date("Y-m-d").'_tuikuan.logs', date("Y-m-d H:i:s") .'微信退款返回信息:--'.$remark.'--'.PHP_EOL, FILE_APPEND | LOCK_EX);
        $pay = $this->pay_model->where("remark='$remark' And status = 1")->find();
        if ($pay['new_order_sn'] == null) {
            $terminal_serialno = $remark;
        } else {
            $terminal_serialno = $pay['new_order_sn'];
        }
        if (!$pay) return array("code" => "error", "msg" => "失败", "data" => "未找到订单");
        $merchant_id = $pay['merchant_id'];
        $list = M("merchants_cate")->where("merchant_id=$merchant_id and status=1")->find();

        $bank['mch_id']=$list['wx_mchid'];
        $bank['mch_pay_key']=$list['wx_key'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['out_trade_no']=$remark;
        $bank['transaction_id']=$pay['transId'];
        $bank['out_refund_no']=date("YmdHis").rand(10000,99999);
        $bank['total_fee']=$pay['price']*100;
        //$bank['refund_fee']=$pay['price']*100;
        $bank['refund_fee']=$price_back*100;
        $bank['op_user_id']=$list['wx_mchid'];
       
        $res=$this->wechat_refunds($bank);
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/tuikuan/'.date("Y-m-d").'_tuikuan.logs', date("Y-m-d H:i:s") .'微信退款返回信息:--'.json_encode($res).'--'.PHP_EOL, FILE_APPEND | LOCK_EX);
        if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
            //$this->pay_back_suc($remark, $pay['price']);
            $this->pay_back_suc($remark, $price_back);
            return array("code" => "success", "msg" => "成功", "data" => "退款成功");
        }else{
                
            /*if ($this->pay_model->where("remark='$remark'")->find()) {
                $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
            }*/
            return array("code" => "error", "msg" => "失败", "data" => "退款失败");
        }
    }
    //处理退款订单状态
    private function pay_back_suc($remark, $refund_amount)
    {
        if ($this->pay_model->where("remark='$remark'")->find()) {
            $this->pay_model->where("remark='$remark'")->save(array("status" => 2, "back_status" => 1, "price_back" => $refund_amount));
        }
    }
    //支付宝退款
    public function ali_pay_back($remark,$price_back)
    {
        header("Content-type:text/html;charset=utf-8");
        file_put_contents('./data/log/zsbank/tuikuan/'.date("Y-m-d").'_tuikuan.logs', date("Y-m-d H:i:s") .'支付宝返回信息:--'.$remark.'--'.PHP_EOL, FILE_APPEND | LOCK_EX);
        if (!$remark) return array("flag" => false, "msg" => "订单号不存在");
        $pay = $this->pay_model->where(array("remark" => $remark))->find();
        if (!$pay) return array("flag" => false, "msg" => "该订单不存在");
        if ($pay['status'] == "2") return array("flag" => false, "msg" => "不能重复退款");
        $merchant_id = $pay['merchant_id'];
        $res = M("merchants_cate")->where("merchant_id=$merchant_id and status=1")->find();
        if (!$res) return array("flag" => false, "msg" => "商户不存在");
        
        $bank['mch_id']=$res['alipay_partner'];
        $bank['mch_pay_key']=$res['alipay_public_key'];
        $bank['nonce_str']=time().rand(10000,99999);
        $bank['out_trade_no']=$remark;
        $bank['transaction_id']=$pay['transId'];
        $bank['out_refund_no']=date("YmdHis").rand(10000,99999);
        //$bank['refund_fee']=$pay['price']*100;
        $bank['refund_fee']=$price_back*100;
        $bank['op_user_id']=$res['wx_mchid'];
        $res=$this->alipay_refunds($bank);
        $res=$this->xmlToArray($res);
        file_put_contents('./data/log/zsbank/tuikuan/'.date("Y-m-d").'_tuikuan.logs', date("Y-m-d H:i:s") .'支付宝返回信息:--'.json_encode($res).'--'.PHP_EOL, FILE_APPEND | LOCK_EX);
        $sign=$res['sign'];
        unset($res['sign']);
        $resign=$this->getSignVeryfy_pay($res,$bank['mch_pay_key']);
        if($sign==$resign){
            if($res['result_code']=='SUCCESS' && $res['return_code']=='SUCCESS'){
                //$this->pay_back_suc($remark, $pay['price']);
                $this->pay_back_suc($remark, $price_back);
                return array("code" => "success", "msg" => "成功", "data" => "退款成功");
            }else{
                /*if ($this->pay_model->where("remark='$remark'")->find()) {
                    $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
                }*/
                    
                return array("code" => "error", "msg" => "成功", "data" => "$res[err_code_des]");
            }
        }else{
                
            /*if ($this->pay_model->where("remark='$remark'")->find()) {
                $this->pay_model->where("remark='$remark'")->save(array("status" => 3, "back_status" => 0));
            }*/
            return array("code" => "error", "msg" => "成功", "data" => "退款失败");
        }
    }

    public function notify(){
		$json_str = file_get_contents('php://input', 'r');
		$data=$this->xmlToArray($json_str);
		file_put_contents('/nasdata/www/youngshop/data/log/zsbank/notify/'.date("Y-m-d").'_notify.logs', date("Y-m-d H:i:s") .'发送信息:--'.json_encode($data).PHP_EOL, FILE_APPEND | LOCK_EX);
		$order_sn=$data['out_trade_no'];
		$transId=$data['transaction_id'];
		$orderData=$this->pay_model->where(array('remark'=>$order_sn))->find();

		if($orderData['status']==0){
//		    $sub_order_sn = substr($order_sn,4);
//			$sql="update  set status=1,transId='$transId' where (remark='".$order_sn."' OR remark='{$sub_order_sn}') AND status='0'";
            $this->pay_model->where(array('remark'=>$order_sn))->save(array('status'=> '1','transId'=>$transId,'paytime'=> time()));
			A("App/PushMsg")->push_pay_message($order_sn);
		}
    }
    //微信支付用户扫商家接口
    private function weixin_c_b_pay($data){
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //否
        if(isset($data['sub_appid']) && !empty($data['sub_appid'])){
            $param['sub_appid']=$data['sub_appid'];//商户微信公众号appid,app支付时,为在微信开放平台上申请的APPID
        }
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['body']=$data['body'];//商品描述
        //否
        if(isset($data['detail']) && !empty($data['detail'])){
            $param['detail']=$data['detail'];//商品详细列表，使用Json格式，传输签名前请务必使用CDATA标签将JSON文本串保护起来。goods_detail 服务商必填 []：└ goods_id String 必填 32 商品的编号└ wxpay_goods_id String 可选 32 微信支付定义的统一商品编号└ goods_name String 必填 256 商品名称└ quantity Int 必填 商品数量└ price Int 必填 商品单价，单位为分└ goods_category String 可选 32 商品类目ID└ body String 可选 1000 商品描述信息
        }
        //否
        if(isset($data['attach']) && !empty($data['attach'])){
            $param['attach']=$data['attach'];//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['fee_type']="CNY";//符合ISO 4217标准的三位字母代码，默认人民币：CNY
        //是
        $param['total_fee']=$data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip']=$data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //是
        // $param['time_start']=date("YmdHis");//订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
        // //是
        // $param['time_expire']=date("YmdHis");//如上
        //否
        if(isset($data['goods_tag']) && !empty($data['goods_tag'])){
            $param['goods_tag']=$data['goods_tag'];//商品标记，代金券或立减优惠功能的参数
        }
        //是
        $param['notify_url']=$data['notify_url'];//接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        //是
        $param['trade_type']=$data['trade_type'];//取值如下：JSAPI，NATIVE，APP
        //否
        if(isset($data['product_id']) && !empty($data['product_id'])){
            $param['product_id']=$data['product_id'];//trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        }
        //否
        if(isset($data['limit_pay']) && !empty($data['limit_pay'])){
            $param['limit_pay']=$data['limit_pay'];//no_credit–指定不能使用信用卡支付
        }
        //否
        if(isset($data['sub_openid']) && !empty($data['sub_openid'])){
            $param['sub_openid']=$data['sub_openid'];//trade_type=JSAPI，此参数必传，用户在子商户appid下的唯一标识。openid和sub_openid可以选传其中之一，如果选择传sub_openid,则必须传sub_appid。
            $param['openid']=$data['openid'];
        }
        if(isset($data['wxapp']) && !empty($data['wxapp'])){
            //否
            $param['wxapp']=$data['wxapp'];//true–小程序支付；此字段控制 js_prepay_info 的生成，为true时js_prepay_info返回小程序支付参数，否则返回公众号支付参数
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/orders";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //微信支付商家扫用户接口
    private function weixin_b_c_pay($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['body']=$data['body'];//商品描述
        //是
        $param['detail']=$data['detail'];//交易详情
        //否
        if(isset($data['attach']) && !empty($data['attach'])){
            $param['attach']=$data['attach'];//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['fee_type']="CNY";//符合ISO 4217标准的三位字母代码，默认人民币：CNY
        //是
        $param['total_fee']=$data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip']=$data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //否
        if(isset($data['goods_tag']) && !empty($data['goods_tag'])){
            $param['goods_tag']=$data['goods_tag'];//商品标记，代金券或立减优惠功能的参数
        }
        //是
        $param['auth_code']=$data['auth_code'];//扫码支付授权码， 设备读取用户展示的条码或者二维码信息
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/orders/micropay";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //关闭微信订单
    //商户订单支付失败需要生成新单号重新发起支付，要对原订单号调用关单，避免重复支付；系统下单后，用户支付超时，系统退出不再受理，避免用户继续，请调用关单接口。
    private function wechat_close($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['sub_appid']) &&!empty($data['sub_appid'])){
            $param['sub_appid']=$data['sub_appid'];//公众账号ID
        }
        //否
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
            $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/orders/close";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //微信支付冲正接口
    //支付交易返回失败或支付系统超时，调用该接口撤销交易。如果此订单用户支付失败，微信支付系统会将此订单关闭；如果用户支付成功，微信支付系统会将此订单资金退还给用户。
    private function wechat_reverse($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['sub_appid']) &&!empty($data['sub_appid'])){
            $param['sub_appid']=$data['sub_appid'];//公众账号ID
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号
        //是
        $param['transaction_id']=$data['transaction_id'];//UCHANG订单号，优先使用
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/orders/reverse";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //微信支付订单查询接口
    //根据商户订单号或者UCHANG订单号查询UCHANG的具体订单信息。
    private function wechat_query($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['sub_appid']) &&!empty($data['sub_appid'])){
            $param['sub_appid']=$data['sub_appid'];//公众账号ID
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号
        //是
        $param['transaction_id']=$data['transaction_id'];//UCHANG订单号，优先使用
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/orders/query";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //微信支付退款接口
    private function wechat_refunds($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串
        //否
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
            $param['out_trade_no']=$data['out_trade_no'];//UChang单号, out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先
        }
        //否
        if(isset($data['transaction_id']) && !empty($data['transaction_id'])){
            $param['transaction_id']=$data['transaction_id'];//商 户 系 统 内 部 的 订 单 号 , out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先
        }
        //是
        $param['out_refund_no']=$data['out_refund_no'];//商户退款单号，32 个字符内、可包含字母,确保在商户系统唯一。同个退款单号多次请求，UChang当一个单处理，只会退一次款。如果出现退款不成功，请采用原退款单号重新发起，避免出现重复退款。
        //是
        $param['total_fee']=$data['total_fee'];//订单总金额，单位为分
        //是
        $param['refund_fee']=$data['refund_fee'];//退款总金额,单位为分,可以做部分退款
        //是
        $param['refund_fee_type']='CNY';//货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY
        //是
        $param['op_user_id']=$data['op_user_id'];//操作员帐号,默认为商户号
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/refunds";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }
    //微信支付退款查询接口
    //交退款申请后， 通过调用该接口查询退款状态。 退款有一定延时， 请在 3 个工作日后重新查询退款状态。
    private function wechat_refunds_query($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串
        //否
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
            $param['out_trade_no']=$data['out_trade_no'];//商 户 系 统 内 部 的 订 单 号 , out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先
        }
        //否
        if(isset($data['transaction_id']) && !empty($data['transaction_id'])){
            $param['transaction_id']=$data['transaction_id'];//UCHANG单号, out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先优先
        }
        //否
        if(isset($data['out_refund_no']) && !empty($data['out_refund_no'])){
            $param['out_refund_no']=$data['out_refund_no'];//商户退款单号，32 个字符内、可包含字母,确保在商户系统唯一。
        }
        //否
        if(isset($data['refund_id']) && !empty($data['refund_id'])){
            $param['refund_id']=$data['refund_id'];//UChang退款单号 refund_id、out_refund_no、 out_trade_no 、 transaction_id 四个参数必填一个， 如果同事存在优先级为： refund_id>out_refund_no>t ransaction_id>out_trade_no
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/wechat/refunds/query";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }


    //支付宝商家扫用户
    private function alipay_micropay($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['notify_url']=$data['notify_url'];//接收ULINE支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。

        //是
        $param['body']=$data['body'];//订单标题
        //否
        if(isset($data['detail']) && !empty($data['detail'])){
            $param['detail']=$data['detail'];//订单描述
        }
        //否
        if(isset($data['attach']) && !empty($data['attach'])){
            $param['attach']=$data['attach'];//商家数据包
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['total_fee']=$data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip']=$data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //否
        if(isset($data['timeout_express']) && !empty($data['timeout_express'])){
            $param['timeout_express']=$data['timeout_express'];//该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
        }
        //是
        $param['scene']=$data['scene'];//支付场景 条码支付，取值：bar_code; 声波支付，取值：wave_code
        //是
        $param['auth_code']=$data['auth_code'];//扫码支付授权码， 设备读取用户展示的条码或者二维码信息
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/micropay";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝jsapi
    private function alipay_create($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['notify_url']=$data['notify_url'];//接收ULINE支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。

        //是
        $param['body']=$data['body'];//订单标题
        //否
        if(isset($data['detail']) && !empty($data['detail'])){
            $param['detail']=$data['detail'];//订单描述
        }
        //否
        if(isset($data['attach']) && !empty($data['attach'])){
            $param['attach']=$data['attach'];//商家数据包
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['total_fee']=$data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip']=$data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //否
        if(isset($data['timeout_express']) && !empty($data['timeout_express'])){
            $param['timeout_express']=$data['timeout_express'];//该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
        }
        //否
        if(isset($data['buyer_id']) && !empty($data['buyer_id'])){
            $param['buyer_id']=$data['buyer_id'];//买家的支付宝唯一用户号（2088开头的16位纯数字）,和buyer_logon_id不能同时为空
        }
        //否
        if(isset($data['buyer_logon_id']) && !empty($data['buyer_logon_id'])){
            $param['buyer_logon_id']=$data['buyer_logon_id'];//买家支付宝账号，和buyer_id不能同时为空
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/create";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝用户扫商家
    private function alipay_precreate($data){
            //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['notify_url']=$data['notify_url'];//接收ULINE支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。

        //是
        $param['body']=$data['body'];//订单标题
        //否
        if(isset($data['detail']) && !empty($data['detail'])){
            $param['detail']=$data['detail'];//订单描述
        }
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //是
        $param['total_fee']=$data['total_fee'];//总金额，以分为单位，不允许包含任何字、符号
        //是
        $param['spbill_create_ip']=$data['spbill_create_ip'];//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        //否
        if(isset($data['timeout_express']) && !empty($data['timeout_express'])){
            $param['timeout_express']=$data['timeout_express'];//该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/precreate";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝支付关闭订单
    //用于交易创建后，用户在一定时间内未进行支付，可调用该接口直接将未付款的交易进行关闭。
    private function alipay_close($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/close";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝撤销订单
    //只有发生支付系统超时或者支付结果未知时可调用撤销，其他正常支付的单如需实现相同功能请调用申请退款API。 提交刷卡支付交易后调用【查询订单API】，没有明确的支付结果再调用【撤销订单API】。
    private function alipay_cancel($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //是
        $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/close";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝订单查询
    //根据商户订单号或者UCHANG订单号查询UCHANG的具体订单信息。
    private function alipay_query($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号，由UCHANG分配
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于32位
        //否
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
            $param['out_trade_no']=$data['out_trade_no'];//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        }
        //否
        if(isset($data['transaction_id']) && !empty($data['transaction_id'])){
            $param['transaction_id']=$data['transaction_id'];//商户系统内部的订单号, out_trade_no 和 transaction_id 至少一个必填，同时存在时transaction_id 优先
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/orders/query";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝退款接口
    private function alipay_refunds($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //否
        if(isset($data['out_trade_no']) && !empty($data['out_trade_no'])){
            $param['out_trade_no']=$data['out_trade_no'];//UChang单号, out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先
        }
        //否
        if(isset($data['transaction_id']) && !empty($data['transaction_id'])){
            $param['transaction_id']=$data['transaction_id'];//商 户 系 统 内 部 的 订 单 号 , out_trade_no 和 transaction_id 至少一个必填，同时存在时 transaction_id 优先
        }
        $param['nonce_str']=$data['nonce_str'];
        //是
        $param['out_refund_no']=$data['out_refund_no'];//商户退款单号，32 个字符内、可包含字母,确保在商户系统唯一。同个退款单号多次请求，UChang当一个单处理，只会退一次款。如果出现退款不成功，请采用原退款单号重新发起，避免出现重复退款。
        //是
        $param['refund_fee']=$data['refund_fee'];//退款总金额,单位为分,可以做部分退款
        //是
        $param['op_user_id']=$data['op_user_id'];//操作员帐号,默认为商户号
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/refunds";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //支付宝退款查询接口
    //交退款申请后， 通过调用该接口查询退款状态。 退款有一定延时， 请在 3 个工作日后重新查询退款状态。
    private function alipay_refunds_query($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于 32 位
        //否
        if(isset($data['out_refund_no']) && !empty($data['out_refund_no'])){
            $param['out_refund_no']=$data['out_refund_no'];//商户退款单号，32 个字符内、可包含字母,确保在商户系统唯一。同个退款单号多次请求，UChang当一个单处理，只会退一次款。如果出现退款不成功，请采用原退款单号重新发起，避免出现重复退款。
        }
        //否
        if(isset($data['refund_id']) && !empty($data['refund_id'])){
            $param['refund_id']=$data['refund_id'];//UChang退款单号 refund_id、out_refund_no 两个参数必填一个
        }
        //否
        if(isset($data['device_info']) && !empty($data['device_info'])){
            $param['device_info']=$data['device_info'];//终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传“WEB”
        }
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/alipay/refunds/query";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //对账单接口
    private function  pay_bills($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于 32 位
        //是
        $param['bill_date']=$data['bill_date'];//格式:yyyyMMdd(如:20150101)
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/bills";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;

    }
    //客户打款记录
    private function  bills_remittance_query($data){
        //是
        $param['mch_id']=$data['mch_id'];//商户号
        //是
        $param['nonce_str']=$data['nonce_str'];//随机字符串，不长于 32 位
        //是
        $param['date']=$data['date'];//格式:yyyyMMdd(如:20150101)
        //获取签名
        $param['sign']=$this->getSignVeryfy_pay($param,$data['mch_pay_key']);
        //转换成xml格式post提交数据
        $xmlData=$this->arrayToXml($param);
        $url="http://api.cmbxm.mbcloud.com/bills/remittance/query";
        $result=$this->httpRequst_pay($url, $xmlData);
        return $result;
    }

    //获取格林时间
    private function gmdate(){
        $time=substr(gmdate(DATE_RFC1123),0,25)." GMT";
        return $time;
    }
    //生成签名
    private function sign($METHOD,$PATH,$DATE,$CONTENT_LENGTH){
        $sign=$METHOD."&".$PATH."&".$DATE."&".$CONTENT_LENGTH."&".$this->apikey;
        return md5($sign);
    }
    //定义头部信息
    private function header($sign,$length){
        $header=array(
        "Authorization:Uline ".$this->id.":".$sign,
        "Content-Type:multipart/form-data",
        "Date:".$this->gmdate()
        );
        return $header;
    }
    //数组转xml
    private function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                 $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    //xml转数组
    private function xmlToArray($xml){    
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $values;
    }
    //curl 请求
    private function httpRequst($url, $post_data, $header)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        //curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
        //显示获得的数据   
    }
    //支付接口 curl
    private function httpRequst_pay($url, $post_data)
    {
        file_put_contents(get_date_dir($this->path) . date("Y_m_d_") . 'curl.log', date("Y-m-d H:i:s") . 'curl信息:请求url  ' . $url . '请求参数' . $post_data . PHP_EOL, FILE_APPEND | LOCK_EX);

        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        file_put_contents(get_date_dir($this->path) . date("Y_m_d_") . 'curl.log', date("Y-m-d H:i:s") . 'curl信息:请求结果' . $data . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);

        curl_close($curl);
        return $data;
        //显示获得的数据   
    }
    //获取商户支付密钥签名
    private function getSignVeryfy($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter =$this->paraFilter($para_temp);
        
        //对待签名参数数组排序
        $para_sort =$this->argSort($para_filter);
        
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr =$this->createLinkstring($para_sort);
        //拼接apikey
        $prestr=$prestr."&key=".$this->apikey;
        //MD5 转大写
        $prestr=strtoupper(md5($prestr));
        return $prestr;
    }
    //支付接口统一签名
    private function getSignVeryfy_pay($para_temp,$paykey) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter =$this->paraFilter($para_temp);
        
        //对待签名参数数组排序
        $para_sort =$this->argSort($para_filter);
        
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr =$this->createLinkstring($para_sort);
        //拼接apikey
        $prestr=$prestr."&key=".$paykey;
        //MD5 转大写
        $prestr=strtoupper(md5($prestr));
        return $prestr;
    }
    //除去空字符串
    private function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    //数组排序
    private function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        
        return $arg;
    }

    //获取openid
    private function _get_openid()
    {
        // 获取配置项
        $config = C('WEIXINPAY_CONFIG');
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 返回的url
//            $redirect_uri = U('Pay/Barcode/qr_weixipay', '', '', true);'http://' . $_SERVER['HTTP_HOST']
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $config['APPID'] . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_base#wechat_redirect';
            redirect($url);
        } else {
            //如果有code参数；则表示获取到openid
            $code = I('get.code');
            //组合获取prepay_id的url
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['APPID'] . '&secret=' . $config['APPSECRET'] . '&code=' . $code . '&grant_type=authorization_code';
            //curl获取prepay_id
            $result = $this->_curl_get_contents($url);
            $result = json_decode($result, true);
            return $result['openid'];

        }
    }

    private function _curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
        // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);        //设置 referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);          //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        $r = curl_exec($ch);
        curl_close($ch);

        file_put_contents(get_date_dir($this->path) . date("Y_m_d_") . 'curl_get.log', date("Y-m-d H:i:s") . '  curl_get 信息:请求url  ' . $url . '  返回结果:  ' . $r . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $r;
    }
    //获取支付费率
    private function cost_rate_1($mch_id,$pay_type){
        $re=M('merchants_zspay')->where(array('ul_mchid'=>$mch_id))->find();
        switch ($pay_type) {
            case '1':
                return "0.".$re['payment_type1'];
                break;
            case '2':
                return "0.".$re['payment_type2'];
                break;
            case '3':
                return "0.".$re['payment_type3'];
                break;
            case '7':
                return "0.".$re['payment_type7'];
                break;
            case '8':
                return "0.".$re['payment_type8'];
                break;
            case '9':
                return "0.".$re['payment_type9'];
                break;  
            default:
                break;
        }
    }
    private function push_pay_message($remark)
    {
        $pay = $this->pay_model->where("remark='$remark'")->find();
        if (!$pay) return;
        //声明推送消息日志路径
        $path = get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/message/');
        if (!$pay) {
            return;
        }
        $mid = $pay['merchant_id'];
        $checker = $pay['checker_id'];

        $status = $pay['status'];
        $price = $pay['price'];
        $mode = $pay['mode'];
        switch ($mode) {
            case 0:
                $mode = "台签";
                break;
            case 1:
                $mode = "商业扫码支付";
                break;
            case 2:
                $mode = "商业刷卡支付";
                break;
            case 3:
                $mode = "收银扫码支付";
                break;
            case 4:
                $mode = "收银现金支付";
                break;
            default:
                $mode = "其他支付";
                break;
        }
        if ($status == 0) {
            $massage = "收款失败";
        } else if ($status == 1) {
            $massage = "[" . $mode . "]" . "来钱啦,收款" . $price . "元！";
        } else
            $massage = '';

        //有收银员的情况下,将信息发给收银员
        if ($checker) {
            $check_phone = M("merchants_users")->where("id=$checker")->getField("user_phone");
        }

        //当前商户
        $merchants_info = M("merchants")->where("id=$mid")->field("uid,mid")->find();
        $uid = $merchants_info['uid'];
        $user_phone = M("merchants_users")->where(array('id' => $uid))->getField("user_phone");

        //多门店大商户
        if ($merchants_info['mid'] > 0) {
            $big_uid = M("merchants")->where(array('id' => $merchants_info['mid']))->getField("uid");
            $big_user_phone = M("merchants_users")->where(array('id' => $big_uid))->getField("user_phone");
        }
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送1' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给大商户****/
        if (isset($big_user_phone) && isset($big_uid) && M("token")->where(array('uid' => $big_uid))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$big_user_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给多门店大商户: ' . $big_user_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送' . $user_phone . "的上级门店未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送2' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给收银员****/
        if (isset($check_phone) && M("token")->where(array('uid' => $checker))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$check_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给收银员: ' . $check_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送3' . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        /***推送给商户***/
        if ($user_phone && M("token")->where(array('uid' => $uid))->getField("uid")) {
            A("Message/adminpush")->adminpush("$massage", "$remark", "$status", "$user_phone");
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '发送信息给商户: ' . $user_phone . "___" . $status . "____" . $massage . ",订单号:" . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else
            file_put_contents($path . date("Y_m_d_") . 'pay_message.log', date("Y-m-d H:i:s") . '未发送' . $user_phone . "未登录____订单号:  " . "$remark" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
