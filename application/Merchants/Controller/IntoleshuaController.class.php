<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * Class IntoxdlController
 * @package Merchants\Controller
 */
class IntoleshuaController extends AdminbaseController
{
    protected $merchants;
    protected $merchants_users;
    protected $merchants_leshua;
    protected $gzhMerchantName = '深圳前海洋仆淘电子商务有限公司';
    protected $publicsignalAppid = 'wx3fa82ee7deaa4a21';
    protected $publicDirectory = 'https://sy.youngport.com.cn/';
    protected $key = 'FBF50AD4E24183AD42DD5F259200FDB7';
    protected $agent_id = '7578577';

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_leshua =M("merchants_leshua");
//        $this->mch_notify_url = "";# https://sy.youngport.com.cn/notify/ls_mch_notify.php
    }

    public function upload_into()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 1048576;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'leshua/'; // 设置附件上传（子）目录
        // 上传文件
        $name = $_POST['data'];
        $info = $upload->upload();
        if ($info) {
            $data['type'] = 1;
            $data['name'] = $name;
            $data['path'] =  '/data/upload/'.$info[$name]['savepath'] . $info[$name]['savename'];
            echo json_encode($data);
            exit();
        } else {
            $data['type'] = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }

    # 进件列表
    public function index()
    {
        $merchant_name = I('merchant_name');
        $merchant_leshua = I('merchant_leshua');
        $m_id = I('m_id');
        $update_status = I('update_status');
        if($merchant_name){
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        if($merchant_leshua){
            $map['w.merchantId'] = $merchant_leshua;
            $formget['merchant_leshua'] = $merchant_leshua;
        }
        if($m_id){
            $map['w.m_id'] = $m_id;
            $formget['m_id'] = $m_id;
        }
        if($update_status !== ''){
            $map['w.update_status'] = $update_status;
            $formget['update_status'] = $update_status;
        }
        $count = $this->merchants_leshua->join('w left join ypt_merchants m on w.m_id=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $info = $this->merchants_leshua
            ->field('m.merchant_name,w.*')
            ->join('w left join ypt_merchants m on w.m_id=m.id')
            ->where($map)
            ->order('w.id desc')
            ->limit($page->firstRow , $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("info",$info);
        $this->assign("formget",$formget);
        $this->display();
    }

    # 添加进件
    public function add()
    {
        if (IS_POST) {
            $data = I("post.");
            if(!$data['m_id']){
                $this->error('参数不全');
            }
            $data['reqSerialNo'] = date('YmdHis').rand(10000000,99999999);
            if($data['into_id']){
                $id = $data['into_id'];
                $data = array_filter($data);
                $this->merchants_leshua->where(array('id' => $id))->save($data);
                $data = $this->merchants_leshua->where(array('id' => $id))->find();
            } else {
                $data['status'] = 1;
                $mdata = $this->merchants_leshua->where(array('m_id'=>$data['m_id']))->find($data);
                if($mdata){
                    $this->error('已有进件');exit;
                }
                $id = $this->merchants_leshua->add($data);
            }
            $this->url = 'https://pos.yeahka.com/api/merchant/register.do';
            $post_data['agentId'] = $this->agent_id;
            $post_data['version'] = '1.0';
            $post_data['reqSerialNo'] = $data['reqSerialNo'];
            $post_data['data'] = $this->get_data($data);
            $post_data['sign'] = $this->getSignVeryfy($post_data['data'], $this->key);
            if($data['merchantType'] != ''){
                if(empty($data['idcardFrontPic'])
                || empty($data['idcardBackPic'])
                || empty($data['bankCardFrontPic'])
                || empty($data['licensePic'])
                || empty($data['insidePic'])
                || empty($data['doorPic'])
                || empty($data['cashierDeskPic'])){
                    $this->error('图片信息不齐全');
                }
                $post_data['idcardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['idcardFrontPic'];
                $post_data['idcardBackPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['idcardBackPic'];
                $post_data['bankCardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['bankCardFrontPic'];
                $post_data['licensePic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['licensePic'];
                $post_data['insidePic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['insidePic'];
                $post_data['doorPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['doorPic'];
                $post_data['cashierDeskPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['cashierDeskPic'];
                if($data['isIndustryDining']){
                    $post_data['footHealthPermissionPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['footHealthPermissionPic'];
                }
                if($data['nonLegSettleAuthPic']){
                    $post_data['nonLegSettleAuthPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLegSettleAuthPic'];
                }
                if($data['nonLegIdcardFrontPic']){
                    $post_data['nonLegIdcardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLegIdcardFrontPic'];
                }
                if($data['nonLlegIdcardBackPic']){
                    $post_data['nonLlegIdcardBackPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLlegIdcardBackPic'];
                }
            }
            $this->writeLog('into.log', '参数', $post_data);
            $return = $this->request_post($this->url, $post_data);
            $this->writeLog('into.log', 'REUTRN', $return,0);
            $result = json_decode($return,true);
            if($result['respCode'] == '000000'){
                $result['data']['status'] = 2;
                $result['data']['key'] = $this->key;
                $res = $this->merchants_leshua->where(array('id' => $id))->save($result['data']);
                if($res){
                    $this->success('进件成功',U("Intoleshua/index"));
                } else {
                    dump($result);
                }
            } else {
                $this->merchants_leshua->where(array('id' => $id))->save(array('err_msg'=>$result['respMsg']));
                $this->error($result['respMsg']);
            }
        }else{
            $merchant_id=$_GET['id'];
            //$merchant_id=53;
            $list=M('Merchants')->where("id='{$merchant_id}'")->find();
            $uid=$list['uid'];
            $phone=M('Merchants_users')->where("id='{$uid}'")->find();
            $province = M('leshua_province')->field('F_province_no as p_num,F_province_name as p_name')->select();
            $this->assign('province', $province);
            $this->assign('phone',$phone);
            $this->assign('list',$list);
            $this->assign('id',$merchant_id);
            $merchants_mpay_data=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
            $this->assign('data',$merchants_mpay_data);
            $this->display();
        }
    }

    public function get_data($data)
    {
        $post_data['base'] = array(
            'merchantType' => $data['merchantType'],
            'name' => $data['name'],
            'idcard' => $data['idcard'],
            'mobile' => $data['mobile'],
            'merchantName' => $data['merchantName'],
            'province' => $data['province'],
            'city' => $data['city'],
            'area' => $data['area'],
            'address' => $data['address'],
        );
        $post_data['account'] = array(
            'type' => $data['type'],
            'branch' => $data['branch'],
            'unionpay' => $data['unionpay'],
            'holder' => $data['holder'],
            'cardId' => $data['cardId'],
            'mobile' => $data['bankMobile'],
        );
        $post_data['other'] = array(
            'gzhMerchantName' => $data['gzhMerchantName'],  //公众号名
            'publicsignalAppid' => $data['publicsignalAppid'],  //公众号appid$data['publicsignalAppid']
            'publicsignalAuthorizationDirectory' => $data['publicDirectory'],   //支付授权目录$data['publicDirectory']
            'publicsignalAppidGZ' => $data['publicsignalAppidGZ'],   //推荐关注
        );
        if($data['mccCode'])$post_data['other']['mccCode'] = $data['mccCode'];
        if($data['mccType'])$post_data['other']['mccType'] = $data['mccType'];
        if($data['userWx'])$post_data['other']['userWx'] = $data['userWx'];
        if($data['merchantType'] == 3){
            $post_data['base']['license'] = $data['license'];
            $post_data['base']['licenseFullName'] = $data['licenseFullName'];
            $post_data['base']['licenseAddress'] = $data['licenseAddress'];
            $post_data['base']['licenseStart'] = $data['licenseStart'];
            $post_data['base']['licenseEnd'] = $data['licenseEnd'];
            $post_data['other']['isIndustryDining'] = $data['isIndustryDining'];
        }

        return json_encode($post_data);
    }

    # 设置费率
    public function set_rate()
    {
        if(IS_POST){
            $this->url = 'https://pos.yeahka.com/api/merchant/open';
            $data=$_POST;
            $post_data['agentId'] = $this->agent_id;
            $post_data['version'] = '1.0';
            $post_data['reqSerialNo'] = date('YmdHis').rand(10000000,99999999);
            $post_data['data'] = $this->get_rete_data($data);
            $post_data['sign'] = $this->getSignVeryfy($post_data['data'], $this->key);
            $this->merchants_leshua->save($data);
            $this->writeLog('set_rate.log', '参数', $post_data);
            $return = $this->request_post($this->url, $post_data);
            $this->writeLog('set_rate.log', 'REUTRN', $return,0);
            $result = json_decode($return,true);
            if($result['respCode'] == '000000'){
                $this->success("设置成功");
            } else {
                $this->error($result['respMsg']);
            }
        } else {
            $id = I('id');
            $mch_id = I('mch_id');
            $this->id=$id;
            $this->mch_id=$mch_id;
            $this->display();
        }
    }

    public function get_rete_data($data)
    {
        $post_data['fee']['openType'] = 1;
        $post_data['fee']['merchantId'] = $data['mch_id'];
        $post_data['fee']['weixin'] = array(
            't1' => array('rate'=>$data['wx_t1_rate']*100),
            't0' => array('rate'=>$data['wx_t0_rate']*100),
        );
        $post_data['fee']['alipay'] = array(
            't1' => array('rate'=>$data['ali_t1_rate']*100),
            't0' => array('rate'=>$data['ali_t0_rate']*100),
        );

        return json_encode($post_data);
    }

    # 编辑
    public function edit()
    {
        if(IS_POST){

        } else {
            $id = I('id');
            $info = $this->merchants_leshua->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }
    # 编辑
    public function detail()
    {
        $id = I('id');
        $info = $this->merchants_leshua->where(array('id' => $id))->find();
        $this->assign('info', $info);
        $this->assign('id', $id);
        $this->display();
    }

    # 切换T0/T1
    public function switcht()
    {
        if(IS_POST){
            $val = I('val');
            $id = I('id');
            $res = $this->merchants_leshua->where(array('id'=>$id))->save(array("is_t0"=>$val));
            if($res){
                $this->ajaxReturn(array('code'=>1));
            } else {
                $this->ajaxReturn(array('code'=>0));
            }
        }
    }

    # 查询进件信息
    public function query_info()
    {
        $this->url = 'https://pos.yeahka.com/api/merchant/get.do';
        $data['merchantId'] = I('merchantId');
        $post_data['agentId'] = $this->agent_id;
        $post_data['version'] = '1.0';
        $post_data['reqSerialNo'] = date('YmdHis').rand(10000000,99999999);
        $post_data['data'] = json_encode($data);
        $post_data['sign'] = $this->getSignVeryfy($post_data['data'], $this->key);
//        $this->writeLog('query_info.log', '参数', $post_data);
        $return = $this->request_post($this->url, $post_data);
//        $this->writeLog('query_info.log', 'REUTRN', $return,0);{$base.auditStatus}
//        1：否决 0：通过 2未审核
        $result = json_decode($return,true);
        if($result['data']['base']['auditStatus'] == 0){
            M('merchants_leshua')->where("merchantId=$data[merchantId]")->save(array('update_status'=>3));
        }
        if($result['data']['base']['auditStatus'] == 1){
            M('merchants_leshua')->where("merchantId=$data[merchantId]")->save(array('update_status'=>1,'err_msg'=>$result['data']['base']['nopassReason']));
        }
        if($result['data']['base']['auditStatus'] == 2){
            M('merchants_leshua')->where("merchantId=$data[merchantId]")->save(array('update_status'=>2));
        }
        if($result['respCode'] == '000000'){
            $this->assign('result', $result);
            $this->assign('base', $result['data']['base']);
            $this->assign('account', $result['data']['account']);
            $this->assign('merchantPics', $result['data']['merchantPics']);
            $this->display();
        } else {
            $this->error($result['respMsg']);
        }
    }

    # 编辑进件信息
    public function edit_info()
    {
        if(IS_POST){
            $data = I("post.");
//            $this->url = 'https://pos.lepass.cn/api/merchant/update';
            $this->url = 'https://pos.yeahka.com/api/merchant/update.do';
            $post_data['agentId'] = $this->agent_id;
            $post_data['version'] = '1.0';
            $post_data['reqSerialNo'] = date('YmdHis').rand(10000000,99999999);
            $post_data['data'] = $this->get_edit_data($data);
            $post_data['sign'] = $this->getSignVeryfy($post_data['data'], $this->key);
            if(!empty($data['idcardFrontPic'])) $post_data['idcardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['idcardFrontPic'];
            if(!empty($data['idcardBackPic'])) $post_data['idcardBackPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['idcardBackPic'];
            if(!empty($data['bankCardFrontPic'])) $post_data['bankCardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['bankCardFrontPic'];
            if(!empty($data['licensePic'])) $post_data['licensePic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['licensePic'];
            if(!empty($data['insidePic'])) $post_data['insidePic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['insidePic'];
            if(!empty($data['doorPic'])) $post_data['doorPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['doorPic'];
            if(!empty($data['cashierDeskPic'])) $post_data['cashierDeskPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['cashierDeskPic'];
            if(!empty($data['footHealthPermissionPic'])) $post_data['footHealthPermissionPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['footHealthPermissionPic'];
            if(!empty($data['nonLegSettleAuthPic'])) $post_data['nonLegSettleAuthPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLegSettleAuthPic'];
            if(!empty($data['nonLegIdcardFrontPic'])) $post_data['nonLegIdcardFrontPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLegIdcardFrontPic'];
            if(!empty($data['nonLlegIdcardBackPic'])) $post_data['nonLlegIdcardBackPic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . $data['nonLlegIdcardBackPic'];

            $this->writeLog('edit_info.log', '参数', $post_data);
            $return = $this->request_post($this->url, $post_data);
            $this->writeLog('edit_info.log', 'REUTRN', $return,0);
            $result = json_decode($return,true);
            if($result['respCode'] == '000000'){
                $save_data = array_filter($data);
                $save_data['update_status'] = 2;
                $res = $this->merchants_leshua->where(array('id' => $data['into_id']))->save($save_data);
                if($res){
                    $this->success('材料已提交，等待审核中',U("Intoleshua/index"));
                } else {
                    $this->error("材料提交失败");
                }
            } else {
                $this->merchants_leshua->where(array('id' => $data['into_id']))->save(array('err_msg'=>$result['respMsg'],'update_status'=>1));
                $this->error($result['respMsg']);
            }
        } else {
            $id = I('id');
            $info = $this->merchants_leshua->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

    public function getCity()
    {
        if(IS_POST){
            $num = I('num');
            $type = I('type');
            if($type == 'city'){
                $data = M('leshua_city')->field('F_city_no as numb,F_city_name as name')->where(array('F_province_no'=>$num))->select();
            }else if($type == 'area'){
                $data = M('leshua_area')->field('F_area_no as numb,F_area_name as name')->where(array('F_city_no'=>$num))->select();
                $this->ajaxReturn(array('code' => '0000', 'data'=> $data));
            } else {
                $data = '';
            }
            $this->ajaxReturn(array('code' => '0000', 'data'=> $data));
        }
    }

    # IP地址设置
    public function set_ip()
    {
        if(IS_POST){
            $data = I('');
            $res = $this->merchants_leshua->where(array('id' => $data['id']))->save($data);
            if($res !== false) $this->ajaxReturn(array('code'=>'0000'));
            else $this->ajaxReturn(array('code'=>'1000', 'msg'=>'设置失败'));
        } else {
            $id = I('id');
            $info = $this->merchants_leshua->where(array('id' => $id))->find();
            $address = $info['province'] . $info['city'] . $info['area'] . $info['address'];
            $this->assign('address', $address);
            $this->assign('ip_address', $info['ip_address']);
            $this->assign('id', $id);
            $this->display();
        }
    }

    public function same()
    {
        $id = I('id');
        if(IS_POST){
            $input = I('');
            $mch_ids = $input['mch_ids'];
            unset($input['mch_ids']);
            if(empty($mch_ids)){
                $this->error('没有数据');
            }
            $into_data = array();
            foreach ($mch_ids as $v) {
                $check = M('merchants_leshua')->where("m_id=$v")->getField('id');
                if($check){
                    continue;
                } else {
                    $input['m_id'] = $v;
                    $input['is_fendian'] = 1;
                    $input['status'] = 2;
                    $input['update_status'] = 3;
                    $input['merchantId'] = $input['merchant_id'];
                    $into_data[] = $input;
                }
            }
            $res = $this->merchants_leshua->addAll($into_data);
            if($res){
                $this->success('同步成功', U('index'));
            } else {
                $this->error('分店数据已经同步');
            }

        } else {
            $into_info = $this->merchants_leshua
                ->field('m_id,username,merchantId as merchant_id,key,ip_address,is_t0,wx_t1_rate,wx_t0_rate,ali_t1_rate,ali_t0_rate,licenseFullName')
                ->where(array('id' => $id))
                ->find();
            $mch_id = $into_info['m_id'];
            $info = array();
            $name = array('商户ID','乐刷登录账户','乐刷商户ID','密钥','IP地址','是否T0','微信T0费率','微信T1费率','支付宝T0费率','支付宝T1费率','商户名称');
            $i = 0;
            foreach ($into_info as $key => $val) {
                $data['key'] = $key;
                $data['value'] = $val;
                $data['name'] = $name[$i];
                $info[] = $data;
                $i++;
            }
            $mch_info = M('merchants')->field('id,merchant_name')->where("mid=$mch_id")->select();
            if(empty($mch_info)){
                $this->error('该商户没有分店');exit;
            }
            $this->assign('info', $info);
            $this->assign('mch_name', $into_info['licenseFullName']);
            $this->assign('mch_info', $mch_info);
            $this->display();
        }
    }
    
    # 修改商户信息
    public function get_edit_data($data)
    {
        $post_data['base'] = array(
            'merchantId' => $data['merchantId'],
            'merchantType' => $data['merchantType'],
            'mobile' => $data['mobile'],
            'merchantName' => $data['merchantName'],
            'province' => $data['province'],
            'city' => $data['city'],
            'area' => $data['area'],
            'address' => $data['address'],
            'license' => $data['license'],
            'licenseFullName' => $data['licenseFullName'],
            'licenseAddress' => $data['licenseAddress'],
            'licenseStart' => $data['licenseStart'],
            'licenseEnd' => $data['licenseEnd'],
            'licenseEnd' => $data['licenseEnd'],
        );
        $post_data['base'] = array_filter($post_data['base']);
        $post_data['account'] = array(
            'type' => $data['type'],
            'branch' => $data['branch'],
            'unionpay' => $data['unionpay'],
            'holder' => $data['holder'],
            'cardId' => $data['cardId'],
            'mobile' => $data['bankMobile'],
        );
        $post_data['account'] = array_filter($post_data['account']);
        $post_data['other'] = array(
            'mccCode' => $data['mccCode'],
            'mccType' => $data['mccType'],
            'userWx' => $data['userWx'],
            'gzhMerchantName' => $data['gzhMerchantName'],  //公众号名
            'publicsignalAppid' => $data['publicsignalAppid'],  //公众号appid$data['publicsignalAppid']
            'publicsignalAuthorizationDirectory' => $data['publicDirectory'],   //支付授权目录$data['publicDirectory']
            'publicsignalAppidGZ' => $data['publicsignalAppidGZ'],   //推荐关注
            'isIndustryDining' => $data['isIndustryDining'],   //是否餐饮
        );
        $post_data['other'] = array_filter($post_data['other']);
        $post_data['fee']['openType'] = 1;
        $post_data['fee']['weixin'] = array(
            't1' => array('rate'=>$data['wx_t1_rate']*100),
            't0' => array('rate'=>$data['wx_t0_rate']*100),
        );
        $post_data['fee']['alipay'] = array(
            't1' => array('rate'=>$data['ali_t1_rate']*100),
            't0' => array('rate'=>$data['ali_t0_rate']*100),
        );
        $post_data = array_filter($post_data);

        return json_encode($post_data);
    }
    
    //支付接口统一签名
    private function getSignVeryfy($para_temp, $key)
    {
        $prestr = base64_encode(md5('lepos'.$key.$para_temp));
        return $prestr;
    }

    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param);
        }
        file_put_contents($path . $file_name, date("Y-m-d H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    private function request_post($url, $data = '',$time=30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, $time);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if ($output) {
            curl_close($curl);
            return $output;
        } else {
            $error = curl_errno($curl);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($curl);
            return false;
        }
    }
    private function get_date_dir($path = '/data/log/leShua/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
//        $d = $Y . '/' . date('d');
        if (!file_exists($Y)) mkdir($Y, 0777, true);
//        if (!file_exists($d)) mkdir($d, 0777);

        return $Y . '/';
    }
}
