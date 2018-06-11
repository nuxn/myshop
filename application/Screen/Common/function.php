<?php


// 获得卡卷的颜色
    function get_color()
    {
        $color=array(
            array('name'=>'Color010','value'=>'#63b359'),
            array('name'=>'Color020','value'=>'#2c9f67'),
            array('name'=>'Color030','value'=>'#509fc9'),
            array('name'=>'Color040','value'=>'#5885cf'),
            array('name'=>'Color050','value'=>'#9062c0'),
            array('name'=>'Color060','value'=>'#d09a45'),
            array('name'=>'Color070','value'=>'#e4b138'),
            array('name'=>'Color080','value'=>'#ee903c'),
            array('name'=>'Color081','value'=>'#f08500'),
            array('name'=>'Color082','value'=>'#a9d92d'),
            array('name'=>'Color090','value'=>'#dd6549'),
            array('name'=>'Color100','value'=>'#cc463d'),
            array('name'=>'Color101','value'=>'#cf3e36'),
            array('name'=>'Color102','value'=>'#5E6671'),
        );
        return $color;
    }
    /**
     * @param $card_type  $counpon
     * @return string  根据优惠卷的种类进行分类
     */
    function get_card_type($counpon)
    {
        switch ($counpon['card_type']){
            case "GROUPON":
                return "团购券";
            case "CASH":
                return "代金券";
            case "card_type":
                return "折扣劵";
            case "GIFT":
                return "礼品劵";
            case "GENERAL_COUPON":
                return "优惠卷";
            default:
                return "";
        }
    }

    /**
     * @param $counpon  当前id的数组
     * @return string   得到有效期
     */
    function get_time_detail($counpon)
    {
        if($counpon['type'] =="DATE_TYPE_FIX_TIME_RANGE"){
            $begin_timestamp=date("Y-m-d",$counpon['begin_timestamp']);
            $end_timestamp=date("Y-m-d",$counpon['end_timestamp']);
            $indate=$begin_timestamp."至".$end_timestamp;
        }
        if($counpon['type'] =="DATE_TYPE_FIX_TERM"){
            $fixed_term =$counpon['fixed_term'];
            $fixed_begin_term =$counpon['fixed_begin_term'];
            if($fixed_begin_term == "0"){
                $fixed_begin_term = "当天";
            }else{
                $fixed_begin_term = $fixed_begin_term."天后";
            }
            $indate="领取后".$fixed_begin_term."生效".$fixed_term."天有效";
        }
        return $indate;
    }

    /**
     * @param $counpon
     * @return string  得到的状态
     */
    function get_status($counpon)
    {
        switch ($counpon['status']){
            case "1":
                return "审核中";
            case "2":
                return "未通过";
            case "3":
                return "待投放";
            case "4":
                return "已投放";
            default:
                return "";
        }
    }

    /**
     * @param $counpon
     * @return string  优惠卷的具体内容
     */
    function get_pay_content($counpon)
    {
        $total_price = $counpon['total_price'];
        $de_price = $counpon['de_price'];
        return "满".$total_price."元减".$de_price."元";
    }

    /**
     * @param $counpon
     * @return string  获得优惠券的本地logo
     */
    function get_base_url($counpon)
    {
        $mid=$counpon['mid'];
        return M("merchants")->where("id=$mid")->getField("base_url");
    }

    /**
     * @param $card_id  优惠券的card_id
     * @return object  获得优惠券的具体信息
     */
    function get_card_detatil($card_id)
    {
//        $url_card_detail="https://api.weixin.qq.com/card/get?access_token=".get_weixin_token();
        $url_card_detail="https://api.weixin.qq.com/card/get?access_token=tkdhj5GPPZKqi9-b_Xe7-dGhfwy7QyBbDfgwLjwH-PrOI7h0_6rbQf4rX1ZC5RWCkvhY4EK-Qsp6dJwtakYhl6m_pFKfo1sSyGzHgrj_zrzCHxyEb9X7VrDC73y1TgH7VCYjABAZGC";
        $data['card_id'] ="$card_id";
        $card_detail=request_post($url_card_detail,json_encode($data));
        return json_decode($card_detail);

    }
    /**
     * @param $card_detail  json格式
     * @return object  获得优惠券的具体信息
     */
    function get_card_status($card_detail)
    {
        $status=$card_detail->card->general_coupon->base_info->status;
        switch ($status){

            case "CARD_STATUS_NOT_VERIFY";  //待审核
                return 1;
            case "CARD_STATUS_VERIFY_FAIL"; //审核失败；
                return 2;
            case "CARD_STATUS_VERIFY_OK";   //通过审核；
                return 3;
            case "CARD_STATUS_DISPATCH";   //在公众平台投放过的卡券
                return 4;
            case "CARD_STATUS_USER_DELETE"; //卡券被商户删除；
                return 5;

        }
    }
    /**
     * @param $user_phone 输入的手机号码
     * @return string  商户的id
     */
    function check_merchant_phone($user_phone)
    {
        $id=M("merchants")->alias("m")->join("left join __MERCHANTS_USERS__ u on m.uid =u.id")->where("u.user_phone=$user_phone")->getField("m.id");
        if($id){
            return $id;
        }
        return "";
    }

    /**
     * @param $mid  商户的id
     * @return mixed 商户的信息
     */
    function get_merchant_detail($mid)
    {
        return M("merchants")->alias("m")->join("left join __MERCHANTS_USERS__ u on m.uid =u.id")->where("m.id=$mid")->field("u.user_name")->find();
    }

    /**
     * @param $time
     * @return false|int  转化时间
     */
     function time_transform($time)
    {
        $time=int($time);
        $begin_timestamp=date("Y-m-d",strtotime($time));
        $time=strtotime($begin_timestamp);
        return $time;
    }