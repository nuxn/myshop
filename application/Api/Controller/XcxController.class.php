<?php
/**
 * 我的
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/9
 * Time: 17:26 
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Upload;
use Common\Lib\Subtable;


/**
 * Class XcxController 
 * @package Api\Controller       
 */
class XcxController extends ApibaseController
{
    public $merchants;
    public $users;
    public $miniapp;
    public $merchants_level;
    public $roles;
    public $order;
    public $order_goods;
    public $dc_no;
	public $dc_set;
	public $dc_eval;
	public $pay;
    public $host;
    public function __construct()
    {
        parent::__construct();
        $this->merchants = M("merchants");
        $this->users = M("merchants_users");
        $this->miniapp = M("miniapp");
        $this->merchants_level = M("merchants_level");
        $this->roles = M("merchants_role_users");
        $this->order = M("order");
        $this->order_goods = M("order_goods");
        $this->dc_no = M("dc_no");
		$this->dc_set = M("merchants_dc_set");
		$this->dc_eval = M("dc_eval");
		$this->pay =M(Subtable::getSubTableName('pay'));
        $this->host = 'http://'.$_SERVER['HTTP_HOST'];

        $this->u_id = $this->get_mer_info($this->userId);
    }

    /**
     * 获取商户信息
     * @Param uid
     * return 商家(uid)，收银员(pid)
     */
    public function get_mer_info($uid)
    {
        $role_id = $this->roles->where("uid=$uid")->getField('role_id');
        if($role_id == 3){
            return $uid;
        }else{
            $pid = $this->users->where(array('id'=>$uid))->getField('pid');
            return $pid;
        }
    }
	
