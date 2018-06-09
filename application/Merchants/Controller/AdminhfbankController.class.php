<?php

namespace Merchants\Controller;

use Common\Controller\AdminbaseController;

/**
 * 恒丰银行后台
 * Class AdminhfbankController
 * @package Merchants\Controller
 */
class AdminhfbankController extends AdminbaseController
{
    protected $shopcates;
    protected $merchants;
    protected $merchants_hfpay;
    protected $merchants_users;
    public $httpUrl, $path, $public_key;

    function _initialize()
    {
        parent::_initialize();
        $this->shopcates = M("merchants_cate");
        $this->merchants = M("merchants");
        $this->merchants_users = M("merchants_users");
        $this->merchants_hfpay = M("merchants_hfpay");
        $this->httpUrl = 'https://fch.yiguanjinrong.com/flashchannel/';
        $this->path = './data/log/hfbank/into/';
        $this->RSA_MAX_ORIGINAL = 117;
        $this->RSA_MAX_CIPHER = 256;
        $this->keystring = 'BD161A60C8933E7EC1D1B802376D6245';
        $this->expanderCd = '820170810145924543966';
        $this->apikey = '185891';
        $this->public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg7pwBwcQWYEF72LAXZap
EgIfIQB5NY3RVcKLF7/mbClEt5x3QODh2ttCtL/SI2rdrvGcyqsMlTCX44TkqZaq
fP3KLxRjJ4qvURpWKxC7z/uIFC+lRumzxnhJqLIOC13kf42MUWgg5sKHnA3XQqlX
RPdX1ZJ/lK+a2d5F0H8tW9uJiqqpfC1qY/fkiPuBh0XgiCHZmqj7VcrLg4P+p0lD
moyXHFFDmQG22rj1TAzcn855Ebdt4vnXENH3fLP3rSE4bCKxkrmZ3AUr9cNhpx4t
FbiRl7Tzv3lLPquzHKu9gFdkImkcra0EYREZKw6kUUmXcpxvxSBt0hzpoqr1L5X6
JwIDAQAB
-----END PUBLIC KEY-----';
    }


    /**
     * 进件列表
     */
    public function index()
    {
        $merchant_name = I('merchant_name');
        if ($merchant_name) {
            $map['m.merchant_name'] = array('like', "%{$merchant_name}%");
            $formget['merchantAlis'] = $merchant_name;
        }
        $count = $this->merchants_hfpay->join('w left join ypt_merchants m on w.merchant_id=m.id')->where($map)->order('w.id desc')->count();
        $page = $this->page($count, 20);
        $pays = $this->merchants_hfpay
            ->field('w.id,w.merchant_id,w.account,m.merchant_name,w.wx_rate,w.ali_rate,w.jd_rate,w.settlement')
            ->join('w left join ypt_merchants m on w.merchant_id=m.id')
            ->where($map)
            ->order('w.merchant_id desc')
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("pays", $pays);
        $this->assign("formget", $formget);
        $this->display();
    }


