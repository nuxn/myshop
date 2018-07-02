<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;
use \ZipArchive;

/**
 * 商户进件(入驻)
 * Class IntosxfController
 * @package Merchants\Controller
 */
class IntosxfController extends AdminbaseController
{
    protected $merchants;
    protected $merchants_users;
    protected $sxfModel;
    protected $input;
    protected $merUrl;
    private $orgId;

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users = M("merchants_users");
        $this->sxfModel = D("MerchantsUpsxf");
        $this->merUrl = 'https://sy.youngport.com.cn/Pay/Banksxf/mer_notify';
//        $this->orgId = '07296653';
        $this->orgId = '65554373';
    }

    #进件列表
    public function index()
    {
        $mch_name = I('mch_name');
        $merchant_id = I('merchant_id');
        $bandconf = I('bandconf');
        $mno = I('mno');
        if($mch_name){
            $map['m.merchant_name'] = array('like', $mch_name);
        }
        if($merchant_id){
            $map['b.merchant_id'] = $merchant_id;
        }
        if($bandconf){
            if($bandconf == 1) $map['b.subMchId'] = array('neq', 0);
            if($bandconf == 2) $map['b.subMchId'] = array('eq', 0);
        }
        if($mno){
            $map['b.mno'] = $mno;
        }
        $count = $this->sxfModel->join('b left join ypt_merchants m on b.merchant_id=m.id')->where($map)->count();

        $page = $this->page($count, 15);
        $info = $this->sxfModel
            ->field('b.id,b.mno,b.subMchId,b.task_code,b.merchant_id,b.status,b.add_time,m.merchant_name')
            ->join('b left join ypt_merchants m on b.merchant_id=m.id')
            ->where($map)
            ->order('b.id desc')
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign("info", $info);
        $this->assign("map", I(''));
        $this->display();
    }


    # 添加进件
    public function add()
    {
        if (IS_POST) {
            $this->input = I("post.");
            $input = array_filter($this->input);
            $taskCode = $this->getTaskCode();
            $this->input['task_code'] = $taskCode;
            unset($input['merchant_id']);
            unset($input['img']);
//            $input['taskCode'] = 'SXF012018062017031592616709762';
            $input['taskCode'] = $taskCode;
            $input['merUrl'] = $this->merUrl;

            $this->sxfModel->setNull();
            $this->sxfModel->setInfoParams($input);
            $result = $this->sxfModel->batchFeedInfo();
            if($result['code'] == 'SXF0000'){
                $return = $result['respData'];
                if($return['bizCode'] == '00'){
                    $this->adddb();
                    $this->ajaxReturn(array('code'=> '0000','msg'=>''));
                } else {
                    $this->ajaxReturn(array('code'=> '1000','msg'=>$return['bizMsg']));
                }
            } else {
                $this->ajaxReturn(array('code'=> '1000','msg'=>$result['msg']?:'失败'));
            }
        } else {
            $merchant_id = I('id');
            if($this->sxfModel->where(array('merchant_id'=>$merchant_id))->getField('id')){
                redirect(U('index'));
            }
            $province = $this->get_province();
            $list = M('Merchants')->field('m.id as `商户id`,m.merchant_name as `商户名称`,m.merchant_jiancheng as `商户简称`,u.user_phone as `手机号`,
            header_interior_img as `门头`,interior_img,business_license_number as `营业执照号`,business_license as `营业执照`,province as `省`,city as `市`,county as `区县`,address as `详细地址`,
            operator_name as `姓名`,id_number as `身份证`,positive_id_card_img as `法人正面`,id_card_img as `法人反面`,case account_type when 0 then "对私" when 1 then "对公" else "未知" end `账户类型`,
            account_name as `账户名`,bank_account as `银行`,branch_account as `支行`,bank_account_no as `卡号`,positive_bank_card_img as `卡正面`')
                ->join('m left join ypt_merchants_users u on m.uid=u.id')
                ->where("m.id='{$merchant_id}'")->find();
            $base = array('营业执照号','身份证','卡号');
            $img = array('门头','营业执照','法人正面','法人反面','卡正面','法人结算正面','法人结算反面','内景','收银台');
            foreach ($list as $key => &$val) {
                if($key == 'interior_img'){
                    if($val){
                        $arr = explode(',', $val);
                        $a['内景'] = $arr[0];
                        $a['收银台'] = $arr[1];
                        $this->array_insert($list,5,$a);
                    }
                    unset($list[$key]);
                    continue;
                }
                if(in_array($key, $base)){
                    $val = decrypt($val);
                    continue;
                }
                if(in_array($key, $img)){
                    if(strpos($val,'merchants') === 0){
                        $val = './data/upload/' . $val;
                    }
                    $com = strpos($val,'com.cn');
                    if($com){
                        $val = substr($val,$com+6);
                    }
                }
            }
            $list['法人结算正面'] = $list['法人正面'];
            $list['法人结算反面'] = $list['法人反面'];
            $list['结算身份证号'] = $list['身份证'];
            $this->assign('img', $img);
            $this->assign('list', array_filter($list));
            $this->assign('id', $merchant_id);
            $this->assign('province', $province);
            $this->display();
        }
    }

    public function array_insert (&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }

    # 编辑
    public function edit()
    {
        if (IS_POST) {
            $input = I("post.");
            $imgs = $input['img'];
            foreach ($imgs as $key => $val) {
                if($val){
                    $input[$key] = $val;
                }
            }
            $input = array_filter($input);
            $merchant_id = $input['merchant_id'];
            if(!$merchant_id){
                $this->ajaxReturn(array('code'=> '1000','msg'=>'商户id为空'));
            }
            $id = $this->sxfModel->where(array('merchant_id'=>$merchant_id))->getField('id');
            if($id){
                $this->sxfModel->where(array('id'=>$id))->save($input);
            } else {
                $this->sxfModel->add($input);
            }
            $this->ajaxReturn(array('code'=> '0000'));
        } else {
            $id = I('id');
            $province = $this->get_province();
            $info = $this->sxfModel->where(array('id' => $id))->find();
            $city = M('address_sxf')->where(array('id'=>$info['regCityCd']))->getField('name');
            $disc = M('address_sxf')->where(array('id'=>$info['regDistCd']))->getField('name');
            $mccN = M('mcc_sxf')->where(array('mccCd'=>$info['mccCd']))->getField('mccNm');
            $this->assign('province', $province);
            $this->assign('cityN', $city);
            $this->assign('discN', $disc);
            $this->assign('mccN', $mccN);
            $this->assign('data', $info);
            $this->assign('id', $id);
            $this->assign('edit', I('edit'));
            $this->display();
        }
    }


    public function wxconfig()
    {
        if (IS_POST) {
            $input = I("post.");
            $this->input = array_filter($input);
            $this->sxfModel->where(array('id'=>$input['id']))->save($this->input);
            $cof_res = $this->bindconfig();
            if($cof_res['code'] == 'SXF0000'){
                $cof_ret = $cof_res['respData'];
                if($cof_ret['bizCode'] != '0000'){
                    $this->ajaxReturn(array('code'=> '1000','msg'=>$cof_ret['bizMsg']));
                }
            } else {
                $this->ajaxReturn(array('code'=> '1000','msg'=>$cof_res['msg']?:'失败'));
            }
            $dir_res = $this->bindDirectory();
            if($dir_res['code'] == 'SXF0000'){
                $dir_ret = $dir_res['respData'];
                if($dir_ret['bizCode'] != '0000'){
                    $this->ajaxReturn(array('code'=> '2000','msg'=>$dir_ret['bizMsg']));
                }
            } else {
                $this->ajaxReturn(array('code'=> '2000','msg'=>$dir_res['msg']?:'失败'));
            }
            $scr_res = $this->bindScribeAppid();
            if($scr_res == 'no data'){
                $this->ajaxReturn(array('code'=> '0000'));
            }
            if($scr_res['code'] == 'SXF0000'){
                $scr_ret = $scr_res['respData'];
                if($scr_ret['bizCode'] != '0000'){
                    $this->ajaxReturn(array('code'=> '3000','msg'=>$scr_ret['bizMsg']));
                }
            } else {
                $this->ajaxReturn(array('code'=> '3000','msg'=>$scr_res['msg']?:'失败'));
            }

            $this->ajaxReturn(array('code'=> '0000'));
        } else {
            $id =  I('id');
            $mno = I('mno');
            $see = I('see', 0);
            if($see){
                $info = $this->sxfModel->where(array('id'=>$id))->field('subMchId,subAppid,jsapiPath,subscribeAppid')->find();
                $this->assign('info', $info);
            }
            $this->assign('see', $see);
            $this->assign('id', $id);
            $this->assign('mno', $mno);
            $this->display();
        }
    }

    public function bindconfig()
    {
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('mno', $this->input['mno']);
        $this->sxfModel->setParameters('subAppid', $this->input['subAppid']);
        $this->sxfModel->setParameters('subMchId', $this->input['subMchId']);
        return $this->sxfModel->bindconfig();
    }

    public function bindDirectory()
    {
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('mno', $this->input['mno']);
        $this->sxfModel->setParameters('subMchId', $this->input['subMchId']);
        $this->sxfModel->setParameters('jsapiPath', $this->input['jsapiPath']);
        return $this->sxfModel->bindDirectory();
    }

    public function bindScribeAppid()
    {
        if($this->input['subscribeAppid']){
            $this->sxfModel->setNull();
            $this->sxfModel->setParameters('mno', $this->input['mno']);
            $this->sxfModel->setParameters('subMchId', $this->input['subMchId']);
            $this->sxfModel->setParameters('subAppid', $this->input['subAppid']);
            $this->sxfModel->setParameters('subscribeAppid', $this->input['subscribeAppid']);
            return $this->sxfModel->bindScribeAppid();
        }
        return 'no data';
    }

    public function adddb()
    {
        $this->input['status'] = 1;
        $merchant_id = $this->input['merchant_id'];
        $id = $this->sxfModel->where(array('merchant_id'=>$merchant_id))->getField('id');
        if($id){
            $this->sxfModel->where(array('id'=>$id))->save($this->input);
        } else {
            $this->sxfModel->add($this->input);
        }
    }

    public function get_province()
    {
        return M('address_sxf')->where("pid=1")->select();
    }

    public function get_city()
    {
        $prov = I('data');
        $result = M('address_sxf')->where(array('pid'=>$prov))->select();
        $this->ajaxReturn(array('code' => '0000', 'data' => $result));
    }

    // 获取 MCC 行业大类信息
    public function getIdtTyps()
    {
        $idtTypCode = I('data');
        $result = M('mcc_sxf')->field('mccCd as mcc_cd,mccNm as mcc_name')->where(array('type'=>$idtTypCode))->select();
        $this->ajaxReturn(array('code' => '0000', 'data' => $result));
    }

    public function getTaskCode()
    {
        $path = $this->getZip();
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('file', "@$path");
        $this->sxfModel->setParameters('orgId', $this->orgId);
        $this->sxfModel->setParameters('reqId', md5(getOrderNumber()));
        $result = $this->sxfModel->getTaskCode();
        if($result['code'] == 'SXF0000'){
            $return = $result['respData'];
            if($return['bizCode'] == '00'){
                return $result['respData']['data'];
            } else {
                $this->ajaxReturn(array('code'=> '1000','msg'=>$return['bizMsg']));
            }
        } else {
            $this->ajaxReturn(array('code'=> '1000','msg'=>$result['msg']?:$result['message']));
        }
    }

    public function getZip()
    {
        $imgs = array_filter($this->input['img']);
        if(empty($imgs)){
            $this->ajaxReturn(array('code'=> '1000','msg'=>'图片上传失败'));
        }
        $filename = "./data/upload/sxf/image.zip";
        unlink($filename);
        $zip = new \ZipArchive();
        $zip->open($filename,ZipArchive::CREATE);   //打开压缩包
        foreach ($imgs as $key => $val) {
            $path = "./data/upload/sxf/{$key}.jpg";
            if($val){
                copy($val, $path);
                if(!file_exists($path)){
                    $this->ajaxReturn(array('code'=> '1000','msg'=>'图片上传失败'));
                }
                $image = new \Think\Image();
                $image->open($path);
                $image->thumb(800,800)->save($path);
                $zip->addFile($path,basename($path));   //向压缩包中添加文件
                $this->input[$key] = $val;
            }
        }
        $zip->close();  //关闭压缩包
        $size = filesize($filename);
        if($size >= 5242880){
            $this->ajaxReturn(array('code'=> '1000','msg'=>'图片过大'));
        }
        return $filename;
    }

    public function upload()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3548576;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/'; // 设置附件上传根目录
        $upload->savePath = 'merchants/'; // 设置附件上传（子）目录
        $upload->saveName = time().mt_rand();
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            $url = './data/upload/' . $info['file']['savepath'] . $info['file']['savename'];
            $image = new \Think\Image();
            $image->open($url);
            $image->thumb(1000,1000)->save($url);
            $this->ajaxReturn(array('code' => '0', 'msg' => '上传成功', 'data' => $url));
        } else {
            $message = $upload->getError();
            $this->ajaxReturn(array('code' => '10', 'msg' => $message));
        }
    }
}
