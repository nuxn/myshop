<?php
/**
 * 我的
 * Created by PhpStorm.
 * User: Joan
 * Date: 2017/3/9
 * Time: 17:26
 */

namespace Api\Controller;

use Common\Controller\ApibaseController;

/**
 * Class AppController
 * @package Api\Controller
 */
class AppController extends ApibaseController

{

    /**
     * 常见问题
     */
    public function question_a()
    {
        $this->display("question2");
    }

    public function question_m()
    {
        $this->display("question");
    }

    public function level()
    {
        $this->display('level');
    }
    /**
     *app更新
     */
    public function app_update()
    {
        $client = I('post.client');
        $appModel = M('app_version');
        $client = isset($client) && $client == 'ios' ? 2 : 1;
        $info = $appModel->where(array("client" => $client))->order('id desc')->find();
        $info['change_log'] = $client == 1 ? strip_tags(htmlspecialchars_decode($info['change_log'])) : htmlspecialchars_decode($info['change_log']);

        $version = array(
            "versionCode" => $info['version_code'],
            "change_log" => $info['change_log'],
            "versionName" => $info['version_name'],
            "app_name" => $info['app_name'],
            "apk_url" => $info['apk_url']
        );
        $this->ajaxReturn(array("code" => "success", "msg" => "成功", "data" => array($version)));
    }

    /**
     * 语音设置
     */
    public function voice_set()
    {
        $this->checkLogin();
        $voice_open = I('post.voice_open');//0关闭,1开启
        $data = array("voice_open" => $voice_open);

        if (!$voice_open && $voice_open != '0') $this->ajaxReturn(array("code" => "error", "msg" => "缺少参数voice_open"));
        if ($voice_open == '1') {
            M("merchants_users")->where(array("id" => $this->userId, "voice_open" => "0"))->save($data);
        } else if ($voice_open == '0') {
            M("merchants_users")->where(array("id" => $this->userId, "voice_open" => "1"))->save($data);
        }
        $user_info = M("token")->where(array("token" => $this->token))->getField("value");
        $user_info = json_decode($user_info, true);
        $user_info['voice_open'] = $voice_open;
        M("token")->where(array("token" => $this->token))->save(array("value" => json_encode($user_info)));
        $this->ajaxReturn(array("code" => "success", "msg" => "设置成功"));
    }

    public function kefu(){
        $this->display("kefu");
    }
}