    /**
     * 进件信息编辑
     */
    public function edit()
    {
        if (IS_POST) {
            $merchant_id = $_GET['merchant_id'];
            $data['account'] = trim(I('account'));
            $data['password'] = trim(I('password'));
            $data['real_name'] = trim(I('real_name'));
            $data['cmer'] = trim(I('cmer'));
            $data['cmer_short'] = trim(I('cmer_short'));
            $data['phone'] = trim(I('phone'));
            $data['business_id'] = trim(I('business_id'));
            $data['wx_rate'] = trim(I('wx_rate'));
            $data['ali_rate'] = trim(I('ali_rate'));
            $data['jd_rate'] = trim(I('jd_rate'));
            $data['channel_code'] = trim(I('channel_code'));
            $data['settlement'] = trim(I('settlement'));
            $data['region_code'] = trim(I('region_code'));
            $data['address'] = trim(I('address'));
            $data['card_no'] = trim(I('card_no'));
            $data['cert_no'] = trim(I('cert_no'));
            $data['mobile'] = trim(I('mobile'));
            if (I('cert_correct')) $data['cert_correct'] = trim(I('cert_correct'));
            if (I('cert_opposite')) $data['cert_opposite'] = trim(I('cert_opposite'));
            if (I('cert_meet')) $data['cert_meet'] = trim(I('cert_meet'));
            if (I('card_correct')) $data['card_correct'] = trim(I('card_correct'));
            if (I('card_opposite')) $data['card_opposite'] = trim(I('card_opposite'));
            if (I('bl_img')) $data['bl_img'] = trim(I('bl_img'));
            $data['email'] = trim(I('email'));
            $data['businessType'] = trim(I('businessType'));
            $data['business'] = trim(I('business'));
            $data['card_type'] = trim(I('card_type'));
            $data['isCorp'] = trim(I('isCorp'));
            $data['open_bank'] = trim(I('open_bank'));
            $data['bankName'] = trim(I('bankName'));
            $data['bankNum'] = trim(I('bankNum'));
            $data['cert_type'] = trim(I('cert_type'));
            if (I('cacc_pubacc_prov')) $data['cacc_pubacc_prov'] = trim(I('cacc_pubacc_prov'));
            if (I('cacc_pubacc_protocol')) $data['cacc_pubacc_protocol'] = trim(I('cacc_pubacc_protocol'));
            if (I('authorization')) $data['authorization'] = trim(I('authorization'));
            if (I('door_img')) $data['door_img'] = trim(I('door_img'));
            if (I('cashier_img')) $data['cashier_img'] = trim(I('cashier_img'));
            if (M('merchants_hfpay')->where(array('merchant_id' => $merchant_id))->find()) {
                M('merchants_hfpay')->where(array('merchant_id' => $merchant_id))->save($data);
                $this->success('编辑成功！');
            } else {
                $data['merchant_id'] = $merchant_id;
                M('merchants_hfpay')->add($data);
                $this->success('编辑成功！');
            }
        } else {
            $merchant_id = $_GET['id'];
            $list = M('Merchants')->where("id='{$merchant_id}'")->find();
            $uid = $list['uid'];
            $phone = M('Merchants_users')->where("id='{$uid}'")->find();
            $this->assign('phone', $phone);
            $this->assign('list', $list);
            $this->assign('id', $merchant_id);
            $merchants_mpay_data = $this->merchants_hfpay->where(array('merchant_id' => $merchant_id))->find();
            $this->assign('data', $merchants_mpay_data);
            $this->display('edit1');
        }
    }


