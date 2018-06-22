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
<div class="wrap">
    <ul class="nav nav-tabs">
        <li><a href="<?php echo U('AdminService/index');?>">开通列表</a></li>
        <li><a href="<?php echo U('AdminService/openList');?>">商家列表</a></li>
        <li><a href="<?php echo U('AdminService/serverList');?>">服务列表</a></li>
        <li class="active"><a href="<?php echo U('AdminService/open');?>">开通服务</a></li>
    </ul>
    <form method="post" class="form-horizontal" action="<?php echo U('AdminService/open');?>">
        <div class="control-group">
            <label class="control-label">商家id</label>
            <div class="controls">
                <input type="text" name="id" value="<?php echo ($data["id"]); ?>" readonly="readonly" placeholder="请输入商家id">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">商家名称</label>
            <div class="controls">
                <input type="text"  name="user_name" value="<?php echo ($data["merchant_name"]); ?>" readonly="readonly" placeholder="请填写商家名称">
            </div>
        </div>
        <?php if(($data["end_time"] == '' and $data["is_time"] == 1) OR ($data["end_time"] < $data["now"] and $data["is_time"] == 1)): ?><div class="control-group">
                <label class="control-label">开通状态</label>
                <div class="controls">
                    <span>未开通</span>
                </div>
            </div>
            <?php else: ?>
            <div class="control-group">
                <label class="control-label">开通状态</label>
                <div class="controls">
                    <span>开通中</span>
                </div>
            </div>
            <?php if($data["is_time"] == 1): ?><div class="control-group">
                    <label class="control-label">到期时间</label>
                    <div class="controls">
                        <?php echo (date("Y-m-d",$data["end_time"])); ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="control-group">
                    <label class="control-label">到期时间</label>
                    <div class="controls">
                        永久
                    </div>
                </div><?php endif; ?>
            <input type="hidden" name="over_time" value="<?php echo ($data["end_time"]); ?>"><?php endif; ?>
        <!-- <div class="control-group">
            <label class="control-label">
                开通类型
            </label>
            <div class="controls">
                <select name="mini_type" >
                    <option value="" selected>请选择</option>
                    <option value="1">多店版便利店</option>
                    <option value="2">点餐</option>
                    <option value="3">单店版便利店</option>
                </select>
            </div>
        </div> -->
        <div class="control-group">
            <label class="control-label">
                开通行业
            </label>
            <div class="controls">
                <input type="radio" name="trade" value="1" checked="checked">&nbsp;便利店&nbsp;&nbsp;&nbsp;
                <input type="radio" name="trade" value="2" >&nbsp;餐饮
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
                独立小程序
            </label>
            <div class="controls">
                <input type="radio" name="is_own" value="1">&nbsp;拥有&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="radio" name="is_own" value="2" checked="checked">&nbsp;未有
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
                是否加入商圈版
            </label>
            <div class="controls">
                <input type="radio" name="is_enter" value="1" checked="checked">&nbsp;加入&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="radio" name="is_enter" value="2">&nbsp;不加入
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
                <?php if(($data["end_time"] == '') OR ($data["end_time"] < $data.now)): ?>开通时长
                    <?php else: ?>
                    增加时长<?php endif; ?>
            </label>
            <div class="controls">
                <select name="addTime" id="long">
                    <option value="" selected>请选择</option>
                    <option value="1">1个月</option>
                    <option value="3">3个月</option>
                    <option value="6">6个月</option>
                    <option value="12">12个月</option>
                    <option value="zero">永久</option>
                    <option value="other">选择时间段</option>
                </select>
            </div>
        </div>
        <div class="control-group" id="otherTime" style="display: none">
            <label class="control-label"></label>
            <div class="controls">
                <input type="text" class="js-date" name="end_time" id="end_time" placeholder="请选择到期时间">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">
                支付方式
            </label>
            <div class="controls">
                <select name="pay_type" id="long">
                    <option value="" selected>请选择</option>
                    <option value="admin">赠送</option>
                    <option value="yue">余额</option>
                    <option value="wx">微信</option>
                    <option value="zfb">支付宝</option>
                </select>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">支付金额</label>
            <div class="controls">
                <input type="text"  name="price" value=""  placeholder="请输入支付金额">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">备注</label>
            <div class="controls">
                <input type="text" value="管理员赠送"  name="remark" placeholder="请填写使用描述">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" id="onSubmit" class="btn btn-primary js-ajax-submit">开通</button>
            <a class="btn" href="javascript:history.back(-1);"><?php echo L('BACK');?></a>
        </div>
    </form>
</div>
<script src="/public/js/common.js"></script>
<script>
    $("#long").change(function () {
        var ss = $(this).children('option:selected').val();
        if (ss == "other") {
            $("#otherTime").show();
        }else{
            $("#otherTime").hide();
            $("#end_time").val("");
        }
    });
</script>
</body>
</html>