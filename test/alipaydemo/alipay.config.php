<?php
/* *
 * 配置文件
 * 版本：3.5
 * 日期：2016-06-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
 * 解决方法：
 * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
 * 2、更换浏览器或电脑，重新登录查询。
 */
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
$alipay_config['partner']		= '2088421497824441';

//收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
$alipay_config['seller_id']	= $alipay_config['partner'];

//商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
$alipay_config['private_key'] = 'MIICXgIBAAKBgQDktchFUfoxjoaGGTOY5/S9cpWWon6Gc2AmmwgEwmqCHMnUIPRMa9nYfJEJo0lnJsJTUfmjwvuyWmKLyBdLjvEIbvvNMHtddKev5WfO4SEU24E2OPeGAQdxFcM89mBHxgcYoGIU7W8WUKTwY2oTjNoFuHG4SFsSBr8FdB0K0E9T/QIDAQABAoGBAMgZz5XuymKvWz1aMU2XrAZQiVZY5zBFI5vDSjm3y634+BCzoGp4dgm1usPe7Crmu2BguXSw9Lwv3kaEEvWVo4VgmOmpUTPBRxBOkSlbEH3zxsy2JtzcSV0dGLzkWlsmWymOCktk7XwKj9KCNLcfyiygIvWlI/sJRLg6fz+X2PN1AkEA/O+1LbRYLP7IvF6KO8Q0+G4/FdNThreoTwuO7ve32mUMBQOFrej08mbt9XAFuB+t7L3FKqEa7Tqq4uoiFpxSRwJBAOd69Hm4sE5bi59qO3hUCxlyLUaw5+PnmWv90wlGCNxgNYZWQyNJES74ifD4Jo3Ya2vJI9azF+MbaxXkLt1w5ZsCQQCPw6eVPMZJfN+XwZyMb+8zrWYJ/72f+s+dbhJl3UMQzRJR3ziiKqDfDoX+VRfLGaZ/wzVID54AbLIom6+Ybm2NAkEAhKZdOvOvdPfZBz2lXssqoSZg88Wk3WF29f+60/GiWkd35MfCKZJRmo0q30AVN+vxgw78zqhK5AZuU1kz0gpESQJAboDgERMcwS5e9ib9ax5lPzsAQ5F5JVUHH0VogEzpx6GjAzPp3/94zoCMEHTBpGy2ABpiyjTJvVM2Aaa0qAfmvw==';

//支付宝的公钥，查看地址：https://b.alipay.com/order/pidAndKey.htm
$alipay_config['alipay_public_key'] = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';


// 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
$alipay_config['notify_url'] = "http://sy.youngport.com.cn/alipay/notify_url.php";

// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
$alipay_config['return_url'] = "http://sy.youngport.com.cn/alipay/return_url.php";

//签名方式
$alipay_config['sign_type']    = strtoupper('RSA');

//字符编码格式 目前支持utf-8
$alipay_config['input_charset']= strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert']    = getcwd().'\\cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport']    = 'http';

// 支付类型 ，无需修改
$alipay_config['payment_type'] = "1";
		
// 产品类型，无需修改
$alipay_config['service'] = "create_direct_pay_by_user";

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


?>