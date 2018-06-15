<?php
namespace Api\Controller;

use Think\Controller;
use Think\Upload;

/**
 * 小程序商品图片上传
 * Class ReceiveController
 * @package Common\Controller
 */
class  ReceiveController extends Controller
{
    public function index()
    {
        echo 1;
    }
    # 多文件上传
    public function upload_into()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'goods/'; // 设置附件上传（子）目录
        $upload->saveName = time().'_'.mt_rand();
        // 上传文件
        $info = $upload->upload();
//        $this->ajaxReturn(array($_FILES,$_POST,$info));exit;
        if ($info) {
            $data['deleteType'] = 'DELETE';
            $data['deleteUrl'] =  './data/upload/'.$info[0]['savepath'] . $info[0]['savename'];
            $data['name'] = $_FILES['files']["name"][0];
            $data['size'] = $_FILES['files']["size"][0];
            $data['thumbnailUrl'] =  'http://'.$_SERVER['HTTP_HOST'].'/data/upload/'.$info[0]['savepath'] . $info[0]['savename'];
            $data['type'] = $_FILES['files']["type"][0];
            $data['url'] =  'http://'.$_SERVER['HTTP_HOST'].'./data/upload/'.$info[0]['savepath'] . $info[0]['savename'];
            echo json_encode(array('files'=>array($data)));
            exit();
        } else {
            $data['type'] = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }
    # 单文件
    public function uploadInto()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'goods/'; // 设置附件上传（子）目录
        $upload->saveName = time().'_'.mt_rand();
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            foreach ($info as $k => $v) {
                $name = $k;
            }
            $data['type'] = 1;
            $data['name'] = $name;
            $data['path'] =  './data/upload/'.$info[$name]['savepath'] . $info[$name]['savename'];
            $data['url'] =  'https://'.$_SERVER['HTTP_HOST'].'./data/upload/'.$info[$name]['savepath'] . $info[$name]['savename'];
            $str = json_encode($data);
//            redirect("http://agent.youngport.com.cn/index.php?g=Api&m=Receive&a=returnData&str=".$str);
            exit($str);
        } else {
            $data['type'] = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }
}