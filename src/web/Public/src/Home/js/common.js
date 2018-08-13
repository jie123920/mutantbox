function pc() {
    var bodyW = $(window).width();
    $('.nav li.item').hover(function () {
        // var left02 = $(this).position().left;
        // $(this).find('.bd ul').css({marginLeft: left02});
        $(this).parents('.nav').find('li').removeClass('on');
        $(this).addClass('on');
    }, function () {
        $(this).parents('.nav').find('li').removeClass('on');
    });
    $('.lang').hover(function () {
        $(this).addClass('on');
    }, function () {
        $(this).removeClass('on');
    })

    $('.mobile_nav_icon').click(function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            $('.header_box').removeClass('on');
        } else {
            $(this).addClass('on');
            $('.header_box').addClass('on');
        }
    });
}

pc();

function dialog(obj, url, shadeClose, type) {

    if (shadeClose != false) {
        shadeClose = true;
    }
    layer.closeAll();
    p = parseInt($(".layer").css("paddingLeft")) * 2;
    w = $(".layer").width() + p + "px";

    $(".layer .dialog_box").hide();
    $('.layer ' + obj).show();

    layer.open({
        type: 1,
        title: false,
        closeBtn: false,
        shade: 0.7,
        shadeClose: shadeClose,
        area: w,
        content: $(".layer"),
        end: function (index) {
            if (type == 1 && $.cookie("user_auth_sign")) {
                location.href = url;
            }
            else if (type == 2 && $.cookie("user_auth_sign")) {
                location.href = url;
            }

        }
    });
}

/*新增 zh start*/
function showDialog(obj) {
    layer.closeAll();
    p = parseInt($(".layer").css("paddingLeft") * 2);
    w = $(".layer").width() + p + "px";
    console.log(p);
    console.log(w);

    $(".layer .dialog_box").hide();
    $('.layer ' + obj).show();

    layer.open({
        type: 1,
        title: false,
        closeBtn: false,
        shade: 0.7,
        shadeClose: true,
        area: w,
        content: $(".layer")
    });
}

/*新增 zh end*/
function w_fixed() {
    var bodyw = $(window).width();
    if (bodyw > 960) {
        $('.header_con').addClass('fixed');
    } else {
        $('.header_con').removeClass('fixed');
    }

    $('.click_user').click(function () {
        showDialog(".login");
    });
}


w_fixed();
$(window).resize(function () {
    w_fixed();
});

$(".guanbi").on("click", function () {
    layer.closeAll();
})

/*新增 zh start*/
function layer_alert(msg, error_type, url, time) {
    if (!time) {
        time = 2000;
    }

    layer.closeAll();
    layer.alert(msg, {
        title: false,
        btn: "",
        shadeClose: true,
        shade: [0.7, "#000"],
        offset: "auto",
        time: time,
        end: function (index) {
            if (error_type == 1) {
                location.href = url;
            }
            else if (error_type == 2) {
                history.go(-1);
            }
            else if (error_type == 3) {
                location.reload();
            }
        }
    });
}


//$(".header_login :input").on("blur", function () {
//    $(".Tsign_box").click(function (event) {
//        event.stopPropagation();
//    });
//    $(".Tsign_box").parents().click(function (e) {
//        $(".btn_logT").mouseleave();
//    });
//});

//$(".header_login :input").on("blur", function () {
//    $(".login_user,.header_login_box").off("click mouseleave");
//    $(".login_user,.header_login_box").click(function () {
//        $(".login_user > span").removeClass("cur");
//        $(".login_user").removeClass("bg_cur");
//        $(".header_login_box").hide().removeClass("css3_fadeIn");
//    });
//});
//
//
//$(".header_login :input").on("focus", function () {
//    $(".login_user,.header_login_box").off("mouseleave");
//});


$(".login_user,.header_login_box").on("mouseenter", function () {
    $(".reg_user > span").removeClass("cur");
    $(".reg_user").removeClass("bg_cur");
    $(".header_register_box").hide().removeClass("css3_fadeIn");
    $(".login_user > span").addClass("cur");
    $(".login_user").addClass("bg_cur");
    $(".header_login_box").show().addClass("css3_fadeIn");
});

$(".login_user,.header_login_box").on("mouseleave", function () {
    if ($('#login_email_input').is(':focus')) {
        return false;
    }
    if ($('#login_pass_input').is(':focus')) {
        return false;
    }
    $(".login_user > span").removeClass("cur");
    $(".login_user").removeClass("bg_cur");
    $(".header_login_box").hide().removeClass("css3_fadeIn");
});

$(".reg_user,.header_register_box").on("mouseenter", function () {
    $(".header_login_box").hide().removeClass("css3_fadeIn");
    $(".login_user > span").removeClass("cur");
    $(".login_user").removeClass("bg_cur");
    $(".reg_user > span").addClass("cur");
    $(".reg_user").addClass("bg_cur");
    $(".header_register_box").show().addClass("css3_fadeIn");
});

$(".reg_user,.header_login_box").on("mouseleave", function () {
    if ($('#reg_email_input').is(':focus')) {
        return false;
    }
    if ($('#reg_pass_input').is(':focus')) {
        return false;
    }
    $(".reg_user > span").removeClass("cur");
    $(".reg_user").removeClass("bg_cur");
    $(".header_register_box").hide().removeClass("css3_fadeIn");
});

/*会员中心头像编辑*/
$(".personal_data .img").bind("mouseenter", function () {
    $(this).find("a").slideDown();
})

$(".personal_data .img").bind("mouseleave", function () {
    $(this).find("a").slideUp();
})
$("#btnChoose").bind("click", function () {
    $('#portrait_file').click();
})

