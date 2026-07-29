window.recaptchaWidgets = {}; // guardamos widgets por formulario

window.recaptchaOnLoad = function () {
    document.querySelectorAll('.g-recaptcha').forEach(function (el) {
        const formId = el.getAttribute('data-form');
        const $form = $('#' + formId);

        const widgetId = grecaptcha.render(el, {
            sitekey: el.getAttribute('data-sitekey'),
            callback: function () {
                $form.data('recaptcha-verified', true);
                toggleSubmit($form);
            },
            'expired-callback': function () {
                $form.data('recaptcha-verified', false);
                toggleSubmit($form);
            }
        });

        recaptchaWidgets[formId] = widgetId;
    });
};

window.toggleSubmit = function ($form) {
    const $submit = $form.find('button[type="submit"]');

    // Validar checkboxes requeridos
    let allChecked = true;
    const $checkboxes = $form.find('input[type="checkbox"][required]');
    if ($checkboxes.length > 0) {
        $checkboxes.each(function () {
            if (!$(this).is(':checked')) {
                allChecked = false;
                return false;
            }
        });
    }

    const captchaOk = $form.data('recaptcha-verified') === true;
    $submit.prop('disabled', !(captchaOk && allChecked));
};

$(document).ready(function() {



    var messages = {
        en: {
            recaptcha: "Please complete the reCAPTCHA.",
            required: "This field is required.",
            remote: "Please fix this field.",
            email: "Please enter a valid email address.",
            emailvalidate: "Your email is already registered in our system.You can identify yourself by ",
            returnlogin: "CLICKING HERE",
            url: "Please enter a valid URL.",
            date: "Please enter a valid date.",
            dateISO: "Please enter a valid date (ISO).",
            number: "Please enter a valid number.",
            digits: "Please enter only digits.",
            equalTo: "Please enter the same value again.",
            equalTo: "Please enter the same value again.",
            maxlength: $.validator.format("Please enter no more than {0} characters."),
            minlength: $.validator.format("Please enter at least {0} characters."),
            rangelength: $.validator.format("Please enter a value between {0} and {1} characters long."),
            range: $.validator.format("Please enter a value between {0} and {1}."),
            max: $.validator.format("Please enter a value less than or equal to {0}."),
            min: $.validator.format("Please enter a value greater than or equal to {0}."),
            step: $.validator.format("Please enter a multiple of {0}.")
        },
        fr: {
            recaptcha: "Veuillez compléter le reCAPTCHA.",
            required: "Ce champ est obligatoire.",
            remote: "Veuillez corriger ce champ.",
            email: "Veuillez fournir une adresse électronique valide.",
            emailvalidate: "Votre adresse e-mail est déjà enregistrée dans notre système. Vous pouvez vous identifier en ",
            returnlogin: "CLIQUANT ICI",
            url: "Veuillez fournir une adresse URL valide.",
            date: "Veuillez fournir une date valide.",
            dateISO: "Veuillez fournir une date valide (ISO).",
            number: "Veuillez fournir un numéro valide.",
            digits: "Veuillez fournir seulement des chiffres.",
            creditcard: "Veuillez fournir un numéro de carte de crédit valide.",
            equalTo: "Veuillez fournir encore la même valeur.",
            notEqualTo: "Veuillez fournir une valeur différente, les valeurs ne doivent pas être identiques.",
            extension: "Veuillez fournir une valeur avec une extension valide.",
            maxlength: $.validator.format("Veuillez fournir au plus {0} caractères."),
            minlength: $.validator.format("Veuillez fournir au moins {0} caractères."),
            rangelength: $.validator.format("Veuillez fournir une valeur qui contient entre {0} et {1} caractères."),
            range: $.validator.format("Veuillez fournir une valeur entre {0} et {1}."),
            max: $.validator.format("Veuillez fournir une valeur inférieure ou égale à {0}."),
            min: $.validator.format("Veuillez fournir une valeur supérieure ou égale à {0}."),
            step: $.validator.format("Veuillez fournir une valeur multiple de {0}."),
            maxWords: $.validator.format("Veuillez fournir au plus {0} mots."),
            minWords: $.validator.format("Veuillez fournir au moins {0} mots."),
            rangeWords: $.validator.format("Veuillez fournir entre {0} et {1} mots."),
            letterswithbasicpunc: "Veuillez fournir seulement des lettres et des signes de ponctuation.",
            alphanumeric: "Veuillez fournir seulement des lettres, nombres, espaces et soulignages.",
            lettersonly: "Veuillez fournir seulement des lettres.",
            nowhitespace: "Veuillez ne pas inscrire d'espaces blancs.",
            ziprange: "Veuillez fournir un code postal entre 902xx-xxxx et 905-xx-xxxx.",
            integer: "Veuillez fournir un nombre non décimal qui est positif ou négatif.",
            vinUS: "Veuillez fournir un numéro d'identification du véhicule (VIN).",
            dateITA: "Veuillez fournir une date valide.",
            time: "Veuillez fournir une heure valide entre 00:00 et 23:59.",
            phoneUS: "Veuillez fournir un numéro de téléphone valide.",
            phoneUK: "Veuillez fournir un numéro de téléphone valide.",
            mobileUK: "Veuillez fournir un numéro de téléphone mobile valide.",
            strippedminlength: $.validator.format("Veuillez fournir au moins {0} caractères."),
            email2: "Veuillez fournir une adresse électronique valide.",
            url2: "Veuillez fournir une adresse URL valide.",
            creditcardtypes: "Veuillez fournir un numéro de carte de crédit valide.",
            currency: "Veuillez fournir une monnaie valide.",
            ipv4: "Veuillez fournir une adresse IP v4 valide.",
            ipv6: "Veuillez fournir une adresse IP v6 valide.",
            require_from_group: $.validator.format("Veuillez fournir au moins {0} de ces champs."),
            nifES: "Veuillez fournir un numéro NIF valide.",
            nieES: "Veuillez fournir un numéro NIE valide.",
            cifES: "Veuillez fournir un numéro CIF valide.",
            postalCodeCA: "Veuillez fournir un code postal valide.",
            pattern: "Format non valide."
        },
        es: {
            recaptcha: "Por favor, completa el reCAPTCHA.",
            required: "Este campo es obligatorio.",
            remote: "Por favor, rellena este campo.",
            email: "Por favor, escribe una dirección de correo válida.",
            emailvalidate: "Tu correo electrónico ya está registrado en nuestro sistema. Puedes identificarte ",
            returnlogin: "PULSANDO AQUÍ ",
            url: "Por favor, escribe una URL válida.",
            date: "Por favor, escribe una fecha válida.",
            dateISO: "Por favor, escribe una fecha (ISO) válida.",
            number: "Por favor, escribe un número válido.",
            digits: "Por favor, escribe sólo dígitos.",
            creditcard: "Por favor, escribe un número de tarjeta válido.",
            equalTo: "Por favor, escribe el mismo valor de nuevo.",
            extension: "Por favor, escribe un valor con una extensión aceptada.",
            maxlength: $.validator.format("Por favor, no escribas más de {0} caracteres."),
            minlength: $.validator.format("Por favor, no escribas menos de {0} caracteres."),
            rangelength: $.validator.format("Por favor, escribe un valor entre {0} y {1} caracteres."),
            range: $.validator.format("Por favor, escribe un valor entre {0} y {1}."),
            max: $.validator.format("Por favor, escribe un valor menor o igual a {0}."),
            min: $.validator.format("Por favor, escribe un valor mayor o igual a {0}."),
            nifES: "Por favor, escribe un NIF válido.",
            nieES: "Por favor, escribe un NIE válido.",
            cifES: "Por favor, escribe un CIF válido."
        },
        pt: {
            recaptcha: "Por favor, complete o reCAPTCHA.",
            required: "Campo de preenchimento obrigat&oacute;rio.",
            remote: "Por favor, corrija este campo.",
            email: "Por favor, introduza um endere&ccedil;o eletr&oacute;nico v&aacute;lido.",
            returnlogin: "CLICANDO AQUI",
            emailvalidate: "O seu endereço de e-mail já está registado no nosso sistema. Pode identificar-se ",
            url: "Por favor, introduza um URL v&aacute;lido.",
            date: "Por favor, introduza uma data v&aacute;lida.",
            dateISO: "Por favor, introduza uma data v&aacute;lida (ISO).",
            number: "Por favor, introduza um n&uacute;mero v&aacute;lido.",
            digits: "Por favor, introduza apenas d&iacute;gitos.",
            creditcard: "Por favor, introduza um n&uacute;mero de cart&atilde;o de cr&eacute;dito v&aacute;lido.",
            equalTo: "Por favor, introduza de novo o mesmo valor.",
            extension: "Por favor, introduza um ficheiro com uma extens&atilde;o v&aacute;lida.",
            maxlength: $.validator.format("Por favor, n&atilde;o introduza mais do que {0} caracteres."),
            minlength: $.validator.format("Por favor, introduza pelo menos {0} caracteres."),
            rangelength: $.validator.format("Por favor, introduza entre {0} e {1} caracteres."),
            range: $.validator.format("Por favor, introduza um valor entre {0} e {1}."),
            max: $.validator.format("Por favor, introduza um valor menor ou igual a {0}."),
            min: $.validator.format("Por favor, introduza um valor maior ou igual a {0}."),
            nifES: "Por favor, introduza um NIF v&aacute;lido.",
            nieES: "Por favor, introduza um NIE v&aacute;lido.",
            cifES: "Por favor, introduza um CIF v&aacute;lido."
        }
        ,
        de: {
            recaptcha: "Bitte vervollständigen Sie das reCAPTCHA.",
            required: "Dieses Feld ist ein Pflichtfeld.",
            maxlength: $.validator.format( "Geben Sie bitte maximal {0} Zeichen ein." ),
            minlength: $.validator.format( "Geben Sie bitte mindestens {0} Zeichen ein." ),
            rangelength: $.validator.format( "Geben Sie bitte mindestens {0} und maximal {1} Zeichen ein." ),
            email: "Geben Sie bitte eine gültige E-Mail-Adresse ein.",
            emailvalidate: "Ihre E-Mail Adresse ist bereits in unserem System registriert. Sie können sich identifizieren, indem Sie ",
            returnlogin: "HIER KLICKEN",
            url: "Geben Sie bitte eine gültige URL ein.",
            date: "Geben Sie bitte ein gültiges Datum ein.",
            number: "Geben Sie bitte eine Nummer ein.",
            digits: "Geben Sie bitte nur Ziffern ein.",
            equalTo: "Wiederholen Sie bitte denselben Wert.",
            range: $.validator.format( "Geben Sie bitte einen Wert zwischen {0} und {1} ein." ),
            max: $.validator.format( "Geben Sie bitte einen Wert kleiner oder gleich {0} ein." ),
            min: $.validator.format( "Geben Sie bitte einen Wert größer oder gleich {0} ein." ),
            creditcard: "Geben Sie bitte eine gültige Kreditkarten-Nummer ein.",
            remote: "Korrigieren Sie bitte dieses Feld.",
            dateISO: "Geben Sie bitte ein gültiges Datum ein (ISO-Format).",
            step: $.validator.format( "Geben Sie bitte ein Vielfaches von {0} ein." ),
            maxWords: $.validator.format( "Geben Sie bitte {0} Wörter oder weniger ein." ),
            minWords: $.validator.format( "Geben Sie bitte mindestens {0} Wörter ein." ),
            rangeWords: $.validator.format( "Geben Sie bitte zwischen {0} und {1} Wörtern ein." ),
            accept: "Geben Sie bitte einen Wert mit einem gültigen MIME-Typ ein.",
            alphanumeric: "Geben Sie bitte nur Buchstaben (keine Umlaute), Zahlen oder Unterstriche ein.",
            bankaccountNL: "Geben Sie bitte eine gültige Kontonummer ein.",
            bankorgiroaccountNL: "Geben Sie bitte eine gültige Bank- oder Girokontonummer ein.",
            bic: "Geben Sie bitte einen gültigen BIC-Code ein.",
            cifES: "Geben Sie bitte eine gültige CIF-Nummer ein.",
            cpfBR: "Geben Sie bitte eine gültige CPF-Nummer ein.",
            creditcardtypes: "Geben Sie bitte eine gültige Kreditkarten-Nummer ein.",
            currency: "Geben Sie bitte eine gültige Währung ein.",
            extension: "Geben Sie bitte einen Wert mit einer gültigen Erweiterung ein.",
            giroaccountNL: "Geben Sie bitte eine gültige Girokontonummer ein.",
            iban: "Geben Sie bitte eine gültige IBAN ein.",
            integer:  "Geben Sie bitte eine positive oder negative Nicht-Dezimalzahl ein.",
            ipv4: "Geben Sie bitte eine gültige IPv4-Adresse ein.",
            ipv6: "Geben Sie bitte eine gültige IPv6-Adresse ein.",
            lettersonly: "Geben Sie bitte nur Buchstaben ein.",
            letterswithbasicpunc: "Geben Sie bitte nur Buchstaben oder Interpunktion ein.",
            mobileNL: "Geben Sie bitte eine gültige Handynummer ein.",
            mobileUK: "Geben Sie bitte eine gültige Handynummer ein.",
            netmask:  "Geben Sie bitte eine gültige Netzmaske ein.",
            nieES: "Geben Sie bitte eine gültige NIE-Nummer ein.",
            nifES: "Geben Sie bitte eine gültige NIF-Nummer ein.",
            nipPL: "Geben Sie bitte eine gültige NIP-Nummer ein.",
            notEqualTo: "Geben Sie bitte einen anderen Wert ein. Die Werte dürfen nicht gleich sein.",
            nowhitespace: "Kein Leerzeichen bitte.",
            pattern: "Ungültiges Format.",
            phoneNL: "Geben Sie bitte eine gültige Telefonnummer ein.",
            phonesUK: "Geben Sie bitte eine gültige britische Telefonnummer ein.",
            phoneUK: "Geben Sie bitte eine gültige Telefonnummer ein.",
            phoneUS: "Geben Sie bitte eine gültige Telefonnummer ein.",
            postalcodeBR: "Geben Sie bitte eine gültige brasilianische Postleitzahl ein.",
            postalCodeCA: "Geben Sie bitte eine gültige kanadische Postleitzahl ein.",
            postalcodeIT: "Geben Sie bitte eine gültige italienische Postleitzahl ein.",
            postalcodeNL: "Geben Sie bitte eine gültige niederländische Postleitzahl ein.",
            postcodeUK: "Geben Sie bitte eine gültige britische Postleitzahl ein.",
            require_from_group: $.validator.format( "Füllen Sie bitte mindestens {0} dieser Felder aus." ),
            skip_or_fill_minimum: $.validator.format( "Überspringen Sie bitte diese Felder oder füllen Sie mindestens {0} von ihnen aus." ),
            stateUS: "Geben Sie bitte einen gültigen US-Bundesstaat ein.",
            strippedminlength: $.validator.format( "Geben Sie bitte mindestens {0} Zeichen ein." ),
            time: "Geben Sie bitte eine gültige Uhrzeit zwischen 00:00 und 23:59 ein.",
            time12h: "Geben Sie bitte eine gültige Uhrzeit im 12-Stunden-Format ein.",
            vinUS: "Die angegebene Fahrzeugidentifikationsnummer (VIN) ist ungültig.",
            zipcodeUS: "Die angegebene US-Postleitzahl ist ungültig.",
            ziprange: "Ihre Postleitzahl muss im Bereich 902xx-xxxx bis 905xx-xxxx liegen."
        }

    };

    $.validator.addMethod('recaptchaRequired', function(value, element) {
        var response = grecaptcha.getResponse();
        return typeof response === 'string' && response.length > 0;
    }, $.validator.messages.recaptcha);

    var $form_newslettersubscribe = $('#alsernet-newslettersubscribe');
    var language_newslettersubscribe = $('input[name="_alsernetforms_language"]', $form_newslettersubscribe).val();
    $.extend($.validator.messages, messages[language_newslettersubscribe]);

    var $submitButtonSubscribe = $('#alsernet-newslettersubscribe').find('.btn');
    var originalText = $submitButtonSubscribe.text();

    $form_newslettersubscribe.validate({
        ignore: "",
        rules: {
            firstname: {
                required: true,
                minlength: 2,
                maxlength: 50,
            },
            lastname: {
                required: true,
                minlength: 2,
                maxlength: 50,
            },
            email: {
                required: true,
                email: true
            },
            'sports[]': {
                required: true,
            },
            'g-recaptcha-response': {
                recaptchaRequired: true
            }
        },
        messages: {
            firstname: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength,
                lettersonly: $.validator.messages.lettersonly
            },
            lastname: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength,
                lettersonly: $.validator.messages.lettersonly
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            'sports[]': {
                required: $.validator.messages.required,
            },
            'g-recaptcha-response': {
                recaptchaRequired: $.validator.messages.recaptcha
            }
        },
        invalidHandler: function(form, validator) {
            $('label .error').removeClass('d-none');
        },
        errorPlacement: function(error, element) {
            if (element.attr("name") === "g-recaptcha-response-none") {
                error.insertAfter("#g-recaptcha-response-none");
            } else {
                error.insertAfter(element);
            }
        },
        showErrors: function(errorMap, errorList) {
            if (errorList.length > 0) {
                $('label .error').removeClass('d-none');
            } else {
                $('label .error').addClass('d-none');
            }
            this.defaultShowErrors();
        },
        submitHandler: function() {

            var formData = $form_newslettersubscribe.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "newslettersubscribe";

            var sports = $('input[name="sports[]"]:checked', $form_newslettersubscribe)
                .map(function() {
                    return $(this).val();
                }).get();

            formData.push({ name: "sports", value: sports.join(',') });
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonSubscribe = $('#alsernet-newslettersubscribe').find('.btn');
            var originalText = $submitButtonSubscribe.text();
            $submitButtonSubscribe.prop('disabled', true);
            $submitButtonSubscribe.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(response) {

                    var status = response.status;
                    var message = response.message;

                    switch (status) {
                        case "success":

                            $('#formSubscribe').addClass('d-none');
                            $('#subscribeConfirmation').removeClass('d-none');

                            $('html, body').animate({
                                scrollTop: $('#header').offset().top
                            }, 600);

                            clearFormsSubscribe($form_newslettersubscribe);
                            outToggleCheck($form_newslettersubscribe);


                            $submitButtonSubscribe.prop('disabled', false);
                            $submitButtonSubscribe.html(originalText);

                            break;

                        case "warning":

                            $form_newslettersubscribe.find('.response-output').addClass(status).html(message);

                            setTimeout(function() {
                                $form_newslettersubscribe.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonSubscribe.prop('disabled', false);
                            $submitButtonSubscribe.html(originalText);

                            break;

                        default:

                    }

                    grecaptcha.reset();

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_newslettersubscribe.find('.form-check-input[required]').on('click', function() {
        window.toggleSubmit($form_newslettersubscribe);
    });

    setInterval(function () {
        $('textarea#g-recaptcha-response').each(function () {
            var $form = $(this).closest('form');
            var response = $(this).val();
            if (response.length > 0 && !$form.data('recaptcha-verified')) {
                $form.data('recaptcha-verified', true);
                window.toggleSubmit($form);
            }
        });
    }, 500);

    toggleSubmit($form_newslettersubscribe);
    function clearFormsSubscribe(form) {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
        $('input[name="email"]', form).val("");
    }


    var $form_newsletterdischargerssports = $('#alsernet-newsletterdischargerssports');
    var language_newsletterdischargerssports = $('input[name="_alsernetforms_language"]', $form_newsletterdischargerssports).val();
    $.extend($.validator.messages, messages[language_newsletterdischargerssports]);

    var $submitButtonDischargerssports = $('#alsernet-newsletterdischargerssports').find('.btn');
    var originalText = $submitButtonDischargerssports.text();


    $form_newsletterdischargerssports.validate({
        ignore: "",
        rules: {
            email: {
                required: true,
                email: true
            },
            'sports[]': {
                required: true,
            },
            'g-recaptcha-response-sports': {
                recaptchaRequired: true
            }
        },
        messages: {
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            'sports[]': {
                required: $.validator.messages.required,
            },
            'g-recaptcha-response-sports': {
                recaptchaRequired: $.validator.messages.recaptcha
            }
        },
        invalidHandler: function(form, validator) {
            $('label .error').removeClass('d-none');
        },
        showErrors: function(errorMap, errorList) {
            if (errorList.length > 0) {
                $('label .error').removeClass('d-none');
            } else {
                $('label .error').addClass('d-none');
            }
            this.defaultShowErrors();
        },errorPlacement: function(error, element) {
            if (element.attr("name") === "g-recaptcha-response-sports") {
                error.insertAfter("#g-recaptcha-response-sports");
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function() {

            var formData = $form_newsletterdischargerssports.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "newsletterdischargerssports";

            var sports = $('input[name="sports[]"]:checked', $form_newsletterdischargerssports)
                .map(function() {
                    return $(this).val();
                }).get();

            formData.push({ name: "sports", value: sports.join(',') });
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonDischargerssports = $('#alsernet-newsletterdischargerssports').find('.btn');
            var originalText = $submitButtonDischargerssports.text();
            $submitButtonDischargerssports.prop('disabled', true);
            $submitButtonDischargerssports.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;

                    switch (status) {
                        case "success":


                            clearFormsSubscribe($form_newsletterdischargerssports);
                            outToggleCheck($form_newsletterdischargerssports);

                            if(message!="") {

                                $form_newsletterdischargerssports.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_newsletterdischargerssports.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtonDischargerssports.prop('disabled', false);
                            $submitButtonDischargerssports.html(originalText);

                            break;

                        case "warning":

                            $form_newsletterdischargerssports.find('.response-output').addClass(status).html(message);


                            setTimeout(function() {
                                $form_newsletterdischargerssports.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDischargerssports.prop('disabled', false);
                            $submitButtonDischargerssports.html(originalText);

                            break;

                        default:

                    }

                    grecaptcha.reset();

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    var $form_newsletterdischargersparties = $('#alsernet-newsletterdischargersparties');
    var language_newsletterdischargersparties = $('input[name="_alsernetforms_language"]', $form_newsletterdischargersparties).val();
    $.extend($.validator.messages, messages[language_newsletterdischargersparties]);

    var $submitButtonDischargersparties = $('#alsernet-newsletterdischargersparties').find('.btn');
    var originalText = $submitButtonDischargersparties.text();

    $form_newsletterdischargersparties.validate({
        ignore: "",
        rules: {
            email: {
                required: true,
                email: true
            },
            'g-recaptcha-response-parties': {
                recaptchaRequired: true
            }
        },
        messages: {
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            'g-recaptcha-response-parties': {
                recaptchaRequired: $.validator.messages.recaptcha
            }
        },
        invalidHandler: function(form, validator) {
            $('label .error').removeClass('d-none');
        },
        showErrors: function(errorMap, errorList) {
            if (errorList.length > 0) {
                $('label .error').removeClass('d-none');
            } else {
                $('label .error').addClass('d-none');
            }
            this.defaultShowErrors();
        },errorPlacement: function(error, element) {
            if (element.attr("name") === "g-recaptcha-response-parties") {
                error.insertAfter("#g-recaptcha-response-parties");
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function() {

            console.log('alsernetforms')
            var formData = $form_newsletterdischargersparties.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "newsletterdischargersparties";
            var recaptcha = $('#parties').val();

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonDischargersparties = $('#alsernet-newsletterdischargersparties').find('.btn');
            var originalText = $submitButtonDischargersparties.text();
            $submitButtonDischargersparties.prop('disabled', true);
            $submitButtonDischargersparties.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;

                    switch (status) {

                        case "success":

                            clearFormsSubscribe($form_newsletterdischargersparties);
                            outToggleCheck($form_newsletterdischargersparties);

                            if(message!="") {

                                $form_newsletterdischargersparties.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_newsletterdischargersparties.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtonDischargersparties.prop('disabled', false);
                            $submitButtonDischargersparties.html(originalText);

                            break;

                        case "warning":

                            $form_newsletterdischargersparties.find('.response-output').addClass(status).html(message);

                            setTimeout(function() {
                                $form_newsletterdischargersparties.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDischargersparties.prop('disabled', false);
                            $submitButtonDischargersparties.html(originalText);

                            break;

                        default:

                    }

                    grecaptcha.reset();

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    var $form_newsletterdischargersnone = $('#alsernet-newsletterdischargersnone');
    var language_newsletterdischargersnone = $('input[name="_alsernetforms_language"]', $form_newsletterdischargersnone).val();
    $.extend($.validator.messages, messages[language_newsletterdischargersnone]);

    var $submitButtonDischargersnone = $('#alsernet-newsletterdischargersnone').find('.btn');
    var originalText = $submitButtonDischargersnone.text();

    $form_newsletterdischargersnone.validate({
        ignore: "",
        rules: {
            email: {
                required: true,
                email: true
            },
            'g-recaptcha-response-none': {
                recaptchaRequired: true
            }
        },
        messages: {
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            'g-recaptcha-response-none': {
                recaptchaRequired: $.validator.messages.recaptcha
            }
        },
        invalidHandler: function(form, validator) {
            $('label .error').removeClass('d-none');
        },
        showErrors: function(errorMap, errorList) {
            if (errorList.length > 0) {
                $('label .error').removeClass('d-none');
            } else {
                $('label .error').addClass('d-none');
            }
            this.defaultShowErrors();
        },errorPlacement: function(error, element) {
            if (element.attr("name") === "g-recaptcha-response-none") {
                error.insertAfter("#g-recaptcha-response-none");
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function() {

            var formData = $form_newsletterdischargersnone.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "newsletterdischargersnone";
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonDischargersnone = $('#alsernet-newsletterdischargersnone').find('.btn');
            var originalText = $submitButtonDischargersnone.text();
            $submitButtonDischargersnone.prop('disabled', true);
            $submitButtonDischargersnone.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;

                    switch (status) {
                        case "success":

                            clearFormsSubscribe($form_newsletterdischargersnone);
                            outToggleCheck($form_newsletterdischargersnone);

                            if(message!="") {

                                $form_newsletterdischargersnone.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_newsletterdischargersnone.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtonDischargersnone.prop('disabled', false);
                            $submitButtonDischargersnone.html(originalText);

                            break;

                        case "warning":

                            $form_newsletterdischargersnone.find('.response-output').addClass(status).html(message);

                            setTimeout(function() {
                                $form_newsletterdischargersnone.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDischargersnone.prop('disabled', false);
                            $submitButtonDischargersnone.html(originalText);

                            break;

                        default:

                    }

                    grecaptcha.reset();

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });

    function outToggleCheck(form, state) {
        var $form = $(form);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);
        $form.find('.form-check-input').prop('checked', false);
    }
    function outToggleCheckSports(formSelector, state) {
        var $form = $(formSelector);
        $form.find('button[type="submit"]').prop('disabled', true);
        $form.find('.sports-container input[type="checkbox"]').prop('checked', state);
    }

});
