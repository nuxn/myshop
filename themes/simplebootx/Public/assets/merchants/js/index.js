$(function () {
    //地址选择
    window.storage = window.localStorage;
    var area1 = new LArea();
    area1.init({
        'trigger': '#address-demo1',
        'valueTo': '#address-value1',
        'keys': {
            id: 'id',
            name: 'name'
        },
        'type': 1, //数据源类型
        'data': LAreaData //数据源
    });

    area1.value = [1, 13, 3];
    //行业选择
    /*
     var area2 = new LArea2();
     area2.init({
     'trigger': '#hangye-demo1',
     'valueTo': '#hangye-value1',
     'keys': {
     id: 'value',
     name: 'text'
     },
     'type': 2, //数据源类型
     'data': [provs_data]
     });*/
    //账户类型
    var area3 = new LArea3();
    area3.init({
        'trigger': '#zhlx-demo1',
        'valueTo': '#zhlx-value1',
        'keys': {
            id: 'value',
            name: 'text'
        },
        'type': 2, //数据源类型
        'data': [zhanghu_data]
    });
    $("#submit").click(function () {
        var info = [];
        var certificate = $.trim($("#certificate").text());
        var idcard = $.trim($("#idcard").text());
        info.push({'data': $(".inputList1-m1 input[name='merchant_name']").val(), 'message': '商户名称不能为空!'});
        info.push({'data': $(".inputList1-m1 input[name='province']").val(), 'message': '请选择省市区!'});
        info.push({'data': $(".inputList1-m1 input[name='address']").val(), 'message': '请填写具体地址!'});
        info.push({'data': $(".inputList1-m1 input[name='industry']").val(), 'message': '请选择行业!'});
        info.push({'data': $(".inputList1-m1 input[name='operator_name']").val(), 'message': '请填写经营者姓名!'});
        info.push({'data': $(".inputList1-m1 input[name='id_number']").val(), 'message': '请填写身份证号码!'});
        info.push({'data': $(".inputList1-m1 input[name='account_type']").val(), 'message': '请选择账户类型!'});
        info.push({'data': $(".inputList1-m1 input[name='account_name']").val(), 'message': '请输入开户名称!'});
        info.push({'data': $(".inputList1-m1 input[name='bank_account']").val(), 'message': '请输入开户银行!'});
        info.push({'data': $(".inputList1-m1 input[name='branch_account']").val(), 'message': '请输入开户支行!'});
        info.push({'data': $(".inputList1-m1 input[name='bank_account_no']").val(), 'message': '请输入银行卡号!'});
        for (var i = 0, length = info.length; i < length; i++) {
            if (!message(info[i].data, info[i].message)) {
                return false;
            }
        }
        if (certificate != '已上传') {
            alert("请上传商户证件!");
            return false;
        }
        if (idcard != '已上传') {
            alert("请上传身份证!");
            return false;
        }
        //验证银行卡号
        if (!luhnCheck($(".inputList1-m1 input[name='bank_account_no']").val())) {
            alert("请检查银行卡号填写是否有误!");
            return false;
        }
        //验证身份证号码
        if (validateIdCard($(".inputList1-m1 input[name='id_number']").val()) !== 0) {
            alert("请检查身份证填写是否有误!");
            return false;
        }
        //验证手机号码(如果填写就验证)
        var referrer = $(".inputList1-m1 input[name='referrer']").val();
        if(referrer && !checkMobile(referrer)){
            alert("您输入的不是有效的手机号码!");
            return false;
        }

        $("#form").submit();

    })

    $("#ckid1").click(function () {
        var submit = $("#submit");
        if ($(this).is(":checked")) {
            submit.removeAttr("disabled");
            submit.css({"background-color": "#3071b9"});
        } else {
            submit.attr("disabled", "disabled");
            submit.css({"background-color": "#91b1d7"});
        }
    })

    //页面跳走后保存当前数据
    var key = ['merchant_name','account_name', 'province', 'address', 'industry', 'operator_name', 'id_number', 'account_type', 'account_name', 'bank_account', 'branch_account', 'bank_account_no','referrer'];

    for (var i = key.length - 1; i >= 0; i--) {
        $(".inputList1-m1 input[name='" + key[i] + "']").val(storage.getItem(key[i]));
    }

    $("input[type='text']").each(function () {
        $(this).blur(function () {
            var val = $(this).val();
            if (val) {
                var name = $(this).attr('name');
                storage.setItem(name, val);
            }
        })
    })

    $(".hangyeList1").on('click', 'li', function (e) {
        var li = e.currentTarget;
        var content = $(li).find('span').text();
        storage.setItem('industry', content);

    })

})

window.alert = function (message) {
    layer.open({
        content: message
        , skin: 'msg'
        , time: 2 //2秒后自动关闭
    });
}


function message(data, message) {
    if (!data) {
        alert(message);
        return false;
    }
    return true;
}

function checkMobile(phone){
    if(!(/^1[34578]\d{9}$/.test(phone))){
        return false;
    }
    return true;
}

//luhn校验规则：16位银行卡号（19位通用）:

// 1.将未带校验位的 15（或18）位卡号从右依次编号 1 到 15（18），位于奇数位号上的数字乘以 2。
// 2.将奇位乘积的个十位全部相加，再加上所有偶数位上的数字。
// 3.将加法和加上校验位能被 10 整除。

