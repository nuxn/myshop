<?php
namespace Xcx\Controller;
use Xcx\Controller\ApibaseController;
use think\Controller;

class  PublicController extends Controller
{
		//上传图片
		public function upload_picture(){
			if(IS_POST){
					if($_FILES){
						$upload = new \Think\Upload(); // 实例化上传类
						$upload->maxSize = 3145728 ;// 设置附件上传大小 (-1) 是不限值大小
						//$upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
						//$upload->savePath = '/banner/';// 设置附件上传目录
						//$upload->rootPath = './public'; //保存根路径
						//$upload->replace = true; //存在同名文件是否是覆盖
						// 是否使用子目录保存上传文件
						//$upload->autoSub = true;
                        $upload->exts      =      array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                        $upload->rootPath  =     C('_WEB_UPLOAD_'); // 设置附件上传根目录
                        $upload->savePath  =     'ad/'; // 设置附件上传（子）目录
						$upload->saveName = uniqid();
						if($info = $upload->upload()){
								succ('./data/upload/'.$info['file']['savepath'].$info['file']['savename']);
						}else{
								err($upload->getError());
						}
						//$this->succ($info);
					}
			}else{
				$this->display();	
			}
		}
		//展示小程序详情
		public function xcx_system(){
//				$data[] = array('icon'=>'/public/icon/p_0.png','title'=>'小程序基础版','is_minapp'=>0);
				$data[] = array('icon'=>'/public/icon/p_1.png','title'=>'小程序高级版','is_minapp'=>0);
//				$data[1]['child'][] =array('icon'=>'/public/icon/c_0.png','title'=>'店铺展示');
//				$data[1]['child'][] =array('icon'=>'/public/icon/c_1.png','title'=>'产品上架');
//				$data[1]['child'][] =array('icon'=>'/public/icon/c_2.png','title'=>'商品浏览');
//				$data[1]['child'][] =array('icon'=>'/public/icon/c_3.png','title'=>'下单购买');
//				$data[1]['child'][] =array('icon'=>'/public/icon/c_4.png','title'=>'订单管理');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_0.png','title'=>'店铺展示');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_2.png','title'=>'产品上架');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_3.png','title'=>'商品浏览');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_4.png','title'=>'下单购买');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_5.png','title'=>'订单管理');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_6.png','title'=>'会员卡');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_7.png','title'=>'优惠券');
				$data[0]['child'][] =array('icon'=>'/public/icon/c_8.png','title'=>'积分');
				succ($data,'请求成功',array('banner'=>'/public/icon/banner.png'));
		}
		//地区
		public function area(){
					$pid = I('pid',0,'intval');
					succ(M('area')->where(array('pid'=>$pid))->field('id,name')->select());
		}
		//地区
		public function areas(){
					if(!$data = S('areas')){
							$data = M('area')->where('level != 4')->field('id,name,pid')->select();
							S('areas',$data);
					}
					$data = list_to_tree($data);
					succ($data);
		}
		//价格
		public function balance_prices(){
					$uid = M("token")->where(array("token" => I('token')))->getField("uid");
					if(empty($uid)){
							err('token is wrong');
					}
					$role_id = M('merchants_role_users')->where('uid='.$uid)->getField('role_id');
					if($role_id>3){
					    $uid = M('merchants_users')->where('id='.$uid)->getField('pid');
                    }
					$price = array(3000,2000,1000,500,200,100,50);
					$yue =  M('MerchantsUsers')->where(array('id'=>$uid))->getField('balance');
					succ($price,'请求成功',array('yue'=>$yue));
				
		}
}