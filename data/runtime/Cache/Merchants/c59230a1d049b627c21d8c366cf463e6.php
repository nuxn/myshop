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
        <li><a href="<?php echo U('AdminService/openList');?>">商家列表</a></li>
        <li class="active"><a href="<?php echo U('AdminService/serverList');?>">服务列表</a></li>
    </ul>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>小程序名称</th>
            <th>封面图</th>
            <th>详情图</th>
            <th>是否开启</th>
            <th>小程序描述</th>
            <th width="120"><?php echo L('ACTIONS');?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($data)): foreach($data as $key=>$vo): ?><tr>
                <td><?php echo ($vo["id"]); ?></td>
                <td><?php echo ($vo["title"]); ?></td>
                <td width="200"><img src="<?php echo ($vo["face_img"]); ?>" style="max-height: 200px;"></td>
                <td>
                    <?php if($vo["img1"] != ''): ?><img src="<?php echo ($vo["img1"]); ?>" style="height: 100px;width:200px;"><?php endif; ?>
                    <?php if($vo["img2"] != ''): ?><img src="<?php echo ($vo["img2"]); ?>" style="height: 100px;width:200px;"><?php endif; ?>
                    <?php if($vo["img3"] != ''): ?><img src="<?php echo ($vo["img3"]); ?>" style="height: 100px;width:200px;"><?php endif; ?>
                </td>
                <td>
                    <span class="change_status" data-status="<?php echo ($vo["is_show"]); ?>" data-id="<?php echo ($vo["id"]); ?>">
                        <img src="/public/images/status_<?php echo ($vo["is_show"]); ?>.gif" alt="改变状态" >
                    </span>
                </td>
                <td><?php echo ($vo["describe"]); ?></td>
                <td>
                    <a href='<?php echo U("AdminService/serverDetail",array("id"=>$vo["id"]));?>'>详情</a>
                    <a href='<?php echo U("AdminService/serverEdit",array("id"=>$vo["id"]));?>'>修改</a>
                </td>
            </tr><?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="pagination" style="text-align: right"><?php echo ($page); ?></div>
</div>
<script src="/public/js/common.js"></script>
<script>
    $(function(){
        $(".change_status").click(function(){
            var data = {
                id : $(this).data('id')
            };
            console.log(data);
            var _this = $(this);
            $.post("<?php echo U('change_status');?>", data, function(ad){
                _this.children("img").attr("src","/public/images/status_"+ad+".gif")
                console.log(ad);
            },"json");
        })
////
//
    })
</script>
</body>
</html>