$(".log_share_lang .item02").bind("mouseenter", function () {

    $(this).find(".header_msg_box").show().addClass("css3_fadeIn");
})

$(".log_share_lang .item02").bind("mouseleave", function () {
    $(this).find(".header_msg_box").hide().removeClass("css3_fadeIn");
})

function show_portrait() {
    layer.closeAll();
    $("#picture_100 img,#picture_50 img").attr("default", 0);
    var cfile = $("#portrait_file").clone();  //复制当前file  
    $("#portrait_file").remove();     //移除当前file  
    $("#avater_upload div.box").prepend(cfile);

    w_p = parseInt($(".avater_box").css("paddingLeft")) * 2;
    w = $(".avater_box").width() + w_p + "px";

    layer.open({
        type: 1,
        title: false,
        closeBtn: false,
        shade: 0.7,
        shadeClose: true,
        area: w,
        content: $(".avater_box"),
        end: function () {
            $(".bring_face").show();
            $("#avater_upload").show();
            $('#div_avatar').hide();
            location.reload();
        }
    });
}

$(".personal_data .img a").bind("click", function () {
    show_portrait();
});

$("#avater_upload").bind("change", "#portrait_file", function () {
    $("#picture_100 img,#picture_50 img").attr("default", 0);
    var allowImgageType = ['jpg', 'jpeg', 'png', 'gif'];
    var file = $("#portrait_file").val();
    //获取大小
    var byteSize = getFileSize('portrait_file');
    //获取后缀
    if (file.length > 0) {
        if (byteSize > 2048) {
            alert("上传的附件文件不能超过2M");
            return;
        }
        var pos = file.lastIndexOf(".");
        //截取点之后的字符串
        var ext = file.substring(pos + 1).toLowerCase();
        //console.log(ext);
        if ($.inArray(ext, allowImgageType) != -1) {

            ajaxFileUpload();
        } else {
            alert("请选择jpg,jpeg,png,gif类型的图片");
        }
    }
    else {
        alert("请选择jpg,jpeg,png,gif类型的图片");
    }
});

function ajaxFileUpload() {
    $.ajaxFileUpload({
        url: upload_portra_url, //用于文件上传的服务器端请求地址
        secureuri: false, //一般设置为false
        fileElementId: 'portrait_file', //文件上传空间的id属性  <input type="file" id="file" name="file" />
        dataType: 'json', //返回值类型 一般设置为json
        success: function (data, status)  //服务器成功响应处理函数
        {
            $("#avatar_box>img").attr({src: data.src, width: data.width, height: data.height});
            $('#imgsrc').val(data.path);
            //同时启动裁剪操作，触发裁剪框显示，让用户选择图片区域
            var cutter = new jQuery.UtrialAvatarCutter({
                    //主图片所在容器ID
                    content: "avatar_box",
                    //缩略图配置,ID:所在容器ID;width,height:缩略图大小
                    purviews: [{id: "picture_100", width: 100, height: 100}, {id: "picture_50", width: 50, height: 50}],
                    //选择器默认大小
                    selector: {width: 200, height: 200},
                    showCoords: function (c) { //当裁剪框变动时，将左上角相对图片的X坐标与Y坐标 宽度以及高度
                        $("#x1").val(c.x);
                        $("#y1").val(c.y);
                        $("#cw").val(c.w);
                        $("#ch").val(c.h);
                    },
                    cropattrs: {boxWidth: 430, boxHeight: 430}
                }
            );
            cutter.reload(data.src);
            $(".bring_face").hide();
            $("#avater_upload").hide();
            $('#div_avatar').show();
        },
        error: function (data, status, e)//服务器响应失败处理函数
        {
            alert(e);
        }
    });
    return false;
}


$('#btnCrop').bind("click", function () {
    var loading = layer.msg('Loading', {icon: 16, time: 0, shade: [0.3, '#000']});
    $.post(crop_portra_url, {
        x: $('#x1').val(),
        y: $('#y1').val(),
        w: $('#cw').val(),
        h: $('#ch').val(),
        src: $('#imgsrc').val()
    }, function (data) {
        layer.close(loading);
        layer.alert(data.msg, {
            title: false,
            btn: "",
            shadeClose: true,
            shade: [0.7, "#000"],
            offset: "auto",
            time: 2000,
            end: function () {
                $(".bring_face").show();
                $("#avater_upload").show();
                $('#div_avatar').hide();
                if (data.result == 0) {
                    location.reload();
                }
            }
        });
    });
    return false;
});

function getFileSize(fileName) {
    var byteSize = 0;
    //console.log($("#" + fileName).val());
    if ($("#" + fileName)[0].files) {
        var byteSize = $("#" + fileName)[0].files[0].size;
    }
    byteSize = Math.ceil(byteSize / 1024) //KB
    return byteSize;//KB
}

$(".titInfo li img").bind("click", function () {
    $("#picture_100 img,#picture_50 img").attr({"src": $(this).attr("src"), "default": 1});
});
$("#default_avater_save").bind("click", function () {
    if ($("#picture_100 img").attr("default") == 1) {
        $.post(save_portra_url, {src: $("#picture_100 img").attr("src")}, function (data) {

            layer.alert(data.msg, {
                title: false,
                btn: "",
                shadeClose: true,
                shade: [0.7, "#000"],
                offset: "auto",
                time: 2000,
                end: function () {
                    $(".bring_face").show();
                    $("#avater_upload").show();
                    $('#div_avatar').hide();
                    if (data.result == 0) {
                        location.reload();
                    }
                }
            });
        })
    }
});