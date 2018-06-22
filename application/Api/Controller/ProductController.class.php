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
class ProductController extends ApibaseController

{
    public $goodsModel;
    public $goods_sku_Model;
//    public $goods_attach;
    public $goods_group_model;
    public $goods_desc;
    public $trade;
    public $posts;

    function _initialize()
    {
        parent::_initialize();
        $this->goodsModel = M("goods");
        $this->goods_sku_Model = M("goods_sku");
//        $this->goods_attach = M("goods_attach");
        $this->goods_group_model = M("goods_group");
        $this->goods_desc = M("goods_desc_img");
        $this->checkLogin();
        $this->userId = get_merchants_id($this->userInfo['role_id'], $this->userId);

        $method = $_SERVER['QUERY_STRING'];
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','product_param',substr($method,strpos($method, 'a=')), json_encode($_POST));
    }

    public function retError($msg = '操作失败')
    {
        $this->ajaxReturn(array("code" => "error", "msg" => $msg));
    }

    public function retSucc($data = '')
    {
        if($data){
            $this->ajaxReturn(array("code" => "success", "msg" => '操作成功', 'data'=>$data));
        } else {
            $this->ajaxReturn(array("code" => "success", "msg" => '操作成功'));
        }
    }

    public function uploadImage()
    {
        $name = 'goods_img';
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 0;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_');
        $upload->savePath = 'goods/';
        // 上传文件
        $info = $upload->upload();
        header('Content-Type:application/json');
        if (!$info) {// 上传错误提示错误信息
            $this->retError($upload->getError());
        } else {// 上传成功
            $url = 'https://sy.youngport.com.cn/data/upload/' . $info[$name]['savepath'] . $info[$name]['savename'];
            $this->retSucc($url);
        }
    }

    # 添加商品
    public function addProduct()
    {
        if (IS_POST) {
            $this->posts = I("");
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','add','数据', json_encode($this->posts));
            if (!$this->posts['goods_name']) $this->retError('商品名称不能为空!');
            if ($this->goodsModel->where(array("goods_name" => $this->posts['goods_name'], 'mid' => $this->userId, 'is_delete' => 0))->getField("goods_id")) {
                $this->retError('商品已存在，不能重复添加!');
            }
            $trade = $this->posts['trade'];     // 行业类别
            switch ($trade) {
                case 1:
                    $this->addStore();
                    break;
                case 2:
                    $this->addFood();
                    break;
                default:
                    $this->retError('行业类别错误!');
            }
        }
    }

    # 商品详情
    public function editInfo()
    {
        $params = I('');
        $goods_id = $params['goods_id'];
        if(!$goods_id){
            $this->retError();
        }
        $goods_data = $this->goodsModel->where(array('goods_id' => $goods_id))->find();
        if(!$goods_data){
            $this->retSucc();
        }
        $goods_data['goods_img'] = array($goods_data['goods_img1'],$goods_data['goods_img2'],$goods_data['goods_img3'],$goods_data['goods_img4'],$goods_data['goods_img5']);
        $goods_data['goods_img'] = array_filter($goods_data['goods_img']);
        $goods_data['goods_img'] = array_map(function ($str){
            if(strpos($str,'http') !== false){
                return $str;
            } else {
                return 'https://sy.youngport.com.cn'.$str;
            }
        }, $goods_data['goods_img']);
        # 商品图文描述
        $desc = $this->goods_desc->where(array('goods_id' => $goods_id))->select();
        foreach ($desc as $val) {
            $url = $this->checkHttp($val['url']);
            $desc_arr[] = $url;
        }
        $goods_data['desc_img'] = $desc_arr;
        if(!$desc_arr){
            $goods_data['desc_img'] = array($goods_data['pic_desc1'],$goods_data['pic_desc2'],$goods_data['pic_desc3']);
        }
        
        # 商品sku
        if($goods_data['is_sku']){
            $properties = $this->goods_sku_Model->field('properties_name,cost buy_price,original_price,price shop_price,quantity goods_number')->where(array('goods_id' => $goods_id))->select();
        } else {
            $properties = array(array(
                'properties_name' => '',
                'buy_price' => $goods_data['buy_price'],
                'original_price' => $goods_data['original_price'],
                'shop_price' => $goods_data['shop_price'],
                'goods_number' => $goods_data['goods_number'],
            ));
        }
        $goods_data['properties'] = $properties;
        $goods_data['group_name'] = $this->getGroupName($goods_data['group_id']);
        $this->retSucc($goods_data);
    }

