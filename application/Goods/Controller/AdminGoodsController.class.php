<?php
/**
 * Created by PhpStorm.
 * User: joan
 * Date: 2017/4/13
 * Time: 17:22
 */
namespace Goods\Controller;

use Common\Controller\AdminbaseController;

/**后台商品管理控制器
 * Class AdminGoodsController
 * @package Goods\Controller
 */
class AdminGoodsController extends AdminbaseController
{
    public $goodsModel;
    public $goods_group_model;
    public $goods_attr_model;
    public $category_model;
    public $goods_sku_Model;
    public $brand_model;
    public $posts;
    public $host;
    public $units_model;
    public $agent_goods;

    function _initialize()
    {
        parent::_initialize();
        $this->goodsModel = M("goods");
        $this->goods_sku_Model = M("goods_sku");
        $this->category_model = M("category");
        $this->goods_group_model = M("goods_group");
        $this->goods_attr_model = M("goods_attr");
        $this->brand_model = M("brand");
        $this->units_model = M("units");
        $this->agent_goods = M("agent_goods");
        $this->host = 'http://' . $_SERVER['HTTP_HOST'];
    }

    public function index()
    {
        $posts = I("");
        if ($posts['bar_code']) {
            $map['g.bar_code'] = $posts['bar_code'];
            $this->assign("bar_code", $posts['bar_code']);
        }
        if ($posts['goods_name']) {
            $map['g.goods_name'] = $posts['goods_name'];
            $this->assign("goods_name", $posts['goods_name']);
        }
        if ($posts['merchant_name']) {
            $map['me.merchant_name'] = $posts['merchant_name'];
            $this->assign("merchant_name", $posts['merchant_name']);
        }
        if ($posts['brand_name']) {
            $map['b.brand_name'] = $posts['brand_name'];
            $this->assign("brand_name", $posts['brand_name']);
        }

        if (in_array($posts['is_on_sale'], array("0", "1"))) {
            $map['g.is_on_sale'] = $posts['is_on_sale'];
        } else
            $posts['is_on_sale'] = '-1';

        $this->assign("is_on_sale", $posts['is_on_sale']);

        $map['is_delete'] = '0';

        $this->_lists($map);
        $this->display();
    }