    /**
     * 上传图片
     */
    public function upload_into()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = C('_WEB_UPLOAD_'); // 设置附件上传根目录
        $upload->savePath = 'msinto/'; // 设置附件上传（子）目录
        $info = $upload->upload();
        if ($info) {
            $data['type'] = 1;
            if ($info['cert_correct']) {
                $data['back'] = 1;
                $data['cert_correct'] = $info['cert_correct']['savepath'] . $info['cert_correct']['savename'];
            } else if ($info['cert_opposite']) {
                $data['back'] = 2;
                $data['cert_opposite'] = $info['cert_opposite']['savepath'] . $info['cert_opposite']['savename'];
            } else if ($info['bl_img']) {
                $data['back'] = 3;
                $data['bl_img'] = $info['bl_img']['savepath'] . $info['bl_img']['savename'];
            } else if ($info['card_correct']) {
                $data['back'] = 4;
                $data['card_correct'] = $info['card_correct']['savepath'] . $info['card_correct']['savename'];
            } else if ($info['card_opposite']) {
                $data['back'] = 5;
                $data['card_opposite'] = $info['card_opposite']['savepath'] . $info['card_opposite']['savename'];
            } else if ($info['cacc_pubacc_prov']) {
                $data['back'] = 6;
                $data['cacc_pubacc_prov'] = $info['cacc_pubacc_prov']['savepath'] . $info['cacc_pubacc_prov']['savename'];
            } else if ($info['cacc_pubacc_protocol']) {
                $data['back'] = 7;
                $data['cacc_pubacc_protocol'] = $info['cacc_pubacc_protocol']['savepath'] . $info['cacc_pubacc_protocol']['savename'];
            } else if ($info['authorization']) {
                $data['back'] = 8;
                $data['authorization'] = $info['authorization']['savepath'] . $info['authorization']['savename'];
            } else if ($info['door_img']) {
                $data['back'] = 9;
                $data['door_img'] = $info['door_img']['savepath'] . $info['door_img']['savename'];
            } else if ($info['cashier_img']) {
                $data['back'] = 10;
                $data['cashier_img'] = $info['cashier_img']['savepath'] . $info['cashier_img']['savename'];
            }

            echo json_encode($data);
            exit();
        } else {
            $data['type'] = 2;
            $data['message'] = $upload->getError();
            echo json_encode($data);
            exit();
        }
    }

    /**
     * 进件1:商户注册
     */
    public function register()
    {
        $id = I('id');
        $bankData = $this->merchants_hfpay->where(array('id' => $id))->find();
        $post_data = array(
            'account' => $bankData['account'],
            'pass' => $bankData['password'],
            'code' => $this->apikey,
            'cbzid' => $this->expanderCd
        );
        $result = $this->send_post($this->httpUrl . 'rlregister', $post_data);
        $result = json_decode($result, true);
        file_put_contents('./data/log/hfbank/into/register.log', date("Y-m-d H:i:s") . "进件信息:" . json_encode($result) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($result['respCode'] == '000000') {
            $this->merchants_hfpay->where(array('id' => $id))->save(array('into_type' => 1));
            $reg['code'] = 'success';
            $reg['msg'] = '注册成功';
        } else {
            $reg['code'] = 'error';
            $reg['msg'] = $result['respInfo'];

        }
        $this->ajaxReturn($reg);
    }


    /**
     * 进件2:下载密钥
     */
    public function get_key()
    {
        $id = I('id');
        $bankData = $this->merchants_hfpay->where(array('id' => $id))->find();
        $post_data = array(
            'orderCode' => 'tb_DownLoadKey',
            'account' => $bankData['account'],
            'password' => $bankData['password'],
            'language' => 'PHP'//非必填项,不填默认为Java
        );
        $datas = base64_encode(json_encode($post_data));
        $encrypted = $this->rsaPublicEncrypt($datas, $this->public_key);
        $params = array(
            'data' => $encrypted
        );
        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($params));
        $res = json_decode($res, true);

        $data = $res['data'];
        $count = $res['count'];
        $plain_text = mcrypt_decrypt(MCRYPT_3DES, $this->hexStrToBytes($this->keystring, 24), $this->hexStrToBytes($data), MCRYPT_MODE_ECB);
        $resjson = substr($plain_text, 0, $count);
        $resArr = json_decode($resjson, true);
        file_put_contents('./data/log/hfbank/into/getkey.log', date("Y-m-d H:i:s") . "进件信息:" . json_encode($resArr) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($resArr['respCode'] == '00000') {
            $opData['into_type'] = I('into_type', '2');
            $opData['privatekey'] = $resArr['privatekey'];
            $this->merchants_hfpay->where(array('id' => $id))->save($opData);
            $reg['code'] = 'success';
            $reg['msg'] = '秘钥获取成功';
        } else {
            $reg['code'] = 'error';
            $reg['msg'] = $result['respInfo'];
        }
        $this->ajaxReturn($reg);
    }


    /**
     * 进件3:进件
     */
    public function into()
    {
        $id = I('id');
        $bankData = $this->merchants_hfpay->where(array('id' => $id))->find();
        $post_data = array(
            'account' => $bankData['account'],
            'orderCode' => 'tb_verifyInfo'
        );
        $msgDate = array(
            'real_name' => base64_encode($bankData['real_name']), //真实姓名
            'cmer' => base64_encode($bankData['cmer']), //商户全称
            'phone' => $bankData['phone'], //商户联系电话
            'cmer_short' => base64_encode($bankData['cmer_short']), //商户简称
            'business_id' => $bankData['business_id'], //经营类目(传对应的微信MCC)
            'wx_rate' => $bankData['wx_rate'] / 100,
            'ali_rate' => $bankData['ali_rate'] / 100,
            'jd_rate' => $bankData['jd_rate'] / 100,
            'channel_code' => $bankData['channel_code'],
            'email' => $bankData['email'],
            'businessType' => $bankData['businessType'],
            'business' => $bankData['business'],
            'settlement' => $bankData['settlement'],
            'region_code' => $bankData['region_code'],
            'address' => $bankData['address'],
            'card_type' => 1,
            'card_no' => $bankData['card_no'],
            'cert_type' => '00',
            'cert_no' => $bankData['cert_no'],
            'mobile' => $bankData['mobile'],
            'location' => base64_encode($bankData['location'])//结算卡开户城市
        );
        if ($bankData['isCorp'] == 'Y') {
            $msgDate['open_bank'] = $bankData['open_bank'];
            $msgDate['bankName'] = $bankData['bankName'];
            $msgDate['bankNum'] = $bankData['bankNum'];
            $picJson['cacc_pubacc_prov'] = $this->base64EncodeImage('./data/upload/' . $bankData['cacc_pubacc_prov']);
            $picJson['cacc_pubacc_protocol'] = $this->base64EncodeImage('./data/upload/' . $bankData['cacc_pubacc_protocol']);
        }
        $picJson = array(
            'cert_correct' => $this->base64EncodeImage('./data/upload/' . $bankData['cert_correct']),
            'cert_opposite' => $this->base64EncodeImage('./data/upload/' . $bankData['cert_opposite']),
            'card_correct' => $this->base64EncodeImage('./data/upload/' . $bankData['card_correct']),
            'card_opposite' => $this->base64EncodeImage('./data/upload/' . $bankData['card_opposite']),
            'bl_img' => $this->base64EncodeImage('./data/upload/' . $bankData['bl_img'])
        );
        if ($bankData['authorization']) $picJson['authorization'] = $this->base64EncodeImage('./data/upload/' . $bankData['authorization']);
        if ($bankData['door_img']) $picJson['door_img'] = $this->base64EncodeImage('./data/upload/' . $bankData['door_img']);
        if ($bankData['cashier_img']) $picJson['cashier_img'] = $this->base64EncodeImage('./data/upload/' . $bankData['cashier_img']);
        $sign = $this->rsaDataSign(json_encode($msgDate), $bankData['privatekey']); //RSA签名
        $post_data['msg'] = json_encode($msgDate);
        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->public_key); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign,
            'pic' => json_encode($picJson)
        );
        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));
        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];
        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $bankData['privatekey']); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
        file_put_contents('./data/log/hfbank/into/into.log', date("Y-m-d H:i:s") . "进件信息:" . json_encode($res_msg) . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->public_key);

        if ($valid == 'success') {
            if ($res_msg['respCode'] == '00000') {
                $this->merchants_hfpay->where(array('id' => $id))->update(array('into_type' => 3));
                $reg['code'] = 'success';
                $reg['msg'] = '进件成功';
            } else {
                $reg['code'] = 'error';
                $reg['msg'] = $res_msg['respInfo'];
            }
        } else {
            $reg['code'] = 'error';
            $reg['msg'] = '验签失败';
        }
        $this->ajaxReturn($reg);
    }


    /**
     * 发送http请求 file_get_contents
     * @param $url
     * @param $post_data
     * @return mixed
     */
    private function send_post($url, $post_data)
    {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 30 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }


    /**
     * 发送http请求 curl
     * @param string $url
     * @param string $post_data
     * @return bool
     */
    private function send_post_http($url = '', $post_data = '')
    {
        if (empty($url) || empty($post_data)) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    /**
     * 加密
     * @param $data
     * @param $keyPath
     * @return mixed
     */
    private function rsaPublicEncrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_public($keyPath);
        $ciphertext = null;
        $cipher_len = strlen($data);
        if ($cipher_len - $this->RSA_MAX_ORIGINAL > 0) {
            $flag = 0;
            for ($i = ceil($cipher_len / $this->RSA_MAX_ORIGINAL); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_ORIGINAL);
                $r = openssl_public_encrypt($temp, $encryptData, $key);
                $ciphertext .= $encryptData;
                if ($r) {
                    $flag += $this->RSA_MAX_ORIGINAL;
                }
            }
        } else {
            $r = openssl_public_encrypt($data, $encryptData, $key);
            if ($r) {
                $ciphertext = $encryptData;
            }
        }
        return base64_encode($ciphertext);
    }


    /**
     * RSA私钥解密(分段解密)
     * @param $data
     * @param $keyPath
     * @return bool|string
     */
    private function rsaPrivateDecrypt($data, $keyPath)
    {
        $key = openssl_pkey_get_private($keyPath);
        $originalText = null;
        $original_len = strlen($data);
        if ($original_len - $this->RSA_MAX_CIPHER > 0) {
            $flag = 0;
            for ($i = ceil($original_len / $this->RSA_MAX_CIPHER); $i > 0; $i--) {
                $temp = substr($data, $flag, $this->RSA_MAX_CIPHER);
                $r = openssl_private_decrypt($temp, $decrypted, $key);
                $originalText .= $decrypted;
                if ($r) {
                    $flag += $this->RSA_MAX_CIPHER;
                }
            }
        } else {
            $r = openssl_private_decrypt($data, $decrypted, $key);
            if ($r) {
                $originalText = $decrypted;
            }
        }
        return base64_decode($originalText);
    }


    /**数据签名
     * @param $data
     * @param $keyPath
     * @return bool
     */
    private function rsaDataSign($data, $keyPath)
    {

        if (empty($data)) {
            return False;
        }

        $private_key = $keyPath;
        if (empty($private_key)) {
            return False;
        }

        $pkeyid = openssl_get_privatekey($private_key);
        if (empty($pkeyid)) {
            return False;
        }

        $verify = openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        openssl_free_key($pkeyid);
        return base64_encode($signature);
    }


    /**
     * 数据验签
     * @param string $data
     * @param string $signature
     * @param $keyPath
     * @return bool
     */
    private function isValid($data = '', $signature = '', $keyPath)
    {
        if (empty($data) || empty($signature)) {
            return False;
        }

        $public_key = $keyPath;
        if (empty($public_key)) {

            return False;
        }

        $pkeyid = openssl_get_publickey($public_key);
        if (empty($pkeyid)) {
            return False;
        }

        $ret = openssl_verify($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
        if ($ret == 1) {
            return 'success';
        } else {
            return 'error';
        }
    }


    /**
     * @param $str
     * @param null $length
     * @return mixed
     */
    private function hexStrToBytes($str, $length = null)
    {
        $ret = array('c*');
        for ($i = 0, $l = strlen($str) / 2; $i < $l; ++$i) {
            $x = intval(substr($str, 2 * $i, 2), 16);
            if ($x > 128)
                $x -= 256;
            $ret[] = $x;
        }
        //补全24位
        if (isset($length)) {
            for ($i = count($ret), $j = 1; $i <= $length; ++$i, ++$j)
                $ret[] = $ret[$j];
        }
        return call_user_func_array('pack', $ret);
    }

    /**
     * 图片Base64编码
     * @param $image_file
     * @return mixed
     */
    private function base64EncodeImage($image_file)
    {
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = array(
            'suffix' => pathinfo($image_file, PATHINFO_EXTENSION),
            'content' => chunk_split(base64_encode($image_data))
        );
        return json_encode($base64_image);
    }


    /**
     * 配置恒丰微信子商户接口
     */
    public function add_dev_config()
    {
        $id = I('id', '60');
        $bankData = $this->merchants_hfpay->where(array('id' => $id))->find();
        $jsapi_path = 'https://sy.youngport.com.cn/';
        $sub_mch_id = '74283792';
        $post_data = array(
            'orderCode' => 'add_dev_config',
            'account' => $bankData['account'],
        );

        $sub_appid = 'wx3fa82ee7deaa4a21';
        $subscribe_appid = 'wx8b17740e4ea78bf5';
        $msgBody = array(
            'sub_mch_id' => $sub_mch_id,
//            'paramName' => 'jsapi_path',
//            'paramValue' => $jsapi_path,
//            'paramName' => 'sub_appid',
//            'paramValue' => $sub_appid,
            'paramName' => 'subscribe_appid',
            'paramValue' => $subscribe_appid,
        );

        $msgJson = base64_encode(json_encode($msgBody));
        $sign = $this->rsaDataSign($msgJson, $bankData['privatekey']); //RSA签名
        $post_data['msg'] = $msgJson;

        $data = base64_encode(json_encode($post_data));
        $decrypt = $this->rsaPublicEncrypt($data, $this->public_key); //RSA公钥加密

        $send_data = array(
            'data' => $decrypt,
            'signature' => $sign
        );
        $res = $this->send_post_http($this->httpUrl . 'Kubei', json_encode($send_data));

        $res = json_decode($res, true);
        $res_data = $res['data'];
        $res_sign = $res['signature'];
        //  解析返回数据
        $original = $this->rsaPrivateDecrypt(base64_decode($res_data), $bankData['privatekey']); //RSA私钥解密
        $original = json_decode($original, true);
        $res_msg = json_decode($original['msg'], true);
        //  验签
        $valid = $this->isValid($original['msg'], base64_decode($res_sign), $this->public_key);

        if ($valid == 'success') {
            if ($res_msg['respCode'] == '00000') {
                $reg['code'] = 'success';
                $reg['msg'] = '操作成功';
            } else {
                $reg['code'] = 'error';
                $reg['respCode'] = $res_msg['respCode'];
                $reg['msg'] = $res_msg['respInfo'];
            }
        } else {
            $reg['code'] = 'error';
            $reg['msg'] = '验签失败';
        }

        echo json_encode($reg);
    }

}