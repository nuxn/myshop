<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 口碑商户操作类
 * by lxl
 * Class AlikoubeiController
 * @package Merchants\Controller
 */
class AlikoubeiController extends AdminbaseController
{
    public function index()
    {
        $mch_id = I('mch_id', '');
        $merchant_name = I('merchant_name', '');
        if(!empty($mch_id)){
            $map['a.mch_id'] = $mch_id;
        }
        if(!empty($merchant_name)){
            $map['b.merchant_name'] = array('LIKE', $merchant_name);
        }
        $map['a.id'] = array('gt', 0);

        $data = M('merchants_alikoubei')->field('a.*,b.merchant_name')
            ->join('a left join ypt_merchants b on a.mch_id=b.id')
            ->where($map)
            ->select();
        $formget['merchant_name'] = $merchant_name;
        $formget['mch_id'] = $mch_id;
        $this->assign('formget', $formget);
        $this->assign('data', $data);
        $this->display();
    }

    public function add()
    {
        $model = M('merchants_alikoubei');
        if (IS_POST) {
            $mch_id = I('mch_id');
            $koubei_url = I('koubei_url');
            $id = I('id');
            $seach = $model->where(array('mch_id' => $mch_id))->find();
            if ($id == 0) {
                if ($seach) {
                    $this->error('商户已存在');
                }
                $add_data['mch_id'] = $mch_id;
                $add_data['koubei_url'] = $koubei_url;
                $add_data['add_time'] = time();
                $res = $model->add($add_data);
                if ($res) {
                    $this->success('添加成功');
                }
            } else {
                $edit_data['mch_id'] = $mch_id;
                $edit_data['koubei_url'] = $koubei_url;
                $res = $model->where(array('id' => $id))->save($edit_data);
                if ($res) {
                    $this->success('修改成功');
                }
            }
        } else {
            $id = I('id', 0);
            if ($id == 0) {

            } else {
                $data = $model->where(array('id' => $id))->find();
                $this->assign('data', $data);
            }
            $this->display();
        }
    }

    public function delete()
    {
        $model = M('merchants_alikoubei');
        $id = I('id');
        $res = $model->where(array('id' => $id))->delete();
        if ($res) {
            $this->success();
        } else {
            $this->error();
        }
    }

}