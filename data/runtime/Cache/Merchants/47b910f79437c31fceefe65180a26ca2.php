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
        <li class="active"><a href="<?php echo U('adminUser/index');?>"><?php echo L('USERS_LIST');?></a></li>
        <li ><a href="<?php echo U('adminUser/add');?>"><?php echo L('USERS_ADD');?></a></li>
    </ul>
    <form class="well form-search" method="post" action="<?php echo U('adminUser/index');?>">
        手机号码:
        <input type="text" name="user_phone" style="width: 100px;" value="<?php echo ($user_phone); ?>" placeholder="请输入用户手机号码">
        <input type="submit" class="btn btn-primary" value="搜索" />
        <a class="btn btn-danger" href="<?php echo U('adminUser/index');?>">清空</a>
    </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th width="50">ID</th>
            <th>用户手机号码</th>
            <th>用户名</th>
            <th>角色</th>
            <th>上级代理商</th>
            <th>IP地址</th>
            <th>添加时间</th>
            <th width="120"><?php echo L('ACTIONS');?></th>
        </tr>
        </thead>
        <tbody>
        <?php $user_statuses=array("0"=>L('USER_STATUS_BLOCKED'),"1"=>L('USER_STATUS_ACTIVATED'),"2"=>L('USER_STATUS_UNVERIFIED')); ?>
        <?php if(is_array($users)): foreach($users as $key=>$vo): ?><tr>
                <td><?php echo ($vo["id"]); ?></td>
                <td><?php echo ($vo["user_phone"]); ?></td>
                <td contenteditable="true" class="change_name" data-id="<?php echo ($vo["id"]); ?>" ><?php echo ($vo["user_name"]); ?></td>
                <td><?php echo ($vo["role_name"]); ?></td>
                <td>
                    <?php if($vo['p_user'] == 13128898154): else: ?>
                        <?php echo ($vo["p_user"]); endif; ?>
                </td>
                <td><?php echo ($vo["ip_address"]); ?></td>
                <td><?php echo (date('Y-m-d',$vo["add_time"])); ?></td>
                <td>
                    <a href='<?php echo U("adminRbac/user_authorize",array("id"=>$vo["id"]));?>'>设置权限</a>
                    <a href='<?php echo U("AdminUser/change_phone",array("id"=>$vo["id"]));?>' onclick="return confirm('确定将此记录删除?')">删除手机号</a>
                    <!--<a href='<?php echo U("adminUser/edit",array("id"=>$vo["id"]));?>'><?php echo L('EDIT');?></a>-->
                    <!--<a class="js-ajax-delete" onclick="return confirm('确定删除吗？')" href="<?php echo U('adminUser/del',array('id'=>$vo['id']));?>"><?php echo L('DELETE');?></a>-->
                </td>
            </tr><?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="pagination" style="text-align: right"><?php echo ($page); ?></div>
</div>
<script src="/public/js/common.js"></script>
<script>
    $(function(){
        $(".change_name").blur(function(){
           var data={
                id:$(this).data('id'),
                new_name:$(this).text()
            };
            console.log(data);
            $.post("<?php echo U('change_name');?>", data)
        });
    })
</script>
</body>
</html>