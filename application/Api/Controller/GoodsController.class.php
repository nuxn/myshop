<?php
/**
 * 商品管理模块
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/4/14
 * Time: 11:02
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**商品管理接口
 * Class GoodsController
 * @package Api\Controller
 */
class GoodsController extends ApibaseController

{
    public $goodsModel;
    public $goods_group_model;
    public $goods_attr_model;
    public $category_model;
    public $goods_sku_Model;
    public $brand_model;
    public $posts;
    public $host;
    public $goods_desc;

    function _initialize()
    {
        parent::_initialize();
        $this->goods_desc = M("goods_desc_img");
        $this->goodsModel = M("goods");
        $this->goods_sku_Model = M("goods_sku");
        $this->category_model = M("category");
        $this->goods_group_model = M("goods_group");
        $this->goods_attr_model = M("goods_attr");
        $this->brand_model = M("brand");
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        $this->checkLogin();
        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);
    }

    /*public function ceshi(){
        $array = array();
        array_push($array,$value['group_id']);
        print_r($array);
    }*/
    /**
     * 商品列表,包含分组商品列表
     */
    public function goods_list()
    {
        $keywords = I('keywords');
        $group_id = I('group_id');
        $is_on_xcx = I('is_on_xcx','');
        $trade = I('trade',0);
        if ($group_id) {
            $res = $this->goods_group_model->where(array('mid' => $this->userId,'group_id'=>$group_id))->find();
            if($res['gid']==0){
                $group_ids = $this->goods_group_model->where(array('mid' => $this->userId,'gid'=>$group_id))->select();
                $array = array();
                foreach ($group_ids as $key => $value) {
                    // dump($value['group_id']);
                    // dump($array);
                    array_push($array,$value['group_id']);
                    // dump($array);
                }
                array_push($array,$group_id);
                $string = join(',',$array);
                $map['g.group_id'] = array('in',$string);
            }else{
                $map['g.group_id'] = $group_id;
            }
        }
        // dump($map);
        if ($keywords) $map['_string'] = '(b.brand_name like "%' . $keywords . '%")  OR ( g.goods_name like "%' . $keywords . '%") OR ( g.bar_code like "%' . $keywords . '%")';
        // if ($group_id) $map['g.group_id'] = $group_id;
        //$group_id&&($map['g.group_id'] = $group_id?:false);
        if($is_on_xcx)$map['g.is_on_xcx'] = $is_on_xcx;
        if($trade)$map['g.trade'] = $trade;
        $map['g.is_on_sale'] = 1;
        $map['g.is_delete'] = 0;
        $map['g.mid'] = $this->userId;
        $per_page = 20;//每页数量
        $page = I("page", "0");//页码,第几页
        $this->_lists($map, $page, $per_page,$trade);
    }

    public function goods_list1()
    {
        $keywords = I('keywords');
        $group_id = I('group_id');
        $is_on_xcx = I('is_on_xcx','');

        if ($keywords) $map['_string'] = '(b.brand_name like "%' . $keywords . '%")  OR ( g.goods_name like "%' . $keywords . '%") OR ( g.bar_code like "%' . $keywords . '%")';
        if ($group_id) $map['g.group_id'] = $group_id;
        //$group_id&&($map['g.group_id'] = $group_id?:false);
        if($is_on_xcx)$map['g.is_on_xcx'] = $is_on_xcx;

        $map['g.is_on_sale'] = 1;
        $map['g.is_delete'] = 0;
        $map['g.mid'] = $this->userId;
        $per_page = 100;//每页数量
        $page = I("page", "0");//页码,第几页
        $this->_lists($map, $page, $per_page);
    }
    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array(), $page, $per_page,$trade)
    {
        $this->goodsModel
            ->alias("g")
            ->where($where);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
        $count = $this->goodsModel->count();

        $total = ceil($count / $per_page);//总页数

        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("g.goods_id DESC");
        $field = 'g.goods_id,g.goods_img1,g.goods_img2,g.goods_img3,g.goods_img4,g.goods_img5,g.pic_desc1,g.pic_desc2,g.pic_desc3,g.goods_name,g.shop_price,g.goods_number,g.sales,g.is_on_xcx,g.group_id,g.window_img';
        $this->goodsModel->field($field);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
        $data_lists = $this->goodsModel->select();
        // echo $this->goodsModel->_sql();
        foreach ($data_lists as $k => $v) {
            $this->checkHttp($v['goods_img1']);
            if ($data_lists[$k]['goods_img1']) $data_lists[$k]['goods_img1'] = $this->host . $v['goods_img1'];
            $this->checkHttp($v['goods_img2']);
            if ($data_lists[$k]['goods_img2']) $data_lists[$k]['goods_img2'] = $this->host . $v['goods_img2'];
            $this->checkHttp($v['goods_img3']);
            if ($data_lists[$k]['goods_img3']) $data_lists[$k]['goods_img3'] = $this->host . $v['goods_img3'];
            $this->checkHttp($v['goods_img4']);
            if ($data_lists[$k]['goods_img4']) $data_lists[$k]['goods_img4'] = $this->host . $v['goods_img4'];
            $this->checkHttp($v['goods_img5']);
            if ($data_lists[$k]['goods_img5']) $data_lists[$k]['goods_img5'] = $this->host . $v['goods_img5'];
            $this->checkHttp($v['pic_desc1']);
            if ($data_lists[$k]['pic_desc1']) $data_lists[$k]['pic_desc1'] = $this->host . $v['pic_desc1'];
            $this->checkHttp($v['pic_desc2']);
            if ($data_lists[$k]['pic_desc2']) $data_lists[$k]['pic_desc2'] = $this->host . $v['pic_desc2'];
            $this->checkHttp($v['pic_desc3']);
            if ($data_lists[$k]['pic_desc3']) $data_lists[$k]['pic_desc3'] = $this->host . $v['pic_desc3'];
            if (!$data_lists[$k]['goods_number']) $data_lists[$k]['goods_number'] = $this->get_goods_number($v['goods_id']);
            if (!floatval($data_lists[$k]['shop_price'])) $data_lists[$k]['shop_price'] = $this->get_buy_price($v['goods_id']);
        }
        $where2['mid'] = $this->userId;
        if($trade)$where2['trade'] = $trade;
        $group_list = $this->goods_group_model->where($where2)->order(array('add_time' => 'DESC'))->field("group_id,group_name")->select();
        $this->ajaxReturn(array(
            "code" => "success",
            "msg" => "成功",
            "data" => array(
                "total" => $total,
                "count" => $count,
                "data" => $data_lists,
                "group_data" => $group_list
            )
        ));
    }

    public function checkHttp($str)
    {
        if(strpos($str,'http') !== false){
            $this->host = '';
        } else {
            $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        }
    }

    /**
     * 商品类目
     */
    public function category()
    {
        $data = $this->category_model->field("cat_id,cat_name,parent_id")->select();
        $mainCategory = array();
        $childCategory = array();
        foreach ($data as $key => $value) {
            $value['parent_id'] == 0 ? $mainCategory[] = $value : $childCategory[] = $value;
        }

        foreach ($mainCategory as $_key => $_value) {
            foreach ($childCategory as $_k => $_v) {
                if ($_value['cat_id'] == $_v['parent_id']) {
                    $mainCategory[$_key]['child'][] = $_v;

                }
            }

        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $mainCategory));
    }

    /**
     * 所有分组
     */
    public function group_list()
    {
        $trade = I('trade',0);  //行业
        $mid = $this->userId;
        if($this->userInfo['role_id']==77){
            $mid = M('merchants_users')->where(array('id'=>$this->userInfo['uid']))->getField('agent_id');
        }
        $where = array(
            'mid'=>$mid,
             "is_on_sale" => 1, 
             "is_delete" => 0
             );
        if ($trade) {
            $where['trade'] = $trade;
        }
        //$data = $this->goods_group_model->where(array('mid' => $this->userId,'trade'=>$trade))->order(array('add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
        $data = $this->goods_group_model->where(array('mid' => $mid,'trade'=>$trade))->order(array('add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
        foreach ($data as $k => $v) {
            $where['group_id'] = $v['group_id'];
            if ($v['gid']==0) {
                //$res = $this->goods_group_model->where(array('mid' => $this->userId,'gid'=>$v['group_id'],'trade'=>$trade))->select();
                $res = $this->goods_group_model->where(array('mid' => $mid,'gid'=>$v['group_id'],'trade'=>$trade))->select();
                $counts=0;

                if (!$res) {
                    $c = $this->goodsModel->where($where)->count();
                    $data[$k]['goods_number'] =$c;
                }else{
                   foreach ($res as $key => $value) {
                        $where['group_id'] = $value['group_id'];
                        $count = $this->goodsModel->where($where)->count();
                        $counts+=$count;
                    }
                    $data[$k]['goods_number'] =$counts;
                }


            }else{
                $data[$k]['goods_number'] = $this->goodsModel->where($where)->count();
            }
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }


    /**
     * 分组编辑
     */
    public function edit_group()
    {
        $group_id = I("group_id", 0);
        $group_name = I("group_name");
        $group_id_arr = explode(",", $group_id);
        $group_name_arr = explode(",", $group_name);
        foreach ($group_id_arr as $k => $v) {
            if (!$v) $this->ajaxReturn(array("code" => "error", "msg" => "分组编号不能为空"));
            $name = $group_name_arr[$k];
            if (!$name) $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能为空"));
            if (mb_strlen($name, 'UTF-8') > 5) $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能超过五个字"));
            if ($group_id=$this->goods_group_model->where(array("group_name" => $name, "group_id" => array('neq', $v),'mid'=>$this->userId))->getField("group_id")){
                $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能重复"));
            }
            $this->goods_group_model->where(array('group_id' => $v))->save(array("group_name" => $name));
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }

    /**
     * 1.6.2 分组编辑
     */
    public function edit_groups()
    {
        ($group_id = I("group_id"))||$this->ajaxReturn(array("code" => "error", "msg" => "分组编号不能为空"));
        ($group_name = I("group_name"))||$this->ajaxReturn(array("code"=>"error","msg"=>"分组名称不能为空"));
        if (mb_strlen($group_name, 'UTF-8') > 5) $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能超过五个字"));
        $sort = I('sort',0);    //分组排序  默认为0
        $gid = I('gid',0);  //上级分组id  默认为0  0是一级分组
        if ($this->goods_group_model->where(array("group_name" => $group_name, "group_id" => array('neq', $group_id),'mid'=>$this->userId))->find()){
            // echo $this->goods_group_model->getLastSql();
                $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能重复"));
        }
        //判断几级分组
        if ($this->goods_group_model->where(array("mid" => $this->userId,"group_id" => $group_id, "gid" => 0))->find()) {
            //是一级分组
            if($this->goods_group_model->where(array("mid" => $this->userId,"gid" => $group_id))->find()&&$gid!=0){
                $this->ajaxReturn(array("code" => "error", "msg" => "该分组存在二级分组，不能修改"));
            }
        }
        if ($this->goods_group_model->where(array("mid" => $this->userId,'group_id' => $group_id))->save(array("group_name" => $group_name,"sort"=>$sort,"gid"=>$gid))) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
        }
        
    }

    /**
     * 添加分组
     */
    public function add_group()
    {
        $data['group_name'] = I('group_name');
        $data['mid'] = $this->userId;
        $data['add_time'] = time();
        $is_name = $this->goods_group_model->where(array("group_name" => $data['group_name'], "mid" => $this->userId))->find();
        if ($is_name) $this->ajaxReturn(array("code" => "error", "msg" => "店铺商品分组名已存在"));

        $result = $this->goods_group_model->add($data);
        if ($result) $this->ajaxReturn(array("code" => "success", "msg" => "新增商品分组成功", "data" => array("group_id" => $result)));
        else $this->ajaxReturn(array("code" => "error", "msg" => "新增商品分组失败"));

    }

    /**
     * 1.6.2 添加分组
     */
    public function add_groups()
    {
        ($data['group_name'] = I('group_name'))||$this->ajaxReturn(array('code'=>'error','分组名称不能为空'));
        $data['sort'] = I('sort',0);    //分组排序  默认为0
        $data['gid'] = I('gid',0);  //上级分组id  默认为0  0是一级分组
        $data['mid'] = $this->userId;
        $data['add_time'] = time();
        $data['trade'] = I('trade');
        $is_name = $this->goods_group_model->where(array("group_name" => $data['group_name'], "mid" => $this->userId,'gid'=>$data['gid'],"trade"=>$data['trade']))->find();
        if ($is_name) $this->ajaxReturn(array("code" => "error", "msg" => "店铺商品分组名已存在"));
        $result = $this->goods_group_model->data($data)->add();
        // echo $this->goods_group_model->getLastSql();
        if ($result) {
            //检测上级分组是否有商品  更改分组商品
            if ($data['gid']!=0) {
                $goods = $this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$data['gid'], "is_on_sale" => 1, "is_delete" => 0))->select();
                if($goods){
                    $group_id = $this->goods_group_model->where(array("mid" => $this->userId,'gid'=>$data['gid'],"trade"=>$data['trade']))->getField("group_id");
                    $result = $this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$data['gid'], "is_on_sale" => 1, "is_delete" => 0))->save(array("group_id" => $group_id));
                }
            }
            
            $this->ajaxReturn(array("code" => "success", "msg" => "新增商品分组成功", "data" => array("group_id" => $result)));
        }else {
            $this->ajaxReturn(array("code" => "error", "msg" => "新增商品分组失败"));
        }

    }

    /**
     * 删除分组
     * id 可单个或多个，多个用,分开 如 2,4,5,7
     */
    public function del_group()
    {
        $map['mid'] = $this->userId;
        $group_id = I("group_id");
        if (!$group_id) $this->ajaxReturn(array("code" => "error", "msg" => "删除商品id为空"));
        $map['group_id'] = array('in', $group_id);
        if(I('trade'))$map['trade'] = I('trade');
        $group_id = explode(",",$group_id);
        // dump($group_id);
        foreach ($group_id as $key => $value) {
            //判断几级分组
            if ($this->goods_group_model->where(array("mid" => $this->userId,"group_id" => $value, "gid" => 0,'trade'=>$map['trade']))->find()) {
                //是一级分组
                if($this->goods_group_model->where(array("mid" => $this->userId,"gid" => $value,'trade'=>$map['trade']))->find()){
                    $this->ajaxReturn(array("code" => "error", "msg" => "该分类存在二级分组，不能删除"));
                }
                $res = $this->goods_group_model->where(array("mid" => $this->userId,"gid" => $value,'trade'=>$map['trade']))->select(); 
                foreach ($res as $k=> $v) {
                    if($this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$v['group_id'], "is_on_sale" => 1, "is_delete" => 0,'trade'=>$map['trade']))->select() ){
                        $this->ajaxReturn(array("code" => "error", "msg" => "该分类存在商品不能删除"));
                    }
                }
            }else{
                if($this->goodsModel->where(array('mid'=>$this->userId,'group_id'=>$value, "is_on_sale" => 1, "is_delete" => 0,'trade'=>$map['trade']))->select()){
                    // echo $this->goodsModel->getLastSql();
                    $this->ajaxReturn(array("code" => "error", "msg" => "该分类存在商品不能删除"));
                }
            }
        }
        $result = $this->goods_group_model->where($map)->delete();
        if ($result) {
            $this->group_list();
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "删除商品分类失败"));
        }

    }

    /**
     * 编辑分组商品
     * id 可单个或多个，多个用,分开 如 2,4,5,7
     */
    public function edit_group_goods()
    {
        $map['mid'] = $this->userId;
        $group_id = I("group_id");
        $goods_ids = I("goods_id");
        if (!$group_id || !$goods_ids) $this->ajaxReturn(array("code" => "error", "msg" => "商品id或分组id为空"));
        $map['goods_id'] = array('in', $goods_ids);
        $result = $this->goodsModel->where($map)->save(array("group_id" => $group_id));
        if ($result) {
            $this->ajaxReturn(array("code" => "success", "msg" => "更改商品分组成功"));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "更改商品分组失败"));
        }

    }

    /**
     * 获取所有商品规格类型
     */
    public function attr()
    {
        $condition['mid'] = array('IN', (array($this->userId, '0')));
        $data = $this->goods_attr_model->where($condition)->field('a_id,a_name')->select();
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    /**
     * 添加商品规格类型
     */
    public function add_attr()
    {
        if (I('a_name')) {
            $a_name = I('a_name');
            $data['a_name'] = $a_name;
            $data['mid'] = $this->userId;
            $data['cat_id'] = 0;
            $data['add_time'] = time();
            if ($this->goods_attr_model->where(array("a_name" => $a_name, "mid" => array('IN', (array($this->userId, '0')))))->getField("a_id")) $this->ajaxReturn(array("code" => "error", "msg" => "规格名称不能重复"));
            $res = $this->goods_attr_model->add($data);
            if ($res) {
                $this->ajaxReturn(array("code" => "success", "msg" => "新增规格成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "新增规格失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "规格名称为空"));
        }

    }

    /**
     * 扫码返回商品信息
     */
    public function barcode()
    {
        $barcode = I('barcode');
        $swith = true;
        if ($swith) {
            if (!$barcode) err('商品条码不能为空');
            $goods_id = $this->goodsModel->where(array("bar_code" => $barcode, 'mid' => $this->userId))->getField("goods_id");
            if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品库无该商品"));
            $this->goods_info($goods_id);
        } else {
            $host = "https://ali-barcode.showapi.com";
            $path = "/barcode";
            $method = "GET";
            $appcode = "b9702950a2cc48dda3d5e19faa4d1377";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "code=6926032353065";
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);
            var_dump($res);
            $res = json_decode($res, true);
            $res['status'] = $res['status'] == 0 ? 1 : $res['status'];
            $this->ajaxReturn($res);

            /*
            $host = "http://jisutxmcx.market.alicloudapi.com";
            $path = "/barcode2/query";
            $method = "GET";
            $appcode = "b9702950a2cc48dda3d5e19faa4d1377";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "barcode=" . $barcode;
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);
            var_dump($res);
            $res = json_decode($res, true);
            $res['status'] = $res['status'] == 0 ? 1 : $res['status'];
            $this->ajaxReturn($res);*/
        }

    }

    public function barcode_ceshi()
    {
        $barcode = I('barcode');
        $this->userId = 181;
        $swith = false;
        if ($swith) {
            if (!$barcode) err('商品条码不能为空');
            $goods_id = $this->goodsModel->where(array("bar_code" => $barcode, 'mid' => $this->userId))->getField("goods_id");
            dump($this->goodsModel->getLastSql());
            dump($goods_id);die;
            if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品库无该商品"));
            $this->goods_info($goods_id);
        } else {

            $host = "https://ali-barcode.showapi.com";
            $path = "/barcode";
            $method = "GET";
            $appcode = "b9702950a2cc48dda3d5e19faa4d1377";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "code=$barcode";
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);

            // var_dump($res);die;
            $res = json_decode($res, true);
            $res['status'] = $res['status'] == 0 ? 1 : $res['status'];
            // dump($res);die;
            $this->ajaxReturn($res);

            /*
            $host = "http://jisutxmcx.market.alicloudapi.com";
            $path = "/barcode2/query";
            $method = "GET";
            $appcode = "b9702950a2cc48dda3d5e19faa4d1377";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "barcode=" . $barcode;
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);
            var_dump($res);
            $res = json_decode($res, true);
            $res['status'] = $res['status'] == 0 ? 1 : $res['status'];
            $this->ajaxReturn($res);*/
        }

    }

    /**
     *添加商品
     */
    public function add_goods()
    {
        if (IS_POST) {
            $this->posts = I("");
            $properties_arr = array();
            $quantity_arr = array();
            $price_arr = array();
            $cost_arr = array();
            if (!$this->posts['goods_name']) $this->ajaxReturn(array("code" => "error", "msg" => "商品名称不能为空!"));
            if (!$this->posts['cat_id']) $this->ajaxReturn(array("code" => "error", "msg" => "分类不能为空!"));
            if (!$this->posts['brand_name']) $this->ajaxReturn(array("code" => "error", "msg" => "品牌不能为空!"));

            $this->posts['brand_id'] = $this->get_brand_id($this->posts['brand_name']);
            if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));
            $this->posts['freight'] = floatval($this->posts['freight']);//运费
            if (!$this->posts['properties']) {
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
                $this->posts['buy_price'] = floatval($this->posts['buy_price']);
                $this->posts['shop_price'] = floatval($this->posts['shop_price']);
                $this->posts['goods_number'] = intval($this->posts['goods_number']);
            } else {
                $this->posts['is_sku'] = '1';
                $properties = I('properties');//规格
                $quantity = I('quantity');//数量
                $price = I('price');//价格
                $cost = I('cost');//成本
                $properties_arr = explode(',', $properties);
                $quantity_arr = explode(',', $quantity);
                $price_arr = explode(',', $price);
                $cost_arr = explode(',', $cost);
                if ((count($properties_arr) + count($quantity_arr) + count($price_arr) + count($cost_arr)) / 4 != count($properties_arr)) {
                    //$this->ajaxReturn(array("code" => "error", "msg" => "参数错误!"));
                }
            }

            $this->posts['mid'] = $this->userId;
            $this->posts['add_time'] = time();
            $this->posts['is_on_sale'] = I('is_on_sale', '1');
            $this->posts['is_on_xcx'] = I('is_on_xcx', '2');
            if ($this->goodsModel->where(array("goods_name" => $this->posts['goods_name'], 'mid' => $this->userId, 'is_delete' => 0))->getField("goods_id")) {
                $this->ajaxReturn(array("code" => "error", "msg" => "不能重复添加!"));
            }
            if ($this->goodsModel->create()) {

                $this->upload_pic();
                $goodsId = $this->goodsModel->add($this->posts);
                $desc_arr = array(
                    0 => array('goods_id' => $goodsId,'url' => $this->posts['pic_desc1']),
                    1 => array('goods_id' => $goodsId,'url' => $this->posts['pic_desc2']),
                    2 => array('goods_id' => $goodsId,'url' => $this->posts['pic_desc3']),
                );
                M('goods_desc_img')->add($desc_arr);
                if ($goodsId) {
                    if ($this->posts['spc']) $this->add_goods_value($this->posts['spc'], $goodsId, 'add');
                    if ($this->posts['properties']) {
                        $sku_arr['goods_id'] = $goodsId;
                        foreach ($properties_arr as $key => $value) {
                            $sku_arr['properties'] = $value;
                            $sku_arr['quantity'] = $quantity_arr[$key];
                            $sku_arr['price'] = $price_arr[$key];
                            $sku_arr['cost'] = $cost_arr[$key];
                            $sku_arr['properties_name'] = $value;
                            $sku_arr['status'] = '1';
                            $sku_arr['add_time'] = time();
                            $this->goods_sku_Model->add($sku_arr);
                        }
                        //更新商品表库存、进价、售价
                        $this->update_other_info($goodsId);
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功!"));

                }
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败!"));

            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败!"));
            }


        }
    }

    /**根据商品品牌名称获取品牌ID
     * @param $brand_name
     * @return mixed
     */
    private function get_brand_id($brand_name)
    {
        $brand_id = $this->brand_model->where(array("brand_name" => $brand_name))->getField("brand_id");
        if (!$brand_id) {
            $brand_id = $this->brand_model->add(array("brand_name" => $brand_name));
        }
        return $brand_id;
    }

    /**
     * 编辑商品
     */
    public function edit_goods()
    {

        if (IS_POST) {
            $this->posts = I("");
            $goodsId = $this->posts['goods_id'];
            $properties_arr = array();
            $quantity_arr = array();
            $price_arr = array();
            $cost_arr = array();
            if (!$this->posts['goods_name']) $this->ajaxReturn(array("code" => "error", "msg" => "商品名称不能为空!"));
            if (!$this->posts['cat_id']) $this->ajaxReturn(array("code" => "error", "msg" => "分类不能为空!"));
            if (!$this->posts['brand_name']) $this->ajaxReturn(array("code" => "error", "msg" => "品牌不能为空!"));
            if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));

            $this->posts['brand_id'] = $this->get_brand_id($this->posts['brand_name']);
            $this->posts['freight'] = floatval($this->posts['freight']);//运费
            if (!$this->posts['properties']) {//没有填商品规格
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
                $this->posts['buy_price'] = floatval($this->posts['buy_price']);
                $this->posts['shop_price'] = floatval($this->posts['shop_price']);
                $this->posts['goods_number'] = intval($this->posts['goods_number']);
            } else {
                $this->posts['is_sku'] = '1';
                $properties = I('properties');//规格
                $quantity = I('quantity');//数量
                $price = I('price');//价格
                $cost = I('cost');//成本
                $properties_arr = explode(',', $properties);
                $quantity_arr = explode(',', $quantity);
                $price_arr = explode(',', $price);
                $cost_arr = explode(',', $cost);
                if ((count($properties_arr) + count($quantity_arr) + count($price_arr) + count($cost_arr)) / 4 != count($properties_arr)) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "参数错误!"));
                }
            }
            $this->posts['mid'] = $this->userId;

            if ($this->goodsModel->create()) {

                $this->upload_pic();
                $res = $this->goodsModel->where(array('goods_id' => $goodsId))->save($this->posts);

                if ($res) {
                    $this->goods_sku_Model->where(array('goods_id' => $goodsId))->delete();
                    if ($this->posts['spc']) $this->add_goods_value($this->posts['spc'], $goodsId, 'edit');
                    $sku_arr['goods_id'] = $goodsId;

                    if ($this->posts['properties']) {
                        $sku_arr['goods_id'] = $goodsId;
                        foreach ($properties_arr as $key => $value) {
                            $sku_arr['properties'] = $value;
                            $sku_arr['quantity'] = $quantity_arr[$key];
                            $sku_arr['price'] = $price_arr[$key];
                            $sku_arr['cost'] = $cost_arr[$key];
                            $sku_arr['properties_name'] = $value;
                            $sku_arr['status'] = '1';
                            $sku_arr['add_time'] = time();
                            $this->goods_sku_Model->add($sku_arr);
                        }
                        //更新商品表库存、进价、售价
                        $this->update_other_info($goodsId);

                    }


                }
                $this->ajaxReturn(array("code" => "success", "msg" => "修改成功!"));

            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "修改失败!"));
            }


        }

    }

    /**
     * 商品图片上传编辑
     */
    private function upload_pic()
    {
        $info = array();//存储图片
        $pic_root_path = C('_WEB_UPLOAD_');
        if ($_FILES) {
            $upload = new \Think\Upload();
            $upload->maxSize = 0;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'goods/';
            $upload->saveName = uniqid;//保持文件名不变

            $info = $upload->upload();
            if (!$info['goods_img1'] && !$info['goods_img2'] && !$info['goods_img3'] && !$info['goods_img4'] && !$info['goods_img5']) {
                // $this->ajaxReturn(array("code" => "error", "msg" => "请至少上传1张商品图片!"));
            }
            // if (!$info) $this->error($upload->getError());
        }

        $this->posts['goods_img1'] = $info['goods_img1'] ? $pic_root_path . $info['goods_img1']['savepath'] . $info['goods_img1']['savename'] : '';
        $this->posts['goods_img2'] = $info['goods_img2'] ? $pic_root_path . $info['goods_img2']['savepath'] . $info['goods_img2']['savename'] : '';
        $this->posts['goods_img3'] = $info['goods_img3'] ? $pic_root_path . $info['goods_img3']['savepath'] . $info['goods_img3']['savename'] : '';
        $this->posts['goods_img4'] = $info['goods_img4'] ? $pic_root_path . $info['goods_img4']['savepath'] . $info['goods_img4']['savename'] : '';
        $this->posts['goods_img5'] = $info['goods_img5'] ? $pic_root_path . $info['goods_img5']['savepath'] . $info['goods_img5']['savename'] : '';
        $this->posts['pic_desc1'] = $info['pic_desc1'] ? $pic_root_path . $info['pic_desc1']['savepath'] . $info['pic_desc1']['savename'] : '';
        $this->posts['pic_desc2'] = $info['pic_desc2'] ? $pic_root_path . $info['pic_desc2']['savepath'] . $info['pic_desc2']['savename'] : '';
        $this->posts['pic_desc3'] = $info['pic_desc3'] ? $pic_root_path . $info['pic_desc3']['savepath'] . $info['pic_desc3']['savename'] : '';
    }



    /**
     * 商品详情
     */
    public function goods_info($goods_id=0)
    {
        $goods_id = $goods_id>0?$goods_id:I('goods_id');
        $where['goods_id'] = $goods_id;
        $where['is_delete'] = 0;
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->order("g.goods_id DESC");

        $this->goodsModel->field('g.goods_id,g.goods_img1,g.goods_img2,g.goods_img3,g.goods_img4,g.goods_img5,g.goods_name,g.shop_price,g.goods_number,g.buy_price,g.sales,g.bar_code,g.pic_desc1,g.pic_desc2 ,g.pic_desc3 ,g.goods_desc1,g.goods_desc2,g.goods_desc3, gg.group_name, gg.group_id,b.brand_name,g.freight');
        $this->goodsModel->join("LEFT JOIN __GOODS_GROUP__ gg ON g.group_id = gg.group_id");
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");

        $res = $this->goodsModel->find();
        $res['specifications'] = $this->get_goods_sku($goods_id);
        $this->checkHttp($res['goods_img1']);
        if ($res['goods_img1']) $res['goods_img1'] = $this->host . $res['goods_img1'];
        $this->checkHttp($res['goods_img2']);
        if ($res['goods_img2']) $res['goods_img2'] = $this->host . $res['goods_img2'];
        $this->checkHttp($res['goods_img3']);
        if ($res['goods_img3']) $res['goods_img3'] = $this->host . $res['goods_img3'];
        $this->checkHttp($res['goods_img4']);
        if ($res['goods_img4']) $res['goods_img4'] = $this->host . $res['goods_img4'];
        $this->checkHttp($res['goods_img5']);
        if ($res['goods_img5']) $res['goods_img5'] = $this->host . $res['goods_img5'];
        $this->checkHttp($res['pic_desc1']);
        if ($res['pic_desc1']) $res['pic_desc1'] = $this->host . $res['pic_desc1'];
        $this->checkHttp($res['pic_desc2']);
        if ($res['pic_desc2']) $res['pic_desc2'] = $this->host . $res['pic_desc2'];
        $this->checkHttp($res['pic_desc3']);
        if ($res['pic_desc3']) $res['pic_desc3'] = $this->host . $res['pic_desc3'];
        if (!$res['goods_number']) $res['goods_number'] = $this->get_goods_number($res['goods_id']);
        if (!floatval($res['buy_price'])) $res['buy_price'] = $this->get_buy_price($res['goods_id']);
        $res['spec'] = $this->get_goods_value($goods_id);

        //$res['cat_name'] = $this->category_model->where(array("cat_id" => $res['cat_id']))->getField("cat_name");
        $cat_info = $this->category_model->where(array("cat_id" => $res['cat_id']))->field("cat_name,parent_id")->find();
        if ($cat_info['parent_id'] != "0") {
            $cat_name = $this->category_model->where(array("cat_id" => $cat_info['parent_id']))->getField("cat_name");
            $res['cat_name'] = $cat_name . ">" . $cat_info['cat_name'];
        } else
            $res['cat_name'] = $cat_info['cat_name'] ? $cat_info['cat_name'] : "";
        foreach ($res as $k => $v) if (!$v && !is_array($v)) $res[$k] = '';
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
//        $score_num = M('member', C("DB_PREFIX_2"), C("DB_NAME_2"))->where(array("usrid" => 288))->find();

    }


    /**获取单个商品sku
     * @param int $goods_id
     * @return mixed
     */
    public function get_goods_sku($goods_id = 0)
    {
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $sku_list = $this->goods_sku_Model->where(array("goods_id" => $goods_id))->field("properties,quantity,price,cost,original_price")->select();
        return $sku_list;

    }


    /**
     * 商品删除
     * @param goods_id
     */
    public function del_goods()
    {
        $goods_id = I('goods_id');
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空"));
        $this->goodsModel->where(array("goods_id" => $goods_id))->save(array("is_delete" => "1"));
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }


    /**添加商品规格内容
     * @param string $spc
     * @param int $goods_id
     */
    private function add_goods_value($spc = '1:k+l,2:ff+kk,3:66+66', $goods_id, $tag)
    {
        $goods_value_model = M("goods_value");
        if ($tag != 'add' && $goods_id) $goods_value_model->where(array("goods_id" => $goods_id))->delete();
        $spc_arr = explode(",", $spc);
        foreach ($spc_arr as $k => $v) {
            $value = explode(":", $v);
            $a_id = $value[0];
            $a_name_arr = explode("+", $value[1]);
            foreach ($a_name_arr as $k1 => $v1) {
                $goods_value_model->add(array("v_name" => $v1, "a_id" => $a_id, "goods_id" => $goods_id, "add_time" => time()));
            }

        }

    }


    /**获取商品规格名称及规格内容
     * @param $goods_id
     * @return array
     */
    private function get_goods_value($goods_id)
    {
        $goods_value_model = M("goods_value");
        $goods_attr_model = M("goods_attr");
        $res = $goods_value_model->where(array("goods_id" => $goods_id))->select();

        $a = array();
        $b = array();
        foreach ($res as $k => $v) {
            $a[$v['a_id']][] = $v['v_name'];
        }

        foreach ($a as $k1 => $v1) {
            $b[$k1]['value'] = $v1;
            $b[$k1]['name'] = $goods_attr_model->where(array("a_id" => $k1))->getField("a_name");
        }
        sort($b);
        return $b;

    }


    /**返回商品库存
     * @param $goods_id
     * @return mixed
     */
    private function get_goods_number($goods_id)
    {
        $rs = $this->goods_sku_Model->where(array("goods_id" => $goods_id))->getField("sum(quantity)");
        if ($rs) return $rs;
        else return '0';
    }


    /**返回商品进价
     * @param $goods_id
     * @return mixed
     */
    private function get_buy_price($goods_id)
    {
        $rs = $this->goods_sku_Model->where(array("goods_id" => $goods_id))->getField("min(price)");
        if ($rs) return $rs;
        else return '0.00';
    }


    /**
     * 更新商品表库存、进价、售价
     * @param $goodsId
     */
    private function update_other_info($goodsId)
    {
        $sku_rs = $this->goods_sku_Model->where(array("goods_id" => $goodsId))->field("min(original_price)original_price,min(cost)cost,min(price)price,sum(quantity)quantity")->find();
        $this->goodsModel->where(array("goods_id" => $goodsId))->save(array("goods_number" => $sku_rs['quantity'], "buy_price" => $sku_rs['cost'], "shop_price" => $sku_rs['price'],"original_price" => $sku_rs['original_price']));
    }


    /**检查条码格式
     * @param $barcode
     * @return bool
     */
    private function check_barcode($barcode = 0)
    {
        // if (mb_substr($barcode, 0, 1, 'utf-8') != '6' || mb_strlen($barcode) < 11 || !preg_match("/^[0-9]*$/", $barcode)) return false;
        if (mb_strlen($barcode) < 6) return false;
        else return true;
    }


    /**
     * 上下架小程序商城
     */
    public function put_xcx()
    {
        $res = M('merchants')->where(array('uid' => $this->userId))->field('end_time,is_miniapp')->find();
        if ($res['end_time'] == '0' || $res['is_miniapp'] < 2) err('未开通小程序');
        else if ($res['end_time'] < time()) err('小程序已过期');
        ($is_on_xcx = I('is_on_xcx', 0, 'intval')) || err('上下架参数错误');
        ($goodsId = I('goods_id', 0, 'intval')) || err('商品ID不能为空');
        $this->goodsModel->where(array("goods_id" => $goodsId))->save(array("is_on_xcx" => $is_on_xcx));
        $is_on_xcx > 1 ? succ(array(), '下架成功') : succ(array(), '上架成功');
    }

     /**
      * 1.3.5版本
     * 商品详情(点餐小程序)
     * @return [type] [description]
     */
    public function detail($goods_id)
    {
        
        $goods_id = $goods_id>0?$goods_id:I('goods_id');
        $where['goods_id'] = $goods_id;
        $where['is_delete'] = 0;
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->order("g.goods_id DESC");
        $this->goodsModel->field('g.goods_id,g.goods_name,g.shop_price,g.goods_number,g.buy_price,g.sales,g.bar_code, gg.group_name, gg.group_id,g.freight,g.goods_brief,g.star,g.is_on_xcx,g.original_price,g.is_hot');
        $this->goodsModel->join("LEFT JOIN __GOODS_GROUP__ gg ON g.group_id = gg.group_id");

        $res = $this->goodsModel->find();
        $res['specifications'] = $this->get_goods_sku($goods_id);
        $result = $this->get_goods_img($goods_id);
        if ($result) {
            $res['goods_img'] =$result['goods_img'];
        }else{
            $res['goods_img'] = array();
        }
        
        
        if ($result['pic_desc']) {
            $res['pic_desc'] =$result['pic_desc'];
        }else{
            $res['pic_desc'] =array();
        }
        $res['goods_desc'] =array();
        $this->checkHttp($res['pic_desc1']);
        if ($res['pic_desc1']) $res['pic_desc1'] = $this->host . $res['pic_desc1'];
        $this->checkHttp($res['pic_desc2']);
        if ($res['pic_desc2']) $res['pic_desc2'] = $this->host . $res['pic_desc2'];
        $this->checkHttp($res['pic_desc3']);
        if ($res['pic_desc3']) $res['pic_desc3'] = $this->host . $res['pic_desc3'];
        if (!$res['goods_number']) $res['goods_number'] = $this->get_goods_number($res['goods_id']);
        if (!floatval($res['buy_price'])) $res['buy_price'] = $this->get_buy_price($res['goods_id']);
        
        $cat_info = $this->category_model->where(array("cat_id" => $res['cat_id']))->field("cat_name,parent_id")->find();
        if ($cat_info['parent_id'] != "0") {
            $cat_name = $this->category_model->where(array("cat_id" => $cat_info['parent_id']))->getField("cat_name");
            $res['cat_name'] = $cat_name . ">" . $cat_info['cat_name'];
        } else
            $res['cat_name'] = $cat_info['cat_name'] ? $cat_info['cat_name'] : "";
        foreach ($res as $k => $v) if (!$v && !is_array($v)) $res[$k] = '';
        // dump($res);die;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
    }

    //获取商品图片数组
    public function get_goods_img($goods_id)
    {
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $goods_img = $this->goodsModel->where(array("goods_id" => $goods_id))->field("goods_img1,goods_img2,goods_img3,goods_img4,goods_img5,pic_desc1,pic_desc2,pic_desc3")->find();
        // dump($goods_img);die;
        if ($goods_img['goods_img1'] != false) {
            $this->checkHttp($goods_img['goods_img1']);
            $res['goods_img'][] = $this->host . $goods_img['goods_img1'];
        }
        if ($goods_img['goods_img2'] != false) {
            $this->checkHttp($goods_img['goods_img2']);
            $res['goods_img'][] = $this->host . $goods_img['goods_img2'];
        }
        if ($goods_img['goods_img3'] != false) {
            $this->checkHttp($goods_img['goods_img3']);
            $res['goods_img'][] = $this->host . $goods_img['goods_img3'];
        }
        if ($goods_img['goods_img4'] != false) {
            $this->checkHttp($goods_img['goods_img4']);
            $res['goods_img'][] = $this->host . $goods_img['goods_img4'];
        }
        if ($goods_img['goods_img5'] != false) {
            $this->checkHttp($goods_img['goods_img5']);
            $res['goods_img'][] = $this->host . $goods_img['goods_img5'];
        }
        if ($goods_img['pic_desc1'] != false) {
            $this->checkHttp($goods_img['pic_desc1']);
            $res['pic_desc'][] = $this->host . $goods_img['pic_desc1'];
        }
        if ($goods_img['pic_desc2'] != false) {
            $this->checkHttp($goods_img['pic_desc2']);
            $res['pic_desc'][] = $this->host . $goods_img['pic_desc2'];
        }
        if ($goods_img['pic_desc3'] != false) {
            $this->checkHttp($goods_img['pic_desc3']);
            $res['pic_desc'][] = $this->host . $goods_img['pic_desc3'];
        }
        // dump($res);die;
        return $res;   
    }

    //下架商品
    public function sold_out()
    {
        $map['goods_id'] = intval(I('id'));
        if (!$map['goods_id']) {
        	$this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空"));
        }
        // dump($map);die;
        $result = $this->goodsModel->where($map)->save(array('is_on_xcx'=>'2','put_xcx'=>0,'put_pos'=>0,'put_two'=>0));
        if ($result == false) {
            $this->ajaxReturn(array("code" => "error", "msg" => "下架失败！"));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "下架成功！"));
        }   
    }

    //上架商品
    public function put_away()
    {
        $map['goods_id'] = intval(I('id'));
        if (!$map['goods_id']) {
        	$this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空"));
        }

        $result = $this->goodsModel->where($map)->save(array('is_on_xcx'=>'1','put_xcx'=>2,'put_pos'=>2,'put_two'=>2));
        // dump($this->goodsModel->getLastSql());
        if ($result == false) {
            $this->ajaxReturn(array("code" => "error", "msg" => "上架失败！"));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => "上架成功！"));
        }
    }

    /**
     *****************************************供应商*START***************************************************************
     */

    /**
     *获取商品列表
     */
    public function sup_goods_lists()
    {
        if (IS_POST) {
            $per_page = 10;
            $page = I('page',0);
            $status = I('status',1);
            $keyword = I('keyword');
            if($keyword){
                $map['goods_name|bar_code'] = array('LIKE',"%$keyword%");
            }
            $map['uid'] = $this->userInfo['uid'];
            $map['status'] = $status;
            $data = M('sup_goods')
                ->where($map)
                ->field('goods_id,goods_name,goods_number,sales,shop_price,status,goods_img')
                ->limit($page * $per_page, $per_page)
                ->select();
            foreach($data as &$v){
                $img_array = explode(',',$v['goods_img']);
                unset($v['goods_img']);
                $v['window_img'] = $img_array[0];
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     *添加/编辑供应商商品
     */
    public function sup_goods()
    {
        if (IS_POST) {
            $post = I('');
            if(!trim($post['goods_name'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品名称"));
            if(!trim($post['group_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择分类"));
            if(!trim($post['units_id'])) $this->ajaxReturn(array("code" => "error", "msg" => "请选择计量单位"));
            if(!trim($post['ratio'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写换算比例"));
            if(!trim($post['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写商品条形码"));
            if(!trim($post['buy_price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写进货价"));
            if(!trim($post['shop_price'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写售价"));
            if(!trim($post['goods_number'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写库存"));
            //if(!trim($post['goods_brief'])) $this->ajaxReturn(array("code" => "error", "msg" => "请填写采购须知"));
            if($post['goods_img']){
                $img_array = explode(',',$post['goods_img']);
                foreach ($img_array as &$v) {
                    $v = 'http://' . $_SERVER['HTTP_HOST'].$v;
                }
                $post['goods_img'] = implode(',',$img_array);
            }
            #如果传了goods_id就是编辑save
            if($post['goods_id']){
                $res = M('sup_goods')->where(array('goods_id'=>$post['goods_id']))->save($post);
                if ($res!==false) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "编辑成功"));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "编辑失败"));
                }
            }else{
                #否则就是添加add
                $post['add_time'] = time();
                $post['uid'] = $this->userInfo['uid'];
                if (M('sup_goods')->add($post)) {
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
     *更改商品状态
     */
    public function exchange_sup_goods_status()
    {
        if (IS_POST) {
            $goods_id = I('goods_id');
            $status = I('status');
            if(!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "goods_id is empty"));
            if(!$status) $this->ajaxReturn(array("code" => "error", "msg" => "status is empty"));
            $res = M('sup_goods')->where(array('goods_id'=>$goods_id))->setField('status',$status);
            if($res !== false){
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            }else{
                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }
    /**
     *获取商品详情
     */
    public function get_sup_detail()
    {
        if (IS_POST) {
            $goods_id = I('goods_id');
            if(!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "goods_id is empty"));
            $data = M('sup_goods')->field('uid,add_time',true)->where(array('goods_id'=>$goods_id))->find();
            $data['goods_img'] = explode(',',$data['goods_img']);
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     *获取供应商的计量单位
     */
    public function get_sup_units()
    {
        if (IS_POST) {
            $data = M('units')->where('uid='.$this->userInfo['uid'].' or uid=0 and belong_to=1')->field('id,unit_name')->select();
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }
    /**
     *****************************************供应商*END***************************************************************
     */

    /**
     *添加商品
     */
    public function new_goods()
    {
        if (IS_POST) {
            // $this->userId =26;
            $this->posts = I("");
            if ($this->posts['star'] > 5 || $this->posts['star'] <1 ) $this->ajaxReturn(array("code" => "error", "msg" => "商品推荐指数只能为1-5星!"));
            if (!$this->posts['goods_name']) $this->ajaxReturn(array("code" => "error", "msg" => "商品名称不能为空!"));
            if (!$this->posts['group_id']) $this->ajaxReturn(array("code" => "error", "msg" => "商品分组不能为空!"));
            if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));
            if ($this->goodsModel->where(array("bar_code" => $this->posts['bar_code'],'is_delete' => 0))->getField("goods_id")) {
                $this->ajaxReturn(array("code" => "error", "msg" => "商品条码已经存在!"));
            }
            $this->posts['freight'] = floatval($this->posts['freight']);//运费
            //处理商品图片
            $goods_img = $this->posts['goods_img'];
            $goods_img_arr = explode(',', $goods_img);
            foreach ($goods_img_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_img'."$count"] = $v;
            }
            unset($this->posts['goods_img']);
            //处理图文描述
            $pic_desc = $this->posts['pic_desc'];
            $pic_desc_arr = explode(',', $pic_desc);
            foreach ($pic_desc_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['pic_desc'."$count"] = $v;
            }

            unset($this->posts['pic_desc']);
            $goods_desc = $this->posts['goods_desc'];
            $goods_desc_arr = explode(',', $goods_desc);
            foreach ($goods_desc_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_desc'."$count"] = $v;
            }
            unset($this->posts['goods_desc']);
            // dump($this->posts);die;
            //判断规格
            if (!$this->posts['sku']) {
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
                $this->posts['original_price'] = floatval($this->posts['original_price']);
                $this->posts['buy_price'] = floatval($this->posts['buy_price']);
                $this->posts['shop_price'] = floatval($this->posts['shop_price']);
                $this->posts['goods_number'] = intval($this->posts['goods_number']);
            } else { 
                $this->posts['is_sku'] = '1';
                $sku = I('sku');
                $sku = json_decode(htmlspecialchars_decode($sku), true);
            }
            // die;
            $this->posts['mid'] = $this->userId;
            $this->posts['add_time'] = time();
            $this->posts['is_on_sale'] = I('is_on_sale', '1');
            $this->posts['is_on_xcx'] = I('is_on_xcx', '2');
            if ($this->posts['is_on_xcx']==1) {
                $this->posts['put_xcx'] = 2;
                $this->posts['put_pos'] = 2;
                $this->posts['put_two'] = 2;
            }
            $this->posts['is_hot'] = I('is_hot', '0');
            // dump($this->posts['is_on_sale']);
            // die;
            if ($this->goodsModel->where(array("goods_name" => $this->posts['goods_name'], 'mid' => $this->userId, 'is_delete' => 0))->getField("goods_id")) {
                $this->ajaxReturn(array("code" => "error", "msg" => "不能重复添加!"));
            }
            if ($this->goodsModel->create()) {
                // dump($this->posts);die;
                // $this->upload_pic();
                $goodsId = $this->goodsModel->add($this->posts);

                if ($goodsId) {
                    // if ($this->posts['spc']) $this->add_goods_value($this->posts['spc'], $goodsId, 'add');
                    if ($this->posts['sku']) {
                        
                        foreach ($sku as $key => $value) {
                            $sku_arr = array();
                            $sku_arr['goods_id'] = $goodsId;
                            $sku_arr['properties'] = $sku[$key]['properties'];
                            $sku_arr['quantity'] = $sku[$key]['quantity'];
                            $sku_arr['price'] = $sku[$key]['price'];
                            $sku_arr['original_price'] = $sku[$key]['original_price'];
                            $sku_arr['cost'] = $sku[$key]['cost'];
                            $sku_arr['properties_name'] = $sku[$key]['properties'];
                            $sku_arr['status'] = '1';
                            $sku_arr['add_time'] = time();
                            $this->goods_sku_Model->add($sku_arr);
                        }
                        //更新商品表库存、进价、售价
                        $this->update_other_info($goodsId);
                        
                        # 图文描述入库
                        if($pic_desc){
                            $this->add_desc($pic_desc, $goodsId);
                        }
                    }
                    $this->ajaxReturn(array("code" => "success", "msg" => "添加成功!"));

                }
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败!"));

            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "添加失败!"));
            }
        }
    }

    private function add_desc($desc_imgs, $goods_id)
    {
        $desc_img = explode(',', $desc_imgs);
        $desc_data = array();
        foreach ($desc_img as $valu) {
            $desc_data[] = array(
                'goods_id' => $goods_id,
                'url'   => $valu,
            );
        }
        $this->goods_desc->addAll($desc_data);
    }

    /**
     * 编辑商品
     */
    public function save_goods()
    {
        if (IS_POST) {
            $this->posts = I("");
            // succ($this->posts);
            $goodsId = $this->posts['goods_id'];
            if (!$goodsId) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
            if ($this->posts['star'] > 5 || $this->posts['star'] <1 ) $this->ajaxReturn(array("code" => "error", "msg" => "商品推荐指数只能为1-5星!"));
            if (!$this->posts['goods_name']) $this->ajaxReturn(array("code" => "error", "msg" => "商品名称不能为空!"));
            if (!$this->posts['group_id']) $this->ajaxReturn(array("code" => "error", "msg" => "商品分组不能为空!"));
            // if (!$this->posts['cat_id']) $this->ajaxReturn(array("code" => "error", "msg" => "分类不能为空!"));
            // if (!$this->posts['brand_name']) $this->ajaxReturn(array("code" => "error", "msg" => "品牌不能为空!"));
            if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));
            
            $this->posts['freight'] = floatval($this->posts['freight']);//运费
            //处理商品图片
            $goods_img = $this->posts['goods_img'];
            $goods_img_arr = explode(',', $goods_img);
            for($i=1;$i<=5;$i++){
                $this->posts['goods_img'."$i"] = '';
            }
            foreach ($goods_img_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_img'."$count"] = $v;
            }
            unset($this->posts['goods_img']);
            //处理图文描述
            $pic_desc = $this->posts['pic_desc'];
            $pic_desc_arr = explode(',', $pic_desc);
            foreach ($pic_desc_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['pic_desc'."$count"] = $v;
            }
            unset($this->posts['pic_desc']);
//            $goods_desc = $this->posts['goods_desc'];
//            $goods_desc_arr = explode(',', $goods_desc);
//            foreach ($goods_desc_arr as $k => $v) {
//                $count = $k + 1;
//                $this->posts['goods_desc'."$count"] = $v;
//            }
//            unset($this->posts['goods_desc']);
            // dump($this->posts);die;
            // $this->ajaxReturn(array("code" => "error", "msg" => $this->posts))
            //判断规格
            if (!$this->posts['sku']) {
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
                $this->posts['buy_price'] = floatval($this->posts['buy_price']);
                $this->posts['original_price'] = floatval($this->posts['original_price']);
                $this->posts['shop_price'] = floatval($this->posts['shop_price']);
                $this->posts['goods_number'] = intval($this->posts['goods_number']);
                $this->posts['is_sku'] = '0';
            } else {
                
                $this->posts['is_sku'] = '1';
                $sku = I('sku');
                $sku = json_decode(htmlspecialchars_decode($sku), true);
                
            }
            $this->posts['mid'] = $this->userId;
            $this->posts['is_hot'] = I('is_hot', '0');
            // succ($sku);
            if ($this->goodsModel->create()) {
                // $this->upload_pic();
                // dump($this->posts);
                $res = $this->goodsModel->where(array('goods_id' => $goodsId))->save($this->posts);
                if ($res) {
                    $this->goods_sku_Model->where(array('goods_id' => $goodsId))->delete();
                    if ($this->posts['sku']) {
                        foreach ($sku as $key => $value) {
                            $sku_arr = array();
                            $sku_arr['goods_id'] = $goodsId;
                            $sku_arr['properties'] = $sku[$key]['properties'];
                            $sku_arr['quantity'] = $sku[$key]['quantity'];
                            $sku_arr['price'] = $sku[$key]['price'];
                            $sku_arr['original_price'] = $sku[$key]['original_price'];
                            $sku_arr['cost'] = $sku[$key]['cost'];
                            $sku_arr['properties_name'] = $sku[$key]['properties'];
                            $sku_arr['status'] = '1';
                            $sku_arr['add_time'] = time();
                            $this->goods_sku_Model->add($sku_arr);
                        }
                        $this->update_other_info($goodsId);
                    }
                    if($pic_desc){
                        $this->goods_desc->where(array('goods_id'=>$goodsId))->delete();
                        $this->add_desc($pic_desc, $goodsId);
                    }
                    
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "保存成功!"));
                
                
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "保存失败!"));
            }
        }
    }

    public function save_goods1()
    {
        if (IS_POST) {
            $this->posts = I("");
            $goodsId = $this->posts['goods_id'];
            if (!$goodsId) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
            if ($this->posts['star'] > 5 || $this->posts['star'] <1 ) $this->ajaxReturn(array("code" => "error", "msg" => "商品推荐指数只能为1-5星!"));
            if (!$this->posts['goods_name']) $this->ajaxReturn(array("code" => "error", "msg" => "商品名称不能为空!"));
            if (!$this->posts['group_id']) $this->ajaxReturn(array("code" => "error", "msg" => "商品分组不能为空!"));
            // if (!$this->posts['cat_id']) $this->ajaxReturn(array("code" => "error", "msg" => "分类不能为空!"));
            // if (!$this->posts['brand_name']) $this->ajaxReturn(array("code" => "error", "msg" => "品牌不能为空!"));
            if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码至少6个字节!"));
            
            $this->posts['freight'] = floatval($this->posts['freight']);//运费
            //处理商品图片
            $goods_img = $this->posts['goods_img'];
            $goods_img_arr = explode(',', $goods_img);
            foreach ($goods_img_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_img'."$count"] = $v;
            }
            unset($this->posts['goods_img']);
            //处理图文描述
            $pic_desc = $this->posts['pic_desc'];
            $pic_desc_arr = explode(',', $pic_desc);
            foreach ($pic_desc_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['pic_desc'."$count"] = $v;
            }
            unset($this->posts['pic_desc']);
            $goods_desc = $this->posts['goods_desc'];
            $goods_desc_arr = explode(',', $goods_desc);
            foreach ($goods_desc_arr as $k => $v) {
                $count = $k + 1;
                $this->posts['goods_desc'."$count"] = $v;
            }
            unset($this->posts['goods_desc']);
            // dump($this->posts);die;
            
            //判断规格
            if (!$this->posts['sku']) {
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
                $this->posts['buy_price'] = floatval($this->posts['buy_price']);
                $this->posts['shop_price'] = floatval($this->posts['shop_price']);
                $this->posts['goods_number'] = intval($this->posts['goods_number']);
                $this->posts['is_sku'] = '0';
            } else {
                // echo "string";
                $this->posts['is_sku'] = '1';
                $sku = I('sku');
                $sku = json_decode(htmlspecialchars_decode($sku), true);
                
            }
            $this->posts['mid'] = 21;

            if ($this->goodsModel->create()) {
                // $this->upload_pic();
                // dump($this->posts);
                $res = $this->goodsModel->where(array('goods_id' => $goodsId))->save($this->posts);
                if ($res) {
                    // echo "111";
                    $this->goods_sku_Model->where(array('goods_id' => $goodsId))->delete();
                    if ($this->posts['sku']) {
                        // echo "222";
                        foreach ($sku as $key => $value) {
                            $sku_arr = array();
                            $sku_arr['goods_id'] = $goodsId;
                            $sku_arr['properties'] = $sku[$key]['properties'];
                            $sku_arr['quantity'] = $sku[$key]['quantity'];
                            $sku_arr['price'] = $sku[$key]['price'];
                            $sku_arr['cost'] = $sku[$key]['cost'];
                            $sku_arr['properties_name'] = $sku[$key]['properties'];
                            $sku_arr['status'] = '1';
                            $sku_arr['add_time'] = time();
                            $this->goods_sku_Model->add($sku_arr);
                        }
                        $this->update_other_info($goodsId);
                    }
                    
                    
                }
                $this->ajaxReturn(array("code" => "success", "msg" => "保存成功!"));
                
                
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "保存失败!"));
            }
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
            $img = $pic_root_path . $info['img']['savepath'] . $info['img']['savename'];
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
    }

}

