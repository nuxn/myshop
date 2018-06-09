<?php

namespace Api\Controller;

use Common\Controller\ApibaseController;
use think\controller;

class CloudController extends ApibaseController
{
    protected $cloudModel;
    protected $server;
    protected $secret_key;
    protected $speak_url;
    protected $cloud_token;
    private $pay_model;
    public function __construct()
    {
        parent::__construct();
        $this->cloudModel = M('cloud_device');
        $this->pay_model = M('pay');
        $this->server = 'http://cloudprint.easyprt.com/o2o-print/print.php';//云打印请求地址
        $this->secret_key = 'zlbz-cloud';//云打印请求密钥
        //$this->speak_url = 'http://101.201.55.12/';//旧打喇叭请求地址
        $this->speak_url = 'http://39.106.131.149/';//新打喇叭请求地址
        //$this->cloud_token = '100013483324';//测试token
        $this->cloud_token = '168966599023';//正式token
    }

    //云设备列表
    public function index()
    {
        if(IS_POST){
            $uid = $this->userId;
            $data = $this->cloudModel->where(array('uid'=>$uid))->select();
            if($data){
                foreach ($data as &$v) {
                    $v['connect_status'] = $v['type']==1?$this->QueryState($v['id_number']):'0';
                }
                $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
            }else{
                $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>array()));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }
    }

    //添加设备
    public function add()
    {
        if(IS_POST){
            $name = I('name','','trim');
            if(empty($name)) $this->ajaxReturn(array('code'=>'error','msg'=>'未填写设备名称'));
            $data['name'] = $name;

            $model = I('model','','trim');
            if(empty($model)) $this->ajaxReturn(array('code'=>'error','msg'=>'未填写设备型号'));
            $data['model'] = $model;

            $id_number = I('id_number','','trim');
            if(empty($id_number)) $this->ajaxReturn(array('code'=>'error','msg'=>'未填写设备id/编号'));
            $data['id_number'] = intval($id_number);

            $type = I('type');
            if(empty($type)){
                $this->ajaxReturn(array('code'=>'error','msg'=>'未传入设备类型'));
            }elseif($type == 1){//云打印机
                $printer_style = I('printer_style');
                if(empty($printer_style)) $this->ajaxReturn(array('code'=>'error','msg'=>'未选择打印模式'));
                $data['printer_style'] = $printer_style;
            }elseif($type == 2){//云喇叭
                $volume = I('volume','','trim');
                if(!isset($volume)){
                    $this->ajaxReturn(array('code'=>'error','msg'=>'未填写设备音量'));
                }
                $data['volume'] = intval($volume);
                $this->bind($id_number,1);
                $this->change_vol($id_number,$data['volume']);
            }
            $data['type'] = $type;
            $data['uid'] = $this->userId;
            $data['add_time'] = time();
            if($id=$this->cloudModel->add($data)){
                $this->ajaxReturn(array('code'=>'success','msg'=>'添加成功','data'=>array('id'=>$id)));
            }else{
                file_put_contents('./data/log/cloud/'.date('Y_m').'_bind.log', date("Y-m-d H:i:s") .',uid:'.$this->userId.',添加失败请求参数:' . json_encode(I('')) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn(array('code'=>'error','msg'=>'添加失败'));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }
    }

    //编辑设备
    public function edit()
    {
        if(IS_POST){
            $id = I('id');
            if(!$id) $this->ajaxReturn(array('code'=>'error','msg'=>'id未传入'));
            $type = $this->cloudModel->where(array('id'=>$id))->getField('type');
            $name = I('name','','trim');
            if($name) $data['name'] = $name;

            $model = I('model','','trim');
            if($model) $data['model'] = $model;

            $id_number = I('id_number','','trim');
            if($id_number) $data['id_number'] = intval($id_number);

            $printer_style = I('printer_style');
            if($printer_style) $data['printer_style'] = $printer_style;

            $volume = I('volume','','trim');
            if(isset($volume)) $data['volume'] = intval($volume);

            if($type == 2){
                $this->change_vol($id_number,$volume);
            }

            $res = $this->cloudModel->where(array('id'=>$id,'uid'=>$this->userId))->save($data);
            if($res!==false){
                $this->ajaxReturn(array('code'=>'success','msg'=>'修改成功'));
            }else{
                file_put_contents('./data/log/cloud/'.date('Y_m').'_api.log', date("Y-m-d H:i:s") .',res:'.$res. ',uid:'.$this->userId.',修改失败请求参数:' . json_encode(I('')) . PHP_EOL, FILE_APPEND | LOCK_EX);
                $this->ajaxReturn(array('code'=>'error','msg'=>'修改失败'));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }
    }

    //删除设备
    public function delete()
    {
        if(IS_POST){
            $id = I('id');
            if(!$id) $this->ajaxReturn(array('code'=>'error','msg'=>'id未传入'));
            $device = $this->cloudModel->where(array('id'=>$id))->field('id_number,type')->find();
            if($device['type'] == 2){
                $this->bind($device['id_number'],0);
            }
            if($this->cloudModel->where(array('id'=>$id,'uid'=>$this->userId))->delete()){
                $this->ajaxReturn(array('code'=>'success','msg'=>'删除成功'));
            }else{
                $this->ajaxReturn(array('code'=>'error','msg'=>'删除失败'));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }
    }

    //订单打印
    public function order_printer()
    {
        if(IS_POST){
            $remark = I('remark');
            if(!$remark) $this->ajaxReturn(array('code'=>'error','msg'=>'remark is empty'));
            $pay = $this->pay_model->where(array('remark'=>$remark))->field('merchant_id,checker_id')->find();
            $uids[] = M('merchants')->where(array('id'=>$pay['merchant_id']))->getField('uid');
            if($pay['checker_id']) $uids[] = $pay['checker_id'];
            //获取设备信息
            $devices = $this->cloudModel->alias('c')
                ->where(array('uid'=>array('in',$uids),'type'=>1))
                ->field('id,type,name')
                ->select();
            if($devices){
                foreach($devices as &$v){
                    $state = $this->print_out($remark,$v['id'],false,true);
                    $data[] = array(
                        'name'=>$v['name'],
                        'code'=>$state,
                        'msg'=>$this->state($state)
                    );
                }
                $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$data));
            }else{
                $this->ajaxReturn(array('code'=>'error','msg'=>'未绑定云打印机'));
            }
        }else{
            $this->ajaxReturn(array('code'=>'error','msg'=>'请求错误'));
        }
    }

    /**
     * 云打印打印订单 & 云喇叭播报
     * @param $order_sn='' 流水号
     * */
    public function printer($order_sn='')
    {
        $remark = I('remark',$order_sn);
        if(!$remark) return;
        $pay = M('pay p')
            ->join('ypt_merchants m on m.id=p.merchant_id')
            ->where(array('remark'=>$remark))
            ->field('p.merchant_id,p.checker_id,m.mid')
            ->find();
        $uids[] = M('merchants')->where(array('id'=>$pay['merchant_id']))->getField('uid');
        if($pay['checker_id']) $uids[] = $pay['checker_id'];
        if($pay['mid']) $uids[] = M('merchants')->where(array('id'=>$pay['mid']))->getField('uid');
        //获取设备信息
        $devices = $this->cloudModel->alias('c')
            ->join('ypt_merchants_users u on u.id=c.uid','left')
            ->where(array('uid'=>array('in',$uids)))
            ->field('c.id,c.type,c.id_number,u.cloud_voice')
            ->select();
        if($devices){
            foreach($devices as &$v){
                if($v['type']==1){
                    $this->print_out($remark,$v['id']);
                }elseif($v['type']==2 && $v['cloud_voice']==1){
                    $this->push_message($remark,$v['id_number']);
                }
            }
        }
    }

    /**
     * --------------------------------------------云打印--------------------------------------------------------
     * */

    /**
     * 测试打印
     * @param id 数据id
     * */
    public function test_printer()
    {
        $id = I('id');
        $device_id = $this->cloudModel->where(array('id'=>$id))->getField('id_number');
        $status=$this->QueryState($device_id);
        file_put_contents('./data/log/cloud/'.date('Y_m').'_print.log', date("Y-m-d H:i:s") .',数据id:'.$id.',设备id:'.$device_id.',Test:QueryState:state:'.$this->state($status).'('.$status.')' . PHP_EOL, FILE_APPEND | LOCK_EX);
        if($status!=1) $this->ajaxReturn(array('code'=>'error','msg'=>$this->state($status)));
        $this->print_out(null,$id,1);
    }

    //打印小票
    protected function print_out($order_sn,$id,$is_test=false,$is_api=false)
    {
        //打印机模式
        $printer = $this->cloudModel->where(array('id'=>$id))->field('printer_style,id_number')->find();
        //打印前查询打印机状态
        $status=$this->QueryState($printer['id_number']);
        file_put_contents('./data/log/cloud/'.date('Y_m').'_print.log', date("Y-m-d H:i:s") .',数据id:'.$id.',设备id:'.$printer['id_number'].',print_out:QueryState:state:'.$this->state($status).'('.$status.')' . PHP_EOL, FILE_APPEND | LOCK_EX);
        if($status!=1) return;
        //时间戳
        $time=time();
        $querystring="action=send&device_id={$printer['id_number']}&secretkey={$this->secret_key}&timestamp={$time}&";
        $data = $this->printer_data($order_sn,$printer['printer_style'],$is_test);
        if(!$data) $this->ajaxReturn(array('code'=>'error','msg'=>'打印内容不能为空'));
        //这里做了一个转码，
        $data=mb_convert_encoding($data,"GBK","UTF-8");
        //base64加密一下打印内容
        $data=base64_encode($data."\x0d\x0a");

        //sha1($querystring.$data) 生成请求签名
        $querystring.="sign=".sha1($querystring.$data);

        $url=$this->server."?".$querystring;

        //测试打印则返回打印结果
        $re=json_decode($this->PostData($url,$data),true);
        file_put_contents('./data/log/cloud/'.date('Y_m').'_print.log', date("Y-m-d H:i:s") .',设备id:'.$printer['id_number'].',state:'.$this->state($re['state']). ',返回结果:' . json_encode($re) . PHP_EOL, FILE_APPEND | LOCK_EX);
        if($re['state'] != 'ok'){
            if($is_test) $this->ajaxReturn(array('code'=>'error','msg'=>$this->state($re['state'])));
            if($is_api) return $re['state'];
        }else{
            if($is_test) $this->ajaxReturn(array('code'=>'success','msg'=>'成功'));
            if($is_api) return '1';
        }

    }

    //渲染打印数据
    private function printer_data($order_sn,$printer_style,$is_test)
    {
        if($is_test){
            $pay_info = $this->test_data();
        }else{
            $pay_info = M('pay p')
                ->join('ypt_merchants m on m.id=p.merchant_id','left')
                ->join('ypt_order o on o.order_id=p.order_id','left')
                ->join('ypt_dc_no dn on dn.id=o.dc_no','left')
                ->field('m.merchant_name,p.price,p.remark,p.paytime,p.jmt_remark,p.paystyle_id,o.order_goods_num,o.user_money,o.coupon_price,o.integral_money,ifnull(o.total_amount,p.price) as total_amount,o.order_amount,o.discount,o.order_id,o.dc_no,o.dc_db,o.consignee,o.address,o.mobile,dc_ch_price,dc_db_price,dc_ps_price,dn.no')
                ->where(array('p.remark'=>"$order_sn",'p.status'=>1))
                ->find();
            if($pay_info['order_goods_num']>0){
                $goods_info = M('order_goods')->where(array('order_id'=>$pay_info['order_id']))->field('goods_name,goods_num,goods_price,spec_key_name')->select();
                $pay_info['goods_info'] = $goods_info;
            }
        }
        switch ($printer_style){
            case 1://小票打印
                $data = "\x1B\x61\x01\x1b\x4d\x01\x1d\x21\x11交易金额\n\n";
                $data .= "\x1B\x21\x30￥$pay_info[price]\n\n";
                $data .= "\x1B\x21\x00-------------------------------\n";
                $data .= "\x1B\x61\x00\x1b\x4d\x00\x1d\x21\x00订单金额:￥$pay_info[total_amount]\n";
                $data .= "付款方式: ".$this->paystyle($pay_info['paystyle_id'])."\n";
                if($pay_info['user_money']>0) $data .= "会员卡储值: -￥$pay_info[user_money]\n";
                if($pay_info['integral_money']>0) $data .= "会员卡积分: -￥$pay_info[integral_money]\n";
                if($pay_info['discount'] && !in_array($pay_info['discount'],array(0,100))){
                    //打包费，配送费，餐盒费不参与折扣
                    $price = $pay_info['total_amount']-$pay_info['dc_ch_price']-$pay_info['dc_db_price']-$pay_info['dc_ps_price'];
                    $data .= "会员卡折扣: -￥".sprintf("%.2f",$price-$price*$pay_info['discount']*0.01)."\n";
                }
                if($pay_info['coupon_price']>0) $data .= "优惠券: -￥$pay_info[coupon_price]\n";
                if($pay_info['dc_ch_price']>0) $data .= "餐盒费: ￥$pay_info[dc_ch_price]\n";
                if($pay_info['dc_db_price']>0) $data .= "打包费: ￥$pay_info[dc_db_price]\n";
                if($pay_info['dc_ps_price']>0) $data .= "配送费: ￥$pay_info[dc_ps_price]\n";
                $data .= "状态: 已付款\n";
                $data .= "收款人: $pay_info[merchant_name]\n";
                $data .= "流水号: $pay_info[remark]\n";
                $data .= "交易时间:". date('Y-m-d H:i:s',$pay_info['paytime'])."\n";
                $data .= "备注: $pay_info[jmt_remark]\n\n\n";
                return $data;
            case 2://一菜一单
                $way = $pay_info['dc_db']!=2?$pay_info['dc_db']==1?'(打包)':'(外卖)':'';
                $empty = '               ';
                $data = '';
                foreach ($pay_info['goods_info'] as &$v) {
                    if($v['spec_key_name']) $v['goods_name'] .= '('.$v['spec_key_name'].')';
                    $strlen = mb_strlen($v['goods_name'],'Unicode');
                    if($strlen<=10){
                        $data .= "\x1B\x21\x00\x1b\x61\x00单号:".$order_sn.$way."\n";
                        $data .= "餐桌号: ".$pay_info['no']."\n";
                        $data .= str_pad($v['goods_name'],16,' ',STR_PAD_RIGHT)."\x09X".$v['goods_num']."\x09￥".$v['goods_num']*$v['goods_price']."\x0A\n\n\n";
                    }else{
                        $data .= "\x1B\x21\x00\x1b\x61\x00单号:".$order_sn.$way."\n";
                        $data .= "餐桌号: ".$pay_info['no']."\n";
                        $data .= $v['goods_name']."\x0A$empty\x09X".$v['goods_num']."\x09￥".$v['goods_num']*$v['goods_price']."\x0A\n\n\n";
                    }
                }
                return $data;
            case 3://总订单打印
                $way = $pay_info['dc_db']!=2?$pay_info['dc_db']==1?'(打包)':'(外卖)':'';
                $data = "\x1B\x61\x00\x1b\x4d\x00\x1d\x21\x00单号:".$order_sn.$way."\n";
                if($pay_info['no']) $data .="餐桌号: $pay_info[no]\n";
                $data .="时间: ". date('Y-m-d H:i:s',$pay_info['paytime'])."\n";
                $data .="备注: $pay_info[jmt_remark]\n";
                if($pay_info['goods_info']){
                    $empty = '               ';
                    $data .="-------------------------------\n";
                    foreach ($pay_info['goods_info'] as &$v) {
                        if($v['spec_key_name']) $v['goods_name'] .= '('.$v['spec_key_name'].')';
                        $strlen = mb_strlen($v['goods_name'],'Unicode');
                        if($strlen<=10){
                            $data .= str_pad($v['goods_name'],16,' ',STR_PAD_RIGHT)."\x09X".$v['goods_num']."\x09￥".$v['goods_num']*$v['goods_price']."\x0A";
                        }else{
                            $data .= $v['goods_name']."\x0A$empty\x09X".$v['goods_num']."\x09￥".$v['goods_num']*$v['goods_price']."\x0A";
                        }
                    }
                    $data .="-------------------------------\n";
                }
                if($pay_info['dc_db_price']>0) $data .="打包费: ￥$pay_info[dc_db_price]\n";
                if($pay_info['dc_ps_price']>0) $data .="配送费: ￥$pay_info[dc_ps_price]\n";
                if($pay_info['dc_ch_price']>0) $data .="餐盒费: ￥$pay_info[dc_ch_price]\n";
                $data .="合计: $pay_info[total_amount]\n";
                if($pay_info['user_money']>0) $data .= "会员卡储值: -￥$pay_info[user_money]\n";
                if($pay_info['integral_money']>0) $data .= "会员卡积分: -￥$pay_info[integral_money]\n";
                if($pay_info['discount'] && !in_array($pay_info['discount'],array(0,100))){
                    //打包费，配送费，餐盒费不参与折扣
                    $price = $pay_info['total_amount']-$pay_info['dc_ch_price']-$pay_info['dc_db_price']-$pay_info['dc_ps_price'];
                    $data .= "会员卡折扣: -￥".sprintf("%.2f",$price-$price*$pay_info['discount']*0.01)."\n";
                }
                if($pay_info['coupon_price']>0) $data .= "优惠券: -￥$pay_info[coupon_price]\n";
                $data .="-------------------------------\n";
                if($pay_info['consignee'] && $pay_info['address'] && $pay_info['mobile']){
                    $data .= "数量:$pay_info[order_goods_num]件    实付金额:￥$pay_info[price]\n";
                    $data .="-------------------------------\n";
                    $data .= "客户信息:\n";
                    $data .= "姓名:$pay_info[consignee]\n";
                    $data .= "联系方式:$pay_info[mobile]\n";
                    $data .= "收货地址:$pay_info[address]\n\n\n";
                }else{
                    $data .= "数量:$pay_info[order_goods_num]件    实付金额:￥$pay_info[price]\n\n\n";
                }
                return $data;
        }
    }

    //查询打印机状态
    public function printstate()
    {
        $device_id = I('device_id');
        if(!$device_id) $this->ajaxReturn(array('code'=>'error','msg'=>'device_id is empty'));
        $status = $this->QueryState($device_id);
        $this->ajaxReturn(array('code'=>'success','msg'=>'成功','data'=>$status));
    }
    private function QueryState($device_id)
    {
        //时间戳
        $time=time();
        $querystring="action=state&device_id={$device_id}&secretkey={$this->secret_key}&timestamp={$time}";
        //sha1($querystring) 生成请求签名
        $querystring.="&sign=".sha1($querystring);

        $url=$this->server."?".$querystring;

        return $this->PostData($url);
    }

    private function PostData($url,$data="")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($data)
        {
            curl_setopt($ch, CURLOPT_POST, true );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $handles = curl_exec($ch);
        curl_close($ch);

        return $handles;
    }

    //支付方式
    private function paystyle($paystyle_id)
    {
        switch ($paystyle_id) {
            case 1:
                return "微信支付";
            case 2:
                return "支付宝支付";
            case 3:
                return "银联钱包支付";
            case 4:
                return "京东支付";
            case 5:
                return "现金支付";
            default:
                return "其他方式";
        }
    }

    //打印机状态
    private function state($state)
    {
        switch ($state) {
            case 1:
                return "状态正常";
            case 2:
                return "打印机未连接";
            case 3:
                return "打印机无纸";
            case 4:
                return "打印盒子超时未上报";
            default:
                return '打印失败'.$state;
        }
    }

    private function test_data()
    {
        return array(
            'price'=>"1.00",
            'remark'=>"1234",
            'merchant_name'=>'ypt',
            'paytime'=>"1512649264",
            'jmt_remark'=>"少辣",
            'paystyle_id'=>"1.00",
            'order_goods_num'=>"4",
            'user_money'=>"124.60",
            'coupon_price'=>"0.00",
            'integral_money'=>"0.00",
            'total_amount'=>"179.00",
            'order_amount'=>"1.00",
            'discount'=>"70",
            'dc_ch_price'=>"0.00",
            'dc_db_price'=>"1.00",
            'dc_ps_price'=>"0.00",
            'order_id'=>"16498",
            'dc_no'=>"43",
            'dc_db'=>"1",
            'no'=>"1号餐桌",
            'goods_info'=>array(
                '0'=>array(
                    'goods_name'=>'合家欢套餐',
                    'goods_num'=>'1',
                    'goods_price'=>'108.00',
                    'spec_key_name'=>'大份',
                ),
                '1'=>array(
                    'goods_name'=>'现磨咖啡',
                    'goods_num'=>'1',
                    'goods_price'=>'16.00',
                    'spec_key_name'=>'少糖',
                ),
                '2'=>array(
                    'goods_name'=>'墨西哥鸡肉卷',
                    'goods_num'=>'1',
                    'goods_price'=>'16.00',
                    'spec_key_name'=>'',
                ),
                '3'=>array(
                    'goods_name'=>'手扒鸡套餐',
                    'goods_num'=>'1',
                    'goods_price'=>'38.00',
                    'spec_key_name'=>'',
                )
            )
        );
    }
    /**
     * --------------------------------------------云喇叭--------------------------------------------------------
     * */

    /**
     * 绑定/解绑
     * @param $id_number 设备id
     * @param $m 操作，0解绑，1绑定
     * */
    public function bind($id_number,$m)
    {
        $seq = mt_rand(1000,9999);//防重复提交
        $url = $this->speak_url.'bind.php?id='.$id_number.'&m='.$m.'&uid='.$this->userId.'&token='.$this->cloud_token.'&seq='.$seq;
        $res = json_decode(file_get_contents($url),true);
        if($res['errcode'] != 0){
            file_put_contents('./data/log/cloud/'.date('Y_m').'_bind.log', date("Y-m-d H:i:s") .',请求url:'.$url.',失败返回参数:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->ajaxReturn(array('code'=>'error','msg'=>$this->bind_errcode($res['errcode'])));
        }
    }

    //绑定云喇叭错误码
    private function bind_errcode($errcode)
    {
        switch ($errcode){
            case 1:
                return '未知错误';
            case 2:
                return '设备ID不存在';
            case 3:
                return '该设备已被其他账号绑定';
            case 4:
                return '该设备已被同一账号绑定';
            case 5:
                return '解绑失败，该设备未被任何ID绑定';
            case 6:
                return '未提供设备ID';
            case 8:
                return '此TOKEN无此SPEAKERID权限';
            case 9:
                return '失败，无效的TOKEN';
            case 17:
                return '错误，重复的请求';
            default:
                return '失败，错误码:'.$errcode;
        }
    }

    /**
     * 提交支付消息
     * @param $order_sn 流水号
     * @param $id_number 设备id
     * */
    public function push_message($order_sn,$id_number)
    {
        $pay_info = $this->pay_model->where(array('remark'=>$order_sn))->field('paystyle_id,price')->find();
        if($pay_info['paystyle_id'] == 1){
            $pt = 2;//微信
        }elseif ($pay_info['paystyle_id'] == 2){
            $pt = 1;//支付宝
        }else{
            $pt = 0;//通用
        }
        $price = $pay_info['price']*100;//价格
        $seq = mt_rand(1000,9999);//防重复提交
        $url = $this->speak_url.'add.php?id='.$id_number.'&pt='.$pt.'&price='.$price.'&token='.$this->cloud_token.'&seq='.$seq;
        $res = json_decode(file_get_contents($url),true);
        if($res['errcode'] != 0){
            file_put_contents('./data/log/cloud/'.date('Y_m').'_push_message.log', date("Y-m-d H:i:s") .',请求url:'.$url.',失败返回参数:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * 更改云喇叭音量
     * @param $id_number 设备id
     * @param $vol 音量，0-100
     * */
    public function change_vol($id_number,$vol)
    {
        $seq = mt_rand(1000,9999);//防重复提交
        $url = $this->speak_url.'add.php?id='.$id_number.'&vol='.$vol.'&token='.$this->cloud_token.'&seq='.$seq;
        $res = json_decode(file_get_contents($url),true);
        if($res['errcode'] != 0){
            file_put_contents('./data/log/cloud/'.date('Y_m').'_change_vol.log', date("Y-m-d H:i:s") .',请求url:'.$url.',失败返回参数:' . json_encode($res) . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->ajaxReturn(array('code'=>'error','msg'=>$this->change_vol_errcode($res['errcode'])));
        }
    }

    //更改云喇叭音量错误码
    private function change_vol_errcode($errcode)
    {
        switch ($errcode){
            case 1:
                return '未知错误';
            case 2:
                return '失败，该设备ID不存在';
            case 6:
                return '失败，USERID不存在或没有与之绑定的SPEAKERID';
            case 8:
                return '失败，此TOKEN无此设备ID';
            case 9:
                return '失败，无效的TOKEN';
            default:
                return '失败，错误码:'.$errcode;
        }
    }

}

