<?php
/**
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/27
 * Time: 15:32
 */

namespace Xcx\Model;

use Think\Model;

class QrcodeModel extends Model
{
	
	public function build($url){
			//    ($data = M('Equity')->where(array('id'=>$id))->find()) || $this->error('code 不存在');
				vendor('phpqrcode.qrlib');
	            // 纠错级别：L、M、Q、H
	            $level = 'L';
	            // 点的大小：1到10,用于手机端4就可以了
	            $size = 4;
	            // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
	            $path = "./public/equity/";
	            // 生成的文件名
	            
	            $fileName = $code.'.png';
	            \QRcode::png($url, $path.$fileName, $level, $size);
	            return '/public/equity/'.$fileName;
	}
	public function update(){
						
	}
	public function err($msg){
				$this->error = $msg;
				return false;
	}
}
