<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class BalanceLogModel extends Model
{
	//商户订单
	//订单状态 1:待付款 2:待发货 3:已发货 4：已收货 5:交易成功 0:交易关闭（订单取消） 7:退换货申请中
    public function lists($mid,$page,$type)
    {				
				$order = $this->where(array('mid'=>$mid,'price'=>array($type?'gt':'lt',0)))->order('add_time desc')->page($page,10)->select();
				//echo $this->_sql();
				return $order;
    }
    
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