    public function checkHttp($str)
    {
        if(strpos($str,'http') !== false){
            return $str;
        } else {
            return 'https://sy.youngport.com.cn'.$str;
        }
    }

    private function getGroupName($group_id)
    {
        return $this->goods_group_model->where(array('group_id'=> $group_id))->getField('group_name');
    }
    # 添加商品
    public function editProduct()
    {
        if (IS_POST) {
            $this->posts = I("");
            if (!$this->posts['goods_name']) $this->retError('商品名称不能为空!');
            if (!$this->posts['goods_id']) $this->retError('参数错误!');
            $trade = $this->posts['trade'];     // 行业类别
            switch ($trade) {
                case 1:
                    $this->editStore();
                    break;
                case 2:
                    $this->editFood();
                    break;
                default:
                    $this->retError('行业类别错误!');
            }
        }
    }

    # 添加便利店商品
    public function addStore()
    {
        if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->retError('商品条码至少6个字节!');
        $this->trade = 1;
        $this->add();
    }

    # 添加餐饮行业商品
    public function addFood()
    {
        $this->trade = 2;
        $this->add();
    }

    private function add()
    {
        # 商品展示图分解
        $goods_imgs = explode(',', $this->posts['goods_img']);
        if(!$goods_imgs) $this->retError('参数错误!');
        # 获取goods表入库数据
        $db_data = $this->getDbData();
        foreach ($goods_imgs as $k => $v) {
            $gkey = 'goods_img'.($k+1);
            $db_data[$gkey] = $v;
        }
        $db_data['window_img'] = $goods_imgs[0];
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','add','db_data', json_encode($db_data));
        M()->startTrans();
        # 商品规格
        $properties = json_decode(htmlspecialchars_decode($this->posts['properties']),true);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','add','sku——data', json_encode($properties));
        if(!is_array($properties))$this->retError('参数错误!');
        if(count($properties) > 1){
            # 多规格
            $db_data['is_sku'] = '1';
            # 将商品基本属性插入表
            $goods_id = $this->goodsModel->add($db_data);
            # 将SKU插入数据库
            foreach ($properties as $val) {
                $sku_data = array(
                    'goods_id' => $goods_id,
                    'properties' => $val['properties_name'],
                    'properties_name' => $val['properties_name'],
                    'original_price' => $val['original_price'],
                    'price' => $val['shop_price'],
                    'quantity' => $val['goods_number'],
                    'add_time' => time(),
                );
                if($this->trade == 1) $sku_data['cost'] = $val['buy_price'];
                $sku_res = $this->goods_sku_Model->add($sku_data);
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','add','sku——data', json_encode($sku_data));

            }
            # 更新商品表规格
            $this->update_other_info($goods_id);
        } else {
            # 单一规格
            $db_data['original_price'] = $properties[0]['original_price'];
            $db_data['shop_price'] = $properties[0]['shop_price'];
            $db_data['goods_number'] = $properties[0]['goods_number'];
            if($this->trade == 1) $db_data['buy_price'] = $properties[0]['buy_price'];
            # 入库
            $goods_id = $this->goodsModel->add($db_data);
        }
        # 图文描述入库
        if($this->posts['desc_img']){
            $this->add_desc($this->posts['desc_img'], $goods_id);
        }
        # 返回结果
        if($goods_id){
            M()->commit();
            $this->retSucc(array('goods_id'=>$goods_id));
        } else {
            M()->rollback();
            $this->retError();
        }
    }

    private function getDbData()
    {
        $arr = array(
            'mid' => $this->userId,
            'trade' => $this->trade,
            'group_id' => $this->posts['group_id'],
            'goods_name' => $this->posts['goods_name'],
            'goods_brief' => $this->posts['goods_brief'],
            'bar_code' => $this->posts['bar_code']?:'',
            'star' => $this->posts['star'],
            'put_xcx' => $this->posts['put_xcx'],
            'put_pos' => $this->posts['put_pos'],
            'put_two' => $this->posts['put_two'],
            'add_time' => time(),
        );

        return $arr;
    }

