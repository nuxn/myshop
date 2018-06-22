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
<!-- Bootstrap Core CSS -->
<link href="/public/css//bootstrap.min.css" rel="stylesheet">

<!-- MetisMenu CSS -->
<link href="/public/css/metisMenu.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="/public/css//sb-admin-2.css" rel="stylesheet">

<!-- Morris Charts CSS -->
<link href="/public/css//morris.css" rel="stylesheet">

<!-- Custom Fonts -->
<!--<link href="/public/css/font-awesome.css" rel="stylesheet" type="text/css">-->

<body >
<!-- /. NAV SIDE  -->
<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header">洋仆淘智慧收银体系</h1>
		</div>
	</div>
	<!-- 头部 -->
	<div class="row">
		<div class="col-lg-3 col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<div class="row">
						<div class="col-xs-3">
							<i class="fa fa-space-shuttle fa-5x"></i>
						</div>
						<div class="col-xs-9 text-right">
							<div class="huge"><?php echo ($row['login_number']); ?></div>
							<div>登录次数</div>
						</div>
					</div>
				</div>
				<a href="#">
					<div class="panel-footer">
						<span class="pull-left">View Details</span>
						<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						<div class="clearfix"></div>
					</div>
				</a>
			</div>
		</div>
		<div class="col-lg-3 col-md-6">
			<div class="panel panel-green">
				<div class="panel-heading">
					<div class="row">
						<div class="col-xs-3">
							<i class="fa fa-male fa-5x"></i>
						</div>
						<div class="col-xs-9 text-right">
							<div class="huge"><?php echo ($row['user_number']); ?></div>
							<div>用户总数</div>
						</div>
					</div>
				</div>
				<a href="#">
					<div class="panel-footer">
						<span class="pull-left">View Details</span>
						<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						<div class="clearfix"></div>
					</div>
				</a>
			</div>
		</div>
		<div class="col-lg-3 col-md-6">
			<div class="panel panel-yellow">
				<div class="panel-heading">
					<div class="row">
						<div class="col-xs-3">
							<i class="fa fa-suitcase fa-5x"></i>
						</div>
						<div class="col-xs-9 text-right">
							<div class="huge"><?php echo ($row['merchant_number']); ?></div>
							<div>商户总数</div>
						</div>
					</div>
				</div>
				<a href="#">
					<div class="panel-footer">
						<span class="pull-left">View Details</span>
						<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						<div class="clearfix"></div>
					</div>
				</a>
			</div>
		</div>
		<div class="col-lg-3 col-md-6">
			<div class="panel panel-red">
				<div class="panel-heading">
					<div class="row">
						<div class="col-xs-3">
							<i class="fa fa-weixin  fa-5x"></i>
						</div>
						<div class="col-xs-9 text-right">
							<div class="huge"><?php echo ($row['mem_number']); ?></div>
							<div>微信用户人数</div>
						</div>
					</div>
				</div>
				<a href="#">
					<div class="panel-footer">
						<span class="pull-left">View Details</span>
						<span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						<div class="clearfix"></div>
					</div>
				</a>
			</div>
		</div>
	</div>
	<div class="copyrights">Collect from <a href="http://www.cssmoban.com/"  title="网站模板">网站模板</a></div>
	<!-- /.row -->
	<div class="row">
		<div class="col-lg-8">
			<!--交易流水统计-->
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-bar-chart-o fa-fw"></i>交易流水统计
					<!--<div class="pull-right">-->
					<!--<div class="btn-group">-->
					<!--<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">-->
					<!--Actions-->
					<!--<span class="caret"></span>-->
					<!--</button>-->
					<!--<ul class="dropdown-menu pull-right" role="menu">-->
					<!--<li><a href="#">Action</a>-->
					<!--</li>-->
					<!--<li><a href="#">Another action</a>-->
					<!--</li>-->
					<!--<li><a href="#">Something else here</a>-->
					<!--</li>-->
					<!--<li class="divider"></li>-->
					<!--<li><a href="#">Separated link</a>-->
					<!--</li>-->
					<!--</ul>-->
					<!--</div>-->
					<!--</div>-->
				</div>
				<div class="panel-body">
					<div id="morris-area-chart" >
						<!--图表1-->
						<div id="mer_today" style="height: 350px">
						</div>
					</div>
				</div>
			</div>
			<!-- /.昨日流水排名 -->
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-bar-chart-o fa-fw"></i> 昨日流水排名
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-lg-5">
							<div class="table-responsive">
								<table class="table table-bordered table-hover table-striped">
									<thead>
									<tr>
										<th style="text-align: center">时间</th>
										<th style="text-align: center">支付金额(元)</th>
										<th style="text-align: center">支付方式</th>
										<th style="text-align: center">订单号</th>
									</tr>
									</thead>
									<tbody>
									<?php if(is_array($pay_top)): foreach($pay_top as $key=>$p_top): ?><tr>
											<td style="text-align: center"><?php echo date('H:i:s',$p_top['paytime']);?></td>
											<td style="text-align: center"><?php echo ($p_top["price"]); ?></td>
											<td style="text-align: center"><?php echo paystyle($p_top['paystyle_id']);?></td>
											<td style="text-align: center"><?php echo ($p_top["remark"]); ?></td>
										</tr><?php endforeach; endif; ?>
									</tbody>
								</table>
							</div>
							<a href="<?php echo U('Pay/Contentadmin/index');?>" class="btn btn-default btn-block">查看详细数据</a>

							<!-- /.table-responsive -->
						</div>
						<!--图表二-->
						<div class="col-lg-7" >
							<div id="mer_today1" style="height: 700px">
							</div>
						</div>
					</div>
					<!-- /.row -->
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-clock-o fa-fw"></i> Responsive Timeline
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body">
					<ul class="timeline">
						<li>
							<div class="timeline-badge"><i class="fa fa-check"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
									<p><small class="text-muted"><i class="fa fa-clock-o"></i> 11 hours ago via Twitter</small>
									</p>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Libero laboriosam dolor perspiciatis omnis exercitationem. Beatae, officia pariatur? Est cum veniam excepturi. Maiores praesentium, porro voluptas suscipit facere rem dicta, debitis.</p>
								</div>
							</div>
						</li>
						<li class="timeline-inverted">
							<div class="timeline-badge warning"><i class="fa fa-credit-card"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Autem dolorem quibusdam, tenetur commodi provident cumque magni voluptatem libero, quis rerum. Fugiat esse debitis optio, tempore. Animi officiis alias, officia repellendus.</p>
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Laudantium maiores odit qui est tempora eos, nostrum provident explicabo dignissimos debitis vel! Adipisci eius voluptates, ad aut recusandae minus eaque facere.</p>
								</div>
							</div>
						</li>
						<li>
							<div class="timeline-badge danger"><i class="fa fa-bomb"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Repellendus numquam facilis enim eaque, tenetur nam id qui vel velit similique nihil iure molestias aliquam, voluptatem totam quaerat, magni commodi quisquam.</p>
								</div>
							</div>
						</li>
						<li class="timeline-inverted">
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Voluptates est quaerat asperiores sapiente, eligendi, nihil. Itaque quos, alias sapiente rerum quas odit! Aperiam officiis quidem delectus libero, omnis ut debitis!</p>
								</div>
							</div>
						</li>
						<li>
							<div class="timeline-badge info"><i class="fa fa-save"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nobis minus modi quam ipsum alias at est molestiae excepturi delectus nesciunt, quibusdam debitis amet, beatae consequuntur impedit nulla qui! Laborum, atque.</p>
									<hr>
									<div class="btn-group">
										<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
											<i class="fa fa-gear"></i> <span class="caret"></span>
										</button>
										<ul class="dropdown-menu" role="menu">
											<li><a href="#">Action</a>
											</li>
											<li><a href="#">Another action</a>
											</li>
											<li><a href="#">Something else here</a>
											</li>
											<li class="divider"></li>
											<li><a href="#">Separated link</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</li>
						<li>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sequi fuga odio quibusdam. Iure expedita, incidunt unde quis nam! Quod, quisquam. Officia quam qui adipisci quas consequuntur nostrum sequi. Consequuntur, commodi.</p>
								</div>
							</div>
						</li>
						<li class="timeline-inverted">
							<div class="timeline-badge success"><i class="fa fa-graduation-cap"></i>
							</div>
							<div class="timeline-panel">
								<div class="timeline-heading">
									<h4 class="timeline-title">Lorem ipsum dolor</h4>
								</div>
								<div class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Deserunt obcaecati, quaerat tempore officia voluptas debitis consectetur culpa amet, accusamus dolorum fugiat, animi dicta aperiam, enim incidunt quisquam maxime neque eaque.</p>
								</div>
							</div>
						</li>
					</ul>
				</div>
				<!-- /.panel-body -->
			</div>
		</div>
		<div class="col-lg-4">
			<!--后台任务时时更新-->
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-bell fa-fw"></i> 后台任务时时更新
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body">
					<div class="list-group">
						<a href="#" class="list-group-item">
							<i class="fa fa-comment fa-fw"></i> New Comment
							<span class="pull-right text-muted small"><em>4 minutes ago</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-twitter fa-fw"></i> 3 New Followers
							<span class="pull-right text-muted small"><em>12 minutes ago</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-envelope fa-fw"></i> Message Sent
							<span class="pull-right text-muted small"><em>27 minutes ago</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-tasks fa-fw"></i> New Task
							<span class="pull-right text-muted small"><em>43 minutes ago</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-upload fa-fw"></i> Server Rebooted
							<span class="pull-right text-muted small"><em>11:32 AM</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-bolt fa-fw"></i> Server Crashed!
							<span class="pull-right text-muted small"><em>11:13 AM</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-warning fa-fw"></i> Server Not Responding
							<span class="pull-right text-muted small"><em>10:57 AM</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-shopping-cart fa-fw"></i> New Order Placed
							<span class="pull-right text-muted small"><em>9:49 AM</em>
                                    </span>
						</a>
						<a href="#" class="list-group-item">
							<i class="fa fa-money fa-fw"></i> Payment Received
							<span class="pull-right text-muted small"><em>Yesterday</em>
                                    </span>
						</a>
					</div>
					<!-- /.list-group -->
					<a href="#" class="btn btn-default btn-block">View All Alerts</a>
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- 银行支付份额 -->
			<div class="panel panel-default">
				<div class="panel-heading" >
					<i class="fa fa-bar-chart-o fa-fw"></i>银行支付份额
				</div>

				<div class="panel-body">
					<div id="morris-donut-chart">
						<div id="mer_today2" style="height: 400px">
						</div>
					</div>
					<a href="#" class="btn btn-default btn-block">View Details</a>
				</div>
			</div>
			<!-- 聊天室 -->
			<div class="chat-panel panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-comments fa-fw"></i> 聊天室
					<div class="btn-group pull-right">
						<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
							<i class="fa fa-chevron-down"></i>
						</button>
						<ul class="dropdown-menu slidedown">
							<li>
								<a href="#">
									<i class="fa fa-refresh fa-fw"></i> Refresh
								</a>
							</li>
							<li>
								<a href="#">
									<i class="fa fa-check-circle fa-fw"></i> Available
								</a>
							</li>
							<li>
								<a href="#">
									<i class="fa fa-times fa-fw"></i> Busy
								</a>
							</li>
							<li>
								<a href="#">
									<i class="fa fa-clock-o fa-fw"></i> Away
								</a>
							</li>
							<li class="divider"></li>
							<li>
								<a href="#">
									<i class="fa fa-sign-out fa-fw"></i> Sign Out
								</a>
							</li>
						</ul>
					</div>
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body">
					<ul class="chat">
						<li class="left clearfix">
							<!--<span class="chat-img pull-left">-->
							<!--<img src="http://placehold.it/50/55C1E7/fff" alt="User Avatar" class="img-circle" />-->
							<!--</span>-->
							<div class="chat-body clearfix">
								<div class="header">
									<strong class="primary-font">Jack Sparrow</strong>
									<small class="pull-right text-muted">
										<i class="fa fa-clock-o fa-fw"></i> 12 mins ago
									</small>
								</div>
								<p>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum ornare dolor, quis ullamcorper ligula sodales.
								</p>
							</div>
						</li>
						<li class="right clearfix">
							<!--<span class="chat-img pull-right">-->
							<!--<img src="http://placehold.it/50/FA6F57/fff" alt="User Avatar" class="img-circle" />-->
							<!--</span>-->
							<div class="chat-body clearfix">
								<div class="header">
									<small class=" text-muted">
										<i class="fa fa-clock-o fa-fw"></i> 13 mins ago</small>
									<strong class="pull-right primary-font">Bhaumik Patel</strong>
								</div>
								<p>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum ornare dolor, quis ullamcorper ligula sodales.
								</p>
							</div>
						</li>
						<li class="left clearfix">
							<!--<span class="chat-img pull-left">-->
							<!--<img src="http://placehold.it/50/55C1E7/fff" alt="User Avatar" class="img-circle" />-->
							<!--</span>-->
							<div class="chat-body clearfix">
								<div class="header">
									<strong class="primary-font">Jack Sparrow</strong>
									<small class="pull-right text-muted">
										<i class="fa fa-clock-o fa-fw"></i> 14 mins ago</small>
								</div>
								<p>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum ornare dolor, quis ullamcorper ligula sodales.
								</p>
							</div>
						</li>
						<li class="right clearfix">
							<!--<span class="chat-img pull-right">-->
							<!--<img src="http://placehold.it/50/FA6F57/fff" alt="User Avatar" class="img-circle" />-->
							<!--</span>-->
							<div class="chat-body clearfix">
								<div class="header">
									<small class=" text-muted">
										<i class="fa fa-clock-o fa-fw"></i> 15 mins ago</small>
									<strong class="pull-right primary-font">Bhaumik Patel</strong>
								</div>
								<p>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur bibendum ornare dolor, quis ullamcorper ligula sodales.
								</p>

							</div>
						</li>
					</ul>
				</div>
				<!-- /.panel-body -->
				<div class="panel-footer">
					<div class="input-group">
						<input id="btn-input" type="text" class="form-control input-sm" placeholder="Type your message here..." />
						<span class="input-group-btn">
                                    <button class="btn btn-warning btn-sm" id="btn-chat">
                                        Send
                                    </button>
                                </span>
					</div>
				</div>
				<!-- /.panel-footer -->
				More Templates <a href="http://www.cssmoban.com/" target="_blank" title="模板之家">模板之家</a> - Collect from <a href="http://www.cssmoban.com/" title="网页模板" target="_blank">网页模板</a>
			</div>
			<!-- /.panel .chat-panel -->
		</div>
	</div>
