//web site register
var register_ad = function(registerurl, data, callback){
    if( "object" !== typeof(data) ){
        alert("The parameter is wrong");
        return false;
    }

    $.post(registerurl, data, function (result) {
        callback(result);
    }, 'json');
};
