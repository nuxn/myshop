<?php
namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;

class  MembercardController extends ScreenbaseController
{

    function _initialize()
    {
        parent::_initialize();
        $this->host = 'https://' . $_SERVER['HTTP_HOST'];
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/member/';
    }
    # 实体卡开卡
    public function open_card()
    {
        if (IS_POST) {
            ($entity_card_code = I('real_card_code','','trim')) || $this->ajaxReturn(array('code'=>'error','msg'=>'实体卡号不能为空'));
            $card = $this->get_entity_card_detail($entity_card_code);
            if(!$card){
                $this->ajaxReturn(array('code'=>'error','msg'=>'实体卡号不存在'));
            }elseif ($card['mid'] != $this->mch_uid){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '不能绑定其他商户的实体卡！'));
            }elseif($card['e_status'] != 0){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '实体卡号已使用！'));
            }
            if(!M('screen_memcard')->where("mid=$this->mch_uid")->getField('id'))array('code'=>'error', 'msg' => '未创建微信会员卡，无法开通实体卡！');
            ($realname = I('realname')) || $this->ajaxReturn(array('code'=>'error','msg'=>'会员姓名不能为空'));
            $memphone = I('memphone','','trim');
            $sex = I('sex','','trim');
            if(empty($memphone)){
                $this->ajaxReturn(array('code'=>'error','msg'=>'会员手机号码不能为空'));
            }elseif(!isMobile($memphone)){
                $this->ajaxReturn(array('code'=>'error','msg'=>'会员手机号码格式不正确'));
            }
            $level = I('level');
            if(!$level){
                $this->ajaxReturn(array('code'=>'error','msg'=>'请选择会员等级'));
            }

            M()->startTrans();
            $memid = M('screen_mem')->add(
                array(
                    'nickname'=>'',
                    'realname'=>$realname,
                    'memphone'=>$memphone,
                    'levelid'=>$level,
                    'status'=>1,
                    'sex'=>$sex,
                    'add_time'=>time(),
                    'userid'=>$this->mch_uid
                    ));
            if(!$memid) {
                M()->rollback();
                $this->ajaxReturn(array('code' => 'error', 'msg' => '添加失败'));
            }
            $res = M('screen_memcard_use')->where(array('entity_card_code'=>$entity_card_code))->save(
                array('memcard_id'=>$card['id'],
                    'e_status'=>1,
                    'status'=>1,
                    'create_time'=>time(),
                    'memid'=>$memid,
                    'level'=>$level));
            if ($res !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 'success', 'msg' => '添加成功', 'data'=>array('member_id'=>$res)));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 'error', 'msg' => '添加失败'));
            }
        }
    }

    public function get_card_level()
    {
        $level_list = M('screen_memcard m')
            ->join('left join ypt_screen_memcard_level l on l.c_id=m.id')
            ->where(array('m.mid'=>$this->mch_uid))
            ->field('level,level_name')
            ->select();
        if(!$level_list[0]["level"] && !$level_list[0]["level_name"]){
            $level_list = array(array('level'=>'1','level_name'=>'普通会员'));
        }
        $this->ajaxReturn(array('code'=>'success', 'data'=> $level_list));
    }

    public function set_password()
    {
        $pay_pass = I('pay_pass','','trim');
        if(empty($pay_pass)){
            $this->ajaxReturn(array('code'=>2,'msg'=>'请会员设置支付密码'));
        }elseif(!is_numeric($pay_pass)){
            $this->ajaxReturn(array('code'=>'error','msg'=>'密码格式不正确'));
        }elseif(strlen($pay_pass) != 6){
            $this->ajaxReturn(array('code'=>'error','msg'=>'密码长度不够6位'));
        }else{
            $pay_pass = md5($pay_pass.'tiancaijing');
            $memid = I('member_id');
            $res = M('screen_memcard_use')->where(array('id'=>$memid))->save(array('pay_pass'=>$pay_pass));
            if($res !== false){
                $this->ajaxReturn(array('code'=>'success', 'msg'=> '设置成功'));
            } else {
                $this->ajaxReturn(array('code'=>'error', 'msg'=> '设置失败'));
            }
        }

    }

    # 微信会员卡绑定实体卡号
    public function bind_card()
    {
        if(IS_POST){
            $wx_card_code = I('wx_card_code','','trim');
            $real_card_code = I('real_card_code','','trim');
            get_date_dir($this->path,'bind_card','参数', json_encode($_POST));
            // $card_id = M('screen_memcard')->where(array('mid'=>$this->mch_uid))->getField('id');
            $memuse_data = M('screen_memcard_use')->where(array('card_code'=>$wx_card_code))->find();
            if(!$memuse_data)$this->ajaxReturn(array('code'=>'error','msg'=> '微信会员不存在'));
            get_date_dir($this->path,'微信会员','参数', json_encode($memuse_data));
            $id = $memuse_data['id'];
            if(!$real_card_code) $this->ajaxReturn(array('code'=>'error', 'msg' => '未输入实体卡号！'));
            $card = $this->get_entity_card_detail($real_card_code);
            if(is_null($card['e_status'])){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '实体卡号不存在！'));
            }elseif($card['e_status'] != 0){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '实体卡号已使用！'));
            }elseif($card['mid'] != $this->mch_uid){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '不能绑定其他商户的实体卡！'));
            }
            M()->startTrans();
            $save = array(
                'entity_card_code' => $real_card_code,
                'e_status' => 1,
            );
            $res = M('screen_memcard_use')->where(array('id'=>$id))->save($save);
            get_date_dir($this->path,'绑定','参数', json_encode($save)."RES:$res");
            M('screen_memcard_use')->where(array('entity_card_code' => $real_card_code))->delete();
            if ($res !== false) {
                M()->commit();
                $this->ajaxReturn(array('code' => 'success', 'msg' => '绑定成功'));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code'=>'error', 'msg' => '绑定失败'));
            }
        }
    }

    #解绑实体卡信息获取
    public function unbind_info()
    {
        if (IS_POST) {
            $phone = I('phone');
            $entity_card_code = I('real_card_code');
            if(!$phone && !$entity_card_code){
                $this->ajaxReturn(array('code' => 'error', 'msg' => '请至少填写一种信息'));
            }
            if($entity_card_code){
                $mem_data = M('screen_memcard_use')->where(array('entity_card_code'=>$entity_card_code))->find();
                if(!$mem_data) $this->ajaxReturn(array('code' => 'error', 'msg' => '会员不存在,请检查卡号'));
            }
            if($phone && !$entity_card_code){
                $mem_id = M('screen_mem')->where(array('memphone'=>$phone,'userid'=>$this->mch_uid))->getField('id');
                if(!$mem_id) $this->ajaxReturn(array('code' => 'error', 'msg' => '会员信息不存在,请检查手机号码'));
                $mem_data = M('screen_memcard_use')->where(array('memid'=>$mem_id))->find();
            }
//            $real_name = M('screen_mem')->where(array('id'=>$mem_data['memid']))->getField('realname');
            if ($mem_data) {
//                $data['member_name'] = $real_name;
                $data['member_id'] = $mem_data['id'];
//                $data['phone'] = $phone;
//                $data['real_card_code'] = $mem_data['entity_card_code'];
                $this->ajaxReturn(array('code' => 'success', 'data' => $data));
            } else {
                $this->ajaxReturn(array('code' => 'error', 'msg' => '会员信息有误'));
            }
        }
    }

    #解绑实体卡
    public function unbind_card()
    {
        if (IS_POST) {
            $id = I('member_id');
            ($new_card_code = I('new_card_code','','trim')) || $this->ajaxReturn(array('code'=>'error','msg'=>'实体卡号不能为空'));
            $card = $this->get_entity_card_detail($new_card_code);
            if(!$card){
                $this->ajaxReturn(array('code'=>'error','msg'=>'实体卡号不存在'));
            }elseif ($card['mid'] != $this->mch_uid){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '不能绑定其他商户的实体卡！'));
            }elseif($card['e_status'] != 0){
                $this->ajaxReturn(array('code'=>'error', 'msg' => '实体卡号已使用！'));
            }
            $save = array(
                'entity_card_code' => $new_card_code,
                'e_status' => 0,
            );
            M()->startTrans();
            $del_res = M('screen_memcard_use')->where(array('entity_card_code' => $new_card_code))->delete();
            $res = M('screen_memcard_use')->where(array('id'=>$id))->save($save);

            if ($res !== false && $del_res) {
                M()->commit();
                $this->ajaxReturn(array('code' => 'success', 'msg' => '操作成功'));
            } else {
                M()->rollback();
                $this->ajaxReturn(array('code' => 'error', 'msg' => '操作失败'));
            }
        }
    }

    #获取实体卡详情
    private function get_entity_card_detail($entity_card_code)
    {
        $card = M('screen_memcard_use u')
            ->join('left join ypt_screen_memcard m on m.id=u.memcard_id')
            ->where(array('u.entity_card_code'=>$entity_card_code))
            ->field('u.e_status,m.mid,m.id')
            ->find();
        return $card;
    }

}