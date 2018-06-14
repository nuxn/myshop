<?php
/*
    洋仆淘 http://www.fangbei.org/
    CopyRight 2015 All Rights Reserved
*/

namespace App\Controller;

use Think\Controller;

class WeixinbaseController extends Controller
{

    private $token;
    public $host;
    public $path;

    public function __construct()
    {
        parent::__construct();
        $this->token = "token";
        $this->host = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/';
    }

    public function call_back()
    {
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        } else {
            $this->valid();
        }
    }

    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "token";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            echo $echoStr;
            exit;
        }
    }

    //响应消息
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)) {
            $this->logger("R \r\n" . $postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe")) {
                //过滤关注和取消关注事件
            } else {

            }

            //消息类型分离
            switch ($RX_TYPE) {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    if (strstr($postObj->Content, "第三方")) {
                        $result = $this->relayPart3("http://www.fangbei.org/test.php" . '?' . $_SERVER['QUERY_STRING'], $postStr);
                    } else {
                        $result = $this->receiveText($postObj);
                    }
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: " . $RX_TYPE;
                    break;
            }
            $this->logger("T \r\n" . $result);
            echo $result;
        } else {
            echo "";
            exit;
        }
    }

    //接收事件消息
    private function receiveEvent($object)
    {
        $content = "";
        $this->path = $this->path . 'member/';
        switch ($object->Event) {
            case "card_pass_check"://卡券审核事件
                get_date_dir($this->path,'Check','卡券审核信息',json_encode($object));
                $card_id = $object->CardId;
                if (!$card_id) {
                    get_date_dir($this->path,'Check','卡券审核失败',$card_id);
                }
                break;
            case "user_get_card"://卡券领取事件推送
                $card_id = $object->CardId;
                $data = array();
                $data['card_id'] = "$card_id";
                $data['toname'] = "$object->ToUserName";
                $data['fromname'] = "$object->FromUserName";
                $data['create_time'] = "$object->CreateTime";
                $data['friendname'] = "$object->FriendUserName";
                $data['usercard'] = "$object->UserCardCode";
                $data['outerid'] = "$object->OuterId";
                $data['status'] = 1;
                $userinfo = A("App/Member")->get_wx_user_info("$object->FromUserName");
                $data['unionid'] = $userinfo['unionid'];
                $is_screen_coupons = M("screen_coupons")->where("card_id='$card_id'")->find();
                $data['coupon_id'] = $is_screen_coupons['id'];

                if (!$is_screen_coupons || $is_screen_coupons == '') {
                    get_date_dir($this->path,'receive_card','---------------用户领取会员卡推送'.PHP_EOL,json_encode($object));
                    A("App/Member")->activate_memcard($object);
                    exit;
                }

                if (M("screen_user_coupons")->data($data)->add()) {
                    $this->pathc = $_SERVER['DOCUMENT_ROOT'] . '/data/log/coupon/';
                    get_date_dir($this->pathc,'receive_coupon','---------------用户领取券推送'.PHP_EOL,json_encode($object));
                    M("screen_coupons")->where("card_id='$card_id'")->setDec('quantity');
                    get_date_dir($this->pathc,'receive_coupon','更改库存-',M()->getLastSql());
                    M("screen_coupons")->where("card_id='$card_id'")->setInc('use_quantity');
                    get_date_dir($this->pathc,'receive_coupon','更改库存+',M()->getLastSql());
                };
                break;
            case 'user_gifting_card'://转让优惠券
                get_date_dir($this->path,'give_way','用户转赠事件',json_encode($object));
                $usercard = "$object->UserCardCode";
                $card_id = $object->CardId;
                $is_screen_coupons = M("screen_coupons")->where("card_id='$card_id'")->find();
                $is_user_coupons = M("screen_user_coupons")->where(array('usercard' => $usercard))->find();
                if ($is_screen_coupons && $is_user_coupons) {
                    M("screen_user_coupons")->where(array('usercard' => $usercard))->save(array('status' => 0));
                    M("screen_coupons")->where("card_id='$card_id'")->setInc('quantity');
                    M("screen_coupons")->where("card_id='$card_id'")->setDec('use_quantity');
                }

                break;
            case "user_del_card"://删除事件推送

                break;

            case "submit_membercard_user_info"://用户提交资料推送
                get_date_dir($this->path,'activate_member','---------------会员卡用户提交资料推送'.PHP_EOL,json_encode($object));
                A("App/Member")->activate_member($object);
                break;
            case "user_consume_card"://核销事件推送

                break;
            case "user_pay_from_pay_cell"://买单事件推送

                break;
            case "user_view_card"://进入会员卡事件推送

                break;
            case "user_enter_session_from_card"://从卡券进入公众号会话事件推送

                break;
            case "card_sku_remind"://库存报警事件

                break;
            case "subscribe":
                $content = "来了啊,坐!";
                file_put_contents('./data/log/weixin/subscribe.log', date("Y-m-d H:i:s") . '关注用户openid: ' . $object->FromUserName . PHP_EOL, FILE_APPEND | LOCK_EX);
                $userinfo = A("App/Member")->get_wx_user_info("$object->FromUserName");
                if ($userinfo['subscribe'] == '1' && $userinfo['headimgurl'] && $userinfo['nickname']) {
                    $where['openid'] = "$object->FromUserName";
                    $where['memimg'] = array('eq', '');
                    M("screen_mem")->where($where)->save(array('memimg' => $userinfo['headimgurl'], 'nickname' => $userinfo['nickname']));
                }
//                $content .= (!empty($object->EventKey))?("\n来自二维码场景 ".str_replace("qrscene_","",$object->EventKey)):"";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "CLICK":
                switch ($object->EventKey) {
                    case "COMPANY":
                        $content = array();
                        $content[] = array("Title" => "洋仆淘", "Description" => "", "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
                        break;
                    case "CREDIT_CARD":
                        $content=A("App/Testadmin")->get_material();
                        break;
                    case "V1001_ACTIVITY1":
                        $content = array("MediaId" => 'DiMoxMKg__SU7McX2E4XvfAvat83hdtLSE3o604GXV4');
                        break;
                    case "V1001_ACTIVITY2":
                        $content = array("MediaId" => "DiMoxMKg__SU7McX2E4Xva8dF4FIMFXF_RYnyxLumvI");
                        break;
                    case "V1001_PHONE":
                        $content = "张总:13926577877(华北、东北)\n梁总:18511876966(华东、华北)\n熊总:13066213777(西南)\n郭总:18665322655(华南)";
                        $content = '客服: 400-888-3658';
                        break;
                    default:
                        $content = "点击菜单：" . $object->EventKey;
                        break;
                }
                break;
            case "VIEW":
                $content = "跳转链接 " . $object->EventKey;
                break;
            case "SCAN":
                $content = "扫描场景 " . $object->EventKey;
                break;
            case "LOCATION":
                $content = "上传位置：纬度 " . $object->Latitude . ";经度 " . $object->Longitude;
                break;
            case "scancode_waitmsg":
                if ($object->ScanCodeInfo->ScanType == "qrcode") {
                    $content = "扫码带提示：类型 二维码 结果：" . $object->ScanCodeInfo->ScanResult;
                } else if ($object->ScanCodeInfo->ScanType == "barcode") {
                    $codeinfo = explode(",", strval($object->ScanCodeInfo->ScanResult));
                    $codeValue = $codeinfo[1];
                    $content = "扫码带提示：类型 条形码 结果：" . $codeValue;
                } else {
                    $content = "扫码带提示：类型 " . $object->ScanCodeInfo->ScanType . " 结果：" . $object->ScanCodeInfo->ScanResult;
                }
                break;
            case "scancode_push":
                $content = "扫码推事件";
                break;
            case "pic_sysphoto":
                $content = "系统拍照";
                break;
            case "pic_weixin":
                $content = "相册发图：数量 " . $object->SendPicsInfo->Count;
                break;
            case "pic_photo_or_album":
                $content = "拍照或者相册：数量 " . $object->SendPicsInfo->Count;
                break;
            case "location_select":
                $content = "发送位置：标签 " . $object->SendLocationInfo->Label;
                break;
            case "poi_check_notify":
                $poi_id = $object->PoiId;
                $result = $object->result;
                $path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin_responseMsg/';
                if($result == 'fail'){
                    M('merchants_wxstore')->where("poi_id=$poi_id")->save(array('status'=>2,'errmsg'=>$object->msg));
                    get_date_dir($path,$object->Event,$object->Event,json_encode($object));
                } else {
                    get_date_dir($path,$object->Event,$object->Event,json_encode($object));
                }
                break;
            default:
                $path = $_SERVER['DOCUMENT_ROOT'] . '/data/log/weixin_responseMsg/';
                get_date_dir($path,'response','未处理事件:'.$object->Event,json_encode($object));
                break;
        }

        if (is_array($content)) {
            if (isset($content[0]['PicUrl'])) {
                $result = $this->transmitNews($object, $content);
            } else if (isset($content['MusicUrl'])) {
                $result = $this->transmitMusic($object, $content);
            } else if (isset($content['MediaId'])) {
                $result = $this->transmitImage($object, $content);

            }
        } else {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }


    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        //多客服人工回复模式
        if (strstr($keyword, "请问在吗") || strstr($keyword, "在线客服")) {
            $result = $this->transmitService($object);
            return $result;
        }

        //自动回复模式
        if (strstr($keyword, "文本")) {
            $content = "这是个文本消息";
        } else if (strstr($keyword, "表情")) {
            $content = "中国：" . $this->bytes_to_emoji(0x1F1E8) . $this->bytes_to_emoji(0x1F1F3) . "\n仙人掌：" . $this->bytes_to_emoji(0x1F335);
        } else if (strstr($keyword, "单图文")) {
            $content = array();
            $content[] = array("Title" => "单图文标题", "Description" => "单图文内容", "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
        } else if (strstr($keyword, "图文") || strstr($keyword, "多图文")) {
            $content = array();
            $content[] = array("Title" => "多图文1标题", "Description" => "", "PicUrl" => "http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title" => "多图文2标题", "Description" => "", "PicUrl" => "http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
            $content[] = array("Title" => "多图文3标题", "Description" => "", "PicUrl" => "http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" => "http://m.cnblogs.com/?u=txw1958");
        } else if (strstr($keyword, "音乐")) {
            $content = array();
            $content = array("Title" => "最炫民族风", "Description" => "歌手：凤凰传奇", "MusicUrl" => "http://121.199.4.61/music/zxmzf.mp3", "HQMusicUrl" => "http://121.199.4.61/music/zxmzf.mp3");
        } else {
            $content = date("Y-m-d H:i:s", time()) . "\nOpenID：" . $object->FromUserName . "\n技术支持 洋仆淘";
        }

        if (is_array($content)) {
            if (isset($content[0])) {
                $result = $this->transmitNews($object, $content);
            } else if (isset($content['MusicUrl'])) {
                $result = $this->transmitMusic($object, $content);
            }
        } else {
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    //接收图片消息
    private function receiveImage($object)
    {
        $content = array("MediaId" => $object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object)
    {
        $content = "你发送的是位置，经度为：" . $object->Location_Y . "；纬度为：" . $object->Location_X . "；缩放级别为：" . $object->Scale . "；位置为：" . $object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition)) {
            $content = "你刚才说的是：" . $object->Recognition;
            $result = $this->transmitText($object, $content);
        } else {
            $content = array("MediaId" => $object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    //接收视频消息
    private function receiveVideo($object)
    {
        $content = array("MediaId" => $object->MediaId, "ThumbMediaId" => $object->ThumbMediaId, "Title" => "", "Description" => "");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：" . $object->Title . "；内容为：" . $object->Description . "；链接地址为：" . $object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)) {
            return "";
        }

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if (!is_array($newsArray)) {
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
        $item_str = "";
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>
$item_str    </Articles>
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray)
    {
        if (!is_array($musicArray)) {
            return "";
        }
        $itemTpl = "<Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[music]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
        <MediaId><![CDATA[%s]]></MediaId>
    </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[image]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "<Voice>
        <MediaId><![CDATA[%s]]></MediaId>
    </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[voice]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
    </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[video]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复多客服消息
    private function transmitService($object)
    {
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复第三方接口消息
    private function relayPart3($url, $rawData)
    {
        $headers = array("Content-Type: text/xml; charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //字节转Emoji表情
    function bytes_to_emoji($cp)
    {
        if ($cp > 0x10000) {       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)) . chr(0x80 | (($cp & 0x3F000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x800) {   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x80) {    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else {                    # 1 byte
            return chr($cp);
        }
    }

    //日志记录
    private function logger($log_content)
    {
        if (isset($_SERVER['HTTP_APPNAME'])) {   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        } else if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1") { //LOCAL
            $max_size = 1000000;
            $log_filename = "./data/log/weixin/log.xml";
            if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
                unlink($log_filename);
            }
            file_put_contents($log_filename, date('Y-m-d H:i:s') . " " . $log_content . "\r\n", FILE_APPEND);
        }
    }

    public function aa()
    {
        A("Api/Member")->activate_memcard(2);
    }

}

?>