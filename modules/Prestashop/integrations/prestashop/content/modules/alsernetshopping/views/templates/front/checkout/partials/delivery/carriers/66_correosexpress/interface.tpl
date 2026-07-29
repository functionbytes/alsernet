{* ==================== MARKUP ==================== *}
<div class="correosexpress-address-wrapper">
    <div class="correosexpress-filters">
        <form class="correosexpress-search-form" id="correosexpress-search-form" novalidate>
            <div class="form-group mb-2">
                <label for="postalcode">{l s='Postalcode' mod='alsernetshopping'} </label>
                <input type="text" id="postalcode"  name="postalcode"  class="form-control" maxlength="5"  inputmode="numeric" autocomplete="postal-code" placeholder="Ej: 28001">
            </div>

            <div class="form-group mb-2">
                <label for="location">{l s='Location' mod='alsernetshopping'}</label>
                <input type="text" id="location"  name="location" class="form-control" autocomplete="address-level2"  placeholder="Ej: Madrid">
            </div>

            <button type="button" class="btn correosexpress-action">{l s='Search offices' mod='alsernetshopping'}</button>
        </form>
    </div>

    <div id="containerOffices" class="d-none">
        <div class="correosexpress-hr">
            <div class="correosexpress-location-list row" id="listsOffices"></div>
        </div>
        <div id="noneOffices" class="correosexpress-hr row form-suscribe-confirmation d-none">
            <div class="col-12">
                <div class="empty-correosexpress-container text-center py-4">
                    <i class="fa-solid fa-location-crosshairs-slash fa-3x mb-3"></i>
                    <h1>{l s='We are sorry' mod='alsernetshopping'}</h1>
                    <p>{l s='No offices were found available for your search.' mod='alsernetshopping'}.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="cexSelected" class="correosexpress-address-wrapper selected-correosexpress-wrapper d-none"></div>

<script>
    window.correosexpress = window.correosexpress || {};

    (function (ns) {
        if (!ns.baseUrl)      ns.baseUrl      = '{$baseUrl|escape:'javascript':'UTF-8'}';
        if (!ns.cexTokenUser) ns.cexTokenUser = '{$cex_token_user|escape:'javascript':'UTF-8'}';
        if (!ns.idCart)       ns.idCart       = '{$id_cart|escape:'javascript':'UTF-8'}';
        if (!ns.idCarrier)    ns.idCarrier    = '{$id_carrier|escape:'javascript':'UTF-8'}';
        if (!ns.idCustomer)   ns.idCustomer   = '{$id_customer|escape:'javascript':'UTF-8'}';

        ns.t = ns.t || {
            select       : '{l s="Select" mod='alsernetshopping' js=1}',
            changePickup : '{l s="Change pickup point" mod='alsernetshopping' js=1}',
            error        : '{l s="An error occurred during the search. Please try again." mod='alsernetshopping' js=1}',
            invalidResp  : '{l s="Invalid server response." mod='alsernetshopping' js=1}',
            empty        : '{l s="No offices were found for your search." mod='alsernetshopping' js=1}',
            enterEither  : '{l s="Enter either the postal code or the city." mod='alsernetshopping' js=1}',
            numbersOnly  : '{l s="Numbers only." mod='alsernetshopping' js=1}',
            mustBe5      : '{l s="Must be 5 digits." mod='alsernetshopping' js=1}',
            searching    : '{l s="Searching..." mod='alsernetshopping' js=1}'
        };

        if (typeof window.baseUrl      === 'undefined') window.baseUrl      = ns.baseUrl;
        if (typeof window.cexTokenUser === 'undefined') window.cexTokenUser = ns.cexTokenUser;
        if (typeof window.selectText   === 'undefined') window.selectText   = ns.t.select;
        if (typeof window.changeText   === 'undefined') window.changeText   = ns.t.changePickup;
        if (typeof window.errorText    === 'undefined') window.errorText    = ns.t.error;
        if (typeof window.invalidResp  === 'undefined') window.invalidResp  = ns.t.invalidResp;
        if (typeof window.emptyMsg     === 'undefined') window.emptyMsg     = ns.t.empty;
    })(window.correosexpress);

    // Set cart data for auto-selection (outside literal to process Smarty variables)
    window.correosexpress_cart_data = {if isset($cart_correosexpress) && !empty($cart_correosexpress)}{json_encode($cart_correosexpress) nofilter}{else}null{/if};
