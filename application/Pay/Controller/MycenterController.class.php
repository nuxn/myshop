<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/5/3
 * Time: 19:46
 */

namespace Pay\Controller;

use Common\Controller\HomebaseController;

class MycenterController extends HomebaseController
{
    //个人中心
    public function mycenter()
    {
       /* $openid = I('openid');
        $mem_name = '游客';
        $mem_info = M('screen_mem')->where(array('openid' => $openid))->field('memimg,nickname,realname')->find();
        if (!$mem_info['nickname']) {
            if (!$mem_info['realname']) {
                $this->assign('mem_name', $mem_name);
            } else {
                $this->assign('mem_name', $mem_info['realname']);
            }
        } else {
            $this->assign('mem_name', $mem_info['nickname']);
        }
        //var_dump($mem_info);
        $this->assign('mem_img', $mem_info['memimg']);
        $this->assign('openid', $openid);
        $this->display();*/
        $userid = I('mid');
        $memModel = M('screen_mem');

        //检测是否登陆过
        if (!$_SESSION["openid"]) {
            $res = $this->get_access_token();
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $res['access_token'] . '&openid=' . $res['openid'];
            $userInfo = file_get_contents($url);
            $userInfo = json_decode($userInfo, true);

            if ($res['openid']) {

                $mem_info = $memModel->where(array('openid' => $res['openid']))->field('memimg,nickname,realname')->find();

                $_SESSION['openid'] = $res['openid'];
                $_SESSION['unionid'] = $res['unionid'];
                $_SESSION['headimgurl'] = $mem_info['memimg'] ? $mem_info['memimg'] : $userInfo['headimgurl'];
                $_SESSION['nickname'] = $mem_info['nickname'] ? $mem_info['nickname'] : $userInfo['nickname'];
                if (!$mem_info) {
                    $usr_arr = array(
                        "openid" => $res['openid'],
                        "add_time" => time(),
                        "userid" => $userid ? $userid : "1",
                        "memimg" => $userInfo['headimgurl'] ? $userInfo['headimgurl'] : '',
                        "nickname" => $userInfo['nickname'] ? $userInfo['nickname'] : '游客' . date("mdHis"),
                    );
                    file_put_contents('./huiyuanka.log', date("Y-m-d H:i:s") . '授权登录插入会员表:' . json_encode($usr_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $memModel->add($usr_arr);
                }
            }


        }

        $this->assign('mem_img', $_SESSION['headimgurl']);
        $this->assign('openid', $_SESSION['openid']);
        $this->assign('mem_name', $_SESSION['nickname']);
        $this->display();
    }

    public function get_access_token()
    {
        $code = $_GET["code"];
        $appid = C("WEIXINPAY_CONFIG.APPID");
        $secret = C("WEIXINPAY_CONFIG.APPSECRET");
        if (isset($code)) {
            $access_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
            $res = file_get_contents($access_token_url);
            $res = json_decode($res, true);
            return $res;
        } else {
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SESSION['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_uri = urlencode($redirect_uri);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope=snsapi_userinfo&state=&connect_redirect=1#wechat_redirect';
            header("Location:$url");
            return '';
        }
    }

    public function test()
    {
        $userid = I('mid');
        $memModel = M('screen_mem');

        //检测是否登陆过
        if (!$_SESSION["openid"]) {
            $res = $this->get_access_token();
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $res['access_token'] . '&openid=' . $res['openid'];
            $userInfo = file_get_contents($url);
            $userInfo = json_decode($userInfo, true);

            if ($res['openid']) {

                $mem_info = $memModel->where(array('openid' => $res['openid']))->field('memimg,nickname,realname')->find();

                $_SESSION['openid'] = $res['openid'];
                $_SESSION['unionid'] = $res['unionid'];
                $_SESSION['headimgurl'] = $mem_info['memimg'] ? $mem_info['memimg'] : $userInfo['headimgurl'];
                $_SESSION['nickname'] = $mem_info['nickname'] ? $mem_info['nickname'] : $userInfo['nickname'];
                if (!$mem_info) {
                    $usr_arr = array(
                        "openid" => $res['openid'],
                        "add_time" => time(),
                        "userid" => $userid ? $userid : "1",
                        "memimg" => $userInfo['headimgurl'] ? $userInfo['headimgurl'] : '',
                        "nickname" => $userInfo['nickname'] ? $userInfo['nickname'] : '游客' . date("mdHis"),
                    );
                    file_put_contents('./huiyuanka.log', date("Y-m-d H:i:s") . '授权登录插入会员表:' . json_encode($usr_arr) . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $memModel->add($usr_arr);
                }
            }


        }

        //print_r($_SESSION);
        $this->assign('mem_img', $_SESSION['headimgurl']);
        $this->assign('openid', $_SESSION['openid']);
        //$this->display();
    }

    //我的钱包
    public function myred()
    {

        $openid = $_SESSION['openid'];
        //$openid = 'oyaFdwF8nUBi9343_hRQMfJD4nAU';
        //p($openid);
        $res = M('red_packer')
            ->where(array('openid' => $openid))
            ->field('price,type,add_time')
            ->order('add_time desc')
            ->limit(0,100)
            ->select();
        //p(M()->_sql());
        //p($res);
        $this->assign('openid', $openid);
        $this->assign('res', $res);
        $this->display();
    }

    //消费记录
    public function myrecord()
    {
        $openid = $_SESSION['openid'];

       /* $User = M('User'); // 实例化User对象
        $count      = $User->where('status=1')->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $User->where('status=1')->order('create_time')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板*/

        $count = M('pay p')
            ->join('__MERCHANTS__  b on p.merchant_id = b.id')
            ->field('b.merchant_name,p.price,p.paytime')
            ->where(array('p.customer_id' => $openid))
            ->order('p.paytime desc')
            ->count();
        /*p(M()->_sql());
        p($count);*/
        $Page       = new \Think\Page($count,1000);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $list = M('pay p')
            ->join('__MERCHANTS__  b on p.merchant_id = b.id')
            ->field('b.merchant_name,p.price,p.paytime')
            ->where(array('p.customer_id' => $openid))
            ->order('p.paytime desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        //p($data);
        //$items_list = $items_mod->where($where)->page(I('page'),10)->order($order_str)->select();
        $show       = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
}