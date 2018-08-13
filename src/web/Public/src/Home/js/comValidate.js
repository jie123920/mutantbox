$(function () {
    $(".getPwd_form").validate({
        rules: {
            email: {
                required: true,
                email: true
            }
        }
    });

    $(".repwd_form").validate({
        rules: {
            password: {
                required: true,
                rangelength: [6, 20]
            },
            repassword: {
                equalTo: "#password"
            }
        }
    });

    $(".reg_form").validate({
        rules: {
            email: {
                required: true,
                email: true,
                remote: {
                    url: "{:U('User/ajaxCheckUser')}",
                    type: "post",
                    dataType: "json",
                    data: {
                        username: function () {
                            return $("#reg_email").val();
                        }
                    }
                }
            },
            password: {
                required: true,
                rangelength: [6, 20]
            },
            repassword: {
                equalTo: "#password"
            }
        },
        messages: {
            email: {
                remote: "the email have been exist"
            }
        }
    });
})
