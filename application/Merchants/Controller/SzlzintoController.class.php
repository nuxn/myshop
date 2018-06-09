<?php
namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 商户进件(入驻)
 * 获取微众分配的商户ID用于支付
 * Class UpwzadminController
 * @package Merchants\Controller
 */
class SzlzintoController extends AdminbaseController
{
    protected $shopcates;
    protected $merchants;
    protected $merchants_sz;
    protected $merchants_users;
    private $appid = '2017071207730667';
    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users =M("merchants_users");
        $this->merchants_sz = M("merchants_szlzwx");
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
        $count = $this->merchants_sz->join('w left join ypt_merchants m on w.mid=m.id')->where($map)->order('w.id desc')->count();

        $page = $this->page($count, 20);
        $info = $this->merchants_sz
            ->field('w.id,w.mid,w.mch_id,w.ali_mchid,m.merchant_name,w.rate,w.ali_token')
            ->join('w left join ypt_merchants m on w.mid=m.id')
            ->where($map)
            ->order('w.mid desc')
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
            if(!$data['mid']){
                $this->error('参数不全');
            }
            $check = $this->merchants_sz->where(array('mid' => $data['mid']))->find();
            $find = $this->merchants->where(array('id'=>$data['mid']))->find();
            if($check){
                $this->error('已存在');
            }
            if(!$find){
                $this->error('系统中不存在该商户');
            }
            $res = $this->merchants_sz->add($data);
            if($res){
                $this->redirect(U('Szlzinto/index'));
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
            $this->display("add1");
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
            if(!$data['mid'] || !$data['mch_id']){
                $this->error('参数不全');
            }
            unset($data['id']);
            $this->merchants_sz->where(array('id'=>$id))->save($data);
            $this->redirect(U('Szlzinto/index'));
        } else {
            $id = I('id');
            $info = $this->merchants_sz->where(array('id' => $id))->find();
            $this->assign('data', $info);
            $this->assign('id', $id);
            $this->display();
        }
    }

    public function getoken()
    {
        $mid = I('mid');
        $url = 'https://sy.youngport.com.cn/index.php?g=pay&m=Szlzpay&a=getoken&mid='.$mid;
        $ali_url = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id='.$this->appid.'&redirect_uri='.urlencode($url);
        vendor("phpqrcode.phpqrcode");

        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 5;           //生成图片大小
        //生成二维码图片
        \QRcode::png($ali_url,false,$errorCorrectionLevel, $matrixPointSize, 2);
    }
}
