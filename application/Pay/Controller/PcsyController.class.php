<?php
namespace Pay\Controller;

use Common\Controller\AdminbaseController;

class PcsyController extends AdminbaseController
{
    protected $pcsy;
    protected $wghl;

    function _initialize()
    {
        parent::_initialize();
        $this->pcsy = M("merchants_pcsy");
        $this->wghl = M("merchants_wghl");
    }

    #插件绑定列表
    public function index()
    {
        $map = array();
        $mid = I('mid');
        if(!empty($mid)){
            $map['p.mid'] = $mid;
            $this->assign('mid',$mid);
        }
        $merchant_name = I('merchant_name');
        if(!empty($merchant_name)){
            $map['m.merchant_name'] = array('LIKE',"%$merchant_name%");
            $this->assign('merchant_name',$merchant_name);
        }
        $device_no = I('device_no');
        if(!empty($device_no)){
            $map['p.device_no'] = $device_no;
            $this->assign('device_no',$device_no);
        }
        $count = $this->pcsy->alias('p')
            ->join('left join ypt_merchants m on m.id=p.mid')
            ->where($map)
            ->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));
        $data = $this->pcsy->alias('p')
            ->field('p.*,m.merchant_name')
            ->join('left join ypt_merchants m on m.id=p.mid')
            ->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->order("id desc")
            ->select();
        $this->assign("data", $data);
        $this->display();
    }
    #插件编辑
    public function edit()
    {
        if(IS_POST){
            $id = I('id');
            if(!$id){
                $this->error('id不能为空');
            }
            $device_no = I('device_no');
            if(!$device_no){
                $this->error('设备号不能为空');
            }
            $mid = I('mid');
            if(!$mid){
                $this->error('商户ID');
            }
            if($this->pcsy->where('device_no='.$device_no)->find()){
                $this->error('该设备id已被绑定');
            }
            $res = $this->pcsy->where('id='.$id)->setField(array('device_no'=>$device_no,'mid'=>$mid));
            if($res !== false){
                $this->success('保存成功',U('index'));
            }else{
                $this->error('保存失败');
            }
        }else{
            $id = I('id');
            $data = $this->pcsy->where('id='.$id)->find();
            $this->assign('data',$data);
            $this->display();
        }
    }
    #插件绑定
    public function add()
    {
        if(IS_POST){
            $device_no = I('device_no');
            if(!$device_no){
                $this->error('设备号不能为空');
            }elseif($this->pcsy->where('device_no='.$device_no)->find()){
                $this->error('该设备号已经被绑定');
            }
            $mid = I('mid');
            if(!$mid){
                $this->error('商户ID不能为空');
            }
            $res = $this->pcsy->add(array('device_no'=>$device_no,'mid'=>$mid));
            if($res){
                $this->success('添加成功',U('index'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }
    #删除绑定
    public function delete()
    {
        $id = I('id');
        if(!$id){
            $this->error('id不能为空');
        }
        $res = $this->pcsy->where('id='.$id)->delete();
        if($res){
            $this->success('删除成功',U('index'));
        }else{
            $this->error('删除失败');
        }
    }

    public function get_merchant_name()
    {
        $id = I('id');
        $merchant_name = M('merchants')->where('id='.$id)->getField('merchant_name');
        if($merchant_name){
            $this->ajaxReturn(array('code'=>1,'merchant_name'=>$merchant_name));
        }
    }

    #微光互联绑定列表
    public function wg_index()
    {
        $map = array();
        $merchant_id = I('merchant_id');
        if(!empty($merchant_id)){
            $map['w.merchant_id'] = $merchant_id;
            $this->assign('merchant_id',$merchant_id);
        }
        $merchant_name = I('merchant_name');
        if(!empty($merchant_name)){
            $map['m.merchant_name'] = array('LIKE',"%$merchant_name%");
            $this->assign('merchant_name',$merchant_name);
        }
        $sn = I('sn');
        if(!empty($sn)){
            $map['w.sn'] = array('LIKE',"%$sn%");;
            $this->assign('sn',$sn);
        }
        $count = $this->wghl->alias('w')
            ->join('left join ypt_merchants m on m.id=w.merchant_id')
            ->where($map)
            ->count();
        $page = $this->page($count, 20);
        $this->assign("page", $page->show('Admin'));
        $data = $this->wghl->alias('w')
            ->field('w.*,m.merchant_name')
            ->join('left join ypt_merchants m on m.id=w.merchant_id')
            ->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->order("id desc")
            ->select();
        $this->assign("data", $data);
        $this->display();
    }
    #插件编辑
    public function wg_edit()
    {
        if(IS_POST){
            $id = I('id');
            if(!$id){
                $this->error('id不能为空');
            }
            $sn = I('sn');
            if(!$sn){
                $this->error('设备号不能为空');
            }elseif (strpos($sn,'ypt')===false){
                $this->error('ypt前面的3个字母要保留');
            }elseif (strlen($sn)!=9){
                $this->error('设备号有误');
            }
            $merchant_id = I('merchant_id');
            if(!$merchant_id){
                $this->error('商户ID');
            }
            if($this->wghl->where(array('sn'=>$sn,'merchant_id'=>array('neq',$merchant_id)))->find()){
                $this->error('该设备id已被绑定');
            }
            $res = $this->wghl->where('id='.$id)->setField(array('sn'=>$sn,'merchant_id'=>$merchant_id));
            if($res !== false){
                $this->success('保存成功',U('wg_index'));
            }else{
                $this->error('保存失败');
            }
        }else{
            $id = I('id');
            $data = $this->wghl->where('id='.$id)->find();
            $this->assign('data',$data);
            $this->display();
        }
    }
    #插件绑定
    public function wg_add()
    {
        if(IS_POST){
            $sn = I('sn');
            if(!$sn){
                $this->error('设备号不能为空');
            }elseif (!is_numeric($sn) || strlen($sn) != 6){
                $this->error('设备号格式错误，请填写后6位数字');
            }elseif($this->wghl->where('sn='.$sn)->find()){
                $this->error('该设备号已经被绑定');
            }
            $merchant_id = I('merchant_id');
            if(!$merchant_id){
                $this->error('商户ID不能为空');
            }
            $res = $this->wghl->add(array('sn'=>'ypt'.$sn,'merchant_id'=>$merchant_id));
            if($res){
                $this->success('添加成功',U('wg_index'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }
    #删除绑定
    public function wg_delete()
    {
        $id = I('id');
        if(!$id){
            $this->error('id不能为空');
        }
        $res = $this->wghl->where('id='.$id)->delete();
        if($res){
            $this->success('删除成功',U('wg_index'));
        }else{
            $this->error('删除失败');
        }
    }
}