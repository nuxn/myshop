<?php
ini_set('date.timezone','Asia/Shanghai');
class index
{
	var $db;
	public function  __construct($_db)
    {
        $this->db = $_db;
    }
    public function refund(){
        if(isset($_POST['order_id']) &&!empty($_POST['order_id'])){    
            $order_id=$_POST['order_id'];
            $orderData=$this->db->query("select * from ypt_pay where remark='$order_id'");
            if($orderData){
                $merchant_id=$orderData['merchant_id'];
                $cate_id=$orderData['cate_id'];
                $cateData=$this->db->query("select * from ypt_merchants_cate where id= '$cate_id'");
                $pay_type=$orderData['paystyle_id'];
                if($pay_type==1){
                    $acquirerType='wechat';
                    $custId=$cateData['wx_mchid'];
                }elseif ($pay_type==2) {
                    $acquirerType='alipay';
                    $custId=$cateData['alipay_partner'];
                }         
                $order_sn=$order_id;
                $orgTransId=$orderData['transId'];
               	$totalAmount=$orderData['price'];
            }else{
                $reslut['responseCode']="1114";
                $reslut['errorMsg']="订单号不存在";
                return json_encode($reslut);
            }
        }else{
            $reslut['responseCode']="1113";
            $reslut['errorMsg']="order_id不能为空";
            return json_encode($reslut);
        }
		$data['action']='wallet/trans/refund';
        $data['version']='2.0';
        $data['reqTime']= date("YmdHis");
        $data['orderId']=$order_id;
        $data['refundOrderId']=date("YmdHis").rand(1000,9999);
        $data['reqId']=date("YmdHis").rand(1000,9999);
        $data['deviceId']='payuser';//终端号
        $data['totalAmount']=$totalAmount*100;
        $data['operatorId']="POS 操作员";
        $data['custId']=$custId;
        $data['orgReqId']=$orgTransId;
        $data['orgTransId']=$orgTransId;
        $data=json_encode($data);
        $data="[".$data."]";
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
      	$result=json_decode($result,true);
        $sql="update ypt_pay set back_status=1,price_back='$totalAmount',status=2 where remark='$order_sn'";
        file_put_contents("/alidata/www/youngshop/application/Alipay/application/paylog/1.txt",date("Y-m-d H:i:s",time())."-".$cate_id."-".$order_id."--".$data."--".json_encode($cateData)."--".json_encode($result)."--".$sql."\r\n",FILE_APPEND | LOCK_EX);
        if($result['body']['responseCode']=='00'){
            $this->db->query($sql);
            return json_encode(array("code" => "success", "msg" => "成功", "data" => "退款成功"));
        }else{
            return json_encode(array("code" => "error", "msg" => "失败", "data" =>$result['body']['errorMsg']));
        }
    }
    public function bill_download(){
        $data['action']='mcht/bill/download';
        $data['version']='2.0';
        $data['coopId']='APPKEY';
        $data['billDate']=date("Ymd",strtotime("-1 day"));;
        $data=json_encode($data);
        $data="[".$data."]";
        $res=rsaSign($data,PRIVATE_KEY);
        $result=httpRequst(URL,$data,$res,APPKEY);
        file_put_contents('./log.txt', date("Y-m-d H:i:s").$result. PHP_EOL, FILE_APPEND | LOCK_EX);
        $result=json_decode(stripslashes($result),true);
        if(isset($result['body']['billUrl']) && !empty($result['body']['billUrl'])){
            $furl=$result['body']['billUrl'];
            $handle = fopen ($furl, "rb"); 
            $contents = array(); 
            while (!feof($handle)) { 
                $contents[]= fread($handle, 8192); 
            } 
            fclose($handle);
            $cont=''; 
            foreach ($contents as $key => $value) {
                $cont.=$value;
            }
            $time=date("Y-m-d",strtotime("-1 day"));
            $sql_1="select * from ypt_ms_daylogs where time='$time'";

            if(!$this->db->query($sql_1)){
                $data=explode("\r\n",$cont);
                $sql= "insert into ypt_ms_logs  (pay_type,pay_status,order_sn,pay_time,pay_price,pay_reprice,transId,type,price,refund_time,pay_id) 
                  VALUES";
                $count=count($data)-2;
                foreach ($data as $key => $value) {

                    $data[$key]=explode(',',$data[$key]);
                    if(isset($data[$key][1]) && !empty($data[$key][1])){
                        $goods[$key]['pay_type']=$data[$key][1];
                        $goods[$key]['pay_status']=$data[$key][2];
                        $goods[$key]['order_sn']=$data[$key][3];
                        $goods[$key]['pay_time']=$data[$key][4].$data[$key][5];
                        $goods[$key]['pay_price']=$data[$key][6];
                        $goods[$key]['pay_reprice']=$data[$key][7];
                        $goods[$key]['transId']=$data[$key][13];
                        $goods[$key]['type']=$data[$key][16];
                        $goods[$key]['price']=$data[$key][20];
                        $goods[$key]['refund_time']=$data[$key][14].$data[$key][15];
                        $goods[$key]['pay_id']=$data[$key][18];
                        if($key==$count){
                            $sql.="('".$data[$key][1]."','".$data[$key][2]."','".$data[$key][3]."','".$data[$key][4].$data[$key][5]."','".$data[$key][6]."','".$data[$key][7]."','".$data[$key][13]."','".$data[$key][16]."','".$data[$key][20]."','".$data[$key][14].$data[$key][15]."','".$data[$key][18]."')";
                        }else{
                            $sql.="('".$data[$key][1]."','".$data[$key][2]."','".$data[$key][3]."','".$data[$key][4].$data[$key][5]."','".$data[$key][6]."','".$data[$key][7]."','".$data[$key][13]."','".$data[$key][16]."','".$data[$key][20]."','".$data[$key][14].$data[$key][15]."','".$data[$key][18]."'),";
                        }
                    }
                }
                $this->db->query($sql);
                $z_price=0;
                $f_price=0;
                $sxf_price=0;
                $z=0;
                $f=0;
                for($i=0;$i<=$count;$i++){
                    if($goods[$i]['pay_status']==1){
                        $z++;
                        $z_price+=$goods[$i]['pay_price'];
                    }
                    if($goods[$i]['pay_status']==3){
                        $f++;
                        $f_price+=$goods[$i]['pay_price'];
                    }
                    $sxf_price+=$goods[$i]['pay_reprice'];
                }
                $price=$z_price-$f_price-$sxf_price;
                $day_sql="insert into ypt_ms_daylogs (time,z_number,f_number,z_price,f_price,price,sxf_price) VALUES ('".$time."','".$z."','".$f."','".$z_price."','".$f_price."','".$price."','".$sxf_price."')";
                $this->db->query($day_sql);
            }
        }
    }
}