</script>

{literal}
    <script>
        (function ($) {
            'use strict';

            console.log('📮 CorreosExpress template script started!');
            console.log('🔍 jQuery available:', typeof $ !== 'undefined');
            console.log('🔍 Cart data available:', typeof window.correosexpress_cart_data !== 'undefined');

            // ========= Utilidades DOM dinámico =========
            const sel = {
                searchWrapper: '.correosexpress-address-wrapper:not(.selected-correosexpress-wrapper)',
                form: '#correosexpress-search-form',
                btn: '.correosexpress-action',
                listWrap: '#containerOffices',
                list: '#listsOffices',
                empty: '#noneOffices',
                selected: '#cexSelected'
            };

            function $searchWrapper() { return $(sel.searchWrapper); }
            function $selected()      { return $(sel.selected); }
            function $form()         { return $(sel.form); }
            function $btn()          { return $(sel.btn); }
            function $listWrap()     { return $(sel.listWrap); }
            function $list()         { return $(sel.list); }
            function $empty()        { return $(sel.empty); }

            // ========= Caja de error (se recrea si no existe) =========
            function ensureErrorBox() {
                let $box = $('#searchError');
                if (!$box.length) {
                    $box = $('<div>', { id:'searchError', class:'alert alert-danger d-none', role:'alert', 'aria-live':'polite' });
                    $form().after($box);
                }
                return $box;
            }

            // ========= Validación dinámica y segura =========
            window.correosexpress = window.correosexpress || {};
            window.correosexpress._hasValidator = false;

            // Añadimos la regla custom si existe $.validator
            if ($.validator && !$.validator.methods.atLeastOne) {
                $.validator.addMethod('atLeastOne', function (value, element, selectorGroup) {
                    let filled = 0;
                    $(selectorGroup).each(function () {
                        if ($.trim($(this).val()).length > 0) filled++;
                    });
                    return filled > 0;
                }, correosexpress.t.enterEither);
            }

            function ensureValidation() {
                const $f = $form();
                if (!$.fn.validate || !$f.length) return false;           // no plugin o no form
                if ($f.data('cexValidated')) return true;                  // ya inicializado para esta instancia

                $f.validate({
                    ignore: [],
                    errorClass: 'error',
                    validClass: 'is-valid',
                    errorElement: 'label',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('.form-group').append(error);
                    },
                    rules: {
                        postalcode: {
                            atLeastOne: '#postalcode, #location',
                            digits: true,
                            rangelength: [5, 5]
                        },
                        location: {
                            atLeastOne: '#postalcode, #location'
                        }
                    },
                    messages: {
                        postalcode: {
                            atLeastOne: correosexpress.t.enterEither,
                            digits: correosexpress.t.numbersOnly,
                            rangelength: correosexpress.t.mustBe5
                        },
                        location: {
                            atLeastOne: correosexpress.t.enterEither
                        }
                    },
                    submitHandler: function (form, event) {
                        event.preventDefault();
                        event.stopPropagation();
                        performSearch();
                        return false;
                    }
                });

                $f.data('cexValidated', true);
                window.correosexpress._hasValidator = true;
                return true;
            }

            function canValidateNow() {
                const $f = $form();
                return !!($.fn.validate && $f.length && $f.data('validator'));
            }

            // ========= Eventos delegados (sobrevive a re-render) =========

            // Click buscar
            $(document)
                .off('click.cex', sel.btn)
                .on('click.cex', sel.btn, function (e) {
                    console.log('🔥 CLICK EVENT TRIGGERED on .correosexpress-action button!');
                    console.log('🔍 Event:', e);
                    console.log('🔍 Button element:', this);

                    e.preventDefault();
                    e.stopPropagation();

                    // Asegura que si el form es nuevo, lo validamos
                    ensureValidation();

                    if (canValidateNow()) {
                        // Si hay validator y falla, no continúa
                        if (!$form().valid()) return;
                    }
                    performSearch();
                });

            // Enter en inputs del formulario (delegado)
            $(document)
                .off('keydown.cex', sel.form + ' input')
                .on('keydown.cex', sel.form + ' input', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();

                        ensureValidation();

                        if (canValidateNow()) {
                            if (!$form().valid()) return;
                        }
                        performSearch();
                    }
                });

            // Seleccionar oficina
            $(document)
                .off('click.cex', '#listsOffices .selected-correosexpress-delivery')
                .on('click.cex', '#listsOffices .selected-correosexpress-delivery', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $btnSel = $(this);
                    const $item   = $btnSel.closest('.correosexpress-location-list-item');
                    const asText  = v => (v == null ? '' : String(v)).trim();

                    const codigo   = asText($item.attr('id'));
                    const name     = asText($btnSel.data('name'));
                    const address  = asText($btnSel.data('address'));
                    const zip      = asText($btnSel.data('zip'));
                    const city     = asText($btnSel.data('city'));
                    const province = asText($btnSel.data('province'));

                    const concatenado = [codigo, address, name, zip, city].join('#!#');

                    const payload = {
                        id_cart    : correosexpress.idCart,
                        id_customer: correosexpress.idCustomer,
                        id_carrier : correosexpress.idCarrier,
                        type       : 'pickup',
                        token      : correosexpress.cexTokenUser,
                        payload    : {
                            office_id     : codigo,
                            office_code   : codigo,
                            office_name   : name,
                            street        : address,
                            city          : city,
                            postcode      : zip,
                            province      : province,
                            texto_oficina : concatenado
                        }
                    };

                    const originalHtml = $btnSel.html();
                    $btnSel.prop('disabled', true).attr('aria-busy','true');

                    $.ajax({
                        url     : window.checkoutManager?.endpoints?.checkout?.selectdelivery,
                        method  : 'POST',
                        data    : payload,
                        dataType: 'json'
                    })
                        .done((data) => {
                            if (data && data.status === 'success') {
                                pintarResumenSeleccion(name, address, zip, city);
                                if (window.checkoutManager?.showToast) {
                                    window.checkoutManager.showToast('success', data.message);
                                }
                            } else {
                                const msg = (data && data.message) ? data.message : 'No se pudo guardar la selección.';
                                if (typeof window.checkoutManager?.handleError === 'function') {
                                    window.checkoutManager.handleError($form(), msg, 'warning');
                                } else {
                                    alert(msg);
                                }
                            }
                        })
                        .fail(() => {
                            if (typeof window.checkoutManager?.handleError === 'function') {
                                window.checkoutManager.handleError($form(), 'Error de conexión', 'danger');
                            } else {
                                alert('Error de conexión');
                            }
                        })
                        .always(() => {
                            $btnSel.prop('disabled', false).removeAttr('aria-busy').html(originalHtml);
                            if (typeof window.checkoutManager?.setFormLoading === 'function') {
                                window.checkoutManager.setFormLoading($form(), $btnSel, false);
                            }
                        });
                });

            // Cambiar punto (volver al listado)
            $(document)
                .off('click.cex', '.change-selected-correosexpress-delivery')
                .on('click.cex', '.change-selected-correosexpress-delivery', function (e) {
                    e.preventDefault();


                    const $sel  = $selected();        // #cexSelected (solo el resumen)
                    const $wrap = $searchWrapper();   // solo el contenedor del buscador

                    $sel.addClass('d-none').empty();
                    $wrap.removeClass('d-none');

                    ensureValidation();
                    scrollToEl($wrap, 120, 400);
                });

            // ========= Lógica de búsqueda =========
            function performSearch() {
                console.log('🚀 performSearch called!');
                console.log('🔍 window.correosexpress:', window.correosexpress);
                console.log('🔍 correosexpress.baseUrl:', window.correosexpress?.baseUrl);

                if (!window.correosexpress || !correosexpress.baseUrl) {
                    console.error('[correosexpress] Config no disponible (correosexpress.baseUrl).');
                    showError('Config error. Please reload.');
                    return false;
                }

                const postalcode = ($('#postalcode').val() || '').trim();
                const location   = ($('#location').val()   || '').trim();

                resetUI();
                setLoading(true);

                $.post(correosexpress.baseUrl, {
                    action: 'procesarCurlOficinaRest',
                    cod_postal: postalcode,
                    poblacion: location,
                    token: correosexpress.cexTokenUser
                })
                    .done(function (response) {
                        let offices = [];
                        try {
                            offices = typeof response === 'string' ? JSON.parse(response || '[]') : (response || []);
                        } catch (_) {
                            showError(correosexpress.t.invalidResp);
                            return;
                        }
                        renderOffices(offices);
                    })
                    .fail(function () {
                        showError(correosexpress.t.error);
                    })
                    .always(function () {
                        setLoading(false);
                    });
            }

            // ========= UI helpers (siempre consultan DOM actual) =========
            function resetUI() {
                hideError();
                $list().empty();
                $listWrap().addClass('d-none');
                $empty().addClass('d-none');
            }

            function setLoading(isLoading) {
                const $b = $btn().first();
                if (!$b.length) return;
                if (isLoading) {
                    $b.attr('aria-busy', 'true').addClass('disabled');
                    if (!$b.data('label')) $b.data('label', $b.text());
                    $b.text(correosexpress.t.searching);
                } else {
                    $b.removeAttr('aria-busy').removeClass('disabled');
                    if ($b.data('label')) $b.text($b.data('label'));
                }
            }

            function showError(msg) {
                ensureErrorBox().removeClass('d-none').text(msg);
            }

            function hideError() {
                ensureErrorBox().addClass('d-none').text('');
            }

            function renderOffices(offices) {
                const $lw = $listWrap();
                const $ls = $list();
                const $em = $empty();

                $ls.empty();
                $lw.addClass('d-none');
                $em.addClass('d-none');

                if (!Array.isArray(offices) || offices.length === 0) {
                    $em.removeClass('d-none').find('p').text(correosexpress.t.empty);
                    return;
                }

                const limited = offices.slice(0, 4);

                limited.forEach(function (of) {
                    const $item = $('<div>', {
                        id: of.codigoOficina,
                        class: 'col-12 col-md-6 col-sm-12 mb-2 correosexpress-location-list-item'
                    });

                    const $info = $('<div>', { class: 'info' }).append(
                        $('<h5>', { class: 'title-office', text: of.nombreOficina }),
                        $('<div>', {
                            class: 'detail-office',
                            html: [
                                escapeHTML(of.direccionOficina),
                                '<br>',
                                escapeHTML(of.codigoPostalOficina),
                                '<br>',
                                escapeHTML(of.poblacionOficina),
                                '<br>',
                                '<strong>Tel:</strong> ' + escapeHTML(of.telefonoOficina || ''),
                                '<br>',
                                escapeHTML(of.horarioOficina || '')
                            ].join('')
                        })
                    );

                    const $btnSel = $('<button>', {
                        type: 'button',
                        class: 'btn btn-primary w-100 selected-correosexpress-delivery',
                        'data-name': of.nombreOficina,
                        'data-address': of.direccionOficina,
                        'data-zip': of.codigoPostalOficina,
                        'data-city': of.poblacionOficina,
                        'data-province': of.provinciaOficina || ''
                    }).text(correosexpress.t.select);

                    const $content = $('<div>', { class: 'item-content' }).append($info, $('<div>').append($btnSel));
                    $item.append($content);
                    $ls.append($item);
                });

                $lw.removeClass('d-none');
            }

            function pintarResumenSeleccion(name, address, zip, city) {
                const $sel = $selected();
                const $wrap = $searchWrapper();

                const $left = $('<div>', { class: 'col-12 mb-2' }).append(
                    $('<div>', { class: 'correosexpress-address' }).append(
                        $('<ul>', { class: 'list-unstyled mb-0' }).append(
                            $('<li class="title-address">').text(name),
                            $('<li>').text(address),
                            $('<li>').text((zip ? zip + ', ' : '') + city)
                        )
                    )
                );

                const $right = $('<div>', { class: 'col-12 mb-2' }).append(
                    $('<a>', {
                        class: 'btn btn-primary mt-3 w-100 change-selected-correosexpress-delivery',
                        href: '#',
                        'aria-label': 'Cambiar punto de recogida'
                    }).html(correosexpress.t.changePickup || 'Cambiar punto')
                );

                $sel.empty().append($left, $right);
                $wrap.addClass('d-none');
                $sel.removeClass('d-none');

                $('html, body').stop(true).animate({ scrollTop: $sel.offset().top - 120 }, 400);
            }

            function scrollToEl($el, offset = 100, duration = 400) {
                if (!$el || !$el.length) return;
                const top = $el.offset().top - offset;
                $('html, body').stop(true).animate({ scrollTop: top }, duration);
            }

            function escapeHTML(str) {
                return String(str == null ? '' : str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // ========= Auto-initialize office selection from backend =========
            function initializePreSelectedCorreosOffice() {
                console.log('📮 Initializing CorreosExpress office selection from backend data...');

                // Get cart correosexpress data from backend
                var cartCorreos = window.correosexpress_cart_data || null;

                // Multiple ways to detect if carrier 66 is selected
                var carrierSelected = $('input[name^="delivery_option"][data-id="66"]:checked').length > 0 ||
                    $('input[name^="delivery_option"][value*="66,"]:checked').length > 0 ||
                    $('input[name="delivery_option[66]"]:checked').length > 0;

                console.log('🔍 Cart correosexpress data:', cartCorreos);
                console.log('🔍 Carrier 66 selected:', carrierSelected);
                console.log('🔍 All delivery option radios:', $('input[name^="delivery_option"]').length);
                console.log('🔍 Checked delivery option radios:', $('input[name^="delivery_option"]:checked').length);
                console.log('🔍 Carrier 66 radio elements (data-id):', $('input[name^="delivery_option"][data-id="66"]').length);
                console.log('🔍 Carrier 66 radio elements (value):', $('input[name^="delivery_option"][value*="66,"]').length);
                console.log('🔍 Carrier 66 radio elements (name):', $('input[name="delivery_option[66]"]').length);
                console.log('🔍 Currently checked delivery option:', $('input[name^="delivery_option"]:checked').attr('name'), $('input[name^="delivery_option"]:checked').val());

                if (cartCorreos && (cartCorreos.office_id || cartCorreos.office_code) && carrierSelected) {
                    console.log('✅ Found office data from backend, creating selected office view');

                    var officeName = cartCorreos.office_name || 'Oficina #' + (cartCorreos.office_id || cartCorreos.office_code);
                    var officeAddress = cartCorreos.street || '';
                    var officeZip = cartCorreos.postcode || '';
                    var officeCity = cartCorreos.city || '';

                    // Show selected office immediately without requiring search
                    pintarResumenSeleccion(officeName, officeAddress, officeZip, officeCity);

                    console.log('✅ CorreosExpress office auto-selected:', officeName);

                } else {
                    console.log('📝 No cart correosexpress data or carrier not selected, showing search form');
                    console.log('🔍 Reason analysis:');
                    console.log('   - Has cartCorreos:', !!cartCorreos);
                    console.log('   - Has office_id:', !!(cartCorreos && cartCorreos.office_id));
                    console.log('   - Has office_code:', !!(cartCorreos && cartCorreos.office_code));
                    console.log('   - Office ID/Code:', cartCorreos ? (cartCorreos.office_id || cartCorreos.office_code) : 'N/A');
                    console.log('   - Carrier selected:', carrierSelected);
                    console.log('   - Full cartCorreos data:', cartCorreos);

                    // Show search form by default
                    $searchWrapper().removeClass('d-none');
                    $selected().addClass('d-none');
                }

                console.log('🔍 Final DOM state:');
                console.log('   - Search wrapper visible:', !$searchWrapper().hasClass('d-none'));
                console.log('   - Selected wrapper visible:', !$selected().hasClass('d-none'));
            }

            // Make function available globally
            window.initializePreSelectedCorreosOffice = initializePreSelectedCorreosOffice;

            // Initialize on document ready with a small delay to ensure DOM is ready
            $(document).ready(function() {
                console.log('📮 CorreosExpress DOM ready, checking if should initialize...');

                // Check if carrier 66 is actually selected before proceeding
                var carrier66Radio = $('input[name^="delivery_option"][data-id="66"], input[name^="delivery_option"][value*="66,"], input[name="delivery_option[66]"]');
                var correosContainer = $('.correosexpress-address-wrapper:visible');

                console.log('🔍 Carrier 66 radio found:', carrier66Radio.length);
                console.log('🔍 Carrier 66 radio checked:', carrier66Radio.is(':checked'));
                console.log('🔍 CorreosExpress container visible:', correosContainer.length);
                console.log('🔍 baseUrl:', window.correosexpress?.baseUrl);
                console.log('🔍 cexTokenUser:', window.correosexpress?.cexTokenUser);
                console.log('🔍 Button elements:', $('.correosexpress-action').length);
                console.log('🔍 Form elements:', $('#correosexpress-search-form').length);

                // *** CONTROL CENTRALIZADO: Solo ejecutar si está permitido ***
                const isAutoselectAllowed = window.isCarrierAutoSelectAllowed && window.isCarrierAutoSelectAllowed(66);

                // Only proceed if carrier 66 is selected, container is visible, and autoselect is allowed
                if (isAutoselectAllowed && carrier66Radio.length > 0 && carrier66Radio.is(':checked') && correosContainer.length > 0) {
                    console.log('✅ Carrier 66 is selected, template is visible, and autoselect is allowed, proceeding with initialization...');

                    // Test click events manually
                    setTimeout(() => {
                        console.log('🔬 Testing click event on button...');
                        const $btn = $('.correosexpress-action');
                        if ($btn.length > 0) {
                            console.log('🔍 Button found, testing manual click...');

                            // Test if button responds to click
                            $btn.off('click.test').on('click.test', function(e) {
                                console.log('✅ Manual click event fired!');
                                console.log('🔍 Event object:', e);
                            });

                            // Try triggering the test event
                            console.log('🔬 Triggering test click...');
                            $btn.trigger('click.test');

                            // Check if there are other click events bound
                            const events = $._data($btn[0], 'events');
                            console.log('🔍 Events bound to button:', events);

                            // Check if button is visible and not disabled
                            console.log('🔍 Button visible:', $btn.is(':visible'));
                            console.log('🔍 Button disabled:', $btn.prop('disabled'));
                            console.log('🔍 Button classes:', $btn.attr('class'));
                            console.log('🔍 Button parent visible:', $btn.parent().is(':visible'));
                        } else {
                            console.warn('⚠️ Button not found!');
                        }
                    }, 100);

                    setTimeout(initializePreSelectedCorreosOffice, 200);
                } else {
                    console.log('❌ Carrier 66 autoselect blocked or conditions not met, skipping CorreosExpress initialization');
                    console.log('   - Autoselect allowed:', isAutoselectAllowed);
                    console.log('   - Radio checked:', carrier66Radio.is(':checked'));
                    console.log('   - Container visible:', correosContainer.length > 0);
                }
            });

        })(jQuery);
    </script>
{/literal}
