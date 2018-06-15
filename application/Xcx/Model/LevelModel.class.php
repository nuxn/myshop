<?php
namespace Xcx\Model;
use Think\Model;

class LevelModel extends Model
{
	public $tableName = 'merchants_level';
	public function lists($mid,$type){
			//查看商家是否拥有小程序
			$info = D('Merchants')->info($mid);
			
			$where = array();
			if($info['is_miniapp']==2&&$info['mini_type']){
                $where['type'] = $info['mini_type'];
			}elseif($type){
                $where['type'] = $type;
            }
			
			   	//只返回指定版本
			$data = $this->where($where)->field('id,type,price,description,title')->select();
		
			foreach($data as &$v){
				$v['description']  =  explode('|',$v['description']);
			}
			return $data;
	}
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
