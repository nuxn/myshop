<?php
//平台公钥
// $pubilc_pkey='-----BEGIN PUBLIC KEY-----
// MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDQ/f+i/3LHz53nXXHYO6HdL71H
// 5nf3azES2/KX+DJGWSAPfmNdeefJibWSogAttMfctGBECGZNKPoaQYStp7yqZaZy
// Qzfx9Li86yo4Goav8Ze5t46SNajR2AtGqOzoAjM/Wuyw266ZIwU9uwSJmLWpAC6s
// TTcddvaZ+XHL4LvUqQIDAQAB
// -----END PUBLIC KEY-----';
// 洋仆淘公钥
$pubilc_key="-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCbexvFt/rOGUOVDPbT99wWt3Ch
nmcqRc+lmJkEDHP98c8rd+IhV34VfjeA2+bhaJ66ZlN+sxJG871GIA6X9o7MOFjF
sdAkXYAK+EyHiRZx4drhoaiMLqxP+ygH3BlvvEEHUUT+ZW0lg2wgcRrzcUDHKZ0u
112cQkZgo+Skivm6QQIDAQAB
-----END PUBLIC KEY-----";
//正式私钥
// $private_key="-----BEGIN RSA PRIVATE KEY-----
// MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==
// -----END RSA PRIVATE KEY-----";
//测试私钥
// $private_key="-----BEGIN RSA PRIVATE KEY-----
// MIICXQIBAAKBgQDZcfK4VpSmB+eAWk7i/I0bl7bLLu869ODuir2Q08yMnLwKxd6I
// TFHIBSEOGviZbzeUtbKJJsS0yj6+Ma0usUZ2lLyFtal4eUl78KQRBz6QqB16j+cz
// TH7cQNjB+JLT3ygbVqaCiz9g7CeBKRka9+MD9wq3IWG45WMhnWOZbzHGxwIDAQAB
// AoGAL8wydH7jshNuufIgARlO00/oKIWqpKULhKQOw3UrM4WIeD3Ciudr2rH18CnR
// l7iw2QmPs0JIXw1N+XTmAquJN0POHI4i1XubBZSTbnAYKpRHMay7FJE7l8zZ7Tlo
// 06VowJ31FwexpL0+3pKZwsKlm/KpVBh87BoqE5EzstHHyFECQQD7kJiNBKTkaj/r
// /1XeklpcaugXttIbE+Xq1ac9intSnM20MEYtqIAaWETGgJk+JFn7JdoVP4W5hEhv
// 8splkvgTAkEA3UdbgiV0iaru1K5YOMwmuJAZNK8GGLbSyY9Yy+neZCmsH/YhIKAB
// JVZUWVll0Z8g+DXrH/J+ZE5YLWsM/o50/QJBAItWj+isBdkusLE7AIkDb2F5JYzd
// CotM/jCQns2LgrtDdvyzMGvhxPLSqWV5nWe6IszlLmJOiPc0uhqn1EtmmFkCQQCT
// I88StMtQe+yCWkhpxD7/PTq1kKjSKEf0JbDbL3FlU1yUiDsxEZSRel1uaIbPJCxt
// QJVP0hT/qCT0Vpn2b04VAkA2zO1+jxfgx2vcdmDxFmGIok4W0xkztZjsQbdlehrh
// apxhxSGtznmWab+0lvmg80xhJq7QcqsUZEnd1qXic7tC
// -----END RSA PRIVATE KEY-----
// ";
//生成签名
//[{"action":"mcht/info/enter","responseCode":"00","status":"3","resultMsg":"1302:已受理！","acquirerTypes":"[{\"acquirerType\":\"alipay\",\"custId\":\"170525183628875\"},{\"acquirerType\":\"qq\",\"custId\":\"170525183628876\"},{\"acquirerType\":\"wechat\",\"custId\":\"170525183627874\"}]"}]
function rsaSign($data, $private_key) {
    //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
    $res=openssl_get_privatekey($private_key);

    if($res)
    {
        openssl_sign($data, $sign,$res);
    }
    else {
        echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";
        exit();
    }
    openssl_free_key($res);
    $sign=strtoupper(bin2hex($sign));
    return $sign;
}
//http 请求
function httpRequst($url,$data,$res,$appkey){
	$post_data='params='.$data;
	$ch = curl_init();  
	curl_setopt($ch, CURLOPT_URL, $url);  
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);  
	curl_setopt($ch, CURLOPT_MAXREDIRS, 4);  
	curl_setopt($ch, CURLOPT_ENCODING, ""); 
	curl_setopt($ch, CURLOPT_HTTPHEADER,array(
	"Content-Type:application/x-www-form-Urlencoded;charset=utf-8",
	"Accept-Language:zh-cn",
	"x-apsignature:".$res,
	"x-appkey:".$appkey
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT,180);

	$header=array(
	"Content-Type:application/x-www-form-Urlencoded;charset=utf-8",
	"Accept-Language:zh-cn",
	"x-apsignature:".$res,
	"x-appkey:".$appkey
	);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);  
	$output = curl_exec($ch);  
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$response_header = substr($output, 0, $header_size);
    $response_body = substr($output, $header_size);
    curl_close($ch);
    $response_body = trim($response_body, '[');
	$response_body = trim($response_body, ']');

	$response_body = json_decode($response_body, 1);

	$response_header_arr = array();
	$response_header_arr = explode(': ', $response_header);
	if ((json_last_error() != JSON_ERROR_NONE) or empty($response_header_arr))
	{
		throw new QrcodePayException("Analyze return json error.");
	}
	$response_header_return = array();
	if (!empty($response_header_arr[4]))
	{
		$response_header_return['x_apsignature'] = str_replace(array("\r\n", "\r", "\n", "Content-Type"), "", $response_header_arr[4]);
	}
	return json_encode(array('header' => $response_header_return, 'body' => $response_body));
}
function paycode($db,$uid){
	$paycode=$db->query("select * from ypt_merchants_mpay where uid='$uid'");
	return $paycode;
}



