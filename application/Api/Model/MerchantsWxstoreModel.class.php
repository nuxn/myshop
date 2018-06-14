<?php

namespace Api\Model;

use Think\Model;

/**
 * Class MerchantsWxstoreModel
 * @package Api\Model
 */
class MerchantsWxstoreModel extends Model
{

    public function get_mch_info($userId)
    {
        $role_id = M('merchants_role_users')->where("uid=$userId")->getField('role_id');
        $mu_id = $userId;
        if($role_id != 3){
            $mu_id = M('merchants_users')->where("id=$userId")->getField('boss_id');
        }
        $mch_info = M('merchants')->field('id as sid,uid,province,city,county,address,lon,lat')->where("uid=$mu_id")->find();

        return $mch_info;
    }


    public function checkStore($userId)
    {
        if(M('merchants_wxstore')->where("mu_id=$userId")->getField('poi_id')){
            return true;
        }

        return false;
    }

    public function get_wx_category()
    {
        return M('category_wx')->field('id,pid,name,level')->where('status=1')->select();
    }

    public function into_wxstore($into)
    {
        return $this->add($into);
    }

    protected function checkInfo($mch_info)
    {
        if($mch_info['lon'] == 0 ||$mch_info['lat'] == 0 ){
            header('Content-Type:application/json; charset=utf-8');
            $return['code'] = 'error';
            $return['msg'] = '商户信息不全';
            exit(json_encode($return));
        }
    }

    public function addpio($input,$mch_info)
    {
        $this->checkInfo($mch_info);
        $token = get_weixin_token();
        $url = "http://api.weixin.qq.com/cgi-bin/poi/addpoi?access_token={$token}";
        $param['business']['base_info'] = array(
            "sid" => "$mch_info[sid]",
            "business_name" => urlencode($input['business_name']),
            "branch_name" => "",
            "province" => urlencode($mch_info['province']),
            "city" => urlencode($mch_info['city']),
            "district" => urlencode($mch_info['county']),
            "address" => urlencode($mch_info['address']),
            "telephone" => $input['telephone'],
            "categories" => array(urlencode("$input[categories]")),
            "offset_type" => 1,
            "longitude" => $mch_info['lon'],
            "latitude" => $mch_info['lat'],
            "open_time" => $input['open_time'],
            "introduction" => urlencode($input['introduction']),
        );
        $param = urldecode(json_encode($param));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','addpio参数', $param);
        $res = request_post($url, $param);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','addpio结果', $res);
        $res = json_decode($res);

        return $res;

    }

    public function get_poi_id($userId)
    {
        $res = $this->where("mu_id=$userId")->getField('poi_id');
        return $res;
    }

    public function updatepoi($input,$poi_id)
    {
        $token = get_weixin_token();
        $url = "https://api.weixin.qq.com/cgi-bin/poi/updatepoi?access_token={$token}";
        $param['business']['base_info'] = array(
            "poi_id" => "$poi_id",
            "telephone" => $input['telephone'],
            "open_time" => $input['open_time'],
            "introduction" => urlencode($input['introduction']),
        );
        $param = urldecode(json_encode($param));
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','updatepoi参数', $param);
        $res = request_post($url, $param);
        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','updatepoi结果', $res);
        $res = json_decode($res);

        return $res;
    }

    public function card_addpoi($card_id, $poi_id)
    {
        $card_info = M('screen_memcard')->field('id,card_id,is_agent')->where("id=$card_id")->find();
        if(!$card_info) return false;

        // 判断是否为异业联盟卡
        if($card_info['is_agent']){
            $this->add_agent_poid($card_info,$poi_id);
        } else {
            $this->add_poid($card_info['card_id'],$poi_id);
        }
    }

    protected function add_poid($card_id, $poi_id)
    {

        $curl_datas = array(
            "card_id" => $card_id,
            "member_card" => array(
                "base_info" => array(
                    "location_id_list" => array($poi_id),
                )));
        return $this->update_card(json_encode($curl_datas));
    }

    // 如果是异业联盟卡，则需要获取所有开通的商户的门店ID
    protected function add_agent_poid($card_info)
    {
        // 加入异业联盟卡的商户
        $use_merchants = M('screen_cardset')->where("c_id=$card_info[id]")->getField('use_merchants');
        // 没有商户则返回
        if(!$use_merchants) return false;
        // 查找商户的微信门店ID
        $poi_ids = $this->where(array('IN', $use_merchants))->getField('poi_id', true);
        if(!$poi_ids) return false;

        $poi_ids = implode(',', $poi_ids);
        $curl_datas = array(
            "card_id" => $card_info['card_id'],
            "member_card" => array(
                "base_info" => array(
                    "location_id_list" => array($poi_ids),
        )));
        return $this->update_card(json_encode($curl_datas));
    }

    protected function update_card($data)
    {
        $token = get_weixin_token();
        $create_card_url = "https://api.weixin.qq.com/card/update?access_token=$token";
        $result = request_post($create_card_url, $data);
        $result = json_decode($result, true);
        if($result['errcode'] == 0){
            return true;
        } else {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','update_card门店请求参数', $data);
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Api/','Merchants_wxstore','update_card结果', json_encode($result));
            return false;
        }

    }

    public function del_wxstore($uid)
    {
        $mch_info = $this->get_mch_info($uid);
        $poi_id = $this->where("sid=$mch_info[sid]")->getField('poi_id');
        if($poi_id){
            $token = get_weixin_token();
            $create_card_url = "https://api.weixin.qq.com/cgi-bin/poi/delpoi?access_token=$token";
            $data ='{"poi_id": "'.$poi_id.'"}';
            $result = request_post($create_card_url, $data);
            return $result;
        } else {
            return 'no wxstore';
        }
    }

}