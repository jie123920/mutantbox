/*
* Payment
* */
var pay = function(url){
    if( "undefined" === typeof(url) ){
        return false;
    }

    layer.open({
        type: 2,
        title: ['Payment', 'font-size:24px;text-align:left;'],
        shadeClose: false,
        area: ['780px', '650px'],
        shade: 0.8,
        content: url
    });
};