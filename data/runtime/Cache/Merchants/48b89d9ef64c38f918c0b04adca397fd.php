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
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo U('AdminService/index');?>">开通列表</a></li>
        <li class="active"><a href="<?php echo U('AdminService/openList');?>">商家列表</a></li>
		<li><a href="<?php echo U('AdminService/serverList');?>">服务列表</a></li>
    </ul>
    <form class="well form-search" method="post" action="<?php echo U('AdminService/openList');?>">
        商家ID:
        <input type="text" name="id" style="width: 200px;" value="" placeholder="请输入商家ID">&nbsp;&nbsp;
        商家名称:
        <input type="text" name="merchant_name" style="width: 200px;" value="" placeholder="请输入商家名称">&nbsp;&nbsp;
        手机号码:
        <input type="text" name="user_phone" style="width: 200px;" value="" placeholder="请输入商家手机号码">&nbsp;&nbsp;
        商家openid:
        <input type="text" name="openid" style="width: 200px;" value="" placeholder="请输入商家openid">&nbsp;&nbsp;
        <input type="submit" class="btn btn-primary" value="搜索" />
        <a class="btn btn-danger" href="<?php echo U('AdminService/openList');?>">清空</a>
    </form>
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th width="50">商家ID</th>
                <th>商家名</th>
                <th>手机号码</th>
                <th>openid</th>
                <th width="120"><?php echo L('ACTIONS');?></th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($data_lists)): foreach($data_lists as $key=>$vo): ?><tr>
                    <td><?php echo ($vo["id"]); ?></td>
                    <td><?php echo ($vo["merchant_name"]); ?></td>
                    <td><?php echo ($vo["user_phone"]); ?></td>
                    <td><?php echo ($vo["openid"]); ?></td>
                    <td><a href='<?php echo U("AdminService/open",array("id"=>$vo["id"]));?>'>开通</a></td>
                </tr><?php endforeach; endif; ?>
            </tbody>
        </table>
        <div class="pagination" style="text-align: right"><?php echo ($page); ?></div>
</div>
<script src="/public/js/common.js"></script>
</body>
</html>