</div>


<!--<h2 style="text-align: center">图表统计流</h2>-->
<!--<hr>-->
<!--<div class="row">-->
<!--<div class="span6 offset2">-->
<!--<h3>昨日交易额前10位</h3>-->
<!--<div id="mer_today" style="display: block ;width: 1200px;height: 750px;"></div>-->
<!--</div>-->
<!--</div>-->

<!--<div class="row">-->
<!--<div class="span6 offset2">-->
<!--<h3 >昨日交易次数统计 </h3>-->
<!--<div id="mer_today1" style="display: block ;width: 1200px;height: 750px;"></div>-->
<!--</div>-->
<!--</div>-->

<script src="/public/js/echarts/echarts3.js"></script>
<script src="/public/js/common.js"></script>
</body>
<!--总流水-->
<script type="text/javascript">
	$(function () {
		var data = eval('(' + '<?php echo ($pay); ?>' + ')');
		// 基于准备好的dom，初始化echarts实例
		var myChart = echarts.init(document.getElementById('mer_today'));
		//option
		var option = {

			tooltip: {
				trigger: 'axis',
				axisPointer: { // 坐标轴指示器，坐标轴触发有效
					type: 'shadow' // 默认为直线，可选为：'line' | 'shadow'
				}
			},
			legend: {
				data: ['流水总额'],
				align: 'right',
				right: 50
			},
			grid: {
				left: '3%',
				right: '4%',
				bottom: '3%',
				containLabel: true
			},
			xAxis: [{
				type: 'category',
				data: [data[0]['user_name'], data[1]['user_name'], data[2]['user_name'], data[3]['user_name'], data[4]['user_name'], data[5]['user_name'], data[6]['user_name'], data[7]['user_name'], data[8]['user_name'], data[9]['user_name']],
				axisTick: {
					alignWithLabel: true
				},
				minInterval:{
					minInterval: 60
				}
			}],
			yAxis: [{
				type: 'value',
				name: '总流水(元)',
				axisLabel: {
					formatter: '{value}'
				}
			}],
			series: [{
				name: '总流水',
				type: 'bar',
				barWidth: '40%',
				data: [data[0]['total_price'], data[1]['total_price'], data[2]['total_price'], data[3]['total_price'], data[4]['total_price'], data[5]['total_price'], data[6]['total_price'], data[7]['total_price'], data[8]['total_price'], data[9]['total_price']]
			}],
			itemStyle: {
				normal: {

					color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
						offset: 0,
						color: 'rgba(17, 168,171, 1)'
					}, {
						offset: 1,
						color: 'rgba(17, 168,171, 0.1)'
					}]),
					shadowColor: 'rgba(0, 0, 0, 0.1)',
					shadowBlur: 10
				}
			}
		};
		// 使用刚指定的配置项和数据显示图表。
		myChart.setOption(option);
	})
