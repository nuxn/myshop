<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 钱方支付对接
 * by lxl
 * Class QianFangadminController
 * @package Merchants\Controller
 */
class QianFangadminController extends AdminbaseController
{
    protected $header;

    /**
     * 钱方进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name', 0);
        $map = 1;
        if(!empty($merchant_name)){
            $map = array('b.merchant_name' => array('LIKE', "%$merchant_name%"));
            $formget['merchant_name'] = $merchant_name;
        }
        $data = M('merchants_upqf')
            ->field("a.*,b.merchant_name")
            ->join("a left join ypt_merchants b on a.mid=b.id")
            ->where($map)
            ->select();
        $this->assign('data', $data);
        $this->assign('formget', $formget);
        $this->display();
    }

    /**
     * 钱方进件
     */
    public function add()
    {
        if (IS_POST) {
            $id = I('id', 0);
            $mid = I('mid', 0);
            $model = M('merchants_upqf');
            if($id == 0){
                $qf_mchid = I('qf_mchid');
                if (!$mid || !M('merchants')->where("id={$mid}")->find()) $this->error("商户id不存在");
                if (!$qf_mchid) $this->error("子商户号为空");
                $data['mid'] = $mid;
                $data['qf_mchid'] = $qf_mchid;
                $data['add_time'] = time();
                $data['updata_time'] = time();
                $model->add($data);
                $this->success();
                exit;
            } else {
                $qf_mchid = I('qf_mchid');
                if (!$mid || !M('merchants')->where("id={$mid}")->find()) $this->error("商户id不存在");
                if (!$qf_mchid) $this->error("子商户号为空");
                $data['mid'] = $mid;
                $data['qf_mchid'] = $qf_mchid;
                $data['updata_time'] = time();
                $res = $model->where(array('id' => $id))->save($data);
                if($res){
                    $this->success();
                } else {
                    $this->error("失败");
                }
                exit;
            }
        }
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = I('id');
        $model = M('merchants_upqf');
        if(IS_POST){

        } else {
            $data = $model->where(array('id' => $id))->find();
            $this->assign('data', $data);
            $this->display("add");
        }
    }
    /**
     * 编辑
     */
    public function detail()
    {
        $id = I('id');
        $model = M('merchants');
        $data = $model->where(array('id' => $id))->find();
        if(strpos($data['positive_id_card_img'], 'data') == false){
            $data['positive_id_card_img'] = '/data/upload/' . $data['positive_id_card_img'];
        }
        if(strpos($data['id_card_img'], 'data') == false){
            $data['id_card_img'] = '/data/upload/' . $data['id_card_img'];
}
        if(strpos($data['business_license'], 'data') == false){
            $data['business_license'] = '/data/upload/' . $data['business_license'];
}
        $this->assign('data', $data);
        $this->display();
    }

    public function upload()
    {
        $id = I('id');

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_');
        $upload->savePath = 'merchants/';
        // 上传文件
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            echo json_encode(array('code' => 'error', 'msg' => $upload->getError()));
        } else {// 上传成功
            $host = 'https://' . $_SERVER['HTTP_HOST'];
            $url = $host . '/data/upload/' . $info[$id]['savepath'] . $info[$id]['savename'];
            echo json_encode(array('code' => 'success', 'msg' => $url));
        }
    }

    /**
     *    作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . C('KEY');
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
//        $String = strtoupper($String);
        return $String;
    }

    /**
     *    作用：格式化参数，签名过程需要使用
     */
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *    作用：以post方式提交xml到对应的接口url
     */
    public function postCurl($data, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //运行curl
        $data = curl_exec($ch);
        //curl_close($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }
}