<?php

namespace Xcx\Controller;
use Xcx\Controller\ApibaseController;
use Think\Upload;
/**
 * @auth lxl
 * Class ShippingController 配送相关
 * @package Xcx\Controller
 */
class ShippingController extends ApibaseController
{
    /**
     * @auth lxl
     * 获取配送范围
     */
    public function get_shipping_range()
    {
        $this->ajaxReturn(array('code' => 'success', 'msg' => '请求成功', 'data'=>C('shipping_range')));
    }

    /**
     * 设置配送范围
     */
    public function shipping_range()
    {
        if (IS_POST) {
            ($shipping_range = I('shipping_range')) || err('参数错误');

            $data['shipping_type'] = 1;
            $data['shipping_range'] = $shipping_range;
            $res = M('merchants')
                ->where(array('uid'=>UID))
                ->save($data);
            file_put_contents('./data/log/ship/' . date("Y_m_") . 'ship.log', date("Y-m-d H:i:s") . '设置配送范围:MySQL返回：'.$res.'，数据：' .json_encode($data). PHP_EOL, FILE_APPEND | LOCK_EX);
            if ($res) {
                $this->ajaxReturn(array('code' => 'success', 'msg' => '设置成功'));
            } else {
                $this->ajaxReturn(array('code' => 'success', 'msg' => '距离未变'));
            }
        }
    }

