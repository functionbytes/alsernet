/**
 * Checkout Navigation System
 *
 * Manages navigation between checkout steps
 * Handles UI updates, state transitions, and address management
 */

// Prevent redeclaration if class already exists
if (typeof window.CheckoutNavigator === 'undefined') {

    class CheckoutNavigator {
        constructor(checkoutManager) {
            this.manager = checkoutManager || window.checkoutManager;
            this.currentStep = null;
            this.previousStep = null;
            this.steps = ['login', 'address', 'delivery', 'payment'];
            this.pendingTransition = null;

            // Flag to prevent double navigation
            this.navigating = false;

            // Flag to prevent double event binding
            this._eventsBound = false;

            // Flag to prevent double validations
            this._validationInProgress = false;

            this.init();
        }

        init() {

            this.currentStep = this.getCurrentStepFromDOM() || 'login';
            console.log(`🧭 CheckoutNavigator initialized on step: ${this.currentStep}`);

            // Bind navigation events
            this.bindEvents();

        }

        bindEvents() {

            console.log('🔗 Binding checkout events...');

            const self = this;

            // Prevent binding events multiple times - comprehensive cleanup
            $(document).off('.checkout .checkout-navigation');

            // Flag to prevent double binding
            if (this._eventsBound) {
                console.log('⚠️ Events already bound, skipping duplicate binding');
                return;
            }

            // Step navigation
            $(document).on('click.checkout-navigation', '.checkout-step-nav, .step-indicator', this.handleStepNavigation.bind(this));

            // Previous step navigation
            $(document).on('click.checkout-navigation', '.previous', this.handlePreviousStep.bind(this));

            // Next step buttons - pre-submit validation (exclude delivery forms which have their own handler)
            $(document).on('click.checkout-navigation', 'button.next[type="submit"]:not(#js-delivery button.next):not(.step-checkout-delivery button.next), .btn.next[type="submit"]:not(#js-delivery .btn.next):not(.step-checkout-delivery .btn.next)', this.handleNextButtonClick.bind(this));

            // Accordion navigation
            $(document).on('click.checkout-navigation', '.accordion-button', this.handleAccordionClick.bind(this));

            // Mark events as bound
            this._eventsBound = true;

            console.log('✅ CheckoutNavigator events bound');
        }


        updateStepUI(step) {
            console.log(`🎨 Updating UI for step: ${step}`);

            // GTM tracking for step navigation (only for begin_checkout when moving to delivery)
            // if (step === 'login') {
            //     this.trackBeginCheckout();
            // }

            // Store current state for potential revert
            this._previousUIState = {
                step: this.currentStep,
                indicators: $('.step-indicator.active').map(function() { return $(this).data('step'); }).get(),
                activeSteps: $('.checkout-step.active').map(function() { return $(this).data('step'); }).get()
            };

            // Update step indicators
            const $stepIndicators = $('.step-indicator');
            $stepIndicators.removeClass('active current');

            // Mark all previous steps and current step as active
            const stepIndex = this.steps.indexOf(step);
            for (let i = 0; i <= stepIndex; i++) {
                $(`.step-indicator[data-step="${this.steps[i]}"]`).addClass('active');
            }
            $(`.step-indicator[data-step="${step}"]`).addClass('current');

            // Update step content
            const $checkoutSteps = $('.checkout-step');
            $checkoutSteps.removeClass('active');
            $(`.checkout-step[data-step="${step}"]`).addClass('active');

            // Update accordion UI
            this.updateAccordionUI(step);

            // Load step content directly if needed
            console.log(`🔧 Loading step content for ${step}`);
        }

        updateStepState(step, isCurrent) {
            if (isCurrent && step) {
                // Update instance state
                this.currentStep = step;

                // Update global state for legacy compatibility
                window.currentStep = step;
                $('body').attr('data-checkout-step', step);

                // Update CheckoutManager state if available
                if (this.manager && this.manager.state) {
                    this.manager.state.currentStep = step;
                }

                console.log(`📍 Step state updated to: ${step}`);
            }
        }

        loadStepDirect(step, isCurrent, skipValidation = false) {
            const module = this.getModuleUrlBase();
            const iso = this.getISO();
            const url = `${module}/alsernetshopping/routes?modalitie=checkout&action=step&step=${step}&iso=${iso}`;

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    success: (response) => {
                        if (response.status === 'success') {
                            this.handleStepResponse(response, step, isCurrent, skipValidation)
                                .then(() => resolve(response))
                                .catch(reject);
                        } else {
                            const errorMsg = response.message || 'Server response error';
                            console.error('Step loading failed:', errorMsg);
                            this.handleCheckoutError(errorMsg);
                            reject(new Error(errorMsg));
                        }
                    },
                    error: (xhr, status, error) => {
                        const errorMsg = 'Connection error loading checkout step';
                        console.error('AJAX error loading step:', error);
                        this.handleCheckoutError(errorMsg);
                        reject(new Error(errorMsg));
                    }
                });
            });
        }

        updateAccordionUI(step) {
            console.log(`📋 Updating accordion for: ${step}`);

            // First, smoothly close all open accordions with animation
            const $openAccordions = $('.accordion-collapse.show');

            if ($openAccordions.length > 0) {
                $openAccordions.each(function () {
                    const $collapse = $(this);
                    const $relatedButton = $('button[data-bs-target="#' + $collapse.attr('id') + '"]');

                    // Use Bootstrap's collapse instance if available for smooth animation
                    if (window.bootstrap && window.bootstrap.Collapse) {
                        const collapseInstance = window.bootstrap.Collapse.getOrCreateInstance(this, { toggle: false });
                        collapseInstance.hide();
                    } else {
                        // Fallback for non-Bootstrap environments
                        $collapse.removeClass('show');
                    }

                    $relatedButton.addClass('collapsed').attr('aria-expanded', 'false');
                });

                // Wait for close animation to complete before opening new accordion
                setTimeout(() => {
                    this.openTargetAccordion(step);
                }, 200);
            } else {
                // No accordions open, directly open target
                this.openTargetAccordion(step);
            }
        }

        openTargetAccordion(step) {
            // Open the target accordion
            const $button = $('.accordion-button[data-slug="' + step + '"]');
            if ($button.length) {
                const target = $button.data('bs-target');
                if (target) {
                    const $target = $(target);

                    // Use Bootstrap's collapse instance if available for smooth animation
                    if (window.bootstrap && window.bootstrap.Collapse) {
                        const collapseInstance = window.bootstrap.Collapse.getOrCreateInstance($target[0], { toggle: false });

                        // Set up scroll after animation completes
                        $target.one('shown.bs.collapse', () => {
                            this.scrollToAccordionWithDelay($button);
                        });

                        collapseInstance.show();
                    } else {
                        // Fallback for non-Bootstrap environments
                        $target.addClass('show');
                        this.scrollToAccordionWithDelay($button);
                    }

                    $button.removeClass('collapsed').attr('aria-expanded', 'true');
                }
            }
        }

        scrollToAccordionWithDelay($button) {
            // Small delay to ensure accordion is fully rendered
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: $button.offset().top - 100
                }, 300);
            }, 100);
        }

        revertStepUI() {
            if (!this._previousUIState) return;

            // Restore step indicators
            $('.step-indicator').removeClass('active current');
            this._previousUIState.indicators.forEach(step => {
                $(`.step-indicator[data-step="${step}"]`).addClass('active');
            });
            $(`.step-indicator[data-step="${this._previousUIState.step}"]`).addClass('current');

            // Restore active steps
            $('.checkout-step').removeClass('active');
            this._previousUIState.activeSteps.forEach(step => {
                $(`.checkout-step[data-step="${step}"]`).addClass('active');
            });

            // Restore accordion
            this.updateAccordionUI(this._previousUIState.step);

            // Clear saved state
            this._previousUIState = null;
        }

        bindStepEvents(step, logged) {
            console.log(`📋 Checking step handlers for: ${step}, logged: ${logged}`);

            switch(step) {
                case 'login':
                    // Check if handler already exists, if not initialize
                    if (window.loginStepHandler && !window.loginStepHandler.initialized) {
                        window.loginStepHandler.init();
                    }
                    break;
                case 'address': {
                    const Ctor = window.AddressStepHandler;
                    if (Ctor && (!window.addressStepHandler || !(window.addressStepHandler instanceof Ctor))) {
                        window.addressStepHandler = new Ctor(window.checkoutManager);
                    }
                    if (window.addressStepHandler && !window.addressStepHandler.initialized) {
                        window.addressStepHandler.init();
                    }
                    break;
                }
                case 'delivery':
                    try {
                        const Ctors = window.DeliveryStepHandler;
                        let needsNewInstance = false;

                        if (!Ctors) {
                            console.warn('DeliveryStepHandler class not available yet');
                            break;
                        }

                        if (!window.deliveryStepHandler) {
                            needsNewInstance = true;
                        } else {
                            try {
                                needsNewInstance = !(window.deliveryStepHandler instanceof Ctors);
                            } catch (e) {
                                console.warn('instanceof check failed, creating new instance:', e);
                                needsNewInstance = true;
                            }
                        }

                        if (needsNewInstance) {
                            window.deliveryStepHandler = new Ctors(window.checkoutManager);
                        }

                        if (window.deliveryStepHandler) {
                            // Always reinitialize delivery step to ensure event handlers are bound
                            window.deliveryStepHandler.init(true); // force = true
                        }
                    } catch (error) {
                        console.error('Error initializing delivery step:', error);
                    }
                    break;
                case 'payment':
                    const Ctort = window.PaymentStepHandler;
                    if (Ctort && (!window.paymentStepHandler || !(window.paymentStepHandler instanceof Ctort))) {
                        window.paymentStepHandler = new Ctort(window.checkoutManager);
                    }
                    if (window.paymentStepHandler && !window.paymentStepHandler.initialized) {
                        window.paymentStepHandler.init();
                    }
                    break;
                default:
                    console.log(`No specific event bindings for step: ${step}`);
            }
        }

        initAddressStep() {
            // Esta función se mantiene para compatibilidad, pero ahora delega a la clase
            if (typeof AddressStepHandler !== 'undefined') {
                if (!window.addressStepHandler) {
                    window.addressStepHandler = new AddressStepHandler(window.checkoutManager);
                }
                window.addressStepHandler.init();
            } else {
                console.warn('AddressStepHandler class not available');
            }
        }

        initDeliveryStep() {
            // Esta función se mantiene para compatibilidad, pero ahora delega a la clase
            if (typeof DeliveryStepHandler !== 'undefined') {
                if (!window.deliveryStepHandler) {
                    window.deliveryStepHandler = new DeliveryStepHandler(window.checkoutManager);
                }
                window.deliveryStepHandler.init();
            } else {
                console.log('DeliveryStepHandler not available - might be in a separate file');
            }
        }

        initLoginStep() {
            // Esta función se mantiene para compatibilidad, pero ahora delega a la clase
            if (typeof LoginStepHandler !== 'undefined') {
                if (!window.loginStepHandler) {
                    window.loginStepHandler = new LoginStepHandler(window.checkoutManager);
                }
                window.loginStepHandler.init();
            } else {
                console.log('LoginStepHandler not available - might be in a separate file');
            }
        }

        initPaymentStep() {
            // Esta función se mantiene para compatibilidad, pero ahora delega a la clase
            if (typeof PaymentStepHandler !== 'undefined') {
                if (!window.paymentStepHandler) {
                    window.paymentStepHandler = new PaymentStepHandler(window.checkoutManager);
                }
                window.paymentStepHandler.init();
            } else {
                console.log('PaymentStepHandler not available - might be in a separate file');
            }
        }

        /**
         * Track begin_checkout event when moving to delivery step
         */
        async trackBeginCheckout() {
            try {
                console.log('🚀 Tracking begin_checkout with fresh data');

                // Use GTM helper with fresh data sync
                if (window.GTMCheckoutHelper) {
                    // await window.GTMCheckoutHelper.trackEvent('begin_checkout', {
                    await window.GTMCheckoutHelper.trackCheckoutEvent('begin_checkout', {
                        options: {
                            checkout_step: '1'
                        }
                    });
                } else if (window.gtmExecuteWithBackendData) {
                    await window.gtmExecuteWithBackendData('begin_checkout', {
                        checkout_step: '1'
                    });
                }

                console.log('✅ GTM begin_checkout event triggered');

            } catch (error) {
                console.error('❌ Error tracking begin_checkout:', error);
            }
        }

        /**
         * Track step navigation with GTM (DEPRECATED - only used for reference)
         */
        async trackStepNavigation(step) {
            try {
                console.log(`🎯 Tracking GTM for step navigation: ${step}`);

                // Map checkout steps to GTM events
                const stepEventMap = {
                    'login': 'page_view',
                    'address': 'page_view',
                    'delivery': 'begin_checkout', // Most important for funnel
                    'payment': 'add_payment_info'
                };

                const eventType = stepEventMap[step];
                if (!eventType) {
                    console.log(`ℹ️ No GTM event mapped for step: ${step}`);
                    return;
                }

                // Special handling for begin_checkout
                if (eventType === 'begin_checkout') {
                    if (window.gtmExecuteByAction) {
                        await window.gtmExecuteByAction('checkout_start', {
                            options: {
                                checkout_step: '1'
                            }
                        });
                        console.log('✅ GTM begin_checkout event triggered');
                    } else if (window.gtmBeginCheckout) {
                        await window.gtmBeginCheckout();
                    }
                } else {
                    // Generic page view tracking
                    if (window.gtmExecuteFromAnywhere) {
                        await window.gtmExecuteFromAnywhere(eventType, {
                            customerData: {
                                page_type: 'checkout',
                                checkout_step: this.getCheckoutStepNumber(step),
                                page_title: this.getStepTitle(step),
                                page_url: window.location.href
                            }
                        });
                        console.log(`✅ GTM ${eventType} event triggered for step: ${step}`);
                    }
                }

            } catch (error) {
                console.error('❌ Error tracking step navigation:', error);
            }
        }

        /**
         * Get checkout step number for GTM
         */
        getCheckoutStepNumber(step) {
            const stepNumbers = {
                'login': '0',
                'address': '1',
                'delivery': '2',
                'payment': '3'
            };
            return stepNumbers[step] || '0';
        }

        /**
         * Get user-friendly step title
         */
        getStepTitle(step) {
            const stepTitles = {
                'login': 'Iniciar Sesión',
                'address': 'Direcciones',
                'delivery': 'Método de Envío',
                'payment': 'Método de Pago'
            };
            return stepTitles[step] || 'Checkout';
        }

        revertAddressUI() {
            if (this._previousAddressState) {
                $('.address-selector').html(this._previousAddressState);
                this._previousAddressState = null;
            }
        }

        updateAddressUI(addressData) {
            // Store current state for potential revert
            this._previousAddressState = $('.address-selector').html();

            // Update address display optimistically
            $(`.address-item[data-address="${addressData.id}"]`).addClass('selected');
            $(`.address-item:not([data-address="${addressData.id}"])`).removeClass('selected');
        }

        getPreviousStep() {
            // Ensure current step is correctly set
            this.currentStep = this.getCurrentStepFromDOM() || this.currentStep;

            const currentIndex = this.steps.indexOf(this.currentStep);
            if (currentIndex > 0) {
                return this.steps[currentIndex - 1];
            }
            return null; // Already at first step
        }


        getCurrentStepFromDOM() {
            // Method 1: Check active accordion
            const $activeAccordion = $('.accordion-button:not(.collapsed)');
            if ($activeAccordion.length) {
                const slug = $activeAccordion.data('slug');
                if (slug && this.steps.includes(slug)) {
                    return slug;
                }
            }

            // Method 2: Check body data attribute
            const bodyStep = $('body').attr('data-checkout-step');
            if (bodyStep && this.steps.includes(bodyStep)) {
                return bodyStep;
            }

            // Method 3: Check which step container is visible/active
            for (const step of this.steps) {
                const $stepContainer = $(`#checkout-${step}, .checkout-step[data-step="${step}"]`);
                if ($stepContainer.length && $stepContainer.is(':visible') && !$stepContainer.hasClass('d-none')) {
                    return step;
                }
            }

            // Method 4: Check URL or other indicators
            const url = window.location.href;
            if (url.includes('step=')) {
                const stepMatch = url.match(/step=([^&]+)/);
                if (stepMatch && this.steps.includes(stepMatch[1])) {
                    return stepMatch[1];
                }
            }

            return null;
        }

        getNextStep() {
            // Ensure current step is correctly set
            this.currentStep = this.getCurrentStepFromDOM() || this.currentStep;

            const currentIndex = this.steps.indexOf(this.currentStep);
            if (currentIndex < this.steps.length - 1) {
                return this.steps[currentIndex + 1];
            }
            return null; // Already at last step
        }

        handleStepNavigation(event) {
            event.preventDefault();
            let step = $(event.currentTarget).data('step');


            if (step) {
                this.navigateToStep(step,true,true);
            }
        }

        handlePreviousStep(event) {
            event.preventDefault();
            event.stopPropagation(); // Prevent backup handler from firing

            const previousStep = this.getPreviousStep();
            if (previousStep) {

                this.validateAndNavigate(previousStep).catch((error) => {
                    console.warn(`❌ Accordion navigation to ${slug} failed:`, error);
                });

            } else {
                console.log('Already at first step, cannot go back');
            }
        }


        async handleNextButtonClick(event) {
            console.log('🎯 Next button clicked, validating before action');

            const $button = $(event.currentTarget);
            const $form = $button.closest('form');
            const isSubmitButton = $button.attr('type') === 'submit';

            console.log(`📋 Button classes: ${$button.attr('class')}`);
            console.log(`📋 Button type: ${$button.attr('type')}, Is submit: ${isSubmitButton}`);
            console.log(`📋 Form found: ${$form.length > 0}, Form ID: ${$form.attr('id')}`);

            // Check if this is already a validated click to prevent infinite loops
            if ($button.data('validation-in-progress') || $button.data('validation-completed')) {
                console.log('🔄 Button already validated, allowing original action to proceed');
                return; // Let the original event handler run
            }

            // 🆕 NUEVO: Verificar si ya hay una validación global en progreso
            if (this._validationInProgress) {
                console.warn('⚠️ Global validation already in progress, ignoring button click');
                event.preventDefault();
                return;
            }

            // Always prevent default to handle validation first
            event.preventDefault();
            event.stopImmediatePropagation(); // Prevent other handlers from firing

            // Mark as validation in progress - both local and global
            $button.data('validation-in-progress', true);
            this._validationInProgress = true;

            try {
                // Delivery forms validation is now completely handled by DeliveryStepHandler

                // Validate checkout before allowing action - SKIP DOUBLE VALIDATION
                if (window.checkoutManager && typeof window.checkoutManager.executeValidations === 'function') {
                    console.log('🔄 Pre-action validation starting...');

                    const validationResult = await window.checkoutManager.executeValidations(true, false); // force=true, autoNavigate=false
                    console.log('📋 Pre-action validation result:', validationResult);

                    if (validationResult?.errors?.hasError === true) {
                        console.warn('❌ Validation errors found, blocking action');
                        console.log('📋 Error type:', validationResult.errors.type);
                        console.log('📋 Current step when error occurred:', window.currentStep);

                        window.checkoutManager.handleValidationErrors(validationResult.errors);

                        // Force navigate to address step if there are any errors - BUT only if not already on address
                        const currentStep = window.currentStep || '';
                        if (currentStep !== 'address') {
                            console.log('🏠 Forcing navigation to address step due to errors (current step:', currentStep, ')');
                            if (window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutStep === 'function') {
                                window.checkoutNavigator.loadCheckoutStep('address', true, true);
                            }
                        } else {
                            console.log('📍 Already on address step, showing modal without navigation');
                        }

                        // Reset validation flag on error
                        this._validationInProgress = false;
                        return; // Block original action
                    }

                    console.log('✅ Pre-action validation passed, allowing action');
                }

                // Mark validation as completed and trigger original action
                $button.removeData('validation-in-progress').data('validation-completed', true);

                // Validation passed - proceed with original action
                if (isSubmitButton && $form.length) {
                    const formId = $form.attr('id');

                    // Special handling for forms with specific handlers
                    if (formId === 'js-delivery' || $form.hasClass('step-checkout-delivery')) {
                        console.log('🚛 Delivery form detected, allowing CheckoutManager to handle it');
                        // Don't handle delivery forms here - let CheckoutManager.handleFormSubmission take over
                        // The form has checkout-form class so it will be handled by the proper handler
                        $form.trigger('submit');
                    } else if (formId === 'step-checkout-address' || $form.hasClass('step-checkout-address')) {
                        console.log('🏠 Address form detected, directly submitting form');
                        console.log('📋 Form validation plugin present:', !!$form.data('validator'));
                        console.log('📋 Form validation valid:', $form.valid ? $form.valid() : 'N/A');

                        // Try direct submit first
                        $form.trigger('submit');
                        console.log('✅ Form submit triggered');

                        // Fallback: If address step handler exists, call it directly
                        setTimeout(() => {
                            console.log('🔍 Checking for address step handler...');
                            console.log('🔍 window.addressStepHandler exists:', !!window.addressStepHandler);
                            console.log('🔍 handleFormSubmit function exists:', !!(window.addressStepHandler?.handleFormSubmit));

                            if (window.addressStepHandler && typeof window.addressStepHandler.handleFormSubmit === 'function') {
                                console.log('🔄 Calling address handler directly as fallback');
                                window.addressStepHandler.handleFormSubmit();
                            } else {
                                console.warn('❌ Address step handler not found, trying alternative navigation');
                                // Direct navigation as last resort
                                if (window.checkoutNavigator?.loadCheckoutStep) {
                                    console.log('🚀 Direct navigation to delivery as last resort');
                                    window.checkoutNavigator.loadCheckoutStep('delivery', true, true);
                                } else if (window.checkoutNavigator?.navigateToStepDirect) {
                                    console.log('🚀 Direct navigation to delivery using navigateToStepDirect');
                                    window.checkoutNavigator.navigateToStepDirect('delivery', true);
                                } else {
                                    console.error('❌ No navigation methods available');
                                    // Last resort: try to manually submit address data
                                    console.log('🔄 Attempting manual address data submission...');
                                    this.manualAddressSubmit($form);
                                }
                            }
                        }, 100);
                    } else {
                        console.log('🚀 Triggering form submit');
                        $form.trigger('submit');
                    }
                } else {
                    console.log('⚠️ Non-submit button clicked - letting step handlers manage it');
                    // Don't create new events - let step handlers handle their own buttons
                }

            } catch (error) {
                console.error('❌ Pre-action validation error:', error);
                console.log('⚠️ Proceeding with action despite validation error');

                // Mark validation as completed even on error
                $button.removeData('validation-in-progress').data('validation-completed', true);

                if (isSubmitButton && $form.length) {
                    const formId = $form.attr('id');

                    if (formId === 'js-delivery' || $form.hasClass('step-checkout-delivery')) {
                        console.log('🚛 Delivery form error fallback, allowing CheckoutManager to handle it');
                        $form.trigger('submit');
                    } else if (formId === 'step-checkout-address' || $form.hasClass('step-checkout-address')) {
                        console.log('🏠 Address form error fallback, directly submitting form');
                        $form.trigger('submit');
                        // Fallback: If address step handler exists, call it directly
                        setTimeout(() => {
                            if (window.addressStepHandler && typeof window.addressStepHandler.handleFormSubmit === 'function') {
                                console.log('🔄 Calling address handler directly as error fallback');
                                window.addressStepHandler.handleFormSubmit();
                            }
                        }, 100);
                    } else {
                        $form.trigger('submit');
                    }
                } else {
                    console.log('⚠️ Non-submit button error - step handlers should manage their buttons');
                    // Don't create new events on error either
                }
            } finally {
                // Clean up the validation flags after a short delay
                setTimeout(() => {
                    $button.removeData('validation-in-progress validation-completed');
                    // 🆕 NUEVO: Reset global validation flag
                    this._validationInProgress = false;
                }, 100);
            }
        }

        canNavigateToStep(targetStep) {
            const currentStepIndex = this.steps.indexOf(this.currentStep);
            const targetStepIndex = this.steps.indexOf(targetStep);

            // Allow navigation to current step or previous steps only
            return targetStepIndex <= currentStepIndex;
        }

        handleAccordionClick(event) {

            event.preventDefault();

            const $button = $(event.currentTarget);
            let slug = $button.data('slug');

            if ($('body').hasClass('checkout-blocked')) {
                console.log('🚫 Checkout blocked, accordion navigation disabled');
                return;
            }

            // Extract slug from target if not found in data-slug
            if (!slug) {
                const target = $button.data('bs-target') || '';
                if (target) {
                    slug = target.replace('#collapse', '').toLowerCase();
                }
            }

            const isCurrentlyOpen = !$button.hasClass('collapsed');

            if (isCurrentlyOpen) {
                console.log(`📋 Accordion ${slug} is already open, skipping navigation`);
                return;
            }

            if (slug && this.steps.includes(slug)) {
                // Check if navigation to this step is allowed
                if (!this.canNavigateToStep(slug)) {
                    console.log(`🚫 Navigation to future step ${slug} blocked. Current step: ${this.currentStep}`);

                    // Visual feedback - add a shake effect or highlight
                    $button.addClass('disabled-step');
                    setTimeout(() => {
                        $button.removeClass('disabled-step');
                    }, 100);

                    return;
                }

                console.log(`🎯 Accordion click navigation to: ${slug} (allowed)`);

                this.validateAndNavigate(slug)
                    .catch((error) => {
                        console.warn(`❌ Accordion navigation to ${slug} failed:`, error);
                    });
            } else {
                console.warn(`⚠️ Invalid accordion step: ${slug}`);
            }
        }

        closeAllAccordions() {
            $('.accordion-collapse.show').each(function() {
                const $collapse = $(this);
                const $relatedButton = $(`button[data-bs-target="#${$collapse.attr('id')}"]`);

                $collapse.removeClass('show');
                $relatedButton.addClass('collapsed').attr('aria-expanded', 'false');
            });

            console.log('📋 All accordions closed');
        }

        scrollToAccordionStep(slug) {
            // Try different collapse naming patterns
            const possibleSelectors = [
                `#collapse${slug.charAt(0).toUpperCase() + slug.slice(1)}`,
                `#collapse-${slug}`,
                `#${slug}-collapse`,
                `[data-bs-target*="${slug}"]`,
                `.accordion-button[data-slug="${slug}"]`,
                `#${slug}`,
                `.collapse[id*="${slug}"]`
            ];

            let $target = null;

            for (const selector of possibleSelectors) {
                $target = $(selector);
                if ($target.length > 0) {
                    console.log(`📍 Found accordion target with selector: ${selector}`);
                    break;
                }
            }

            if ($target && $target.length > 0) {
                // Get the first element and scroll to it
                const targetElement = $target[0];

                // Smooth scroll to the beginning of the collapse
                const offset = 100;
                const y = targetElement.offsetTop - offset;

                window.scrollTo({
                    top: y,
                    behavior: 'smooth'
                });

                console.log(`✅ Scrolled to accordion step: ${slug}`);
            } else {
                console.warn(`⚠️ Could not find accordion target for step: ${slug}`);
            }
        }

        loadCheckoutSummary() {
            console.log('📋 Loading checkout summary');
            const iso = this.getISO();
            const module = this.getModuleUrlBase();
            const link = `${module}/alsernetshopping/routes?modalitie=checkout&action=summary&iso=${iso}`;

            $.ajax({ cache: false, url: link })
                .done((response) => {
                    if (response.status === "success") {

                        $('.container-products').html(response.products || '');
                        $('.container-summary').html(response.summary || '');
                        $('.container-shipping').html(response.shipping || '');

                    }
                })
                .fail(() => console.warn("Error al cargar el resumen del carrito."));
        }


        getISO() {
            const segments = window.location.pathname.split('/');
            return (segments[1] && segments[1].length === 2) ? segments[1] : 'es';
        }

        getModuleUrlBase() {
            const iso = this.getISO();
            const prefix = (iso.toLowerCase() !== 'es') ? `/${iso}` : '';
            return `${prefix}/modules`;
        }

        handleCheckoutError(message) {
            console.error('Checkout error:', message);
            if (typeof settings?.showToast === 'function') {
                settings.showToast('error', message);
            } else {
                alert(message);
            }
        }

        handleEmptyCart() {
            console.log('Cart is empty');
            $('.checkout-container').addClass('d-none');
            $('.checkout-empty-container').removeClass('d-none');
        }

        openAccordion(step) {
            if (!step) return;

            const $button = $(`.accordion-button[data-slug="${step}"]`);
            if ($button.length === 0) return;

            const targetSelector = $button.data('bs-target');
            if (!targetSelector) return;

            const $target = $(targetSelector);
            if ($target.length === 0) return;

            $('.accordion-collapse.show')
                .not($target)
                .each(function () {
                    const inst = window.bootstrap
                        ? window.bootstrap.Collapse.getOrCreateInstance(this, { toggle: false })
                        : null;

                    const relatedBtn = document.querySelector(
                        `button[data-bs-target="#${this.id}"]`
                    );
                    if (relatedBtn) {
                        relatedBtn.classList.add('collapsed');
                        relatedBtn.setAttribute('aria-expanded', 'false');
                    }

                    if (inst) inst.hide();
                    else $(this).removeClass('show');
                });

            const doScroll = () => {

                const $accordionHeader = $button.closest('.accordion-header, .accordion-item');
                const targetElement = $accordionHeader.length ? $accordionHeader[0] : $button[0];

                const OFFSET = 20;

                const rect = targetElement.getBoundingClientRect();
                const top = rect.top + window.pageYOffset - OFFSET;

                window.scrollTo({ top, behavior: 'smooth' });

                try { $button[0].focus({ preventScroll: true }); } catch (e) {}
            };

            const instTarget = window.bootstrap
                ? window.bootstrap.Collapse.getOrCreateInstance($target[0], { toggle: false })
                : null;

            $button.removeClass('collapsed').attr('aria-expanded', 'true');

            // Si ya está abierto, solo hacemos scroll
            if ($target.hasClass('show')) {
                doScroll();
                return;
            }

            if (instTarget) {
                $target.one('shown.bs.collapse', doScroll);
                instTarget.show();
            } else {
                $target.addClass('show');
                requestAnimationFrame(() => setTimeout(doScroll, 0));
            }
        }

        navigateToStepOnly(step, scroll = true) {
            try {
                console.log(`🎯 Visual-only navigation to step: ${step}`);

                // Validate step parameter
                if (!step || !this.steps.includes(step)) {
                    console.error(`Invalid step: ${step}. Available steps:`, this.steps);
                    return false;
                }

                this.previousStep = this.currentStep;
                this.currentStep = step;

                $('body').attr('data-checkout-step', step);

                window.currentStep = step;

                $('.step-indicator').removeClass('active current');
                $(`.step-indicator[data-step="${step}"]`).addClass('active current');

                $('#checkoutAccordion .accordion-collapse').removeClass('show');
                $('#checkoutAccordion .accordion-button').addClass('collapsed').attr('aria-expanded', 'false');

                const $targetCollapse = $(`#collapse${step.charAt(0).toUpperCase() + step.slice(1)}`);
                const $targetButton = $(`.accordion-button[data-slug="${step}"]`);

                if ($targetCollapse.length && $targetButton.length) {
                    $targetCollapse.addClass('show');
                    $targetButton.removeClass('collapsed').attr('aria-expanded', 'true');
                    console.log(`✅ Opened accordion for step: ${step}`);
                } else {
                    console.warn(`⚠️ Could not find accordion elements for step: ${step}`);
                }

                if (scroll) {
                    this.scrollToAccordionStep(step);
                }

                console.log(`✅ Visual navigation to ${step} completed`);
                return true;

            } catch (error) {
                console.error(`❌ Visual navigation to ${step} failed:`, error);
                return false;
            }
        }

        closeStepAccordion(step) {
            console.log(`📋 Closing ${step} accordion`);

            const stepSelectors = [
                `#collapse${step.charAt(0).toUpperCase() + step.slice(1)}`,
                `.accordion-button[data-slug="${step}"]`,
                `.${step}-step .accordion-collapse`,
                `.checkout-step[data-step="${step}"] .accordion-collapse`
            ];

            stepSelectors.forEach(selector => {
                const $elements = $(selector);
                if ($elements.length) {
                    console.log(`Found ${step} elements with selector: ${selector}`);

                    if (selector.includes('accordion-button')) {
                        // Button element
                        $elements.addClass('collapsed').attr('aria-expanded', 'false');

                        // Close associated collapse
                        const target = $elements.data('bs-target');
                        if (target) {
                            $(target).removeClass('show');
                        }
                    } else {
                        // Collapse element
                        $elements.removeClass('show');

                        // Update associated button
                        const collapseId = $elements.attr('id');
                        if (collapseId) {
                            $(`.accordion-button[data-bs-target="#${collapseId}"]`)
                                .addClass('collapsed')
                                .attr('aria-expanded', 'false');
                        }
                    }
                }
            });
        }

        clearStepContent(step) {
            console.log(`🧹 Clearing ${step} step content`);

            const stepContentSelectors = [
                `#checkout-${step}`,
                `#collapse${step.charAt(0).toUpperCase() + step.slice(1)} .accordion-body`,
                `.checkout-step[data-step="${step}"] .step-content`,
                `.${step}-options`,
                `.${step}-option-item`,
                '.carrier-extra-content'
            ];

            stepContentSelectors.forEach(selector => {
                const $elements = $(selector);
                if ($elements.length) {
                    console.log(`Clearing content for: ${selector} (${$elements.length} elements)`);

                    // Remove selected/active states
                    $elements.find('.selected').removeClass('selected');
                    $elements.find('.active').removeClass('active');
                    $elements.find('.show').removeClass('show');

                    // Clear carrier-extra-content specifically
                    if (selector === '.carrier-extra-content') {
                        $elements.empty();
                    }
                }
            });
        }

        destroyStepInstances(step) {
            console.log(`💥 Destroying ${step} step instances`);

            // Step-specific cleanup
            if (step === 'delivery') {
                // Destroy delivery step handler
                if (window.deliveryStepHandler && typeof window.deliveryStepHandler.destroy === 'function') {
                    try {
                        window.deliveryStepHandler.destroy();
                        console.log('Destroyed deliveryStepHandler');
                    } catch (error) {
                        console.warn('Error destroying deliveryStepHandler:', error);
                    }
                }

                // Destroy carrier instances
                const carrierInstances = [
                    'deliveryAddress',
                    'mondialRelayManager',
                    'correosExpressManager',
                    'guardPickupManager'
                ];

                carrierInstances.forEach(instanceName => {
                    if (window[instanceName] && typeof window[instanceName].destroy === 'function') {
                        try {
                            window[instanceName].destroy();
                            window[instanceName] = null;
                            console.log(`Destroyed ${instanceName}`);
                        } catch (error) {
                            console.warn(`Error destroying ${instanceName}:`, error);
                        }
                    }
                });

                // Clean up carrier events and delivery events
                $(document).off('.mondialrelay .correosexpress .guardpickup .deliveryaddress .delivery .deliveryStep');
            }

            // Generic step cleanup
            $(document).off(`.${step}`);
        }

        async manualAddressSubmit($form) {
            console.log('🔄 Manual address submission starting...');
            try {
                const $invoice = $('#need_invoice');
                const formData = $form.serializeArray();
                formData.push({
                    name: $invoice.attr('name'),
                    value: $invoice.is(':checked') ? '1' : '0'
                });

                console.log('📋 Manual address form data:', formData);

                const response = await $.ajax({
                    url: window.checkoutManager.endpoints.checkout.stepaddress,
                    type: "POST",
                    data: formData,
                    dataType: 'json'
                });

                console.log('📋 Manual address response:', response);

                if (response.status === "success") {
                    console.log('✅ Manual address submission successful, navigating to delivery');
                    if (this.loadCheckoutStep) {
                        this.loadCheckoutStep('delivery', true, true);
                    } else if (this.navigateToStepDirect) {
                        this.navigateToStepDirect('delivery', true);
                    }
                } else {
                    console.warn('⚠️ Manual address submission failed:', response);
                }
            } catch (error) {
                console.error('❌ Manual address submission error:', error);
            }
        }


        /**
         * Public method to check if validation is in progress
         * This allows CheckoutManager to avoid duplicate validations
         */
        isValidationInProgress() {
            return this._validationInProgress;
        }

        /**
         * Public method to set validation state
         * Used by CheckoutManager to coordinate validations
         */
        setValidationInProgress(inProgress) {
            console.log(`🔒 Setting validation in progress: ${inProgress}`);
            this._validationInProgress = inProgress;
        }

        destroy() {
            $(document).off('.checkout-navigation');
            this._eventsBound = false;
            this._validationInProgress = false; // Reset validation flag
            console.log('🗑️ CheckoutNavigator destroyed');
        }


        async loadStepWithManager(step, isCurrent) {
            try {
                const response = await this.navigateToStep(step, true, false);

                if (response && response.html) {
                    $(`#checkout-${step}`).html(response.html);
                }

                if (isCurrent) {
                    this.bindStepEvents(step, response?.logged);
                    this.openAccordion(step);
                }

                if (response?.error) this.handleCheckoutError(response.error);
                if (response?.empty) this.handleEmptyCart();

                return response;
            } catch (error) {
                console.warn('Manager navigation failed, falling back to direct loading:', error);
                return this.loadStepDirect(step, isCurrent, true);
            }
        }

        async loadStep(step) {
            console.log(`🔄 Loading step: ${step}`);

            if (this.manager && typeof this.manager.makeRequest === 'function') {
                const endpoint = this.manager.endpoints.checkout[`step${step}`];
                if (!endpoint) {
                    throw new Error(`No endpoint found for step: ${step}`);
                }

                try {
                    const response = await this.manager.makeRequest(endpoint, {
                        useCache: true,
                        autoRetry: true
                    });
                    console.log(`✅ Step ${step} response:`, response);
                    return response;
                } catch (error) {
                    console.error(`❌ Failed to load step ${step}:`, error);
                    throw error;
                }
            } else {
                return this.loadStepDirectSimple(step);
            }
        }

        async loadStepDirectSimple(step) {
            const iso = this.getISO();
            const module = this.getModuleUrlBase();
            const url = `${module}?modalitie=checkout&action=step${step}&iso=${iso}`;

            try {
                const response = await $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json'
                });

                return response;
            } catch (error) {
                console.error(`❌ Failed to load step ${step}:`, error);
                throw error;
            }
        }

        async navigateToStep(step, validate = true, force = false) {
            // Validate step
            if (!step || !this.steps.includes(step)) {
                console.error(`Invalid step: ${step}. Available steps:`, this.steps);
                throw new Error(`Invalid step: ${step}`);
            }

            // Skip if already on this step and not forcing
            if (this.currentStep === step && !force) {
                console.log(`Already on step: ${step}`);
                return { status: 'success', skipped: true };
            }

            // Prevent double navigation
            if (this.navigating) {
                console.log('Navigation already in progress');
                this.pendingTransition = step;
                return { status: 'pending' };
            }

            this.navigating = true;

            try {
                console.log(`🚀 Navigating to step: ${step}`);

                // Store previous step
                this.previousStep = this.currentStep;

                // Clean up previous step before navigating
                if (this.previousStep && this.previousStep !== step) {
                    console.log(`🧹 Cleaning up previous step: ${this.previousStep}`);
                    this.clearStepContent(this.previousStep);
                    this.destroyStepInstances(this.previousStep);
                }

                // Optimistic UI update
                this.updateStepUI(step);

                // Validate if required using CheckoutManager
                if (validate && this.manager && typeof this.manager.validate === 'function') {
                    try {
                        await this.manager.validate();
                    } catch (validationError) {
                        console.warn('Validation failed during navigation:', validationError);
                        // Continue with navigation despite validation error
                    }
                }

                // Load the step content
                const response = await this.loadStep(step);

                if (response.status === 'success') {
                    // Update step state
                    this.currentStep = step;

                    // Update global state for legacy compatibility
                    window.currentStep = step;

                    // Update body attribute
                    $('body').attr('data-checkout-step', step);

                    // Bind step events
                    this.bindStepEvents(step, response.logged);

                    console.log(`✅ Successfully navigated to step: ${step}`);

                    // Handle any pending transition
                    if (this.pendingTransition && this.pendingTransition !== step) {
                        const nextStep = this.pendingTransition;
                        this.pendingTransition = null;
                        this.navigating = false;
                        return this.navigateToStep(nextStep, validate, force);
                    }

                    return response;
                } else {
                    // Revert optimistic UI update
                    this.revertStepUI();
                    throw new Error(response.message || 'Step navigation failed');
                }
            } catch (error) {
                // Revert optimistic UI update
                this.revertStepUI();
                console.error('❌ Navigation failed:', error);
                throw error;
            } finally {
                this.navigating = false;
            }
        }

        async loadCheckoutStep(step = null, isCurrent = true, skipValidation = false) {
            if (!step) {
                throw new Error('Step parameter is required');
            }
            console.log(`📄 Loading checkout step: ${step} (current: ${isCurrent}, skipValidation: ${skipValidation})`);

            // Update step state first
            this.updateStepState(step, isCurrent);

            // Choose loading method based on CheckoutManager availability and validation needs
            if (!skipValidation && this.manager && typeof this.manager.makeRequest === 'function') {
                // Use modern CheckoutManager navigation
                return this.loadStepWithManager(step, isCurrent);
            } else {
                // Use legacy direct loading
                return this.loadStepDirect(step, isCurrent, skipValidation);
            }

            this.openAccordion(step);
            this.scrollToAccordionStep(step);

        }

        async validateAndNavigate(slug) {
            try {
                console.log(`🔍 Validating and navigating to: ${slug}`);
                let shouldNavigate = true;

                // Use CheckoutManager validation if available, otherwise skip validation
                if (this.manager && typeof this.manager.validate === 'function') {
                    const validationResult = await this.manager.validate({ autoNavigate: false });

                    // Check if validation returned errors
                    if (validationResult && validationResult.errors && validationResult.errors.hasError) {
                        console.log(`⚠️ Validation errors found for step ${slug}, preventing navigation`);
                        console.log(`📋 Error type: ${validationResult.errors.type}`);
                        shouldNavigate = false;
                    } else {
                        console.log(`✅ Validation passed for step: ${slug}`);
                    }
                } else {
                    console.log(`⚠️ No CheckoutManager available, skipping validation for: ${slug}`);
                }

                // Only navigate if there are no validation errors
                if (shouldNavigate) {
                    await this.loadCheckoutStep(slug, true, true);
                    console.log(`✅ Successfully navigated to: ${slug}`);
                } else {
                    console.log(`🛑 Navigation to ${slug} blocked due to validation errors`);
                }

            } catch (validationError) {
                console.warn(`❌ Validation failed for step ${slug}:`, validationError);
                throw validationError;
            }
        }

        async handleStepResponse(response, step, isCurrent, skipValidation) {
            // Validate only if needed and manager is not available
            if (!skipValidation && this.manager && typeof this.manager.validate === 'function') {
                try {
                    await this.manager.validate({ autoNavigate: false });
                    console.log(`✅ Validation completed for step: ${step}`);
                } catch (validationError) {
                    console.warn("Validation interrupted step loading:", validationError);
                    throw validationError;
                }
            }

            // Process response
            const stepToLoad = response.step || step;
            const html = response.html;
            const logged = response.logged;

            // Update DOM with error protection
            if (html) {
                try {
                    console.log(`🔧 Updating DOM for step: ${stepToLoad}`);

                    // Clear any existing content first
                    const $target = $(`#checkout-${stepToLoad}`);
                    $target.empty();

                    // Insert new HTML with error protection
                    $target.html(html);

                    // Wait for DOM to settle before continuing
                    await new Promise(resolve => setTimeout(resolve, 50));

                    console.log(`✅ DOM updated successfully for step: ${stepToLoad}`);
                } catch (domError) {
                    console.error(`❌ Error updating DOM for step ${stepToLoad}:`, domError);
                    // Continue execution despite DOM error
                }
            }

            // Show/hide containers
            $('.checkout-container').removeClass('d-none');
            $('.checkout-container-process').addClass('d-none');
            $('.checkout-empty-container').addClass('d-none');

            // Bind events and open accordion if current
            if (isCurrent) {
                this.bindStepEvents(stepToLoad, logged);
                this.openAccordion(stepToLoad);
            }

            // Handle errors and empty state
            if (response.error) this.handleCheckoutError(response.error);
            if (response.empty) this.handleEmptyCart();
        }

        async updateAddress(addressData, optimistic = true) {
            if (!this.manager || typeof this.manager.makeRequest !== 'function') {
                console.warn('CheckoutManager not available for address update');
                return;
            }

            const payload = {
                id_address: addressData.id_address ?? addressData.id,
                type: addressData.type || 'delivery'
            };

            try {
                if (optimistic) {
                    this.updateAddressUI(addressData);
                }

                const response = await this.manager.makeRequest(this.manager.endpoints.checkout.setaddress, {
                    method: 'POST',
                    data: payload,
                    autoRetry: true
                });

                if (response.status === 'success') {
                    if (!optimistic) {
                        this.updateAddressUI(addressData);
                    }
                    if (typeof settings?.showToast === 'function') {
                        settings.showToast('success', response.message, response.operation);
                    }
                    return response;
                } else {
                    if (typeof settings?.showToast === 'function') {
                        settings.showToast('warning', response.message, response.operation);
                    }
                    if (optimistic) {
                        this.revertAddressUI();
                    }
                    throw new Error(response.message || 'Address update failed');
                }
            } catch (error) {
                if (optimistic) {
                    this.revertAddressUI();
                }
                throw error;
            }
        }

        async navigateToStepSection(step, openAccordion = true) {
            try {
                console.log(`🎯 Direct navigation to step: ${step} (no validations)`);

                // Validate step parameter
                if (!step || !this.steps.includes(step)) {
                    console.error(`Invalid step: ${step}. Available steps:`, this.steps);
                    throw new Error(`Invalid step: ${step}`);
                }

                // Update step state immediately
                this.updateStepState(step, true);

                // Load step content directly (skipValidation = true)
                const response = await this.loadStepDirect(step, true, true);

                if (response && response.status === 'success') {
                    console.log(`✅ Successfully navigated to ${step} without validations`);

                    // Bind step events if needed
                    //this.bindStepEvents(step, response.logged);

                    // Open accordion if requested
                    if (openAccordion) {
                        this.openAccordion(step);
                        this.scrollToAccordionStep(step);
                    }

                    return response;
                } else {
                    throw new Error(response?.message || 'Navigation failed');
                }

            } catch (error) {
                console.error(`❌ Direct navigation to ${step} failed:`, error);
                throw error;
            }
        }
        async navigateToStepDirect(step, openAccordion = true) {
            try {
                console.log(`🎯 Direct navigation to step: ${step} (no validations)`);

                // Validate step parameter
                if (!step || !this.steps.includes(step)) {
                    console.error(`Invalid step: ${step}. Available steps:`, this.steps);
                    throw new Error(`Invalid step: ${step}`);
                }

                // Clean up previous step before navigating
                if (this.currentStep && this.currentStep !== step) {
                    console.log(`🧹 Cleaning up current step: ${this.currentStep} before navigating to ${step}`);
                    this.clearStepContent(this.currentStep);
                    this.destroyStepInstances(this.currentStep);
                }

                // Update step state immediately
                this.updateStepState(step, true);

                // Load step content directly (skipValidation = true)
                const response = await this.loadStepDirect(step, true, true);

                if (response && response.status === 'success') {
                    console.log(`✅ Successfully navigated to ${step} without validations`);

                    // Bind step events if needed
                    //this.bindStepEvents(step, response.logged);

                    // Open accordion if requested
                    if (openAccordion) {
                        this.openAccordion(step);
                        this.scrollToAccordionStep(step);
                    }

                    return response;
                } else {
                    throw new Error(response?.message || 'Navigation failed');
                }

            } catch (error) {
                console.error(`❌ Direct navigation to ${step} failed:`, error);
                throw error;
            }
        }

        async navigateToAddressesOnError() {
            try {
                console.log('📍 Navigating to addresses step due to validation error');

                // First update checkout summary
                if (typeof this.loadCheckoutSummary === 'function') {
                    this.loadCheckoutSummary();
                }

                // Then navigate to addresses step without validations and open accordion
                if (typeof this.navigateToStepDirect === 'function') {
                    await this.navigateToStepDirect('address', true);
                    console.log('✅ Successfully navigated to addresses step (error recovery)');
                } else if (typeof this.navigateToStep === 'function') {
                    // Fallback: use regular navigation but skip validations
                    await this.navigateToStep('address', false, true);
                    console.log('✅ Successfully navigated to addresses step (fallback method)');
                } else {
                    console.warn('No navigation method available for error recovery');
                }

            } catch (error) {
                console.error('❌ Failed to navigate to addresses on error:', error);
            }
        }
        async navigateWithCleanup(fromStep, toStep, openAccordion = true) {
            try {
                console.log(`🧹 Navigating with cleanup from ${fromStep} to ${toStep}`);

                // 1. Close current step accordion
                this.closeStepAccordion(fromStep);

                // 2. Clear step content
                this.clearStepContent(fromStep);

                // 3. Destroy step instances
                this.destroyStepInstances(fromStep);

                // 4. Try to navigate to target step with fallbacks
                try {
                    const response = await this.navigateToStepDirect(toStep, openAccordion);
                    console.log(`✅ Navigation with cleanup from ${fromStep} to ${toStep} completed`);
                    return response;
                } catch (navigationError) {
                    console.warn(`⚠️ navigateToStepDirect failed, trying fallback methods:`, navigationError);

                    // Fallback 1: Try navigateToStepOnly (UI only)
                    if (this.navigateToStepOnly(toStep, true)) {
                        console.log(`✅ Navigation with cleanup completed using navigateToStepOnly`);
                        return { status: 'success', method: 'navigateToStepOnly' };
                    }

                    // Fallback 2: Try loadCheckoutStep
                    try {
                        this.loadCheckoutStep(toStep, true, true);
                        console.log(`✅ Navigation with cleanup completed using loadCheckoutStep`);
                        return { status: 'success', method: 'loadCheckoutStep' };
                    } catch (loadError) {
                        console.error(`❌ All navigation methods failed:`, loadError);
                        throw new Error(`Navigation failed: ${navigationError.message}`);
                    }
                }

            } catch (error) {
                console.error(`❌ Navigation with cleanup from ${fromStep} to ${toStep} failed:`, error);
                throw error;
            }
        }

    }

// Export for external access
    window.CheckoutNavigator = CheckoutNavigator;

// Initialize if CheckoutManager is available
    if (window.checkoutManager) {
        window.checkoutNavigator = new CheckoutNavigator(window.checkoutManager);
        console.log('✅ CheckoutNavigator initialized');
    } else {
        // Initialize when CheckoutManager becomes available
        $(document).on('checkoutManager:ready', function() {
            window.checkoutNavigator = new CheckoutNavigator(window.checkoutManager);
            console.log('✅ CheckoutNavigator initialized (delayed)');
        });

        // Fallback initialization after timeout
        setTimeout(function() {
            if (!window.checkoutNavigator && window.checkoutManager) {
                window.checkoutNavigator = new CheckoutNavigator(window.checkoutManager);
                console.log('✅ CheckoutNavigator initialized (fallback)');
            }
        }, 100);
    }

} // End of guard condition for CheckoutNavigator
