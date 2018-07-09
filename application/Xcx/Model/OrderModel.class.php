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
    //全部订单
    public function all_lists($mid,$order_sn,$type,$page,$role=0)
    {
        $where = array('user_id'=>$mid);
        $where['type'] = $type;
        if($order_sn){$where['o.order_sn'] = array('like','%'.$order_sn.'%');}
        if ($type==1) {
            if ($role) {
                $where['staff_id'] = $role;
            }
            $field = 'order_id,order_status,order_sn,city,consignee,province,district,area_id,address,mobile,order_amount,total_amount,add_time';
            $order = $this->where($where)->page($page,15)->field($field)->select();
            foreach($order as $key=>$v){
                if ($back = M('pay_back')->where(array('remark'=>$v['order_sn']))->field('remark,paytime,price,price_back,status')->find()) {
                    if ($back['status']==5) {
                        $data = array(
                            'order_sn'=>$back['remark'],
                            'order_status'=>6,
                            'consignee'=>$v['consignee'],
                            'mobile'=>$v['mobile'],
                            'address'=>$v['address'],
                            'add_time'=>$back['paytime'],
                            'order_amount'=>$v['order_amount'],
                            'price_back'=>$back['price_back']
                        );
                        array_push($order,$data);
                    }
                }
            }
            foreach ($order as $ke => $va) {
                $d[$ke]  = $va['add_time'];
            }
            array_multisort($d, SORT_DESC, $order);
            return $order;
        }elseif($type==2){

        }

    }

	//商户订单
	//订单状态 1:待付款 2:待发货 3:已发货 4：已收货 5:交易成功 0:交易关闭（订单取消） 7:退换货申请中
    public function lists($mid,$status,$type,$page)
    {
        if ($status==6){
            $order = $this->pay_back($mid,$type,$page);
            return $order;
        }
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
        if ($status==6){
            $order = $this->pay_back($mid,$type,$page);
            return $order;
        }
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

    //订单详情 {"goods":[{"bar_code":"0111111","goods_id":"7154","goods_num":"1.0","sku":"","goods_price":"0.03","subtotal":"0.030"}],"should_refund":"0.03"}
    public function detail($order_id,$mid)
    {
        $where['order_id'] = $order_id;
        // $where['user_id'] = $mid;
        $type = $this->where($where)->getField('type');
        if ($type==1){
            $field = 'order_id,order_status,order_sn,area_id,address,mobile,order_amount,order_benefit,total_amount,dc_ps_price,add_time,coupon_price,integral_money,pay_time,discount_money,user_money,consignee,paystyle';

        }elseif($type==2){
            $field = 'order_id,order_status,order_sn,area_id,address,mobile,order_amount,order_benefit,total_amount,dc_no,dc_db,dc_db_price,dc_ch_price,dc_ps_price,add_time,coupon_price,integral_money,pay_time,discount_money,user_money,user_note,real_price,consignee,paystyle';
        }
        $data = $this->where($where)->field($field)->find();
        $data['dc_no']= (string)$data['dc_no'];
        if ($data['dc_no']) {
            $no = M('dc_no')->where(array('id'=>$data['dc_no']))->getField('no');
            $data['no'] = $no?$no:'';
        }else{
            $data['no'] ='';
        }
        $data['province'] = M('area')->where(array('id'=>$data['area_id']))->getField('name')?:'';
        //查询订单商品
        $field = 'goods_id,goods_name,goods_num,spec_key,spec_key_name,goods_img,goods_price';
        $data['goods'] = M('order_goods')->where(array('order_id'=>$order_id))->field($field)->select();
        $good_price=0;
        foreach($data['goods'] as &$v){
            if (!$v['goods_img']) {
                $v['goods_img'] = M('goods')->where(array('goods_id'=>$v['goods_id']))->getField('goods_img1');
            }
            if (!$v['spec_key_name']) {
                $units_id = M('goods')->where(array('goods_id'=>$v['goods_id']))->getField('units_id');
                $v['spec_key_name'] = (string)M('units')->where(array('id'=>$units_id))->getField('unit_name');
            }
            $picture = $v['goods_img'];
            if(preg_match("/\x20*https?\:\/\/.*/i",$v['goods_img'])){
                $v['goods_img'] = $picture;
            }else{
                $v['goods_img'] ='https://sy.youngport.com.cn'. substr($picture,1);
            }
            $good_price += $v['goods_price']*$v['goods_num'];
        }
        $data['good_price'] = $good_price;
        $field = 'mode,price,price_back,jmt_remark,order_info';
        $back = M('pay_back')->where(array('remark'=>$data['order_sn']))->field($field)->find();
        if ($back) {
            switch ($back['mode']) {
                case '99':
                    $back['mode'] = '现金退款';
                    break;
                case '98':
                    $back['mode'] = '原路退款';
                    break;
                default:
                    # code...
                    break;
            }
            if ($back['order_info']) {
                $order_info = json_decode($back['order_info'],true);
                $good_price=0;
                foreach ($order_info['goods'] as $key => &$value) {
                    if ($value['sku']) {
                        $goods = M('goods')->where(array('goods_id'=>$value['goods_id']))->field('goods_name,goods_img1')->find();
                        $value['goods_name'] = $goods['goods_name'];
                        $value['goods_img'] = $goods['goods_img1'];
                        $units_id = M('goods_sku')->where(array('sku_id'=>$value['sku']))->getField('units_id');
                        $value['spec_key'] = (string)M('units')->where(array('id'=>$units_id))->getField('unit_name');
                    }else{
                        $goods = M('goods')->where(array('goods_id'=>$value['goods_id']))->field('goods_name,units_id,goods_img1')->find();
                        $value['goods_name'] = $goods['goods_name'];
                        $value['goods_img'] = $goods['goods_img1'];
                        $value['spec_key'] = (string)M('units')->where(array('id'=>$goods['units_id']))->getField('unit_name');
                    }
                    $good_price += $value['goods_price']*$value['goods_num'];

                }
                $order_info['good_price'] = $good_price;
                $back['order_info'] = $order_info;
            }elseif($back['order_info']==null){
                $back['order_info']= array('goods'=>array(),'good_price'=>'0.00');
            }
            if ($back['jmt_remark']==null) {
                $back['jmt_remark']='';
            }
        }else{
            $object =  json_decode(json_encode(array(''=>'')),true);
        }
        $data['back'] = $back?$back:$object;
        return $data;
    }

    protected function pay_back($mid,$type,$page,$role)
    {

        $where = array('o.user_id'=>$mid);
        if ($role) {
            $where['o.staff_id'] = $role;
        }
        $where['o.type'] = $type;
        $where['p.status'] = 5;
        $field = 'p.status,o.order_id,o.order_sn,o.city,o.consignee,o.province,o.district,o.area_id,o.address,mobile,o.order_amount,o.total_amount,o.add_time';
        $order = M('pay_back')->alias('p')
            ->join("ypt_order o on o.order_sn=p.remark", 'left')
            ->where($where)
            ->page($page,5)
            ->field($field)
            ->order('p.paytime desc')
            ->select();
        foreach($order as $key=>$v){
            $order[$key]['city'] = '';
            if ($order[$key]['status']==5){
                $order[$key]['order_status']=6;
            }
            $order[$key]['district'] = '';
            $province = M('area')->where(array('id'=>$v['area_id']))->getField('name');
            $order[$key]['province'] = $province?:'';
        }
        //add_log(json_encode($order));
        return $order;
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
            $data = array('order_status'=>$status,'update_time'=>time());
    	   	if($this->where($where)->setField($data)){
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
