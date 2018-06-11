$(function () {
    upload_img($("#image0"), 0);
    upload_img($("#image1"), 1);
    $(".selectbtn").addClass("file_upload").show();
    $(".selectbtn").attr("capture","camera")

})

window.alert = function (message) {
    layer.open({
        content: message
        , skin: 'msg'
        , time: 2 //2秒后自动关闭
    });
}

function checkInfo() {
    var str0 = $("#imageID0").val();
    var str1 = $("#imageID1").val();
    if (!str0 || !str1) {
        alert("请上传身份证");
        return false;
    }
    $("#imageIDStr").val(str0 + ',' + str1);
    return true;
}

function upload_img(obj, i) {
    $(obj).Huploadify({
        auto: true,
        multi: false,
        fileTypeExts: '*.JPG;*.PNG;*.jpg;*.png;*.jpeg;*.JPEG',
        formData: {key: 123456, key2: 'vvvv'},
        fileSizeLimit: 10240,
        showUploadedPercent: false,//是否实时显示上传的百分比，如20%
        showUploadedSize: false,
        removeTimeout: 9999999,
        buttonText: '',
        uploader: idcardURL,
        onUploadStart: function () {
            //loading层
            layer.open({type: 2});
        },
        onInit: function () {
            //alert('初始化');
        },
        onUploadComplete: function (data) {
//                    console.log(data);
        },
        onUploadSuccess: function (file, data, response) {
            layer.closeAll();
            var returnData = $.parseJSON(data);
            if (returnData.status == 1) {
                $("#imageID" + i).val(returnData.message);
                preview(i);
            } else {
                alert(returnData.message);
            }
        },
    });
}

function preview(i) {
    var input = $('#image' + i).find("input[type='file']");
    var objUrl = getObjectURL(input.prop('files')[0]);
    if (objUrl) {
        var Oimg = $('#image' + i).parent()
        Oimg.find('>img:eq(0)').attr("src", objUrl);
//            Oimg.find('>img:eq(1)').hide();
        Oimg.find('>img:eq(2)').hide();
    }
}

//建立一個可存取到該file的url
function getObjectURL(file) {
    var url = null;
    if (window.createObjectURL != undefined) { // basic
        url = window.createObjectURL(file);
    } else if (window.URL != undefined) { // mozilla(firefox)
        url = window.URL.createObjectURL(file);
    } else if (window.webkitURL != undefined) { // webkit or chrome
        url = window.webkitURL.createObjectURL(file);
    }
    return url;
}