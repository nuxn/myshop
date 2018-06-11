<?php
/*
    洋仆淘 http://www.fangbei.org/
    CopyRight 2015 All Rights Reserved
*/

namespace App\Controller;

use Think\Controller;

class TestadminController extends Controller
{

    /**
     *微信公众号菜单
     */
    public function menu()
    {
        $data = array(
            'type' => 'image',
            'offset' => 0,
            'count' => 10
        );
        $token = get_weixin_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $token;
//        $data=urldecode(json_encode($data));
//        $res = request_post($url, $data);
//        echo $res;exit;
        $menu = array(
            'button' => array(
                array(
                    'name' => urlencode("用卡管家"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode("下载聚财管家APP"),
                            //'url' => 'https://fir.im/juca'
                            'url' => 'https://sy.youngport.com.cn/app/Testadmin/downApp'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode("进入用卡管家"),
                            'url' => 'https://qr.youngport.com.cn/test/dist/#/login'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode("用卡管家手册"),
                            'url' => 'http://mp.weixin.qq.com/s/6XZXF36-h0C7nglwhr5D5g'
                        ),
//                        array(
//                            'type' => 'click',
//                            'name' => urlencode("用卡管家手册"),
//                            'key' => 'CREDIT_CARD'
//                        ),
                    )
                ),
                array(
                    'type' => 'view',
                    'name' => urlencode("下载APP"),
                    'url' => 'https://sy.youngport.com.cn/index.php?s=app/downloadApk/index'
//                    'name' => urlencode("商户服务"),
//                    'sub_button' => array(
//                        array(
//                            'type' => 'view',
//                            'name' => urlencode("客服电话"),
//                            'url' => 'http://sy.youngport.com.cn/index.php?s=Api/App/kefu'
//                        ),
//                        array(
//                            'type' => 'miniprogram',
//                            'name' => urlencode("小程序附近购"),
//                            'url' => 'http://mp.weixin.qq.com',
//                            "appid" => "wx7aa4b28fb4fae496",
//                            "pagepath" => "pages/map/lists"
//                        )
//                    )
                ),
                array(
                    'name' => urlencode("合作咨询"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode("行业解决方案"),
                            'url' => 'http://www.hz41319.com/'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode("招募合伙人"),
                            'url' => 'https://jinshuju.net/f/OXPoiG'
                        ),
                        array(
                            'type' => 'click',
                            'name' => urlencode("商务合作"),
                            'key' => 'V1001_PHONE'
                        )
                    )
                )
            ),
            "matchrule" => array(
                "country" => urlencode("中国"),
                "province" => urlencode("广东"),
                "city" => urlencode("深圳"),
            )
        );
        $data = urldecode(json_encode($menu));
        echo $data;

        $url_create = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $token;
        $url_delete = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $token;
        $res = request_post($url_delete, $data);
        print_r($res);
        $res = request_post($url_create, $data);
        print_r($res);
    }

    /**
     * 获取公众号素材列表
     * @return array
     */
    public function get_material()
    {
        $data = array(
            'type' => 'news',
            'offset' => 0,
            'count' => 2
        );

        $content = array(
            "Title" => "洋仆淘",
            "Description" => "",
            "PicUrl" => "",
            "Url" => ""
        );
        $token = get_weixin_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $token;
        $res = request_post($url, json_encode($data));
        $res = json_decode($res, true);
        if ($url_material = $res['item'][0]['content']['news_item'][0]['url']) {
            $content['Title'] = $res['item'][0]['content']['news_item'][0]['title'];
            $content['Description'] = $res['item'][0]['content']['news_item'][0]['digest'];
            $content['PicUrl'] = $res['item'][0]['content']['news_item'][0]['thumb_url'];
            $content['Url'] = $url_material;
        }
        echo '<pre/>';
        print_r($content);
        return array($content);
    }

    /**
     * 聚财OEM下载app
     */
    public function downApp()
    {
        $clientId = self::get_client_id();
        if ($clientId == 2) {
            $url = 'http://fir.im/jucaiios';
        } else {
            $url = 'https://fir.im/juca';
        }
        $url='https://fir.im/jucaiguanjia';
        header('Location:' . $url);
    }

    /**
     * 判断访问客户端
     * @return int
     */
    static public function get_client_id()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            echo 'systerm is IOS';
            return 2;
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            echo 'systerm is Android';
            return 1;
        } else {
            echo 'systerm is other';
            return 0;
        }
    }
}