<?php
namespace Merchants\Controller;
use Common\Controller\AdminbaseController;

class CateadminController extends AdminbaseController{
	protected $shopcates;
    protected $users;
    protected $roles;
    protected $merchants;
    function _initialize() {
        parent::_initialize();
        $this->users =M("merchants_users");
        $this->shopcates = M("merchants_cate");
        $this->roles =M("merchants_role_users");
        $this->merchants =M("merchants");
    }

	public function index(){
//        // 要修改的数据对象属性赋值
//        $User->time = time();
//        $User->status = 1;
//        $User->where('id=5')->save(); // 根据条件更新记录

//        Vendor('Cache.MyRedis');
//        $redis = new \MyRedis();
//        $redis->set("name1", "Redis tuto++++11+rial");
//        var_dump($redis->get("name1"));
//        exit;
//        return $ab;

        $name=I('get.name');
        if (!empty($name)) {
            $map['no_number'] = array('like', "%$name%");
        }
        $start_time=strtotime(I('start_time'));
        $end_time=strtotime(I('end_time'));
        $user_phone=trim(I('user_phone'));
        $no_number=trim(I('no_number'));
        $jianchen=trim(I('jianchen'));
        $qz_number=trim(I('qz_number'));
        $is_use=trim(I('is_use'));
        $status = I('status');
        $is_test = I("is_test");
        $wx_bank = I("wx_bank");
        $ali_bank = I("ali_bank");
        $merchant_id = I("merchant_id");
        $who = I("who");
        if($status!=""){
            if($status == "0"){
                $map['s.status'] = 0;
            }
            $map['s.status']=$status;
        }
        if($user_phone){
            $map['u.user_phone']=$user_phone;
        }
        if($is_test){
            $map['is_test']=$is_test;
        }
        if($merchant_id){
            $map['merchant_id']=$merchant_id;
        }
        if($jianchen){
            $map['jianchen']=array('LIKE',"%$jianchen%");
        }
        if($no_number){
            $map['no_number']=$no_number;
        }
        if($qz_number){
            $map['qz_number']=strtoupper($qz_number);
        }
        if($wx_bank){
            $map['wx_bank']=$wx_bank;
        }
        if($ali_bank){
            $map['ali_bank']=$ali_bank;
        }
        if($start_time&&$end_time){
            $map['create_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
        }
        if($is_use) {
            if ($is_use == 1) {
                $map['_string'] = 's.merchant_id is null or s.merchant_id=0';
            } else {
                $map['_string'] = 's.merchant_id is not null and s.merchant_id<>0';
            }
        }
        if($who){
            if($who == "YPT"){
                $map['s.qz_number'] = $who;
            } else {
                $map['s.qz_number'] = array('neq', 'YPT');
            }
        }
        $shopcate=$this->shopcates->alias('s')
                 ->join("left join __MERCHANTS__ m on s.merchant_id = m.id")
                 ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
                 ->join("left join __MERCHANTS_USERS__ u1 on s.checker_id = u1.id")
                 ->field("s.*,u.user_phone,u1.user_phone as checker_phone")
                 ->where($map);
        $count=$shopcate->count();
        $page = $this->page($count, 20);

        $shopcate->limit($page->firstRow , $page->listRows)->order("id asc");
        $this->assign("page", $page->show('Admin'));

        $selct_page=$this->shopcates->alias('s')
            ->join("left join __MERCHANTS__ m on s.merchant_id = m.id")
            ->join("left join __MERCHANTS_USERS__ u on m.uid = u.id")
            ->join("left join __MERCHANTS_USERS__ u1 on s.checker_id = u1.id")
            ->field("s.*,u.user_phone,u1.user_phone as checker_phone ")
            ->where($map)
            ->order("update_time desc,id asc")
            ->select();
        $this->assign("formget", array_merge($_GET, $_POST));
        $this->assign("shopcates",$selct_page);
        $this->assign("wx_bank",C('WX_BANK'));
        $this->assign("ali_bank",C('ALI_BANK'));
        $this->display();

	}

    //编辑
    public function edit()
    {
        $id=  I("get.id",0,'intval');
        $shopcates=$this->shopcates;
        $shopcate = $shopcates->where(array("id"=>$id))->find();
        if($shopcate['checker_id'])$shopcate['checker_phone'] = $this->users->where("id=".$shopcate['checker_id'])->getField("user_phone");
        else $shopcate['checker_id'] = 0;
        $this->assign("shopcate",$shopcate);
        $this->display();
    }

    //成功编辑
    public function edit_post()
    {
        if(IS_POST){
            if(!$_POST['merchant_id']){$this->error("未填写商户,编辑失败");}
            $id=I("id");
            $this->shopcates->create_time = time();
//                为那些验证的字段重新提交
            $this->shopcates->name =$_POST['post']['name'];
            $this->shopcates->jianchen = trim($_POST['post']['jianchen']);
            $this->shopcates->wx_name = $_POST['post']['wx_name'];
            $this->shopcates->alipay_partner = $_POST['post']['alipay_partner'];
            $this->shopcates->wx_mchid = trim($_POST['post']['wx_mchid']);
            $this->shopcates->wx_key = trim($_POST['post']['wx_key']);
            $this->shopcates->merchant_id = $_POST['merchant_id'];
            $this->shopcates->wx_bank = $_POST['wx_bank'];
            $this->shopcates->xcx_bank = $_POST['xcx_bank'];
            $this->shopcates->ali_bank = trim($_POST['ali_bank']);
            $this->shopcates->alipay_public_key = trim($_POST['alipay_public_key']);
            $this->shopcates->checker_id = $this->get_checker_id($_POST['checker_phone'],$_POST['merchant_id']);
            $this->shopcates->update_time = time();
            $this->shopcates->cate_name = trim($_POST['cate_name']);
            if($_POST['wx_bank'] ==6 &&$_POST['ali_bank'] == 6){
                $this->shopcates->is_cash = 1;
                $this->shopcates->cate_name = "D0秒到台签";
            }else{
                $this->shopcates->is_cash = 0;
                $this->shopcates->cate_name = $_POST['cate_name'];
            }
            $this->shopcates->is_ypt = 0;
            if($this->shopcates->where("id=$id")->save()){
                $mid=$_POST['merchant_id'];
                $user_name=$_POST['post']['jianchen'];
                $uid=M("merchants")->where("id=$mid")->getField("uid");
                if($uid)M("merchants_users")->where("id=$uid")->save(array("user_name"=>$user_name));
            }
//            echo $this->shopcates->_sql();
//            exit;
            $this->success("保存成功",U("index"));
        }
    }

    function get_checker_id($phone,$mid)
    {
        if(!$phone) return "";
        $ab=$this->users->where("user_phone = $phone")->find();
        if(!$ab)$this->error("手机号码不存在");
        $role = $this->roles->where("uid=".$ab['id'])->getField("role_id");
        if($role != 7)$this->error("该手机号不为收银员");
        $m_id =$this->merchants->where(array("uid"=>$ab['pid']))->getField("id");
        if($mid != $m_id)$this->error("该手机号与商户不匹配");
        return $ab['id'];
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
//        for($i=6401;$i<=7400;$i++){
//            $data['id']=$i;
//            $data['create_time']=time();
////            $data['qz_number']="YL";   云
//            $data['qz_number']="YPT";
//            $seven = "000000".$i;
//            $no_number = "YPTTQ".substr($seven,-7);
////            $no_number = "YL".substr($seven,-7);   云
//            $data['no_number']=$no_number;
//            $data['barcode_img'] = "data/upload/pay/".$no_number.".png";
//            M('merchants_cate')->add($data);
////            echo M('merchants_cate')->getLastSql();
//        }
        echo 111;
        EXIT;
        vendor("phpqrcode.phpqrcode");
//        下面为快速生成二位码的函数
        for($i=7201;$i<=7400;$i++){
            $seven = "000000".$i;
            $no_number = "YPTTQ".substr($seven,-7);
//            $no_number = "YL".substr($seven,-7);  //云来
//                    这里留着作为新服务器的上线的网址
//                    $value = "http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$result;
//                    这个作为旧服务器上线之后的网址
            $value = "https://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$i;
//            $value = "http://pay.vipylsh.com/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$i;  //云
//            $value = "http://hedui.youngport.com.cn/index.php?g=Pay&m=Barcode1&a=qrcode&type=0&id=".$i; //合兑
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 10;//生成图片大小
            //生成二维码图片
            $path_url = "data/upload/pay/".$no_number.".png";
            // 生成二位码的函数
            $av =new \QRcode();
            ob_clean(); //这个很重要
            $av->png($value, $path_url, $errorCorrectionLevel, $matrixPointSize, 2);
            $imgs="data/upload/pay/seller_barcode/bg_pay.png";
            $this->save_qrcode($imgs,$path_url,$no_number);
        }
        echo 222;
        exit;
        if($_POST){
            if($this->shopcates->create()){
                $qt_number=strtoupper($_POST['qz_number']);
                $this->shopcates->create_time = time();
//                为那些验证的字段重新提交
                $this->shopcates->name =$_POST['post']['name'];
                $this->shopcates->jianchen = $_POST['post']['jianchen'];
                $this->shopcates->wx_name = $_POST['post']['wx_name'];
                $this->shopcates->alipay_partner = $_POST['post']['alipay_partner'];
                $this->shopcates->wx_mchid = $_POST['post']['wx_mchid'];
                $this->shopcates->wx_mchid = $_POST['post']['wx_key'];
                $result = $this->shopcates->add();
                if($result){
                    $seven = "000000".$result;
                    $no_number = $qt_number."TQ".substr($seven,-7);
//                    这里留着作为新服务器的上线的网址
                    $value = "http://sy.youngport.com.cn/index.php?g=Pay&m=Barcode&a=qrcode&type=0&id=".$result;
//                    这个作为旧服务器上线之后的网址
//                    $value = "http://139.224.74.153/youngshop/index.php?g=Pay&m=Barcode&a=qrcode&id=".$result;
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

//        $font='data/upload/pay/seller_barcode/ttf/arial-bold.otf';
        $font='data/upload/pay/seller_barcode/ttf/ceshi.TTF';
        $fontsize=12;
        $dstwidth=imagesx($img_bg);
        $black = imagecolorallocate($img_bg, 30, 30, 30);
        $len = $this->utf8_strlen($number);
        $a=19;
        $b=385;
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