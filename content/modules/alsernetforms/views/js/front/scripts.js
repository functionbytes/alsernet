$(document).ready(function() {
    // Caso Newsletters

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

    var $form_fitting = $('#alsernet-fitting');
    var language_fitting = $('input[name="_alsernetforms_language"]', $form_fitting).val();
    $.extend($.validator.messages, messages[language_fitting]);

    $form_fitting.validate({
        ignore: "",
        rules: {
            phone: {
                required: true,
                minlength: 5
            },
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
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
        },
        submitHandler: function() {

            var formData = $form_fitting.serializeArray();
            var links = $('input[name="_alsernetforms_link"]', $form_fitting).val();
            var iso = $('input[name="_alsernetforms_language"]', $form_fitting).val();
            var action = $('input[name="_alsernetforms_action"]', $form_fitting).val();
            //var recaptcha = $('textarea[id="g-recaptcha-response"]', $form_fitting).val();

            //formData.push({ name: "g-recaptcha-response", value: recaptcha });
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonFitting = $('#alsernet-fitting').find('.btn');
            var originalText = $submitButtonFitting.text();
            $submitButtonFitting.prop('disabled', true);
            $submitButtonFitting.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...'); // Cambia el texto del botón a "Cargando..."


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

                            clearFormsFitting($form_fitting);

                            if(message!="") {
                                $form_fitting.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_fitting.find('.response-output').removeClass(status).html('');
                                }, 4000);
                            }

                            $submitButtonFitting.prop('disabled', false);
                            $submitButtonFitting.html(originalText);

                            break;

                        case "warning":

                            $form_fitting.find('.response-output').addClass(status).html(message);

                            //grecaptcha.reset();

                            setTimeout(function() {
                                $form_fitting.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonFitting.prop('disabled', false);
                            $submitButtonFitting.html(originalText);

                            break;

                        default:

                    }

                    $submitButtonFitting.prop('disabled', false);

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });

    $form_fitting.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_fitting);
    });

    toggleSubmit($form_fitting);


    var $form_demoday = $('#alsernet-demoday');
    var language_demoday = $('input[name="_alsernetforms_language"]', $form_demoday).val();
    $.extend($.validator.messages, messages[language_demoday]);

    $form_demoday.validate({
        ignore: "",
        rules: {
            phone: {
                required: true,
                minlength: 5
            },
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
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
        },
        submitHandler: function() {

            var formData = $form_demoday.serializeArray();
            var links = $('input[name="_alsernetforms_link"]', $form_demoday).val();
            var iso = $('input[name="_alsernetforms_language"]', $form_demoday).val();
            var action = $('input[name="_alsernetforms_action"]', $form_demoday).val();
            //var recaptcha = $('textarea[id="g-recaptcha-response"]', $form_demoday).val();

            formData.push({ name: "g-recaptcha-response", value: recaptcha });
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonDemoday = $('#alsernet-demoday').find('.btn');
            var originalText = $submitButtonDemoday.text();
            $submitButtonDemoday.prop('disabled', true);
            $submitButtonDemoday.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...'); // Cambia el texto del botón a "Cargando..."

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

                            clearFormsFitting($form_demoday);

                            if(message!="") {
                                $form_demoday.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_demoday.find('.response-output').removeClass(status).html('');
                                }, 4000);
                            }

                            $submitButtonDemoday.prop('disabled', false);
                            $submitButtonDemoday.html(originalText);

                            break;

                        case "warning":
                            $form_demoday.find('.response-output').addClass(status).html(message);

                            // grecaptcha.reset();

                            setTimeout(function() {
                                $form_demoday.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDemoday.prop('disabled', false);
                            $submitButtonDemoday.html(originalText);

                            break;

                        default:

                    }

                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_demoday.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_demoday);
    });

    toggleSubmit($form_demoday);

    var $form_demodayorder = $('#alsernet-demodayorder');
    var language_demodayorder = $('input[name="_alsernetforms_language"]', $form_demodayorder).val();
    $.extend($.validator.messages, messages[language_demodayorder]);

    var $submitButtonDemodayorder = $('#alsernet-demodayorder').find('.btn');
    var originalTextDemodayorder = $submitButtonDemodayorder.text();

    // Al iniciar la solicitud AJAX
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.url.includes('controller=product') && $('.page-product-demoday').length > 0 ) {
            // Deshabilita el botón y cambia el texto mientras se procesa la solicitud
            $submitButtonDemodayorder.prop('disabled', true);
            $submitButtonDemodayorder.html('Procesando...');
        }
    });

    // Al completar la solicitud AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url.includes('controller=product') && $('.page-product-demoday').length > 0 ) {
            $submitButtonDemodayorder.prop('disabled', false);
            $submitButtonDemodayorder.html(originalTextDemodayorder);
        }
    });


    $form_demodayorder.validate({
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
            phone: {
                required: true,
                minlength: 5
            },
            email: {
                required: true,
                email: true
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
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
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
        },
        submitHandler: function() {

            var formData = $form_demodayorder.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "demondayvalidation";
            var id_product = $('input[name="id_product"]', $form_demodayorder).val();

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });
            formData.push({ name: "product", value: id_product });

            var $submitButtonDemodayorder = $('#alsernet-demodayorder').find('.btn');
            var originalTextDemodayorder = $submitButtonDemodayorder.text();
            $submitButtonDemodayorder.prop('disabled', true);
            $submitButtonDemodayorder.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;
                    var user = data.data;

                    switch (status) {
                        case "success":

                            var link = '/modules/alsernetforms/controllers/routes.php?action=demondayorder&modalitie=notrecaptcha';

                            $.ajax({
                                url:   link,
                                data:  {
                                    "iso" : iso,
                                    "id_product" : user.id_product,
                                    "id_address" : user.id_address,
                                    "id_customer" : user.id_customer,
                                    "group_192" : $('#group_192').val(),
                                    "group_193" : $('#group_193').val(),
                                    "group_194" : $('#group_194').val(),
                                    "group_195" : $('#group_195').val(),
                                },
                                type:  'POST',
                                success:  function (data) {

                                    var user = data.user;
                                    var status = data.status;
                                    var message = data.message;

                                    switch (status) {
                                        case "success":

                                            clearFormsDemodayOrder($form_demodayorder);
                                            
                                            $('.demodayorder-modal').modal('show');

                                            $submitButtonDemodayorder.prop('disabled', false);
                                            $submitButtonDemodayorder.html(originalTextDemodayorder);

                                            break;

                                        case "warning":

                                            $form_demodayorder.find('.response-output').addClass(status).html(message);

                                            grecaptcha.reset();

                                            setTimeout(function() {
                                                $form_demodayorder.find('.response-output').removeClass(status).html('');
                                            }, 4000);

                                            $submitButtonDemodayorder.prop('disabled', false);
                                            $submitButtonDemodayorder.html(originalTextDemodayorder);

                                            break;

                                        default:
                                    }


                                }
                            });

                            break;

                        case "warning":

                            $form_demodayorder.find('.response-output').addClass(status).html(message);

                            //grecaptcha.reset();

                            setTimeout(function() {
                                $form_demodayorder.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDemodayorder.prop('disabled', false);
                            $submitButtonDemodayorder.html(originalText);

                            break;

                        default:

                    }


                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_demodayorder.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_demodayorder);
    });

    toggleSubmit($form_demodayorder);


    // /* CONTACT FORM */


    // $form_contact.validate({
    //     ignore: "",
    //     rules: {
    //         firstname: {
    //             required: true,
    //             minlength: 2,
    //             maxlength: 50,
    //         },
    //         lastname: {
    //             required: true,
    //             minlength: 2,
    //             maxlength: 50,
    //         },
    //         phone: {
    //             required: true,
    //             minlength: 5
    //         },
    //         email: {
    //             required: true,
    //             email: true
    //         },
    //         message: {
    //             required: true,
    //             minlength: 2,
    //             maxlength: 2000,
    //         },
    //         'sports[]': {
    //             required: true,
    //             minlength: 5
    //         },
    //     },
    //     messages: {
    //         firstname: {
    //             required: $.validator.messages.required,
    //             minlength: $.validator.messages.minlength,
    //             maxlength: $.validator.messages.maxlength,
    //             lettersonly: $.validator.messages.lettersonly
    //         },
    //         lastname: {
    //             required: $.validator.messages.required,
    //             minlength: $.validator.messages.minlength,
    //             maxlength: $.validator.messages.maxlength,
    //             lettersonly: $.validator.messages.lettersonly
    //         },
    //         phone: {
    //             required: $.validator.messages.required,
    //             minlength: $.validator.messages.minlength
    //         },
    //         email: {
    //             required: $.validator.messages.required,
    //             email: $.validator.messages.email
    //         },
    //         message: {
    //             required: $.validator.messages.required,
    //             minlength: $.validator.messages.minlength,
    //             maxlength: $.validator.messages.maxlength,
    //             lettersonly: $.validator.messages.lettersonly
    //         },
    //         'sports[]': {
    //             required: $.validator.messages.required,
    //             email: $.validator.messages.email
    //         }
    //     },
    //     invalidHandler: function(form, validator) {
    //         $('label .error').removeClass('d-none');
    //     },
    //     showErrors: function(errorMap, errorList) {
    //         if (errorList.length > 0) {
    //             $('label .error').removeClass('d-none');
    //         } else {
    //             $('label .error').addClass('d-none');
    //         }
    //         this.defaultShowErrors();
    //     },
    //     submitHandler: function() {

    //         var formData = $form_contact.serializeArray();

    //         var pathArray = window.location.pathname.split('/');
    //         var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
    //         var iso = language!= '' ? language : 'es';

    //         var links = "/modules/alsernetforms/controllers/routes.php";
    //         var action = "demondayvalidation";
    //         var recaptcha = $('textarea[id="g-recaptcha-response"]', $form_contact).val();
    //         var id_product = $('input[name="id_product"]', $form_contact).val();

    //         formData.push({ name: "g-recaptcha-response", value: recaptcha });
    //         formData.push({ name: "action", value: action });
    //         formData.push({ name: "iso", value: iso });

    //         var $submitButtonContact = $('#alsernet-contact').find('.btn');
    //         var originalText = $submitButtonContact.text();
    //         $submitButtonContact.prop('disabled', true);
    //         $submitButtonContact.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...'); // Cambia el texto del botón a "Cargando..."

    //         $.ajax({
    //             url: links,
    //             type: "POST",
    //             data: formData,
    //             dataType: 'json',
    //             success: function(data) {

    //                 var status = data.status;
    //                 var message = data.message;
    //                 var user = data.data;

    //                 switch (status) {
    //                     case "success":


    //                         clearFormsDemodayOrder($form_demodayorder);

    //                         if(message!="") {

    //                             $form_demodayorder.find('.response-output').addClass(status).html(message);

    //                             setTimeout(function() {
    //                                 $form_demodayorder.find('.response-output').removeClass(status).html('');
    //                             }, 4000);

    //                         }

    //                         $submitButtonDemodayorder.prop('disabled', false);
    //                         $submitButtonDemodayorder.html(originalText);

    //                         break;

    //                     case "warning":

    //                         $form_demodayorder.find('.response-output').addClass(status).html(message);

    //                         grecaptcha.reset();

    //                         setTimeout(function() {
    //                             $form_demodayorder.find('.response-output').removeClass(status).html('');
    //                         }, 4000);

    //                         $submitButtonDemodayorder.prop('disabled', false);
    //                         $submitButtonDemodayorder.html(originalText);

    //                         break;

    //                     default:

    //                 }


    //             },
    //             error: function(xhr, status, error) {
    //                 console.log("Error:", error);
    //             }
    //         });
    //     }
    // });


    // $form_contact.find('.form-check-input[required]').on('click', function() {
    //     toggleSubmit($form_contact);
    // });

    // toggleSubmit($form_contact);





    // /* CONTACT FORM */


    var $form_exchangesandreturns = $('#alsernet-exchangesandreturns');
    var language_exchangesandreturns = $('input[name="_alsernetforms_language"]', $form_exchangesandreturns).val();
    $.extend($.validator.messages, messages[language_exchangesandreturns]);

    var $submitButtonExchangesandreturns = $('#alsernet-exchangesandreturns').find('.btn');
    var originalTextExchangesandreturns = $submitButtonExchangesandreturns.text();

    $form_exchangesandreturns.validate({
        ignore: "",
        rules: {
            firstname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            lastname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            number: {
                required: true,
                min: 5,
                max: 9999999,
                number: true
            },
            phone: {
                required: true,
                minlength: 5,
                maxlength: 9
            },
            email: {
                required: true,
                email: true
            },
            reason: {
                required: true,
            },
            message: {
                required: true,
                minlength: 2,
                maxlength: 20000,
            },
            address: {
                required: true,
                minlength: 2,
                maxlength: 20000,
            },
            code: {
                required: true,
                min: 2,
                max: 999999,
            },
            location: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            province: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            country: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            preferred: {
                required: true,
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
            number: {
                required: $.validator.messages.required,
                min: $.validator.messages.min,
                max: $.validator.messages.max,
                number: $.validator.messages.number
            },
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            reason: {
                required: $.validator.messages.required,
            },
            message: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength,
                lettersonly: $.validator.messages.lettersonly
            },
            address: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength
            },
            code: {
                required: $.validator.messages.required,
                min: $.validator.messages.min,
                max: $.validator.messages.max,
                number: $.validator.messages.number
            },
            location: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength
            },
            province: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength
            },
            country: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength,
                maxlength: $.validator.messages.maxlength
            },
            preferred: {
                required: $.validator.messages.required,
            },
            'sports[]': {
                required: $.validator.messages.required,
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
        },
        submitHandler: function() {

            var formData = $form_exchangesandreturns.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "exchangesandreturns";

            var sports = $('input[name="sports[]"]:checked', $form_exchangesandreturns)
                .map(function() {
                    return $(this).val();
                }).get();


            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });
            formData.push({ name: "sports", value: sports.join(',') });

            var $submitButtonContact = $('#alsernet-exchangesandreturns').find('.btn');
            var originalText = $submitButtonContact.text();
            $submitButtonContact.prop('disabled', true);
            $submitButtonContact.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

            $.ajax({
                url: links,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;
                    var user = data.data;

                    switch (status) {
                        case "success":

                            clearFormsExchangesandreturns($form_exchangesandreturns);
                            outToggleCheck($form_exchangesandreturns);
                            outToggleCheckSports($form_exchangesandreturns,false);


                            if(message!="") {

                                $form_exchangesandreturns.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_exchangesandreturns.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtonDemodayorder.prop('disabled', false);
                            $submitButtonDemodayorder.html(originalText);

                            break;

                        case "warning":

                            $form_exchangesandreturns.find('.response-output').addClass(status).html(message);

                            grecaptcha.reset();

                            setTimeout(function() {
                                $form_exchangesandreturns.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonDemodayorder.prop('disabled', false);
                            $submitButtonDemodayorder.html(originalText);

                            break;

                        default:

                    }


                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_exchangesandreturns.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_exchangesandreturns);
    });

    toggleSubmit($form_exchangesandreturns);




    var $form_wecallyouus = $('#alsernet-wecallyouus');
    var language_wecallyouus = $('input[name="_alsernetforms_language"]', $form_wecallyouus).val();
    $.extend($.validator.messages, messages[language_wecallyouus]);

    var $submitButtonwecallyouus = $('#alsernet-wecallyouus').find('.btn');

    $form_wecallyouus.validate({
        ignore: "",
        rules: {
            firstname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            lastname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            phone: {
                required: true,
                minlength: 5,
                maxlength: 9
            },
            email: {
                required: true,
                email: true
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
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
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
        },
        submitHandler: function() {

            var formData = $form_wecallyouus.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "wecallyouus";

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtonWecallyouus = $('#alsernet-wecallyouus').find('.btn');
            var originalTextwecallyouus = $submitButtonWecallyouus.text();
            $submitButtonWecallyouus.prop('disabled', true);
            $submitButtonWecallyouus.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

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

                            clearFormswecallyouus($form_wecallyouus);
                            outToggleCheck($form_wecallyouus);

                            if(message!="") {

                                $form_wecallyouus.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_wecallyouus.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtonWecallyouus.prop('disabled', false);
                            $submitButtonWecallyouus.html(originalTextwecallyouus);

                            break;

                        case "warning":

                            $form_wecallyouus.find('.response-output').addClass(status).html(message);

                            grecaptcha.reset();

                            setTimeout(function() {
                                $form_wecallyouus.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtonWecallyouus.prop('disabled', false);
                            $submitButtonWecallyouus.html(originalTextwecallyouus);

                            break;

                        default:

                    }


                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_wecallyouus.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_wecallyouus);
    });

    toggleSubmit($form_wecallyouus);
    function clearFormswecallyouus(form)
    {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
        $('input[name="email"]', form).val("");
        $('input[name="phone"]', form).val("");
    }

    $('.btn-wecallyouus').on('click', function(event) {
        event.preventDefault(); // Prevenir cualquier acción por defecto
        $('.request-price-form').toggle(); // Alternar la visibilidad del formulario
    });

    function clearFormsDemodayOrder(form) {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
        $('input[name="phone"]', form).val("");
        $('input[name="email"]', form).val("");
    }


    function clearFormsFitting(form) {
        $('input[name="phone"]', form).val("");
        $('input[name="email"]', form).val("");
    }


    function clearFormsExchangesandreturns(form) {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
        $('input[name="phone"]', form).val("");
        $('input[name="number"]', form).val("");
        $('input[name="email"]', form).val("");
        $('textarea[name="message"]', form).val("");
        $('input[name="address"]', form).val("");
        $('input[name="code"]', form).val("");
        $('input[name="location"]', form).val("");
        $('input[name="province"]', form).val("");
        $('input[name="country"]', form).val("");
        $('input[name="preferred"]', form).val("");
        $('input[name="reason"]', form).val("");

        // Clear select dropdowns
        $('select[name="preferred"]', form).prop('selectedIndex', 0); // Reset to first option
        $('select[name="reason"]', form).prop('selectedIndex', 0); // Reset to first option
    }

    $('.form #file').on('change', function() {
        var filenames = [];
        $.each(this.files, function(i, file) {
            filenames.push(file.name);
        });
        $(this).next('.custom-file-label').html(filenames.join(', '));
    });


    var $form_internalinformationsystem = $('#alsernet-internalinformationsystem');
    var language_internalinformationsystem = $('input[name="_alsernetforms_language"]', $form_internalinformationsystem).val();
    $.extend($.validator.messages, messages[language_internalinformationsystem]);

    var $form_internalinformationsystem = $('#alsernet-internalinformationsystem');


    var $submitButtoninternalinformationsystem = $('#alsernet-internalinformationsystem').find('.btn');


    $form_internalinformationsystem.validate({
        ignore: "",
        rules: {
            firstname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            lastname: {
                required: true,
                minlength: 2,
                maxlength: 2000,
            },
            phone: {
                required: true,
                minlength: 5,
                maxlength: 9
            },
            email: {
                required: true,
                email: true
            },
            message: {
                required: true,
            },
            file: {
                required: true,
                extension: "pdf|doc|docx|txt|xls|xlsx|gif|jpg|png",
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
            phone: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            message: {
                required: $.validator.messages.required,
            },
            file: {
                required: $.validator.messages.required,
                extension: $.validator.messages.extension,
            },
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
        },
        submitHandler: function() {

            var formData = $form_internalinformationsystem.serializeArray();

            var pathArray = window.location.pathname.split('/');
            var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
            var iso = language!= '' ? language : 'es';

            var links = "/modules/alsernetforms/controllers/routes.php";
            var action = "internalinformationsystem";

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            var $submitButtoninternalinformationsystem = $('#alsernet-internalinformationsystem').find('.btn');
            var originalTextinternalinformationsystem = $submitButtoninternalinformationsystem.text();
            $submitButtoninternalinformationsystem.prop('disabled', true);
            $submitButtoninternalinformationsystem.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...');

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

                            clearFormsinternalinformationsystem($form_internalinformationsystem);
                            outToggleCheck($form_internalinformationsystem);

                            if(message!="") {

                                $form_internalinformationsystem.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_internalinformationsystem.find('.response-output').removeClass(status).html('');
                                }, 4000);

                            }

                            $submitButtoninternalinformationsystem.prop('disabled', false);
                            $submitButtoninternalinformationsystem.html(originalTextinternalinformationsystem);

                            break;

                        case "warning":

                            $form_internalinformationsystem.find('.response-output').addClass(status).html(message);

                            grecaptcha.reset();

                            setTimeout(function() {
                                $form_internalinformationsystem.find('.response-output').removeClass(status).html('');
                            }, 4000);

                            $submitButtoninternalinformationsystem.prop('disabled', false);
                            $submitButtoninternalinformationsystem.html(originalTextinternalinformationsystem);

                            break;

                        default:

                    }


                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });
        }
    });


    $form_internalinformationsystem.find('.form-check-input[required]').on('click', function() {
        toggleSubmit($form_internalinformationsystem);
    });

    toggleSubmit($form_internalinformationsystem);

    function clearFormsinternalinformationsystem(form)
    {
        $('input[name="firstname"]', form).val("");
        $('input[name="lastname"]', form).val("");
        $('input[name="email"]', form).val("");
        $('input[name="phone"]', form).val("");
        $('input[type="file"]', form).val("");
        $('textarea', form).val("");
    }


    function toggleSubmit(form, state) {
        var $form = $(form);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);

        if (typeof state !== 'undefined') {
            $submit.prop('disabled', !state);
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
        // Seleccionar el formulario o contenedor usando el selector proporcionado
        var $form = $(formSelector);
        // Deshabilitar el botón de envío dentro del formulario
        $form.find('button[type="submit"]').prop('disabled', true);

        // Cambiar el estado de los checkboxes que tienen un padre con la clase .sports-container
        $form.find('.sports-container input[type="checkbox"]').prop('checked', state);
    }

});
