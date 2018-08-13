$.extend( $.validator.messages, {
    required: "Acest domeniu este necesar.",
    maxlength: $.validator.format( "Vă rugăm să nu introduceți mai mult de {0} caractere." ),
    minlength: $.validator.format( "Vă rugăm să introduceți cel puțin {0} caractere." ),
    rangelength: $.validator.format( "Vă rugăm să introduceți o valoare lungă între {0} și {1} caractere." ),
    email: "Vă rugăm să introduceți o adresă de e-mail validă.",
    url: "Vă rugăm să introduceți un URL valid.",
    date: "Vă rugăm să introduceți o dată validă.",
    number: "Vă rugăm să introduceți un număr valid.",
    digits: "Vă rugăm să introduceţi numai cifre.",
    equalTo: "Vă rugăm să introduceți aceeași valoare din nou.",
    range: $.validator.format( "Vă rugăm să introduceți o valoare între {0} și {1}." ),
    max: $.validator.format( "Vă rugăm să introduceți o valoare mai mică decât sau egală cu {0}." ),
    min: $.validator.format( "Vă rugăm să introduceți o valoare mai mare decât sau egală cu {0}." ),
    creditcard: "Vă rugăm să introduceţi un număr de card de credit valabil."
} );