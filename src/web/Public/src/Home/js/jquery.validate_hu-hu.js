$.extend( $.validator.messages, {
    required: "Ez a mező szükséges.",
    maxlength: $.validator.format( "Kérjük ne használ több mint {0} karaktert." ),
    minlength: $.validator.format( "Adj meg legalább {0} karaktert." ),
    rangelength: $.validator.format( "Adj meg egy értéket {0} és {1} karakter hosszúság között." ),
    email: "Adj meg egy érvényes e-mail címet.",
    url: "Adj meg egy érvényes URL-t.",
    date: "Adj meg egy érvényes dátumot.s",
    number: "Adj meg egy érvényes számot.",
    digits: "Kérjük csak számjegyeket használj.",
    equalTo: "Kérjük, írd be ismét ugyanazt az értéket.",
    range: $.validator.format( "Adj meg egy értéket a {0} és {1} között." ),
    max: $.validator.format( "Adj meg egy értéket ami kisebb vagy egyenlő, mint {0}." ),
    min: $.validator.format( "Adj meg egy értéket, ami nagyobb vagy egyenlő, mint {0}." ),
    creditcard: "Adj meg egy érvényes hitelkártya számot."
} );