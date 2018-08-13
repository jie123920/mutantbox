//Google Login
var start = function () {
    gapi.load('auth2', function () {
        auth2 = gapi.auth2.init({
            client_id: '455615275123-gnkurafbvjr751fioggsujmrli55ltu8.apps.googleusercontent.com'
        })
    });
};

var loginGoogle = function (callback){
    auth2.grantOfflineAccess({'redirect_uri': 'postmessage'}).then(callback);
};

var bindGoogle = function (callback) {
    auth2.grantOfflineAccess({'redirect_uri': 'postmessage'}).then(callback);
};







/***
 * GOOGLE Adwords code
 * Default success
 */
var adwords_ad = function(callback, params){
    if( "object" != typeof(params) ){
        params = {};
    }
    var id = params.id || "940547953";
    var label = params.label || "vvF-CK3EqmEQ8b6-wAM";
    var tag = params.tag || "";

    var google_conversion_id = id;
    var google_conversion_language = "en";
    var google_conversion_format = "3";
    var google_conversion_color = "ffffff";
    var google_conversion_label = label;		//vvF-CK3EqmEQ8b6-wAM 成功lable	Nwn7CIPo0GAQ8b6-wAM 浏览
    var google_remarketing_only = false;

    var js = document.createElement("script");
    js.src = "//www.googleadservices.com/pagead/conversion.js";
    document.body.appendChild(js);
    var imgpic = document.createElement("img");
    imgpic.width = 1;
    imgpic.height = 1;
    imgpic.style.border = 0;
    imgpic.src = "//www.googleadservices.com/pagead/conversion/" + id + "/?label=" + label + "&guid=ON&script=0";

    console.log("Google adwords execute successfully." + id + label);

    if( "function" === typeof(callback) ){
        setTimeout(function(){
            callback();
        }, 2000);
    }
};
