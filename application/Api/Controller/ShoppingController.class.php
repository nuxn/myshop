<?php
namespace Api\Controller;

use Common\Controller\ApibaseController;
use Think\Controller;
/**
 * 采购商城模块
 * Class ShoppingController
 * @package Api\Controller
 */
class ShoppingController extends ApibaseController
{
	public $goodsModel;
	public $sup_goods_model;
    public $groupModel;
    public $goods_sku_Model;
    public $host;
    public $units;
    public $merchants;
    public $merchants_users;
    public $merchants_agent;
    public $cartModel;
    public $recordModel;
    public $addressModel;
    public $supplierModel;
    public $orderModel;
    public $posts;

    function _initialize()
    {
        parent::_initialize();
        $this->goodsModel = M("goods");
        $this->sup_goods_model = M("sup_goods");
        $this->cartModel = M("sup_cart");
        $this->goods_sku_Model = M("goods_sku");
        $this->groupModel = M("goods_group");
        $this->merchantsModel = M("merchants");
        $this->merchants_users_model = M("merchants_users");
        $this->merchants_agent_model = M("merchants_agent");
        $this->units = M("units");
        $this->addressModel = M("sup_address");
        $this->supplierModel = M("merchants_supplier");
        $this->record = M("search_record");
        $this->orderModel = M("order");
        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        // $this->checkLogin();
    }

    /**
     * 采购商城首页展示
     * @return [type] [description]
     */
    public function index()
    {
    	$uid = $this->get_agent_id();  //代理uid
    	//获取代理分组列表
    	$groups_list = $this->groups_list($uid);
        $data['groups_list'] = $groups_list;
        $data['goods'] =array();
    	//banner图片
    	
    	//商品推荐  每个一级分类，选2个商品，价格最低的商品，价格从低到高排序
    	if ($groups_list['type']==1) {
    		//一级分类
    		foreach ($groups_list['groups'] as $key => &$value) {
                $where = array('group_id'=>$value['group_id'],'status'=>1);
                //商品列表
                $field ='goods_name,goods_id,uid,shop_price,goods_brief,goods_img,group_id';
    			$goods = $this->_list($where,$field,0,2,'shop_price');
    			foreach ($goods as $k => &$v) {
	                unset($v['goods_img']);
	                //供应商店铺设置 店铺名称  起订金
	                $store = $this->get_sup_info($v['uid']);
	                $v['supplier_shortname'] =  $store['supplier_shortname'];
	                $v['send_min_price'] =  $store['send_min_price'];
	                array_push($data['goods'],$v);
    			}
            }
        }else{
            //二级分类
            foreach ($groups_list['groups'] as $key => &$value) {
                if ($value['sub']) {
                    foreach ($value['sub'] as $k => $v) {
                        $group_id .= ','.$v['group_id'];
                    }
                    $where = array('status'=>1);
                    $where['group_id'] = array('in',$group_id);
                    
                }else{
                    $where = array('group_id'=>$value['group_id'],'status'=>1);
                    
                }
                $field ='goods_name,uid,goods_id,shop_price,goods_brief,goods_img,group_id';
                $goods = $this->_list($where,$field,0,2,'shop_price');
                foreach ($goods as $k => &$v) {
	                unset($v['goods_img']);
	                //供应商店铺设置 店铺名称  起订金
	                $store = $this->get_sup_info($v['uid']);
	                $v['supplier_shortname'] =  $store['supplier_shortname'];
	                $v['send_min_price'] =  $store['send_min_price'];
	                array_push($data['goods'],$v);
    			}
            }
    	}
        // 排序商品
        usort($data['goods'], $this->ss('shop_price'));
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
		
    }


    /**
     * [groups 全部分类列表]
     * @return [data] [分类列表]
     */
    public function groups()
    {
    	$uid = $this->get_agent_id();  //代理uid

    	//获取代理分组列表
    	$groups_list = $this->groups_list($uid);
    	if ($groups_list) {
    		$this->ajaxReturn(array('code'=>'success','data'=>$groups_list));
    	}else{
    		$this->ajaxReturn(array('code'=>'error','msg'=>'未找到分类','data'=>array()));
    	}
    	
    }

    /**
     * 搜索记录
     * @return [array] [搜索记录]
     */
    public function search_record()
    {
    	$sup_id = I('sup_id');  //供应商uid
    	$uid = $this->get_agent_id();  //代理uid
    	$where = array('agent_id'=>$uid,'uid'=>$this->userId,'status'=>1);
    	if ($sup_id) {
    		$where['sup_id'] = $sup_id;
    	}
    	$record = $this->record->where($where)->field('id,record,type')->select();
    	$this->ajaxReturn(array('code'=>'success','data'=>$record));
    }

    /**
     * [del_record 删除搜索记录]
     */
    public function del_record()
    {
    	$sup_id = I('sup_id');  //供应商uid
    	$uid = $this->get_agent_id();  //代理uid
    	$where = array('agent_id'=>$uid,'uid'=>$this->userId);
    	if ($sup_id) {
    		$where['sup_id'] = $sup_id;
    	}
    	$record = $this->record->where($where)->field('status')->save(array('status'=>0));
    	if ($record) {
    		$this->ajaxReturn(array('code'=>'success','msg'=>'删除成功'));
    	}else{
    		$this->ajaxReturn(array('code'=>'error','msg'=>'删除失败'));
    	}
    }

    /**
     * [search 模糊搜索]
     * @return [type] [description]
     */
    public function search()
    {
    	$search_record = I('search_record');  //搜索词条
    	$type = I('type');    //搜索类型  1=商品  2=供应商
    	$per_page = 20;//每页数量
        $page = I("page", "0");//页码,第几页
        $uid = I('uid');  //供应商uid
    	//添加搜索记录
    	if($uid){
    		$this->add_record($search_record,$type,$uid);
    	}else{
			$this->add_record($search_record,$type);
    	}
    	//查询代理商
    	$agent_uid = $this->get_agent_id();
    	if ($type==1) {
    		//搜索商品 判断是否查询店铺 
    		if($uid){
				$where['uid'] = $uid;
    		}else{
    			//查询代理旗下所有供应商 agent_uid
	    		$supplier = $this->supplierModel
	            ->where(array('agent_uid'=>$agent_uid,'status'=>1))
	            ->getField('mu_id',0);
	            $str = implode(',',$supplier);
	            $where['uid'] = array('in',$str);
    		}
    		
			//查询商品
    		$where['status'] = 1;
    		$where['goods_name'] = 'like %'.$search_record.'%';
    		$field = 'goods_id,goods_name,goods_number,shop_price,uid,window_img';
    		$goods = $this->_list($where,$field,$page,$per_page);
    		if($goods){
				$this->ajaxReturn(array('code'=>'success','data'=>$goods));
    		}else{
    			$this->ajaxReturn(array('code'=>'success','data'=>array()));
    		}
    		
    	}elseif($type==2){
    		//搜索供应商店铺
    		$where = array('agent_uid'=>$agent_uid,'status'=>1);
    		$where['supplier_shortname'] = 'like %'.$search_record.'%';
    		$field = 'supplier_shortname,mu_id,logo';
    		$supplier = $this->supplierModel->where($where)->limit($page * $per_page, $per_page)->field($field)->select();
    		if($supplier){
				$this->ajaxReturn(array('code'=>'success','data'=>$supplier));
    		}else{
    			$this->ajaxReturn(array('code'=>'success','data'=>array()));
    		}
    	}

    }

    /**
     * [goods_list 分类商品列表]
     */
    public function goods_list()
    {
    	$group_id = I('group_id');  //分类
    	$per_page = 20;//每页数量
        $page = I("page", "0");//页码,第几页
        $where = array('status'=>1,'group_id'=>$group_id);
		$field = 'goods_id,goods_name,goods_number,shop_price,uid,goods_img';
		$goods = $this->_list($where,$field,$page,$per_page);
		$goods_list = $this->sup_goods_model->where($where)->field('uid')->group('uid')->select();
		$sup_list = array();
		foreach ($goods_list as $key => $value) {
			//查询供应商
			$supplier = $this->get_sup_info($value['uid']);
			array_push($sup_list,$supplier);
		}
		$this->ajaxReturn(array(  
            "code" => "success",
            "msg" => "成功",
            "data" => array(
                "goods" => $goods?$goods:array(),
                "sup_list"=>$sup_list
            )
        ));
    }

