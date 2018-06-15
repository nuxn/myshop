<?php
namespace Xcx\Controller;

use Xcx\Controller\ApibaseController;
use Think\Controller;

class  OrderController extends  ApibaseController
{
	
    public function lists()
    {
		($type = I('type',0,'intval')) || err('type is empty');
		($page = I('page',0,'intval')) || err('page is empty');
		($status = I('status',0,'intval'));
		$order = D('order');
        if (ROLE_ID==7) {
            $data = $order->lists_y(UID,$status,$type,$page,ROLEID);
            succ($data);
        }else{ 
            $data = $order->lists(UID,$status,$type,$page);
            succ($data);
        }
			
    }
    
    public function info(){
    		($order_id = I('order_id',0,'intval')) || err('order_id is empty!');
    		$order = D('order');
    		if($data = $order->info($order_id,UID)){
    				succ($data);
    		}else{
    				err($order->getError());
    		}
    		
    }
    //发货
    public function fahuo(){
    		($order_id = I('order_id',0,'intval'))  || err('order_id is empty!');
    		$order = D('order');
    		if($order->change_status($order_id,UID,3)){
    				succ('','发货成功');
    		}else{
    				err($order->getError());
    		}
    }
    public function pj(){
    		($order_id = I('order_id',0,'intval'))  || err('order_id is empty!');
    		
    		$pj = M('pj')->where(array('order_id'=>$order_id))->field('goods_id,content,star,add_time')->select();
    		foreach($pj as &$v){
				 $v = array_merge($v,M('goods')->where(array('goods_id'=>$v['goods_id']))->field('goods_name,goods_img1')->find());
				 $v['properties'] = '';
                 if ($v['star']==null) {
                    $v['star']=5;
                 }
    		}
    		succ((array)$pj);
    }
    
}