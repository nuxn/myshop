<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

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

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->sxfModel =D("MerchantsUpsxf");
    }

    /**
     * 进件列表
     */
    public function index()
    {
        $count = $this->sxfModel->join('b left join ypt_merchants m on b.mch_id=m.id')->count();

        $page = $this->page($count, 20);
        $info = $this->sxfModel
            ->field('b.id,b.mch_id,b.status,b.add_time,m.merchant_name')
            ->join('b left join ypt_merchants m on b.mch_id=m.id')
            ->order('b.id desc')
            ->limit($page->firstRow , $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("info",$info);
        $this->display();
    }


    /**
     * 添加进件
     */
    public function add()
    {
        if (IS_POST) {
            $data = I("post.");
            if(!$data['m_id']){
                $this->error('参数不全');
            }
            $check = $this->sxfModel->where(array('m_id' => $data['m_id']))->find();
            $find = $this->merchants->where(array('id'=>$data['m_id']))->find();
            if($check){
                $this->error('已存在');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $res = $this->sxfModel->add($data);
            if($res){
                $this->redirect(U('Intoxdl/index'));
            } else{
                $this->success('未作改动');
            }
        }else{
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
        $this->sxfModel->setParameters('addressType', '01');
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        return $result['respData']['data'];
    }

    public function get_city()
    {
        $prov = I('data');
        $this->sxfModel->setParameters('addressType', '02');
        $this->sxfModel->setParameters('addressCode', $prov);
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        $this->ajaxReturn(array('code'=>'0000', 'data'=>$result['respData']['data']));
    }

    public function get_area()
    {
        $prov = I('data');
        $this->sxfModel->setParameters('addressType', '03');
        $this->sxfModel->setParameters('addressCode', $prov);
        $this->sxfModel->setParameters('blackFlag', '00');
        $result = $this->sxfModel->get_address();
        $this->ajaxReturn(array('code'=>'0000', 'data'=>$result['respData']['data']));
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if(IS_POST){
            $data = I('post.');
            $id = I('id');
            if(!$data['m_id']){
                $this->error('参数不全');
            }
            unset($data['id']);
            if($this->sxfModel->where(array('id'=>$id))->save($data)){
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


}
