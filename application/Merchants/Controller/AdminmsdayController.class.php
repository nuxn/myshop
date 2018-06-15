<?php
namespace  Merchants\Controller;
use Common\Controller\AdminbaseController;
use Think\Page;

/***
 * Class AdminRoleController
 * @package Merchants\Controller
 * @auth 534244896@qq.com
 */

class  AdminmsdayController extends  AdminbaseController {
	function _initialize() {
        $this->apikey='34cfc590d398413b903f9e8966a01c62';
        $this->expanderCd='0199980855';
        $this->public_key='-----BEGIN CERTIFICATE-----\nMIIDRjCCAi6gAwIBAgIEVBlClDANBgkqhkiG9w0BAQUFADBlMQswCQYDVQQGEwJjbjEQMA4GA1UE\nCBMHYmVpamluZzEQMA4GA1UEBxMHYmVpamluZzESMBAGA1UEChMJdGVzbGF0ZWFtMQ0wCwYDVQQL\nEwRjbWJjMQ8wDQYDVQQDDAZ0cHBfZ3cwHhcNMTQwOTE3MDgxMzA4WhcNMjQwOTE0MDgxMzA4WjBl\nMQswCQYDVQQGEwJjbjEQMA4GA1UECBMHYmVpamluZzEQMA4GA1UEBxMHYmVpamluZzESMBAGA1UE\nChMJdGVzbGF0ZWFtMQ0wCwYDVQQLEwRjbWJjMQ8wDQYDVQQDDAZ0cHBfZ3cwggEiMA0GCSqGSIb3\nDQEBAQUAA4IBDwAwggEKAoIBAQDKJ6sDzFi/sTYWXpnlkF7PJIYG0yZ48JV2/SmB0ob5vZMwkFdY\n495FIuxPJLIS3PKt2UyoSXqPyhYeYzUoMpSM4rEx/rj9WfoSZODjeoAZTji+rNxi1LVAbYx+x9LO\nAZVkkXllloGXM+iT/J7eq9t/Lf/STvKKVGiGIhEb++Nz9yn3RxJNR0SqJJx2PgwL4z6qDVti2iLs\nSNRO1LycmZ4oz7ewohpGkF4BsdlLABLPha3UpmP9oGgCYVt31sJ4lkjiWZx56yBpYt+jbkjavFIv\n7nL9btAPm8DApu9aFbH6hMCbyFjf9jFr5UlCe7m8CWexMJ0ieIQkfPim0IJOtQnRAgMBAAEwDQYJ\nKoZIhvcNAQEFBQADggEBAJNDIFjX0VYMNwU4TNYeurcf31O0A97GKI21D7jzLJnnsLXqxM60blNp\nV9fcycrZOmQXiAK3LGLJeao6JB596zmqxXOCc8veAPvE5ItEWhs/e4IVh02KlYy/Im2nxq7QJFkw\n1tlqK9Cf1OgZhBxF4x+RG8CqEAJzTAdo/XmF8BpbSdDW0R2BdmC0RJ6SWcfBXiBhTPd3Fctrjb9r\nlsgW4Dw+0ZgDL3foFdPsarvPm2lYB8lTlsSXwlkr94jySX/iHc/Un9Xhn/F/qEe15BbHKUYlWjV8\nWt0mzDhoSKYqeFc/S++EWY3DTCLbprNN6HI/FxLCKcT18hSzbo5ArS5P22k=\n-----END CERTIFICATE-----';
    }
	public function index(){
		$merchant_id=$_GET['id'];
		//$merchant_id=53;
        $list=M('Merchants')->where("id='{$merchant_id}'")->find();
        $uid=$list['uid'];
        $phone=M('Merchants_users')->where("id='{$uid}'")->find();
        $this->assign('phone',$phone);
        $this->assign('list',$list);
        $this->assign('id',$merchant_id);
        $merchants_mpay_data=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
        $this->assign('data',$merchants_mpay_data);
        $this->display("index2");
	}
	/**
     * 进件列表
     */
    public function jn_list(){
        $user_phone = trim(I('user_phone'));
        $merchant_name = trim(I('merchant_name'));
        if ($user_phone) {
            $map['u.user_phone'] = $user_phone;
			$this->assign('user_phone',$user_phone);
        }
        if ($merchant_name) {
            $map['m.merchant_name'] = array('like',"%$merchant_name%");
			$this->assign('merchant_name',$merchant_name);
        }
        $zspay = M('merchants_mdaypay')->alias('z')
            ->join("left join __MERCHANTS__ m on z.merchant_id = m.id")
            ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
            ->field("z.id,z.merchant_id,z.customerId,m.merchant_name,u.user_phone")
            ->where($map)
            ->order("id desc")
            ->select();
			//dump($zspay);exit;
        $count = count($zspay);
        $page = $this->page($count, 20);
        $list = array_slice($zspay, $page->firstRow, $page->listRows);
        $this->assign("page", $page->show('Admin'));
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("zspay", $list);
        $this->display();
    }
	public function upload_into(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     13145728 ;// 设置附件上传大小
        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'msinto/'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if($info){
            $data['type']=1;
            if($info['id_card_z']){
                $data['back']=1;
                $data['id_card_z']=$info['id_card_z']['savepath'].$info['id_card_z']['savename'];
            }else if($info['id_card_f']){
                $data['back']=2;
                $data['id_card_f']=$info['id_card_f']['savepath'].$info['id_card_f']['savename'];
            }else if($info['id_card_z_s']){
                $data['back']=3;
                $data['id_card_z_s']=$info['id_card_z_s']['savepath'].$info['id_card_z_s']['savename'];
            }else if($info['id_card_f_s']){
                $data['back']=4;
                $data['id_card_f_s']=$info['id_card_f_s']['savepath'].$info['id_card_f_s']['savename'];
            }else if($info['bslicenceFile']){
                $data['back']=5;
                $data['bslicenceFile']=$info['bslicenceFile']['savepath'].$info['bslicenceFile']['savename'];
            }
            echo json_encode($data);
            exit();
        }else{
            $data['type']=2;
            $data['message']=$upload->getError();
            echo json_encode($data);
            exit();
        }
    }
    public function upload_zspay(){
        $merchant_id=$_POST['merchant_id'];
        $data=$_POST;
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     13145728 ;// 设置附件上传大小
        //$upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath  =     'msinto/'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if($info){
        	$data['supply']=$info['supply']['savepath'].$info['supply']['savename'];

	        $row=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
	        if($row){
	            $re=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->save($data);
	            $this->success('保存成功!');
	        }else{
	            $re=M('merchants_mdaypay')->add($data);
	            $this->success('添加成功!');
	        }
	    }else{
	    	$row=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
	        if($row){
	            $re=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->save($data);
	            $this->success('保存成功!');
	        }else{
	            $re=M('merchants_mdaypay')->add($data);
	            $this->success('添加成功!');
	        }
	    }
    }
    public function mch_into(){
    	$id=$_POST['id'];
    	$merchantData=M("merchants_mdaypay")->where(array('merchant_id'=>$id))->find();	
    	$merchants_mdaypay_id=$merchantData['id'];
    	if($merchantData['customerId']){
    		$data['customerId']=$merchantData['customerId'];
    	}
    	$data['expanderCd']='0199980906';
    	$data['merchantName']=$merchantData['merchantName'];
    	$data['merchantShortName']=$merchantData['merchantShortName'];
    	$data['merchantType']=$merchantData['merchantType'];
    	$data['merchantLevel']=$merchantData['merchantLevel'];
    	$data['businessModel']="4";
    	if($merchantData['parentCustomerId']){
    		$data['parentCustomerId']=$merchantData['parentCustomerId'];
    	}
    	$data['openType']=$merchantData['openType'];
    	$data['gszcName']=$merchantData['gszcName'];
    	$data['qualificationInfos'][0]['legalIdName']=$merchantData['legalIdName'];
    	$data['qualificationInfos'][0]['legalIdType'] = '1';
        $data['qualificationInfos'][0]['legalIdNumber'] = $merchantData['idNo'];
        $data['qualificationInfos'][0]['legalIdExpiredTime'] = $merchantData['id_card_s_time'];
        $data['qualificationInfos'][1]['legalIdName']=$merchantData['legalIdName'];
    	$data['qualificationInfos'][1]['legalIdType'] = 'A';
        $data['qualificationInfos'][1]['legalIdNumber'] = $merchantData['bslicenceNo'];
        $data['qualificationInfos'][1]['legalIdExpiredTime'] = "2999-01-01";
        $data['qualificationInfos']=json_encode($data['qualificationInfos']);
        $data['manageOrgId']='1600';
        $data['merchantAddr']=$merchantData['merchantAddr'];
        $data['province']=$merchantData['province'];
        $data['city']=$merchantData['city'];
        $data['county']=$merchantData['county'];
        $data['accountType']=$merchantData['accountType'];
        $data['account']=$merchantData['account'];
        $data['accountName']=$merchantData['accountName'];
        $data['banckCode']=$merchantData['banckCode'];
        $data['bankName']=$merchantData['bankName'];
        $data['openBranch']=$merchantData['openBranch'];
        $data['merchantConsacts']=$merchantData['merchantConsacts'];
        $data['telephone']=$merchantData['telephone'];
        $data['payChennel']=$merchantData['payChennel'];
        $data['repaidRate']=(string)($merchantData['repaidRate']);
        $data['minAmount']=$merchantData['minAmount'];
        $data['poundage']=$merchantData['poundage'];
        $data['minRepaidAmount']=$merchantData['minRepaidAmount'];
        $supply="https://sy.youngport.com.cn/data/upload/".$merchantData['supply'];
        $data['payServices'][0]['payService'] = 'WEIXIN';
        $data['payServices'][0]['isOpen'] = 'Y';
        $data['payServices'][0]['scale'] = (string)($merchantData['wechat_cost_rate']*10);
        $data['payServices'][0]['countRole'] = $merchantData['countRole'];
        $data['payServices'][0]['tradeType'] = $merchantData['wxcode'];
        $data['payServices'][0]['supply']=$supply;
        $data['payServices'][0]['supplyname']=basename($supply);
        $data['payServices'][1]['payService'] = 'ZFB';
        $data['payServices'][1]['isOpen'] = 'Y';
        $data['payServices'][1]['scale'] = (string)($merchantData['alipay_cost_rate']*10);
        $data['payServices'][1]['countRole'] = $merchantData['countRole'];
        $data['payServices'][1]['tradeType'] = $merchantData['alicode']; //'2015050700000011';
        $data['payServices'][2]['payService'] = 'QQ';
        $data['payServices'][2]['isOpen'] = 'Y';
        $data['payServices'][2]['scale'] = (string)($merchantData['qq_cost_rate']*10);
        $data['payServices'][2]['countRole'] = $merchantData['countRole'];
        $data['payServices'][2]['tradeType'] = $merchantData['qqcode'];
        $data['payServices']=json_encode($data['payServices']);
      	$url='http://jt.ypt5566.com/TbmServlet';
      	$data=urldecode(http_build_query($data));
  		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($ch);
		curl_close($ch);
		file_put_contents('./data/log/jnmsbank/into/into.logs', date("Y-m-d H:i:s") . '商户进件:' .$output.'--'.$data.PHP_EOL, FILE_APPEND | LOCK_EX);
       	$output=json_decode($output,true);
       	if($output['reply']['returnCode']['code']=='AAAAAA'){
       		$customerId=$output['reply']['customerId'];
       		M("merchants_mdaypay")->where(array('id'=>$merchants_mdaypay_id))->save(array('customerId'=>$customerId,'into_type'=>'2'));
       		$reg['code']='success';
       		$reg['msg']='进件成功';
       		$this->ajaxReturn($reg);
       	}else{
       		$message=$output['reply']['returnCode']['message'];
       		$file = fopen("./data/log/jnmsbank/into/1.txt", "r");
			$user=array();
			$i=0;
			//输出文本中所有的行，直到文件结束为止。
			while(!feof($file))
			{
			 $user[$i]= fgets($file);//fgets()函数从文件指针中读取一行
			 $i++;
			}
			$message=explode("[",$message);
			$message=$message[0];
			fclose($file);
			$keywords=$message;
			$goods='1';
			foreach ($user as $key => $value) {
				if (strstr($user[$key],$keywords ) !== false ){
					$goods=$user[$key];
				}
			}
			if($goods=='1'){
				$message=$keywords;
			}else{
				$data=explode("=",$goods);
				$message=$data[1];
			}
       		$reg['code']='error';
       		$reg['msg']=$message;
       		$this->ajaxReturn($reg);
       	}
    }
    public function check_into(){
        $id=I('id');
        M('merchants_mdaypay')->where(array('id'=>$id))->save(array('into_type'=>3));
        $this->success('审核成功');
    }
}