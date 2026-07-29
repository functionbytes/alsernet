<div id="kb_pts_carrier_block" class="delivery-content">

    <!-- Container for pre-selected store card -->
    <div id="storeSelected" class="selected-kb-pts-carrier-wrapper row align-items-center mb-3 d-none"></div>

    <div id="kb-pts-carrier-wrapper" class="kb-pts-carrier-wrapper">

        <input type="hidden" id="velo-add-longitude" name="velo-add-longitude" value="{$store['longitude']|escape:'htmlall':'UTF-8'}">
        <input type="hidden" id="velo-add-latitude"  name="velo-add-latitude"  value="{$store['latitude']|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="kb_pickup_selected_store" class="kb_pickup_selected_store" id="kb_pickup_selected_store" value="{$default_store|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="kb_store_select_date"  class="kb_store_select_date "id="kb_store_select_date" value='{$default_store_detail|escape:'htmlall':'UTF-8'}'>
        {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
            <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['preferred_date'])}{if !empty($cart_pickup['preferred_date'])}yes{/if}{/if}"/>
        {else}
            <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['id_store'])}{if !empty($cart_pickup['id_store'])}yes{/if}{/if}"/>
        {/if}

        <div class="velo-location-list row g-3">
            {if !empty($available_stores)}
                {foreach $available_stores as $key => $store}
                    {assign var=random value=132310|mt_rand:20323221}

                    <div id="{$random|escape:'htmlall':'UTF-8'}"
                         data-lat="{$store['latitude']|escape:'htmlall':'UTF-8'}"
                         data-lng="{$store['longitude']|escape:'htmlall':'UTF-8'}"
                         class="col-12 col-md-6 col-sm-12">
                        <div class="velo-pickup-location-item ">
                            <input type="hidden" class="velo-add-longitude" name="velo-add-longitude"
                                   value="{$store['longitude']|escape:'htmlall':'UTF-8'}">
                            <input type="hidden" class="velo-add-latitude"  name="velo-add-latitude"
                                   value="{$store['latitude']|escape:'htmlall':'UTF-8'}">

                            <div class="velo-pickup-location-content">
                                <div class="title-address fw-bold">
                                    {$store['name']|escape:'htmlall':'UTF-8'}
                                </div>
                                <div class="detail-address small">
                                    {$store['address1']|escape:'htmlall':'UTF-8'}
                                    {if !empty($store['address2'])}<br/>{$store['address2']|escape:'htmlall':'UTF-8'}{/if}
                                    {if !empty($store['state'])}<br/>{$store['state']|escape:'htmlall':'UTF-8'}{/if}
                                    {if !empty($store['postcode'])}<br/>{$store['postcode']|escape:'htmlall':'UTF-8'}{/if}
                                    {if !empty($store['city'])}<br/>{$store['city']|escape:'htmlall':'UTF-8'}{/if}
                                </div>
                            </div>

                            <a class="btn btn-primary velo-store-select-link mt-2 {if $default_store == $store['id_store']}store-selected{/if}"
                               data-store-id="{$store['id_store']|escape:'htmlall':'UTF-8'}"
                               data-key="{$store['id_store']|escape:'htmlall':'UTF-8'}"
                               href="javascript:void(0)">
                                {if $default_store == $store['id_store']}
                                    {l s='Selected' mod='alsernetshopping'}
                                {else}
                                    {l s='Select' mod='alsernetshopping'}
                                {/if}
                            </a>
                        </div>

                    </div>
                {/foreach}
            {/if}
        </div>


        <input type="hidden" class="page_controller" value="order">
    </div>


    <div id="storeSelected" class="selected-kb-pts-carrier-wrapper row align-items-center mb-3 d-none"></div>


</div>

