$.extend( $.validator.messages, {
    required: "Questo campo è obbligatorio.",
    maxlength: $.validator.format( "Per favore non inserire più di.{0} caratteri" ),
    minlength: $.validator.format( "Per favore inserire almento {0} caratteri." ),
    rangelength: $.validator.format( "Per favore inserire un valore compreso tra {0} e {1} caratteri." ),
    email: "Per favore inserire un indirizzo email valido.",
    url: "Per favore inserire un URL valido.",
    date: "Per favore inserire una data valida.",
    number: "Per favore inserire un numero valido.",
    digits: "Per favore inserire solo cifre.",
    equalTo: "Per favore inserire nuovamente lo stesso valore.",
    range: $.validator.format( "Per favore inserire un valore compreso tra {0} e {1}." ),
    max: $.validator.format( "Per favore inserire un valore inferiore o uguale a {0}." ),
    min: $.validator.format( "Per favore inserire un valore maggiore di o uguale a {0}." ),
    creditcard: "Per favore inserire un numero di carta di credito valido"
} );