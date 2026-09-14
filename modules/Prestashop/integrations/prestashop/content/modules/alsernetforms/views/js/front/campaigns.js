$(document).ready(function() {

    
    var messages = {
        en: {
            required: "This field is required.",
            remote: "Please fix this field.",
            email: "Please enter a valid email address.",
            url: "Please enter a valid URL.",
            date: "Please enter a valid date.",
            dateISO: "Please enter a valid date (ISO).",
            number: "Please enter a valid number.",
            digits: "Please enter only digits.",
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
            required: "Ce champ est obligatoire.",
            remote: "Veuillez corriger ce champ.",
            email: "Veuillez fournir une adresse électronique valide.",
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
            required: "Este campo es obligatorio.",
            remote: "Por favor, rellena este campo.",
            email: "Por favor, escribe una dirección de correo válida.",
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
            required: "Campo de preenchimento obrigat&oacute;rio.",
            remote: "Por favor, corrija este campo.",
            email: "Por favor, introduza um endere&ccedil;o eletr&oacute;nico v&aacute;lido.",
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
    };

    var $form_campaigns = $('#alsernet-campaigns');
    var language_campaigns = $('input[name="_alsernetforms_language"]', $form_campaigns).val();
    $.extend($.validator.messages, messages[language_campaigns]);

    var $submitButtonCampaigns = $('#alsernet-campaigns').find('.btn');
    var originalText = $submitButtonCampaigns.text();

    $form_campaigns.validate({
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
            }
        },
        invalidHandler: function(form, validator) {
            $('label .error').removeClass('d-none');
            $('#commercial').prop('checked', false); 
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

            var formData = $form_campaigns.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = $('input[name="_alsernetforms_action"]', $form_campaigns).val();
            var campaigns = $('input[name="_alsernetforms_campaigns"]', $form_campaigns).val();
            var form = $('input[name="_alsernetforms_form"]', $form_campaigns).val();
            
            var sports = $('input[name="sports[]"]:checked', $form_campaigns)
                .map(function() {
                    return $(this).val();
                }).get();

            formData.push({ name: "sports", value: sports.join(',') });
            formData.push({ name: "action", value: action });
            formData.push({ name: "campaigns", value: campaigns });
            formData.push({ name: "form", value: form });
            formData.push({ name: "iso", value: iso });
            
            var $submitButtonCampaigns = $('#alsernet-campaigns').find('.btn');
            var originalText = $submitButtonCampaigns.text();
            $submitButtonCampaigns.prop('disabled', true);
            $submitButtonCampaigns.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

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

                            $('#formCampaigns').addClass('d-none');
                            $('#campaignsConfirmation').removeClass('d-none');

                            $('html, body').animate({
                                scrollTop: $('#header').offset().top
                            }, 600);

                            break;

                        case "warning":

                            clearFormsSubscribe($form_campaigns);
                            outToggleCheck($form_campaigns);clearFormsSubscribe

                            $form_campaigns.find('.response-output').addClass(status).html(message);

                            setTimeout(function() {
                                $form_campaigns.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonCampaigns.prop('disabled', false);
                            $submitButtonCampaigns.html(originalText);

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

    $form_campaigns.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_campaigns);
    });

    toggleSubmit($form_campaigns);
    function clearFormsSubscribe(form) {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
    }
    function toggleSubmit(form, state) {
        var $form = $(form);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);

        if (typeof state !== 'undefined') {
            $submit.prop('disabled', !state);
            grecaptcha.reset();
            return;
        }

        var allChecked = true;
        $form.find('.form-check-input[required]').each(function() {
            if (!$(this).is(':checked')) {
                allChecked = false;
                return false;
            }
        });

        $submit.prop('disabled', !allChecked);
    }
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
