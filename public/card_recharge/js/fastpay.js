window.addEventListener("load",
    function() {
        FastClick.attach(document.body);
    },false);
$(function() {
    $('#send').click(function() {
        var merchantNo = $("#merchantNo").val();
        var orderNum = $("#orderNum").val();
        var phone = $("#phone").val();
        if (phone == null || phone == '') {
            alert("鎵嬫満鍙蜂笉鑳戒负绌�");
            return;
        }
        $('#send').attr("disabled", true);
        var jsondata = "{\"merchantNo\":\"" + merchantNo + "\",\"orderNum\":\"" + orderNum + "\",\"phone\":\"" + phone + "\"}";
        var url = "http://portal.ronghuijinfubj.com/middlepayportal/merchant/insideOpenCardSMS";
        $.ajax({
            type: "POST",
            url: url,
            dataType: "JSON",
            data: jsondata,
            success: function(msg){
                OnGetDataSuccessByjsonpCode(msg);
                sendCode('send');
            },
            error: function(XMLHttpRequest, textStatus, data) {
                $('#send').prop("disabled", false);
                alert("璇锋眰澶辫触锛岃绋嶅悗閲嶈瘯");
            }
        });
    });
    $('#open').click(function() {
        var merchantNo = $("#merchantNo").val();
        var orderNum = $("#orderNum").val();
        var phone = $("#phone").val();
        var smsCode = $("#smsCode").val();
        var expired = $("#expired").val();
        var cvn2 = $("#cvn2").val();
        if (phone == null || phone == '') {
            alert("鎵嬫満鍙蜂笉鑳戒负绌�");
            return;
        }
        if (smsCode == null || smsCode == '') {
            alert("鐭俊楠岃瘉鐮佷笉鑳戒负绌�");
            return;
        }
        $('#open').attr("disabled", true);
        var jsondata = "{\"merchantNo\":\"" + merchantNo + "\",\"orderNum\":\"" + orderNum + "\",\"phone\":\"" + phone + "\",\"smsCode\":\"" + smsCode + "\",\"expired\":\"" + expired + "\",\"cvn2\":\"" + cvn2 + "\"}";
        var url = "http://portal.ronghuijinfubj.com/middlepayportal/merchant/insideBackOpenCard";
        $.ajax({
            type: "POST",
            url: url,
            dataType: "JSON",
            data: jsondata,
            success: function(msg){
                OnGetDataSuccessByjsonp(msg);
            },
            error: function(XMLHttpRequest, textStatus, data) {
                $('#open').attr("disabled", false);
                alert("璇锋眰澶辫触锛岃绋嶅悗閲嶈瘯");
            }
        });
    })

});
function OnGetDataSuccessByjsonp(data) {
    var objResp = eval('(' + data + ')');
    $('#open').attr("disabled", false);
    if (objResp.respCode == "0000" || "1000" == objResp.respCode) {
        alert(objResp.respMsg);
        var getval =document.getElementById("orderNum").value;
        succeed(getval);
    } else {
        if (null == objResp.respMsg) {
            alert(objResp.respMsg);
        } else {
            alert(objResp.respMsg);
        }
    }
};
function OnGetDataSuccessByjsonpCode(data) {
    var objResp = eval('(' + data + ')');
    $('#open').attr("disabled", false);
    if (objResp.respCode == "0000" || "1000" == objResp.respCode) {
        alert(objResp.respMsg);
    } else {
        if (null == objResp.respMsg) {
            alert(objResp.respMsg);
        } else {
            alert(objResp.respMsg);
        }
    }
};
var clock = '';
var nums = 30;
function sendCode(jQuery){
    $('#'+jQuery).attr('disabled', 'true');
    $('#'+jQuery).val(nums+'绉�')
    clock = setInterval(function(){doLoop(jQuery);}, 1000);
}
function doLoop(jQuery){
    nums--;
    if(nums > 0){
        $('#'+jQuery).val(nums+'绉�')
    }else{
        clearInterval(clock);

        $('#'+jQuery).css('disabled','false');
        $('#'+jQuery).removeAttr('disabled')
        $('#'+jQuery).val('鑾峰彇楠岃瘉鐮�');
        nums = 30;
    }
}
function succeed(str){
    window.location.href="http://trx.ronghuijinfubj.com/pay/succeed.html?orderNum="+str;
    // window.location.href="succeed.html?orderNum="+str;    }
}
// function go(){
//     var getval =document.getElementById("orderNum").value;
//     localStorage.setItem("orderNum",getval) ;//瀛樺偍鍚嶅瓧涓簄ame鍊间负caibin鐨勫彉閲�
// }