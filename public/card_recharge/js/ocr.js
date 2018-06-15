/*!
 * 2017/12/5
 *身份证,银行卡识别OCR
 * by joan
 */

document.write("<script language=javascript src='../js/js/exif.js'></script>");

$(function () {
    // var UserAgent = checkUserAgent();
    // if (UserAgent == 2)AlipayJSBridge.call('hideOptionMenu');
    // else if (UserAgent == 1) {
    //     document.write("<script language=javascript src='https://res.wx.qq.com/open/js/jweixin-1.0.0.js'></script>");
    //     if (typeof WeixinJSBridge == "undefined") {
    //         if (document.addEventListener) {
    //             document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
    //         } else if (document.attachEvent) {
    //             document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
    //             document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
    //         }
    //     } else {
    //         onBridgeReady();
    //     }
    // }
});

$(".bank_no,.id_card").hide();
$(".camera2").click(function () {
    $('.bank_no').click();
});
$(".camera1").click(function () {
    $('.id_card').click();
});

$('.xuinyong input.neiputs,.xuinyong input.neiput').css({'width': 'calc(55%)'});
function base64_uploading(base64Data, type) {
    var common = 'https://qr.youngport.com.cn/c1/ocr/',
        shade = layer.open({type: 2}),
        url = type == 'i' ? common + 'get_idcard' : common + 'get_bankcard';
    $.ajax({
        type: 'POST',
        url: url,
        data: {
            'img': base64Data
        },
        dataType: 'json',
        timeout: 50000,
        success: function (data) {
            //alert(JSON.stringify(data));
            layer.close(shade);
            if (data.code == '0') {
                if (type == 'b')$(".bank_cardNo").val(data.data.bank_card_number);
                else {
                    $(".user_idcardNo").val(data.data.card_no);
                    $(".user_name").val(data.data.name);
                }
            } else
                layer.open({
                    content: '识别失败,请重试或手动输入!'
                    , btn: '确定'
                });

        },
        complete: function () {
        },
        error: function () {
            layer.open({
                content: '识别超时,请重试或手动输入!'
                , btn: '确定'
            });
        }
    });
}

function ocr(e, t) {
    var file = e.files[0];
    //用size属性判断文件大小不能超过5M,前端直接判断的好处,免去服务器的压力。
    if (file.size > 5 * 1024 * 1024) {
        layer.open({
            content: '上传的图片不能超过5M!'
            , btn: '确定'
        });
    }

    var orientation = 0;
    if (file && /^image\//i.test(file.type)) {
        EXIF.getData(file, function () {
            orientation = EXIF.getTag(file, 'Orientation');
        });

        var reader = new FileReader();
        reader.onload = function () {
            //通过reader.result来访问生成的base64DataURL
            var base64 = reader.result;
            //上传图片
            base64_uploading(base64, t);
        };
        reader.readAsDataURL(file);
    } else {
        layer.open({
            content: '只能识别图片!'
            , btn: '确定'
        });

    }
}

function onBridgeReady() {
    WeixinJSBridge.call('hideOptionMenu');
}

function checkUserAgent() {
    var userAgent = navigator.userAgent.toLowerCase();
    if (userAgent.match(/MicroMessenger/i) == "micromessenger") {
        return 1;
    } else if (userAgent.match(/Alipay/i) == "alipay") {
        return 2;
    } else  return 0;
}