<script>
    (function($){
        'use strict';

        console.log('🏪 StorePickup template script started!');
        console.log('🔍 jQuery available:', typeof $ !== 'undefined');

        var kb_selected = (typeof window.kb_selected !== 'undefined') ? window.kb_selected : 'Selected';
        var kb_select   = (typeof window.kb_select   !== 'undefined') ? window.kb_select   : 'Select';
        var show_more   = (typeof window.show_more   !== 'undefined') ? window.show_more   : 'Show More';
        var hide_txt    = (typeof window.hide        !== 'undefined') ? window.hide        : 'Hide';

        function selectionCarrier(name, address, zip, city) {
            const $selectedWrap  = $('.selected-kb-pts-carrier-wrapper');
            const $searchWrapper = $('.kb-pts-carrier-wrapper');

            $selectedWrap.empty().append($left, $right);
            $searchWrapper.addClass('d-none');
            $selectedWrap.removeClass('d-none');

            // scroll (como ya lo tenías)
            $('html, body').stop(true).animate({ scrollTop: $selectedWrap.offset().top - 120 }, 400);
        }

        // ====== Helpers ======
        function clearPreferredAlerts() {
            $('.velo-location-list-alert, #preferred-alert, #kb_shipping_error').remove();
        }

        function setButtonsState(storeId) {
            var $all = $('.velo-store-select-link');
            $all.removeClass('store-selected').html(kb_select);

            var $targets = $('.velo-store-select-link[data-store-id="' + storeId + '"], .velo-store-select-link[data-key="' + storeId + '"]');
            $targets.addClass('store-selected').html(kb_selected + ' <i class="icon-check"></i>');
        }

        function updateHiddenInputs(storeId) {
            $('#delivery_confirmation').val('');
            $('input[name="kb_pickup_selected_store"]').val(storeId);
        }

        function getStoreIdFrom($btn){
            return String($btn.data('store-id') || $btn.data('key') || '');
        }

        function selectionCarrierFromItem($item) {
            const $selectedWrap  = $('#storeSelected');          // contenedor destino (no lo vaciamos)
            const $searchWrapper = $('.kb-pts-carrier-wrapper');


            const $tableClone = $item.clone(false, false).removeClass('velo-pickup-location-content');
            if (!$tableClone.hasClass('w-10')) $tableClone.addClass('w-10');

            const $wrapper = $('<div class="velo-pickup-location-content"></div>').append($tableClone);

            const $btnChange = $(
                '<a href="javascript:void(0)" class="btn btn-outline-secondary mt-3 w-100">Cambiar tienda</a>'
            ).on('click', function (e) {
                e.preventDefault();
                // Ocultamos solo el slot seleccionado y mostramos la lista
                $selectedWrap.addClass('d-none');        // si quieres ocultar todo el bloque seleccionado
                $searchWrapper.removeClass('d-none');
                $('#delivery_confirmation').val('');
            });

            // 👉 Slot interno para no reemplazar todo el #storeSelected
            let $slot = $selectedWrap.find('#selectedStoreCard');
            if (!$slot.length) {
                // si no existe, lo creamos y lo agregamos SIN vaciar el contenedor padre
                $slot = $('<div id="selectedStoreCard" class="col-12 selected-kb-pts-wrapper"></div>').appendTo($selectedWrap);
            } else {
                // si ya existe, solo reemplazamos su contenido
                $slot.empty();
            }

            $slot.append($wrapper, $btnChange);

            // Mostrar seleccionado y ocultar lista
            $selectedWrap.removeClass('d-none');
            $searchWrapper.addClass('d-none');

            // Scroll
            $('html, body').stop(true).animate({ scrollTop: $selectedWrap.offset().top - 120 }, 400);
        }


        // ====== Selección de tienda ======
        function selectStoreByIdUnified(storeId, $sourceItem) {
            if (!storeId && storeId !== 0) return;

            clearPreferredAlerts();
            setButtonsState(storeId);
            updateHiddenInputs(storeId);

            // Disparamos envío con un leve debounce
            _kbSubmitPreferred_debounced($sourceItem);
        }


        // Debounce mínimo para envío
        window._kbSubmitPreferred_debounced = (function () {
            var t = null;
            return function ($sourceItem) {
                clearTimeout(t);
                t = setTimeout(function () { submitPreferredStore($sourceItem); }, 10);
            };
        })();

        function getLocationTable(src) {
            if (!src) return $();
            var $el = (src.jquery) ? src : $(src);

            // Si ya es la tabla, úsala tal cual
            var $table = $el.is('.velo-pickup-location-content')
                ? $el.first()
                : $el.find('.velo-pickup-location-content').first();

            return $table.length ? $table.clone(true, true) : $();
        }

        function submitPreferredStore($sourceItem) {
            // Limpieza UI mínima
            $('.updatePickupTime').closest('td').remove();
            $('#kb_selected_store_name').closest('td').remove();
            $('#kb_selected_date').closest('td').remove();
            $('#delivery_confirmation').val('');


            var preferred_store = String($('input[name="kb_pickup_selected_store"]').val() || '');

            var locationItem = $sourceItem.find('.velo-pickup-location-content').first().clone(true, true);
// ...


            console.log(locationItem);

            var payload = {
                id_carrier: 78, // ajusta si corresponde
                payload: {
                    preferred_store: preferred_store,
                    preferred_date: '',
                }
            };

            $.ajax({
                url: window.checkoutManager.endpoints.checkout.selectdelivery,
                method: 'POST',
                data: payload,
                dataType: 'json'
            }).done(function (data) {
                if (data && data.status === 'success') {

                    $('#delivery_confirmation').val('yes');

                    selectionCarrierFromItem(locationItem);

                    if (window.checkoutManager?.showToast) {
                        window.checkoutManager.showToast('success', data.message);
                    }
                } else {
                    $('#delivery_confirmation').val('');
                    if (window.checkoutManager?.showToast) {
                        window.checkoutManager.showToast('warning', data?.message || 'No se pudo guardar la tienda.');
                    }
                }
            }).fail(function () {
                if (window.checkoutManager?.handleError) {
                    window.checkoutManager.handleError(null, 'Error de conexión', 'danger');
                } else {
                    alert('Error de conexión');
                }
            });
        }
        // ====== Eventos ======
        // Show more / Hide
        $(document).off('click.kbpts', '.button-show-more').on('click.kbpts', '.button-show-more', function (e) {
            e.preventDefault();
            var $toggle = $(this).closest('.velo-show-more').next('.extra_content');
            $toggle.toggleClass('d-none');
            $(this).text($toggle.hasClass('d-none') ? show_more : hide_txt);
        });

        // Selección desde la lista
        $(document)
            .off('click.kbptsSelect', '.velo-location-list .velo-store-select-link')
            .on('click.kbptsSelect', '.velo-location-list .velo-store-select-link', function (e) {
                e.preventDefault();
                var $btn  = $(this);
                var $item = $btn.closest('.velo-pickup-location-item');
                var storeId = getStoreIdFrom($btn);
                if (!storeId && storeId !== '0') return;

                selectStoreByIdUnified(storeId, $item); // ✅ pasamos el item para “pintar”
            });

        // Toggle del bloque extra del carrier (sin mapas)
        $(document).ready(toggleCarrierBlock);
        $(document).on('change', '.delivery_option_select', function(e){
            e.preventDefault();
            toggleCarrierBlock();
        });

        function toggleCarrierBlock() {
            $('.delivery_option_select:checked').each(function () {
                var idCarrier = parseInt($(this).data('id'), 10);
                if (isNaN(idCarrier)) return;

                var $deliveryItem = $('.delivery_option_select[value="' + idCarrier + '"]').closest('.delivery-option-item');
                var $content = $deliveryItem.find('.carrier-extra-contents');
                var $block = $content.find('.delivery-content[data-carrier="' + idCarrier + '"]');

                $block.removeClass('d-none');
            });

        }

    })(jQuery);
