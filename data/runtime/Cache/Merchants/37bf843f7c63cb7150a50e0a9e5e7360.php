<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<!-- Set render engine for 360 browser -->
	<meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->

	<link href="/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
	<script type="text/javascript">
	//全局变量
	var GV = {
	    ROOT: "/",
	    WEB_ROOT: "/",
	    JS_ROOT: "public/js/",
	    APP:'<?php echo (MODULE_NAME); ?>'/*当前应用名*/
	};
	</script>
    <script src="/public/js/jquery.js"></script>
    <script src="/public/js/wind.js"></script>
    <script src="/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
    <script>
    	$(function(){
    		$("[data-toggle='tooltip']").tooltip();
    	});
    </script>
<?php if(APP_DEBUG): ?><style>
		#think_page_trace_open{
			z-index:9999;
		}
	</style><?php endif; ?>
<link href="/public/plugins/magiczoomplus/magiczoomplus.css" rel="stylesheet" type="text/css" media="screen"/>
</head>
<body>
<div class="wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo U('AdminService/index');?>">开通列表</a></li>
        <li><a href="<?php echo U('AdminService/openList');?>">商家列表</a></li>
		<li><a href="<?php echo U('AdminService/serverList');?>">服务列表</a></li>
        <li class="active"><a href="javascript:;">开通详情</a></li>
    </ul>
    <fieldset>
        <table class="table table-bordered" >
            <tr>
                <td width=" 200px">id</td>
                <td><?php echo ($data["id"]); ?></td>
            </tr>
            <tr>
                <td width=" 200px">订单号</td>
                <td><?php echo ($data["order_sn"]); ?></td>
            </tr>
            <tr>
                <td width=" 200px">商户ID</td>
                <td><?php echo ($data["mid"]); ?></td>
            </tr>
            <tr>
                <td width=" 200px">商户名称</td>
                <td><?php echo ($data["user_name"]); ?></td>
            </tr>
            <tr>
                <td width=" 200px" >购买类型</td>
                <td>
                    <?php if($data["type"] == '1'): ?>小程序
                        <?php else: ?>
                        <?php echo ($data["type"]); endif; ?>
                </td>
            </tr>
            <?php if($data["pay_type"] != 'admin'): if($data["status"] == '0'): ?><tr>
                        <td width=" 200px" >购买状态</td>
                        <td>未付款</td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td width=" 200px" >购买状态</td>
                        <td>已付款</td>
                    </tr>
                    <tr>
                        <td width=" 200px" >支付方式</td>
                        <td>
                            <if condition="$data.pay_type eq 'zfb'">支付宝<?php endif; ?>
                            <?php if($data["pay_type"] == 'wx'): ?>微信<?php endif; ?>
                            <?php if($data["pay_type"] == 'yue'): ?>余额<?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td width=" 200px" >支付时间</td>
                        <td><?php echo (date("Y-m-d H:i:s",$data['pay_time'])); ?></td>
                    </tr><?php endif; ?>
                <?php if($data["pay_type"] != 'admin'): ?><tr>
                        <td width=" 200px" >商品价格</td>
                        <td><?php echo ($data["goods_price"]); ?></td>
                    </tr>
                    <tr>
                        <td width=" 200px" >实付金额</td>
                        <td><?php echo ($data["order_price"]); ?></td>
                    </tr>
                    <tr>
                        <td width=" 200px" >收到金额</td>
                        <td><?php echo ($data["pay_price"]); ?></td>
                    </tr><?php endif; ?>
                <?php if($data["title"] != ''): ?><tr>
                        <td width=" 200px">现金券名称</td>
                        <td><?php echo ($data["title"]); ?>(满<?php echo ($data["up_price"]); ?>减<?php echo ($data["price"]); ?>)</td>
                    </tr>
                    <tr>
                        <td width=" 200px">优惠金额</td>
                        <td><?php echo ($data["cash_price"]); ?></td>
                    </tr><?php endif; ?>
            </if>
            <?php if($data["pay_type"] == 'admin'): ?><td width=" 200px" >开通方式</td>
                <td>管理员开通</td><?php endif; ?>
            <tr>
                <td width=" 200px">开通时间</td>
                <td><?php echo (date("Y-m-d H:i:s",$data['add_time'])); ?></td>
            </tr>
            <tr>
                <td width=" 200px">开始时间</td>
                <td><?php echo (date("Y-m-d H:i:s",$data['start_time'])); ?></td>
            </tr>
            <tr>
                <td width=" 200px">到期时间</td>
                <td><?php echo (date("Y-m-d H:i:s",$data['end_time'])); ?></td>
            </tr>
            <tr>
                <td width=" 200px" >备注</td>
                <td><?php echo ($data["remark"]); ?></td>
            </tr>
        </table>
    </fieldset>
    <div class="form-actions">
        <a class="btn" href="javascript:history.back(-1);"><?php echo L('BACK');?></a>
    </div>
</div>
<script src="/public/js/common.js"></script>
<script src="/public/plugins/magiczoomplus/magiczoomplus.js"></script>
</body>
</html>