    /**
     * 判断是否开通小程序
     * @param uid
     */
    public function oc()
    {
        $uid = $this->u_id;
        $data = $this->merchants->alias('m')
            ->join('join __MINIAPP__ mi on mi.mid=m.uid')
            ->where(array('m.uid'=>$uid,'mi.start_time'=>array('elt',time()),'mi.end_time'=>array('egt',time())))
            ->field('mi.type,m.mini_type')
            ->find();
        //if(!$data){$this->ajaxReturn(array('code'=>'error','msg'=>'你还未开通小程序'));}
        if(!$data){$data['type']='0';$data['mini_type']='0';}
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array('type'=>$data['type'],'mini_type'=>$data['mini_type'])));
    }

    /**
     * 未开通小程序，点击立即开通选择需要创建的店铺类型
     */
    public function xcxType()
    {
        $data = $this->merchants_level->field('id,title,face_img,describe')->where('is_show=1')->select();
        if(!$data){$this->ajaxReturn(array('code'=>'error','msg'=>'暂无小程序可以开通'));}
        foreach($data as $k => $v){
            if($v['face_img']){
                $data[$k]['face_img'] = $this->host.$v['face_img'];
            }
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    }

    /**
     * 查看小程序详情图
     * @Param id 小程序id
     */
    public function xcxDetail()
    {
        $id = I('id');
        $img = $this->merchants_level->where(array('id'=>$id))->field('img1,img2,img3')->find();
        $data = array();
        foreach($img as $k => $v){
            if($v){$data[] = $this->host.$v;}
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    }

    /**
     * 开通小程序
     * @Param id 小程序id
     * @Param uid 商家uid
     */
    public function xcxOpen()
    {
        $id = I('id');
        $uid = $this->userId;
        //判断是否是商家
        $role_id=$this->roles->where(array("uid"=>$uid))->getField("role_id");
        if($role_id!='3'){$this->ajaxReturn(array('code'=>'error','msg'=>'您不是商家，不能开通小程序'));}
        $this->merchants->where(array('uid'=>$uid))->setField(array('is_miniapp'=>'2','mini_type'=>$id,'is_open'=>'1'));
        $add = array('mid'=>$uid,'type'=>$id,'level'=>1,'add_time'=>time(),'price'=>0,'status'=>1,'start_time'=>time(),'end_time'=>'4000000000','pay_time'=>time(),'remark'=>'app免费开通','order_sn'=>date('YmdHis').mt_rand(1000000,9999999));
        $this->miniapp->add($add);
		if($id == '2'){
            $mid = $this->merchants->where(array('uid'=>$uid))->getField('id');
            $this->dc_set->add(array('mid'=>$mid));
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'开通成功'));
    }
	
	/**
     * 点餐小程序订单
     * @Param order_status 订单状态
     * @Param uid 商家uid
     */
    public function dc_order()
    {
        $uid = $this->u_id;
        //$mid = $this->_get_mch_id($uid);
        $map['o.type'] = 2;//2点餐
        $map['o.user_id'] = $uid;
        $map['o.order_status'] = I('order_status');
        //$map['o.mid'] = $mid;
        if(I('no_id')){$map['dn.id'] = I('no_id');}
        if(I('start_price')){$map['o.real_price'] = array('egt',I('start_price'));}
        if(I('end_price')){$map['o.real_price'] = array('elt',I('end_price'));}
        if(I('start_num')){$map['o.order_goods_num'] = array('egt',I('start_num'));}
        if(I('end_num')){$map['o.order_goods_num'] = array('egt',I('end_num'));}
        if(I('order_sn')){$map['o.order_sn'] = array('like','%'.I('order_sn').'%');}
        $map['dc_db'] = I('dc_db')?I('dc_db'):array('in',array('1','2','3'));
        //$map['dn.mid'] = $mid;
		$per_page = 10;
        $page = I("page")?I("page"):0;
        if(I('order_status') == 5){
            $order_desc = 'o.update_time DESC';
        }else{
            $order_desc = 'o.order_id DESC';
        }
        $data = $this->order->alias('o')
            ->join('left join __DC_NO__ dn on o.dc_no=dn.id')
            ->where($map)
            ->field('o.order_sn,o.order_id,o.dc_db,o.real_price,o.order_goods_num,dn.id as no_id,dn.no')
			->limit($page * $per_page, $per_page)
            ->order($order_desc)
            ->select();
        if($data){
            foreach ($data as $k => $v) {
                $data[$k]['goods_list'] = $this->order_goods->where(array('order_id'=>$v['order_id']))->field('goods_name,goods_num,goods_price,spec_key_name')->select();
                foreach ($data[$k]['goods_list'] as $key => $val){
                    if($data[$k]['goods_list'][$key]['spec_key_name'] != ''){
                        $data[$k]['goods_list'][$key]['goods_name'] .= '('. $data[$k]['goods_list'][$key]['spec_key_name'] .')';
                    }
                }
            }
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        }else{
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array()));
        }
    }
	
	/**
     * 获取商家对应的餐桌号
     * @Param uid 商家uid
     */
    public function get_no()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $data = $this->dc_no->field('id as no_id,no,qr_img')->where(array('mid'=>$mid))->select();
        if($data){
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'未添加餐桌'));
        }
    }
	
	/**
     * 点餐小程序订单详情
     * @Param order_id 订单id
     * @Param uid 商家uid
     */
    public function dc_order_detail()
    {
        $uid = $this->u_id;
        //$mid = $this->_get_mch_id($uid);
        $map['o.order_id'] = I('order_id');
        $map['o.user_id'] = $uid;
        //$map['o.mid'] = $mid;
        $data = $this->order->alias('o')
            ->join('left join __DC_NO__ dn on o.dc_no=dn.id')
            ->where($map)
            ->field('o.order_id,o.order_sn,o.address,o.consignee,o.mobile,o.add_time,o.pay_time,o.order_status,o.coupon_price,o.integral_money,o.total_amount,o.user_note,o.dc_db,o.dc_db_price,o.dc_ch_price,dc_ps_price,o.real_price,o.order_goods_num,dn.id as no_id,dn.no,o.discount,o.user_money')
            ->find();
        if($data){
            if(in_array($data['discount'],array('100','0'))){
                $data['discount_money'] = '0';
                $data['discount'] = '1';
            }else{
                $data['discount_money'] = strval($data['total_amount'] - ($data['total_amount'] * $data['discount'])/100);
                $data['discount'] = strval($data['discount'] / 10);
            }
            $data['goods_list'] = $this->order_goods->where(array('order_id'=>I('order_id')))->field('goods_name,goods_num,goods_price,spec_key_name')->select();
            foreach ($data['goods_list'] as $key => $val){
                if($data['goods_list'][$key]['spec_key_name'] != ''){
                    $data['goods_list'][$key]['goods_name'] .= '('. $data['goods_list'][$key]['spec_key_name'] .')';
                }
                unset($data['goods_list'][$key]['spec_key_name']);
            }
            if(is_null($data['no'])) $data['no'] = '';
            if(is_null($data['no_id'])) $data['no_id'] = '';
            if(is_null($data['order_goods_num'])) $data['order_goods_num'] = '0';
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'查不到该订单信息'));
        }
    }
	
	/**
     * 获取商家设置信息
     * @Param uid 商家uid
     */
    public function set_info()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $data = $this->dc_set->where(array('mid'=>$mid))->find();
		$data['img'] = explode(',',$data['img']);
		$data['start_time'] = $data['start_time']?substr($data['start_time'],0,-3):'00:00';
		$data['end_time'] = $data['end_time']?substr($data['end_time'],0,-3):'00:00';
        if($data){
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        }else{
            $id = $this->dc_set->add(array('mid'=>$mid));
            $data = $this->dc_set->where(array('id'=>$id))->find();
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
        }
    }
	
	/**
     * 保存商家设置信息
     * @Param uid 商家uid
     */
    public function set_info_post()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $data = I('');
        $this->merchants->where(array('id'=>$mid))->setField('shipping_type',1);
        $data['ps_price'] = htmlspecialchars_decode($data['ps_price']);
        $res = $this->dc_set->where(array('mid'=>$mid))->setField($data);
        if($res!==false){
            $this->ajaxReturn(array('code'=>'success','msg'=>'保存成功'));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'保存失败'));
        }
    }

    /**
     * 商品图片上传编辑 
     */
    public function upload_pic()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 0;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'merchants/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
        }
        if($info['img']){
            $img = $pic_root_path . $info['img']['savepath'] . $info['img']['savename'];
        }
        if($img){
            $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'上传失败'));
        }
    }
	
	/**
     * 获取用户评价
     * @Param uid 商家uid 
     */
    public function get_eval()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $per_page = 10;
        $page = I("page")?I("page"):0;
        $res = $this->dc_eval->where(array('mid'=>$mid))->limit($page * $per_page, $per_page)->order('add_time DESC')->field('id',true)->select();
        foreach($res as $k => $v){
            if($v['memid']){
                $mem = M('screen_mem')->where(array('id'=>$v['memid']))->field('memimg,nickname')->find();
                $res[$k]['memimg'] = $mem['memimg']?$mem['memimg']:'';
                $res[$k]['nickname'] = $mem['nickname']?$mem['nickname']:'';
            }else{
                $res[$k]['memimg'] = '';
                $res[$k]['nickname'] = '';
            }
            if($v['img']){
                $res[$k]['img'] = explode(',',$v['img']);
                foreach($res[$k]['img'] as $key => $val){
                    if(!strstr($res[$k]['img'][$key],'http')){
                        $res[$k]['img'][$key] = $this->host.$res[$k]['img'][$key];
                    }
                }
            }else{
                $res[$k]['img'] = array();
            }
        }
        if($res){
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$res));
        }else{
            $this->ajaxReturn(array('code'=>'success','msg'=>'empty','data'=>array()));
        }
    }
	
	/**
     * 获取商家ID
     * @Param uid 商家uid
     */
	public function _get_mch_id($uid)
    {
        $id = $this->merchants->where(array('uid'=>$uid))->getField('id');
        return $id;
    }

    /**
     * 呼叫服务详情
     * @Param  uid 商家uid
     */
    public function serve_detail()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $res = $this->dc_set->where(array('mid'=>$mid))->field('is_serve,serve_mode')->find();
        $res['serve_mode'] = explode(',',$res['serve_mode']);
        if ($res) {
            $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$res));  
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'未查询到该商户设置')); 
        } 
    }

    /**
     * 开启关闭呼叫服务
     * @Param  uid 商家uid
     */
    public function start_serve()
    {
        $is_serve = I('is_serve');
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        // $data = $this->dc_set->where(array('mid'=>$mid))->find();
        if($is_serve == 2){
            $res = $this->dc_set->where(array('mid'=>$mid))->save(array('is_serve'=>2));
            // echo $this->dc_set->_sql();die;
            // dump($res);die;
            if ($res) {
                $this->ajaxReturn(array('code'=>'success','msg'=>'关闭成功'));
            }else{
                $this->ajaxReturn(array('code'=>'error','msg'=>'关闭失败,服务已关闭'));
            }
        }else if($is_serve == 1){
            $m = M('merchants_dc_set');
            $res =$m->where(array('mid'=>$mid))->save(array('is_serve'=>1));
            // echo $m->_sql();die;
            // dump($res);die;
            if ($res) {
                $result = $this->dc_set->where(array('mid'=>$mid))->getField('serve_mode');
                $this->ajaxReturn(array('code'=>'success','msg'=>'开启成功','data'=>$result));
            }else{
                $this->ajaxReturn(array('code'=>'error','msg'=>'开启失败,服务已开启'));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'参数错误')); 
        }
    }

    /**
     * 保存呼叫服务
     * @Param  uid 商家uid
     */
    public function save_serve()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $data = I('');
        $res = $this->dc_set->where(array('mid'=>$mid))->save($data);
        if($res){
            $this->ajaxReturn(array('code'=>'success','msg'=>'保存成功'));
        }else{
            $this->ajaxReturn(array('code'=>'success','msg'=>'保存失败'));
        }  

    }
	
	/**
     * 添加餐桌
     * @Param  uid 商家uid
     */
    public function add_no()
    {
        $uid = $this->u_id;
        $mid = $this->_get_mch_id($uid);
        $data['mid'] = $mid;
        if(trim(I('no'))){
            $data['no'] = trim(I('no'));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请输入餐桌编号'));
        }
        M()->startTrans();
        $no_id = $this->dc_no->add($data);
        $mini_type = M('merchants')->where(array('uid'=>$uid))->getField('mini_type');
        if ($mini_type==2) {
            // $agent_id = M('merchants_users')->where(array('id'=>$uid))->getField('agent_id');
            $ids = M()->query('select getagentchild(182) as ids');
            $ids = explode(',',$ids[0]["ids"]);
            if(in_array($uid,$ids)){
                $filePath = $this->add_no_store($no_id,$uid);
            }else{
                $filePath = $this->add_no_png($no_id,$uid);
            }
        }else{
            $filePath = $this->add_no_png($no_id,$uid);
        }
        $res = $this->dc_no->where("id=$no_id")->setField('qr_img',$filePath);
        if($no_id && $res){
            M()->commit();
            $this->ajaxReturn(array('code'=>'success','msg'=>'添加成功'));
        }else{
            M()->rollback();
            $this->ajaxReturn(array('code'=>'error','msg'=>'添加失败'));
        }
    }

    //上传二维码照片到服务器
	private function add_no_png($no,$uid)
    {
        $path='pages/store/index?no_id='.$no.'&store_id='.$uid;
        // $access_token = $this->get_token();
        $access_token = $this->get_token1($uid);
        $width=430;
        $post_data='{"path":"'.$path.'","width":'.$width.'}';
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result=$this->api_notice_increment($url,$post_data);
        $filepath = $this->upload_qr_img($result);
        if(!$filepath){
            return false;
        }else{
            return $filepath;
        }
    }

    //上传二维码照片到服务器
    private function add_no_store($no,$uid)
    {
        $path='dc/store/index?no_id='.$no.'&store_id='.$uid;
        $access_token = $this->get_token();
        // $access_token = $this->get_token1($uid);
        $width=430;
        $post_data='{"path":"'.$path.'","width":'.$width.'}';
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result=$this->api_notice_increment($url,$post_data);
        $filepath = $this->upload_qr_img($result);
        if(!$filepath){
            return false;
        }else{
            return $filepath;
        }
    }
    //获取晋城尚购小程序access_token
	private function get_token(){
        // $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $param['appid'] = 'wxdbca93f421a8ec1c';
        $param['secret'] = 'a60b2f4271d7b43a422db73871220df1';
        // $param['grant_type'] = 'client_credential';
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$param['appid'].'&secret='.$param['secret'];
        $data = request_post($url,array());
        $data = json_decode($data,true);
        $token = $data['access_token'];
        return $token;
    }

    //获取小程序token
    private function get_token1($uid){
        $merchants = M('merchants')->where(array('uid'=>$uid))->find();
        if ($merchants['mini_type'] == 2) {
            $merchants_users = M('merchants_users')->where(array('id'=>$uid))->find();
            $agent_id =  $merchants_users['agent_id'];
            $appid = M('dc_appid')->where(array('uid'=>$agent_id))->find();
            if(empty($appid)){
                $appid = M('dc_appid')->where(array('uid'=>756))->find();
                $agent_id = 756;
            }
            $token1 = M('config')->where(array('name'=>'xcx_access_token_dc','type'=>$agent_id))->find();
            $time = time();
            if(empty($token1) || empty($token1['value']) || $token1['add_time']+7200<$time){
                //获取token
                $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid['appid'].'&secret='.$appid['secret'];
                // dump($url);
                // add_log($url);
                $token = request_post($url,array());
                $token = json_decode($token,true);
                $token = $token['access_token'];
                $token1?M('config')->where(array('name'=>'xcx_access_token_dc'))->where(array('type'=>$agent_id))->save(array('value'=>$token,'add_time'=>$time)):M('config')->add(array('name'=>'xcx_access_token_dc','value'=>$token,'add_time'=>$time,'type'=>$agent_id));
            }else{
                $token = $token1['value']; 
            }
            return $token;
        }else{
            $appid = M('dc_appid')->where(array('uid'=>$uid))->find();
            if(empty($appid)){
                $appid = M('dc_appid')->where(array('uid'=>756))->find();
                $uid = 756;
            }
            $token1 = M('config')->where(array('name'=>'xcx_access_token_dc','type'=>$uid))->find();
            $time = time();
            if(empty($token1) || empty($token1['value']) || $token1['add_time']+7200<$time){
                //获取token
                $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid['appid'].'&secret='.$appid['secret'];
                // dump($url);
                // add_log($url);
                $token = request_post($url,array());
                $token = json_decode($token,true);
                $token = $token['access_token'];
                $token1?M('config')->where(array('name'=>'xcx_access_token_dc'))->where(array('type'=>$uid))->save(array('value'=>$token,'add_time'=>$time)):M('config')->add(array('name'=>'xcx_access_token_dc','value'=>$token,'add_time'=>$time,'type'=>$uid));
            }else{
                $token = $token1['value']; 
            }
            return $token;
        }
        
    }
    //图片二进制数据转图片
	private function upload_qr_img($data){
        //生成图片  
        $imgDir = 'data/upload/no/';
        $filename = uniqid().".png";///要生成的图片名字  

        $xmlstr =  $data;
        if(empty($xmlstr)) {
            $xmlstr = file_get_contents('php://input');
        }

        $jpg = $xmlstr;//二进制原始数据
        if(empty($jpg))
        {
            echo 'nostream';
            exit();
        }

        $file = fopen("./".$imgDir.$filename,"w");//打开文件准备写入  
        fwrite($file,$jpg);//写入  
        fclose($file);//关闭  

        $filePath = './'.$imgDir.$filename;

        //图片是否存在  
        if(!file_exists($filePath))
        {
            return false;
        }else{
			return $filePath;
		}
    }
    //获取二维码二进制数据
    private function api_notice_increment($url, $data){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        }
    }

    /**
     * 编辑餐桌
     * @Param  no_id 餐桌id
     */
    public function edit_no()
    {
        $no_id = trim(I('no_id'));
        if(!$no_id){
            $this->ajaxReturn(array('code'=>'error','msg'=>'缺少餐桌id'));
        }
        $no = urldecode(I('no'));
        if(!$no){
            $this->ajaxReturn(array('code'=>'error','msg'=>'请输入餐桌编号'));
        }
        $this->dc_no->where(array('id'=>$no_id))->setField('no',$no);
        $this->ajaxReturn(array('code'=>'success','msg'=>'编辑成功'));
    }

    /**
     * 删除餐桌
     * @Param  no_id 餐桌id
     * @Param  uid 商家uid
     */
    public function del_no()
    {
        $no_id = trim(I('no_id'));
        if(!$no_id){
            $this->ajaxReturn(array('code'=>'error','msg'=>'缺少餐桌id'));
        }
        $res = $this->dc_no->where(array('id'=>$no_id))->delete();
        if($res){
            $this->ajaxReturn(array('code'=>'success','msg'=>'删除成功'));
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'删除失败'));
        }
    }

    /**
     * 删除小程序（测试）
     * @Param 餐桌id
     */
    public function test_xcx()
    {
        $res = M('users')->field('id,mobile')->select();
        foreach ($res as $k => $v) {
            if($v['mobile']){
                $re = M('merchants_users')->where(array('user_phone'=>$v['mobile']))->field('id,pid,agent_id')->find();
                if($re['agent_id']){
                    M('users')->where(array('id'=>$v['id']))->setField(array('muid'=>$re['id'],'pid'=>$re['agent_id']));
                }else{
                    M('users')->where(array('id'=>$v['id']))->setField(array('muid'=>$re['id'],'pid'=>$re['pid']));
                }
            }
        }
        /*$uid = 128;
        $res = M('miniapp')->where(array('mid'=>$uid))->delete();
        $result = M('merchants')->where(array('uid'=>$uid))->setField(array('end_time'=>0,'is_miniapp'=>1,'mini_type'=>0));
        if($res && $result){
            echo '删除成功';
        }else{
            echo '删除失败';
        }*/
    }

    /**
     * 确认收款
     * @Param  order_id 订单id
     */
    public function confirm()
    {
        $order_id = I('order_id','');
        if(empty($order_id)){
            $this->error('缺少参数(order_id)');
        }
        $map['order_id'] = $order_id;
        $map['user_id'] = $this->u_id;
        $order_data = $this->order->field('order_sn,coupon_code,card_code,user_money,integral,order_amount')->where($map)->find();
        if(!$order_data){
            $this->error('确认收款失败，服务器错误');
        }
        $coupon_code = $order_data['coupon_code'];//优惠券code
        $card_code = $order_data['card_code'];//会员卡code
        $price = $order_data['order_amount'];//订单应付金额（优惠后的价格）
        $dikoufen = $order_data['integral'];//会员卡使用的积分
        $yue = $order_data['user_money'];//会员卡使用的余额

        $save['update_time'] = time();
        $save['pay_time'] = time();
        $save['order_status'] = '5';
        $save['pay_time'] = time();
        $this->order->where("order_id='$order_id'")->save($save);

        if($id = $this->pay->where(array('order_id'=>$order_id))->getField('id')){
            $this->pay->where(array('id'=>$id))->setField('status','1');
        }elseif($id = $this->pay->where(array('remark'=>$order_data['order_sn']))->getField('id')){
            $this->pay->where(array('id'=>$id))->setField('status','1');
        }

        //核销优惠券
        if($coupon_code){
            $url = "https://api.weixin.qq.com/card/code/consume?access_token=" . get_weixin_token();
            $data['code'] = $coupon_code;
            $use_coupon = request_post($url, json_encode($data));
            $result = json_decode($use_coupon,true);
            M("screen_user_coupons")->where("usercard=$coupon_code")->setField('status','0');
            file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($result['errcode'] != "0") {
                file_put_contents('./data/log/wz/weixin/coupon.log', date("Y-m-d H:i:s") . '用户使用优惠券失败' . json_encode($use_coupon) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        //会员卡
        if($card_code){
            $card = M("screen_memcard_use")->alias('u')
                ->join('left join ypt_screen_memcard m on u.card_id=m.card_id')
                ->field('m.id,m.credits_set,m.expense,m.expense_credits,m.expense_credits_max,u.card_balance,u.yue,u.card_id,u.card_amount')
                ->where("u.card_code='$card_code'")
                ->find();

            //会员卡消费送积分
            if($card['credits_set']==1){
                $send=floor($price/$card['expense'])*$card['expense_credits'];
                //如果送的积分大于最多可送的分
                if($send>$card['expense_credits_max']){
                    $send=$card['expense_credits_max'];
                }
            }
            if($dikoufen){$data['card_balance']=$card['card_balance']-$dikoufen+$send;}else{$data['card_balance']=$card['card_balance']+$send;}
            if($yue){$data['yue']=$card['yue']-$yue;}
            M("screen_memcard_use")->where("card_code='$card_code'")->save($data);
            $ts['code'] = urlencode($card_code);
            $ts['card_id'] = urlencode($card['card_id']);
            $ts['custom_field_value1'] = urlencode($card['yue']-$yue);//会员卡余额
            $ts['custom_field_value2'] = urlencode(M('screen_memcard_level')->where("c_id=$card[id] and level_integral<=$card[card_amount]")->order('level desc')->getField('level_name'));//会员卡名称
            $ts["add_bonus"] = urlencode($send-$dikoufen);//会员卡积分
            $token = get_weixin_token();
            file_put_contents('./data/log/testcoupon.log', date("Y-m-d H:i:s") . json_encode($ts). PHP_EOL, FILE_APPEND | LOCK_EX);
            request_post('https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$token,urldecode(json_encode($ts)));
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'确认收款成功'));
    }
}