</script>

<script>



    // ========= AUTO-INITIALIZE STORE SELECTION FROM BACKEND DATA =========
    (function($) {
        'use strict';

        // Get cart pickup data from backend - same pattern as GuardPickup
        var cartPickup = {if isset($cart_pickup) && !empty($cart_pickup)}{json_encode($cart_pickup) nofilter}{else}null{/if};

        // Parse if it comes as string (which it does for StorePickup due to backend issue)
        if (typeof cartPickup === 'string' && cartPickup.length > 0) {
            try {
                cartPickup = JSON.parse(cartPickup);
                console.log('✅ Parsed cartPickup string to object:', cartPickup);
            } catch (e) {
                console.log('❌ Failed to parse cartPickup string:', e.message);
                cartPickup = null;
            }
        }

        /**
         * Auto-initialize store selection using backend cart_pickup data
         */
        function initializePreSelectedStore() {
            console.log('🏪 ========= STOREPICKUP CARRIER 78 FUNCTION CALLED! =========');
            console.log('🏪 Initializing STORE PICKUP selection from backend data...');
            console.log('🔍 STOREPICKUP-78 INITIAL cartPickup at function start:', cartPickup);
            console.log('🔍 STOREPICKUP-78 INITIAL cartPickup.id_store at function start:', cartPickup ? cartPickup.id_store : 'cartPickup is null');
            console.log('🔍 STOREPICKUP-78 Function scope cartPickup type:', typeof cartPickup);
            console.log('🔍 STOREPICKUP-78 Function scope cartPickup value:', cartPickup);

            // Multiple ways to detect if carrier 78 is selected
            var carrierSelected = $('input[name^="delivery_option"][data-id="78"]:checked').length > 0 ||
                $('input[name^="delivery_option"][value*="78,"]:checked').length > 0 ||
                $('input[name="delivery_option[78]"]:checked').length > 0;

            console.log('🔍 Cart pickup raw:', cartPickup);
            console.log('🔍 Cart pickup type:', typeof cartPickup);
            console.log('🔍 Cart pickup keys:', cartPickup ? Object.keys(cartPickup) : 'none');
            console.log('🔍 Carrier 78 selected:', carrierSelected);
            console.log('🔍 All delivery option radios:', $('input[name^="delivery_option"]').length);
            console.log('🔍 Checked delivery option radios:', $('input[name^="delivery_option"]:checked').length);
            console.log('🔍 Currently checked delivery option:', $('input[name^="delivery_option"]:checked').attr('name'), $('input[name^="delivery_option"]:checked').val());
            console.log('🔍 StoreSelected element exists:', $('#storeSelected').length);
            console.log('🔍 Search wrapper exists:', $('.kb-pts-carrier-wrapper').length);
            console.log('🔍 BEFORE centralized control check - cartPickup:', cartPickup);
            console.log('🔍 BEFORE centralized control check - cartPickup.id_store:', cartPickup ? cartPickup.id_store : 'null');

            // *** CONTROL CENTRALIZADO: Solo ejecutar si está permitido ***
            const isAutoselectAllowed = window.isCarrierAutoSelectAllowed && window.isCarrierAutoSelectAllowed(78);

            if (!isAutoselectAllowed) {
                console.log('❌ StorePickup autoselect blocked by central control');
                $('.kb-pts-carrier-wrapper').removeClass('d-none');
                $('#storeSelected').addClass('d-none');
                return;
            }

            console.log('🔍 AFTER centralized control check - cartPickup:', cartPickup);
            console.log('🔍 AFTER centralized control check - cartPickup.id_store:', cartPickup ? cartPickup.id_store : 'null');

            console.log('🔍 Debug cartPickup.id_store check:');
            console.log('   - cartPickup:', !!cartPickup);
            console.log('   - cartPickup.id_store value:', cartPickup.id_store);
            console.log('   - cartPickup.id_store type:', typeof cartPickup.id_store);
            console.log('   - cartPickup.id_store truthy:', !!cartPickup.id_store);
            console.log('   - carrierSelected:', carrierSelected);

            if (cartPickup && cartPickup.id_store && carrierSelected) {
                console.log('✅ Found store data from backend, creating selected store view');

                var storeName = cartPickup.store_name || 'Tienda #' + cartPickup.id_store;
                var storeAddress = (cartPickup.address1 || '') + (cartPickup.address2 ? ', ' + cartPickup.address2 : '');
                var storeCity = cartPickup.city || '';
                var storePostcode = cartPickup.postcode || '';
                var preferredDate = cartPickup.preferred_date || '';

                // Create and show selected store card matching the expected StorePickup interface structure
                var selectedStoreCardHTML = '<div id="selectedStoreCard" class="col-12 selected-kb-pts-wrapper">' +
                    '<div class="velo-pickup-location-content">' +
                    '<div class="w-10">' +
                    '<div class="title-address fw-bold">' + storeName + '</div>' +
                    '<div class="detail-address small">' +
                    storeAddress;

                if (cartPickup.address2 && cartPickup.address2.trim()) {
                    selectedStoreCardHTML += '<br>' + cartPickup.address2.trim();
                }
                if (storeCity && storeCity.trim()) {
                    selectedStoreCardHTML += '<br>' + storeCity.trim();
                }
                if (storePostcode && storePostcode.trim()) {
                    selectedStoreCardHTML += '<br>' + storePostcode.trim();
                }
                if (cartPickup.phone && cartPickup.phone.trim()) {
                    selectedStoreCardHTML += '<br>' + cartPickup.phone.trim();
                }

                selectedStoreCardHTML += '</div>' +
                    '</div>' +
                    '</div>' +
                    '<a href="javascript:void(0)" class="btn btn-outline-secondary mt-3 w-100">Cambiar tienda</a>' +
                    '</div>';

                // Hide search interface and show selected store using correct StorePickup DOM elements
                $('.kb-pts-carrier-wrapper').addClass('d-none');
                $('#storeSelected').html(selectedStoreCardHTML).removeClass('d-none');

                // Set confirmation value
                $('#delivery_confirmation').val('yes');
                $('#kb_pickup_selected_store').val(String(cartPickup.id_store));

                console.log('✅ StorePickup store auto-selected:', storeName);

                // Event handler for changing selected store - note: using event delegation
                $(document).off('click.changePts', '.btn-outline-secondary').on('click.changePts', '.btn-outline-secondary', function(e) {
                    e.preventDefault();
                    console.log('🔄 Switched back to store search interface');

                    // Remove selected store card and show search interface
                    $('#storeSelected').addClass('d-none').empty();
                    $('.kb-pts-carrier-wrapper').removeClass('d-none');
                    $('#delivery_confirmation').val('');
                });

            } else {
                console.log('📝 No cart pickup data or carrier not selected, showing search interface');
                console.log('🔍 Reason analysis:');
                console.log('   - Has cartPickup:', !!cartPickup);
                console.log('   - Has id_store:', !!(cartPickup && cartPickup.id_store));
                console.log('   - Carrier selected:', carrierSelected);
                console.log('   - Full cartPickup data:', cartPickup);

                // Show search interface by default using correct StorePickup DOM elements
                $('.kb-pts-carrier-wrapper').removeClass('d-none');
                $('#storeSelected').addClass('d-none');
            }

            console.log('🔍 Final DOM state:');
            console.log('   - Search wrapper visible:', !$('.kb-pts-carrier-wrapper').hasClass('d-none'));
            console.log('   - Selected store card visible:', $('#selectedStoreCard').length > 0);
        }

        // Make function available globally for delivery-step.js
        window.initializePreSelectedStore = initializePreSelectedStore;
        console.log('🔗 initializePreSelectedStore function assigned to window object');

    })(jQuery);
</script>