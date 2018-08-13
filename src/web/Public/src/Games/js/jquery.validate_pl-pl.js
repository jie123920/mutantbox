$.extend( $.validator.messages, {
    required: "To pole jest wymagane.",
    maxlength: $.validator.format( "Proszę wpisać nie więcej niż {0} znaków." ),
    minlength: $.validator.format( "Podaj przynajmniej {0} znaków." ),
    rangelength: $.validator.format( "Proszę wprowadź wartość między {0} i {1} długością znaków." ),
    email: "Proszę wprowadzić poprawny adres e-mail.",
    url: "Proszę wprowadzić poprawny adres URL.",
    date: "Proszę wprowadzić poprawną datę.",
    number: "Proszę wprowadzić poprawny numer.",
    digits: "Proszę wpisać tylko cyfry.",
    equalTo: "Proszę wprowadzić ponownie tę samą wartość.",
    range: $.validator.format( "Proszę wprowadź wartość między {0} i {1}." ),
    max: $.validator.format( "Wpisz wartość mniejszą niż lub równą {0}." ),
    min: $.validator.format( "Wpisz wartość większa niż lub równą {0}." ),
    creditcard: "Proszę wprowadzić poprawny numer karty kredytowej."
} );