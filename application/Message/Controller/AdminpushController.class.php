<?php
/**
 * Created by PhpStorm.
 * By: JC
 * Date: 2017/5/5
 * Time: 11:16
 */

namespace Message\Controller;

use Think\Controller;


class AdminpushController extends Controller
{

    public $app_key = 0;//推送key
    public $master_secret = 0;//推送密匙
    public $title = 0;//推送标题
    private $pay_model;

    function _initialize()
    {
        $this->pay_model = M('pay');
        $this->title = '点击获取更多!';
        //洋仆淘
        $this->app_key = '74cf5522a74ab07a4442b92f';
        $this->master_secret = '376aab71e4322352a2b762da';
        //钱嘟嘟
        //$this->app_key = '69e041d9be7650d1aaf283db';
        //$this->master_secret = '4f335826b02ec9504328180a';
        //云来支付
        $this->yl_app_key = '7060eb57341cf8d2eaae3bb1';
        $this->yl_master_secret = '41519815a6ac60659685fb31 ';
    }

    /**
     * app消息推送1.2版本
     * @param string $info1 app消息推送
     * @param string $wid 推送字段1
     * @param string $msg 推送字段2
     * @param string $alias 别名或RegistrationId
     * @return array|object  推送结果
     */
    public function adminpush($info1 = '', $wid = '', $msg = '', $alias = '')
    {
        ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        vendor('JPush.src.JPush.JPush');
        $path = get_date_dir($_SERVER['DOCUMENT_ROOT'] . '/data/log/message/') . date("Y_m_d_");

        //实例化配置
        $client = new \JPush($this->app_key, $this->master_secret);

        if (is_array($info1)) {//推送内容为数组

            $content = mb_substr(strip_tags(htmlspecialchars_decode($info1['content'])), 0, 20, 'utf-8') . '...';
            //$content = preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", , $chinese);
            if ($info1['title']) $this->title = strip_tags($info1['title']);
            $result = $client->push()
                ->setPlatform('all')
                ->addAllAudience()
                ->addAndroidNotification($content, $this->title, 1, array("id" => $wid, "msg" => $msg))
                ->addIosNotification($content, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("id" => $wid, "msg" => $msg))
                ->send();

        } else {//推送内容为字符串

            $result = $client->push()
                ->setPlatform('all')//使用平台
                //->addAllAudience()
                ->addAlias($alias)//别名
                ->addAndroidNotification($info1, $this->title, 1, array("id" => $wid, "msg" => $msg))
                ->addIosNotification($info1, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("id" => $wid, "msg" => $msg))
                ->send();
        }

        file_put_contents($path . 'pay_message.log', date("Y-m-d H:i:s") . '1.2---' . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
//        echo '<pre/>';
//        var_dump($result);
        return $result;
    }

    /**
     * app消息推送 1.3及以上版本
     * @param string $info1 app消息推送
     * @param string $wid 推送字段1
     * @param string $msg 推送字段2
     * @param string $alias 别名或RegistrationId
     * @return array|object  推送结果
     */
    public function api_push_msg($info1 = '', $wid = '', $msg = '', $alias = '')
    {
        ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        vendor('JPush.src.JPush.JPush');

        //实例化配置
        $client = new \JPush($this->app_key, $this->master_secret);
        $remark = $this->pay_model->where(array('id' => $wid))->getField('remark');
        if (is_array($info1)) {//推送内容为数组

            $content = mb_substr(strip_tags(htmlspecialchars_decode($info1['content'])), 0, 20, 'utf-8') . '...';
            //$content = preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", , $chinese);
            if ($info1['title']) $this->title = strip_tags($info1['title']);
            $result = $client->push()
                ->setPlatform('all')
                ->addAllAudience()
                ->addAndroidNotification($content, $this->title, 1, array("id" => $wid, "msg" => $msg, "remark" => $remark, "type" => 1))
                ->addIosNotification($content, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("id" => $wid, "msg" => $msg, "remark" => $remark, "type" => 1))
                ->setOptions(null, null, null, true, null)
                ->send();

        } else {//推送内容为字符串

            $result = $client->push()
                ->setPlatform('all')//设置平台
                //->addAllAudience()//设置受众
                //->addAlias($alias)//别名
                ->addRegistrationId($alias)//RegistrationId
                ->addAndroidNotification($info1, $this->title, 1, array("id" => $wid, "msg" => $msg, "remark" => $remark, "type" => 1))//设置通知
                ->addIosNotification($info1, 'iOS sound', \JPush::DISABLE_BADGE, true, 'iOS category', array("id" => $wid, "msg" => $msg, "remark" => $remark, "type" => 1))//设置通知
                ->setOptions(null, null, null, true, null)
                ->send();//send发送
        }
        //file_put_contents($path . 'pay_message.log', date("Y-m-d H:i:s") . '1.3---' . $result . PHP_EOL, FILE_APPEND | LOCK_EX);
//        echo '<pre/>';
//        print_r(json_decode(json_encode($result),true));
        return $result;
    }

    /**
     * 简单推送案例
     */
    public function simple_test()
    {
        // 初始化
        $client = new \JPush($this->app_key, $this->master_secret);
        // 简单推送
        $content = '';
        $extra = array('url' => 'http://ym/c/?', 'id' => '1');
        $result = $client->push()
            ->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert($this->title . $content)
            ->setMessage("$content", '', 'text', $extra)
            ->send();
        var_dump($result);
    }


    /**
     * 后台消息模块推送
     * @param $wid
     */
    public function getinfo($wid)
    {
        $info1 = M('message')
            ->where(array('id' => $wid))
            ->Field('title,content,update_time')
            ->find();
        if ($info1) {
            $this->adminpush($info1, $wid, $msg = '');
            $this->success('推送到APP成功', U('AdminSystem/index'));
        } else {
            $this->error('网络原因，推送失败，请重新操作！', U('AdminSystem/index'));
        }
    }

}