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

    function _initialize()
    {
        parent::_initialize();
        $this->goodsModel = M("goods");
        $this->goods_sku_Model = M("goods_sku");
        $this->category_model = M("category");
        $this->goods_group_model = M("goods_group");
        $this->goods_attr_model = M("goods_attr");
        $this->brand_model = M("brand");
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
        //$this->userId = 8;
    }


    /**
     * 商品列表,包含分组商品列表
     */
    public function goods_list()
    {
        $keywords = I('keywords');
        $group_id = I('group_id');

        if ($keywords) {
            $map['b.brand_name'] = array('like', "%$keywords%");
            $map['g.goods_name'] = array('like', "%$keywords%");
            $map['g.bar_code'] = array('like', "%$keywords%");
        }

        if ($group_id) $map['g.group_id'] = $group_id;

        $map['g.is_on_sale'] = 1;
        $map['g.is_delete'] = 0;
        $map['g.mid'] = $this->userId;
        $per_page = 20;//每页数量
        $page = I("page,0");//页码,第几页

        $this->_lists($map, $page, $per_page);
    }


    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array(), $page, $per_page)
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
        $field = 'g.goods_id,g.goods_img1,g.goods_img2,g.goods_img3,g.goods_img4,g.goods_img5,g.pic_desc1,g.pic_desc2,g.pic_desc3,g.goods_name,g.shop_price,g.goods_number,g.sales ';
        $this->goodsModel->field($field);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
        $data_lists = $this->goodsModel->select();
        foreach ($data_lists as $k => $v) {
            $data_lists[$k]['goods_img1'] = $this->host . $v['goods_img1'];
            $data_lists[$k]['goods_img2'] = $this->host . $v['goods_img2'];
            $data_lists[$k]['goods_img3'] = $this->host . $v['goods_img3'];
            $data_lists[$k]['goods_img4'] = $this->host . $v['goods_img4'];
            $data_lists[$k]['goods_img5'] = $this->host . $v['goods_img5'];
            $data_lists[$k]['pic_desc1'] = $this->host . $v['pic_desc1'];
            $data_lists[$k]['pic_desc2'] = $this->host . $v['pic_desc2'];
            $data_lists[$k]['pic_desc3'] = $this->host . $v['pic_desc3'];
        }


        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array("total" => $total, "count" => $count, "data" => $data_lists)));
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
        $data = $this->goods_group_model->where(array('mid' => $this->userId))->order(array('add_time' => 'DESC'))->field("group_id,group_name")->select();
        foreach ($data as $k => $v) {
            $data[$k]['goods_number'] = $this->goodsModel->where(array('group_id' => $v['group_id']))->count();
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
            if ($this->goods_group_model->where(array("group_name" => $name, "group_id" => array('neq', $v)))->getField("group_id")) $this->ajaxReturn(array("code" => "error", "msg" => "分组名称不能重复"));
            $this->goods_group_model->where(array('group_id' => $v))->save(array("group_name" => $name));

        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
    }

    /**
     * 添加分组
     */
    public function add_group()
    {
        $data['group_name'] = I('group_name');
        $data['mid'] = $this->userId;
        $data['add_time'] = time();

        $is_name = $this->goods_group_model->where(array("group_name" => $data['group_name']))->find();
        if ($is_name) $this->ajaxReturn(array("code" => "error", "msg" => "店铺商品分组名已存在"));

        $result = $this->goods_group_model->add($data);
        if ($result) $this->ajaxReturn(array("code" => "success", "msg" => "新增商品分组成功", "data" => array("group_id" => $result)));
        else $this->ajaxReturn(array("code" => "error", "msg" => "新增商品分组失败"));

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
        $result = $this->goods_group_model->where($map)->delete();
        if ($result) {
            $this->group_list();
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "删除商品分组失败"));
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
        $this->ajaxReturn($res);
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

            if (!$this->posts['bar_code']) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码不能为空!"));
            if (!$this->posts['properties']) {
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
            } else {
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
            $this->posts['is_on_sale'] = I('is_on_sale', '0');

            if ($this->goodsModel->where(array("goods_name" => $this->posts['goods_name']))->getField("goods_id")) {
                $this->ajaxReturn(array("code" => "error", "msg" => "不能重复添加!"));
            }
            if ($this->goodsModel->create()) {

                $this->upload_pic();
                $goodsId = $this->goodsModel->add($this->posts);

                if ($goodsId) {
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
            $this->posts['brand_id'] = $this->get_brand_id($this->posts['brand_name']);
            if (!$this->posts['bar_code']) $this->ajaxReturn(array("code" => "error", "msg" => "商品条码不能为空!"));
            if (!$this->posts['properties']) {//没有填商品规格
                if (!$this->posts['buy_price']) $this->ajaxReturn(array("code" => "error", "msg" => "进价不能为空!"));
                if (!$this->posts['shop_price']) $this->ajaxReturn(array("code" => "error", "msg" => "售价不能为空!"));
                if (!$this->posts['goods_number']) $this->ajaxReturn(array("code" => "error", "msg" => "库存不能为空!"));
            } else {

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
            $this->posts['is_on_sale'] = I('is_on_sale', '0');

            if ($this->goodsModel->create()) {

                $this->upload_pic();
                $res = $this->goodsModel->where(array('goods_id' => $goodsId))->save($this->posts);

                if ($res) {
                    $this->goods_sku_Model->where(array('goods_id' => $goodsId))->delete();
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
            $upload->maxSize = 3145728;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = C('_WEB_UPLOAD_');
            $upload->savePath = 'goods/';
            $upload->saveName = uniqid;//保持文件名不变

            $info = $upload->upload();
            if (!$info['goods_img1'] && !$info['goods_img2'] && !$info['goods_img3'] && !$info['goods_img4'] && !$info['goods_img5']) {
                $this->ajaxReturn(array("code" => "error", "msg" => "请至少上传1张商品图片!"));
            }
            if (!$info) $this->error($upload->getError());
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
    public function goods_info()
    {
        $goods_id = I('goods_id');
        $where['goods_id'] = $goods_id;
        $where['is_delete'] = 0;
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->order("g.goods_id DESC");

        $this->goodsModel->field('g.goods_id,g.goods_img1,g.goods_name,g.shop_price,g.goods_number,g.buy_price,g.sales,g.bar_code,g.pic_desc1,g.pic_desc2 ,g.pic_desc3 , gg.group_name,b.brand_name');
        $this->goodsModel->join("RIGHT JOIN __GOODS_GROUP__ gg ON g.group_id = gg.group_id");
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");

        $res = $this->goodsModel->find();
        $res['specifications'] = $this->get_goods_sku($goods_id);
        $res['goods_img1'] = $this->host . $res['goods_img1'];
        $res['pic_desc1'] = $this->host . $res['pic_desc1'];
        $res['pic_desc2'] = $this->host . $res['pic_desc2'];
        $res['pic_desc3'] = $this->host . $res['pic_desc3'];
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));
//        $score_num = M('member', C("DB_PREFIX_2"), C("DB_NAME_2"))->where(array("usrid" => 288))->find();
//        dump($score_num);

    }


    /**获取单个商品sku
     * @param int $goods_id
     * @return mixed
     */
    public function get_goods_sku($goods_id = 0)
    {
        if (!$goods_id) $this->ajaxReturn(array("code" => "error", "msg" => "商品id不能为空!"));
        $sku_list = $this->goods_sku_Model->where(array("goods_id" => $goods_id))->field("properties,quantity,price,cost")->select();
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
}