</script>
<!--昨日流水-->
<script type="text/javascript">
	$(function () {
		var number = eval('(' + '<?php echo ($time_numer); ?>' + ')');
		// 基于准备好的dom，初始化echarts实例
		var myChart = echarts.init(document.getElementById('mer_today1'));
		//option
		var xData = function() {
			var data = [];
			for (var i = 1; i < 13; i++) {
				data.push(2*(i-1)+":00~" + 2*i+":00");
			}
			return data;
		}();

		option = {
			backgroundColor: "#fff",
			"title": {
				x: "4%",

				textStyle: {
					color: '#fff',
					fontSize: '22'
				},
				subtextStyle: {
					color: '#90979c',
					fontSize: '16',

				},
			},
			"tooltip": {
				"trigger": "axis",
				"axisPointer": {
					"type": "shadow",
					textStyle: {
						color: "#fff"
					}

				},
			},
			"grid": {
				"borderWidth": 0,
				"top": 110,
				"bottom": 95,
				textStyle: {
					color: "#fff"
				}
			},
			"legend": {
				x: '4%',
				top: '11%',
				textStyle: {
					color: '#90979c',
				},
				"data": ['女', '男', '平均']
			},


			"calculable": true,
			"xAxis": [{
				"type": "category",
				"axisLine": {
					lineStyle: {
						color: '#000000'
					}
				},
				"splitLine": {
					"show": false
				},
				"axisTick": {
					"show": false
				},
				"splitArea": {
					"show": false
				},
				"axisLabel": {
					"interval": 0,

				},
				"data": xData,
			}],
			"yAxis": [{
				name: '昨日交易量(笔)',
				"type": "value",
				"splitLine": {
					"show": false
				},
				"axisLine": {
					lineStyle: {
						color: '#000000'
					}
				},
				"axisTick": {
					"show": false
				},
				"axisLabel": {
					"interval": 0,

				},
				"splitArea": {
					"show": false
				},

			}],
			"dataZoom": [{
				"show": true,
				"height": 30,
				"xAxisIndex": [
					0
				],
				bottom: 30,
				"start": 30,
				"end": 100,
				handleIcon: 'path://M306.1,413c0,2.2-1.8,4-4,4h-59.8c-2.2,0-4-1.8-4-4V200.8c0-2.2,1.8-4,4-4h59.8c2.2,0,4,1.8,4,4V413z',
				handleSize: '110%',
				handleStyle:{
					color:"#ddd",

				},
				textStyle:{
					color:"#fff"},
				borderColor:"#eee"


			}, {  //滑动条
				"type": "inside",
				"show": true,
				"height": 15,
				"start": 1,
				"end": 35
			}],
			"series": [{
				"name": "微信",
				"type": "bar",
				"stack": "总量",
				"barMaxWidth": 35,
				"barGap": "10%",
				"itemStyle": {
					"normal": {
						"color": "#86c610",  //色值
						"label": {
							"show": false,
							"textStyle": {
								"color": "#fff"
							},
							"position": "insideTop",
							formatter: function(p) {
								return p.value > 0 ? (p.value) : '';
							}
						}
					}
				},
				"data": [
					number[11]['per_weixin_num'],
					number[10]['per_weixin_num'],
					number[9]['per_weixin_num'],
					number[8]['per_weixin_num'],
					number[7]['per_weixin_num'],
					number[6]['per_weixin_num'],
					number[5]['per_weixin_num'],
					number[4]['per_weixin_num'],
					number[3]['per_weixin_num'],
					number[2]['per_weixin_num'],
					number[1]['per_weixin_num'],
					number[0]['per_weixin_num']
				],
			}, {
				"name": "支付宝",
				"type": "bar",
				"stack": "总量",
				"itemStyle": {
					"normal": {
						"color": "#00aaef",  //色值
						"barBorderRadius": 0,
						"label": {
							"show": false,
							"position": "top",
							formatter: function(p) {
								return p.value > 0 ? (p.value) : '';
							}
						}
					}
				},
				"data": [
					number[11]['per_ali_num'],
					number[10]['per_ali_num'],
					number[9]['per_ali_num'],
					number[8]['per_ali_num'],
					number[7]['per_ali_num'],
					number[6]['per_ali_num'],
					number[5]['per_ali_num'],
					number[4]['per_ali_num'],
					number[3]['per_ali_num'],
					number[2]['per_ali_num'],
					number[1]['per_ali_num'],
					number[0]['per_ali_num']
				]
			}, {
				"name": "总数",
				"type": "line",
				"stack": "总量",
				symbolSize:10,
				symbol:'circle',
				"itemStyle": {
					"normal": {
						"color": "#8B90F4", // 折线色值
						"barBorderRadius": 0,
						"label": {
							"show": true,
							"position": "top",
							formatter: function(p) {
								return p.value > 0 ? (p.value) : '';
							}
						}
					}
				},
				"data": [
					number[11]['total_num'],
					number[10]['total_num'],
					number[9]['total_num'],
					number[8]['total_num'],
					number[7]['total_num'],
					number[6]['total_num'],
					number[5]['total_num'],
					number[4]['total_num'],
					number[3]['total_num'],
					number[2]['total_num'],
					number[1]['total_num'],
					number[0]['total_num']
				]
			},
			]
		}
		// 使用刚指定的配置项和数据显示图表。
		myChart.setOption(option);
	})
