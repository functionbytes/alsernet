window.settings = window.settings || {};

Object.assign(window.settings, {
    getISO: function () {
        const pathArray = window.location.pathname.split('/');
        const language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
        return language || 'es';
    },
    getBaseUrl: function () {
        return window.location.origin;
    },
    getMessages: function () {
        const lang = this.getISO();
        return this.messages[lang] || this.messages['es'];
    },

    initValidationMessages: function () {
        if (typeof $.validator !== 'undefined' && typeof $.validator.messages !== 'undefined') {
            $.extend($.validator.messages, this.getMessages());
            this.addCustomValidationMethods();
        } else {
            console.warn('jQuery Validate no está cargado aún. initValidationMessages ignorado.');
        }
    },

    addCustomValidationMethods: function () {
        if (typeof $.validator === 'undefined' || typeof $.validator.addMethod !== 'function') {
            return;
        }

        // Custom validation method for names (letters, spaces and hyphens)
        $.validator.addMethod("lettersandspace", function(value, element) {
            // Allow letters, single spaces, and hyphens (no consecutive spaces or hyphens)
            return this.optional(element) || /^[a-zA-ZÀ-ÿ\u00f1\u00d1]+([a-zA-ZÀ-ÿ\u00f1\u00d1\s-]*[a-zA-ZÀ-ÿ\u00f1\u00d1])?$/.test(value);
        }, this.getMessages().lettersandspace || "Solo se permiten letras, espacios y guiones para nombres compuestos");

        // Custom validation method for letters only (backward compatibility)
        $.validator.addMethod("lettersOnly", function(value, element) {
            return this.optional(element) || /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜçÇ\s]+$/i.test(value);
        }, this.getMessages().lettersOnly || "Solo se permiten letras y espacios.");

        // Custom validation method for numbers only
        $.validator.addMethod("numbersOnly", function(value, element) {
            return this.optional(element) || /^\d+$/.test(value);
        }, this.getMessages().numbersOnly || "Solo se permiten números.");

        // Custom validation method for phone numbers
        $.validator.addMethod("phoneNumbers", function(value, element) {
            if (this.optional(element)) return true;
            const regex = /^\+?[0-9]{1,3}?([ \-()]?[0-9]{1,4}){2,6}$/;
            const digits = value.replace(/[^\d]/g, '');
            return regex.test(value) && digits.length >= 8 && digits.length <= 15;
        }, this.getMessages().phoneNumbers || "Ingrese un número de teléfono válido (8 a 15 dígitos).");

        // Custom validation method for postal codes (international format)
        // Uses backend validation for country-specific format checking
        $.validator.addMethod("postalCode", function(value, element) {
            if (this.optional(element)) return true;

            // Basic format validation - covers most international postal code patterns
            // Letters, numbers, spaces, and hyphens (3-12 characters)
            const basicFormat = /^[A-Z0-9\s\-\.]{2,12}$/i.test(value.trim());

            if (!basicFormat) {
                return false;
            }

            // Additional validation for common patterns
            const patterns = {
                // Common international patterns
                numeric: /^\d{3,8}$/, // Pure numeric (3-8 digits)
                alphanumeric: /^[A-Z0-9]{4,8}$/i, // Alphanumeric
                withHyphen: /^[A-Z0-9]{1,5}-[A-Z0-9]{1,5}$/i, // With hyphen
                withSpace: /^[A-Z0-9]{1,5}\s[A-Z0-9]{1,5}$/i, // With space
                uk: /^[A-Z]{1,2}[0-9][A-Z0-9]?\s?[0-9][A-Z]{2}$/i, // UK format
                canada: /^[ABCEGHJ-NPRSTVXY][0-9][ABCEGHJ-NPRSTV-Z]\s?[0-9][ABCEGHJ-NPRSTV-Z][0-9]$/i // Canadian format
            };

            // Check if matches any common pattern
            for (const pattern of Object.values(patterns)) {
                if (pattern.test(value.trim())) {
                    return true;
                }
            }

            return basicFormat; // Fall back to basic format check
        }, this.getMessages().postalCode || "Ingrese un código postal válido.");
    },

    getValidationMessage: function (rule, param) {
        const message = $.validator.messages[rule];

        if (typeof message === 'function') {
            return message(param);
        }

        if (typeof message === 'string' && typeof param !== 'undefined') {
            return message.replace('{0}', param);
        }

        return message || '';
    },
    applyDynamicValidation: function ($form) {
        const rules = {};
        const messages = {};

        $form.find('input, select, textarea').each(function () {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');

            if (!name || name.startsWith('_') || name === 'sports[]') return;

            const fieldRules = {};
            const fieldMessages = {};

            if ($field.prop('required')) {
                fieldRules.required = true;
                fieldMessages.required = settings.getValidationMessage('required');
            }

            if (type === 'email') {
                fieldRules.email = true;
                fieldMessages.email = settings.getValidationMessage('email');
            }

            if (type === 'password') {
                fieldRules.minlength = 8;
                fieldMessages.minlength = settings.getValidationMessage('minlength', 8);
            }

            if ($field.attr('maxlength')) {
                const max = parseInt($field.attr('maxlength'), 10);
                fieldRules.maxlength = max;
                fieldMessages.maxlength = settings.getValidationMessage('maxlength', max);
            }

            if ($field.attr('minlength')) {
                const min = parseInt($field.attr('minlength'), 10);
                fieldRules.minlength = min;
                fieldMessages.minlength = settings.getValidationMessage('minlength', min);
            }

            if (type === 'date') {
                fieldRules.date = true;
                fieldMessages.date = settings.getValidationMessage('date');
            }

            rules[name] = fieldRules;
            messages[name] = fieldMessages;
        });


    },
    showToast: function (type, message, title = '', customOptions = {}) {
        if (typeof toastr === 'undefined') {
            console.warn("toastr no está disponible.");
            return;
        }

        const defaultOptions = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-bottom-right",
            timeOut: 5000,
            preventDuplicates: true
        };

        toastr.options = { ...defaultOptions, ...customOptions };

        switch (type) {
            case 'success':
                toastr.success(message, title);
                break;
            case 'error':
                toastr.error(message, title);
                break;
            case 'warning':
                toastr.warning(message, title);
                break;
            case 'info':
                toastr.info(message, title);
                break;
            default:
                console.warn(`Tipo de toast no reconocido: ${type}`);
                break;
        }
    },
    toggleSubmit: function ($form) {
        const $submit = $form.find('button[type="submit"]');

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
    },
    setResponsiveBanner: function (selector, options) {
        const language = (window.location.pathname.split('/')[1] || 'es').substring(0, 2);
        const userAgent = navigator.userAgent;
        const screenWidth = $(window).width();
        const screenHeight = $(window).height();

        const isMobileResolution = screenWidth < 900 && screenHeight < 1280;
        const isIpad = /iPad/.test(userAgent) || (isMobileResolution && /Macintosh/.test(userAgent) && 'ontouchend' in document);
        const isIphone = /iPhone/.test(userAgent);
        const isAndroid = /Android/.test(userAgent);
        const isWeb = !(isIphone || isAndroid || isIpad);

        const $image = $(selector);
        const imageName = isWeb ? options.desktop : options.mobile;
        const finalPath = `${options.basePath}/${language}/${imageName}`;

        $image.attr('src', finalPath);
    },
    clearForm: function ($form) {
        $form.find('input:not([type="hidden"]), select, textarea').each(function () {
            const $field = $(this);
            const type = $field.attr('type');

            if (type === 'checkbox' || type === 'radio') {
                $field.prop('checked', false);
            } else {
                $field.val('');
            }

            if ($field.is('select') && $field.hasClass('select2-hidden-accessible')) {
                $field.trigger('change');
            }
        });

        $form.find('.response-output').removeClass().html('');

        if ($form.data('validator')) {
            $form.validate().resetForm();
            $form.find('.error').removeClass('error');
        }
    },
    resetCheckbox: function (selector) {
        $(selector).prop('checked', false);
    },
    outToggleCheck: function (form, state) {
        const $form = $(form);
        const $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);
        $form.find('.form-check-input').prop('checked', false);
    },
    outToggleCheckSports: function (formSelector, state) {
        const $form = $(formSelector);
        $form.find('button[type="submit"]').prop('disabled', true);
        $form.find('.sports-container input[type="checkbox"]').prop('checked', state);
    },
    validateEmailField: function ($form) {
        const email = $form.find('[name="email"]').val();
        const iso = $form.find('[name="_alsernetauth_language"]').val();
        const $emailErrorLabel = $form.find('label[for="emails"]');

        $.ajax({
            url: 'modules/alsernetauth/controllers/routes.php?action=validateemail',
            type: "POST",
            data: { email: email, iso: iso },
            dataType: 'json',
            success: function (data) {
                if (data.status === "success") {
                    $emailErrorLabel.removeClass('d-none').text(data.message);
                    setTimeout(() => {
                        $emailErrorLabel.addClass('d-none').text('');
                    }, 30000);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error al validar email:", error);
            }
        });
    },
    bindRequiredCheckboxWatcher: function (formSelector) {
        const $form = $(formSelector);
        $form.find('.form-check-input[required]').on('click', function () {
            settings.toggleSubmit($form);
        });

        // Llamada inicial para desactivar/activar según estado actual
        settings.toggleSubmit($form);
    },
    bindEmailValidation: function (formSelector) {
        const $form = $(formSelector);
        const $emailInput = $form.find('[name="email"]');
        const $emailErrorLabel = $form.find('label[for="emails"]');

        $emailInput.on('blur', function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailRegex.test(email)) {
                settings.validateEmailField($form);
            }
        });

        $emailInput.on('input', function () {
            $emailErrorLabel.addClass('d-none').text('');
        });
    },
    clearEmailError: function ($form) {
        const $emailErrorLabel = $form.find('label[for="emails"]');
        $emailErrorLabel.addClass('d-none').text('');
    },
    initToastr: function () {
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-bottom-right",
                timeOut: 5000,
                preventDuplicates: true
            };
        } else {
            console.warn("toastr no está disponible. Asegúrate de que toastr.min.js esté cargado.");
        }
    },

    messages: {
        en: {
            recaptcha: "Please complete the reCAPTCHA.",
            required: "This field is required.",
            remote: "Please fix this field.",
            email: "Please enter a valid email address.",
            emailvalidate: "Your email is already registered in our system. You can identify yourself by ",
            returnlogin: "CLICKING HERE",
            url: "Please enter a valid URL.",
            date: "Please enter a valid date.",
            dateISO: "Please enter a valid date (ISO).",
            number: "Please enter a valid number.",
            digits: "Please enter only digits.",
            equalTo: "Please enter the same value again.",
            maxlength: "Please enter no more than {0} characters.",
            minlength: "Please enter at least {0} characters.",
            rangelength: "Please enter a value between {0} and {1} characters long.",
            range: "Please enter a value between {0} and {1}.",
            max: "Please enter a value less than or equal to {0}.",
            min: "Please enter a value greater than or equal to {0}.",
            step: "Please enter a multiple of {0}.",
            lettersandspace: "Only letters, spaces and hyphens are allowed for compound names.",
            lettersOnly: "Only letters and spaces are allowed.",
            numbersOnly: "Only numbers are allowed.",
            phoneNumbers: "Enter a valid phone number (8 to 15 digits).",
            postalCode: "Enter a valid postal code."
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
            notEqualTo: "Veuillez fournir une valeur différente.",
            extension: "Veuillez fournir une extension valide.",
            maxlength: "Veuillez fournir au plus {0} caractères.",
            minlength: "Veuillez fournir au moins {0} caractères.",
            rangelength: "Veuillez fournir une valeur entre {0} et {1} caractères.",
            range: "Veuillez fournir une valeur entre {0} et {1}.",
            max: "Veuillez fournir une valeur inférieure ou égale à {0}.",
            min: "Veuillez fournir une valeur supérieure ou égale à {0}.",
            step: "Veuillez fournir une valeur multiple de {0}.",
            maxWords: "Veuillez fournir au plus {0} mots.",
            minWords: "Veuillez fournir au moins {0} mots.",
            rangeWords: "Veuillez fournir entre {0} et {1} mots.",
            letterswithbasicpunc: "Veuillez fournir seulement des lettres et des signes de ponctuation.",
            alphanumeric: "Veuillez fournir seulement des lettres, nombres, espaces et soulignages.",
            lettersonly: "Veuillez fournir seulement des lettres.",
            nowhitespace: "Veuillez ne pas utiliser d'espaces.",
            ziprange: "Veuillez fournir un code postal entre 902xx-xxxx et 905-xx-xxxx.",
            integer: "Veuillez fournir un nombre entier.",
            vinUS: "Numéro d'identification du véhicule invalide.",
            time: "Heure invalide.",
            phoneUS: "Numéro de téléphone US invalide.",
            phoneUK: "Numéro de téléphone UK invalide.",
            mobileUK: "Numéro de mobile UK invalide.",
            strippedminlength: "Veuillez fournir au moins {0} caractères.",
            email2: "Veuillez fournir une adresse email valide.",
            url2: "Veuillez fournir une URL valide.",
            creditcardtypes: "Veuillez fournir un type de carte valide.",
            currency: "Veuillez fournir une devise valide.",
            ipv4: "Adresse IPv4 invalide.",
            ipv6: "Adresse IPv6 invalide.",
            require_from_group: "Veuillez remplir au moins {0} de ces champs.",
            nifES: "NIF invalide.",
            nieES: "NIE invalide.",
            cifES: "CIF invalide.",
            postalCodeCA: "Code postal CA invalide.",
            pattern: "Format invalide.",
            lettersandspace: "Seules les lettres, espaces et tirets sont autorisés pour les noms composés.",
            lettersOnly: "Seules les lettres et espaces sont autorisés.",
            numbersOnly: "Seuls les numéros sont autorisés.",
            phoneNumbers: "Entrez un numéro de téléphone valide (8 à 15 chiffres).",
            postalCode: "Entrez un code postal valide."
        },
        es: {
            recaptcha: "Por favor, completa el reCAPTCHA.",
            required: "Este campo es obligatorio.",
            remote: "Por favor, rellena este campo.",
            email: "Por favor, escribe un correo válido.",
            emailvalidate: "Tu correo ya está registrado. Puedes identificarte ",
            returnlogin: "PULSANDO AQUÍ",
            url: "Por favor, escribe una URL válida.",
            date: "Por favor, escribe una fecha válida.",
            dateISO: "Por favor, escribe una fecha ISO válida.",
            number: "Por favor, escribe un número válido.",
            digits: "Sólo se permiten dígitos.",
            creditcard: "Número de tarjeta no válido.",
            equalTo: "Repite el mismo valor.",
            extension: "Extensión no permitida.",
            maxlength: "Máximo {0} caracteres.",
            minlength: "Mínimo {0} caracteres.",
            rangelength: "Entre {0} y {1} caracteres.",
            range: "Valor entre {0} y {1}.",
            max: "Valor menor o igual a {0}.",
            min: "Valor mayor o igual a {0}.",
            nifES: "NIF no válido.",
            nieES: "NIE no válido.",
            cifES: "CIF no válido.",
            lettersandspace: "Solo se permiten letras, espacios y guiones para nombres compuestos.",
            lettersOnly: "Solo se permiten letras y espacios.",
            numbersOnly: "Solo se permiten números.",
            phoneNumbers: "Ingrese un número de teléfono válido (8 a 15 dígitos).",
            postalCode: "Ingrese un código postal válido."
        },
        pt: {
            recaptcha: "Por favor, complete o reCAPTCHA.",
            required: "Campo obrigatório.",
            remote: "Por favor, corrija este campo.",
            email: "Por favor, introduza um e-mail válido.",
            returnlogin: "CLICANDO AQUI",
            emailvalidate: "Seu email já está registrado.",
            url: "Por favor, introduza uma URL válida.",
            date: "Data inválida.",
            dateISO: "Data ISO inválida.",
            number: "Número inválido.",
            digits: "Apenas dígitos permitidos.",
            creditcard: "Número de cartão inválido.",
            equalTo: "Repita o mesmo valor.",
            extension: "Extensão inválida.",
            maxlength: "Insira no máximo {0} caracteres.",
            minlength: "Insira pelo menos {0} caracteres.",
            rangelength: "Entre {0} e {1} caracteres.",
            range: "Valor entre {0} e {1}.",
            max: "Valor máximo {0}.",
            min: "Valor mínimo {0}.",
            nifES: "NIF inválido.",
            nieES: "NIE inválido.",
            cifES: "CIF inválido.",
            lettersandspace: "Apenas letras, espaços e hífens são permitidos para nomes compostos.",
            lettersOnly: "Apenas letras e espaços são permitidos.",
            numbersOnly: "Apenas números são permitidos.",
            phoneNumbers: "Digite um número de telefone válido (8 a 15 dígitos).",
            postalCode: "Digite um código postal válido."
        },
        de: {
            recaptcha: "Bitte vervollständigen Sie das reCAPTCHA.",
            required: "Dieses Feld ist erforderlich.",
            email: "Bitte geben Sie eine gültige E-Mail-Adresse ein.",
            emailvalidate: "Diese E-Mail ist bereits registriert.",
            returnlogin: "HIER KLICKEN",
            url: "Bitte geben Sie eine gültige URL ein.",
            date: "Bitte geben Sie ein gültiges Datum ein.",
            dateISO: "Bitte im ISO-Format.",
            number: "Bitte geben Sie eine Zahl ein.",
            digits: "Nur Ziffern erlaubt.",
            creditcard: "Ungültige Kreditkartennummer.",
            equalTo: "Bitte denselben Wert erneut eingeben.",
            extension: "Ungültige Dateierweiterung.",
            maxlength: "Bitte maximal {0} Zeichen eingeben.",
            minlength: "Bitte mindestens {0} Zeichen eingeben.",
            rangelength: "Zwischen {0} und {1} Zeichen.",
            range: "Wert zwischen {0} und {1} erforderlich.",
            max: "Maximalwert: {0}.",
            min: "Minimalwert: {0}.",
            require_from_group: "Bitte mindestens {0} dieser Felder ausfüllen.",
            strippedminlength: "Bitte mindestens {0} Zeichen.",
            ziprange: "PLZ muss zwischen 902xx-xxxx und 905xx-xxxx liegen.",
            nifES: "Ungültige NIF.",
            nieES: "Ungültige NIE.",
            cifES: "Ungültige CIF.",
            lettersandspace: "Nur Buchstaben, Leerzeichen und Bindestriche sind für zusammengesetzte Namen erlaubt.",
            lettersOnly: "Nur Buchstaben und Leerzeichen sind erlaubt.",
            numbersOnly: "Nur Zahlen sind erlaubt.",
            phoneNumbers: "Geben Sie eine gültige Telefonnummer ein (8 bis 15 Ziffern).",
            postalCode: "Geben Sie eine gültige Postleitzahl ein."
        }
    }

});

$(function () {
    settings.initToastr();
    settings.initValidationMessages();
});