    # 编辑便利店商品
    public function editStore()
    {
        if ($this->posts['bar_code'] && !$this->check_barcode($this->posts['bar_code'])) $this->retError('商品条码至少6个字节!');
        $this->trade = 1;
        $this->edit();
    }

    # 餐饮行业商品
    public function editFood()
    {
        $this->trade = 2;
        $this->edit();
    }

    private function edit()
    {
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/goods/','edit','参数', json_encode($_POST));
        $goods_id = $this->posts['goods_id'];
        $db_data = $this->posts;
        $properties = json_decode(htmlspecialchars_decode($this->posts['properties']),true);
        if(!is_array($properties))$this->retError('参数错误!');
        if(count($properties) > 1){
            # 多规格
            $db_data['is_sku'] = '1';
            # 将商品基本属性插入表
            $this->goods_sku_Model->where(array('goods_id'=>$goods_id))->delete();
            # 将SKU插入数据库
            foreach ($properties as $val) {
                $sku_data = array(
                    'goods_id' => $goods_id,
                    'properties' => $val['properties_name'],
                    'properties_name' => $val['properties_name'],
                    'cost' => $val['buy_price'],
                    'original_price' => $val['original_price'],
                    'price' => $val['shop_price'],
                    'quantity' => $val['goods_number'],
                    'add_time' => time(),
                );
                if($this->trade == 1) $sku_data['cost'] = $val['buy_price'];
                $this->goods_sku_Model->add($sku_data);
            }
            # 更新商品表规格
            $this->update_other_info($goods_id);
        } else {
            # 单一规格
            $db_data['original_price'] = $properties[0]['original_price'];
            $db_data['shop_price'] = $properties[0]['shop_price'];
            $db_data['goods_number'] = $properties[0]['goods_number'];
            if($this->trade == 1) $db_data['buy_price'] = $properties[0]['buy_price'];
        }
        # 商品展示图分解
        $goods_imgs = explode(',', $this->posts['goods_img']);
        if(!$goods_imgs) $this->retError('参数错误!');
        $db_data = array_filter($db_data);
        for($i = 0; $i < 5; $i++){
            $key = 'goods_img'.($i+1);
            $db_data[$key] = $goods_imgs[$i]?:'';
        }
        $db_data['window_img'] = $goods_imgs[0];
        $this->goodsModel->where(array('goods_id' => $goods_id))->save($db_data);

        if($this->posts['desc_img']){
            $this->goods_desc->where(array('goods_id'=>$goods_id))->delete();
            $this->add_desc($this->posts['desc_img'], $goods_id);
        }

        $this->retSucc();
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
     * 更新商品表库存、进价、售价
     * @param $goodsId
     */
    private function update_other_info($goodsId)
    {
        $sku_rs = $this->goods_sku_Model->where(array("goods_id" => $goodsId))->field("min(original_price)original_price,min(cost)cost,min(price)price,sum(quantity)quantity")->find();
        $this->goodsModel->where(array("goods_id" => $goodsId))
            ->save(array("goods_number" => $sku_rs['quantity'], "buy_price" => $sku_rs['cost'], "shop_price" => $sku_rs['price'],"original_price" => $sku_rs['original_price']));
    }


    public function goodsList()
    {
        $keywords = I('keywords');
        $trade = I('trade');
        if ($keywords) $map['_string'] = '( g.goods_name like "%' . $keywords . '%") OR ( g.bar_code like "%' . $keywords . '%")';
        if ($trade) $map['g.trade'] = $trade;
        $map['g.is_delete'] = 0;
        $map['g.mid'] = $this->userId;
        $per_page = 100;//每页数量
        $page = I("page", "0");//页码,第几页
        $this->_lists_($map, $page, $per_page);
    }
    
    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     * @param $page 查询条件
     * @param $per_page 查询条件
     */
    private function _lists_($where = array(), $page, $per_page)
    {
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','test','查询条件', json_encode($where));
        $this->goodsModel
            ->alias("g")
            ->where($where);
        $count = $this->goodsModel->count();


        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("g.goods_id DESC");
        $field = 'g.goods_id,g.goods_name,g.shop_price,g.goods_number,g.sales,g.group_id,g.is_on_sale,g.window_img,g.put_xcx,g.put_pos,g.put_two';
        $this->goodsModel->field($field);
        $data_lists = $this->goodsModel->select();
        foreach($data_lists as $k=> &$v){
            
            $picture = $v['window_img'];
            if(preg_match("/\x20*https?\:\/\/.*/i",$v['window_img'])){
                $v['window_img'] = $picture;
            }else{
                $v['window_img'] = 'https://sy.youngport.com.cn'.$picture;
            }
        }

        $total = ceil($count / $per_page);//总页数
        $data_lists = array_values($data_lists);
        $this->retSucc(array(
            "total" => $total,
            "count" => $count,
            "data" => $data_lists,
        ));
    }

    /**
     * 系统信息列表处理方法,根据不同条件显示不同的列表
     * @param array $where 查询条件
     * @param $page 查询条件
     * @param $per_page 查询条件
     */
    private function _lists($where = array(), $page, $per_page)
    {
//        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','test','查询条件', json_encode($where));
        $this->goodsModel
            ->alias("g")
            ->where($where);
        $count = $this->goodsModel->count();


        $this->goodsModel
            ->alias("g")
            ->where($where)
            ->limit($page * $per_page, $per_page)
            ->order("g.goods_id DESC");
        $field = 'g.goods_id,g.goods_name,g.shop_price,g.goods_number,g.sales,g.group_id,g.is_on_sale,g.window_img,g.put_xcx,g.put_pos,g.put_two,g.trade';
        $this->goodsModel->field($field);
        $data_lists = $this->goodsModel->select();
        foreach($data_lists as $k=> &$v){
            $desc = $this->goods_desc->where(array('goods_id' => $v['goods_id']))->select();
            if ($desc) {
                $v['desc'] = $desc;
            }
            
            $picture = $v['window_img'];
            if(preg_match("/\x20*https?\:\/\/.*/i",$v['window_img'])){
                $v['window_img'] = $picture;
            }else{
                $v['window_img'] = 'https://sy.youngport.com.cn'.$picture;
            }
        }

        $total = ceil($count / $per_page);//总页数
        $data_lists = array_values($data_lists);
        $this->retSucc(array(
            "total" => $total,
            "count" => $count,
            "data" => $data_lists,
        ));
    }

    # 终端商品列表
    public function partList()
    {
        if(IS_POST){
            $trade = I('trade');
            $terminal = I('terminal');
            $sale_status = I('status'); // 是否上架
            $keywords = I('keywords');
            if(!$trade || !$terminal) $this->retError('参数为空!');
            if ($keywords) $map['_string'] = '( g.goods_name like "%' . $keywords . '%") OR ( g.bar_code like "%' . $keywords . '%")';
            if ($trade) $map['g.trade'] = $trade;
            switch ($terminal) {
                case 'xcx': // 小程序商品
                    $map['g.put_xcx'] = $sale_status;
                    break;
                case 'pos': // POS机商品
                    $map['g.put_pos'] = $sale_status;
                    break;
                case 'two': // 双屏商品
                    $map['g.put_two'] = $sale_status;
                    break;
                default:
                    $this->retError('参数错误!');
                    break;
            }
            $map['g.is_delete'] = 0;
            $map['g.mid'] = $this->userId;
            $per_page = 100;//每页数量
            $page = I("page", "0");//页码,第几页
            $this->_lists($map, $page, $per_page);
        }
    }


    # 终端商品列表
    public function otherList()
    {
        if(IS_POST){
            $trade = I('trade');
            $terminal = I('terminal');
            if(!$trade || !$terminal) $this->retError('参数为空!');
            if ($trade) $map['g.trade'] = $trade;
            switch ($terminal) {
                case 'xcx': // 小程序商品
                    $map['g.put_xcx'] = 0;
                    break;
                case 'pos': // POS机商品
                    $map['g.put_pos'] = 0;
                    break;
                case 'two': // 双屏商品
                    $map['g.put_two'] = 0;
                    break;
                default:
                    $this->retError('参数错误!');
                    break;
            }
            $map['g.is_delete'] = 0;
            $map['g.mid'] = $this->userId;
            $per_page = 100;//每页数量
            $page = I("page", "0");//页码,第几页
            $this->_lists($map, $page, $per_page);
        }
    }

    # 终端商品列表
    public function addOther()
    {
        if(IS_POST){
            $terminal = I('terminal');
            $goods_ids = I('goods_ids');
            if(!$goods_ids){
                $this->retError();
            }
            switch ($terminal) {
                case 'xcx': // 小程序商品
                    $str = 'put_xcx';
                    break;
                case 'pos': // POS机商品
                    $str = 'put_pos';
                    break;
                case 'two': // 双屏商品
                    $str = 'put_two';
                    break;
                default:
                    $this->retError('参数错误!');
                    break;
            }

            $res = M()->query("UPDATE `ypt_goods` SET {$str} = 1 WHERE goods_id IN ($goods_ids)");
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/test/','goods','sql', M()->getLastSql());
            $this->retSucc();
        }
    }

    # 更改商品的上下架状态
    public function changeSale()
    {
        if(IS_POST){
            $goods_id = I('goods_id');  // 商品
            $status = I('status');
            $terminal = I('terminal');
            if(!$terminal) $this->retError('参数为空!');
            if ($terminal=='xcx'&&$status==2) {
                if ($this->goodsModel->where(array('goods_id' => $goods_id,'trade'=>1,'put_xcx'=>1))->find()) {
                    if(!M('goods_desc_img')->where(array('goods_id'=>$goods_id))->find()){
                        $this->retError('缺少图文描述，不能上架!');
                    }

                }
            }

            if ($status==2) {
                if(!$this->goodsModel->where(array('goods_id' => $goods_id))->getField('goods_img1')){
                    $this->retError('缺少商品图片，不能上架!');
                }
            }
            
            switch ($terminal) {
                case 'xcx':
                    $data['put_xcx'] = $status;
                    break;
                case 'pos':
                    $data['put_pos'] = $status;
                    break;
                case 'two':
                    $data['put_two'] = $status;
                    break;
                default:
                    $this->retError('参数错误!');
                    break;
            }
            $res = $this->goodsModel->where(array('goods_id' => $goods_id))->save($data);
            if($res){
                $this->retSucc();
            } else {
                $this->retError('投放失败');
            }
        }
    }

    # 投放设置
    public function putSet()
    {
        if(IS_POST){
            $data = I('');
            $goods_id = $data['goods_id'];
            $put_set['put_xcx'] = $data['put_xcx'];
            $put_set['put_pos'] = $data['put_pos'];
            $put_set['put_two'] = $data['put_two'];
            $put_set['is_on_sale'] = 1;
            $res = $this->goodsModel->where(array('goods_id' => $goods_id))->save($put_set);
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/product/','product_param','putSet', $this->goodsModel->getLastSql());
            if(false !== $res){
                $this->retSucc();
            } else {
                $this->retSucc();
            }
        }
    }

    # 更改小程序属性
    public function editProperty()
    {
        if (IS_POST) {
            $data = I('');
            $goods_id = $data['goods_id'];
            $res = $this->goodsModel->where(array('goods_id' => $goods_id))->save($data);
            if ($res) {
                $this->retSucc();
            } else {
                $this->retError('修改失败');
            }
        }
    }

    # 删除商品
    public function goodsDelete()
    {
        $goods_id = I('goods_id');
        if (!$goods_id) $this->retError("商品id不能为空");
        $this->goodsModel->where(array("goods_id" => $goods_id))->save(array("is_delete" => "1"));
        $this->retSucc();
    }

    # 删除商品
    public function unPut()
    {
        $goods_id = I('goods_id');
        $terminal = I('terminal');
        if (!$goods_id) $this->retError("商品id不能为空");
        switch ($terminal) {
            case 'xcx':
                $data['put_xcx'] = 0;
                break;
            case 'pos':
                $data['put_pos'] = 0;
                break;
            case 'two':
                $data['put_two'] = 0;
                break;
            default:
                $this->retError('参数错误!');
                break;
        }
        $res = $this->goodsModel->where(array('goods_id' => $goods_id))->save($data);
        if($res){
            $this->retSucc();
        } else {
            $this->retError();
        }
    }

####################################################################################################################################################

}

