<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class BlanceCashModel extends Model
{
	public function _add(){
				
	}
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
