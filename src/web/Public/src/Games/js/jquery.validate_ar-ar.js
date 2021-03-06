$.extend( $.validator.messages, {
    required: "هذا الحقل مطلوب.",
    maxlength: $.validator.format( "الرجاء عدم إدخال أكثر من {0} حرفاً." ),
    minlength: $.validator.format( "الرجاء إدخال على الأقل {0} حرفاً." ),
    rangelength: $.validator.format( "الرجاء إدخال قيمة ما بين {0} و {1} من الأحرف." ),
    email: "الرجاء إدخال عنوان بريد إلكتروني صحيح.",
    url: "الرجاء إدخال عنوان رابط صحيح.",
    date: "الرجاء إدخال تاريخ صحيح.",
    number: "الرجاء إدخال رقم صحيح.",
    digits: "الرجاء إدخال أرقام فقط.",
    equalTo: "الرجاء إدخال القيمة نفسها مرة أخرى.",
    range: $.validator.format( "الرجاء إدخال قيمة بين {0} و {1}." ),
    max: $.validator.format( "الرجاء إدخال قيمة أقل من أو تساوي {0}." ),
    min: $.validator.format( "الرجاء إدخال قيمة أكبر من أو تساوي {0}." ),
    creditcard: "الرجاء إدخال رقم بطاقة ائتمان صالحة."
} );