    public function detail()
    {
        $map['g.goods_id'] = intval(I('id'));
        $this->goodsModel
            ->alias("g")
            ->where($map);
        $field = 'g.goods_id,g.goods_img1,g.pic_desc1,g.goods_name,g.shop_price,g.goods_number,g.sales,g.bar_code,g.is_on_sale,g.is_best,g.is_new,g.is_hot,g.buy_price,g.add_time,b.brand_name,gr.group_name,m.user_name';
        $this->goodsModel->field($field);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
//        $this->goodsModel->join("LEFT JOIN __CATEGORY__ c ON g.cat_id = c.cat_id");
        $this->goodsModel->join("LEFT JOIN __GOODS_GROUP__ gr ON g.group_id = gr.group_id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS_USERS__ m ON g.mid = m.id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS__ me ON g.mid = me.uid");
        $data = $this->goodsModel->find();
        if (!$data['goods_img1']) $data['goods_img1'] = 'http://a.ypt5566.com/data/upload/goods/2017-05-11/5913e23d27652.jpg';
        else
            if ($_SERVER['HTTP_HOST'] == '127.0.0.1') $data['goods_img1'] = 'http://a.ypt5566.com' . $data['goods_img1'];
//        $data['cat_name'] = $this->get_full_cat_name($data['cat_id']);
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     */
    private function _lists($where = array())
    {

        $this->goodsModel
            ->alias("g")
            ->where($where);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
//        $this->goodsModel->join("LEFT JOIN __CATEGORY__ c ON g.cat_id = c.cat_id");
        $this->goodsModel->join("LEFT JOIN __GOODS_GROUP__ gr ON g.group_id = gr.group_id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS_USERS__ m ON g.mid = m.id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS__ me ON g.mid = me.uid");
        $count = $this->goodsModel->count('distinct(g.goods_id)');
        
        $page = $this->page($count, 20);

        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->group("g.goods_id DESC");
        $field = 'g.goods_id,g.add_time,g.goods_img1,g.goods_name,g.shop_price,g.goods_number,g.sales,g.bar_code,g.is_on_sale,g.buy_price,b.brand_name,gr.group_name,m.user_name';
        $this->goodsModel->field($field);
        $this->goodsModel->join("LEFT JOIN __BRAND__ b ON g.brand_id = b.brand_id");
//        $this->goodsModel->join("LEFT JOIN __CATEGORY__ c ON g.cat_id = c.cat_id");
        $this->goodsModel->join("LEFT JOIN __GOODS_GROUP__ gr ON g.group_id = gr.group_id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS_USERS__ m ON g.mid = m.id");
        $this->goodsModel->join("LEFT JOIN __MERCHANTS__ me ON g.mid = me.uid");
        $data_lists = $this->goodsModel->select();

        foreach ($data_lists as $k => $v) {
            if (!$v['goods_img1']) $data_lists[$k]['goods_img1'] = 'http://a.ypt5566.com/data/upload/goods/2017-05-11/5913e23d27652.jpg';
            else
                if ($_SERVER['HTTP_HOST'] == '127.0.0.1') $data_lists[$k]['goods_img1'] = 'http://a.ypt5566.com' . $v['goods_img1'];
//            $data_lists[$k]['cat_name'] = $this->get_full_cat_name($v['cat_id']);
        }

        $this->assign("page", $page->show('Admin'));
        $this->assign("data_lists", $data_lists);
    }


    /**返回一级二级分类
     * @param $cat_id
     * @return string
     */
    private function get_full_cat_name($cat_id)
    {
        $cat_info = $this->category_model->where(array("cat_id" => $cat_id))->field("cat_name,parent_id")->find();
        if ($cat_info['parent_id'] > 0) {
            $cat_name = $this->category_model->where(array("cat_id" => $cat_info['parent_id']))->getField("cat_name");
            return $cat_name . ">" . $cat_info['cat_name'];
        } else
            return $cat_info['cat_name'] ? $cat_info['cat_name'] : "";
    }


    public function add()
    {
        if (IS_POST) {
            if ($_FILES) {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 3145728;
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
                $upload->rootPath = C('_WEB_UPLOAD_');
                $upload->savePath = 'goods/';
                $upload->saveName = uniqid;//保持文件名不变

                $info = $upload->upload();
                if (!$info) {
                    $this->error($upload->getError());
                }
            }

            $data = I("");
//            $data['admin'] = $_SESSION['name'];
//            if (!$data['cat_name']) $this->error("请输入分类名称!");
//            if ($this->catModel->where(array("cat_name" => $data['cat_name']))->getField("cat_id")) $this->error("分类名称不能重复添加!");
            if ($this->goodsModel->add($data)) $this->success(L('添加成功'), U("AdminCategory/index"));
            else  $this->error("添加失败!");
        } else {
            $this->display();
        }

    }

    public function edit()
    {
        if (IS_POST) {
            $data = I("");
            $data['admin'] = $_SESSION['name'];
            if (!$data['cat_name']) $this->error("请输入分类名称!");
            if ($this->catModel->where(array("cat_name" => $data['cat_name']))->getField("cat_id")) $this->error("分类名称不能重复添加!");
            $this->catModel->where(array("id" => $data['id']))->add($data);
            $this->success(L('修改成功'), U("AdminCategory/index"));
        } else {
            $cat_id = I("id", 0);
            $info = $this->catModel->where(array("cat_id" => $cat_id))->find();
            $this->assign("info", $info);
            $this->display();
        }

    }

    //删除
    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = I("get.id", 0, 'intval');
            if ($this->catModel->where(array('cat_id' => $id))->save(array('is_show' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }

        if (isset($_POST['ids'])) {
            $ids = I('post.ids/a');

            if ($this->catModel->where(array('cat_id' => array('in', $ids)))->save(array('is_show' => '-1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }

        /**
     * 商品总库列表
     * @return [type] [description]
     */
    public function library()
    {
        $posts = I("");
        if ($posts['bar_code']) {
            $map['g.bar_code'] = array('like',$posts['bar_code'].'%');
            $this->assign("bar_code", $posts['bar_code']);
        }
        if ($posts['goods_name']) {
            $map['g.goods_name'] = array('like','%'.$posts['goods_name'].'%');
            $this->assign("goods_name", $posts['goods_name']);
        }
        
        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map["g.add_time"] = array('between', array(strtotime($start_time), strtotime($end_time) + 84399));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        }
        //商品分类选择

        if (!empty($posts['group_id_sec'])) {
        //有第二分类
            $map['g.group_id'] = $posts['group_id_sec'];

        } else {
            //查询条件只有一级分类
            if (!empty($posts['group_id'])) {
                $con['gid']  = $posts['group_id'];
                $ids               = $this->goods_group_model->where($con)->getField('group_id', true);
                $ids[]             = $posts['group_id'];
                $map['g.group_id'] = array('in', $ids);

            }
        }
        $field = 'g.id,g.goods_img,g.goods_name,g.shop_price,g.bar_code,g.is_sku,g.buy_price,g.units_id,g.group_id,g.trade,g.add_time,gr.group_name';
        $map['g.is_delete'] = 0;
        $goods = M('goods_library')->alias('g')->where($map)->join("LEFT JOIN __GOODS_GROUP__ gr ON g.group_id = gr.group_id")->field($field)->select();
        foreach ($goods as $key => &$value) {
            $groups = array();
            if ($value['group_id']==0) {
                $value['groups'] = $groups;
            }else{
                $group = M('goods_group')->where(array('group_id'=>$value['group_id']))->find();
                if ($group['gid']==0) {
                    //一级分类
                    array_push($groups,array('group_id'=>$group['group_id'],'group_name'=>$group['group_name']));
                    $value['group_name1'] = $group['group_name'];
                }else{                  
                    //二级分类
                    $group2 = M('goods_group')->where(array('group_id'=>$group['gid']))->find();
                    array_push($groups,array('group_id'=>$group2['group_id'],'group_name'=>$group2['group_name']));
                    array_push($groups,array('group_id'=>$group['group_id'],'group_name'=>$group['group_name']));
                     $value['group_name1'] = $group2['group_name'];
                      $value['group_name2'] = $group['group_name'];
                }
                $value['groups'] = $groups;
            }
            $unit_name = M('units')->where(array('id'=>$value['units_id']))->getField('unit_name');
            $value['unit_name'] = $unit_name?$unit_name:'';
            $goods_img = explode(',',$value['goods_img']);
            $value['window_img'] = $goods_img[0];
        }
        
        $g = M('goods_group')->where(array('mid'=>0,'gid'=>0))->select();
        $this->assign('data_lists',$goods);
        $this->assign('group',$g);
        $this->display();
    }

    /**
     * 添加商品库
     */
    public function add_library()
    {
        if (IS_POST) { 
             M()->startTrans();
            $this->posts              = I("");
            $goodsData = $this->posts;
            if (!$goodsData['goods_name']) {
                $this->error("商品名称不能为空!");
            }
            if (strlen($goodsData['goods_name'])>60) {
                $this->error("商品名称不超过20个汉字!");
            }
            if (M('goods_library')->where(array("bar_code" => $this->posts['bar_code'], 'is_delete' => 0))->getField("bar_code")) {
                M()->rollback();
                $this->error("条形码已存在!");
            }
            if ($goodsData['bar_code'] && strlen($this->posts['bar_code']) < 6) {
                $this->error("商品条码至少6个字节!");
            }
            if (empty($goodsData['goods_img1'])) {
                $this->error("最少一张商品图片!");
            }
            if ($goodsData['goods_img1']) {
                $goodsData['goods_img'] = $goodsData['goods_img1'];
                if ($goodsData['goods_img2']) {
                    $goodsData['goods_img'] = $goodsData['goods_img1'].','.$goodsData['goods_img2'];
                    if ($goodsData['goods_img3']) {
                        $goodsData['goods_img'] = $goodsData['goods_img1'].','.$goodsData['goods_img2'].','.$goodsData['goods_img3'];
                    }
                }
            }
            if ($goodsData['group_id_sec']) {
                $goodsData['group_id'] = $goodsData['group_id_sec'];
            }else{

            }
            unset($goodsData['group_id_sec']);

            //未开启库存添加
            if (!$this->posts['buy_price'][0]) {
                $this->error("进价不能为空!");
            }
            if (!$this->posts['buy_price'][0]) {
                $this->error("售价不能为空!");
            }
            $skuCount                = count($this->posts['shop_price']);
            $goodsData['buy_price']  = $this->posts['buy_price'][0];
            $goodsData['shop_price'] = $this->posts['shop_price'][0];

            $goodsData['is_sku'] = $skuCount == 1 ? 0 : 1; //是否开启多单位
            if ($skuCount == 1) {
                $goodsData['units_id'] = $this->posts['unit'][0];

            } else {
                $goodsData['units_id'] = 0;

            }
            $goodsData['add_time']=time();
            $goodsData['trade']=1;
            unset($goodsData['unit']);
            $goodsId = M('goods_library')->add($goodsData); //添加商品

            //单单位
            if ($skuCount == 1) {
                $skuResult = 1; //添加成功
            } else {
                //多单位
                $skuData = array();
                for ($i = 0; $i < $skuCount; $i++) {
                    if (!$this->posts['buy_price'][$i]) {
                        $this->error("进价不能为空!");
                    }

                    if (!$this->posts['shop_price'][$i]) {
                        $this->error("售价不能为空!");
                    }

                    if (!$this->posts['unit'][$i]) {
                        $this->error("单位不能为空!");
                    }
                    $data['goods_id'] = $goodsId;
                    $data['buy_price']     = $this->posts['buy_price'][$i];
                    $data['shop_price']    = $this->posts['shop_price'][$i];
                    //计量单位
                    $data['units_id'] = $this->posts['unit'][$i];
                    $unit_name = M('units')->where(array('id'=>$data['units_id']))->getField('unit_name');
                    $data['units_name']   = $unit_name;
                    $data['add_time'] = time();
                    $skuData[]        = $data;
                }
                $skuResult = M('library_sku')->addAll($skuData);

            }
            

    

            if ($goodsId && $skuResult) {
                M()->commit();
                $this->success("添加成功!");
            } else {
                M()->rollback();
                $this->error("添加失败!");
            } 
        }else{
            $this->units_list();
            $this->assign("group", $this->_lists_(array('mid' => 0)));
            $this->display();  
        }
        
    }

    public function edit_library()
    {
        if (IS_POST) {
            M()->startTrans();
            $this->posts              = I("");
            $goodsId = $this->posts['goods_id'];
            $goodsData = $this->posts;
            if (!$goodsData['goods_name']) {
                $this->error("商品名称不能为空!");
            }
            if (strlen($goodsData['goods_name'])>60) {
                $this->error("商品名称不超过20个汉字!");
            }
            
            if ($goodsData['bar_code'] && strlen($this->posts['bar_code']) < 6) {
                $this->error("商品条码至少6个字节!");
            }
            if (empty($goodsData['goods_img1'])) {
                $this->error("最少一张商品图片!");
            }
            if ($goodsData['goods_img1']) {
                $goodsData['goods_img'] = $goodsData['goods_img1'];
                if ($goodsData['goods_img2']) {
                    $goodsData['goods_img'] = $goodsData['goods_img1'].','.$goodsData['goods_img2'];
                    if ($goodsData['goods_img3']) {
                        $goodsData['goods_img'] = $goodsData['goods_img1'].','.$goodsData['goods_img2'].','.$goodsData['goods_img3'];
                    }
                }
            }
            if ($goodsData['group_id_sec']) {
                $goodsData['group_id'] = $goodsData['group_id_sec'];
            }else{

            }
            unset($goodsData['group_id_sec']);

            //未开启库存添加
            if (!$this->posts['buy_price'][0]) {
                $this->error("进价不能为空!");
            }
            if (!$this->posts['buy_price'][0]) {
                $this->error("售价不能为空!");
            }
            $skuCount                = count($this->posts['shop_price']);
            $goodsData['buy_price']  = $this->posts['buy_price'][0];
            $goodsData['shop_price'] = $this->posts['shop_price'][0];

            $goodsData['is_sku'] = $skuCount == 1 ? 0 : 1; //是否开启多单位
            if ($skuCount == 1) {
                $goodsData['units_id'] = $this->posts['unit'][0];

            } else {
                $goodsData['units_id'] = 0;

            }
            $goodsData['add_time']=time();
            $goodsData['trade']=1;
            unset($goodsData['unit']);
            M('goods_library')->where(array('id' => $goodsId))->save($goodsData); //添加商品

            //单单位
            if ($skuCount == 1) {
                $skuResult = 1; //添加成功
            } else {
                //多单位
                $skuData = array();
                for ($i = 0; $i < $skuCount; $i++) {
                    if (!$this->posts['buy_price'][$i]) {
                        $this->error("进价不能为空!");
                    }

                    if (!$this->posts['shop_price'][$i]) {
                        $this->error("售价不能为空!");
                    }

                    if (!$this->posts['unit'][$i]) {
                        $this->error("单位不能为空!");
                    }
                    $data['goods_id'] = $goodsId;
                    $data['buy_price']     = $this->posts['buy_price'][$i];
                    $data['shop_price']    = $this->posts['shop_price'][$i];
                    //计量单位
                    $data['units_id'] = $this->posts['unit'][$i];
                    $unit_name = M('units')->where(array('id'=>$data['units_id']))->getField('unit_name');
                    $data['units_name']   = $unit_name;
                    $data['add_time'] = time();
                    $skuData[]        = $data;
                }
                M('library_sku')->where(array('goods_id'=>$goodsId))->delete();
                $skuResult = M('library_sku')->addAll($skuData);

            }
            if ($goodsId && $skuResult) {
                M()->commit();
                $this->success("编辑成功!");
            } else {
                M()->rollback();
                $this->error("编辑失败!");
            } 
        }else{
            //获取商品详情
        $goods =M('goods_library')->where(array('id'=>I('id')))->find();
        $goods_img = explode(',',$goods['goods_img']);
        foreach ($goods_img as $key => $value) {
            $k = $key+1;
            $goods["goods_img".$k] = $value;
        }
        $this->assign('goods', $goods);
        if ($goods['is_sku']==1) {
            $sku = M('library_sku')->where(array('goods_id'=>I('id')))->select();
            $this->assign('sku', $sku);
        }
        //计量单位
        $units = $this->units_model->where(array('belong_to' => 3,'is_delete'=>0))->select();
        $this->assign('units', $units);
        $g = M('goods_group')->where(array('mid'=>$this->UID,'gid'=>0))->select();
        $cate = M('goods_group')->where(array('group_id'=>$goods['group_id']))->find();
        if ($cate['gid'] == 0) {
        //只有一级分类情况
            $top_info = array('top_id' => $cate['group_id'], 'top_name' => $cate['group_name']);
            $sec_info = null;

        } else {
            $top_info   = array('top_id' => $cate['gid'], 'top_name' => '');
            $child_list = $this->goods_group_model->where(array('gid' => $cate['gid']))->select();
            $sec_id     = $cate['group_id'];

        }
        $map['mid'] =$this->UID;
        if (!$map['mid']) {
            $map['id']        = array('gt', 0);
            $map['gid'] = 0;
        }
        $group = M('goods_group')->where($map)->select();
        if (!empty($child_list)) {
            $this->assign('child_list', $child_list);
            $this->assign('sec_id', $sec_id);

        }
        $this->assign('top_info', $top_info);
        $this->assign('group',$g);
        // dump($goods);dump($g);dump($top_info);dump($sec_id);dump($child_list);dump($sku);dump($units);
        $this->display();
        }
    }

    public function delete_library()
    {
        if (isset($_GET['id'])) {
            $id = I("get.id", 0, 'intval');
            if (M('goods_library')->where(array('id' => $id))->save(array('is_delete' => '1')) !== false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }

    /**
     * 分组列表
     * @return [type] [description]
     */
    public function group_list()
    {
        $this->assign("data_lists", $this->_lists_(array('mid' => 0)));
        $this->display();
    }

    private function _lists_($where = array())
    {
        $data          = $this->goods_group_model->where($where)->select();
        $mainCategory  = array();
        $childCategory = array();
        foreach ($data as $key => $value) {
            $value['gid'] == 0 ? $mainCategory[] = $value : $childCategory[] = $value;
        }

        foreach ($mainCategory as $_key => $_value) {
            $child_num = 0;
            foreach ($childCategory as $_k => $_v) {
                if ($_value['group_id'] == $_v['gid']) {
                    $child_num++;
                    $mainCategory[$_key]['child'][] = $_v;

                }

            }
            $mainCategory[$_key]['child_num'] = $child_num;

        }
        return $mainCategory;
        
    }

    /**
     * 添加一级分类
     */
    public function add_top_level()
    {

        $cate_id  = I('post.top_id');
        $top_name = trim(I('post.cate_name'));
        $cate_img= I('post.cate_img');
        $trade= I('post.trade');

        if (empty($top_name)) {return 0;}
        $data          = array();
        
        if (!empty($cate_img)) {
            $data['img'] = $cate_img;
        }
        //存在id即更新
        if ($cate_id) {
            $data['group_id']   = $cate_id;
            $data['group_name'] = $top_name;

            if ($this->goods_group_model->save($data) !== false) {
                $this->ajaxReturn(1);
            } else {
                return $this->ajaxReturn(0);
            }

        } else {
            if ($this->goods_group_model->where(array("group_name" => $top_name,'mid'=>0))->getField("group_id")) {
                $this->ajaxReturn(0);
            }
            $data['mid']   = $this->UID;
            $data['add_time'] = time();
            $data['group_name'] = $top_name;
            $data['trade'] = $trade;
            // dump($data);
            if ($this->goods_group_model->add($data)) {
                $this->ajaxReturn(1);
            }

        }

    }

    //添加/编辑二级分类
    public function add_cate()
    {
        $cat_id    = I('post.cat_id');
        $parent_id = I('post.parent_id');
        $cat_name  = I('post.cat_name');

        if (empty($cat_name)) {
            return 0;

        }
        $data          = array();
        $data['mid']   = $this->UID;
        $data['add_time'] = time();
        //$data['admin'] = $_SESSION['name'];
        //存在id即更新
        if ($cat_id) {
            $data['group_id']    = $cat_id;
            $data['group_name']  = $cat_name;
            $data['gid'] = $parent_id;
            if ($this->goods_group_model->save($data) !== false) {
                $this->ajaxReturn((int) $cat_id);
            } else {
                return $this->ajaxReturn(0);
            }

        } else {
            if ($this->goods_group_model->where(array("group_name" => $cat_name,'mid'=>$this->UID))->getField("group_id")) {
                $this->ajaxReturn(0);
            }

            $data['group_name']  = $cat_name;
            $data['gid'] = $parent_id;
            if ($cate = (int) ($this->goods_group_model->add($data))) {
                $this->ajaxReturn($cate);
            }

        }

    }

    //删除顶级分类
    public function del_top()
    {
        $cat_id = I('post.cat_id');
        if ($cat_id) {
            //删除顶级分类
            if ($this->goods_group_model->delete($cat_id)) {
                
                //查询是否有子类,要删除所有子类
                if ($this->goods_group_model->where(array('gid' => $cat_id))->count()) {
                    $ids       = $this->goods_group_model->where(array('gid' => $cat_id))->getfield('group_id', true);
                    $id_string = implode(',', $ids);
                    if ($this->goods_group_model->delete($id_string)  == false) {
                        $this->ajaxReturn(0);
                    }

                }
                $this->ajaxReturn(1);

            }

        }

    }



      //删除二级分类
    public function del_cate()
    {
        $cat_id = I('post.cat_id');
        if ($cat_id) {
            //删除顶级分类
            if ($this->goods_group_model->delete($cat_id)!== false) {
                //查询是否有子类,要删除所有子类
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(0);
            }

        }

    }

    # 单文件
    public function uploadInto()
    {
        $upload           = new \Think\Upload(); // 实例化上传类
        $upload->maxSize  = 3145728; // 设置附件上传大小
        $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'goods/'; // 设置附件上传（子）目录
        $upload->saveName = time() . '_' . mt_rand();
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            foreach ($info as $k => $v) {
                $name = $k;
            }
            $data['type'] = 1;
            $data['name'] = $name;
            $data['path'] = 'http://' . $_SERVER['HTTP_HOST'] . '/data/upload/' . $info[$name]['savepath'] . $info[$name]['savename'];
            echo json_encode($data);
            exit();
        } else {
            $data['type']    = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }

    /*
     *    返回子分类
     */
    public function get_child()
    {
        $parent_id = I('post.parent_id');
        //获取所有子类
        $child_cate = $this->goods_group_model->where('gid=' . $parent_id)->select();

        if (!empty($child_cate)) {
            $data = array('status' => 1, 'list' => $child_cate);

        } else {
            $data = array('status' => 0, 'list' => null);

        }
        $this->ajaxReturn($data);

    }

    /**desc 商品计量单位列表
     *
     */
    public function unitshow()
    {
        $this->units_list();
        $this->display();

    }

    /*
     * desc 计量单位列表
     *
     */
    private function units_list()
    {
        $count = M('units')->where(array('belong_to'=>3,'uid'=>$this->UID))->field('id,unit_name')->count();
        $where      = array('uid' => $this->UID, 'belong_to' => 3);
        $data_lists = M('units')->where($where)->select();
        $this->assign("data_lists", $data_lists);

    }

    /*desc ajax保存计量单位信息
     *
     */
    public function save_unit()
    {
        //更改类型
        $type    = I('post.type'); //更改类型
        $id      = I('post.unit_id');
        $content = I('post.content');
        $content = !empty($content) ? trim($content) : '';
        $data    = array();

        if ($id) {
        //存在即更新
            if ($type == 1) {
                $data['id']        = $id;
                $data['unit_name'] = $content;

            } else {
                $data['id']        = $id;
                $data['unit_info'] = $content;

            }

            $this->units_model->save($data);
            $mess = 'add';

        } else {
        //新增
            if ($type == 1) {
                //修改单位名称时候才新增
                $data['unit_name'] = $content;
                $data['belong_to'] = 3;
                $flag              = $this->units_model->add($data);

            } else {
                $flag = false;
            }
            $mess = 'update';

        }
        if ($flag !== false) {
        //插入或者更新成功
            $return = array('mess' => $mess, 'status' => $flag);

        } else {
            $return = array('mess' => $mess, 'status' => -1);

        }
        $this->ajaxReturn($return);

    }
    /*
     * desc 删除单位数
     */

    public function del_unit()
    {
        $id = I('post.unit_id');
        //查询剩余单位数，如果只剩下一个了，不允许删除
        $last_units = $this->units_model->where(array('uid' => $this->UID, 'belong_to' => 3))->count();
        if ($last_units == 1) {
        //只剩一个不允许删除
            $this->ajaxReturn(-1);

        }

        $is_delete = $this->units_model->delete($id);
        $this->ajaxReturn($is_delete);
    }
}
