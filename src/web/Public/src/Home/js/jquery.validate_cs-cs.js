$.extend( $.validator.messages, {
    required: "Toto pole je vyžadováno.",
    maxlength: $.validator.format( "Prosím nepoužij více jak {0} znaků." ),
    minlength: $.validator.format( "Prosím použij alespoň {0} znaků." ),
    rangelength: $.validator.format( "Prosím zadej hodnotu mezi {0} a {1} délky znaků." ),
    email: "Prosím zadej platnou e-mailovou adresu.",
    url: "Prosím zadej platné URL.",
    date: "Prosím zadej platné datum.",
    number: "Prosím zadej platné číslo.",
    digits: "Prosím zadej pouze číslice.",
    equalTo: "Bitte denselben Wert wiederholen.",
    range: $.validator.format( "Prosím zadej hodnotu mezi {0} a {1}." ),
    max: $.validator.format( "Prosím zadej hodnotu menší nebo rovnu {0}." ),
    min: $.validator.format( "Prosím zadej hodnotu větší nebo rovnu {0}." ),
    creditcard: "Prosím zadej platné číslo karty."
} );