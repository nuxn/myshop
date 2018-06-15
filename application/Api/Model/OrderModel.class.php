<?php

namespace Api\Model;

use Think\Model;

/**用户模型
 * Class OrderModel
 * @package Api\Model
 */
class OrderModel extends Model
{

    public function get_merchant_info($mch_id, $time='')
    {
        if ($time != "") $map['pay_time'] = array("between", $time);
        $mu_id = M('merchants')->where("id=$mch_id")->getField('uid');
        $card_id = M('screen_memcard')->where("mid=$mu_id")->getField('id');
        if($card_id){

            $card_code = M('screen_memcard_use')->where("memcard_id=$card_id and status=1 and card_code is not null")->getField('card_code', true);
            $entity_card_code = M('screen_memcard_use')->where("memcard_id=$card_id and status=1 and entity_card_code !=''")->getField('entity_card_code', true);
//            echo json_encode($card_code);
//            echo json_encode($entity_card_code);
            if(!$card_code && !$entity_card_code) return array('amount'=> 0,'number'=>0);
            if($card_code && $entity_card_code) {
                $code = array_merge($card_code,$entity_card_code);
            } elseif($card_code){
                $code = $card_code;
            }else if($entity_card_code){
                $code = $entity_card_code;
            } else {
                return array('amount'=> 0,'number'=>0);
            }
            $map['card_code'] = array('IN', $code);
            $result = $this
                ->field('ifnull(sum( if(order_status=5, 1, 0)),0) as number,ifnull(sum( if(order_status=5, user_money, 0)),0) as amount')
                ->where($map)
                ->find();
            return $result;
        } else {
            return array('amount'=> 0,'number'=>0);
        }
    }

    public function get_agent_info($mch_id, $time='')
    {
        if ($time != "") $map['pay_time'] = array("between", $time);
        $mu_id = M('merchants')->where("id=$mch_id")->getField('uid');
        $agent_id = M('merchants_users')->where("id=$mu_id")->getField('agent_id');
        $card_id = M('screen_memcard')->where("mid=$agent_id")->getField('id');
        if($card_id){
            $card_code = M('screen_memcard_use')->where("memcard_id=$card_id and status=1 and card_code is not null")->getField('card_code', true);
            $entity_card_code = M('screen_memcard_use')->where("memcard_id=$card_id and status=1 and entity_card_code !=''")->getField('entity_card_code', true);

            if(!$card_code && !$entity_card_code) return array('amount'=> 0,'number'=>0);
            if($card_code && $entity_card_code) {
                $code = array_merge($card_code,$entity_card_code);
                $result = $this
                    ->field('ifnull(sum( if(order_status=5, 1, 0)),0) as number,ifnull(sum( if(order_status=5, user_money, 0)),0) as amount')
                    ->where(array('user_id'=>$mu_id,'card_code'=>array('IN', $code)))
                    ->find();
                return $result;
            }elseif ($card_code) {
                $code = $card_code;
            } else if ($entity_card_code) {
                $code = $entity_card_code;
            } else {
                return array('amount' => 0, 'number' => 0);
            }
            $map['user_id'] = $mu_id;
            $map['card_code'] = array('IN', $code);
            $result = $this
                ->field('ifnull(sum( if(order_status=5, 1, 0)),0) as number,ifnull(sum( if(order_status=5, user_money, 0)),0) as amount')
                ->where($map)
                ->find();
            return $result;
        } else {
            return array('amount' => 0, 'number' => 0);
        }
    }

}