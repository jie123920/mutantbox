$.extend( $.validator.messages, {
    required: "Bu alan gereklidir.",
    maxlength: $.validator.format( "Lütfen {0} karakterden az girin." ),
    minlength: $.validator.format( "Lütfen en az {0} karakter girin." ),
    rangelength: $.validator.format( "Lütfen {0} ile {1} arası karakter uzunluğunda bir değer girin." ),
    email: "Lütfen geçerli bir e-posta adresi girin.",
    url: "Lütfen geçerli bir URL girin.",
    date: "Lütfen geçerli bir tarih girin.",
    number: "Lütfen geçerli bir sayı girin.",
    digits: "Lütfen yalnızca rakam girin.",
    equalTo: "Lütfen aynı değeri yeniden girin.",
    range: $.validator.format( "Lütfen {0} ile {1} arasında bir değer girin." ),
    max: $.validator.format( "Lütfen {0} den daha az veya eşit bir değer girin." ),
    min: $.validator.format( "Lütfen {0} den daha büyük veya eşit bir değer girin." ),
    creditcard: "Lütfen geçerli bir kredi kartı numarası girin."
} );