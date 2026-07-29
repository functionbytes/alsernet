(function() {
    'use strict';

    if (typeof window.addressStepHandler === 'undefined') {

        class LoginStepHandler {
            constructor(checkoutManager) {
                this.manager = checkoutManager;
                this.initialized = false;
            }

            init() {
                if (this.initialized) return;

                this.bindEvents();
                this.setupValidations();
                this.initialized = true;
            }


            bindEvents() {
                $(document).off('.loginStep');
                $(document).off('.registerValidation');

                $(document).off('click', '.step-login .next');


                $(document).on('click.loginStep', '.redirect-to-login, .redirect-to-register', (e) => {
                    e.preventDefault();

                    const $trigger = $(e.currentTarget);
                    const newLabel = $trigger.data('label') || '';

                    // ahora "this" sí es la instancia de la clase
                    this.setLoginHeading(newLabel);

                    if ($trigger.hasClass('redirect-to-login')) {
                        this.showLoginForm(e);
                    } else {
                        this.showRegisterForm(e);
                    }
                });


                // Interceptar submits
                $(document).on('submit.loginStep', '#login-checkout', (e) => {
                    e.preventDefault();
                    const $form = $(e.currentTarget);
                    if ($form.valid()) this.performLogin($form);
                });

                $(document).on('submit.loginStep', '#register-checkout', (e) => {
                    console.log('📝 Register form submit event fired');
                    e.preventDefault();
                    const $form = $(e.currentTarget);
                    const isValid = $form.valid();

                    if (isValid) {
                        console.log('✅ Form is valid, calling performRegister');
                        this.performRegister($form);
                    } else {
                        if (typeof $.fn.validate === 'function') {
                            const validator = $form.validate();
                            console.log('🚨 Validation errors:', validator.errorList);
                        }
                    }
                });

                // Botón login
                $(document).on('click.loginStep', '#login-submit-btn', (e) => {
                    e.preventDefault();
                    console.log('🎯 Login submit button clicked');
                    $('#login-checkout').trigger('submit');
                });

                // Botón registro
                $(document).on('click.loginStep', '#register-checkout button[type="submit"]', (e) => {
                    e.preventDefault();
                    console.log('🎯 Register submit button clicked');
                    $('#register-checkout').trigger('submit');
                });

                // Botón next - validar autenticación antes de navegar
                $(document).on('click.loginStep', '.step-login .next', (e) => {
                    e.preventDefault();
                    e.stopImmediatePropagation(); // Evitar que otros handlers intercepten
                    console.log('🎯 LOGIN STEP: Next button clicked - validating authentication');

                    // Primero validar si el usuario está autenticado
                    this.checkAuthenticationStatus()
                        .then((isAuthenticated) => {
                            if (isAuthenticated) {
                                console.log('✅ User is authenticated, navigating to address');
                                if (window.checkoutNavigator) {
                                    window.checkoutNavigator.navigateToStepDirect('address', true)
                                        .then(() => {
                                            console.log('✅ Successfully navigated to address step');
                                        })
                                        .catch((error) => {
                                            console.warn('⚠️ Failed to navigate to address:', error);
                                            window.checkoutNavigator.navigateToStep('address', true, true);
                                        });
                                }
                            } else {
                                console.log('❌ User not authenticated, staying in login step');
                                this.showNotification('Por favor inicia sesión o regístrate para continuar', 'warning');
                                // Mantener el accordión abierto en login
                                $('#collapseLogin').collapse('show');
                            }
                        })
                        .catch((error) => {
                            console.error('❌ Error checking authentication:', error);
                            this.showNotification('Error al verificar autenticación', 'danger');
                        });
                });
            }

            setupValidations() {
                // Check if jQuery Validate is available
                if (typeof $.fn.validate !== 'function') {
                    console.warn('⚠️ jQuery Validate plugin not available, skipping login form validation setup');
                    return;
                }

                // Get messages from backups.js for multilingual support
                const messages = (typeof window.settings !== 'undefined' && window.settings.getMessages)
                    ? window.settings.getMessages()
                    : {};

                // Ensure custom validation methods are loaded from backups BEFORE using them
                if (typeof window.settings !== 'undefined' && typeof window.settings.addCustomValidationMethods === 'function') {
                    window.settings.addCustomValidationMethods();
                }

                // Login
                $('#login-checkout').validate({
                    rules: {
                        email: { required: true, email: true },
                        password: { required: true, minlength: 6 }
                    },
                    messages: {
                        email: {
                            required: messages.required || "Este campo es obligatorio.",
                            email: messages.email || "Por favor, escribe una dirección de correo válida."
                        },
                        password: {
                            required: messages.required || "Este campo es obligatorio.",
                            minlength: messages.minlength || "Por favor, no escribas menos de {0} caracteres."
                        }
                    },
                    submitHandler: (form) => this.performLogin($(form))
                });


                // Always allow guest registration (password is optional)
                const guestAllowed = true; // Always true - password is optional

                const registerRules = {
                    firstname: {
                        required: true,
                        lettersandspace: true,
                        minlength: 2
                    },
                    lastname: {
                        required: true,
                        lettersandspace: true,
                        minlength: 2
                    },
                    email: {
                        required: true,
                        email: true,
                        remote: {
                            url: window.checkoutManager.endpoints.checkout.authvalidateemail,
                            type: "POST",
                            dataType: "json",
                            data: {
                                emails: $('#register-checkout').find('input[name="email"]').val()
                            },
                            dataFilter: function(response) {
                                let resp = JSON.parse(response);
                                if (resp.status === "success") {
                                    // Asigna el email del formulario de registro al de login
                                    const email = $('#register-checkout input[name="email"]').val();
                                    $('#login-checkout input[name="email"]').val(email);

                                    // Construye el mensaje de error con el enlace
                                    const errorMessage = `${$.validator.messages.emailvalidate} <a class="redirect-to-login">${$.validator.messages.returnlogin}</a>`;

                                    // Se retorna el mensaje envuelto en comillas (como requiere jQuery Validate)
                                    return JSON.stringify(errorMessage);

                                }
                                return "true";
                            }
                        }
                    },
                    'sports[]': { required: true },
                    condition: { required: true }
                };

                // Only require password if guest checkout is NOT allowed
                if (!guestAllowed) {
                    registerRules.password = { required: true, minlength: 8 };
                }

                console.log('🔧 Register validation rules (guest always allowed):', { guestAllowed, rules: registerRules });

                // Register form validation messages
                const registerMessages = {
                    firstname: {
                        required: messages.required || "Este campo es obligatorio.",
                        lettersandspace: messages.lettersandspace || "Solo se permiten letras, espacios y guiones para nombres compuestos.",
                        minlength: messages.minlength || "Por favor, no escribas menos de {0} caracteres."
                    },
                    lastname: {
                        required: messages.required || "Este campo es obligatorio.",
                        lettersandspace: messages.lettersandspace || "Solo se permiten letras, espacios y guiones para nombres compuestos.",
                        minlength: messages.minlength || "Por favor, no escribas menos de {0} caracteres."
                    },
                    email: {
                        required: messages.required || "Este campo es obligatorio.",
                        email: messages.email || "Por favor, escribe una dirección de correo válida.",
                        remote: messages.emailvalidate || "Este email ya está registrado."
                    },
                    'sports[]': {
                        required: messages.required || "Este campo es obligatorio."
                    },
                    condition: {
                        required: messages.required || "Debe aceptar las condiciones."
                    }
                };

                // Add password validation messages if not guest allowed
                if (!guestAllowed) {
                    registerMessages.password = {
                        required: messages.required || "Este campo es obligatorio.",
                        minlength: messages.minlength || "Por favor, no escribas menos de {0} caracteres."
                    };
                }

                $('#register-checkout').validate({
                    rules: registerRules,
                    messages: registerMessages,
                    submitHandler: (form) => {
                        console.log('🚀 Form validation passed, calling performRegister');
                        this.performRegister($(form));
                    }
                });

                this.setupRegisterButtonToggle();
            }

            setupRegisterButtonToggle() {
                const $form = $('#register-checkout');
                const $submitBtn = $form.find('button[type="submit"]');

                const toggleSubmitButton = () => {
                    const isConditionChecked = $('#condition').is(':checked');
                    $submitBtn.prop('disabled', !isConditionChecked);
                };

                // Estado inicial
                toggleSubmitButton();

                // Escuchar cambios en el checkbox de condiciones
                $(document).on('change.registerValidation', '#condition', toggleSubmitButton);

                // También escuchar cambios en otros campos requeridos si es necesario
                $(document).on('change.registerValidation', '#register-checkout input[name="condition"]', toggleSubmitButton);
            }

            async checkAuthenticationStatus() {
                try {
                    // Usar las validaciones del checkout manager para verificar autenticación
                    const validationResult = await window.checkoutManager.executeValidations(false, false);

                    console.log('🔍 Authentication check result:', validationResult);

                    // Check authentication status more thoroughly
                    const isAuthenticated = validationResult.authentication === true;
                    const hasErrors = validationResult.errors && validationResult.errors.hasError;
                    const isLoginStep = validationResult.step === 'login';

                    console.log('🔍 Authentication analysis:', {
                        authentication: validationResult.authentication,
                        hasErrors: hasErrors,
                        step: validationResult.step,
                        isAuthenticated: isAuthenticated
                    });

                    // If we're still in login step with no authentication true, user is not authenticated
                    if (isLoginStep && !isAuthenticated) {
                        return false;
                    }

                    // If authentication is explicitly true, user is authenticated
                    if (isAuthenticated) {
                        return true;
                    }

                    // Check if guest checkout is allowed and no login errors exist
                    const guestAllowed = window.checkoutConfig?.guest_allowed || false;
                    if (guestAllowed && !hasErrors) {
                        console.log('🔍 Guest checkout allowed, treating as authenticated');
                        return true;
                    }

                    return false;
                } catch (error) {
                    console.error('❌ Error checking authentication status:', error);
                    return false;
                }
            }

            performLogin($form) {
                const payload = $form.serialize();             // 1) Serializa ANTES de deshabilitar
                const $btn = $('#login-submit-btn');

                this.setFormLoading($form, $btn, true);

                $.ajax({
                    url: window.checkoutManager.endpoints.checkout.authlogin,
                    method: 'POST',
                    data: payload,
                    dataType: 'json'
                })
                    .done((data) => data.status === "success"
                        ? this.handleSuccess(data)
                        : this.handleError($form, data.message, 'warning'))
                    .fail(() => this.handleError($form, 'Error de conexión', 'danger'))
                    .always(() => this.setFormLoading($form, $btn, false));
            }

            performRegister($form) {
                console.log('🎯 performRegister called!');
                const payload = $form.serialize();             // 1) Serializa ANTES de deshabilitar
                const $btn = $form.find('button[type="submit"]');

                console.log('📋 Form payload:', payload);
                console.log('🔗 URL:', window.checkoutManager.endpoints.checkout.authregister);

                this.setFormLoading($form, $btn, true);

                $.ajax({
                    url: window.checkoutManager.endpoints.checkout.authregister,
                    method: 'POST',
                    data: payload,
                    dataType: 'json'
                })
                    .done((data) => {
                        console.log('✅ AJAX success, response:', data);
                        return data.status === "success"
                            ? this.handleSuccess(data)
                            : this.handleError($form, data.message, 'warning');
                    })
                    .fail((xhr, status, error) => {
                        console.error('❌ AJAX failed:', { xhr, status, error });
                        this.handleError($form, 'Error de conexión', 'danger');
                    })
                    .always(() => {
                        console.log('🏁 AJAX completed');
                        this.setFormLoading($form, $btn, false);
                    });
            }

            async handleSuccess(data) {
                if (data.message) this.showNotification(data.message, 'success');

                // 🧾 GTM: Trigger begin_checkout event after successful login/register
                // try {
                //     if (window.GTMCheckoutHelper) {
                //         console.log('🧾 Triggering GTM begin_checkout event after login/register');
                //         await window.GTMCheckoutHelper.trackCheckoutEvent('begin_checkout', {
                //             options: {
                //                 checkout_step: '0'
                //             }
                //         });
                //     }
                // } catch (gtmError) {
                //     console.warn('⚠️ GTM tracking failed for begin_checkout:', gtmError);
                // }

                // Navega primero y dispara begin_checkout en ADDRESS con step "1"
                const fireBeginCheckout = async () => {
                  try {
                    if (window.GTMCheckoutHelper?.trackBeginCheckout) {
                      await window.GTMCheckoutHelper.trackBeginCheckout(); // (lo ajustamos a "1" en el helper – ver punto 2)
                    } else if (window.GTMCheckoutHelper?.trackCheckoutEvent) {
                      await window.GTMCheckoutHelper.trackCheckoutEvent('begin_checkout', { options: { checkout_step: '1' } });
                    } else if (window.gtmExecuteWithBackendData) {
                      await window.gtmExecuteWithBackendData('begin_checkout', { checkout_step: '1' });
                    }
                  } catch (e) {
                    console.warn('⚠️ begin_checkout after login failed:', e);
                  }
                };

                // Directly navigate to address step after successful login/register
                $('body').removeClass('checkout-blocked');

                if (window.checkoutNavigator) {
                    // Use navigateToStepDirect to skip validation and go directly to address
                    // window.checkoutNavigator.navigateToStepDirect('address', true)
                    //     .then(() => {
                    //         console.log('✅ Successfully navigated to address step after login/register');
                    //     })
                    window.checkoutNavigator.navigateToStepDirect('address', true)
                        .then(async () => {
                            console.log('✅ Navigated to address; firing begin_checkout(step=1)');
                            await fireBeginCheckout();
                        })
                        .catch((error) => {
                            console.warn('⚠️ Failed to navigate to address, trying fallback:', error);
                            // Fallback: try regular navigation
                            window.checkoutNavigator.navigateToStep('address', true, true);
                        });
                } else {
                    console.warn('⚠️ CheckoutNavigator not available');
                }
            }

            handleError($form, message, type = 'warning') {
                const $output = $form.find('.response-output');

                $output
                    .removeClass('warning danger success') // limpia estados anteriores
                    .addClass(type)                        // warning | danger | success
                    .html(message)
                    .fadeIn();

                setTimeout(() => {
                    $output.fadeOut(() => {
                        $output.removeClass(type).html('');
                    });
                }, 3000);
            }

            setLoginHeading(label) {
                const $btn = $('#headingLogin .accordion-button');
                const $label = $btn.find('.label');
                if ($label.length) {
                    $label.text(label);
                } else {
                    $btn.text(label);
                }
            }
            showLoginForm() {
                const newLabel = $(this).data('label')
                $('#headingLogin .accordion-button').text(newLabel);
                $('.checkout-login-form').removeClass('d-none').addClass('d-visible');
                $('.checkout-register-form').addClass('d-none');
                $('#login-checkout input[name="email"]').first().focus();
            }

            showRegisterForm() {

                const newLabel = $(this).data('label')
                $('#headingLogin .accordion-button').text(newLabel);

                $('.checkout-register-form').removeClass('d-none').addClass('d-visible');
                $('.checkout-login-form').addClass('d-none');
                $('#register-checkout input[name="firstname"]').first().focus();


            }

            setFormLoading($form, $btn, isLoading) {
                $form.find('input, button').prop('disabled', isLoading);
                if (isLoading) {
                    if (!$btn.data('original-text')) $btn.data('original-text', $btn.html());
                    $btn.html('...');
                } else {
                    $btn.html($btn.data('original-text'));
                }
            }

            showNotification(message, type = 'info') {
                const $n = $(`<div class="checkout-notification ${type}">${message}</div>`);
                $('body').append($n);
                setTimeout(() => $n.fadeOut(() => $n.remove()), 3000);
            }
        }



        window.LoginStepHandler = LoginStepHandler;

        if (!(window.loginStepHandler instanceof LoginStepHandler)) {
            window.loginStepHandler = new LoginStepHandler(window.checkoutManager);
        }
        console.log('📤 LoginStepHandler instance ready');

        window.initLoginStep = function () {
            console.log('🔧 Global initLoginStep called');
            if (!(window.loginStepHandler instanceof LoginStepHandler)) {
                window.loginStepHandler = new LoginStepHandler(window.checkoutManager);
            }
            if (!window.loginStepHandler.initialized) {
                window.loginStepHandler.init();
            }

            // Asegurar que los eventos de validación del botón se configuren
            setTimeout(() => {
                if ($('#register-checkout').length && typeof window.loginStepHandler.setupRegisterButtonToggle === 'function') {
                    window.loginStepHandler.setupRegisterButtonToggle();
                }
            }, 100);
        };

    }

})();