//bankno位银行卡号
function luhnCheck(bankno) {
    var lastNum = bankno.substr(bankno.length - 1, 1);//取出最后一位（与luhn进行比较）

    var first15Num = bankno.substr(0, bankno.length - 1);//前15或18位
    var newArr = new Array();
    for (var i = first15Num.length - 1; i > -1; i--) {    //前15或18位倒序存进数组
        newArr.push(first15Num.substr(i, 1));
    }
    var arrJiShu = new Array();  //奇数位*2的积 <9
    var arrJiShu2 = new Array(); //奇数位*2的积 >9

    var arrOuShu = new Array();  //偶数位数组
    for (var j = 0; j < newArr.length; j++) {
        ((j + 1) % 2 == 1) ? (parseInt(newArr[j]) * 2 < 9 ? arrJiShu.push(parseInt(newArr[j]) * 2) : arrJiShu2.push(parseInt(newArr[j]) * 2)) : arrOuShu.push(newArr[j]);
    }

    var jishu_child1 = new Array();//奇数位*2 >9 的分割之后的数组个位数
    var jishu_child2 = new Array();//奇数位*2 >9 的分割之后的数组十位数
    for (var h = 0; h < arrJiShu2.length; h++) {
        jishu_child1.push(parseInt(arrJiShu2[h]) % 10);
        jishu_child2.push(parseInt(arrJiShu2[h]) / 10);
    }

    var sumJiShu = 0; //奇数位*2 < 9 的数组之和
    var sumOuShu = 0; //偶数位数组之和
    var sumJiShuChild1 = 0; //奇数位*2 >9 的分割之后的数组个位数之和
    var sumJiShuChild2 = 0; //奇数位*2 >9 的分割之后的数组十位数之和
    var sumTotal = 0;
    for (var m = 0; m < arrJiShu.length; m++) {
        sumJiShu = sumJiShu + parseInt(arrJiShu[m]);
    }

    for (var n = 0; n < arrOuShu.length; n++) {
        sumOuShu = sumOuShu + parseInt(arrOuShu[n]);
    }

    for (var p = 0; p < jishu_child1.length; p++) {
        sumJiShuChild1 = sumJiShuChild1 + parseInt(jishu_child1[p]);
        sumJiShuChild2 = sumJiShuChild2 + parseInt(jishu_child2[p]);
    }
    //计算总和
    sumTotal = parseInt(sumJiShu) + parseInt(sumOuShu) + parseInt(sumJiShuChild1) + parseInt(sumJiShuChild2);

    //计算luhn值
    var k = parseInt(sumTotal) % 10 == 0 ? 10 : parseInt(sumTotal) % 10;
    var luhn = 10 - k;

    if (lastNum == luhn) {
        return true;
    }
    else {
        return false;
    }
}

/*
 功能：验证身份证号码是否有效
 提示信息：未输入或输入身份证号不正确！
 使用：validateIdCard(strIDno)
 返回：0,1,2,3
 */
function validateIdCard(strIDno) {
    var aCity = {
        11: "北京",
        12: "天津",
        13: "河北",
        14: "山西",
        15: "内蒙古",
        21: "辽宁",
        22: "吉林",
        23: "黑龙江",
        31: "上海",
        32: "江苏",
        33: "浙江",
        34: "安徽",
        35: "福建",
        36: "江西",
        37: "山东",
        41: "河南",
        42: "湖北",
        43: "湖南",
        44: "广东",
        45: "广西",
        46: "海南",
        50: "重庆",
        51: "四川",
        52: "贵州",
        53: "云南",
        54: "西藏",
        61: "陕西",
        62: "甘肃",
        63: "青海",
        64: "宁夏",
        65: "新疆",
        71: "台湾",
        81: "香港",
        82: "澳门",
        91: "国外"
    };
    var iSum = 0;
    var idCardLength = strIDno.length;
    if (!/^\d{17}(\d|x)$/i.test(strIDno) && !/^\d{15}$/i.test(strIDno))
        return 1; // 非法身份证号

    if (aCity[parseInt(strIDno.substr(0, 2))] == null)
        return 2;// 非法地区

    // 15位身份证转换为18位
    if (idCardLength == 15) {
        sBirthday = "19" + strIDno.substr(6, 2) + "-"
            + Number(strIDno.substr(8, 2)) + "-"
            + Number(strIDno.substr(10, 2));
        var d = new Date(sBirthday.replace(/-/g, "/"))
        var dd = d.getFullYear().toString() + "-" + (d.getMonth() + 1) + "-"
            + d.getDate();
        if (sBirthday != dd)
            return 3; // 非法生日
        strIDno = strIDno.substring(0, 6) + "19" + strIDno.substring(6, 15);
        strIDno = strIDno + GetVerifyBit(strIDno);
    }

    // 判断是否大于2078年，小于1900年
    var year = strIDno.substring(6, 10);
    if (year < 1900 || year > 2078)
        return 3;// 非法生日

    // 18位身份证处理

    // 在后面的运算中x相当于数字10,所以转换成a
    strIDno = strIDno.replace(/x$/i, "a");

    sBirthday = strIDno.substr(6, 4) + "-" + Number(strIDno.substr(10, 2))
        + "-" + Number(strIDno.substr(12, 2));
    var d = new Date(sBirthday.replace(/-/g, "/"))
    if (sBirthday != (d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d
            .getDate()))
        return 3; // 非法生日
    // 身份证编码规范验证
    for (var i = 17; i >= 0; i--)
        iSum += (Math.pow(2, i) % 11) * parseInt(strIDno.charAt(17 - i), 11);
    if (iSum % 11 != 1)
        return 1;// 非法身份证号

    // 判断是否屏蔽身份证
    var words = new Array();
    words = new Array("11111119111111111", "12121219121212121");

    for (var k = 0; k < words.length; k++) {
        if (strIDno.indexOf(words[k]) != -1) {
            return 1;
        }
    }
    return 0;
}
