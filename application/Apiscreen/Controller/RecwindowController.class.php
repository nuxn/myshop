<<<<<<< HEAD
<?php

namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;

class RecwindowController extends ScreenbaseController
{

    private $pay_model;
    public function _initialize()
    {
        parent::_initialize();
        $this->pay_model = M('pay');
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/';
    }

    // 商品检索
    public function goods_list()
    {
        // $this->userId =26;
        ($bar_code = I("bar_code")) || $this->ajaxReturn(array("code" => "error", "msg" => "bar_code is empty", "data" => ""));
        $user_id = $this->userId;
        // dump($user_id);
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if (!in_array($role_id, array(2, 3))) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $goods_id = M("goods")->where("bar_code='$bar_code' AND mid=$uid and is_delete=0")->getField("goods_id");
        // echo M("goods")->getLastSql();die;
        //        $goods_list = M("goods g")
        //            ->join("__GOODS_SKU__ gs on g.goods_id=gs.goods_id")
        //            ->where('g.goods_id = '.$goods_id)
        //            ->field("g.goods_name,g.shop_price,g.bar_code,g.discount,gs.properties")
        //            ->select();
        if ($goods_id == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => array()));
        } else {
            $goods = M('goods')->where('goods_id=' . $goods_id)->field('goods_id,goods_name,shop_price,bar_code,discount,group_id,put_two,trade')->find();
            if ($goods['put_two'] != 2) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该商品未上架", "data" => array()));
            }
            if ($goods['group_id'] == 0) {
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                if ($group_name) {
                    $goods['group_name'] = $group_name;
                    $goods['group_id'] = $group_id;
                } else {
                    $goods['group_name'] = array();
                }
            } else {
                $group_name = M('goods_group')->where(array('group_id' => $goods['group_id']))->getField('group_name');
                if ($group_name) {
                    $goods['group_name'] = $group_name;
                } else {
                    $goods['group_name'] = array();
                }
            }
            $ress = M('goods_sku')->where('goods_id=' . $goods_id)->field('discount,price,sku_id,properties')->select();
            if ($ress) {
                $goods['properties'] = $ress;
                foreach ($goods['properties'] as $k => $v) {
                    //dump($v["properties"]);
                    $goods['properties'][$k]["properties"] = $v["properties"];
                }
            } else {
                $aa = array();
                $aa['price'] = $goods['shop_price'];
                $aa['discount'] = $goods['discount'];
                //$aa['discount']='11111';
                $goods['properties'][] = $aa;
                //unset($goods['discount']);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods));
        }

    }

    /**
     * desc 获取所有双频订单支付成功的信息
     *
     * @return void
     * @author
     **/
    public function getDoubleScreenOrders()
    {
        $paystyle = array(1 => '微信支付', 2 => '支付宝', 5 => '现金支付', 3 => '刷卡', 4 => '储值余额');
        $user_id = $this->userId;
        if (IS_POST) {

            $today = date('Y-m-d');
            $end_time = strtotime($today . ' 23:59:59');
            $before_time = date("Y-m-d", strtotime("-14 days"));
            $start_time = strtotime($before_time);

            $data = M('order')->where(array('user_id' => $user_id, 'pay_status' => 1, 'order_status' => 5, 'type' => 4, 'pay_time' => array(array('EGT', $start_time), array('ELT', $end_time), 'AND')))->field('order_id,order_sn,paystyle,pay_time,order_amount,total_amount')->order(' pay_time desc')->select();

            get_date_dir($this->path, 'Recwindow_getDoubleScreenOrders', 'SQL', M()->_sql());
            if (!empty($data)) {
                foreach ($data as $key => $val) {
                    $data[$key]['pay_style_name'] = $paystyle[$val['paystyle']];
                    $data[$key]['pay_time'] = date("Y-m-d H:i:s", $val['pay_time']);
                    $data[$key]['check_user_name'] = $this->getCheckUser($val['order_id']);

                }

                get_date_dir($this->path, 'Recwindow_getDoubleScreenOrders', '订单数据', json_encode($data));

                $this->ajaxReturn(array("code" => "success", "msg" => "查询成功", "data" => $data));

            } else {

                $this->ajaxReturn(array("code" => "error", "msg" => "没有数据"));

            }

        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请求错误"));

        }

    }


    /**
     * desc 获取所有双频订单支付成功的信息
     *
     * @return void
     * @author
     **/
    public function getOrderGoodsBySn()
    {

        if (IS_POST) {

            $order_sn = I('order_sn');
            if (empty($order_sn)) {
                $this->ajaxReturn(array("code" => "error", "msg" => "没有订单号"));
            } else {
                $data = array();
                $order_info = M('order')->where(array('order_sn' => $order_sn))->find();
                if (!empty($order_info)) {
                    $data['order_sn'] = $order_info['order_sn'];
                    $data['paystyle'] = $order_info['paystyle'];
                    $data['pay_time'] = date("Y-m-d H:i:s", $order_info['pay_time']);
                    $data['check_user_name'] = $this->getCheckUser($order_info['order_id']);
                    $data['discount'] = $order_info['discount'] / 10;
                    $data['discount_money'] = (1 - $order_info['discount'] / 100) * $order_info['total_amount'];
                    $data['integral'] = $order_info['integral'];
                    $data['integral_money'] = $order_info['integral_money'];
                    $data['user_money'] = $order_info['user_money'];
                    $data['coupon_price'] = $order_info['coupon_price'];
                    $data['order_benefit'] = $order_info['order_benefit'];

                    $data['order_goods'] = M('order_goods')
                        ->where(array('order_id' => $order_info['order_id']))
                        ->Field('goods_id,spec_key,bar_code,goods_name,goods_price,goods_num,goods_price*goods_num as subtotal')
                        ->select();
                    foreach ($data['order_goods'] as $key => &$value){
                        $value['goods_img']= M('goods')->where(array('goods_id'=>$value['goods_id']))->getField('goods_img1');
                    }
                    get_date_dir($this->path, 'Recwindow_getDoubleScreenDetail', '订单数据', json_encode($data));


                    $this->ajaxReturn(array("code" => "success", "msg" => "查询成功", "data" => $data));


                    /*
                                         discount折扣，discount_money使用折扣抵用，integral使用积分，integral_money使用积分抵多少钱，user_money使用余额，coupon_price优惠券抵扣多少钱*/

                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "没有数据"));

                }

            }


        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请求错误"));

        }

    }

    public function getCheckUser($order_id)
    {
        $checkuser = $this->pay_model->alias('pay')->join('ypt_merchants_users yus on yus.id=pay.checker_id ')->where(array('pay.order_id' => $order_id))->getField('yus.user_name');

        if (empty($checkuser)) {
            $checkuser = M('merchants_users')->where('id=' . $this->userId)->getField('user_name');

        }

        return $checkuser;

    }

    //商品模糊搜索
    public function search_goods_list()
    {
        // $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $two_type = I('two_type', 1);
        if ($two_type == 2) {
            $listRows = 11;
        } else {
            $listRows = 6;
        }

        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }
        // $this->userId =26;
        // if (!$search_like) {
        //     $this->ajaxReturn(array("code"=>"error","msg"=>"content is empty","data"=>""));
        // } else {
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $map['trade'] = $two_type;
        $map['put_two'] = 2;
        $map['goods_name|bar_code'] = array('like', '%' . $search_like . '%');
        $goods = M("goods")
            ->where($map)
            ->where("is_delete=0 and mid=$uid")
            ->field('goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku,group_id,discount')
            ->order('goods_id')->limit($firstRow . ',' . $listRows)
            ->select();
        $count = M("goods")
            ->where($map)
            ->where("is_delete=0 and mid=$uid")
            ->count();
        $page_count = ceil($count / $listRows);
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $aa = array();
                $aa['price'] = $value['shop_price'];
                $aa['discount'] = $value['discount'];
                $goods[$key]["properties"][] = $aa;
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('discount,properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]["properties"][$k]['discount'] = $ress[$k]["discount"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
            if ($value['group_id'] == 0) {
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                    $goods[$key]['group_id'] = $group_id;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            } else {
                $group_name = M('goods_group')->where(array('group_id' => $value['group_id']))->getField('group_name');
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            }
        }
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));
        // }
    }


    //商品首字母搜索
    public function search_letters()
    {
        $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $listRows = 6;
        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }
        // $this->userId = 26;
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        // dump($role_id);
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $sql = "select upper(pinyin(goods_name)) as un,goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku,group_id from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%' order by goods_id limit $firstRow,$listRows";
        $goods = M('goods')->query($sql);
        // echo M('goods')->getLastSql();
        // die;
        $s = "select count(goods_name) from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%'";
        $count = M('goods')->query($s);
        $count = (int)$count[0]['count(goods_name)'];
        $page_count = ceil($count / $listRows);
        // dump($page_count);die;
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $goods[$key]["properties"] = array();
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {
                // dump($goods[$key]["goods_id"]);

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
            // dump($this->userId);die;
            // $mid = $this->_get_mch_id($uid);
            if ($value['group_id'] == 0) {
                // echo '111';
                // dump($mid);
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                // dump($group_id);die;
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                    $goods[$key]['group_id'] = $group_id;
                } else {
                    $goods[$key]['group_name'] = array();
                }

            } else {
                // echo '222';
                $group_name = M('goods_group')->where(array('group_id' => $value['group_id']))->getField('group_name');
                // dump($group_name);die;
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            }
        }
        // dump($goods);die;
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));

    }

    /**
     * 获取商家ID
     * @Param uid 商家uid
     */
    public function _get_mch_id($uid)
    {
        $id = M('merchants')->where(array('uid' => $uid))->getField('id');
        // echo $this->merchants->setLastSql();die;
        return $id;
    }

    //商品首字母搜索
    public function search_letters_demo()
    {
        // echo '1111';die;
        // $sql = "select upper(pinyin(goods_name)) as un,goods_name from ypt_goods where mid=181 and is_delete=0 and upper(pinyin(goods_name)) like '%yk%'";
        // $sql = "select p.goods_name,c.* from ypt_goods p , ypt_cosler c where  CONV( HEX( LEFT( CONVERT( goods_name
        // USING gbk ) , 1 ) ) , 16, 10 )  between c.cBegin and c.cEnd and c.fPY='L' and is_delete=0 and mid=181";
        //  // $sp='A';
        //  // $sql = "select goods_name from ypt_goods where goods_name='A' and dbo.f_GetPy(goods_name) like '%". $sp. "%' order by goods_name";
        // $res = M('goods')->query($sql);
        // dump($res);
        $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $listRows = 6;
        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }

        // if (!$search_like) {
        //     $this->ajaxReturn(array("code"=>"error","msg"=>"content is empty","data"=>""));
        // } else {
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        // $map['goods_name|bar_code']  = array('like','%'.$search_like.'%');
        $sql = "select upper(pinyin(goods_name)) as un,goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%' order by goods_id limit $firstRow,$listRows";
        $goods = M('goods')->query($sql);
        $s = "select count(goods_name) from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%'";
        $count = M('goods')->query($s);
        // $goods=M("goods")
        // ->where($map)
        // ->where("is_delete=0 and mid=$uid")
        // ->field('goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku')
        // ->order('goods_id')->limit($firstRow.','.$listRows)
        // ->select();
        // $count=M("goods")
        // // ->where($map)
        // ->where("is_delete=0 and mid=$uid")
        // ->count();

        // $count= $count + 1 - 1;

        $count = (int)$count[0]['count(goods_name)'];
        $page_count = ceil($count / $listRows);
        // dump($page_count);die;
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $goods[$key]["properties"] = array();
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {
                // dump($goods[$key]["goods_id"]);

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
        }
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));

    }

    //初始化登录页生成流水号
    public function get_order_sn()
    {
        $order_sn = date('YmdHis') . mt_rand(10000, 99999) . U;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $order_sn));
    }

    //点击挂单
    public function res_order()
    {
        if (IS_POST) {
            $order_info = array();
            $order_info["order_sn"] = I("order_sn"); //流水号
            $order_info["order_amount"] = I("order_amount"); //应收金额
            $order_info["pay_status"] = I("pay_status"); //支付状态为0
            //$order_info["pay_time"]  = I("pay_time");//支付时间
            $order_info["coupon_code"] = I("coupon_code", ""); //优惠券ID
            $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount"); //原订单总价
            $order_info["user_id"] = I('uid') ? I('uid') : $this->userId; //当前使用双屏的用户ID
            $order_info["add_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
            $order_info["card_code"] = I("card_id", ""); //会员卡号
            /* $order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
            $order_info["goods_num"]  = 4;
            $order_info["goods_price"]  = 32;
            $order_info["total_amount"]  = 30;
            $order_info["user_id"]  = 71;*/
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            //echo $res;die;
            M()->commit();
            if ($res) {
                // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $goods_id = explode(",", I("goods_id"));
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                $sku_id = explode(",", I("sku_id"));
                /* $bar_code = "5588585,5668885,11111111";
                $bar_code = explode(",",$bar_code);
                $discount = "50,90,10";
                $discount = explode(",",$discount);
                $goods_num = "5,3,14";
                $goods_num = explode(",",$goods_num);*/
                foreach ($bar_code as $key => $val) {
                    //$goods = array();
                    $order_goods[$key]["bar_code"] = $val;
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["goods_id"] = $goods_id[$key];
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                    $order_goods[$key]["spec_key"] = $sku_id[$key];
                };
                $result = $goods->addAll($order_goods);
                if ($result) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "挂单成功"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "挂单失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }

    }

    //点击取单
    public function get_order()
    {
        if (IS_POST) {
            $user_id = I('uid') ? I('uid') : $this->userId;
            //$user_id = '46';
            $order_info = M("order")
                ->where("user_id =$user_id AND  pay_status = 0 ")
                ->Field("order_sn,total_amount,order_amount,user_id,discount,order_benefit,order_goods_num")
                ->select();
            /*$order_info['properties'] = array();
            $order_info['properties']['discount'] = '100';
            $order_info['properties']['price'] = '15';
            $order_info['properties']['sku_id'] = '1';
            $order_info['properties']['propertie'] = '';*/
            //p($order_info);
            //echo "<pre>";
            //var_dump($order_info);die;*/
            if (!$order_info) {
                $this->ajaxReturn(array("code" => "success", "msg" => "当前没有挂单", "data" => array()));
            } else {
                $this->ajaxReturn(array("code" => "success", "msg" => "false", "data" => $order_info));
            }

        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }

    }

    //删除挂单
    public function del_order1()
    {
        if (IS_POST) {
            $order_sn = I('order_sn');
            //$order_sn = '201704191412437776UI';
            $data = M('order')->where(array('order_sn' => $order_sn))->delete();
            //echo M()->_sql();
            if ($data) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未知错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "参数错误！"));
        }
    }

    //挂单删除
    public function del_order()
    {
        if (IS_POST) {
            $order_sn = I('order_sn');
            $order_sn = explode(',', $order_sn);
            $where['order_sn'] = is_array($order_sn) ? array('in', $order_sn) : $order_sn;
            $data = M("order")
                ->where($where)
                ->delete();
            // echo M()->_sql();die;
            if ($data) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未知错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //进入挂单页点击取回
    public function get_order_goods()
    {

        $order_sn = I('order_sn');
        //$order_sn = '2017050817073748463U';
        $order_info = M('order')
            ->where(array('order_sn' => $order_sn))
            ->field('order_id,coupon_code,coupon_price,card_code,order_benefit')
            ->find();
        //p($order_info);
        $order_id = $order_info['order_id'];
        //$order_id = 1538;
        //var_dump($order_id);
        $order_goods_info = M('order_goods')->alias('g')
            ->join("LEFT JOIN ypt_goods_group a ON g.group_id = a.group_id")
            ->join("LEFT JOIN ypt_goods_sku u ON u.sku_id = g.spec_key")
            ->where(array('order_id' => $order_id))
            ->field('g.goods_id,a.group_name,g.group_id,g.goods_name,g.goods_price,g.discount,g.bar_code,g.goods_num,g.sku,convert((g.goods_price*g.goods_num*g.discount/100),decimal(10,2)) as xiaoji,g.spec_key as sku_id,u.properties')
            ->select();
        // dump($order_goods_info['goods_price']);
        //p($order_goods_info);
        //p($order_goods_info);
        //$order_goods_info['order_id'] = $order_id;
        //$order_goods_info['properties'][] = array();
        $data = array();
        $data['properties'] = $order_goods_info;
        $data['coupon_code'] = $order_info['coupon_code'];
        $data['coupon_price'] = $order_info['coupon_price'];
        $data['card_id'] = $order_info['card_code'];
        $data['order_benefit'] = $order_info['order_benefit'];
        /*$order_goods_info['properties']['discount'] = '100';
        $order_goods_info['properties']['price'] = '15';
        $order_goods_info['properties']['sku_id'] = '1';
        $order_goods_info['properties']['propertie'] = '';*/
        //p($data);
        if (is_array($data)) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "查询失败！"));
        }

    }

    //点击支付
    public function pay_order()
    {
        if (IS_POST) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '数据', json_encode($_POST));
            $order_info = array();
            $order_sn = I("order_sn"); //流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["discount"] = I('order_discount');
            $order_info["discount_money"] = I('discount_money', $order_amount * I('order_discount') / 100);
            $order_info["order_amount"] = $order_amount; //应收金额
            $order_info["pay_status"] = I("pay_status"); //支付状态为1
            $order_info["type"] = "4"; //3为pos机订单
            $order_info['integral'] = I('dikoufen'); //该订单使用积分
            $order_info['integral_money'] = I('dikoujin'); //该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code; //优惠券ID
            $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount"); //订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id; //当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
            $card_code = I("card_id", "");
            $order_info["card_code"] = $card_code; //会员卡号
            $paystyle_id = I('paystyle_id');
            $order_info["user_money"] = I('user_money', 0);
            $order_info["paystyle"] = $paystyle_id;//现金支付
            $two_type = I('two_type', 1);   //行业类别   1=便利店  2=餐饮
            // $order_info["group_id"]  = I('group_id');
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {

                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $group_id = explode(",", I("group_id"));
                $sku = explode(",", I("sku"));
                $goods_id = explode(",", I("goods_id"));
                $goods_weight = explode(",", I("goods_weight"));
                if ($two_type == 2) {
                    foreach ($goods_id as $key => $val) {
                        $order_goods[$key]['order_id'] = $res;
                        $order_goods[$key]["goods_id"] = $val;
                        $order_goods[$key]["goods_name"] = $goods_name[$key];
                        $order_goods[$key]["goods_num"] = $goods_num[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"] = $discount[$key];
                        $order_goods[$key]["group_id"] = $group_id[$key];
                        $order_goods[$key]["sku"] = $sku[$key];//规格编号

                    }
                } else {
                    foreach ($bar_code as $key => $val) {
                        $order_goods[$key]['order_id'] = $res;
                        $order_goods[$key]["bar_code"] = $val;
                        $order_goods[$key]["goods_name"] = $goods_name[$key];
                        $order_goods[$key]["goods_num"] = $goods_num[$key];
                        $order_goods[$key]["goods_weight"] = $goods_weight[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"] = $discount[$key];
                        $order_goods[$key]["group_id"] = $group_id[$key];
                        $order_goods[$key]["sku"] = $sku[$key];//规格编号
                        $order_goods[$key]['goods_id'] = M('goods')->where(array('mid' => $user_id, 'bar_code' => $val))->getField('goods_id');


                        /*   //扣减库存
                           if (!$val || !$sku[$key]) {
                               M()->rollback();
                               $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));

                           }
                           $stock_flag = $this->decrease_stock($val, $sku[$key], $goods_num[$key]);
                           if ($stock_flag['code'] == 'error') {
                               M()->rollback();
                               $this->ajaxReturn($stock_flag);

                           }*/

                    }
                }

                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'order_goods数据', json_encode($order_goods));

                $result = $goods->addAll($order_goods);
                //M('memcard_user');
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'order_goods数据', $result . '--+--' . $res);
                if ($result && $res) {
                    if ($card_code != '') {
                        $card_info = M('screen_memcard m')
                            ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                            ->where("card_code='$card_code' or entity_card_code='$card_code'")
                            ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                            ->find();
                        $ass = floor($order_amount / $card_info['expense']) * $card_info['expense_credits'];
                        //M('screen_memcard_use')->where("card_code='$card_code' or entity_card_code='$card_code'")->setInc('card_amount', $ass);
                        //M('screen_memcard_use')->where("card_code='$card_code' or entity_card_code='$card_code'")->setInc('card_balance', $ass);
                        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                        $memcard_info = array(
                            "memid" => $card_info['memid'],
                            "status" => 1,
                            "point" => $ass,
                            "card_id" => $card_info['card_id'],
                            "add_time" => time(),
                            "merchants_id" => $this->userId,
                        );
                        M('memcard_user')->data($memcard_info)->add();
                    }
                    if ($paystyle_id == 5) {
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 4,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 5,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "paytime" => time(),
                            'order_id' => $res
                        );
                        $pay = $this->pay_model;
                        $pay_add_res = $pay->add($pay_info);
                        A("Pay/barcode")->decrease_stock($res);

                        //$ab=A("Apiscreen/Twocoupon")->use_card($code);
                        if ($pay_add_res) {
                            M()->commit();
                            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付成功', 1);
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功"));
                        } else {
                            M()->rollback();
                            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付失败', 1);
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                        }
                    } else {
                        A("Pay/barcode")->decrease_stock($res);


                        $value = A('Pay')->two_get_card($user_id, $order_sn);
                        M()->commit();
                        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'success-1', json_encode($value));
                        $this->ajaxReturn(array("code" => "success", "data" => $value));
                    }
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付成功', 1111);
                    $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                } else {
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付失败', '网络错误1');
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '失败', '网络错误2');
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '参数错误', '参数错误');
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //扣减库存
    public function decrease_stock($bar_code, $sku_id, $goods_num)
    {
        $is_success = array();
        $goods_info = M('goods')->alias('gds')->join('ypt_goods_sku ysk on ysk.goods_id=gds.goods_id')->where(array('ysk.sku_id' => $sku_id))->field('gds.goods_number,gds.goods_id')->find();
        if (!empty($goods_info)) {
            $stock_num = $goods_info['goods_number'];
            $new_stock_num = $stock_num - $goods_num;
            if ($new_stock_num < 0) {
                $is_success = array('code' => "error", 'msg' => '支付数量超出库存');

            } else {

                $sku_num = M('goods_sku')->where(array('sku_id' => $sku_id))->getField('quantity');
                $new_sku_num = $sku_num - $goods_num;
                if ($new_sku_num < 0) {
                    $is_success = array('code' => "error", 'msg' => '支付数量超出库存');

                } else {
                    $goods_update = array('goods_number' => $new_stock_num);
                    M('goods')->where(array('goods_id' => $goods_info['goods_id']))->save($goods_update); //更新商品总库存
                    $sku_update = array('quantity' => $new_sku_num);
                    M('goods_sku')->where(array('sku_id' => $sku_id))->save($sku_update); //更新商品总库存
                    $is_success = array('code' => "success", 'msg' => '支付成功');

                }

            }

        } else {
            $is_success = array('code' => "error", 'msg' => '支付失败');

        }

        return $is_success;

    }

    //验证会员卡号
    public function check_card_id()
    {
        if (IS_POST) {
            $card_code = I("card_id");
            $price = I("total_amount");
            $uid = $this->userInfo['uid'];
            //$card_code = '442743416296';
            /* $user_id = $this->userId;
            $role_id = M('merchants_role_users')->where(array('uid'=>$user_id))->getField('role_id');
            if($role_id == 7){
            $uid = M('merchants_users')->where(array('id'=>$user_id))->getField('pid');
            }else{
            $uid = $user_id;
            }*/
            if ($card_code) {
                $check = M('screen_memcard_use')->alias('mu')
                    ->join("left join __SCREEN_MEMCARD__ m on m.id=mu.memcard_id")
                    ->where("mu.card_code='$card_code'And m.mid='$uid'")
                    ->find();
                if (!$check) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "非该商家的优惠券"));
                }
                //$res = M('screen_memcard_use')->where(array('card_code' => $card_code))->field('card_id')->find();
                $res = M('screen_memcard_use')->alias('u')
                    ->join('left join ypt_screen_memcard m on m.card_id=u.card_id')
                    ->where(array('u.card_code' => $card_code))
                    ->field('u.card_id,u.card_balance,u.memid,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount')
                    ->find();
                //p($res);
                if ($res) {
                    if ($res['credits_set'] == 0) {
                        $this->ajaxReturn(array("code" => "success", "msg" => "商家关闭积分设置", "data" => array('card_de_price' => '0', 'jifen_use' => '0')));
                    } else {
                        $data = array();
                        if ($res['card_balance'] < $res['max_reduce_bonus']) {
                            $p = strval(floor($res['card_balance'] / $res['credits_use']) * $res['credits_discount']);
                        } else {
                            $p = strval(floor($res['max_reduce_bonus'] / $res['credits_use']) * $res['credits_discount']);
                        }

                        if ($p == 0 || $price < $res['credits_discount']) {
                            $data['card_de_price'] = '0';
                            $data['jifen_use'] = '0';
                        } else if ($p < $price) {
                            $data['card_de_price'] = $p;
                            $data['jifen_use'] = strval(floor($p / $res['credits_discount']) * $res['credits_use']);
                        } else {
                            $data['jifen_use'] = strval(floor($price / $res['credits_discount']) * $res['credits_use']);
                            $data['card_de_price'] = strval(($data['jifen_use'] / $res['credits_use']) * $res['credits_discount']);
                        }
                        $data['memid'] = $res['memid'];
                        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                    }
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "该会员卡无效！"));
                }
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "当前没有输入会员卡"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //交接班
    public function connect_wei()
    {

        $uid = I('uid') ? I('uid') : $this->userId;
        $mac = I('mac');
        $token = I('token');
        //$start_time =  I('timestamp');
        //$start_time =  1495008187;
        //$mac = '44:2c:05:ca:d4:30';
        //$uid = '209';
        //$uid ='991';
        $twotoken = M('twotoken');
        $twotoken->uid = $uid;
        $twotoken->where(array('token' => $token))->save();
        //p(M()->_sql());
        //$start_time = M('twotoken')->where(array('uid' => $uid))->getField('time_start');
        $start_time = M('twotoken')->where(array('token' => $token, 'uid' => $uid))->getField('time_start');
        //p(M()->_sql());
        //p($start_time);
        //$start_time = strtotime('2017-5-7 9:15:00');
        //end_time = strtotime('2017-5-8 19:15:00');
        $end_time = time();
        $res = M('order o')
            ->join("right join __PAY__ p on p.remark=o.order_sn")
            ->field('sum(o.user_money) total_user_money,sum(o.order_amount) order_amount,sum(o.total_amount) total_amount,sum(o.order_goods_num) total_num,sum(o.coupon_price) coupon_price,count(o.order_id) shuliang,count(case when o.coupon_code!=\'\' then id end) coupon_num,o.discount,p.paystyle_id')//coupon_status
            ->where("p.paytime>$start_time AND $end_time>p.paytime AND o.pay_status = 1 AND o.user_id = $uid")
            ->group('p.paystyle_id')
            ->select();
        get_date_dir($this->path, 'connect_wei', 'I', json_encode(I('')));
        get_date_dir($this->path, 'connect_wei', 'SQL', M()->_sql());
        //p($res[0]['total_amount']);
        //p(M()->_sql());
        //p(array_column($res,'total_amount'));
        /*  $info = M('order')
        ->where("pay_time>$start_time AND $end_time>pay_time AND pay_status = 1 AND user_id = $uid")
        ->field('sum(convert((total_amount*(1-discount/100)),decimal(10,2))) res')
        ->select();*/
        //p(M()->_sql());
        //$info  = array_column($info,'res');
        $info = $res[0]['total_amount'] - $res[0]['order_amount'] - $res[0]['coupon_price'];
        //p($info);
        $data = array();
        $data['coupon_num'] = array_sum(array_column($res, 'coupon_num'));
        $data['discount_price'] = $info;
        $data['total_user_money'] = '0';
        foreach ($res as $k => $v) {
            $data['total_user_money'] += $v['total_user_money'];
            $data['sales_amount'] += $v['order_amount'];
            $data['shop_amount'] += $v['total_amount'];
            $data['total_num'] += $v['total_num'];
            $data['xiaopiao_num'] += $v['shuliang'];
            $data['coupon_price'] += round($v['coupon_price'], 2);
            //$data['discount_price'] += ($v['total_amount'] - ($v['total_amount'] * $v['discount'] / 100));
            if ($v['paystyle_id'] == 1) {
                $data['wx_pay'] = $v['order_amount'];
            } elseif ($v['paystyle_id'] == 2) {
                $data['ali_pay'] = $v['order_amount'];
            } elseif ($v['paystyle_id'] == 5) {
                $data['cash_pay'] = $v['order_amount'];
            }
        }
        $data['total_user_money'] = strval($data['total_user_money']);
        //p($data);
        /*$field="p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
        ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        }*/
        //p($data);
        $mac_id = M('screen_pos')->where(array('mac' => $mac))->Field('id')->find();
        $role_id = M('merchants_role_users')->where(array('uid' => $uid))->getField('role_id');
        //p($role_id);
        //$role_id = 3;

        if ($role_id) {
            if (!in_array($role_id, array(2, 3))) {
                $user = M('merchants_users');
                $pid = $user->field('pid')->where(array('id' => $uid))->find();
                //p($pid);
                $pid = $pid['pid'];
                //p($pid);
                $as = $user->field('id,user_name')->where(array('pid' => $pid, 'status' => 0))->select();
                $connect = array();
                foreach ($as as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                //p(M()->_sql());
                $asd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $data['jiebanren'] = $connect;
                array_unshift($asd, 'shouyin');
                $data['dangqian'] = $asd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            } else {
                $user = M('merchants_users');
                $d = $user->field('id,user_name')->where(array('pid' => $uid, 'status' => 0))->select();
                $dd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $connect = array();
                foreach ($d as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                $data['jiebanren'] = $connect;
                array_unshift($dd, 'shangjia');
                $data['dangqian'] = $dd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '网络错误'));
        }

    }

    private function checkConnectAuth($uid)
    {
        $auth_id = 14;   // 交接班权限id
        $role_id = M('merchants_role_users')->where(array('uid' => $uid))->getField('role_id');
        if (!$role_id || $role_id == '3') return true;
        $screen_auth = M('merchants_role')->where("id=$role_id")->getField('screen_auth');
        if (!$screen_auth) return false;
        $screen_auth = explode(',', $screen_auth);
        if (in_array($auth_id, $screen_auth)) return true;

        return false;
    }

    //双屏收银广告
    public function screen_idea()
    {
        $res = M('adver')->field('thumb')->where(array('callstyle' => 3))->order('id desc')->limit(3)->select();
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['thumb'] = $host . $v['thumb'];
            }
        } else {
            $res[0]['thumb'] = 'http://sy.youngport.com.cn/data/upload/ad/uploadimages/20170607143741_326134.jpg';
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));

    }

    //后台推送
    public function adminpush2()
    {
        /*$openid = 'ssaddaw';
        $red = '0.02';
        $_SESSION[$openid]=$red;
        var_dump($_SESSION[$openid]);*/
        /*ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        vendor('JPush.src.JPush.JPush');
        $br = '<br/>';
        $app_key = '74cf5522a74ab07a4442b92f';
        $master_secret = '376aab71e4322352a2b762da';

        // 初始化
        $client = new \JPush($app_key, $master_secret);

        // 简单推送
        $result = $client->push()
        ->setPlatform('all')
        ->addAllAudience()
        ->setNotificationAlert('Hi！Youngport~~~')
        ->send();

        echo 'Result=' . json_encode($result) . $br;*/

        //查询本月的数据
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-d H:i:s');
        $sql = "select `uid` id  from `ypt_post_token`  WHERE `add_time` >= unix_timestamp('$start') AND `add_time` <= unix_timestamp('$end')";
        $ress = M('post_token')->query($sql);
        //查询近30天的数据
        $ss = 30;
        $start = date('Y-m-d H:i:s', strtotime("-$ss day"));
        $end = date('Y-m-d H:i:s');
        //$sql = "select `uid` id  from `ypt_post_token`  WHERE `add_time` >= unix_timestamp('$start') AND `add_time` <= unix_timestamp('$end')";
        //p($start);
        //p($ress);
        //p(M()->_sql());
        $timess = $this->type_time(1);
        $map['add_time'] = array('between', $timess);
        $ress = M('post_token')->where($map)->field('uid')->select();
        //p(M()->_sql());
        //p(date("m-d-Y", mktime(0, 0, 0, 12, 32, 1997)));
    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     * mktime(时，分，秒，月，日，年)
     * a:   "am"或是"pm"
     * A:   "AM"或是"PM"
     * d:   几日，两位数字，若不足则补零；从"01"至"31"
     * D:    星期几，3个英文字母，如:"Fri"
     * F:    月份，英文全名，如:"January"
     * h:    12小时制的小时，从"01"至"12"
     * H:    24小时制的小时，从"00"至"23"
     * g:    12小时制的小时，不补零；从"1"至"12"
     * G:    24小时制的小时，不补零；从"0"至"23"
     * j:    几日，不足不被零；从"1"至"31"
     * l:    星期几，英文全名，如："Friday"
     * m:    月份，两位数字，从"01"至"12"
     * n:    月份，两位数字，不补零；从"1"至"12"
     * M:    月份，3个英文字母；如："Jan"
     * s:   秒；从"00"至"59"
     * S:    字尾加英文序数，两个英文字母,如："21th"
     * t:    指定月份的天数，从"28"至"31"
     * U:    总秒数
     * w:    数字型的星期几，从"0(星期天)"至"6(星期六)"
     * Y:    年，四位数字
     * y:    年，两位数字
     * z： 一年中的第几天；从"1"至"366"
     */
    public function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    //支付宝接口调用
    public function ali_pay()
    {
        $order_info = array();
        $order_sn = I("order_sn"); //流水号
        $order_info["order_sn"] = $order_sn;
        $order_amount = I("order_amount");
        $order_info["discount"] = I('order_discount');
        $order_info["order_amount"] = $order_amount; //应收金额
        $order_info["pay_status"] = '0'; //支付状态为0
        $order_info["type"] = "3"; //3为pos机订单
        $order_info['integral'] = I('dikoufen'); //该订单使用积分
        $order_info['integral_money'] = I('dikoujin'); //该订单使用积分抵扣金额
        $code = I("coupon_code", "");
        $order_info["coupon_code"] = $code; //优惠券ID
        $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
        $order_info["order_goods_num"] = I("order_goods_num");
        $order_info["total_amount"] = I("total_amount"); //订单总价
        $user_id = I('uid') ? I('uid') : $this->userId;
        $order_info["user_id"] = $user_id; //当前使用双屏的用户ID
        $order_info["pay_time"] = I("timestamp");
        $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
        $card_code = I("card_id", "");
        $order_info["card_code"] = $card_code; //会员卡号
        $paystyle_id = I('paystyle_id');
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == '7') {
            $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
            $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
            $checker_id = $this->userId;
        } else {

            $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
            $checker_id = '0';
        }

        /*$order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
        $order_info["goods_num"]  = 4;
        $order_info["goods_price"]  = 32;
        $order_info["total_amount"]  = 30;
        $order_info["user_id"]  = 71;*/
        M()->startTrans(); // 开启事务
        $data = M('order');
        $res = $data->add($order_info);
        //$card_code  = '442743416296';
        //$price  = '12';
        /*if($card_code!==''){
        $card_info = M('screen_memcard m')
        ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
        ->where(array('card_code'=>$card_code))
        ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
        ->find();
        $ass = floor($order_amount/$card_info['expense'])*$card_info['expense_credits'];
        M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_amount',$ass);
        M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
        $memcard_info=array(
        "memid"=>$card_info['memid'],
        "status"=>1,
        "point"=>$ass,
        "card_id"=>$card_info['card_id'],
        "add_time" =>time(),
        "merchants_id"=>$this->userId
        );
        M('memcard_user')->data($memcard_info)->add();
        }*/
        if ($res) {
            // 加入订单表
            $order_goods = array();
            $goods = M("order_goods");
            $bar_code = explode(",", I("bar_code"));
            $goods_num = explode(",", I("goods_num"));
            $goods_name = explode(",", I("goods_name"));
            $goods_price = explode(",", I("goods_price"));
            $discount = explode(",", I("goods_discount"));
            $sku = explode(",", I("sku"));
            /* $bar_code = "5588585,5668885,11111111";
            $bar_code = explode(",",$bar_code);
            $discount = "50,90,10";
            $discount = explode(",",$discount);
            $goods_num = "5,3,14";
            $goods_num = explode(",",$goods_num);*/
            foreach ($bar_code as $key => $val) {
                $order_goods[$key]['order_id'] = $res;
                $order_goods[$key]["bar_code"] = $val;
                $order_goods[$key]["goods_name"] = $goods_name[$key];
                $order_goods[$key]["goods_num"] = $goods_num[$key];
                $order_goods[$key]["goods_price"] = $goods_price[$key];
                $order_goods[$key]["discount"] = $discount[$key];
                $order_goods[$key]["sku"] = $sku[$key];
            };
            $result = $goods->addAll($order_goods);
            //M('memcard_user');
            if ($result && $res) {
                M()->commit();
                $ab = $this->zfb_pay($order_sn, $order_amount);
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $ab));
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
        }

    }

    public function zfb_pay($order_sn, $price)
    {
        // 支付宝合作者身份ID，以2088开头的16位纯数字
        $partner = "2017010704905089";
        // 支付宝账号
        $seller_id = 'guoweidong@hz41319.com';
        // 商品网址
        // 异步回调地址
        //$notify_url = 'http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/ali_barcode_pay';
        $notify_url = 'http://a.ypt5566.com/notify.php?s=/Post/Index/zfb_notify_url';
        // 订单标题
        $subject = '1';
        // 订单详情
        $body = '我是测试数据';
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $content = array();
        $content['timeout_express'] = '30m';
        $content['product_code'] = 'QUICK_MSECURITY_PAY';
        $content['total_amount'] = $price;
        $content['subject'] = $subject;
        $content['body'] = $body;
        $content['out_trade_no'] = $order_sn;
        //$orderinfo['order_amount'];
        $data = array();
        $data['app_id'] = $partner;
        $data['biz_content'] = json_encode($content);
        $data['charset'] = 'utf-8';
        $data['format'] = 'json';
        $data['method'] = 'alipay.trade.app.pay';
        $data['notify_url'] = $notify_url;
        $data['sign_type'] = 'RSA';
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['version'] = '1.0';
        $orderInfo = $this->createLinkstring($data);
        //$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
        //var_dump($orderInfo);
        $sign = $this->sign($orderInfo);
        //var_dump($sign);
        $data['sign'] = $sign;
        $orderInfo = $this->getSignContentUrlencode($data);

        return $orderInfo;
    }

    public function createLinkstring($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if (trim($value) === "") {
            return true;
        }

        return false;
    }

    public function getSignContentUrlencode($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $params['sign'] = $sign;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = "MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
        //$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function rsaSign($data)
    {
        $rsaPrivateKey = 'MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';
        $priKey = $rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($res);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    public function zfb_notify_url()
    {
        $data = $_POST;
        $order_sn = $data['out_trade_no'];
        $pay_code = $data['trade_no'];
        $order_amount = $data['total_amount'];
        $sign = $data['sign'];
        $data['sign_type'] = null;
        $data['sign'] = null;
        $data = $this->getSignContent($data);
        $pubKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        if ($result && $_POST['trade_status'] == 'TRADE_SUCCESS') {
            $Order = M("order"); // 实例化对象
            // 要修改的数据对象属性赋值
            $Order->pay_code = $pay_code;
            $Order->pay_status = '1';
            $Order->add_time = time();
            $Order->order_amount = $order_amount;
            $Order->where(array('order_sn' => $order_sn))->save(); // 根据条件更新记录
            $Pay = $this->pay_model; // 实例化对象
            $Pay->status = '1';
            $Pay->add_time = time();
            $Pay->price = $order_amount;
            $Pay->where(array('remark' => $order_sn))->save();
        }
    }

    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . stripslashes($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . stripslashes($v);
                }
                $i++;
            }
        }

        unset($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 设置双屏行业
     */
    public function set_type()
    {
        if (IS_POST) {
            ($two_type = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));  //行业类别  1=便利店  2=餐饮
            if (in_array($two_type, array('1', '2'))) {
                $user_id = $this->userId;
                if (M('merchants')->where(array('uid' => $user_id))->setField('two_type', $two_type)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "设置成功", "two_type" => $two_type));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "设置失败"));
                }
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "未定义的类型"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     * 商品分组
     * @return [type] [description]
     */
    public function group_list()
    {
        if (IS_POST) {
            ($trade = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));  //行业  1=便利店  2=餐饮
            $mid = $this->userId;
            $data = M('goods_group')->where(array('mid' => $mid, 'trade' => $trade, 'gid' => 0))->order(array('sort' => 'asc', 'add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
            foreach ($data as $k => &$v) {
                $res = M('goods_group')->where(array('mid' => $mid, 'gid' => $v['group_id'], 'trade' => $trade))->order(array('sort' => 'asc', 'add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
                $v['sub'] = $res;
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }

    }

    public function foreach_goods()
    {
        if (IS_POST) {
            if ($search_like = I('search_like')) {
                $where['goods_name|bar_code'] = array('like', '%' . $search_like . '%');
            }
            if ($group_id = I('group_id')) {
                $where['group_id'] = $group_id;
            }
            $role_id = M('merchants_role_users')->where(array('uid' => $this->userId))->getField('role_id');
            if (!in_array($role_id, array(2, 3))) {
                $uid = M('merchants_users')->where(array('id' => $this->userId))->getField('pid');
            } else {
                $uid = $this->userId;
            }
            // $uid = $this->userId;
            ($trade = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));
            $page = I('page', 0);
            $per_page = 11;
            $where['mid'] = $uid;
            $where['is_delete'] = 0;
            $where['put_two'] = 2;
            $where['trade'] = $trade;
            $count = M('goods')->where($where)->count();
            $total = ceil($count / $per_page);//总页数
            $lists = M('goods')->where($where)->limit($page * $per_page, $per_page)->field('goods_id,goods_name,shop_price as price,goods_brief,star,sales,original_price,group_id,goods_img1,is_sku')->select();
            // echo M('goods')->getLastSql();
            // dump($lists);
            foreach ($lists as &$v) {
                $picture = $v['goods_img1'];
                if (preg_match("/\x20*https?\:\/\/.*/i", $v['goods_img1'])) {
                    $v['picture'] = $picture;
                } else {
                    $v['picture'] = URL . $picture;
                }
                unset($v['goods_img1']);
                if ($v['is_sku'] == 1) {
                    $v['sku'] = M('goods_sku')->where(array('goods_id' => $v['goods_id']))->field('properties,sku_id,price')->select();
                }
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array(
                "total" => $total,
                "count" => $count,
                "data" => $lists,
            )));

        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }


    }


}
=======
<?php

namespace Apiscreen\Controller;

use Common\Controller\ScreenbaseController;

class RecwindowController extends ScreenbaseController
{

    private $pay_model;
    public function _initialize()
    {
        parent::_initialize();
        $this->pay_model = M('pay');
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/';
    }

    // 商品检索
    public function goods_list()
    {
        // $this->userId =26;
        ($bar_code = I("bar_code")) || $this->ajaxReturn(array("code" => "error", "msg" => "bar_code is empty", "data" => ""));
        $user_id = $this->userId;
        // dump($user_id);
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if (!in_array($role_id, array(2, 3))) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $goods_id = M("goods")->where("bar_code='$bar_code' AND mid=$uid and is_delete=0")->getField("goods_id");
        // echo M("goods")->getLastSql();die;
        //        $goods_list = M("goods g")
        //            ->join("__GOODS_SKU__ gs on g.goods_id=gs.goods_id")
        //            ->where('g.goods_id = '.$goods_id)
        //            ->field("g.goods_name,g.shop_price,g.bar_code,g.discount,gs.properties")
        //            ->select();
        if ($goods_id == "") {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => array()));
        } else {
            $goods = M('goods')->where('goods_id=' . $goods_id)->field('goods_id,goods_name,shop_price,bar_code,discount,group_id,put_two,trade')->find();
            if ($goods['put_two'] != 2) {
                $this->ajaxReturn(array("code" => "error", "msg" => "该商品未上架", "data" => array()));
            }
            if ($goods['group_id'] == 0) {
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                if ($group_name) {
                    $goods['group_name'] = $group_name;
                    $goods['group_id'] = $group_id;
                } else {
                    $goods['group_name'] = array();
                }
            } else {
                $group_name = M('goods_group')->where(array('group_id' => $goods['group_id']))->getField('group_name');
                if ($group_name) {
                    $goods['group_name'] = $group_name;
                } else {
                    $goods['group_name'] = array();
                }
            }
            $ress = M('goods_sku')->where('goods_id=' . $goods_id)->field('discount,price,sku_id,properties')->select();
            if ($ress) {
                $goods['properties'] = $ress;
                foreach ($goods['properties'] as $k => $v) {
                    //dump($v["properties"]);
                    $goods['properties'][$k]["properties"] = $v["properties"];
                }
            } else {
                $aa = array();
                $aa['price'] = $goods['shop_price'];
                $aa['discount'] = $goods['discount'];
                //$aa['discount']='11111';
                $goods['properties'][] = $aa;
                //unset($goods['discount']);
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods));
        }

    }

    /**
     * desc 获取所有双频订单支付成功的信息
     *
     * @return void
     * @author
     **/
    public function getDoubleScreenOrders()
    {
        $paystyle = array(1 => '微信支付', 2 => '支付宝', 5 => '现金支付', 3 => '刷卡', 6 => '储值余额');
        $user_id = $this->userId;
        if (IS_POST) {

            $today = date('Y-m-d');
            $end_time = strtotime($today . ' 23:59:59');
            $before_time = date("Y-m-d", strtotime("-14 days"));
            $start_time = strtotime($before_time);

            $data = M('order')->where(array('user_id' => $user_id, 'pay_status' => 1, 'order_status' => 5, 'type' => 4, 'pay_time' => array(array('EGT', $start_time), array('ELT', $end_time), 'AND')))->field('order_id,order_sn,paystyle,pay_time,order_amount,total_amount')->order(' pay_time desc')->select();

            get_date_dir($this->path, 'Recwindow_getDoubleScreenOrders', 'SQL', M()->_sql());
            if (!empty($data)) {
                foreach ($data as $key => $val) {
                    $data[$key]['pay_style_name'] = $paystyle[$val['paystyle']];
                    $data[$key]['pay_time'] = date("Y-m-d H:i:s", $val['pay_time']);
                    $data[$key]['check_user_name'] = $this->getCheckUser($val['order_id']);

                }

                get_date_dir($this->path, 'Recwindow_getDoubleScreenOrders', '订单数据', json_encode($data));

                $this->ajaxReturn(array("code" => "success", "msg" => "查询成功", "data" => $data));

            } else {

                $this->ajaxReturn(array("code" => "error", "msg" => "没有数据"));

            }

        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请求错误"));

        }

    }


    /**
     * desc 获取所有双频订单支付成功的信息
     *
     * @return void
     * @author
     **/
    public function getOrderGoodsBySn()
    {

        if (IS_POST) {

            $order_sn = I('order_sn');
            if (empty($order_sn)) {
                $this->ajaxReturn(array("code" => "error", "msg" => "没有订单号"));
            } else {
                $data = array();
                $order_info = M('order')->where(array('order_sn' => $order_sn))->find();
                if (!empty($order_info)) {
                    $data['order_sn'] = $order_info['order_sn'];
                    $data['paystyle'] = $order_info['paystyle'];
                    $data['pay_time'] = date("Y-m-d H:i:s", $order_info['pay_time']);
                    $data['check_user_name'] = $this->getCheckUser($order_info['order_id']);
                    $data['discount'] = $order_info['discount'] / 10;
                    $data['discount_money'] = (1 - $order_info['discount'] / 100) * $order_info['total_amount'];
                    $data['integral'] = $order_info['integral'];
                    $data['integral_money'] = $order_info['integral_money'];
                    $data['user_money'] = $order_info['user_money'];
                    $data['coupon_price'] = $order_info['coupon_price'];
                    $data['order_benefit'] = $order_info['order_benefit'];

                    $data['order_goods'] = M('order_goods')
                        ->where(array('order_id' => $order_info['order_id']))
                        ->Field('goods_id,spec_key,bar_code,goods_name,goods_price,goods_num,goods_price*goods_num as subtotal')
                        ->select();
                    foreach ($data['order_goods'] as $key => &$value){
                        $value['goods_img1']= M('goods')->where(array('goods_id'=>$value['goods_id']))->getField('goods_img1');
                    }
                    get_date_dir($this->path, 'Recwindow_getDoubleScreenDetail', '订单数据', json_encode($data));


                    $this->ajaxReturn(array("code" => "success", "msg" => "查询成功", "data" => $data));


                    /*
                                         discount折扣，discount_money使用折扣抵用，integral使用积分，integral_money使用积分抵多少钱，user_money使用余额，coupon_price优惠券抵扣多少钱*/

                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "没有数据"));

                }

            }


        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "请求错误"));

        }

    }

    public function getCheckUser($order_id)
    {
        $checkuser = $this->pay_model->alias('pay')->join('ypt_merchants_users yus on yus.id=pay.checker_id ')->where(array('pay.order_id' => $order_id))->getField('yus.user_name');

        if (empty($checkuser)) {
            $checkuser = M('merchants_users')->where('id=' . $this->userId)->getField('user_name');

        }

        return $checkuser;

    }

    //商品模糊搜索
    public function search_goods_list()
    {
        // $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $two_type = I('two_type', 1);
        if ($two_type == 2) {
            $listRows = 11;
        } else {
            $listRows = 6;
        }

        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }
        // $this->userId =26;
        // if (!$search_like) {
        //     $this->ajaxReturn(array("code"=>"error","msg"=>"content is empty","data"=>""));
        // } else {
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $map['trade'] = $two_type;
        $map['put_two'] = 2;
        $map['goods_name|bar_code'] = array('like', '%' . $search_like . '%');
        $goods = M("goods")
            ->where($map)
            ->where("is_delete=0 and mid=$uid")
            ->field('goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku,group_id,discount')
            ->order('goods_id')->limit($firstRow . ',' . $listRows)
            ->select();
        $count = M("goods")
            ->where($map)
            ->where("is_delete=0 and mid=$uid")
            ->count();
        $page_count = ceil($count / $listRows);
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $aa = array();
                $aa['price'] = $value['shop_price'];
                $aa['discount'] = $value['discount'];
                $goods[$key]["properties"][] = $aa;
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('discount,properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]["properties"][$k]['discount'] = $ress[$k]["discount"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
            if ($value['group_id'] == 0) {
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                    $goods[$key]['group_id'] = $group_id;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            } else {
                $group_name = M('goods_group')->where(array('group_id' => $value['group_id']))->getField('group_name');
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            }
        }
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));
        // }
    }


    //商品首字母搜索
    public function search_letters()
    {
        $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $listRows = 6;
        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }
        // $this->userId = 26;
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        // dump($role_id);
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        $sql = "select upper(pinyin(goods_name)) as un,goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku,group_id from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%' order by goods_id limit $firstRow,$listRows";
        $goods = M('goods')->query($sql);
        // echo M('goods')->getLastSql();
        // die;
        $s = "select count(goods_name) from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%'";
        $count = M('goods')->query($s);
        $count = (int)$count[0]['count(goods_name)'];
        $page_count = ceil($count / $listRows);
        // dump($page_count);die;
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $goods[$key]["properties"] = array();
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {
                // dump($goods[$key]["goods_id"]);

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
            // dump($this->userId);die;
            // $mid = $this->_get_mch_id($uid);
            if ($value['group_id'] == 0) {
                // echo '111';
                // dump($mid);
                $group_name = M('goods_group')->where(array('mid' => $uid))->getField('group_name');
                $group_id = M('goods_group')->where(array('mid' => $uid))->getField('group_id');
                // dump($group_id);die;
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                    $goods[$key]['group_id'] = $group_id;
                } else {
                    $goods[$key]['group_name'] = array();
                }

            } else {
                // echo '222';
                $group_name = M('goods_group')->where(array('group_id' => $value['group_id']))->getField('group_name');
                // dump($group_name);die;
                if ($group_name) {
                    $goods[$key]['group_name'] = $group_name;
                } else {
                    $goods[$key]['group_name'] = array();
                }
            }
        }
        // dump($goods);die;
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));

    }

    /**
     * 获取商家ID
     * @Param uid 商家uid
     */
    public function _get_mch_id($uid)
    {
        $id = M('merchants')->where(array('uid' => $uid))->getField('id');
        // echo $this->merchants->setLastSql();die;
        return $id;
    }

    //商品首字母搜索
    public function search_letters_demo()
    {
        // echo '1111';die;
        // $sql = "select upper(pinyin(goods_name)) as un,goods_name from ypt_goods where mid=181 and is_delete=0 and upper(pinyin(goods_name)) like '%yk%'";
        // $sql = "select p.goods_name,c.* from ypt_goods p , ypt_cosler c where  CONV( HEX( LEFT( CONVERT( goods_name
        // USING gbk ) , 1 ) ) , 16, 10 )  between c.cBegin and c.cEnd and c.fPY='L' and is_delete=0 and mid=181";
        //  // $sp='A';
        //  // $sql = "select goods_name from ypt_goods where goods_name='A' and dbo.f_GetPy(goods_name) like '%". $sp. "%' order by goods_name";
        // $res = M('goods')->query($sql);
        // dump($res);
        $search_like = I('search_like');
        ($search_like = I('search_like')) || $this->ajaxReturn(array("code" => "error", "msg" => "content is empty", "data" => ""));
        $p = I('p');
        $listRows = 6;
        if ($p == 1) {
            $firstRow = 0;
        } else {
            $firstRow = $listRows * ($p - 1);
        }

        // if (!$search_like) {
        //     $this->ajaxReturn(array("code"=>"error","msg"=>"content is empty","data"=>""));
        // } else {
        $user_id = $this->userId;
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == 7) {
            $uid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
        } else {
            $uid = $user_id;
        }
        // $map['goods_name|bar_code']  = array('like','%'.$search_like.'%');
        $sql = "select upper(pinyin(goods_name)) as un,goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%' order by goods_id limit $firstRow,$listRows";
        $goods = M('goods')->query($sql);
        $s = "select count(goods_name) from ypt_goods where mid=$uid and is_delete=0 and upper(pinyin(goods_name)) like '%$search_like%'";
        $count = M('goods')->query($s);
        // $goods=M("goods")
        // ->where($map)
        // ->where("is_delete=0 and mid=$uid")
        // ->field('goods_id,goods_name,bar_code,goods_number,goods_number,shop_price,is_sku')
        // ->order('goods_id')->limit($firstRow.','.$listRows)
        // ->select();
        // $count=M("goods")
        // // ->where($map)
        // ->where("is_delete=0 and mid=$uid")
        // ->count();

        // $count= $count + 1 - 1;

        $count = (int)$count[0]['count(goods_name)'];
        $page_count = ceil($count / $listRows);
        // dump($page_count);die;
        foreach ($goods as $key => $value) {
            if ($value['is_sku'] == 0) {
                $goods[$key]["properties"] = array();
                $goods[$key]['sort'] = $key + $firstRow + 1;
            } else {
                // dump($goods[$key]["goods_id"]);

                $ress = M('goods_sku')->where('goods_id=' . $goods[$key]["goods_id"])->field('properties,price,quantity,sku_id')->select();
                foreach ($ress as $k => $v) {
                    $goods[$key]["properties"][$k]["properties"] = $ress[$k]["properties"];
                    $goods[$key]["properties"][$k]['price'] = $ress[$k]["price"];
                    $goods[$key]["properties"][$k]['quantity'] = $ress[$k]["quantity"];
                    $goods[$key]["properties"][$k]['sku_id'] = $ress[$k]["sku_id"];
                    $goods[$key]['sort'] = $key + $firstRow + 1;
                }
            }
        }
        // echo json_encode(array("code"=>"success","msg"=>"true","data"=>$goods));
        $this->ajaxReturn(array("code" => "success", "msg" => "true", "data" => $goods, "page" => $page_count));

    }

    //初始化登录页生成流水号
    public function get_order_sn()
    {
        $order_sn = date('YmdHis') . mt_rand(10000, 99999) . U;
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $order_sn));
    }

    //点击挂单
    public function res_order()
    {
        if (IS_POST) {
            $order_info = array();
            $order_info["order_sn"] = I("order_sn"); //流水号
            $order_info["order_amount"] = I("order_amount"); //应收金额
            $order_info["pay_status"] = I("pay_status"); //支付状态为0
            //$order_info["pay_time"]  = I("pay_time");//支付时间
            $order_info["coupon_code"] = I("coupon_code", ""); //优惠券ID
            $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount"); //原订单总价
            $order_info["user_id"] = I('uid') ? I('uid') : $this->userId; //当前使用双屏的用户ID
            $order_info["add_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
            $order_info["card_code"] = I("card_id", ""); //会员卡号
            /* $order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
            $order_info["goods_num"]  = 4;
            $order_info["goods_price"]  = 32;
            $order_info["total_amount"]  = 30;
            $order_info["user_id"]  = 71;*/
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            //echo $res;die;
            M()->commit();
            if ($res) {
                // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $goods_id = explode(",", I("goods_id"));
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $sku = explode(",", I("sku"));
                $sku_id = explode(",", I("sku_id"));
                /* $bar_code = "5588585,5668885,11111111";
                $bar_code = explode(",",$bar_code);
                $discount = "50,90,10";
                $discount = explode(",",$discount);
                $goods_num = "5,3,14";
                $goods_num = explode(",",$goods_num);*/
                foreach ($bar_code as $key => $val) {
                    //$goods = array();
                    $order_goods[$key]["bar_code"] = $val;
                    $order_goods[$key]['order_id'] = $res;
                    $order_goods[$key]["discount"] = $discount[$key];
                    $order_goods[$key]["goods_id"] = $goods_id[$key];
                    $order_goods[$key]["goods_name"] = $goods_name[$key];
                    $order_goods[$key]["goods_price"] = $goods_price[$key];
                    $order_goods[$key]["goods_num"] = $goods_num[$key];
                    $order_goods[$key]["sku"] = $sku[$key];
                    $order_goods[$key]["spec_key"] = $sku_id[$key];
                };
                $result = $goods->addAll($order_goods);
                if ($result) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "挂单成功"));
                } else {
                    M()->rollback();
                    $this->ajaxReturn(array("code" => "error", "msg" => "挂单失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }

    }

    //点击取单
    public function get_order()
    {
        if (IS_POST) {
            $user_id = I('uid') ? I('uid') : $this->userId;
            //$user_id = '46';
            $order_info = M("order")
                ->where("user_id =$user_id AND  pay_status = 0 ")
                ->Field("order_sn,total_amount,order_amount,user_id,discount,order_benefit,order_goods_num")
                ->select();
            /*$order_info['properties'] = array();
            $order_info['properties']['discount'] = '100';
            $order_info['properties']['price'] = '15';
            $order_info['properties']['sku_id'] = '1';
            $order_info['properties']['propertie'] = '';*/
            //p($order_info);
            //echo "<pre>";
            //var_dump($order_info);die;*/
            if (!$order_info) {
                $this->ajaxReturn(array("code" => "success", "msg" => "当前没有挂单", "data" => array()));
            } else {
                $this->ajaxReturn(array("code" => "success", "msg" => "false", "data" => $order_info));
            }

        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }

    }

    //删除挂单
    public function del_order1()
    {
        if (IS_POST) {
            $order_sn = I('order_sn');
            //$order_sn = '201704191412437776UI';
            $data = M('order')->where(array('order_sn' => $order_sn))->delete();
            //echo M()->_sql();
            if ($data) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未知错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "参数错误！"));
        }
    }

    //挂单删除
    public function del_order()
    {
        if (IS_POST) {
            $order_sn = I('order_sn');
            $order_sn = explode(',', $order_sn);
            $where['order_sn'] = is_array($order_sn) ? array('in', $order_sn) : $order_sn;
            $data = M("order")
                ->where($where)
                ->delete();
            // echo M()->_sql();die;
            if ($data) {
                $this->ajaxReturn(array("code" => "success", "msg" => "成功"));
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "未知错误！"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //进入挂单页点击取回
    public function get_order_goods()
    {

        $order_sn = I('order_sn');
        //$order_sn = '2017050817073748463U';
        $order_info = M('order')
            ->where(array('order_sn' => $order_sn))
            ->field('order_id,coupon_code,coupon_price,card_code,order_benefit')
            ->find();
        //p($order_info);
        $order_id = $order_info['order_id'];
        //$order_id = 1538;
        //var_dump($order_id);
        $order_goods_info = M('order_goods')->alias('g')
            ->join("LEFT JOIN ypt_goods_group a ON g.group_id = a.group_id")
            ->join("LEFT JOIN ypt_goods_sku u ON u.sku_id = g.spec_key")
            ->where(array('order_id' => $order_id))
            ->field('g.goods_id,a.group_name,g.group_id,g.goods_name,g.goods_price,g.discount,g.bar_code,g.goods_num,g.sku,convert((g.goods_price*g.goods_num*g.discount/100),decimal(10,2)) as xiaoji,g.spec_key as sku_id,u.properties')
            ->select();
        // dump($order_goods_info['goods_price']);
        //p($order_goods_info);
        //p($order_goods_info);
        //$order_goods_info['order_id'] = $order_id;
        //$order_goods_info['properties'][] = array();
        $data = array();
        $data['properties'] = $order_goods_info;
        $data['coupon_code'] = $order_info['coupon_code'];
        $data['coupon_price'] = $order_info['coupon_price'];
        $data['card_id'] = $order_info['card_code'];
        $data['order_benefit'] = $order_info['order_benefit'];
        /*$order_goods_info['properties']['discount'] = '100';
        $order_goods_info['properties']['price'] = '15';
        $order_goods_info['properties']['sku_id'] = '1';
        $order_goods_info['properties']['propertie'] = '';*/
        //p($data);
        if (is_array($data)) {
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "查询失败！"));
        }

    }

    //点击支付
    public function pay_order()
    {
        if (IS_POST) {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '数据', json_encode($_POST));
            $order_info = array();
            $order_sn = I("order_sn"); //流水号
            $order_info["order_sn"] = $order_sn;
            $order_amount = I("order_amount");
            $order_info["discount"] = I('order_discount');
            $order_info["discount_money"] = I('discount_money', $order_amount * I('order_discount') / 100);
            $order_info["order_amount"] = $order_amount; //应收金额
            $order_info["pay_status"] = I("pay_status"); //支付状态为1
            $order_info["type"] = "4"; //3为pos机订单
            $order_info['integral'] = I('dikoufen'); //该订单使用积分
            $order_info['integral_money'] = I('dikoujin'); //该订单使用积分抵扣金额
            $code = I("coupon_code", "");
            $order_info["coupon_code"] = $code; //优惠券ID
            $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
            $order_info["order_goods_num"] = I("order_goods_num");
            $order_info["total_amount"] = I("total_amount"); //订单总价
            $user_id = I('uid') ? I('uid') : $this->userId;
            $order_info["user_id"] = $user_id; //当前使用双屏的用户ID
            $order_info["pay_time"] = I("timestamp");
            $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
            $card_code = I("card_id", "");
            $order_info["card_code"] = $card_code; //会员卡号
            $paystyle_id = I('paystyle_id');
            $order_info["user_money"] = I('user_money', 0);
            $order_info["paystyle"] = $paystyle_id;//现金支付
            $two_type = I('two_type', 1);   //行业类别   1=便利店  2=餐饮
            // $order_info["group_id"]  = I('group_id');
            $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
            if ($role_id == '7') {
                $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
                $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
                $checker_id = $this->userId;
            } else {

                $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
                $checker_id = '0';
            }
            M()->startTrans(); // 开启事务
            $data = M('order');
            $res = $data->add($order_info);
            if ($res) {
                // 加入订单表
                $order_goods = array();
                $goods = M("order_goods");
                $bar_code = explode(",", I("bar_code"));
                $goods_num = explode(",", I("goods_num"));
                $goods_name = explode(",", I("goods_name"));
                $goods_price = explode(",", I("goods_price"));
                $discount = explode(",", I("goods_discount"));
                $group_id = explode(",", I("group_id"));
                $sku = explode(",", I("sku"));
                $goods_id = explode(",", I("goods_id"));
                $goods_weight = explode(",", I("goods_weight"));
                if ($two_type == 2) {
                    foreach ($goods_id as $key => $val) {
                        $order_goods[$key]['order_id'] = $res;
                        $order_goods[$key]["goods_id"] = $val;
                        $order_goods[$key]["goods_name"] = $goods_name[$key];
                        $order_goods[$key]["goods_num"] = $goods_num[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"] = $discount[$key];
                        $order_goods[$key]["group_id"] = $group_id[$key];
                        $order_goods[$key]["sku"] = $sku[$key];//规格编号

                    }
                } else {
                    foreach ($bar_code as $key => $val) {
                        $order_goods[$key]['order_id'] = $res;
                        $order_goods[$key]["bar_code"] = $val;
                        $order_goods[$key]["goods_name"] = $goods_name[$key];
                        $order_goods[$key]["goods_num"] = $goods_num[$key];
                        $order_goods[$key]["goods_weight"] = $goods_weight[$key];
                        $order_goods[$key]["goods_price"] = $goods_price[$key];
                        $order_goods[$key]["discount"] = $discount[$key];
                        $order_goods[$key]["group_id"] = $group_id[$key];
                        $order_goods[$key]["sku"] = $sku[$key];//规格编号
                        $order_goods[$key]['goods_id'] = M('goods')->where(array('mid' => $user_id, 'bar_code' => $val))->getField('goods_id');


                        /*   //扣减库存
                           if (!$val || !$sku[$key]) {
                               M()->rollback();
                               $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));

                           }
                           $stock_flag = $this->decrease_stock($val, $sku[$key], $goods_num[$key]);
                           if ($stock_flag['code'] == 'error') {
                               M()->rollback();
                               $this->ajaxReturn($stock_flag);

                           }*/

                    }
                }

                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'order_goods数据', json_encode($order_goods));

                $result = $goods->addAll($order_goods);
                //M('memcard_user');
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'order_goods数据', $result . '--+--' . $res);
                if ($result && $res) {
                    if ($card_code != '') {
                        $card_info = M('screen_memcard m')
                            ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
                            ->where("card_code='$card_code' or entity_card_code='$card_code'")
                            ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
                            ->find();
                        $ass = floor($order_amount / $card_info['expense']) * $card_info['expense_credits'];
                        //M('screen_memcard_use')->where("card_code='$card_code' or entity_card_code='$card_code'")->setInc('card_amount', $ass);
                        //M('screen_memcard_use')->where("card_code='$card_code' or entity_card_code='$card_code'")->setInc('card_balance', $ass);
                        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
                        $memcard_info = array(
                            "memid" => $card_info['memid'],
                            "status" => 1,
                            "point" => $ass,
                            "card_id" => $card_info['card_id'],
                            "add_time" => time(),
                            "merchants_id" => $this->userId,
                        );
                        M('memcard_user')->data($memcard_info)->add();
                    }
                    if ($paystyle_id == 5) {
                        $pay_info = array(
                            "remark" => $order_sn,
                            "mode" => 4,
                            "merchant_id" => $merchant_id,
                            "checker_id" => $checker_id,
                            "paystyle_id" => 5,
                            "price" => $order_amount,
                            "status" => 1,
                            "cate_id" => 1,
                            "paytime" => time(),
                            'order_id' => $res
                        );
                        $pay = $this->pay_model;
                        $pay_add_res = $pay->add($pay_info);
                        A("Pay/barcode")->decrease_stock($res);

                        //$ab=A("Apiscreen/Twocoupon")->use_card($code);
                        if ($pay_add_res) {
                            M()->commit();
                            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付成功', 1);
                            $this->ajaxReturn(array("code" => "success", "msg" => "支付成功"));
                        } else {
                            M()->rollback();
                            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付失败', 1);
                            $this->ajaxReturn(array("code" => "error", "msg" => "支付失败"));
                        }
                    } else {
                        A("Pay/barcode")->decrease_stock($res);


                        $value = A('Pay')->two_get_card($user_id, $order_sn);
                        M()->commit();
                        get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', 'success-1', json_encode($value));
                        $this->ajaxReturn(array("code" => "success", "data" => $value));
                    }
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付成功', 1111);
                    $this->ajaxReturn(array("code" => "success", "msg" => "支付成功1111"));
                } else {
                    M()->rollback();
                    get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '支付失败', '网络错误1');
                    $this->ajaxReturn(array("code" => "error", "msg" => "支付失败", "data" => "网络错误！"));
                }
            } else {
                M()->rollback();
                get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '失败', '网络错误2');
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "网络错误！"));
            }
        } else {
            get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/Apiscreen/', 'Recwindow_pay_order', '参数错误', '参数错误');
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //扣减库存
    public function decrease_stock($bar_code, $sku_id, $goods_num)
    {
        $is_success = array();
        $goods_info = M('goods')->alias('gds')->join('ypt_goods_sku ysk on ysk.goods_id=gds.goods_id')->where(array('ysk.sku_id' => $sku_id))->field('gds.goods_number,gds.goods_id')->find();
        if (!empty($goods_info)) {
            $stock_num = $goods_info['goods_number'];
            $new_stock_num = $stock_num - $goods_num;
            if ($new_stock_num < 0) {
                $is_success = array('code' => "error", 'msg' => '支付数量超出库存');

            } else {

                $sku_num = M('goods_sku')->where(array('sku_id' => $sku_id))->getField('quantity');
                $new_sku_num = $sku_num - $goods_num;
                if ($new_sku_num < 0) {
                    $is_success = array('code' => "error", 'msg' => '支付数量超出库存');

                } else {
                    $goods_update = array('goods_number' => $new_stock_num);
                    M('goods')->where(array('goods_id' => $goods_info['goods_id']))->save($goods_update); //更新商品总库存
                    $sku_update = array('quantity' => $new_sku_num);
                    M('goods_sku')->where(array('sku_id' => $sku_id))->save($sku_update); //更新商品总库存
                    $is_success = array('code' => "success", 'msg' => '支付成功');

                }

            }

        } else {
            $is_success = array('code' => "error", 'msg' => '支付失败');

        }

        return $is_success;

    }

    //验证会员卡号
    public function check_card_id()
    {
        if (IS_POST) {
            $card_code = I("card_id");
            $price = I("total_amount");
            $uid = $this->userInfo['uid'];
            //$card_code = '442743416296';
            /* $user_id = $this->userId;
            $role_id = M('merchants_role_users')->where(array('uid'=>$user_id))->getField('role_id');
            if($role_id == 7){
            $uid = M('merchants_users')->where(array('id'=>$user_id))->getField('pid');
            }else{
            $uid = $user_id;
            }*/
            if ($card_code) {
                $check = M('screen_memcard_use')->alias('mu')
                    ->join("left join __SCREEN_MEMCARD__ m on m.id=mu.memcard_id")
                    ->where("mu.card_code='$card_code'And m.mid='$uid'")
                    ->find();
                if (!$check) {
                    $this->ajaxReturn(array("code" => "error", "msg" => "非该商家的优惠券"));
                }
                //$res = M('screen_memcard_use')->where(array('card_code' => $card_code))->field('card_id')->find();
                $res = M('screen_memcard_use')->alias('u')
                    ->join('left join ypt_screen_memcard m on m.card_id=u.card_id')
                    ->where(array('u.card_code' => $card_code))
                    ->field('u.card_id,u.card_balance,u.memid,m.max_reduce_bonus,m.credits_set,m.integral_dikou,m.max_reduce_bonus,m.credits_use,m.credits_discount')
                    ->find();
                //p($res);
                if ($res) {
                    if ($res['credits_set'] == 0) {
                        $this->ajaxReturn(array("code" => "success", "msg" => "商家关闭积分设置", "data" => array('card_de_price' => '0', 'jifen_use' => '0')));
                    } else {
                        $data = array();
                        if ($res['card_balance'] < $res['max_reduce_bonus']) {
                            $p = strval(floor($res['card_balance'] / $res['credits_use']) * $res['credits_discount']);
                        } else {
                            $p = strval(floor($res['max_reduce_bonus'] / $res['credits_use']) * $res['credits_discount']);
                        }

                        if ($p == 0 || $price < $res['credits_discount']) {
                            $data['card_de_price'] = '0';
                            $data['jifen_use'] = '0';
                        } else if ($p < $price) {
                            $data['card_de_price'] = $p;
                            $data['jifen_use'] = strval(floor($p / $res['credits_discount']) * $res['credits_use']);
                        } else {
                            $data['jifen_use'] = strval(floor($price / $res['credits_discount']) * $res['credits_use']);
                            $data['card_de_price'] = strval(($data['jifen_use'] / $res['credits_use']) * $res['credits_discount']);
                        }
                        $data['memid'] = $res['memid'];
                        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                    }
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "该会员卡无效！"));
                }
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "失败", "data" => "当前没有输入会员卡"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '参数错误'));
        }
    }

    //交接班
    public function connect_wei()
    {

        $uid = I('uid') ? I('uid') : $this->userId;
        $mac = I('mac');
        $token = I('token');
        //$start_time =  I('timestamp');
        //$start_time =  1495008187;
        //$mac = '44:2c:05:ca:d4:30';
        //$uid = '209';
        //$uid ='991';
        $twotoken = M('twotoken');
        $twotoken->uid = $uid;
        $twotoken->where(array('token' => $token))->save();
        //p(M()->_sql());
        //$start_time = M('twotoken')->where(array('uid' => $uid))->getField('time_start');
        $start_time = M('twotoken')->where(array('token' => $token, 'uid' => $uid))->getField('time_start');
        //p(M()->_sql());
        //p($start_time);
        //$start_time = strtotime('2017-5-7 9:15:00');
        //end_time = strtotime('2017-5-8 19:15:00');
        $end_time = time();
        $res = M('order o')
            ->join("right join __PAY__ p on p.remark=o.order_sn")
            ->field('sum(o.user_money) total_user_money,sum(o.order_amount) order_amount,sum(o.total_amount) total_amount,sum(o.order_goods_num) total_num,sum(o.coupon_price) coupon_price,count(o.order_id) shuliang,count(case when o.coupon_code!=\'\' then id end) coupon_num,o.discount,p.paystyle_id')//coupon_status
            ->where("p.paytime>$start_time AND $end_time>p.paytime AND o.pay_status = 1 AND o.user_id = $uid")
            ->group('p.paystyle_id')
            ->select();
        get_date_dir($this->path, 'connect_wei', 'I', json_encode(I('')));
        get_date_dir($this->path, 'connect_wei', 'SQL', M()->_sql());
        //p($res[0]['total_amount']);
        //p(M()->_sql());
        //p(array_column($res,'total_amount'));
        /*  $info = M('order')
        ->where("pay_time>$start_time AND $end_time>pay_time AND pay_status = 1 AND user_id = $uid")
        ->field('sum(convert((total_amount*(1-discount/100)),decimal(10,2))) res')
        ->select();*/
        //p(M()->_sql());
        //$info  = array_column($info,'res');
        $info = $res[0]['total_amount'] - $res[0]['order_amount'] - $res[0]['coupon_price'];
        //p($info);
        $data = array();
        $data['coupon_num'] = array_sum(array_column($res, 'coupon_num'));
        $data['discount_price'] = $info;
        $data['total_user_money'] = '0';
        foreach ($res as $k => $v) {
            $data['total_user_money'] += $v['total_user_money'];
            $data['sales_amount'] += $v['order_amount'];
            $data['shop_amount'] += $v['total_amount'];
            $data['total_num'] += $v['total_num'];
            $data['xiaopiao_num'] += $v['shuliang'];
            $data['coupon_price'] += round($v['coupon_price'], 2);
            //$data['discount_price'] += ($v['total_amount'] - ($v['total_amount'] * $v['discount'] / 100));
            if ($v['paystyle_id'] == 1) {
                $data['wx_pay'] = $v['order_amount'];
            } elseif ($v['paystyle_id'] == 2) {
                $data['ali_pay'] = $v['order_amount'];
            } elseif ($v['paystyle_id'] == 5) {
                $data['cash_pay'] = $v['order_amount'];
            }
        }
        $data['total_user_money'] = strval($data['total_user_money']);
        //p($data);
        /*$field="p.paytime,ifnull(sum(price),0) as total_price,count(p.id) as total_num,ifnull(sum( if( p.paystyle_id =1, 1, 0)),0) as per_weixin_num,ifnull(sum( if( p.paystyle_id =2, 1, 0)),0) as per_ali_num,
        ifnull(sum( if( p.paystyle_id =1,p.price, 0)),0) as per_wei_price,ifnull(sum( if( p.paystyle_id =2,p.price, 0)),0) as per_ali_price";
        }*/
        //p($data);
        $mac_id = M('screen_pos')->where(array('mac' => $mac))->Field('id')->find();
        $role_id = M('merchants_role_users')->where(array('uid' => $uid))->getField('role_id');
        //p($role_id);
        //$role_id = 3;

        if ($role_id) {
            if (!in_array($role_id, array(2, 3))) {
                $user = M('merchants_users');
                $pid = $user->field('pid')->where(array('id' => $uid))->find();
                //p($pid);
                $pid = $pid['pid'];
                //p($pid);
                $as = $user->field('id,user_name')->where(array('pid' => $pid, 'status' => 0))->select();
                $connect = array();
                foreach ($as as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                //p(M()->_sql());
                $asd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $data['jiebanren'] = $connect;
                array_unshift($asd, 'shouyin');
                $data['dangqian'] = $asd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            } else {
                $user = M('merchants_users');
                $d = $user->field('id,user_name')->where(array('pid' => $uid, 'status' => 0))->select();
                $dd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $connect = array();
                foreach ($d as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                $data['jiebanren'] = $connect;
                array_unshift($dd, 'shangjia');
                $data['dangqian'] = $dd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '网络错误'));
        }

    }

    /**
     * 1.8.7  交接班
     */
    public function connect_staff()
    {
        $uid = I('uid') ? I('uid') : $this->userId;
        $mac = I('mac');  //型号
        $token = I('token');
        $twotoken = M('twotoken');
        $twotoken->uid = $uid;
        $twotoken->where(array('token' => $token))->save();
        $start_time = M('twotoken')->where(array('token' => $token, 'uid' => $uid))->getField('time_start');
        $end_time = time();
        $res = M('order o')
            ->join("right join __PAY__ p on p.remark=o.order_sn")
            ->field('sum(o.user_money) total_user_money,ifnull(sum( if( o.order_benefit>0,1, 0)),0) benefit_num,sum(o.order_benefit) benefit_price,sum(o.order_amount) order_amount,sum(o.total_amount) total_amount,sum(o.order_goods_num) total_num,sum(o.coupon_price) coupon_price,count(o.order_id) shuliang,count(case when o.coupon_code!=\'\' then id end) coupon_num,o.discount,p.paystyle_id,p.use_member,o.user_id')//coupon_status
            ->where("p.paytime>$start_time AND $end_time>p.paytime AND o.pay_status = 1 AND o.user_id = $uid and p.status=1")
            ->group('p.paystyle_id,p.use_member')
            ->select();
        // echo M('order o')->getLastSql();
        // dump($res);
        get_date_dir($this->path, 'connect_staff', 'I', json_encode(I('')));
        get_date_dir($this->path, 'connect_staff', 'SQL', M()->_sql());
        $info = $res[0]['total_amount'] - $res[0]['order_amount'] - $res[0]['coupon_price'];
        $data = array();
        $data['merchant_price'] = $data['order_amount'] = $data['shop_amount']=$data['total_num']= $data['benefit_num']=$data['benefit_price']=0;
        $data['agent_price'] = $data['sales_amount']=$data['cash_pay']= $data['ali_pay']=$data['wx_pay']=0;
        $data['total_user_money'] = '0';
        foreach ($res as $k => $v) {
            $data['total_user_money'] += $v['total_user_money'];
            $data['order_amount'] += $v['order_amount'];  //实收金额
            $data['shop_amount'] += $v['total_amount'];  //订单金额
            $data['total_num'] += $v['total_num'];   //商品数量
            $data['benefit_num'] += $v['benefit_num'];  //优惠笔数
            $data['benefit_price'] += $v['benefit_price'];  //优惠金额
            $data['coupon_price'] += round($v['coupon_price'], 2);
            if ($v['paystyle_id'] == 1) {
                $data['wx_pay'] += $v['order_amount'];
            } elseif ($v['paystyle_id'] == 2) {
                $data['ali_pay'] += $v['order_amount'];
            } elseif ($v['paystyle_id'] == 5) {
                $data['cash_pay'] += $v['order_amount'];
            }elseif ($v['paystyle_id'] == 4) {
                if($v['use_memeber'] == 1){
                    $data['merchant_price'] += $v['order_amount'];//储值支付
                } elseif ($v['use_memeber'] == 2) {
                    $data['agent_price'] += $v['order_amount'];//异业联支付
                }
            }
        }
        $pay_back = M('pay_back')
            ->field('sum(price) prcie,mode')//coupon_status
            ->where("paytime>$start_time AND $end_time>paytime AND status = 5 AND checker_id = $uid")
            ->group('mode')
            ->select();
        $data['cash_back'] = $data['double_back']=0;
        foreach ($pay_back as $key =>$val){
            if ($val['mode']==99){
                $data['cash_back'] = $v['price'];
            }elseif($val['mode']==98){
                $data['double_back'] = $v['price'];
            }
        }
        $data['total_user_money'] = strval($data['total_user_money']);
        $data['sales_amount'] = $data['order_amount']-$data['cash_back']-$data['double_back'];  //总销售额
        $mac_id = M('screen_pos')->where(array('mac' => $mac))->Field('id')->find();
        $role_id = M('merchants_role_users')->where(array('uid' => $uid))->getField('role_id');
        if ($role_id) {
            if (!in_array($role_id, array(2, 3))) {
                $user = M('merchants_users');
                $pid = $user->field('pid')->where(array('id' => $uid))->find();
                //p($pid);
                $pid = $pid['pid'];
                //p($pid);
                $as = $user->field('id,user_name')->where(array('pid' => $pid, 'status' => 0))->select();
                $connect = array();
                foreach ($as as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                //p(M()->_sql());
                $asd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $data['jiebanren'] = $connect;
                array_unshift($asd, 'shouyin');
                $data['dangqian'] = $asd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            } else {
                $user = M('merchants_users');
                $d = $user->field('id,user_name')->where(array('pid' => $uid, 'status' => 0))->select();
                $dd = $user->field('id,user_name')->where(array('id' => $uid))->find();
                $connect = array();
                foreach ($d as $key => $value) {
                    if ($this->checkConnectAuth($value['id'])) {
                        array_push($connect, $value);
                    }
                }
                $data['jiebanren'] = $connect;
                array_unshift($dd, 'shangjia');
                $data['dangqian'] = $dd;
                $data['mac_id'] = $mac_id;
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
                //p($data);
            }
        } else {
            $this->ajaxReturn(array("code" => "error", 'msg' => '网络错误'));
        }

    }

    /**
     * 添加交班记录
     */
    public function add_connect()
    {
        $connect_id = I('connect_id');  //交班人员
        $start_time = I('start_time');  //开始时间
        $end_time = I('end_time');      //结束时间
        $mac_id = I('mac_id');      //机号
        $connect_price = I('connect_price');      //交班金额
        $accept_id = I('accept_id'); //接收人员
        $note = I('note');  //备注
        $info = I('info');  //交班详情
        if (!$accept_id){
            $this->ajaxReturn(array("code" => "error", 'msg' => '请选择交班人员'));
        }
        $role_id = M('merchants_role_users')->where(array('uid' => $this->userId))->getField('role_id');
        if ($role_id==3) {
            $uid = $this->userId;
        }else{
            $uid = M('merchants_users')->where(array('id' => $this->userId))->getField('pid');
        }
        // echo data('Y年m月d日',1530511201);
        $string = json_decode(htmlspecialchars_decode($info),true);
        $data= array(
            'uid'=>$uid,
            'connect_id'=>$connect_id,
            'start_time'=>$start_time,
            'end_time'=>$end_time,
            'mac_id'=>$mac_id,
            'connect_price'=>$connect_price,
            'accept_id'=>$accept_id,
            'note'=>$note,
            'info'=>json_encode($string),
            'add_time'=>time()
        );
        $res = M('screen_connect')->data($data)->add();
        if($res){
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        }else{
            $this->ajaxReturn(array("code" => "error", 'msg' => '网络错误'));
        }

    }

    //交班记录
    public function connect_record()
    {
        $role_id = M('merchants_role_users')->where(array('uid' => $this->userId))->getField('role_id');
        if ($role_id==3) {
            $uid = $this->userId;
        }else{
            $uid = M('merchants_users')->where(array('id' => $this->userId))->getField('pid');
        }
        $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $data = M('screen_connect')
            ->where("add_time>$beginLastmonth AND $endToday>add_time AND  uid = $uid")
            ->field('id,start_time,end_time')
            ->select();
        $res3 = $res4 = array();

        foreach ($data as $k =>$v){
            $month = date('Ym',$v['end_time']);
            $v['start_time'] = date('Y年m月d日 H:s:i',$v['start_time']);
            $v['end_time'] = date('Y年m月d日 H:s:i',$v['end_time']);
            if($month==date('Ym')){
                array_push($res3,$v);
            }elseif($month==date('Ym',$beginLastmonth)){
                array_push($res4,$v);
            }
        }
        $res  = (object)array('month'=>date('Y年m月'),'data'=>$res3);
        $res2 = (object)array('month'=>date('Y年m月',$beginLastmonth),'data'=>$res4);
        $result = array();
        array_push($result,$res,$res2);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $result));
    }

    //交班记录详情
    public function connect_details()
    {
        $id = I('id');
        $data = M('screen_connect')->where(array('id'=>$id))->find();
        $data['connect_staff'] = M('merchants_users')->where(array('id' => $data['connect_id']))->getField('user_name');
        $data['accept_staff'] = M('merchants_users')->where(array('id' => $data['accept_id']))->getField('user_name');
        $data['info'] = json_decode($data['info'],true);
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
    }

    private function checkConnectAuth($uid)
    {
        $auth_id = 14;   // 交接班权限id
        $role_id = M('merchants_role_users')->where(array('uid' => $uid))->getField('role_id');
        if (!$role_id || $role_id == '3') return true;
        $screen_auth = M('merchants_role')->where("id=$role_id")->getField('screen_auth');
        if (!$screen_auth) return false;
        $screen_auth = explode(',', $screen_auth);
        if (in_array($auth_id, $screen_auth)) return true;

        return false;
    }

    //双屏收银广告
    public function screen_idea()
    {
        $res = M('adver')->field('thumb')->where(array('callstyle' => 3))->order('id desc')->limit(3)->select();
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['thumb'] = $host . $v['thumb'];
            }
        } else {
            $res[0]['thumb'] = 'http://sy.youngport.com.cn/data/upload/ad/uploadimages/20170607143741_326134.jpg';
        }
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $res));

    }

    //后台推送
    public function adminpush2()
    {
        /*$openid = 'ssaddaw';
        $red = '0.02';
        $_SESSION[$openid]=$red;
        var_dump($_SESSION[$openid]);*/
        /*ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        vendor('JPush.src.JPush.JPush');
        $br = '<br/>';
        $app_key = '74cf5522a74ab07a4442b92f';
        $master_secret = '376aab71e4322352a2b762da';

        // 初始化
        $client = new \JPush($app_key, $master_secret);

        // 简单推送
        $result = $client->push()
        ->setPlatform('all')
        ->addAllAudience()
        ->setNotificationAlert('Hi！Youngport~~~')
        ->send();

        echo 'Result=' . json_encode($result) . $br;*/

        //查询本月的数据
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-d H:i:s');
        $sql = "select `uid` id  from `ypt_post_token`  WHERE `add_time` >= unix_timestamp('$start') AND `add_time` <= unix_timestamp('$end')";
        $ress = M('post_token')->query($sql);
        //查询近30天的数据
        $ss = 30;
        $start = date('Y-m-d H:i:s', strtotime("-$ss day"));
        $end = date('Y-m-d H:i:s');
        //$sql = "select `uid` id  from `ypt_post_token`  WHERE `add_time` >= unix_timestamp('$start') AND `add_time` <= unix_timestamp('$end')";
        //p($start);
        //p($ress);
        //p(M()->_sql());
        $timess = $this->type_time(1);
        $map['add_time'] = array('between', $timess);
        $ress = M('post_token')->where($map)->field('uid')->select();
        //p(M()->_sql());
        //p(date("m-d-Y", mktime(0, 0, 0, 12, 32, 1997)));
    }

    /**
     * @param $type   支付的类型;
     * @return int    0: 所有1 :今日 2:昨日 3:本周 4:本月 5:上周 6:上月
     * mktime(时，分，秒，月，日，年)
     * a:   "am"或是"pm"
     * A:   "AM"或是"PM"
     * d:   几日，两位数字，若不足则补零；从"01"至"31"
     * D:    星期几，3个英文字母，如:"Fri"
     * F:    月份，英文全名，如:"January"
     * h:    12小时制的小时，从"01"至"12"
     * H:    24小时制的小时，从"00"至"23"
     * g:    12小时制的小时，不补零；从"1"至"12"
     * G:    24小时制的小时，不补零；从"0"至"23"
     * j:    几日，不足不被零；从"1"至"31"
     * l:    星期几，英文全名，如："Friday"
     * m:    月份，两位数字，从"01"至"12"
     * n:    月份，两位数字，不补零；从"1"至"12"
     * M:    月份，3个英文字母；如："Jan"
     * s:   秒；从"00"至"59"
     * S:    字尾加英文序数，两个英文字母,如："21th"
     * t:    指定月份的天数，从"28"至"31"
     * U:    总秒数
     * w:    数字型的星期几，从"0(星期天)"至"6(星期六)"
     * Y:    年，四位数字
     * y:    年，两位数字
     * z： 一年中的第几天；从"1"至"366"
     */
    public function type_time($type)
    {
        switch ($type) {
            case 0:
                return;
            case 1:
                //  今天
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                return array($beginToday, $endToday);
            case 2:
                //昨天
                $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                return array($beginYesterday, $endYesterday);
            case 3:
                //        本周
                $beginThisweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThisweek=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                return array($beginThisweek, $endToday);
            case 4:
                //        本月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

                //                $endThismonth = mktime(23,59,59,date('m'),date('t'),date('Y'));
                return array($beginThismonth, $endToday);
            case 5:
                //上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                return array($beginLastweek, $endLastweek);
            case 6:
                //上月
                $beginLastmonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $endLastmonth = mktime(23, 59, 59, date("m"), 0, date("Y"));
                return array($beginLastmonth, $endLastmonth);
        }
    }

    //支付宝接口调用
    public function ali_pay()
    {
        $order_info = array();
        $order_sn = I("order_sn"); //流水号
        $order_info["order_sn"] = $order_sn;
        $order_amount = I("order_amount");
        $order_info["discount"] = I('order_discount');
        $order_info["order_amount"] = $order_amount; //应收金额
        $order_info["pay_status"] = '0'; //支付状态为0
        $order_info["type"] = "3"; //3为pos机订单
        $order_info['integral'] = I('dikoufen'); //该订单使用积分
        $order_info['integral_money'] = I('dikoujin'); //该订单使用积分抵扣金额
        $code = I("coupon_code", "");
        $order_info["coupon_code"] = $code; //优惠券ID
        $order_info["coupon_price"] = I("coupon_price"); //使用优惠券抵扣多少金额
        $order_info["order_goods_num"] = I("order_goods_num");
        $order_info["total_amount"] = I("total_amount"); //订单总价
        $user_id = I('uid') ? I('uid') : $this->userId;
        $order_info["user_id"] = $user_id; //当前使用双屏的用户ID
        $order_info["pay_time"] = I("timestamp");
        $order_info["order_benefit"] = I("order_benefit"); //整单优惠金额
        $card_code = I("card_id", "");
        $order_info["card_code"] = $card_code; //会员卡号
        $paystyle_id = I('paystyle_id');
        $role_id = M('merchants_role_users')->where(array('uid' => $user_id))->getField('role_id');
        if ($role_id == '7') {
            $pid = M('merchants_users')->where(array('id' => $user_id))->getField('pid');
            $merchant_id = M('merchants')->where(array('uid' => $pid))->getField('id');
            $checker_id = $this->userId;
        } else {

            $merchant_id = M('merchants')->where(array('uid' => $user_id))->getField('id');
            $checker_id = '0';
        }

        /*$order_info["order_sn"] = date('YmdHis').rand(1000,9999).UID;
        $order_info["goods_num"]  = 4;
        $order_info["goods_price"]  = 32;
        $order_info["total_amount"]  = 30;
        $order_info["user_id"]  = 71;*/
        M()->startTrans(); // 开启事务
        $data = M('order');
        $res = $data->add($order_info);
        //$card_code  = '442743416296';
        //$price  = '12';
        /*if($card_code!==''){
        $card_info = M('screen_memcard m')
        ->join('__SCREEN_MEMCARD_USE__ mu on m.card_id=mu.card_id')
        ->where(array('card_code'=>$card_code))
        ->field('m.expense,m.expense_credits,mu.card_amount,mu.card_id,mu.memid')
        ->find();
        $ass = floor($order_amount/$card_info['expense'])*$card_info['expense_credits'];
        M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_amount',$ass);
        M('screen_memcard_use')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
        //M('memcard_user')->where(array('card_code'=>$card_code))->setInc('card_balance',$ass);
        $memcard_info=array(
        "memid"=>$card_info['memid'],
        "status"=>1,
        "point"=>$ass,
        "card_id"=>$card_info['card_id'],
        "add_time" =>time(),
        "merchants_id"=>$this->userId
        );
        M('memcard_user')->data($memcard_info)->add();
        }*/
        if ($res) {
            // 加入订单表
            $order_goods = array();
            $goods = M("order_goods");
            $bar_code = explode(",", I("bar_code"));
            $goods_num = explode(",", I("goods_num"));
            $goods_name = explode(",", I("goods_name"));
            $goods_price = explode(",", I("goods_price"));
            $discount = explode(",", I("goods_discount"));
            $sku = explode(",", I("sku"));
            /* $bar_code = "5588585,5668885,11111111";
            $bar_code = explode(",",$bar_code);
            $discount = "50,90,10";
            $discount = explode(",",$discount);
            $goods_num = "5,3,14";
            $goods_num = explode(",",$goods_num);*/
            foreach ($bar_code as $key => $val) {
                $order_goods[$key]['order_id'] = $res;
                $order_goods[$key]["bar_code"] = $val;
                $order_goods[$key]["goods_name"] = $goods_name[$key];
                $order_goods[$key]["goods_num"] = $goods_num[$key];
                $order_goods[$key]["goods_price"] = $goods_price[$key];
                $order_goods[$key]["discount"] = $discount[$key];
                $order_goods[$key]["sku"] = $sku[$key];
            };
            $result = $goods->addAll($order_goods);
            //M('memcard_user');
            if ($result && $res) {
                M()->commit();
                $ab = $this->zfb_pay($order_sn, $order_amount);
                $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $ab));
            } else {
                M()->rollback();
                $this->ajaxReturn(array("code" => "error", "msg" => "失败"));
            }
        }

    }

    public function zfb_pay($order_sn, $price)
    {
        // 支付宝合作者身份ID，以2088开头的16位纯数字
        $partner = "2017010704905089";
        // 支付宝账号
        $seller_id = 'guoweidong@hz41319.com';
        // 商品网址
        // 异步回调地址
        //$notify_url = 'http://sy.youngport.com.cn/index.php?s=/Pay/Barcode/ali_barcode_pay';
        $notify_url = 'http://a.ypt5566.com/notify.php?s=/Post/Index/zfb_notify_url';
        // 订单标题
        $subject = '1';
        // 订单详情
        $body = '我是测试数据';
        // 订单号，示例代码使用时间值作为唯一的订单ID号
        $content = array();
        $content['timeout_express'] = '30m';
        $content['product_code'] = 'QUICK_MSECURITY_PAY';
        $content['total_amount'] = $price;
        $content['subject'] = $subject;
        $content['body'] = $body;
        $content['out_trade_no'] = $order_sn;
        //$orderinfo['order_amount'];
        $data = array();
        $data['app_id'] = $partner;
        $data['biz_content'] = json_encode($content);
        $data['charset'] = 'utf-8';
        $data['format'] = 'json';
        $data['method'] = 'alipay.trade.app.pay';
        $data['notify_url'] = $notify_url;
        $data['sign_type'] = 'RSA';
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['version'] = '1.0';
        $orderInfo = $this->createLinkstring($data);
        //$orderInfo = 'biz_content={"timeout_express":"30m","product_code":"QUICK_MSECURITY_PAY","total_amount":"0.01","subject":"1","body":"我是测试数据","out_trade_no":"0603181557-1017"}&method=alipay.trade.app.pay&charset=utf-8&version=1.0&app_id=2017010704905089&timestamp=2016-07-29 16:55:53&sign_type=RSA';
        //var_dump($orderInfo);
        $sign = $this->sign($orderInfo);
        //var_dump($sign);
        $data['sign'] = $sign;
        $orderInfo = $this->getSignContentUrlencode($data);

        return $orderInfo;
    }

    public function createLinkstring($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if (trim($value) === "") {
            return true;
        }

        return false;
    }

    public function getSignContentUrlencode($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $params['sign'] = $sign;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = "MIICXAIBAAKBgQC/UIMSw0mWKRp3wP3v0tbKKqtQO80iL3gBkceCE41KRtpE8+ljXzH16jXs5Alj3cPNZlAh+2SApLBv9sVY9nGU8rd6d5294HHH8APDdrHBtdUTpgZnKGNATFhCeiZPuLD76DJslWtSM4a8kW9EkBUDe3mFLtDQwu+ZtbUYf0k8eQIDAQABAoGALzKFo5NaDBmH1hNeklPJeYadTOXz7YMYcAqu1YBNUw23u1sRMNrDUI+/TfnT9zc2nu2mxztlx/bZMEYI2bGyw0Y/3oYl4GHDmeSyqq9o6SjL9S6GRtj+ngG8CX9QuVj7lTqcvHJrBR8E48EiyH4VK9ouySIHE9Ukf71VQVPd0AECQQD5uCTXuCd6aGbs2XqTfX/tfaUio5lFsdvTrccnFdTvDJ/EWwuMXgceJvehsOmNvZK8NabruGkyAk25ABM9bxq5AkEAxCBR2xvTVSBf/ohpB1/y94Imx1pb5OLvrRMvxq3LEcQORDzbSQdY27UZ1i3tC8CLvPF68KE985j75xgY//9PwQJBALx10LhM7t5etG7DotJ0wHtHe70sopwKotCaMda5jz3p1RmnbIu+2rhSyEhq75hdHcSU6Si0wA9R1b5s5BhVJWECQClVdmrrLlree9y0+w2m1xn6wBl7napbeY/MX2FL92RDIY6YFM8LUVrcjBlrjG3RoqGrcvGLyfaw40YM+hfZwwECQFNwNJ6wB5INtt+CeJkJSiaHuhevFmr3w8UD00dOXxTMz2m86cmV+ZNl5srCkeunKdwVCMwNhUHFfLfCno3XyEk=";
        //$priKey="MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAL6EhsF9ufhXqx5ZJwGy5MLP5AcoFsp1I3hWpJgWwLSXKSRM5mkKmp/OOLltJtIF+ViKk1nOgE99J3C9yFjoXV9PWtNhClZmvOk+qAGweC4rzkjumhNC5vTnYf11Hp2+oes5vWMm7DAFFx/owNecNrlQl9cHQCj96pcElWFrhYhNAgMBAAECgYEAln5nWEbxdWwDHwj7mArxS7YegUy4nBrl9vQyNnWaqczSUftw8r7On7et9UN0q+jOK5Pji8hkcOYDFrrDnP+IaRX6KVMYjL4sHltoj+XlEWnUdz5B9MIlKg6ops1aEd4d5PFD+ixw5yvbEsc9nXaKz+8ttm2w+7LWkUTEGres6t0CQQD+paORxMv7APKSlKtzyOw0m6Xr7cydwtJqWexzOI8whfud7ODJV2VEmsJMfsh7HCxpeJET/9Rt5jq9P51ZicbrAkEAv4epQ3xaNUFfkFgYn94V8gGP0K11LrFhB30/MvWGHEuPt+/2ZiF9hXmyeIIktW3QDTcwfd0hfHAzkwgrurcPpwJAUUsbztteq0EAL59apNoN3jWaYJlH601Y0y7l91qlC76aNy56DIzj/WTSho0q/3JdE0a0OghADt2i/uuiFgWQBQJAVFnr6uPWWsP60XhrB+VoZtfXPcFW7YSDRigb8FZ/hPCmUAznyJ0RSfqJ5lby0dCWI2vd+GCuQb6siCG+GJJM2wJATROJfcSEWwNahKNCykUeN8eDd8Iv4Ko1uixynvnMdZZB8YgVQ4C0Y09RBtzi7Dt1StF1aYlAqn9T/ryhFMoP3A=="
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function rsaSign($data)
    {
        $rsaPrivateKey = 'MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';
        $priKey = $rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($res);

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    public function zfb_notify_url()
    {
        $data = $_POST;
        $order_sn = $data['out_trade_no'];
        $pay_code = $data['trade_no'];
        $order_amount = $data['total_amount'];
        $sign = $data['sign'];
        $data['sign_type'] = null;
        $data['sign'] = null;
        $data = $this->getSignContent($data);
        $pubKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        if ($result && $_POST['trade_status'] == 'TRADE_SUCCESS') {
            $Order = M("order"); // 实例化对象
            // 要修改的数据对象属性赋值
            $Order->pay_code = $pay_code;
            $Order->pay_status = '1';
            $Order->add_time = time();
            $Order->order_amount = $order_amount;
            $Order->where(array('order_sn' => $order_sn))->save(); // 根据条件更新记录
            $Pay = $this->pay_model; // 实例化对象
            $Pay->status = '1';
            $Pay->add_time = time();
            $Pay->price = $order_amount;
            $Pay->where(array('remark' => $order_sn))->save();
        }
    }

    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . stripslashes($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . stripslashes($v);
                }
                $i++;
            }
        }

        unset($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 设置双屏行业
     */
    public function set_type()
    {
        if (IS_POST) {
            ($two_type = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));  //行业类别  1=便利店  2=餐饮
            if (in_array($two_type, array('1', '2'))) {
                $user_id = $this->userId;
                if (M('merchants')->where(array('uid' => $user_id))->setField('two_type', $two_type)) {
                    $this->ajaxReturn(array("code" => "success", "msg" => "设置成功", "two_type" => $two_type));
                } else {
                    $this->ajaxReturn(array("code" => "error", "msg" => "设置失败"));
                }
            } else {
                $this->ajaxReturn(array("code" => "error", "msg" => "未定义的类型"));
            }
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }
    }

    /**
     * 商品分组
     * @return [type] [description]
     */
    public function group_list()
    {
        if (IS_POST) {
            ($trade = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));  //行业  1=便利店  2=餐饮
            $mid = $this->userId;
            $data = M('goods_group')->where(array('mid' => $mid, 'trade' => $trade, 'gid' => 0))->order(array('sort' => 'asc', 'add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
            foreach ($data as $k => &$v) {
                $res = M('goods_group')->where(array('mid' => $mid, 'gid' => $v['group_id'], 'trade' => $trade))->order(array('sort' => 'asc', 'add_time' => 'DESC'))->field("group_id,group_name,gid,sort")->select();
                $v['sub'] = $res;
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => $data));
        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }

    }

    public function foreach_goods()
    {
        if (IS_POST) {
            if ($search_like = I('search_like')) {
                $where['goods_name|bar_code'] = array('like', '%' . $search_like . '%');
            }
            if ($group_id = I('group_id')) {
                $where['group_id'] = $group_id;
            }
            $role_id = M('merchants_role_users')->where(array('uid' => $this->userId))->getField('role_id');
            if (!in_array($role_id, array(2, 3))) {
                $uid = M('merchants_users')->where(array('id' => $this->userId))->getField('pid');
            } else {
                $uid = $this->userId;
            }
            // $uid = $this->userId;
            ($trade = I('two_type')) || $this->ajaxReturn(array('code' => 'error', 'msg' => '未获取到行业类别'));
            $page = I('page', 0);
            $per_page = 11;
            $where['mid'] = $uid;
            $where['is_delete'] = 0;
            $where['put_two'] = 2;
            $where['trade'] = $trade;
            $count = M('goods')->where($where)->count();
            $total = ceil($count / $per_page);//总页数
            $lists = M('goods')->where($where)->limit($page * $per_page, $per_page)->field('goods_id,goods_name,shop_price as price,goods_brief,star,sales,original_price,group_id,goods_img1,is_sku')->select();
            // echo M('goods')->getLastSql();
            // dump($lists);
            foreach ($lists as &$v) {
                $picture = $v['goods_img1'];
                if (preg_match("/\x20*https?\:\/\/.*/i", $v['goods_img1'])) {
                    $v['picture'] = $picture;
                } else {
                    $v['picture'] = URL . $picture;
                }
                unset($v['goods_img1']);
                if ($v['is_sku'] == 1) {
                    $v['sku'] = M('goods_sku')->where(array('goods_id' => $v['goods_id']))->field('properties,sku_id,price')->select();
                }
            }
            $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array(
                "total" => $total,
                "count" => $count,
                "data" => $lists,
            )));

        } else {
            $this->ajaxReturn(array("code" => "error", "msg" => "非法请求"));
        }


    }


}
>>>>>>> origin/dev
