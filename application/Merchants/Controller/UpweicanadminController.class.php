<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * 获取微众分配的商户ID用于支付
 * Class UpwzadminController
 * @package Merchants\Controller
 */
class UpweicanadminController extends AdminbaseController
{
    protected $shopcates;
    protected $merchants;
    protected $merchants_upwx;
    protected $merchants_users;

    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_upwx = M("merchants_upwx");
    }


    /**
     * 进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name');
        $is_weican = I('is_weican');
        if($merchant_name){
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        if($is_weican){
            $map['w.is_weican'] = $is_weican;
            $formget['is_weican'] = $is_weican;
        }
        $count = $this->merchants_upwx->join('w left join ypt_merchants m on w.mid=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $upwxs = $this->merchants_upwx
            ->field('w.id,w.mid,w.sub_mchid,w.status,m.merchant_name,w.is_weican,w.cost_rate')
            ->join('w left join ypt_merchants m on w.mid=m.id')
            ->where($map)
            ->order('w.update_time desc')
            ->limit($page->firstRow , $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("upwxs",$upwxs);
        $this->assign("formget",$formget);
        $this->display();
    }


    /**
     * 添加进件
     */
    public function add()
    {
        if (IS_POST) {
            $data = I("post.");
            if(!$data['mid'] || !$data['sub_mchid']){
                $this->error('参数不全');
            }
            $check = $this->merchants_upwx->where(array('mid' => $data['mid']))->find();
            $find = $this->merchants->where(array('id'=>$data['mid']))->find();
            if($check){
                $this->error('已存在围餐计划中');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $data['status'] = '1';
            $data['mchid'] = '1420218502';
            $data['cost_rate'] = '0.0';
            $data['add_time'] = time();
            $data['update_time'] = time();
            $res = $this->merchants_upwx->add($data);
            if($res){
                $this->redirect(U('Upweicanadmin/index'));
            } else{
                $this->success('未作改动');
            }
        }else{
            $this->display();
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if(IS_POST){
            $data = I('post.');
            if(!$data['mid'] || !$data['sub_mchid']){
                $this->error('参数不全');
            }
            $data['update_time'] = time();
            $data['status'] = '1';
            $this->merchants_upwx->save($data);
            $this->redirect(U('Upweicanadmin/index'));
        } else {
            $id = I('id');
            $info = $this->merchants_upwx->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->display();
        }
    }

}
