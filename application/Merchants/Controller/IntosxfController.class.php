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

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users = M("merchants_users");
        $this->sxfModel = D("MerchantsUpsxf");
        $this->merUrl = 'https://sy.youngport.com.cn/Pay/Banksxf/mer_notify';
    }

    #进件列表
    public function index()
    {
        $count = $this->sxfModel->join('b left join ypt_merchants m on b.merchant_id=m.id')->count();

        $page = $this->page($count, 20);
        $info = $this->sxfModel
            ->field('b.id,b.merchant_id,b.status,b.add_time,m.merchant_name')
            ->join('b left join ypt_merchants m on b.merchant_id=m.id')
            ->order('b.id desc')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("info", $info);
        $this->display();
    }


    # 添加进件
    public function add()
    {
        if (IS_POST) {
            $this->input = I("post.");
//            $taskCode = $this->getTaskCode();
            $input = array_filter($this->input);
            unset($input['img']);
            $input['taskCode'] = 'SXF012018062017031592616709762';
            $input['merUrl'] = $this->merUrl;

            $this->sxfModel->setNull();
            $this->sxfModel->setInfoParams($input);
            $result = $this->sxfModel->batchFeedInfo();
            if($result['code'] == 'SXF0000'){
                $return = $result['respData'];
                if($return['bizCode'] == '00'){
                    $this->ajaxReturn(array('code'=> '0000','msg'=>''));
                } else {
                    $this->ajaxReturn(array('code'=> '1000','msg'=>$return['bizMsg']));
                }
            } else {
                $this->ajaxReturn(array('code'=> '1000','msg'=>$result['msg']));
            }
        } else {
            $merchant_id = $_GET['id'];
            $province = $this->get_province();
            $list = M('Merchants')->where("id='{$merchant_id}'")->find();
            $this->assign('list', $list);
            $this->assign('id', $merchant_id);
            $this->assign('province', $province);
            $this->display();
        }
    }

    public function get_province()
    {
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('addressType', '01');
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        return $result['respData']['data'];
    }

    public function get_city()
    {
        $prov = I('data');
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('addressType', '02');
        $this->sxfModel->setParameters('addressCode', $prov);
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        $this->ajaxReturn(array('code' => '0000', 'data' => $result['respData']['data']));
    }

    public function get_area()
    {
        $prov = I('data');
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('addressType', '03');
        $this->sxfModel->setParameters('addressCode', $prov);
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        $this->ajaxReturn(array('code' => '0000', 'data' => $result['respData']['data']));
    }

    // 获取 MCC 行业大类信息
    public function getIdtTyps()
    {
        $idtTypCode = I('data');
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('idtType', '02');
        $this->sxfModel->setParameters('idtTypCode', $idtTypCode);
        $result = $this->sxfModel->getIdtTyps();
        $this->ajaxReturn(array('code' => '0000', 'data' => $result['respData']['data']));
    }

    public function getTaskCode()
    {
        $path = $this->getZip();
        $this->sxfModel->setNull();
        $this->sxfModel->setParameters('file', "@$path");
        $this->sxfModel->setParameters('orgId', '07296653');
        $this->sxfModel->setParameters('reqId', md5(getOrderNumber()));
        $result = $this->sxfModel->getTaskCode();
        return $result['respData']['data'];
    }

    # 编辑
    public function edit()
    {
        if (IS_POST) {
            $data = I('post.');
            $id = I('id');
            if (!$data['m_id']) {
                $this->error('参数不全');
            }
            unset($data['id']);
            if ($this->sxfModel->where(array('id' => $id))->save($data)) {
                $this->redirect(U('Intoxdl/index'));
            } else {
                $this->error('未修改');
            }
        } else {
            $id = I('id');
            $info = $this->sxfModel->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

    public function getZip()
    {
        $imgs = $this->input['img'];
        $filename = "./data/upload/sxf/imagefile.zip";
        $zip = new \ZipArchive();
        $zip->open($filename,ZipArchive::CREATE);   //打开压缩包
        foreach ($imgs as $key => $val) {
            $path = "./data/upload/sxf/{$key}.jpg";
            if($val){
                copy($val, $path);
                $zip->addFile($path,basename($path));   //向压缩包中添加文件
                $this->input[$key] = $val;
            }
        }
        $zip->close();  //关闭压缩包
        return $filename;
    }

    public function upload()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 1048576;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/'; // 设置附件上传根目录
        $upload->savePath = 'merchants/'; // 设置附件上传（子）目录
        $upload->saveName = time().mt_rand();
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            $url = './data/upload/' . $info['file']['savepath'] . $info['file']['savename'];
            $this->ajaxReturn(array('code' => '0', 'msg' => '上传成功', 'data' => $url));
        } else {
            $message = $upload->getError();
            $this->ajaxReturn(array('code' => '10', 'msg' => $message));
        }
    }
}
