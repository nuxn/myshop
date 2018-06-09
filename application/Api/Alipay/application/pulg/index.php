<?php
date_default_timezone_set('Asia/Shanghai'); 
class index
{
	var $db;
	public function  __construct($_db)
    {
        $this->db = $_db;
    }
	public function province($param){
		$data=$this->db->query("select * from ypt_ms_address where pid='0'");
		return json_encode($data);
	}
	public function city($param){
		$parent_id=$param['parent_id'];
		$data=$this->db->query("select * from ypt_ms_address where pid='$parent_id'");
		return json_encode($data);
	}
	public function bank($param){
		$file = fopen("../application/pulg/bank.txt", "r");
		$user=array();
		//$keywords=$parent['seach'];
		$keywords=$param['bank'];
		if(!$keywords){
			return json_encode($data);
		}
		while(! feof($file))
		{
		 $user[]= fgets($file);//fgets()函数从文件指针中读取一行
		}
		$data=array();
		$goods=array();
		
		foreach ($user as $key => $value) {
			$user[$key]=explode('|',$value);
			if($user[$key][0]!=''){
				if (strstr($user[$key][1],$keywords ) !== false ){
					$goods[]=$user[$key];
				}
			}
		}
		if($goods){
			$goods=array_slice($goods,0,20);
			foreach ($goods as $key => $value) {
				$data[$key]['name']=$goods[$key][1];
				$data[$key]['code']=$goods[$key][0];
			}
		}
		
		return json_encode($data);
	}
	public function subbranch($param){
		$data=array();
		$goods=array();
		
		if(!isset($param['bank']) && empty($param['bank'])){
			return json_encode($data);
		}
		$keywords=$param['bank'];
		$file = fopen("../application/pulg/subbranch.txt", "r");
		$user=array();
		//$keywords=$parent['seach'];

		while(! feof($file))
		{
		 $user[]= fgets($file);//fgets()函数从文件指针中读取一行
		}
		
		
		foreach ($user as $key => $value) {
			$user[$key]=explode('|',$value);
			if($user[$key][0]!=''){
				if (strstr($user[$key][2],$keywords ) !== false && $user[$key][1]==$param['number']){
					$goods[]=$user[$key];
				}
			}
		}
		if($goods){
			$goods=array_slice($goods,0,20);
			foreach ($goods as $key => $value) {
				$data[$key]['name']=$goods[$key][2];
				$data[$key]['code']=$goods[$key][0];
			}
		}
		return json_encode($data);
	}
	public function qwp(){
		$file_path = "../application/pulg/qwp.txt";
		if(file_exists($file_path)){
			$fp = fopen($file_path,"r");
			$str = fread($fp,filesize($file_path));
		}
		echo $str;
	}
	public function qwpchild($param){
		$parent=$param['parent'];
		$file_path = "../application/pulg/qwp.txt";
		if(file_exists($file_path)){
			$fp = fopen($file_path,"r");
			$str = fread($fp,filesize($file_path));
		}
		$data=json_decode($str,true);
		$data=$data[$parent];
		return json_encode($data);
	}
}