    /**
     * 供应商列表
     * @return [type] [description]
     */
    public function sup_list()
    {
    	$agent_uid = get_agent_id();  //代理uid
    	$where['agent_uid'] = $agent_uid;
    	$supplier = $this->get_sup_list($where);
    	if($supplier){
			$this->ajaxReturn(array('code'=>'success','data'=>$supplier));
		}else{
			$this->ajaxReturn(array('code'=>'success','data'=>array()));
		}
    }

   /**
    * 供应商店铺商品列表
    * @return [type] [description]
    */
   public function sup_goods_lists()
   {
    	$per_page = 20;//每页数量
        $page = I("page", "0");//页码,第几页
        $uid = I('uid');  //供应商uid
        $where = array('uid'=>$uid,'status'=>1);
        $field = 'goods_id,goods_name,goods_number,shop_price,uid,goods_img';
		$goods = $this->_list($where,$field,$page,$per_page);
		//查询供应商详情
		$f ='mu_id,supplier_shortname,send_min_price,send_day';
		$supplier = $this->get_sup_info($uid,$f);
		$this->ajaxReturn(array(  
            "code" => "success",
            "msg" => "成功",
            "data" => array(
                "goods" => $goods?$goods:array(),
                "supplier"=>$supplier
            )
        ));
   }

   /**
    * 店铺分类列表
    */
   public function store_groups()
   {
   		$uid = I('uid');  //供应商uid
        $where = array('uid'=>$uid,'status'=>1);
		$goods = $this->sup_goods_model->where($where)->field('group_id')->group('group_id')->select();
		$groups_list = array();
		foreach ($goods as $key => $value) {
			$field = 'group_id,group_name,mid,gid,sort';
			$group = $this->groupModel->where(array('group_id'=>$value['group_id']))->field($field)->find();
			if ($group['gid']!=0) {
				//查一级分类
				$groups = $this->groupModel->where(array('group_id'=>$group['group_id']))->field($field)->find();
				$sub = $this->groupModel->where(array('gid'=>$groups['group_id']))->field($field)->select();
				$groups['sub'] = $sub;
				$type=2;
		        array_push($groups_list,$groups);
			}else{
				array_push($groups_list,$group);
			}
		}
		if ($type==2) {
			$data['type'] =2;
			$data['groups_list'] = $groups_list;
		}else{
			$data['type'] =1;
			$data['groups_list'] = $groups_list;
		}
		$this->ajaxReturn(array('code'=>'success','data'=>$data));
   }

    /**
     * 商品详情
     * @return [type] [description]
     */
    public function goods_details()
    {
    	$goods_id = I('goods_id');  //商品id
    	$field = 'goods_id,goods_name,goods_number,shop_price,uid,window_img,units_id,goods_brief,goods_img,group_id';
    	$goods = $this->sup_goods_model->where('goods_id',$goods_id)->field($field)->find();
    	$goods_img = explode(',',$goods['goods_img']);
    	$goods['goods_img'] = $goods_img;
    	$goods['unit_name'] = $this->units->where('id',$goods['units_id'])->getField('unit_name');
    	$this->ajaxReturn(array('code'=>'success','data'=>$goods));
    }

