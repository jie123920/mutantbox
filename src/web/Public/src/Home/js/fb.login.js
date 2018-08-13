//FB INIT
var appid = "714278052039214";

window.fbAsyncInit = function () {
    FB.init({
        appId: appid,
        cookie: true,
        version: 'v2.4'
    });
};

(function (d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {
        return;
    }
    js = d.createElement(s);
    js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));


//FB LGOIN
var loginFB = function(loginurl, callback){
    FB.login(function (response) {
        if( response.authResponse ){
            $.post(loginurl, function (result) {
                callback(result);
            });
        }
    }, {scope: 'email'});

    return false;
};












/**************** Jquery扩展方法 - 获取URL参数 *************/
(function($){
    $.getUrlParam = function(name){
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var arg = window.location.search.substr(1).match(reg);
        if( arg === null ){
            return '';
        }else{
            return decodeURIComponent(arg[2]);
        }
    }
})(jQuery);
(function($){
    $.getDomain = function(page){
        var sdomain = window.location.host;
        var url = sdomain;

        if( 0 !== sdomain.indexOf("http") ){
            sdomain = "http://" + sdomain;
        }
        if( typeof("page") !== "undefined" ){
            if( 0 !== page.indexOf("/") ){
                page = "/" + page;
            }
            url = sdomain + page;
        }
        return url;
    }
})(jQuery);
