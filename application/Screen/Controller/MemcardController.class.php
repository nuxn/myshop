<?php

namespace Screen\Controller;

use Common\Controller\AdminbaseController;
use Think\Page;

class MemcardController extends AdminbaseController
{

    protected $memcard;

    public function index()
    {
        $model = M('screen_memcard');

        $start_time = I("start_time");
        $end_time = I("end_time");
        if (strtotime($start_time) > strtotime($end_time)) {
            $this->error("开始时间不能大于结束时间");
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map['add_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        } else {
            if ($start_time) {
                $map['add_time'] = array('gt', strtotime($start_time));
                $this->assign('start_time', $start_time);
            }

            if ($end_time) {
                $map['add_time'] = array('lt', strtotime($end_time));
                $this->assign('end_time', $end_time);
            }
        }

        $cardname = I("cardname");
        if ($cardname != '') {
            $map['cardname'] = array('like', "%{$cardname}%");
            $this->assign('cardname', $cardname);
        } else {
            $this->assign('cardname', '');
        }

        $userphone = I("userphone");
        if ($userphone) {
            $map['userphone'] = array('like', "%$userphone%");
            $this->assign('userphone', $userphone);
        }
        $count = $model->where($map)->count();
        $page = $this->page($count, 15);


        $data = $model->field('m.*,u.user_name')->join('m left join ypt_merchants_users u on u.id = m.mid')->where($map)->order("m.add_time  desc")->limit($page->firstRow, $page->listRows)->select();
//        $model->limit($page->firstRow, $page->listRows)->order("m.add_time desc");
        $this->assign('data', $data);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function delete()
    {
        $id = I('id');
        M()->startTrans();
        $card_info = M('screen_memcard')->where("id=$id")->find();
        if(!$card_info){
            $this->error('系统问题');
        } else {
            $mem_id = M('screen_memcard_use')->field('memid')->where("memcard_id=$id")->select();
            if($mem_id){
                $mem_arr = array();
                for ($i = 0; $i < count($mem_id); $i++) {
                    $mem_arr[] = $mem_id[$i]['memid'];
                }
                $id_str = implode($mem_arr,',');
                 M('screen_mem')->where(array('id' => array('in', $id_str)))->delete();
            }
            $this->del_card($id);
            M('screen_memcard_level')->where("c_id=$id")->delete();
            $use_res = M('screen_memcard_use')->where("memcard_id=$id")->delete();
            $set_res = M('screen_cardset')->where("c_id=$id")->delete();
            $cad_res = M('screen_memcard')->where("id=$id")->delete();
            if($cad_res){
                M()->commit();
                $this->deleteWx($card_info['card_id']);
                $this->success("删除成功");
            } else {
                M()->rollback();
                $this->error("删除失败了");
            }
        }
    }

    public function del_card($id)
    {
        $this->token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/code/unavailable?access_token=$this->token";
        $use_res = M('screen_memcard_use')->field('card_id,card_code')->where("memcard_id=$id")->select();
        if($use_res){
            foreach($use_res as $val){
                $data['card_id'] = $val['card_id'];
                $data['code'] = $val['card_code'];
                $data['reason'] = urlencode('此卡停用');
                $result = request_post($create_card_url, urldecode(json_encode($data)));
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Screen/','memcard','设置卡券失效', json_encode($data).PHP_EOL.$result);
            }
        } else {
            return;
        }

    }

    public function deleteWx($card_id)
    {
        $url = "https://api.weixin.qq.com/card/delete?access_token=$this->token";
        $data['card_id'] = $card_id;
        $res = request_post($url, json_encode($data));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/member/','delete','参数', json_encode($data));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/member/','delete','结果', $res);
    }
    
    public function edit_img()
    {
        if(IS_POST){
            $post = $_POST;
            $id = $post['id'];
            $bgimg = $post['bgimg'];
            $logoimg = $post['logoimg'];
            if(empty($bgimg) && empty($logoimg)){
                $this->success('未修改',U('index'));die;
            }
            if($bgimg){
                $post['bgimg'] = $this->uploadImg($bgimg);
            }
            if($logoimg){
                $post['logoimg'] = $this->uploadImg($logoimg);
            }
            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
            $curl_datas = $this->privcreateEditJson($post);
            file_put_contents('./data/log/member_bg.log', date("Y-m-d H:i:s") .  '修改背景' .$curl_datas . PHP_EOL, FILE_APPEND | LOCK_EX);

            $result = request_post($create_card_url, $curl_datas);
            // 将返回数据转化为数组
            $result = object2array(json_decode($result));
            if($result['errcode'] == '0'){
                $save = array('logoimg'=>"$logoimg",'bgimg'=>"$bgimg");
                $save = array_filter($save);
                M('screen_memcard')->where(array('id' => $id))->save($save);
                $this->success('修改成功',U('index'));die;
            }
            file_put_contents('./data/log/member_bg.log', date("Y-m-d H:i:s") .  '修改结果' .json_encode($result) . PHP_EOL, FILE_APPEND | LOCK_EX);

            $this->error('修改失败',U('index'));
        } else {
            $id = I('id');
            $info = M('screen_memcard')->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

    public function privcreateEditJson($post)
    {
        $curl_datas = array(
            "card_id" => urlencode($post['card_id']),
            "member_card" => array(
                "base_info" => array(
                    "code_type" => "CODE_TYPE_QRCODE", //CODE_TYPE_TEXT,CODE_TYPE_BARCODE,CODE_TYPE_QRCODE
                ),
            )
        );
        if(!empty($post['bgimg'])){
            $curl_datas['member_card']['background_pic_url'] = urlencode($post['bgimg']);
        }
        if(!empty($post['logoimg'])){
            $curl_datas['member_card']['base_info']['logo_url'] = urlencode($post['logoimg']);
        }

        return urldecode(json_encode($curl_datas));
    }

    public function upload_into()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'memcard/'; // 设置附件上传（子）目录
        // 上传文件
        $name = $_POST['data'];
        $info = $upload->upload();
        if ($info) {
            $data['type'] = 1;
            $data['name'] = $name;
            $data['path'] =  '/data/upload/'.$info[$name]['savepath'] . $info[$name]['savename'];
            echo json_encode($data);
            exit();
        } else {
            $data['type'] = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }

    public function uploadImg($url)
    {
        $arr=array();
        $arr['buffer']='@'.$_SERVER['DOCUMENT_ROOT'].$url;
//        $arr['buffer']='@'.$url;
        $url_getlog="https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".get_weixin_token();
        $result = request_post($url_getlog, $arr);
        file_put_contents('./data/log/member.log', date("Y-m-d H:i:s") .  '上传图片' . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
        $result = json_decode($result, true);

        return $result['url'];
    }

    public function store()
    {
        $store = M('merchants_wxstore')->select();
        $this->assign('store', $store);
        $this->display();
    }

    public function check()
    {
        $poi_id = I('poi_id');
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/cgi-bin/poi/getpoi?access_token=$token";
        $curl_datas = '{"poi_id":'.$poi_id.'}';
        $result = request_post($create_card_url, $curl_datas);
        // 将返回数据转化为数组
        $result = json_decode($result, true);
        $this->assign('info', $result['business']['base_info']);
        $this->display();
    }

}