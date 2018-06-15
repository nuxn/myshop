<?php
namespace Xcx\Model;
use Think\Model;
class MerchantsUsersModel extends Model
{
		public function lists($uid,$page=1){
				//算出总页数
//			$count = $this->alias("u")->where(array('pid'=>$uid,'role_id'=>array('neq',3),'status'=>0))->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid")->count();
//			$totalPage = ceil($count/$page);
			$data = $this->alias("u")->where(array('pid'=>$uid,'role_id'=>array('neq',3),'status'=>0))->field('u.id,u.user_phone,u.user_name,ru.role_id')->order('ru.id')->join("LEFT JOIN __MERCHANTS_ROLE_USERS__ ru ON u.id = ru.uid")->select();
			foreach($data as &$v){
				$v['role_name']  = M('merchants_role')->where(array('id'=>$v['role_id']))->getField('role_name');
			}
			$data1['totalPage'] = $totalPage;
			$data1['lists'] = $data;
			return $data1;
		}
		
		
}
