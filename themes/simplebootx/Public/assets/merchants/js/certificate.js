$(function () {
    for (var i = 0; i < 6; i++) {
        upload_img($("#image" + i),i);
    }
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

    var check = $("input[name='radio1']:checked").val();
    if(check == 1){
        var str0 = $("#uImg0").val();
        var str1 = $("#uImg1").val();
        if (!str0 || !str1) {
            alert("请上传文件");
            return false;
        }
        $("#header_interior_img").val(str0);
        $("#business_license").val(str1);
        return true;
    }else{
        var str0 = $("#uImg2").val();
        var str1 = $("#uImg3").val();
        var str2 = $("#uImg4").val();
        var str3 = $("#uImg5").val();
        if(!str0 || !str1 || !str2 || !str3){
            alert("请上传文件");
            return false;
        }
        $("#header_interior_img").val(str0 + ',' + str1 + ',' + str2);
        $("#business_license").val(str3);
        return true;
    }
}

function upload_img(obj, i) {
    $(obj).Huploadify({
        auto: true,
        multi: false,
        fileTypeExts: '*.JPG;*.PNG;*.jpg;*.png;*.JPEG;*.jpeg',
        formData: {key: 123456, key2: 'vvvv'},
        fileSizeLimit: 10240,
        showUploadedPercent: false,//是否实时显示上传的百分比，如20%
        showUploadedSize: false,
        removeTimeout: 9999999,
        buttonText: '',
        uploader: certificateURL,
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
                $("#uImg" + i).val(returnData.message);
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