</script>
<!--银行流水-->
<script type="text/javascript">
	$(function () {
		var bank = eval('(' + '<?php echo ($bank); ?>' + ')');
		console.log(bank);
		// 基于准备好的dom，初始化echarts实例
		var myChart = echarts.init(document.getElementById('mer_today2'));
		//option
		var option = {
			tooltip : {
				trigger: 'item',
				formatter: "{a} <br/>{b} : {c} ({d}%)"
			},
			color:['#8fc31f','#f35833','#00ccff','#ffcc00','#36EAED'],
			series : [
				{
					name: '通道交易流水',
					type: 'pie',
					radius : '55%',
					center: ['45%', '65%'],
					data:[
//						{value:bank['wz_bank'], name:'微众'},
//						{value:bank['ms_bank'], name:'民生'},
						{value:bank['sz_bank'], name:'李灿'},
						{value:bank['wx_bank'], name:'围餐'},
//						{value:bank['zs_bank'], name:'招商'},
						{value:bank['xy_bank'], name:'兴业'},
						{value:bank['xdl_bank'], name:'新大陆'},
						{value:bank['ls_bank'], name:'乐刷'},
//						{value:bank['pa_bank'], name:'平安付'}
					],
					itemStyle: {
						emphasis: {
							shadowBlur: 10,
							shadowOffsetX: 0,
							shadowColor: 'rgba(0, 0, 0, 0.5)'
						}
					}
				}
			]
		};

		// 使用刚指定的配置项和数据显示图表。
		myChart.setOption(option);
	})
</script>
</html>