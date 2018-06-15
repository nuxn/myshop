<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class BalanceModel extends Model
{
	//商户订单
	//订单状态 1:待付款 2:待发货 3:已发货 4：已收货 5:交易成功 0:交易关闭（订单取消） 7:退换货申请中
    public function lists($mid,$page)
    {				
				$order = $this->where(array('mid'=>$mid,'status'=>1))->page($page,10)->select();
				return $order;
    }
    
    
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
