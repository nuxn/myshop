$(document).ready(function($) {
  //----------------------------
  asmonload();//初始化执行函数
  $(window).resize(function() {asmresize();}); //浏览器动态放大缩小
  $(window).bind("scroll", function(event){asmscroll();});//浏览器滚动监听函数
   //------------------------------
    function asmonload(){
      //pdappear();//是否出现在可视区域内
    }//初始化监听
    function asmresize(){
      //pdappear();//是否出现在可视区域内 
    }//缩放监听
    function asmscroll(){
      //pdappear();//是否出现在可视区域内
    }//滚动监听
  //-----------------------------
function widthresize(){
  var jqwd;
    jqwd=$(window).width();
    if(jqwd>767){
    }
}//widthresize end


//rem框架--手机
tryrem();
function tryrem(){
  try{
  window['adaptive'].desinWidth = 640; //设计页面宽度
  window['adaptive'].maxWidth = 640;// 页面最大宽度 默认540
  window['adaptive'].init();
  }catch(err){}
}

//多选
$(".pagem11 .tt").click(function() {
   if($(this).find("input[type='checkbox']").is(":checked")){
        $(this).addClass("dc-cked1");
      }//如果被选中  
      else{
        $(this).removeClass("dc-cked1");
      }
  });

//单选按钮
$(".zj-inputList2-m1").click(function() {
  radiockfn();
});
function radiockfn(){
  $(".zj-inputList2-m1").removeClass("rdchcked");  
  $(".zj-radioList1 input[type='radio']").each(function(){
      if($(this).is(":checked")){
        $(this).closest(".zj-inputList2-m1").addClass("rdchcked");
      }//如果被选中  
  });
}//radiockfn end

  // 选项卡 鼠标点击
$(".TAB_CLICK li").click(function(){
  var tab=$(this).parent(".TAB_CLICK");
  var con=tab.attr("id");
  var on=tab.find("li").index(this);
  $(this).addClass('hover').siblings(tab.find("li")).removeClass('hover');
  $(con).eq(on).show().siblings(con).hide();
});

//点击上传弹出方式
$(".up-open1").click(function(){
  $(".upway1").addClass("dc-bt1");
});

$(".up-close1").click(function(){
  $(".upway1").removeClass("dc-bt1");
});

//行业类名
$(".up-open1").click(function(){
  $(".hangye-m1").addClass("dc-bt1");
});

$(".hangyeList1-m1 span").click(function(){
  var getval=$(this).html();
  $(".hytext").val(getval);
  $(".hangye-m1").removeClass("dc-bt1");
});

});