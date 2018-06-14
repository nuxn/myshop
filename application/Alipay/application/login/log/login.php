<?php
ini_set('date.timezone','Asia/Shanghai');
class login
{
	var $db;
	var $account;
	var $accountObj;
	public function  __construct($_db)
    {
        $this->db = $_db;
    }
    public function login($param){
        $data='[{"idNo":"6209123456781234","mchtPhone":"15021790421","acquirerTypes":"[{\"acquirerType\":\"wechat\",\"tradeType\":\"203\"},{\"acquirerType\":\"alipay\",\"tradeType\":\"1223\"},{\"acquirerType\":\"qq\",\"tradeType\":\"1223\"}]","contactEmail":"13817182998@163.com","mchtAddr":"上海浦东金科路2","version":"2.0","city":"310000","mchtShortName":"改版0422","area":"310115","bizLicense":"yinyezhizhao","contactName":"联系人","action":"mcht/info/reg","province":"310000","coopMchId":"201704211424","contactMobile":"15021793622","mchtName":"入驻改版0422"}]';
        $res=rsaSign($data,PRIVATE_KEY);
        return httpRequst(URL,$data,$res,APPKEY);
    }
    public function check($param){
        $data='[{"acquirerType":"wechat","action":"mcht/info/query","coopMchId":"'.APPKEY.'","version":"2.0","custId":"'.APPKEY.'"}]';
        $res=rsaSign($data,PRIVATE_KEY);
        return httpRequst(URL,$data,$res,APPKEY);
    }
    public function into($param){
        if(!isset($param['uid']) && empty($param['uid'])){
            $reslut['responseCode']="1112";
            $reslut['resultMsg']="非法调用";
            return json_encode($reslut);
        }
        $uid=$param['uid'];
        $sql="select * from ypt_merchants_mpay where uid='$uid'";
        $arr=$this->db->query($sql);
        $data['action']='mcht/info/enter';
        $data['version']='2.0';
        $data['expanderCd']='0848160400';
        $data['coopMchtId']=$arr['bizLicense'];
        $data['mchtName']=$arr['mchtName'];
        $data['mchtShortName']=$arr['mchtShortName'];
        $data['mchtType']=$arr['mchtType'];//上级商户
        //$data['parentMchtId']=$arr['parentMchtId'];
        $data['gszcName']=$arr['gszcName'];
        $data['bizLicense']=$arr['bizLicense'];//营业执照有效期
        $data['legalIdExpiredTime']=$arr['legalIdExpiredTime'];
        $data['IdNo']=$arr['IdNo'];
        $data['mchtAddr']=$arr['mchtAddr'];
        $data['province']=$arr['province'];//省代码
        $data['city']=$arr['city'];//城市代码
        $data['area']=$arr['area'];//区县代码
        $data['accountType']='3';//0-公户、1-私户
        $data['account']=$arr['account'];//银行账号
        $data['accountName']=$arr['accountName'];//账号名
        $data['bankCode']=$arr['bankCode'];//开户行号
        $data['bankName']=$arr['bankName'];//开户行名
        $data['openBranch']=$arr['openBranch'];//开户网点（具体参考字典 6.12）
        $data['contactName']=$arr['contactName'];//联系人名称
        $data['contactMobile']=$arr['contactMobile'];//联系人手机
        $data['contactEmail']=$arr['contactEmail'];//联系人邮箱
        $data['mchtLevel']=$arr['mchtLevel'];//1-分店（上送父级商户号时，必须选择该级别）、2-商户
        $data['openType']=$arr['openType'];// 1-个人、C - 企业
        $arr1['acquirerType']='wechat';
        $arr1['scale']=$arr['weicodefen'];
        $arr1['countRole']='0';
        $arr1['tradeType']=$arr['weicode'];
        $arr2['acquirerType']='alipay';
        $arr2['scale']=$arr['alipaycodefen'];
        $arr2['countRole']='0';
        $arr2['tradeType']=$arr['alipaycode'];
        $arr3['acquirerType']='qq';
        $arr3['scale']=$arr['qqcodefen'];
        $arr3['countRole']='0';
        $arr3['tradeType']=$arr['qqcode'];
        $data['acquirerTypes']=json_encode(array($arr1,$arr2,$arr3));
        $data=json_encode($data);
        $data="[".$data."]";
        $res=rsaSign($data,PRIVATE_KEY);
        $reslut=httpRequst(URL,$data,$res,APPKEY);
        $row=json_decode($reslut,true);
        $body=$row['body'];
        file_put_contents("../application/login/log/into.logs",date("Y-m-d H:i:s",time())."--".$data."--".$reslut."\r\n",FILE_APPEND | LOCK_EX);
        if($body['responseCode']=='00'){

            if(isset($body['bankMchtId']) && !empty($body['bankMchtId'])){
                $bankMchtId=$body['bankMchtId'];
                $acq=$body['acquirerTypes'];
                $acq=json_decode($acq,true);
                foreach ($acq as $key => $value) {
                    if($acq[$key]['acquirerType']=='wechat'){
                        $wechat=$acq[$key]['custId'];
                    }elseif ($acq[$key]['acquirerType']=='alipay') {
                        $alipay=$acq[$key]['custId'];
                    }elseif ($acq[$key]['acquirerType']=='qq') {
                        $qq=$acq[$key]['custId'];
                    }
                }
                file_put_contents("../application/login/log/intosuccess.logs",date("Y-m-d H:i:s",time())."--".$data."--".$reslut."\r\n",FILE_APPEND | LOCK_EX);
                $paysql="UPDATE ypt_merchants_mpay SET wechat='$wechat',alipay='$alipay',qq='$qq',into_type='2',bankMchtId='$bankMchtId' WHERE uid='$uid'";
                $re=$this->db->query($paysql);
            }else{
                $acq=$body['acquirerTypes'];
                $acq=json_decode($acq,true);
                foreach ($acq as $key => $value) {
                    if($acq[$key]['acquirerType']=='wechat'){
                        $wechat=$acq[$key]['custId'];
                    }elseif ($acq[$key]['acquirerType']=='alipay') {
                        $alipay=$acq[$key]['custId'];
                    }elseif ($acq[$key]['acquirerType']=='qq') {
                        $qq=$acq[$key]['custId'];
                    }
                }
                $paysql="UPDATE ypt_merchants_mpay SET wechat='$wechat',alipay='$alipay',qq='$qq',into_type='2' WHERE uid='$uid'";
                $re=$this->db->query($paysql);
            }
        }else{
            $paysql="UPDATE ypt_merchants_mpay SET into_type='1' WHERE uid='$uid'";
            $re=$this->db->query($paysql);
        }
        return $reslut;
    }
}