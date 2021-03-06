<?php
namespace Pay\Controller;
use Common\Controller\AdminbaseController;

class CateadminController extends AdminbaseController{
	protected $shopcates;

    function _initialize() {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
    }

	public function index(){
        $name=I('get.name');
        if (!empty($name)) {
            $map['no_number'] = array('like', "%$name%");
        }
        if($_POST){
            $start_time=strtotime(I('start_time'));
            $end_time=strtotime(I('end_time'));
            $merchant_id=I('merchant_id');
            $no_number=I('no_number');
            if($merchant_id){
                $map['merchant_id']=$merchant_id;
            }
            if($no_number){
                $map['no_number']=$no_number;
            }
            if($start_time&&$end_time){
                $map['create_time'] = array(array('EGT',$start_time),array('ELT',$end_time)) ;
            }
        }

        $count=$this->shopcates->where($map)->count();
        $page = $this->page($count, 20);
        $this->shopcates->limit($page->firstRow , $page->listRows)->order("id asc");
        $this->assign("page", $page->show('Admin'));
        $shopcates=$this->shopcates->where($map)->select();
        $this->assign("shopcates",$shopcates);
        $this->display();

	}

    //编辑
    public function edit()
    {
        $id=  I("get.id",0,'intval');
        $shopcates=$this->shopcates;
        $shopcate = $shopcates->where(array("id"=>$id))->select();
        $this->assign("shopcate",$shopcate);
        $this->display();
    }

    //成功编辑
    public function edit_post()
    {
        if(IS_POST){
            $id=I("id");
            $this->shopcates->create_time = time();
//                为那些验证的字段重新提交
            $this->shopcates->name =$_POST['post']['name'];
            $this->shopcates->wx_name = $_POST['post']['wx_name'];
            $this->shopcates->alipay_partner = $_POST['post']['alipay_partner'];
            $this->shopcates->wx_mchid = $_POST['post']['wx_mchid'];
            $this->shopcates->alipay_private_key = I("alipay_private_key");
            $this->shopcates->alipay_public_key = I("alipay_public_key");
            $this->shopcates->merchant_id = I("merchant_id");
            $this->shopcates->wx_appid = I("wx_appid");
            $this->shopcates->wx_key = I("wx_key");
            $this->shopcates->wx_appsecret = I("wx_appsecret");
            $this->shopcates->where("id=$id")->save();
            $this->success("保存成功");
        }
    }

//    删除选中项
    public function delete()
    {
//        $this->success("删除成功");
        if($_POST){
            $ids=I("ids");
            foreach ($ids as $k=>$v){
                $this->shopcates->where("id=$v")->delete();
            }
            $this->success("恭喜你删除成功");
        }
        if($_GET){
            $id=I("id");
            $this->shopcates->where("id=$id")->delete();
            $this->success("恭喜你删除成功");
        }
    }
//    改变上线状态
    public function change_status(){
        $id=I('post.id');
        $cate=$this->shopcates->find($id);
        $status=$cate['status']== 0 ? 1 : 0;
        echo $status;
        $this->shopcates->where("id=$id")->setField('status', $status);
    }

//新增验证码
    public function add()
    {
        vendor("phpqrcode.phpqrcode");
//        for($i=1001;$i<=1100;$i++){
//            $seven = "000000".$i;
//                $no_number = "YPTTQ".substr($seven,-7);
////                    这里留着作为新服务器的上线的网址
////                    $value = "http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$result;
////                    这个作为旧服务器上线之后的网址
//            $value = "http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$i;
//            $errorCorrectionLevel = 'L';//容错级别
//            $matrixPointSize = 10;//生成图片大小
//            //生成二维码图片
//            $path_url = "data/upload/pay/".$no_number.".png";
//            // 生成二位码的函数
//            $av =new \QRcode();
//            ob_clean(); //这个很重要
//            $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
//            $imgs="data/upload/pay/seller_barcode/bg_pay.png";
//            $this->save_qrcode($imgs,$path_url,$no_number);
//            echo 213;
//        }
//        exit;
        if($_POST){
            if($this->shopcates->create()){
                $this->shopcates->create_time = time();
//                为那些验证的字段重新提交
                $this->shopcates->name =$_POST['post']['name'];
                $this->shopcates->wx_name = $_POST['post']['wx_name'];
                $this->shopcates->alipay_partner = $_POST['post']['alipay_partner'];
                $this->shopcates->wx_mchid = $_POST['post']['wx_mchid'];
                $result = $this->shopcates->add();
                if($result){
                    $seven = "000000".$result;
                    $no_number = "YPTTQ".substr($seven,-7);
//                    这里留着作为新服务器的上线的网址
//                    $value = "http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$result;
//                    这个作为旧服务器上线之后的网址
                    $value = "http://139.224.74.153/youngshop/index.php?g=Pay&m=Barcode&a=qrcode&id=".$result;
                    $errorCorrectionLevel = 'L';//容错级别
                    $matrixPointSize = 10;//生成图片大小
                    //生成二维码图片
                    $path_url = "data/upload/pay/".$no_number.".png";
                    // 生成二位码的函数
                    $av =new \QRcode();
                    ob_clean(); //这个很重要
                    $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
                    $this->shopcates->where('id='.$result)->setField(array('barcode_img'=>$path_url,'no_number'=>$no_number));
                    $this->success('新增成功！');
                }else{
                    $this->error('新增失败！');
                }
            }
        }
        $this->display();
    }
//测试生成只有下面带有标签的图片
    function save_qrcode($imges, $qrcode,$number='')
    {

        //加载背景图
        $img_bg_info = getimagesize($imges);
        $img_bg_type = image_type_to_extension($img_bg_info[2], false);
        $fun_bg = "imagecreatefrom{$img_bg_type}";
        $img_bg = $fun_bg($imges);


        //加载二维码
        $img_qrcode_src = $qrcode;
        $img_qrcode_info = getimagesize($img_qrcode_src);
        list($width,$height) = $img_qrcode_info;
        $img_qrcode_type = image_type_to_extension($img_qrcode_info[2], false);
        $fun_qrcode = "imagecreatefrom{$img_qrcode_type}";
        $img_qrcode = $fun_qrcode($img_qrcode_src);

        $font='data/upload/pay/seller_barcode/ttf/arial-bold.otf';
        $fontsize=25;
        $dstwidth=imagesx($img_bg);
        $black = imagecolorallocate($img_bg, 30, 30, 30);
        $len = $this->utf8_strlen($number);
        $a=22;
        $b=398;
        for($i=0;$i<=$len;){
            $box = imagettfbbox($fontsize,0,$font,mb_substr($number,$i,$a,'utf8'));
            $box_width = max(abs($box[2] - $box[0]),abs($box[4] - $box[6]));
            $x=ceil(($dstwidth-$box_width)/2);
            $tempstr=mb_substr($number,$i,$a,'utf8');
            imagettftext($img_bg,$fontsize, 0, $x,$b, $black,$font,$tempstr);
            if($this->utf8_strlen($tempstr)==$a) {
                $i += $a;
                $b += 50;
            }else{
                break;
            }
        }
        imagecopyresized($img_bg, $img_qrcode, 0, 0, 0, 0, 370, 370,$width,$height);
        $save_img = "data/upload/pay/cate/QR_".$number.".png";
        imagepng($img_bg,$save_img);
        imagedestroy($img_bg);
        imagedestroy($img_qrcode);

    }

