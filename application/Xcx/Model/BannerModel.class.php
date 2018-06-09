<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class BannerModel extends Model
{
	public function lists($mid){
				
		   	$data =  $this->where(array('mid'=>$mid))->order('sort')->field('img,sort')->limit(3)->select();
		   	foreach($data as $key=>$v){
		   			$data1[$v['sort']] = &$data[$key];
		   	}
		   	
		   	for($i=1;$i<=3;$i++){
		   			if(isset($data1[$i])){
		   					$data2[$i-1] = &$data1[$i];
		   			}else{
		   					$data2[$i-1] = array('img'=>'','sort'=>"$i");
		   			}
		   	}
		   	return $data2;
		   	
	}
	public function update($mid,$imgs,$sorts){
			//查询是否存在
			$imgs = explode(',',$imgs);
			$sorts = explode(',',$sorts);
	
			foreach($imgs as  $key=>$img){
					if(in_array((int)$sorts[$key],array(1,2,3))){
						
						 if($id = $this->where(array('mid'=>$mid,'sort'=>$sorts[$key]))->getField('id')){
						 			//更新
				  				$result = $this->where(array('id'=>$id))->setField('img',$img);
						 }else{
				  				$result = $this->where(array('id'=>$id))->add(array('img'=>$img,'sort'=>$sorts[$key],'mid'=>UID));
						 }
						 if($result===false){
						 		return $this->err('修改失败');
						 }
					}else{
						return $this->err('sort must is 1,2,3');
					}
				
			}
			return true;
	}
	
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
