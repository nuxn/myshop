<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 口碑商户操作类
 * by lxl
 * Class AlikoubeiController
 * @package Merchants\Controller
 */
class AliintoController extends AdminbaseController
{
    public function index()
    {
        $mid = I('mid', '');
        $merchant_name = I('merchant_name', '');
        if(!empty($mid)){
            $map['a.mid'] = $mid;
        }
        if(!empty($merchant_name)){
            $map['b.merchant_name'] = array('LIKE', $merchant_name);
        }
        $map['a.id'] = array('gt', 0);

        $data = M('merchants_ali')->field('a.*,b.merchant_name')
            ->join('a left join ypt_merchants b on a.mid=b.id')
            ->where($map)
            ->select();
        $formget['merchant_name'] = $merchant_name;
        $formget['mid'] = $mid;
        $this->assign('formget', $formget);
        $this->assign('data', $data);
        $this->display();
    }

    public function add()
    {
        $model = M('merchants_ali');
        if (IS_POST) {
            $mid = I('mid');
            $id = I('id');
            $ali_mchid = I('ali_mchid');
            $ali_token = I('ali_token','');
            $seach = $model->where(array('mid' => $mid))->find();
            if ($id == 0) {
                if ($seach) {
                    $this->error('商户已存在');
                }
                $add_data['mid'] = $mid;
                $add_data['ali_mchid'] = $ali_mchid;
                $add_data['add_time'] = time();
                $add_data['ali_token'] = $ali_token;
                $res = $model->add($add_data);
                if ($res) {
                    $this->success('添加成功');
                }
            } else {
                $edit_data['mid'] = $mid;
                $edit_data['ali_mchid'] = $ali_mchid;
                $edit_data['ali_token'] = $ali_token;
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
        $model = M('merchants_ali');
        $id = I('id');
        $res = $model->where(array('id' => $id))->delete();
        if ($res) {
            $this->success();
        } else {
            $this->error();
        }
    }

}