    /**
     * [add_cart 添加购物车]
     */
    public function add_cart()
    {
    	$nums = I('nums',1);	//商品数量
    	$goods_id = I('goods_id');//商品id
    	//检查商品
    	$goods = $this->check_good($goods_id,$nums);
    	$data = array(
    		'uid'=>$this->userId,
    		'goods_id'=>$goods_id,
    		'sup_id'=>$goods->uid,
    		'nums'=>$nums,
    		'goods_info'=>json_encode($goods)
    		);
    	if($this->cartModel->data($data)->add()){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'添加成功'));
    	}else{
			$this->ajaxReturn(array('code'=>'error','msg'=>'添加失败'));
    	}
    	
    }

    /**
     * [cart_list 购物车列表]
     * @return [type] [description]
     */
    public function cart_list()
    {
    	$sup = $this->cartModel->where('uid',$this->userId)->field('sup_id')->group('sup_id')->select();
    	foreach ($sup as $key => &$value) {
    		//查询供应商
    		$value['supplier'] = $this->get_sup_info($value['sup_id']);
    		//查询供应商商户购物车对应商品 判断商品状态
    		$value['cart'] = $this->cartModel->where(array('sup_id'=>$value['sup_id'],'uid'=>$this->userId))->select();
    		foreach ($cart as $k=> &$v) {
    			//判断商品状态
    			$good = $this->sup_goods_model->where(array('goods_id'=>$v['goods_id']))->field('status,goods_number,shop_price,goods_name,goods_img')->find();
    			$goods_img = explode(',',$good['goods_img']);
    			$good['window_img'] = $goods_img[0];
    			$v['goods'] = $good;
			}
		}
		if($sup){
			$this->ajaxReturn(array('code'=>'success','data'=>$sup));
		}else{
			$this->ajaxReturn(array('code'=>'success','data'=>array()));
		}
    }

    /**
     * [del_cart 删除购物车商品]
     * @return [type] [description]
     */
    public function del_cart()
    {
    	$id = I('id');
    	if($this->cartModel->delete($id)){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'删除成功'));
    	}else{
			$this->ajaxReturn(array('code'=>'error','msg'=>'删除失败'));
    	}

    }

    /**
     * [confirm_order 确认订单]
     * @return [type] [description]
     */
    public function confirm_order()
    {
    	//订单商品信息
    	$sup_id = I('sup_id');  //供应商uid
    	$goods = $this->cartModel->where(array('sup_id'=>$sup_id,'uid'=>$this->userId))->select();
    	foreach ($goods as $key => &$value) {
    		$good = $this->sup_goods_model->where(array('goods_id'=>$value['goods_id']))->field('status,goods_number,shop_price,goods_name,goods_img')->find();
    		$goods_img = explode(',',$good['goods_img']);
    		$good['window_img'] = $goods_img[0];
    		$value['goods'] = $good;
		}
		$data['goods'] = $goods;  //商品信息
		$merchants = $this->merchants_users_model->alias('mu')
		->join('ypt_merchants as m on m.uid=mu.id')
		->where(array('id'=>$this->userId))
		->field('mu.balance,mu.pay_pwd,m.province,m.city,m.county,m.address,m.merchant_jiancheng')
		->find();
		$data['address'] = $merchants['province'].$merchants['city'].$merchants['county'].$merchants['address'];   //地址
		$data['balance'] =$merchants['balance'];   //储值
		$data['pay_pwd'] =$merchants['pay_pwd'];	//密码
		$data['merchant_jiancheng'] =$merchants['merchant_jiancheng'];   //商户简称
		//供应商店铺名称
		$supplier = $this->get_sup_info($sup_id);
		$data['supplier_shortname'] = $supplier['supplier_shortname'];
		$this->ajaxReturn(array('code'=>'success','data'=>$data));

    }

    /**
     * 支付接口
     * @return [type] [description]
     */
    public function create()
    { 
        //获取商品信息
        ($goods_id = I('goods_id'))|| $this->ajaxReturn(array('code'=>'error','msg'=>'未获取到商品信息'));  //商品id  多个用逗号隔开
        ($type = I('type'))||$this->ajaxReturn(array('code'=>'error','msg'=>'未获取到支付方式'));         //支付方式  1=余额，2=支付宝
        ($address_id = I('address_id'))||$this->ajaxReturn(array('code'=>'error','msg'=>'未获取到收货信息'));     //收货信息id
        ($date = I('date'))||$this->ajaxReturn(array('code'=>'error','msg'=>'未获取到期望配送时间'));       
        $leave = I('leave');  //留言信息
        $sup_id = I('sup_id');  //供应商uid
        //检查是否支持配送
        $data = $this->check_store($sup_id,$goods_id,$address_id);
        //生成订单
        $order_id = $this->add_order($sup_id,$data,$date,$leave,$type);
        //统一下单 支付
        switch ($type) {
            case 1:
                //余额支付
                $this->yue_pay($order_sn,$data['price']);
                break;
            case 1:
                //支付宝
                $this->zfb_pay($order_sn,$data['price']);
                break;
            default:
                $this->ajaxReturn(array('code'=>'error','msg'=>'暂未开通该支付'));
                break;
        }
        
        //返回请求支付
    }

    /**
     * 余额支付
     * @param  [type] $order_sn [description]
     * @param  [type] $price    [description]
     * @return [type]           [description]
     */
    private function yue_pay($order_sn,$price)
    {
         
    }

    /**
     * 支付宝支付
     * @param  [type] $order_sn [description]
     * @param  [type] $price    [description]
     * @return [type]           [description]
     */
    private function zfb_pay($order_sn,$price)
    {
        // 支付宝合作者身份ID，以2088开头的16位纯数字
        $partner = "2017010704905089";
        // 支付宝账号
        $seller_id = 'guoweidong@hz41319.com';
        // 商品网址
        // 异步通知地址
        //$notify_url = 'http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/ali_barcode_pay';
        $notify_url = 'http://sy.youngport.com.cn/notify/balance_notify.php';
        // 订单标题
        $subject = '余额充值';
        // 订单详情
        $body = '余额充值'; 
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $content = array();
        $content['timeout_express'] = '30m';
        $content['product_code'] = 'QUICK_MSECURITY_PAY';
        $content['total_amount'] = $price;
        $content['subject'] = $subject;
        $content['body'] = $body;
        $content['out_trade_no'] = $order_sn;
        //$orderinfo['order_amount'];
        $data = array();
        $data['app_id'] = $partner;
        $data['biz_content'] = json_encode($content);
        $data['charset'] = 'utf-8';
        $data['format'] = 'json';
        $data['method'] = 'alipay.trade.app.pay';
        $data['notify_url'] = $notify_url;
        $data['sign_type'] = 'RSA';
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['version'] = '1.0';
        $orderInfo = $this->createLinkstring($data);
        //$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
        //var_dump($orderInfo);
        $sign = $this->sign($orderInfo);
        //var_dump($sign);
        $data['sign'] = $sign;
        $orderInfo = $this->getSignContentUrlencode($data);
        //var_dump($orderInfo);
        //$orderInfo .= '&sign='.urlencode($sign);
        //$orderInfo = "biz_content=%7B%22timeout_express%22%3A%2230m%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%2C%22total_amount%22%3A%220.01%22%2C%22subject%22%3A%221%22%2C%22body%22%3A%22%E6%88%91%E6%98%AF%E6%B5%8B%E8%AF%95%E6%95%B0%E6%8D%AE%22%2C%22out_trade_no%22%3A%220603181557-1017%22%7D&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29+16%3A55%3A53&sign_type=RSA&sign=YZPNvZRrerdHsGrcWx9O3IimjMEXGvPeWQcOt8e71eZgo5xedgzn2wDH5nKAX9TEKWa9kDOT7DorsSfYpXST8AQkquzNTqyqzB%2BWmtD4D6Xk73emfJaokbqYNl560rZ01i2mCmdhksgBq2%2F9hgcmPU%2FBzsPlKbw2Zamd50ZWPKE%3D";
        
        return $orderInfo;
    }

    private function getSignContentUrlencode($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $params['sign'] = $sign;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    
    private function createLinkstring($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    private function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
    private function sign($data, $signType = "RSA") 
    {
        $priKey="MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
        //$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
    
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    private function characet($data, $targetCharset) {
        
        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //              $data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }

    /**
     * 添加收货人信息
     */
    public function add_address()
    {
    	$name = I('name');  //收货人
    	$phone = I('phone');
    	if (!$this->check_phone($phone)) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '手机号不符合规则'));
        }
        $data = array(
            'uid'=>$this->userId,
            'name'=>$name,
            'phone'=>$phone
            );
        
    	if($this->addressModel->data($data)->add()){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'添加成功'));
    	}else{
			$this->ajaxReturn(array('code'=>'error','msg'=>'添加失败'));
    	}
    }

    /**
     * 收货人信息列表
     */
    public function address_list()
    {
    	if($data = $this->addressModel->where(array('uid'=>$this->userId))->select()){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
    	}else{
    		$this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array()));
    	}

    }

    /**
     * 更新收货人信息
     */
    public function save_address()
    {
    	$name = I('name');  //收货人
    	$phone = I('phone');
    	$id = I('id');
    	if (!$this->check_phone($phone)) {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '手机号不符合规则'));
        }
        $data = array(
    		'uid'=>$this->userId,
    		'name'=>$name,
    		'phone'=>$phone
    		);
    	if($this->addressModel->where('id',$id)->save($data)){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'更新成功'));
    	}else{
			$this->ajaxReturn(array('code'=>'error','msg'=>'更新失败'));
    	}
    }

    /**
     * 删除收货人信息
     */
    public function del_address()
    {
    	$id = I('id');
    	if($this->addressModel->delete($id)){
    		$this->ajaxReturn(array('code'=>'success','msg'=>'删除成功'));
    	}else{
			$this->ajaxReturn(array('code'=>'error','msg'=>'删除失败'));
    	}
    }

    /**
     * -----------------------------------------------------------------------------------------------------------
     *商超供应链商品管理
     */
    /**
     * 获取商户商品详情
     */
    public function goods_info()
    {
        if (IS_POST) {
            ($goods_id = I('goods_id'))||$this->ajaxReturn(array('code'=>'error','msg'=>'未获取到商品id'));
            $field = 'goods_id,goods_img1,goods_img2,goods_img3,goods_img4,goods_img5,goods_name,group_id,is_sku,bar_code,buy_price,shop_price,goods_number,units_id,trade,put_xcx,put_two,put_pos';
            $goods = $this->goodsModel->where(array('goods_id'=>$goods_id))->field($field)->find();
            $goods_img = array();
            if ($goods['goods_img1']) {
                array_push($goods_img,$goods['goods_img1']);
            }
            if ($goods['goods_img2']) {
                array_push($goods_img,$goods['goods_img2']);
            }
            if ($goods['goods_img3']) {
                array_push($goods_img,$goods['goods_img3']);
            }
            if ($goods['goods_img4']) {
                array_push($goods_img,$goods['goods_img4']);
            }
            if ($goods['goods_img5']) {
                array_push($goods_img,$goods['goods_img5']);
            }
            unset($goods['goods_img1']);
            unset($goods['goods_img2']);
            unset($goods['goods_img3']);
            unset($goods['goods_img4']);
            unset($goods['goods_img5']);
            $goods['goods_img'] = $goods_img;
            
            //获取商品分类
            $group = $this->groupModel->where(array('group_id'=>$goods['group_id']))->find();
            if ($group['gid']==0) {
                #一级分类
                $goods['group'] = array(
                    'group_id'=>$group['group_id'],
                    'group_name'=>$group['group_name'],
                    'gid'=>$group['gid']
                    );
            }else{
                #有二级分类 
                $sup = $this->groupModel->where(array('group_id'=>$group['gid']))->find();
                $goods['group'] = array(
                    'group_id'=>$group['group_id'],
                    'group_name'=>$group['group_name'],
                    'gid'=>$group['gid'],
                    'sup'=>array(
                        'group_id'=>$sup['group_id'],
                        'group_name'=>$sup['group_name'],
                        'gid'=>$sup['gid']
                        )
                    );
            }
            $goods['units'] = array();
            //获取商品计量单位
            if ($goods['is_sku']==0) {
                //单单位
                $unit_name = $this->units->where(array('id'=>$goods['units_id']))->getField('unit_name');
                $goods['unit_name'] = $unit_name?$unit_name:'';
                array_push($goods['units'],array('unit_name'=>$unit_name?$unit_name:'','buy_price'=>floatval($goods['buy_price']),'shop_price'=>floatval($goods['shop_price']),'goods_number'=>$goods['goods_number'],'units_id'=>$goods['units_id']));
            }else{
                //多单位 properties 单位名称  cost 进价   price 售价  quantity 库存
                $sku = $this->goods_sku_Model->where(array('goods_id'=>$goods_id))->field('sku_id,properties as unit_name,cost as buy_price,price as shop_price,quantity as goods_number,units_id')->select();
                foreach ($sku as $key => &$value) {
                    if ($value['unit_name']==0) {
                        $value['unit_name'] = $this->units->where(array('id'=>$value['units_id']))->getField('unit_name');
                    }
                 }
                $goods['units']=$sku;
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功",'data'=>$goods));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }
    

    /**
     * 获取商户计量单位设置
     */
    public function get_units()
    {
        if (IS_POST) {
            $trade = I('trade');  //行业类别  1=便利店  2= 餐饮
            $count = $this->units->where('uid='.$this->userId.' and belong_to=2 and trade='.$trade)->field('id,unit_name')->count();
            if ($count==0) {
                $data = $this->units->where('uid=0 and belong_to=2 and trade=1')->field('id,unit_name')->select();
                $res = array();
                foreach ($data as $key => $value) {
                    $res[$key]['unit_name'] = $value['unit_name'];
                    $res[$key]['belong_to'] =2;
                    $res[$key]['trade'] = $trade;
                    $res[$key]['uid'] = $this->userId;
                }
                $this->units->addAll($res);
            }else{
                $data = $this->units->where('uid='.$this->userId.' and belong_to=2 and trade='.$trade)->field('id,unit_name')->select();
            }
            
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     * 商户保存计量单位
     */
    public function save_units()
    {
        if (IS_POST) {
            $this->units->where(array('belong_to'=>2,'uid'=>$this->userId))->delete();
            $unit_name = I('unit_name');
            $trade = I('trade');
            $units_name = explode(',',$unit_name);
            $data = array();
            foreach ($units_name as $key => &$value) {
                $data[$key]['unit_name'] = $value;
                $data[$key]['belong_to'] =2;
                $data[$key]['trade'] = $trade;
                $data[$key]['uid'] = $this->userId;
            }
            if ($this->units->addAll($data)) {
                $this->write_log('保存单位',0);
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            }else{
                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     * 商品图片上传编辑 
     */
    public function upload_picture()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 0;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'goods/';
            $upload->saveName = uniqid();//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
        }
        if($info['img']){
            $img = 'http://' . $_SERVER['HTTP_HOST'].'/data/upload/' . $info['img']['savepath'] . $info['img']['savename'];
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
    }

    /**
     * 分类图片上传编辑 
     */
    public function upload_group()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 0;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'group/';
            $upload->saveName = uniqid();//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
        }
        if($info['img']){
            $img = 'http://' . $_SERVER['HTTP_HOST'].'/data/upload/' . $info['img']['savepath'] . $info['img']['savename'];
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
    }

    /**
     *未开启库存添加商户商品
     */
    public function add_goods()
    {
        if (IS_POST) {
            $this->posts = I('');
            if(!I('trade')) $this->ajaxReturn(array("code" => "error", "msg" => "请选择上传行业"));
            switch (I('trade')) {
                case '1':
                    $this->addStore();
                    break;
                case '2':
                    $this->addDc();
                    break;
                default:
                    $this->ajaxReturn(array("code" => "error", "msg" => "暂未该行业"));
                    break;
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     *未开启库存添加便利店商品
     */
    private function addStore()
    {
        if(!trim($this->posts['goods_img'])) $this->ajaxReturn(array("code" => "error", "msg" => "请上传商品图片"));
        if(!trim($this->posts['goods_name'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品名称"));
        if(!trim($this->posts['group_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择分类"));
        if(!trim($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品条形码"));
        // if(!trim($post['goods_number'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
        if(!trim($this->posts['trade'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择上传行业"));
        $units = json_decode(htmlspecialchars_decode($this->posts['units']),true);
        unset($this->posts['units']);
    	if(!is_array($units))$this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));
    	if (count($units) > 1) {
    		//开启多单位
    		$this->posts['is_sku'] = 1;
    		if (count($units) > 3) $this->ajaxReturn(array("code" => "error", "msg" => "最多3个单位"));
    		if (count($units) < 2) $this->ajaxReturn(array("code" => "error", "msg" => "最少2个单位"));
    	}else{
            $this->posts['is_sku'] = 0;
        }
        if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));
    	if(!trim($units[0]['units_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写计量单位id"));
		if(!trim($units[0]['cost'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写进货价"));
        if(!trim($units[0]['price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写售价"));
    	if(!trim($units[0]['quantity'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
    	$this->posts['units_id'] = $units[0]['units_id'];
    	$this->posts['buy_price'] = $units[0]['cost'];
    	$this->posts['shop_price'] = $units[0]['price'];
        $this->posts['goods_number'] = $units[0]['quantity'];
        if($this->posts['goods_img']){
            $goods_img = $this->posts['goods_img'];
            $goods_img_arr = explode(',', $goods_img);
            $this->posts['window_img'] = $goods_img_arr[0];
            foreach ($goods_img_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_img'."$count"] = $v;
            }
        }
        
        #如果传了goods_id就是编辑save
        if($this->posts['goods_id']!=''){
            $res = $this->goodsModel->where(array('goods_id'=>$this->posts['goods_id']))->save($this->posts);

            if ($res!==false) {
            	//多单位
            	if ($this->posts['is_sku']==1) {
            		# 将商品原有计量单位删除
            		$this->goods_sku_Model->where(array('goods_id'=>$this->posts['goods_id']))->delete();
                	# 将SKU插入数据库
                	$this->save_sku($this->posts['goods_id'],$units);
                	# 更新商品表规格
        			$this->update_other_info($goods_id);
            	}
            	$this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
            }
        }else{
            //检查条形码唯一性
            if ($this->goodsModel->where(array('bar_code'=>$this->posts['bar_code'], 'mid' => $this->userId,'is_delete'=>0))->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "存在的商品条形码"));
            }
            //检查商品名称唯一性
            if ($this->goodsModel->where(array('goods_name'=>$this->posts['goods_name'], 'mid' => $this->userId,'is_delete'=>0))->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "存在的商品名称"));
            }
            #否则就是添加add
            $this->posts['add_time'] = time();
            $this->posts['mid'] = $this->userId;
            if ($goods_id = $this->goodsModel->add($this->posts)) {
            	//多单位
            	if ($this->posts['is_sku']==1) {
            		# 将SKU插入数据库
                	$this->save_sku($goods_id,$units);
                	# 更新商品表规格
        			$this->update_other_info($goods_id);
            	}
                //添加到代理商品库
                $this->add_agent_goods($this->posts,$units);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功",'data'=>array('goods_id'=>$goods_id)));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败"));
            }
        }
        
    }

    /**
     *未开启库存添加餐饮商品
     */
    private function addDc()
    {
        if(!trim($this->posts['goods_img'])) $this->ajaxReturn(array("code" => "error", "msg" => "请上传商品图片"));
        if(!trim($this->posts['goods_name'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品名称"));
        if(!trim($this->posts['group_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择分类"));
        // if(!trim($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品条形码"));
        // if(!trim($post['goods_number'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
        if(!trim($this->posts['trade'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择上传行业"));
        $units = json_decode(htmlspecialchars_decode($this->posts['units']),true);
        unset($this->posts['units']);
        if(!is_array($units))$this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));
        if (count($units) > 1) {
            //开启多单位
            $this->posts['is_sku'] = 1;
            if (count($units) > 3) $this->ajaxReturn(array("code" => "error", "msg" => "最多3个单位"));
            if (count($units) < 2) $this->ajaxReturn(array("code" => "error", "msg" => "最少2个单位"));
        }
        if(!trim($units[0]['units_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写计量单位id"));
        // if(!trim($units[0]['cost'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写进货价"));
        if(!trim($units[0]['price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写售价"));
        if(!trim($units[0]['quantity'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
        $this->posts['units_id'] = $units[0]['units_id'];
        // $this->posts['buy_price'] = $units[0]['cost'];
        $this->posts['shop_price'] = $units[0]['price'];
        $this->posts['goods_number'] = $units[0]['quantity'];
        if($this->posts['goods_img']){
            $goods_img = $this->posts['goods_img'];
            $goods_img_arr = explode(',', $goods_img);
            $this->posts['window_img'] = $goods_img_arr[0];
            foreach ($goods_img_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_img'."$count"] = $v;
            }
        }
        
        #如果传了goods_id就是编辑save
        if($this->posts['goods_id']!=''){
            $res = $this->goodsModel->where(array('goods_id'=>$this->posts['goods_id']))->save($this->posts);
            if ($res!==false) {
                //多单位
                if ($this->posts['is_sku']==1) {
                    # 将商品原有计量单位删除
                    $this->goods_sku_Model->where(array('goods_id'=>$this->posts['goods_id']))->delete();
                    # 将SKU插入数据库
                    $this->save_sku($this->posts['goods_id'],$units);
                    # 更新商品表规格
                    $this->update_other_info($goods_id);
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
            }
        }else{
            #否则就是添加add
            //检查商品名称唯一性
            if ($this->goodsModel->where(array('goods_name'=>$this->posts['goods_name'], 'mid' => $this->userId,'is_delete'=>0))->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "存在的商品名称"));
            }
            $this->posts['add_time'] = time();
            $this->posts['mid'] = $this->userId;
            if ($goods_id = $this->goodsModel->add($this->posts)) {
                //多单位
                if ($this->posts['is_sku']==1) {
                    # 将SKU插入数据库
                    $this->save_sku($goods_id,$units);
                    # 更新商品表规格
                    $this->update_other_info($goods_id);
                }
                //添加到代理商品库
                // $this->add_agent_goods($this->posts,$units);
                $this->ajaxReturn(array("code" => "success", "msg" => "添加成功",'data'=>array('goods_id'=>$goods_id)));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败"));
            }
        }
        
    }

    /**
     * 开启库存添加商品
     */
    public function open_goods()
    {
    	if (IS_POST) {
            $post = I('');
            if(!trim($post['goods_img'])) $this->ajaxReturn(array("code" => "error", "msg" => "请上传商品图片"));
            if(!trim($post['goods_name'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品名称"));
            if(!trim($post['buy_price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写进货价"));
            if(!trim($post['group_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择分类"));
            if(!trim($post['goods_number'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
            $units = json_decode(htmlspecialchars_decode($post['units']),true);
        	if(!is_array($units))$this->ajaxReturn(array("code" => "error", "msg" => "参数错误"));
        	if (count($units) > 1) {
        		//开启多单位
        		$post['is_sku'] = 1;
        		if (count($units) > 3) $this->ajaxReturn(array("code" => "error", "msg" => "最多3个单位"));
        		if (count($units) < 2) $this->ajaxReturn(array("code" => "error", "msg" => "最少2个单位"));
        	}

        	if(!trim($units[0]['units_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写计量单位"));
        	if(!trim($units[0]['shop_price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写售价"));
        	if(!trim($units[0]['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写售价"));
        	$post['units_id'] = $units[0]['units_id'];
        	$post['bar_code'] = $units[0]['bar_code'];
        	$post['shop_price'] = $units[0]['shop_price'];
            if($post['goods_img']){
                $goods_img = $post['goods_img'];
	            $goods_img_arr = explode(',', $goods_img);
	            foreach ($goods_img_arr as $k => $v) {
	                $count = $k + 1;
	                $post['goods_img'."$count"] = $v;
	            }
	            unset($post['goods_img']);
            }
            //检查条形码唯一性
            foreach ($units as $val) {
                //检查条形码唯一性
                if ($this->goods_sku_Model->where(array('goods_code'=>$val['bar_code']))->find()) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "存在的商品条形码"));
                }
            }
            #如果传了goods_id就是编辑save
            if($post['goods_id']=''){
                $res = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->save($post);
                if ($res!==false) {
                	//多单位
                	if ($post['is_sku']==1) {
                		# 将商品原有计量单位删除
	            		$this->goods_sku_Model->where(array('goods_id'=>$post['goods_id']))->delete();
	                	# 将SKU插入数据库
	                	$this->open_sku($post['goods_id'],$units);
                	}
                	$this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
                }
            }else{
                #否则就是添加add
                $post['add_time'] = time();
                $post['uid'] = $this->userId;
                if ($goods_id = $this->goodsModel->add($post)) {
                	//多单位
                	if ($post['is_sku']==1) {
                		# 将SKU插入数据库
	                	$this->open_sku($goods_id,$units);
                	}
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功"));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "添加失败"));
                }
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     * 商品投放设置
     */
    public function put_goods()
    {
    	if(!$type = I('type'))$this->ajaxReturn(array("code" => "error", "msg" => "请选择小程序类型"));   //投放类型  1=小程序，2=双屏 ,3=pos
    	$post = I('');
    	switch ($type) {
    		case 1:
    			$this->put_xcx($post);
    			break;
    		case 2:
    			$this->put_two($post);
    			break;
                case 3:
                $this->put_pos($post);
                break;
    		default:
    			$this->ajaxReturn(array("code" => "error", "msg" => "未定义的投放类型"));
    			break;
    	}
    }

    /**
     * 获取投放设置接口
     */
    public function get_put()
    {
        $post = I('');
        if(!trim($post['type'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择投放类型"));
        switch ($post['type']) {
            case 1:
                $this->get_xcx($post);
                break;
            case 2:
                $this->get_two($post);
                break;
            case 3:
                $this->get_pos($post);
                break;
            default:
                $this->ajaxReturn(array("code" => "error", "msg" => "未定义的投放类型"));
                break;
        }
    }

    /**
     * 获取所有分组
     */
    public function group_list()
    {
        $trade = I('trade',0);  //行业
        $mid = $this->userId;
        if($this->userInfo['role_id']==77){
            $mid = M('merchants_users')->where(array('id'=>$this->userInfo['uid']))->getField('agent_id');
        }
        $data = $this->groupModel->where(array('mid' => $mid,'trade'=>$trade,'gid'=>0))->order(array('sort'=>'asc','add_time' => 'DESC'))->field("group_id,group_name,gid,sort,img")->select();
        foreach ($data as $k => &$v) {
            $res = $this->groupModel->where(array('mid' => $mid,'gid'=>$v['group_id'],'trade'=>$trade))->order(array('sort'=>'asc','add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
            $v['goods_number'] = count($res);
            $v['childData'] = $res;
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }


    /**
     * 保存和编辑分类
     * @return [type] [description]
     */
    public function save_group()
    {
        $group_id = I('group_id');  //分类id
        (I('group_name'))||$this->ajaxReturn(array('code'=>'error','分类名称不能为空'));
        $post = I('');
        if ($group_id=='') {
            //添加分类

            $this->add_groups($post);
        }else{
            //编辑分类
            $this->edit_groups($post);
        }

    }

    /**
     * 修改分组排序
     * @return [type] [description]
     */
    public function save_sort()
    {
        if (I('group_id')=='') {
            //修改一级
            $sort = json_decode(htmlspecialchars_decode(I('sort')), true);
            foreach ($sort as $key => $value) {
                $this->groupModel->where(array('group_id'=>$value['group_id']))->setField('sort',$value['sort']);
            }
        }else{
            //修改二级
            $group_name = I('group_name');
            $group_id = I('group_id');
            if ($this->groupModel->where(array("group_name" => $group_name, "group_id" => array('neq', $group_id),'mid'=>$this->userId))->find()){
                $this->ajaxReturn(array("code" => "error", "msg" => "分类名称不能重复"));
            }
            $this->groupModel->where(array('group_id'=>I('group_id')))->setField('group_name',$group_name);
            $sort = json_decode(htmlspecialchars_decode(I('sort')), true);
            foreach ($sort as $key => $value) {
                $this->groupModel->where(array('group_id'=>$value['group_id']))->setField('sort',$value['sort']);
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }

    

    /**检查条码格式
     * @param $barcode
     * @return bool
     */
    private function check_barcode($barcode = 0)
    {
        if (mb_strlen($barcode) < 6) return false;
        else return true;
    }

    /**
     *  添加分组
     */
    private function add_groups($data)
    {
        $data['mid'] = $this->userId;
        $data['add_time'] = time();
        // if ($data['gid']) {
        // 	//二级
        // 	$count = $this->groupModel->where(array("mid" => $this->userId,'gid'=>$data['gid'],"trade"=>$data['trade']))->count('group_id');
        // 	if ($count>14) {
        // 		$this->ajaxReturn(array("code" => "error", "msg" => "商品二级分类数量超过限制"));
        // 	}
        // }else{
        // 	//一级
        // 	$count = $this->groupModel->where(array("mid" => $this->userId,"trade"=>$data['trade']))->count('group_id');
        // 	if ($count>19) {
        // 		$this->ajaxReturn(array("code" => "error", "msg" => "商品一级分类数量超过限制"));
        // 	}
        // }
        $is_name = $this->groupModel->where(array("group_name" => $data['group_name'], "mid" => $this->userId,'gid'=>$data['gid'],"trade"=>$data['trade']))->find();
        if ($is_name) $this->ajaxReturn(array("code" => "error", "msg" => "店铺商品分类名已存在"));
        $result = $this->groupModel->data($data)->add();
        // echo $this->goods_group_model->getLastSql();
        if ($result) {
            //检测上级分组是否有商品  更改分组商品
            if ($data['gid']!=0) {
                $goods = $this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$data['gid'], "is_on_sale" => 1, "is_delete" => 0))->select();
                if($goods){
                    $group_id = $this->groupModel->where(array("mid" => $this->userId,'gid'=>$data['gid'],"trade"=>$data['trade']))->getField("group_id");
                    $result = $this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$data['gid'], "is_on_sale" => 1, "is_delete" => 0))->save(array("group_id" => $group_id));
                }
            }
            $this->write_log('新增商品分类'. $data['group_name'],$result);
            
            $this->ajaxReturn(array("code" => "success", "msg" => "新增商品分类成功", "data" => array("group_id" => (string)$result)));
        }else {
            $this->ajaxReturn(array("code" => "error", "msg" => "新增商品分类失败"));
        }

    }

    /**
     * 分组编辑
     */
    private function edit_groups($data)
    {
        ($data['group_id'])||$this->ajaxReturn(array("code" => "error", "msg" => "分类编号不能为空"));
        if ($this->groupModel->where(array("group_name" => $data['group_name'], "group_id" => array('neq', $data['group_id']),'mid'=>$this->userId))->find()){
            // echo $this->goods_group_model->getLastSql();
                $this->ajaxReturn(array("code" => "error", "msg" => "分类名称不能重复"));
        }
        //判断几级分组
        if ($this->groupModel->where(array("mid" => $this->userId,"group_id" => $data['group_id'], "gid" => 0))->find()) {
            //是一级分组
            if($this->groupModel->where(array("mid" => $this->userId,"gid" => $data['group_id']))->find()&&$data['gid']!=0){
                $this->ajaxReturn(array("code" => "error", "msg" => "该分类存在二级分类，不能修改"));
            }
        } 
        $this->groupModel->where(array("mid" => $this->userId,'group_id' => $data['group_id']))->save(array("group_name" => $data['group_name'],"sort"=>$data['sort'],"gid"=>$data['gid']));
        $this->write_log('编辑商品分类'. $data['group_name'],$result);

        $this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
        
    }

    /**
     * 获取小程序投放设置
     * @param  [type] $post [description] 
     * @return [type]       [description]
     */
    private function get_xcx($post)
    {
        if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
        $goods = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->field('goods_img1,put_xcx,goods_id,goods_brief,original_price,star,is_hot,hot_sort')->find();
        $desc = $this->get_desc_img($post['goods_id']);
        $goods['desc'] = $desc;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功","data"=>$goods));

    }

    private function get_two($post)
    {
        if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
        $goods = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->field('goods_img1,put_two,goods_id')->find();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功","data"=>$goods));
    }

    private function get_pos($post)
    {
        if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
        $goods = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->field('goods_img1,put_pos,goods_id')->find();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功","data"=>$goods));
    }
    /**
     * 获取图文描述
     * @return [type] [description]
     */
    private function get_desc_img($goods_id)
    {
        $desc = M('goods_desc_img')->where(array('goods_id'=>$goods_id))->field('url')->select();
        $desc_array = array();
        foreach ($desc as $key => $value) {
            array_push($desc_array,$value['url']);
        }
        return $desc_array;
    }

    /**
     * 投放小程序
     * @return [type] [description]
     */
    private function put_xcx($post)
    {
    	if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
    	if(!trim($post['type'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择小程序类型"));
    	// if(!trim($post['goods_brief'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品描述"));
    	// if(!trim($post['original_price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品原价"));
    	if($post['trade']==1){if(!trim($post['desc'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品图文描述"));}
    	// if(!trim($post['status'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品状态"));
    	$arr =array('0','1','2');
    	if (!in_array($post['status'], $arr)) {
    		$this->ajaxReturn(array("code" => "error", "msg" => "未定义的商品状态"));
    	}
        if ($post['status']==2) {
            if(!$this->goodsModel->where(array('goods_id' => $post['goods_id']))->getField('goods_img1')){
                $this->ajaxReturn(array("code" => "error", "msg" => "缺少商品图片，不能上架!"));
            }
        }
        if ($this->goodsModel->where(array('goods_id'=>$post['goods_id']))->getField('trade')==1) {
            if(!M('goods_desc_img')->where(array('goods_id'=>$post['goods_id']))->find()){
                //上传代理商品库
                $this->add_agent_desc($post);
            }
        }
        
        $post['put_xcx'] = $post['status'];
    	if($post['goods_id']){
			$res = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->save($post);
            //上传图文描述
            if($post['trade']==1){$this->add_desc($post['desc'], $post['goods_id']);}
			if ($res!==false) {
            	$this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
            }
    	}
    }

    private function add_agent_desc($post)
    {
        $bar_code = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->getField('bar_code');
        $goods_id = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->getField('goods_id');
        if ($bar_code) {
            M('agent_goods')->where(array('bar_code'=>$bar_code))->save($post);
            $desc_img = explode(',', $post['desc']);
            $desc_data = array();
            foreach ($desc_img as $valu) {
                $desc_data[] = array(
                    'goods_id' => $goods_id,
                    'url'   => $valu,
                );
            }
            M('agent_desc')->addAll($desc_data);
        }
        

    }

    /**
     * 上传图文描述
     * @param [type] $desc_imgs [description]
     * @param [type] $goods_id  [description]
     */
    private function add_desc($desc_imgs, $goods_id)
    {
        M('goods_desc_img')->where(array('goods_id'=>$goods_id))->delete();
        $desc_img = explode(',', $desc_imgs);
        $desc_data = array();
        foreach ($desc_img as $valu) {
            $desc_data[] = array(
                'goods_id' => $goods_id,
                'url'   => $valu,
            );
        }
        M('goods_desc_img')->addAll($desc_data);
    }

     /**
     * 生成待支付订单，扣除购物车商品，扣除商品库存，如果订单取消或者超时1小时未支付，库存返还
     * @param [type] $sup_id 供应商uid
     * @param [array] $data   商品信息 收货信息 金额
     * @param [type] $date   期望配送时间
     * @param [type] $leave  留言信息
     * @param [type] $type   支付方式  1=余额，2=支付宝
     */
    private function add_order($sup_id,$data,$date,$leave,$type)
    {
        //开始添加数据
        $this->startTrans();
        //查看商品
        foreach($data['goods'] as $k =>$v){
            $goods_img = explode(',',$v['goods_img']);
            $order_good['goods_id'] = $v['goods_id'];
            $order_good['group_id'] = $v['group_id'];
            $order_good['goods_name'] = $v['goods_name'];
            $order_good['goods_num'] = $v['nums'];
            $order_good['goods_price'] = $v['shop_price'];
            $order_good['spec_key'] = $v['units_id'];
            $order_good['spec_key_name'] = $this->get_units_name($units_id);
            $order_good['goods_img'] = $goods_img[0];
            $order_goods[] = $order_good;
            $order['total_amount'] += $v['shop_price']*$v['nums'];
            $order['order_goods_num'] += $v['nums'];
            $order['type'] = 5;       
        }
        
        $order['order_amount'] = $order['total_amount'];
        $order['order_sn'] = $this->get_order_sn();
        $order['user_id'] = $this->userId;
        $order['sup_id'] = $sup_id;
        $order['user_note'] = $leave;
        $order['consignee'] = $data['address']['name'];
        $order['mobile'] = $data['address']['phone'];
        $merchants = $this->get_merchant_info();
        $order['address'] = $merchants['province'].$merchants['city'].$merchants['county'].$merchants['address'];
        $order['order_status'] = 1;
        if ($type==2) {
            $order['paystyle'] = 2;
        }elseif($type==1){
            $order['paystyle'] = 6;
        }
        //添加订单
        if(!$this->orderModel->field(true)->data($data)->add()){
            $this->rollback();
            $this->ajaxReturn(array('code'=>'error','msg'=>'添加订单失败')); 
        }
        $order_id = $this->orderModel->getLastInsID();
        foreach($order_goods as $key=>$v){
            $order_goods[$key]['order_id'] = $order_id;
        }
        if(!M('order_goods')->addAll($order_goods)){
            $this->rollback();
            return $this->error('添加商品失败');
        }
        //减去商品库存
        foreach($order_goods as $value){
            if(!M('goods')->where('goods_id',$value['goods_id'])->setDec('goods_number',$value['goods_num'])){
                $this->rollback();
                return $this->error('修改商品库存失败');
            }
            //删除购物车
            if(!M('cart')->where('goods_id',$value['goods_id'])->where('sup_id',$sup_id)->where('uid',$this->userId)->delete()){
                $this->rollback();
                return $this->error('删除购物车失败');
            }
        }
        $this->commit();
        return $order_id;
    }

    /**
     * 获取登录商户信息
     * @return [type] [description]
     */
    private function get_merchant_info()
    {
       $merchants = $this->merchants_users_model->alias('mu')
        ->join('ypt_merchants as m on m.uid=mu.id')
        ->where(array('id'=>$this->userId))
        ->field('mu.balance,mu.pay_pwd,m.province,m.city,m.county,m.address,m.merchant_jiancheng')
        ->find();
        return $merchants;
    }

    /**
     * 生成采购订单号
     * @return [type] [description]
     */
    private function get_order_sn()
    {
        return '10'.date('Ymdhis').rand(10000,99999);
    }

    /**
     * 获取计量单位名称
     * @return [type] [description]
     */
    private function get_units_name($units_id)
    {
        return $this->units->where(array('id'=>$units_id))->getField('unit_name');
    }

    /**
     * 检查是否支持配送
     * @param send_time_range 配送时间  
     * @param send_min_price 配送起订金额
     */
    private function check_store($sup_id,$goods_id,$address_id)
    {
        //获取供应商店铺设置
        $supplier = $this->get_sup_info($sup_id,'send_time_range,send_min_price');   
        //计算总金额
        // $goods_id = explode(',',$goods_id);
        $where['c.goods_id'] = array('in',$goods_id);
        $goods = $this->sup_goods_model->alias('g')
        ->join('ypt_sup_cart as c on c.goods_id=g.goods_id')
        ->where($where)
        ->field('g.goods_id,g.goods_name,g.shop_price,g.goods_number,c.nums,g.status,g.units_id,g.goods_img,g.group_id')
        ->select();
        $price = 0;
        foreach ($goods as $key => $value) {
            if ($value['status']!=1) {
                $this->ajaxReturn(array('code'=>'error','msg'=>$value['goods_name'].'商品已下架'));
            }
            if ($value['goods_number']<$value['nums']) {
                $this->ajaxReturn(array('code'=>'error','msg'=>$value['goods_name'].'商品库存不足'));
            }
            $price += $value['nums']*$value['shop_price'];
        }
        if ($price<$supplier['send_min_price']) {
            $this->ajaxReturn(array('code'=>'error','msg'=>'未达到配送起订金额'));
        }
        //查询收货地址      
        if(!$address = $this->addressModel->where('id',$address_id)->find()){
            $this->ajaxReturn(array('code'=>'error','msg'=>'未找到该收货信息'));
        }
        $data['goods'] =$goods;
        $data['price'] =$price;
        $data['address'] =$address;
        return $data;
    }

    /**
     * 投放双屏
     * @return [type] [description]
     */
    private function put_two($post)
    {
    	if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
    	if(!trim($post['type'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择小程序类型"));
    	if(!trim($post['status'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品状态"));
    	$arr =array('0','1','2');
    	if (!in_array($post['status'], $arr)) {
    		$this->ajaxReturn(array("code" => "error", "msg" => "未定义的商品状态"));
    	}
    	if($post['goods_id']){
			$res = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->save(array('put_two'=>$post['status']));
			if ($res!==false) {
            	$this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
            }
    	}
    }

    /**
     * 投放双屏
     * @return [type] [description]
     */
    private function put_pos($post)
    {
        if(!trim($post['goods_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "未识别的商品id"));
        if(!trim($post['type'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择小程序类型"));
        if(!trim($post['status'])) $this->ajaxReturn(array("code" => "error", "msg" => "请输入商品状态"));
        $arr =array('0','1','2');
        if (!in_array($post['status'], $arr)) {
            $this->ajaxReturn(array("code" => "error", "msg" => "未定义的商品状态"));
        }
        if($post['goods_id']){
            $res = $this->goodsModel->where(array('goods_id'=>$post['goods_id']))->save(array('put_pos'=>$post['status']));
            if ($res!==false) {
                $this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
            }
        }
    }

    /**
     * 将SKU插入数据库
     * @param  [type] $goodsId 商品id
     * @param  [array] $units   计量单位
     * @return [type]          [description]
     */
    private function save_sku($goodsId,$units)
    {
    	foreach ($units as $val) {
    		$unit_name = $this->units->where(array('id'=>$val['units_id']))->getField('unit_name');
	        $sku_data = array(
	            'goods_id' => $goodsId,
	            'units_id' => $val['units_id'],
	            'properties' => $unit_name,
	            'properties_name' => $unit_name,
	            'cost' => $val['cost']?$val['cost']:0,
	            'price' => $val['price'],
	            'add_time' => time(),
                'quantity'=>$val['quantity']
	        );
	        $this->goods_sku_Model->add($sku_data);
    	}
    }

    /**
     * 添加代理商品库
     */
    private function add_agent_goods($post,$units)
    {
        if (!M('agent_goods')->where(array('bar_code'=>$post['bar_code']))->getField('id')) {
            $agent_id = M('merchants_users')->where(array('id'=>$this->userId))->getField('agent_id');
            $unit_name = $this->units->where(array('id'=>$post['units_id']))->getField('unit_name');
            $post['unit_name'] = $unit_name;
            $post['uid'] = $this->userId;
            $post['agent_id'] = $agent_id?$agent_id:0;
            $goods_id = M('agent_goods')->data($post)->add();
            if ($post['is_sku']==1) {
                  foreach ($units as $val) {
                    $unit_name1 = $this->units->where(array('id'=>$val['units_id']))->field('unit_name')->find();
                    $sku_data = array(
                        'goods_id' => $goods_id,
                        'units_id' => $val['units_id'],
                        'unit_name' => $unit_name1,
                        'buy_price' => $val['cost']?$val['cost']:0,
                        'shop_price' => $val['price'],
                        'add_time' => time()
                    );
                    M('agent_sku')->add($sku_data);
                }
            }
        }
          
    }

     /**
     * 将SKU插入数据库
     * @param  [type] $goodsId 商品id
     * @param  [array] $units   计量单位
     * @return [type]          [description]
     */
    private function open_sku($goodsId,$units)
    {
    	foreach ($units as $val) {
    		$unit_name = $this->units->where(array('id'=>$val['units_id']))->getField('unit_name');
            //检查条形码唯一性
            if ($this->goods_sku_Model->where(array('goods_code'=>$val['bar_code']))->find()) {
                $this->ajaxReturn(array("code" => "error", "msg" => "存在的商品条形码"));
            }
	        $sku_data = array(
	            'goods_id' => $goodsId,
	            'units_id' => $val['units_id'],
	            'properties' => $unit_name,
	            'properties_name' => $unit_name,
	            'bar_code' => $val['bar_code'],
	            'price' => $val['shop_price'],
	            'ratio' => $val['ratio'],
	            'add_time' => time()
	        );
	        $this->goods_sku_Model->add($sku_data);
    	}
    }
    
	/**
     * 更新商品表库存、进价
     * @param $goodsId
     */
    private function update_other_info($goodsId)
    {
        $sku_rs = $this->goods_sku_Model->where(array("goods_id" => $goodsId))->field("sum(quantity)quantity")->find();
        $this->goodsModel->where(array("goods_id" => $goodsId))
            ->save(array("goods_number" => $sku_rs['quantity']));
    }

    /**
     * 商品列表
     * @param  [array] $where 搜索条件
     * @param  [string] $field 搜索字段
     * @param  [string] $page 页码  0为第一
     * @param  [string] $per_page 每页数量
     * @param  [string] $order 排序规则
     * @return [type]        商品列表
     */
    private function _list($where,$field='goods_name,goods_id,uid,shop_price,goods_brief,goods_img,group_id',$page=0,$per_pag=20,$order='goods_id desc')
    {
        $goods = $this->sup_goods_model
        ->where($where)
        ->field($field)
        ->order($order)
        ->limit($page * $per_page, $per_page)
        ->select();
        foreach ($goods as $k => &$v) {
			$window_img = explode(',',$v['goods_img']);
            $v['window_img'] =  $window_img[0];
		}
        return  $goods;
    }

    /**
     * 商品详情
     * @param  [type] $goods_id [description]
     * @param  string $field    [description]
     * @return [type]           [description]
     */
    private function _details($goods_id,$field = 'goods_id,goods_name,goods_number,shop_price,uid,window_img,units_id,goods_brief,goods_img,group_id')
    {
    	$goods = $this->sup_goods_model->where('goods_id',$goods_id)->field($field)->find();
    	$goods_img = explode(',',$goods['goods_img']);
    	$goods['goods_img'] = $goods_img;
    	$goods['unit_name'] = $this->units->where('id',$goods['units_id'])->getField('unit_name');
    	return $goods;
    }

    # 获取供应商列表
    private function get_sup_list($where)
    {
        $data = $this->supplierModel
            ->where($where)
            ->field('mu_id,supplier_shortname,logo,send_min_price')
            ->select();
        return $data;
    }

    # 获取供应商信息
    private function get_sup_info($mu_id,$field='mu_id,supplier_shortname,logo,send_min_price')
    {
        $data = $this->supplierModel
            ->where(array('mu_id'=>$mu_id))
            ->field($field)
            ->find();
        return $data;
    }

    //排序规则
   	private function ss($key)
   	{  
	    return function ($a,$b) use ($key) {  
	        return $a[$key] > $b[$key];     //通过改变大于、小于来正向反向排序  
	    };  
	}

    /**
	 * 手机号码验证
     */
    private function check_phone($str)//手机号码正则表达试
    {
        return (preg_match("/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/",$str)||preg_match("/^\d{3}-\d{8}|\d{4}-\d{7}$/",$str))?true:false;
    }

    /**
     * [add_record 添加记录]
     * @param [type] $search_record [搜索词条]
     * @param [type] $type          [搜索类型]
     */
    private function add_record($search_record,$type,$sup_id)
    {
    	//查询记录是否存在
    	$uid = $this->get_agent_id();  //代理uid
    	$where = array('agent_id'=>$uid,'uid'=>$this->userId,'search_record'=>$search_record,'type'=>$type,'sup_id'=>$sup_id);
    	if($record = $this->record->where($where)->field('nums,status')->find()){
    		//存在
    		if ($record['status']==1) {
    			$this->record->where($where)->setInc('nums');
    		}else{
    			$record['nums']++;
    			$this->record->where($where)->save(array('status'=>1,'nums'=>$record['nums']));
    		}
    		
    	}else{
    		//不存在
    		$this->record->data($where)->add();
    	}
    }

    /**
     * [get_agent_id 获取代理商uid]
     * @param  [type] $agent_id [代理商id]
     * @return [type]           [代理商uid]
     */
    private function get_agent_id()
    {
    	$agent_id = $this->merchants_users_model->where('id',$this->userId)->getField('agent_id');  //代理 id
    	return $this->merchants_agent_model->where('id',$agent_id)->getField('uid');
    }

   	/**
   	 * [groups_list 商品分类列表]
   	 * @param  number $uid 代理商uid
   	 * @return array  分类列表
   	 */
   	private function groups_list($uid)
   	{
   		$where = array(
   			'trade'=>1,
   			'mid'=>$uid,
   			'gid'=>0
   			);
   		$field = 'group_id,group_name,mid,gid,sort';
   		$groups = $this->groupModel->where($where)->field($field)->select();
   		$openTwo = false;
   		foreach ($groups as $key => &$value) {
   			$map = array(
	   			'trade'=>1,
	   			'mid'=>$uid,
	   			'gid'=>$value['group_id']
	   			);
			if ($sub = $this->groupModel->where($map)->field($field)->select()) {
				$value['sub'] = $sub;
				$openTwo = true;
			}
		}
		if ($openTwo) {
			$type = array('type'=>2);
            $group['type']=2;
            $group['groups']=$groups; 
			return $group;
		}else{
			$type = array('type'=>1);
			// array_merge($groups,$type);
            $group['type']=1;
            $group['groups']=$groups; 
			return $group;
		}
	}

	/**
	 * [check_good 检查商品]
	 * @param  [type] $goods_id [商品id]
	 * @param  [type] $nums     [商品数量]
	 * @return [type] $goods    [商品信息]
	 */
	private function check_good($goods_id,$nums)
	{
        $field = 'goods_id,goods_name,goods_number,shop_price,uid,goods_img';
		$goods = $this->sup_goods_model->where('goods_id',$goods_id)->field($field)->find();
    	if ($goods['goods_number']<$nums) {
    		$this->ajaxReturn(array("code"=>'error',"msg"=>'商品库存不足'));
    	}
    	if ($goods['status']==2) {
    		$this->ajaxReturn(array("code"=>'error',"msg"=>'商品已下架'));
    	}
    	if ($nums<=0) {
    		$this->ajaxReturn(array("code"=>'error',"msg"=>'商品数量最小为1'));
    	}
    	if (!$goods) {
    		$this->ajaxReturn(array("code"=>'error',"msg"=>'未找到该商品'));
    	}
    	return $goods;
	}
}