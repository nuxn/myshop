<?php
/**
 * Created by PhpStorm.
 * User: lxl
 * Date: 2017/6/29
 */

namespace App\Controller;

use Think\Controller;

/**
 * 下载APP
 * Class DownloadApkController
 * @package App\Controller
 */
class DownloadApkController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        define("TMPL_PATH", C("SP_ADMIN_TMPL_PATH") . C("SP_ADMIN_DEFAULT_THEME") . "/");
    }

    public function index()
    {
        $app = (int)I('app',1);

        // 从数据库中获取 ANDROID 下载链接
        $sql = "SELECT apk_url FROM `ypt_app_version` WHERE app_company={$app} AND `client`=1 ORDER BY id DESC LIMIT 1";
        $list = D('app_version')->query($sql);

        // 判断需要下载的APP
        if ($app == 1) {    // 下载洋仆淘
            $ios_url = "itms-apps://itunes.apple.com/us/app/yang-pu-tao/id1221812573?mt=8";
            $img = "yptapp";
            $_img = "_yptapp";
        } else if ($app == 2) { // 下载钱嘟嘟
            $ios_url = "itms-apps://itunes.apple.com/us/app/yang-pu-tao/id1221812573?mt=8";
            $img = "qddapp";
            $_img = "_qddapp";
        }
        $this->assign("android_url", $list[0]['apk_url']);
        $this->assign("ios_url", $ios_url);
        $this->assign("img", $img);
        $this->assign("_img", $_img);
        $this->display();
    }
}