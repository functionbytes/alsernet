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

    var $form_login = $('#alsernet-login');
    var language_login = $('input[name="_alsernetauth_language"]', $form_login).val();
    $.extend($.validator.messages, messages[language_login]);

    $form_login.validate({
        ignore: "",
        rules: {
            password: {
                required: true,
                minlength: 5
            },
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            password: {
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


            var formData = $form_login.serializeArray();
            var links = $('input[name="_alsernetauth_link"]', $form_login).val();
            var iso = $('input[name="_alsernetauth_language"]', $form_login).val();
            var link =  window.location.origin + '/' + links;
            var action = $('input[name="_alsernetauth_action"]', $form_login).val();

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            $.ajax({
                url: link,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
                    var message = data.message;

                    switch (status) {
                        case "success":

                            clearFormsLogin($form_login);

                            if(message!="") {
                                $form_login.find('.response-output').addClass(status).html(message);

                                setTimeout(function() {
                                    $form_login.find('.response-output').removeClass(status).html('');
                                }, 5000);
                            }

                            var referrer = document.referrer;
                            var currentUrl = window.location.href;
                            var myAccountUrl = data.url;

                            var cookie = getCookie('wishlist_redirect');
                            
                            if (cookie) {
                                var data = JSON.parse(decodeURIComponent(cookie));
                                var redirectUrl = data.redirect_url;

                                eraseCookie('wishlist_redirect');
                                window.location.replace(redirectUrl);
                            } else {
                                window.location.replace(myAccountUrl);
                            }


                            break;

                        case "warning":
                            $form_login.find('.response-output').addClass(status).html(message);

                            setTimeout(function() {
                                $form_login.find('.response-output').removeClass(status).html('');
                            }, 30000);

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


    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i<ca.length;i++){
            var c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
        }
        return null;
    }
    function eraseCookie(name) {
        document.cookie = name + "=; Max-Age=-1; path=/";
    }


    var $form_register = $('#alsernet-register');
    var language_register = $('input[name="_alsernetauth_language"]', $form_register).val(); 
    $.extend($.validator.messages, messages[language_register]);

    $form_register.validate({
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
            date: {
				required: false,
				date: true
            },
            password: {
				required: true,
				minlength: 8
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
			date: {
                required: $.validator.messages.required,
                date: $.validator.messages.date
            },
            password: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
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
            $('#condition').prop('checked', false); 
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

            var formData = $form_register.serializeArray();
            var links = $('input[name="_alsernetauth_link"]', $form_register).val();
            var link =  window.location.origin + '/' + links;
            var iso = $('input[name="_alsernetauth_language"]', $form_register).val();
            var action = $('input[name="_alsernetauth_action"]', $form_register).val(); 

            var sports = $('input[name="sports[]"]:checked', $form_register)
                .map(function() {
                    return $(this).val();
                }).get();

            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });
            formData.push({ name: "sports", value: sports.join(',') });

            $.ajax({
                url: link,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {
					
                    var status = data.status;
					var message = data.message;
					
					switch (status) {
						case "success":

                            clearFormsRegister($form_register);
                            outToggleCheck($form_register);
                            outToggleCheckSports($form_register,false);


                            var referrer = document.referrer;
                            var currentUrl = window.location.href;
                            var myAccountUrl = data.url;

                            var cookie = getCookie('wishlist_redirect');

                            if (cookie) {
                                var data = JSON.parse(decodeURIComponent(cookie));
                                var redirectUrl = data.redirect_url;

                                eraseCookie('wishlist_redirect');
                                window.location.replace(redirectUrl);
                            } else {
                                window.location.replace(myAccountUrl);
                            }


							break;
						case "warning":
							$form_register.find('.response-output').addClass(status).html(message);

							setTimeout(function() {
								$form_register.find('.response-output').removeClass(status).html('');
							}, 30000);

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

    var $form_resetpassword = $('#alsernet-resetpassword');
    var language_resetpassword = $('input[name="_alsernetauth_language"]', $form_resetpassword).val();
    $.extend($.validator.messages, messages[language_resetpassword]);

    $form_resetpassword.validate({
        ignore: "",
        rules: {
            email: {
                required: true,
                email: true
            }
        },
		messages: {
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

            var formData = $form_resetpassword.serializeArray();
            var links = $('input[name="_alsernetauth_link"]', $form_resetpassword).val();

            var link =  window.location.origin + '/' + links;
            var iso = $('input[name="_alsernetauth_language"]', $form_resetpassword).val();
            var action = $('input[name="_alsernetauth_action"]', $form_resetpassword).val();
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            $.ajax({
                url: link,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
					var message = data.message;

					switch (status) {
						case "success":

							$form_resetpassword.find('.response-output').addClass(status).html(message);

							setTimeout(function() {
								$form_resetpassword.find('.response-output').removeClass(status).html('');
							}, 30000);

							break;
						case "warning":

							$form_resetpassword.find('.response-output').addClass(status).html(message);

							setTimeout(function() {
								$form_resetpassword.find('.response-output').removeClass(status).html('');
							}, 30000);

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


    var $form_changepassword = $('#alsernet-changepassword');
    var language_changepassword  = $('input[name="_alsernetauth_language"]', $form_changepassword).val();
    $.extend($.validator.messages, messages[language_changepassword]);

    $form_changepassword.validate({
        ignore: "",
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
				required: true,
				minlength: 8
            },
            confirmation: {
				required: true,
				minlength: 8
            },
        },
		messages: {
            email: {
                required: $.validator.messages.required,
                email: $.validator.messages.email
            },
            password: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
            },
            confirmation: {
                required: $.validator.messages.required,
                minlength: $.validator.messages.minlength
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

            var formData = $form_changepassword.serializeArray();
            var links = $('input[name="_alsernetauth_link"]', $form_changepassword).val();
            var link =  window.location.origin + '/' + links;
            var iso = $('input[name="_alsernetauth_language"]', $form_changepassword).val();
            var action = $('input[name="_alsernetauth_action"]', $form_changepassword).val();
            formData.push({ name: "action", value: action });
            formData.push({ name: "iso", value: iso });

            $.ajax({
                url: link,
                type: "POST",
                data: formData,
                dataType: 'json',
                success: function(data) {

                    var status = data.status;
					var message = data.message;

					switch (status) {
						case "success":

							$form_changepassword.find('.response-output').addClass(status).html(message);


							setTimeout(function() {
								$form_changepassword.find('.response-output').removeClass(status).html('');
                                window.location.replace("/iniciar-sesion");
							}, 30000);

							break;

						case "warning":

							$form_changepassword.find('.response-output').addClass(status).html(message);

							setTimeout(function() {
								$form_changepassword.find('.response-output').removeClass(status).html('');
							}, 30000);

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

	function clearFormsPassword(form) {
		$('input[name="email"]', $form).val("");
	}
	function clearFormsLogin(form) {
		$('input[name="password"]', $form).val("");
		$('input[name="email"]', $form).val("");
	}

	function clearFormsRegister(form) {
		$('input[name="firstname"]', $form).val("");
        $('input[name="lastname"]', $form).val("");
		$('input[name="date"]', $form).val("");
		$('input[name="password"]', $form).val("");
		$('input[name="email"]', $form).val("");
	}

    $form_register.find('.form-check-input[required]').on('click', function() {
        window.toggleSubmit($form_register);
    });

    toggleSubmit($form_register);

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



    function validateEmailRegister() {

        var email = $('.alsernet-register [name="email"]').val();
        var language = $('.alsernet-register [name="_alsernetauth_language"]').val();

        console.log(email,language);

        $.ajax({
            url: 'modules/alsernetauth/controllers/routes.php?action=validateemail',
            type: "POST",
            data: {
                email: email,
                iso: language
            },
            dataType: 'json',
            success: function(data) {

                console.log(data);
                var status = data.status;
                var message = data.message;

                var $emailErrorLabel = $('.alsernet-register').find('label[for="emails"]');

                switch (status) {
                    case "success":

                        $emailErrorLabel.removeClass('d-none').html(message);

                        setTimeout(function() {
                            $emailErrorLabel.addClass('d-none').html('');
                        }, 30000);
                        break;

                }

            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
            }
        });

    }

    $('.alsernet-register [name="email"]').on('blur', function () {

        var email = $(this).val();

        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (emailRegex.test(email)) {
            validateEmailRegister();
        }

    });

    $('.alsernet-register [name="email"]').on('input', function() {
        var $emailErrorLabel = $('.alsernet-register').find('label[for="emails"]');
        $emailErrorLabel.addClass('d-none').html('');
    });


});
