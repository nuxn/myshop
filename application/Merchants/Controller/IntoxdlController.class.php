<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * Class IntoxdlController
 * @package Merchants\Controller
 */
class IntoxdlController extends AdminbaseController
{
    protected $merchants;
    protected $merchants_users;
    protected $merchants_xdl;
    private $version = 'V1.0.1'; // 签名方式
    private $orgNo;
    private $mercId;
    private $signKey;
    private $url;

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_xdl =M("merchants_xdl");
        $this->orgNo = '7170';
        $this->mercId = '800584000001927';
        $this->orgNo = '7170';
        $this->signKey = '7170';
        $this->url = 'http://sandbox.starpos.com.cn/emercapp';
    }

    /**
     * 进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name');
        $merc_id = I('mercId');
        if($merchant_name){
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        if($merc_id){
            $map['w.mercId'] = $merc_id;
            $formget['mercId'] = $merc_id;
        }
        $count = $this->merchants_xdl->join('w left join ypt_merchants m on w.m_id=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $info = $this->merchants_xdl
            ->field('w.id,w.m_id,w.orgNo,w.trmNo,w.mercId,w.signKey,w.wx_rate,w.ali_rate,w.debit_rate,w.credit_rate,m.merchant_name')
            ->join('w left join ypt_merchants m on w.m_id=m.id')
            ->where($map)
            ->order('w.id desc')
            ->limit($page->firstRow , $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("info",$info);
        $this->assign("formget",$formget);
        $this->display();
    }


    /**
     * 添加进件
     */
    public function add()
    {
        if (IS_POST) {
            $data = I("post.");
            if(!$data['m_id']){
                $this->error('参数不全');
            }
            $check = $this->merchants_xdl->where(array('m_id' => $data['m_id']))->find();
            $find = $this->merchants->where(array('id'=>$data['m_id']))->find();
            if($check){
                $this->error('已存在');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $res = $this->merchants_xdl->add($data);
            if($res){
                $this->redirect(U('Intoxdl/index'));
            } else{
                $this->success('未作改动');
            }
        }else{
            $merchant_id=$_GET['id'];
            //$merchant_id=53;
            $list=M('Merchants')->where("id='{$merchant_id}'")->find();
            $uid=$list['uid'];
            $phone=M('Merchants_users')->where("id='{$uid}'")->find();
            $this->assign('phone',$phone);
            $this->assign('list',$list);
            $this->assign('id',$merchant_id);
            $merchants_mpay_data=M('merchants_mdaypay')->where(array('merchant_id'=>$merchant_id))->find();
            $this->assign('data',$merchants_mpay_data);
            $this->display();
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        if(IS_POST){
            $data = I('post.');
            $id = I('id');
            if(!$data['m_id']){
                $this->error('参数不全');
            }
            unset($data['id']);
            if($this->merchants_xdl->where(array('id'=>$id))->save($data)){
                $this->redirect(U('Intoxdl/index'));
            } else {
                $this->error('未修改');
            }
        } else {
            $id = I('id');
            $info = $this->merchants_xdl->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

    // 安心签签约
    public function qianyue()
    {
            $id = I('id');
//            $this->getInfo($id);
            $params['serviceId'] = '6060105';
            $params['version'] = $this->version;
            $params['mercId'] = $this->mercId;
            $params['orgNo'] = $this->orgNo;
            $params['signValue'] = $this->getSign($params);
            $return = $this->requestPost(json_encode($params));
            echo $this->url;
            echo '<br/>';
            echo json_encode($params);
            dump($return);
    }

    // 安心签签约查询
    public function qianyueq()
    {
            $id = I('id');
            $this->getInfo($id);
            $params['serviceId'] = '6060106';
            $params['version'] = $this->version;
            $params['mercId'] = $this->mercId;
            $params['orgNo'] = $this->orgNo;
            $params['signValue'] = $this->getSign($params);
            $return = $this->requestPost(json_encode($params));
            echo $this->url;
            echo '<br/>';
            echo json_encode($params);
            dump($return);
    }

    private function getSign($params)
    {
        ksort($params);
        $str = '';
        foreach ($params as $v) {
            $str .= $v;
        }

        return md5($str . $this->signKey);
    }

    private function getInfo($id)
    {
        $re = $this->merchants_xdl->where(array('id' => $id))->find();
        $this->orgNo = $re['orgNo'];
        $this->mercId = $re['mercId'];
        $this->signKey = $re['signKey'];
        return $re;
    }
    # 发送请求
    private function requestPost($data, $second = 20)
    {
        $header = array("Content-type:application/json;charset=UTF-8");
        //初始化curl
        $curl = curl_init();
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //post提交方式
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl
        $res = curl_exec($curl);
        //返回结果
        if ($res) {
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            echo $error;
            curl_close($curl);
            return false;
        }
    }

}