    // 拼接图片
    public function detail()
    {
        $id = intval($_REQUEST['id']);
        $imges = "data/upload/pay/seller_barcode/bg_pay.jpg";
        $res = M('merchants_cate')->where('id='.$id)->find();
        $this->save_images($imges,$res['barcode_img'],$res['no_number']);
    }

    function save_images($imges, $qrcode,$number='')
    {

        //加载背景图
        $img_bg_info = getimagesize($imges);
        $img_bg_type = image_type_to_extension($img_bg_info[2], false);
        $fun_bg = "imagecreatefrom{$img_bg_type}";
        $img_bg = $fun_bg($imges);


        //加载二维码
        $img_qrcode_src = $qrcode;
        $img_qrcode_info = getimagesize($img_qrcode_src);
        list($width,$height) = $img_qrcode_info;
        $img_qrcode_type = image_type_to_extension($img_qrcode_info[2], false);
        $fun_qrcode = "imagecreatefrom{$img_qrcode_type}";
        $img_qrcode = $fun_qrcode($img_qrcode_src);

        $good_name=$number;
        $font='data/upload/pay/seller_barcode/ttf/simhei.ttf';
        $fontsize=50;
        $dstwidth=imagesx($img_bg);
        $black = imagecolorallocate($img_bg, 30, 30, 30);
        $len = $this->utf8_strlen($good_name);
        $a=25;
        $b=1300;
        for($i=0;$i<=$len;){
            $box = imagettfbbox($fontsize,0,$font,mb_substr($good_name,$i,$a,'utf8'));
            $box_width = max(abs($box[2] - $box[0]),abs($box[4] - $box[6]));
            $x=ceil(($dstwidth-$box_width)/2);
            $tempstr=mb_substr($good_name,$i,$a,'utf8');
            imagettftext($img_bg,$fontsize, 0, $x,$b, $black,$font,$tempstr);
            if($this->utf8_strlen($tempstr)==$a) {
                $i += $a;
                $b += 50;
            }else{
                break;
            }
        }
        imagecopyresized($img_bg, $img_qrcode, 278, 504, 0, 0, 740, 740,$width,$height);
        header("content-type:image/jpeg");
        imagejpeg($img_bg);
        imagedestroy($img_bg);
        imagedestroy($img_qrcode);
    }

    public function utf8_strlen($string = null)
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }


}