    /**
     * 附近配送保存
     */
    public function shipping_range_new()
    {
        if (IS_POST) {
            $shipping = I('shipping');
            $shipping = json_decode(htmlspecialchars_decode($shipping), true);
            $data['shipping_type'] = 1;
            $res = M('merchants')
                ->where(array('uid'=>UID))
                ->save($data);
                // echo M('merchants')->getLastSql();
            file_put_contents('./data/log/ship/' . date("Y_m_") . 'ship.log', date("Y-m-d H:i:s") . '设置配送范围:MySQL返回：'.$res.'，数据：' .json_encode($data). PHP_EOL, FILE_APPEND | LOCK_EX);
            M('shipping_near')->where(array('uid'=>UID))->delete();
            if ($shipping) {
                foreach ($shipping as $key => $value) {
                    $arrayName = array(
                        'uid' => UID,
                        'shipping_qs' =>$value['shipping_qs'],
                        'shipping_ps'=>$value['shipping_ps'],
                        'shipping_free'=>$value['shipping_free'],
                        'begin_distance'=>$value['begin_distance'],
                        'end_distance'=>$value['end_distance'],
                        'add_time'=>time()
                        );
                    // dump($arrayName);
                    $result = M('shipping_near')->add($arrayName);
                }
                if ($result) {
                    $this->ajaxReturn(array('code' => 'success', 'msg' => '设置成功'));
                } else {
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '设置失败'));
                }
            }else{
               $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误')); 
            }
        }
    }
    /**
     * 区域配送保存
     */
    public function shipping_type_new()
    {
        if (IS_POST) {
            $type = I('type');
            $shipping_qs = I('shipping_qs');
            $shipping_ps = I('shipping_ps');
            $shipping_free = I('shipping_free');
            if ( ! in_array($type,array(1,2))) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误'));
            }
            $where['uid'] = UID;
            $where['province_id'] = array('neq', 0);
            $count = M('shipping_area')->where($where)->count();
            if($count){
                $map['uid'] = UID;
                $map['province_id'] = 0;
                M('shipping_area')->where($map)->delete();
            }else{
                $wh['uid'] = UID;
                $wh['province_id'] = array('eq', 0);
                $count = M('shipping_area')->where($wh)->count();
                if(!$count){
                    if($type == 2){
                        $data['uid'] = UID;
                        $data['add_time'] = time();
                        M('shipping_area')->add($data);
                        $this->ajaxReturn(array('code' => 'success', 'msg' => '区域设置成功'));
                    }
                }
            }
            if ($type==1) {
                M('merchants')->where(array('uid' => UID))->setField('shipping_type', $type);
            }else if($type == 2){
                // $arrayName = array('shipping_qs' => $shipping_qs,'shipping_type' =>$type,'shipping_ps'=>$shipping_ps,'shipping_free'=>$shipping_free);
                // dump($shipping_free);
                // M('merchants')->where(array('uid' => UID))->setField($arrayName);
                M('merchants')->query("UPDATE ypt_merchants SET shipping_type='".$type."' ,shipping_qs='".$shipping_qs."' , shipping_ps='".$shipping_ps."' , shipping_free='".$shipping_free."' WHERE uid=".UID);
                // echo M('merchants')->getLastSql();
            }
            
            file_put_contents('./data/log/ship/' . date("Y_m_") . 'ship.log', date("Y-m-d H:i:s") . '设置配送方式:'.$type.'，数据' . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->ajaxReturn(array('code' => 'success', 'msg' => '配送方式设置成功'));
        }
    }
    /**
     * 配送方式
     */
    public function shipping_type()
    {
        if (IS_POST) {
            $type = I('type');
            if ( ! in_array($type,array(1,2))) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误'));
            }
            $where['uid'] = UID;
            $where['province_id'] = array('neq', 0);
            $count = M('shipping_area')->where($where)->count();
            if($count){
                $map['uid'] = UID;
                $map['province_id'] = 0;
                M('shipping_area')->where($map)->delete();
            }else{
                $wh['uid'] = UID;
                $wh['province_id'] = array('eq', 0);
                $count = M('shipping_area')->where($wh)->count();
                if(!$count){
                    if($type == 2){
                        $data['uid'] = UID;
                        $data['add_time'] = time();
                        M('shipping_area')->add($data);
                        $this->ajaxReturn(array('code' => 'success', 'msg' => '区域设置成功'));
                    }
                }
            }
            M('merchants')->where(array('uid' => UID))->setField('shipping_type', $type);
            file_put_contents('./data/log/ship/' . date("Y_m_") . 'ship.log', date("Y-m-d H:i:s") . '设置配送方式:'.$type.'，数据' . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->ajaxReturn(array('code' => 'success', 'msg' => '配送方式设置成功'));
        }
    }
    /**
     * 商户地址
     */
    public function get_shipping()
    {
        if (IS_POST) {
            $data = M('merchants')
                ->where(array('uid' => UID))
                ->field('province,city,county,address,shipping_type,shipping_range,shipping_ps,shipping_qs,shipping_free')
                ->find();
            if ($data) {
                if($data['shipping_type'] == 1){
                    $shipping = M('shipping_near')->where(array('uid' => UID))->select();
                    $data['shipping'] = $shipping;
                    unset($data['shipping_ps']);
                    unset($data['shipping_qs']);
                    unset($data['shipping_free']);
                } elseif ($data['shipping_type'] == 2) {
                    unset($data['shipping_range']);
                    $shipping_data = M('shipping_area')->where(array('uid'=>UID))->field('id,province,city,county')->select();

                    $data['shipping_area'] = $shipping_data;
                    // 查找省份id为0的记录
                    $result = M('shipping_area')->where(array('uid'=>UID,'province_id'=>0))->find();
                    if($result){
                        unset($data['shipping_area']);
                    }
                }
                $this->ajaxReturn(array('code' => 'success', 'msg' => '成功', 'data' => $data));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数有误'));
            }
        }
    }

    /**
     * 设置配送区域
     */
    public function shipping_area()
    {
        if (IS_POST) {
            $area_id = I('area_id');
            $data = M('area')->where(array('id' => $area_id))->field('id,pid,name')->find();

            if (empty($data)) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误'));
            }

            if ($data['pid'] == 0) { // 只有省级区域
                $add_data['province'] = $data['name'];
                $add_data['province_id'] = $data['id'];
            } else {
                // 查询上级
                $pdata = M('area')->where(array('id' => $data['pid']))->field('id,pid,name')->find();

                if ($pdata['pid'] == 0) { // 只有省市区域
                    $add_data['province'] = $pdata['name'];
                    $add_data['province_id'] = $pdata['id'];
                    $add_data['city'] = $data['name'];
                    $add_data['city_id'] = $data['id'];
                } else {
                    // 查询上上级
                    $gdata = M('area')->where(array('id' => $pdata['pid']))->field('id,pid,name')->find();
                    // 三级区域齐全
                    $add_data['province_id'] = $gdata['id'];
                    $add_data['province'] = $gdata['name'];
                    $add_data['city_id'] = $pdata['id'];
                    $add_data['city'] = $pdata['name'];
                    $add_data['county_id'] = $data['id'];
                    $add_data['county'] = $data['name'];
                }
            }
            $add_data['uid'] = UID;
            $add_data['add_time'] = time();
            $res = M('shipping_area')->add($add_data);
            if ($res) {
                $id = M('shipping_area')->where(array('uid'=>UID))->max('id');
                $this->ajaxReturn(array('code' => 'success', 'msg' => '设置成功', 'data' => $id));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '插入失败'));
            }
        }
    }
    
    /**
     * 删除配送区域
     */
    public function delete_area()
    {
        if(IS_POST){
            $mod = M('shipping_area');
            $id = (int)I('id',0);
            if(!$id){
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误'));
            }
            $res = $mod->where(array('id'=>$id))->delete();
            if($res){
                $count = $mod->where(array('uid'=>UID))->count();
                if(!$count){
                    $data['uid'] = UID;
                    $data['add_time'] = time();
                    $mod->save($data);
                }
                $this->ajaxReturn(array('code' => 'success', 'msg' => '已删除'));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '未查询到数据'));
            }
        }
    }

    /**
     * 是否开业
     */
    public function is_open()
    {
        if (IS_POST) {
            $is_open = I('is_open');
            if (!in_array($is_open, array(0, 1))) {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '参数错误'));
            }

            $res = M('merchants')->where(array('uid' => UID))->setField('is_open', $is_open);
            if ($res) {
                $this->ajaxReturn(array('code' => 'success', 'msg' => 'success'));
            } else {
                $this->ajaxReturn(array('code' => 'error', '' => '设置失败'));
            }
        }
    }
    /**
     * 获取设置
     */
    public function is_set()
    {
        if (IS_POST) {
            $set = M('merchants')->where(array('uid' => UID))->field('is_open,shipping_type')->find();
        if (empty($set)) $this->ajaxReturn(array('code' => 'error', 'msg' => '该用户未开店'));
            $data['is_open'] = $set['is_open'];
            if (in_array($set['shipping_type'], array(1, 2))) {
                $data['shipping_set'] = 1;
            } else {
                $data['shipping_set'] = 0;
            }
//            $mid = M('merchants')->where(array('uid' => UID))->getField('id');
            $pic = M('banner')->where(array('mid' => UID))->select();
            if (empty($pic)) {
                $data['upload_img'] = 0;
            } else {
                $data['upload_img'] = 1;
            }
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'succ', 'data' => $data));
        }
    }
    /**
     * 获取设置
     */
    public function get_set()
    {
        if (IS_POST) {
            $set = M('merchants')->where(array('uid' => UID))->field('is_open,shipping_type,shipping_ps,shipping_qs,shipping_free,about_store,about_store,kefu_phone,shipping_img')->find();
	    if (empty($set)) $this->ajaxReturn(array('code' => 'error', 'msg' => '该用户未开店'));
            $data['is_open'] = $set['is_open'];
            $data['shipping_type'] = $set['shipping_type'];
            $data['kefu_phone'] = $set['kefu_phone']?$set['kefu_phone']:'';
            $data['about_store'] = $set['about_store']?$set['about_store']:'';
            $data['shipping_img'] = $set['shipping_img']?$set['shipping_img']:'';
            if ($set['shipping_type']==2) {
                $data['shipping_ps'] = $set['shipping_ps'];
                $data['shipping_qs'] = $set['shipping_qs'];
                $data['shipping_free'] = $set['shipping_free'];
            }elseif($set['shipping_type']==1){
                $shipping = M('shipping_near')->where(array('uid' => UID))->select();
                $data['shipping'] = $shipping;
            }
            $mid = $this->_get_mch_id(UID);
            $kefu = M('merchants_kefu')->where(array('mid' => $mid))->find();
            if ($kefu['qrcode']) {
                $data['qrcode'] = $kefu['qrcode'];
                $data['qrcode_id'] = $kefu['id'];
            }else{
                $data['qrcode'] = '';
                $data['qrcode_id'] = '';
            }
            
            if (in_array($set['shipping_type'], array(1, 2))) {
                $data['shipping_set'] = 1;
            } else {
                $data['shipping_set'] = 0;
            }
//            $mid = M('merchants')->where(array('uid' => UID))->getField('id');
            $pic = M('banner')->where(array('mid' => UID))->select();
            if (empty($pic)) {
                $data['upload_img'] = 0;
            } else {
                $data['upload_img'] = 1;
            }
            $this->ajaxReturn(array('code' => 'success', 'msg' => 'succ', 'data' => $data));
        }
    }

    /**
     * 保存店铺介绍
     */
    public function add_about()
    {
        if (IS_POST) {
            ($about_store = I('about_store')) || err('参数错误'); //店铺介绍
            $data['about_store']=$about_store;
            // $res = M('merchants')->where(array('uid' => UID))->setField($data);
            M('')->query("UPDATE ypt_merchants SET about_store='".$about_store."' WHERE uid=".UID);
            $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
        }
    }

    /**
     * 保存客服电话
     */
    public function add_phone()
    {
        if (IS_POST) {
            if ($phone = I('phone')) {
                if (!$this->check_phone($phone)) {
                    $this->ajaxReturn(array('code' => 'error', 'msg' => '手机号不符合规则'));
                }
            }else{
                $phone = '';
            } 
            
            M('')->query("UPDATE ypt_merchants SET kefu_phone='".$phone."' WHERE uid=".UID);
            $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
        }
    }

    public function check_phone($str)//手机号码正则表达试
    {
        return (preg_match("/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/",$str)||preg_match("/^\d{3}-\d{8}|\d{4}-\d{7}|\d{3}-\d{3}-\d{4}$/",$str))?true:false;
    }
    /**
     * 微信客服图片上传编辑 
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
            $upload->savePath = 'wechat/';
            $upload->saveName = uniqid;//保持文件名不变
            $info = $upload->upload();
            if (!$info)$this->error($upload->getError());
        }
        if($info['img']){
            $img = '/data/upload/' .  $info['img']['savepath'] . $info['img']['savename'];
        }
        $this->ajaxReturn(array('code'=>'success','msg'=>'上传成功','data'=>$img));
    }

    /**
     * 保存微信客服二维码
     */
    public function add_wechat()
    {
        ($qrcode = I('qrcode'))||$this->ajaxReturn(array('code' => 'error', 'msg' => '未找到图片链接'));
        $wechat = I('wechat');
        $mid = $this->_get_mch_id(UID);
        if (substr($qrcode,0,1)=='.') { 
            $qrcode = substr($qrcode,1);
        }
        $data = array('qrcode' => $qrcode,'wechat' =>$wechat,'mid'=>$mid,'add_time'=>time());
        if(M('merchants_kefu')->where(array('mid'=>$mid))->find()){
        	$res = M('merchants_kefu')->where(array('mid'=>$mid))->setField('qrcode',$qrcode);
        }else{
        	$res = M('merchants_kefu')->data($data)->add();
        }
        
        if ($res) { 
            $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
        } else {
            $this->ajaxReturn(array('code' => 'error', 'msg' => '保存失败'));
        } 
    }

    /**
     * 保存配送区域图片
     */
    public function add_shipping_img()
    {
        if (IS_POST) {
            ($shipping_img = I('shipping_img'))||$this->ajaxReturn(array('code' => 'error', 'msg' => '未找到图片链接'));
            $shipping_img = 'https://sy.youngport.com.cn'.$shipping_img;
            $res = M('merchants')->where(array('uid'=>UID))->setField('shipping_img',$shipping_img);
            if ($res) { 
                $this->ajaxReturn(array('code' => 'success', 'msg' => '保存成功'));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '保存失败'));
            } 
        }else{
            $this->ajaxReturn(array('code' => 'error', 'msg' => '不支持的请求方式'));
        }
    }

    /**
     * 删除客服二维码
     */
    public function del_wechat()
    {
        $id = I('qrcode_id');
        $mid = $this->_get_mch_id(UID);
        if (!$id) $this->ajaxReturn(array("code" => "error", "msg" => "图片id不能为空"));
        if(M('merchants_kefu')->where(array("id" => $id,'mid'=>$mid))->delete()){
            $this->ajaxReturn(array("code" => "success", "msg" => "删除成功"));
        }else{
            $this->ajaxReturn(array("code" => "error", "msg" => "删除失败"));
        }
        
    }

    /**
     * 获取商家ID
     * @Param uid 商家uid
     */
    public function _get_mch_id($uid)
    {
        $id = M('merchants')->where(array('uid'=>$uid))->getField('id');
        return $id;
    }
}