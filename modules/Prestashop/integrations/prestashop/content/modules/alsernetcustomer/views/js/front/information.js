
$(document).ready(function() {

    const $form_information = $('#customer-information');

    $(document).on('click', '.toggle-passwords', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const targetId = $btn.data('target');
        const $input = $('#' + targetId);

        if ($input.length) {
            const isHidden = $input.attr('type') === 'password';
            $input.attr('type', isHidden ? 'text' : 'password');

            const textHide = $btn.data('text-hide') || 'Ocultar';
            const textShow = $btn.data('text-show') || 'Mostrar';

            $btn.text(isHidden ? textHide : textShow);
        }
    });
    const sportsRaw = $('#customer-sports').val();
    if (sportsRaw) {
        const selectedSports = sportsRaw.split(',').map(s => s.trim());
        selectedSports.forEach(function (sportId) {
            const checkbox = $('#registersports-sports_' + sportId);
            if (checkbox.length) {
                checkbox.prop('checked', true).trigger('change');
            }
        });
    }

    $('input[name="sports[]"]').on('change', function () {
        const label = $('label[for="' + $(this).attr('id') + '"]');
        label.toggleClass('active', $(this).is(':checked'));
    });

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

    const pathArray = window.location.pathname.split('/');
    const language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : 'es';
    const iso = language || 'es';

    $.extend($.validator.messages, messages[iso]);

    // Generar reglas dinámicamente
    const dynamicRules = {};
    const dynamicMessages = {};

    $form_information.find('input, select, textarea').each(function () {
        const $field = $(this);
        const name = $field.attr('name');
        const type = $field.attr('type');
        const required = $field.prop('required');
        const maxlength = $field.attr('maxlength');
        const minlength = $field.attr('minlength');

        if (!name || name.startsWith('_') || name === 'sports[]') return;

        dynamicRules[name] = {};
        dynamicMessages[name] = {};

        if (required) {
            dynamicRules[name].required = true;
            dynamicMessages[name].required = $.validator.messages.required;
        }

        if (type === 'email') {
            dynamicRules[name].email = true;
            dynamicMessages[name].email = $.validator.messages.email;
        }

        if (type === 'password') {
            dynamicRules[name].minlength = 8;
            dynamicMessages[name].minlength = $.validator.messages.minlength;
        }

        if (maxlength) {
            dynamicRules[name].maxlength = parseInt(maxlength);
            dynamicMessages[name].maxlength = $.validator.messages.maxlength;
        }

        if (minlength) {
            dynamicRules[name].minlength = parseInt(minlength);
            dynamicMessages[name].minlength = $.validator.messages.minlength;
        }

        if (type === 'date') {
            dynamicRules[name].date = true;
            dynamicMessages[name].date = $.validator.messages.date;
        }
    });

    dynamicRules['commercial'] = { required: true };
    dynamicMessages['commercial'] = { required: $.validator.messages.required };
    $form_information.validate({
        ignore: "",
        rules: dynamicRules,
        messages: dynamicMessages,
        invalidHandler: function () {
            $('label .error').removeClass('d-none');
        },
        showErrors: function (errorMap, errorList) {
            if (errorList.length > 0) {
                $('label .error').removeClass('d-none');
            } else {
                $('label .error').addClass('d-none');
            }
            this.defaultShowErrors();
        },
        submitHandler: function () {
            const formData = $form_information.serializeArray();
            const iso = language || 'es';
            const link = `/modules/alsernetcustomer/controllers/routes.php?action=information&modalitie=customer&iso=${iso}`;

            $.ajax({
                url: link,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function (response) {
                    const { status, message, operation } = response;

                    if (status === "success") {
                        toastr.success(message, operation, {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    } else if (status === "warning") {
                        let output = message;
                        const errors = response.data?.errors ?? [];

                        if (Array.isArray(errors)) {
                            output += '<ul>';
                            errors.forEach(function (error) {
                                output += `<li>${error}</li>`;
                            });
                            output += '</ul>';
                        }

                        $form_information.find('.response-output').addClass('error').html(output);

                        setTimeout(function () {
                            $form_information.find('.response-output').removeClass('error').html('');
                        }, 30000);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error:", error);
                }
            });
        }
    });

    function toggleSubmits(form, state) {
        const $form = $(form);
        const $submit = $form.find('button[type="submit"]');

        if (typeof state !== 'undefined') {
            $submit.prop('disabled', !state).toggleClass('disabled', !state);
            return;
        }

        const allChecked = $form.find('.form-check-input[required]').toArray()
            .every(input => $(input).is(':checked'));

        $submit.prop('disabled', !allChecked).toggleClass('disabled', !allChecked);
    }

    toggleSubmits($form_information);

    $form_information.find('.form-check-input[required]').on('change', function () {
        toggleSubmits($form_information);
    });

    $('#submit-information').on('click', function (e) {
        e.preventDefault();
        if ($form_information.valid()) {
            $form_information.submit(); // Esto dispara el submitHandler de jQuery Validate
        } else {
            $('html, body').animate({
                scrollTop: $form_information.find('.error:visible').first().offset().top - 100
            }, 600);
        }
    });


});
