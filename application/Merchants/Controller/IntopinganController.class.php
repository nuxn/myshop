<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * Class IntoxdlController
 * @package Merchants\Controller
 */
class IntopinganController extends AdminbaseController
{
    protected $merchants;
    protected $merchants_users;
    protected $merchants_pingan;

    function _initialize()
    {
        parent::_initialize();
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_pingan =M("merchants_pingan");
    }
    /**
     * 进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name');
        if($merchant_name){
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        $count = $this->merchants_pingan->join('w left join ypt_merchants m on w.mid=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $info = $this->merchants_pingan
            ->field('m.merchant_name,w.*')
            ->join('w left join ypt_merchants m on w.mid=m.id')
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
            if(!$data['mid'] || !$data['sub_mchid']){
                $this->error('参数不全');
            }
            $check = $this->merchants_pingan->where(array('mid' => $data['mid']))->find();
            $find = $this->merchants->where(array('id'=>$data['mid']))->find();
            if($check){
                $this->error('已存在');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $data['mchid'] = '1420218502';
            $data['add_time'] = time();
            $data['update_time'] = time();
            $res = $this->merchants_pingan->add($data);
            if($res){
                $this->redirect(U('index'));
            } else{
                $this->success('未作改动');
            }
        }else{
            $mid = I("id",'');
            $this->assign("mid", $mid);
            $this->display();
        }
    }
    public function edit()
    {
        if (IS_POST) {
            $data = I('post.');
            if(!$data['mid'] || !$data['sub_mchid']){
                $this->error('参数不全');
            }
            $data['update_time'] = time();
            $this->merchants_pingan->save($data);
            $this->redirect(U('index'));
        }else{
            $id = I('id');
            $info = $this->merchants_pingan->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->display();
        }
    }

    private function writeLog($file_name, $title, $param, $json=true)
    {
        $path = $this->get_date_dir();
        if($json){
            $param = json_encode($param, JSON_UNESCAPED_UNICODE);
        }
        file_put_contents($path . date("Y_m_") . $file_name, date("Y-m-d H:i:s") . $title.':'. $param . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function get_date_dir($path = '/data/log/leShua/')
    {
        $Y = $_SERVER['DOCUMENT_ROOT'] . $path . date("Y-m");
        if (file_exists($Y)) {
//            echo '存在';
        } else {
            mkdir($Y, 0777, true);
        }

        return $Y . '/';
    }
}
