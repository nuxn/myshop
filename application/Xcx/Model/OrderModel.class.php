<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class OrderModel extends Model
{
	//商户订单
	//订单状态 1:待付款 2:待发货 3:已发货 4：已收货 5:交易成功 0:交易关闭（订单取消） 7:退换货申请中
    public function lists($mid,$status,$type,$page)
    {		
    			$where = array('user_id'=>$mid);
    			switch($status){
    				 case 4:
    				 $where['order_status'] = array('in','4,5');
    				 break;
    				 default:
    				 $where['order_status'] = $status;
    				 break;
    			}
    			$where['type'] = $type;
    			$field = 'order_id,order_status,order_sn,city,consignee,province,district,area_id,address,mobile,order_amount,total_amount,add_time';
				$order = $this->where($where)->page($page,5)->field($field)->order('add_time desc')->select();
				foreach($order as $key=>$v){
						$order[$key]['city'] = '';
					
						$order[$key]['district'] = '';
						$province = M('area')->where(array('id'=>$v['area_id']))->getField('name');
						$order[$key]['province'] = $province?:'';
				}
				//add_log(json_encode($order));
				return $order;
    }

    //员工订单
    //订单状态 1:待付款 2:待发货 3:已发货 4：已收货 5:交易成功 0:交易关闭（订单取消） 7:退换货申请中
    public function lists_y($mid,$status,$type,$page,$role)
    {       
                $where = array('user_id'=>$mid);
                if ($role) {
                    $where['staff_id'] = $role;
                }
                switch($status){
                     case 4:
                     $where['order_status'] = array('in','4,5');
                     break;
                     default:
                     $where['order_status'] = $status;
                     break;
                }
                $where['type'] = $type;
                $field = 'order_id,order_status,order_sn,city,consignee,province,district,area_id,address,mobile,order_amount,total_amount,add_time';
                $order = $this->where($where)->page($page,5)->field($field)->order('add_time desc')->select();
                foreach($order as $key=>$v){
                        $order[$key]['city'] = '';
                    
                        $order[$key]['district'] = '';
                        $province = M('area')->where(array('id'=>$v['area_id']))->getField('name');
                        $order[$key]['province'] = $province?:'';
                }
                //add_log(json_encode($order));
                return $order;
    }
    //订单信息
    public function info($order_id,$mid){
    			$where['order_id'] = $order_id;
    			$where['user_id'] = $mid;
    			$field = 'order_id,order_status,order_sn,city,area_id,consignee,province,district,address,mobile,order_amount,total_amount,dc_db,dc_db_price,dc_ch_price,dc_ps_price,add_time,coupon_price,integral_money,paystyle,pay_time,user_note,discount_money,user_money';
    			if($data = $this->where($where)->field($field)->find()){
    					$data['city'] = '';
						
						$data['district'] = '';
						$data['province'] = M('area')->where(array('id'=>$data['area_id']))->getField('name')?:'';
    				//查询订单商品
    				$field = 'goods_name,goods_num,spec_key_name as spec_key,goods_img,goods_price';
    				$data['goods'] = M('order_goods')->where(array('order_id'=>$order_id))->field($field)->select();
                    foreach($data['goods'] as &$v){
                        $picture = $v['goods_img'];
                        if(preg_match("/\x20*https?\:\/\/.*/i",$v['goods_img'])){
                            $v['goods_img'] = substr($picture,27);
                        }else{
                            $v['goods_img'] = $picture;
                        }
                    }
    				return $data;
    			}else{
    				
    				return $this->err('没有查到订单');
    			}		
    }
    //确认发货
    public function change_status($order_id,$mid,$status){
    	   	switch($status){
    	   			case 3:
	    	   		$where['order_id'] = $order_id;
	    			$where['user_id'] = $mid;
    	   			$order_status = $this->where($where)->getField('order_status');
    	   			if($order_status==3){
    	   				return $this->err('该订单已确认收货');
    	   			}
    	   			//开始推送消息
    	   			curl_post('https://mp.youngport.com.cn/index/Notify/fahuo',array('order_id'=>$order_id,'sign'=>'tiancai'));
    	   			break;
    	   			default:
    	   			$this->err('status is wrong');
    	   			break;
    	   			
    	   	}
    	   	if($this->where($where)->setField('order_status',$status)){
    	   			return true;
    	   	}else{
    	   			return $this->err('修改状态失败');
    	   	}
    }
    
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
