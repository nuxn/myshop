<?php
namespace Xcx\Model;

use Think\Model;

class MerchantsModel extends Model
{
		public function info($uid,$field=true){
				return $this->where(array('uid'=>$uid))->field($field)->find();
		}
}
