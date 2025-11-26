
<div id="kbgcs_pts_carrier_block" class="delivery-content" >
    <div id="kbgcs-pts-carrier-wrapper" class="kbgcs-pts-carrier-wrapper">

        <div class="velo-search-container">

            <input type="hidden" id="velo-add-longitude" name="velo-add-longitude" value="{$store['longitude']|escape:'htmlall':'UTF-8'}">
            <input type="hidden" id="velo-add-latitude"  name="velo-add-latitude" value="{$store['latitude']|escape:'htmlall':'UTF-8'}">

            <div class="velo-store-locator">
                <div class="store-filters" >
                    <form method="post" id="guard-search-store-form" class="guard-search-store-form" novalidate>
                        <div class="col-12">
                            <label for="address_search" class="form-label fw-semibold">
                                {l s='Address' mod='alsernetshopping'}
                            </label>
                            <input
                                    type="text"
                                    id="address_search"
                                    name="address_search"
                                    placeholder="{l s='Enter the address, city or state' mod='alsernetshopping'}"
                                    autocomplete="off"
                                    class="form-control"
                                    aria-label="{l s='Address to find stores' mod='alsernetshopping'}">
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-sm btn-primary w-100 search-store">
                                {l s='Search' mod='alsernetshopping'}
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary w-100 restart-store">
                                {l s='Reboot' mod='alsernetshopping'}
                            </button>
                        </div>
                    </form>

                </div>

                <div class="velo-pickup-stores">
                    <div class="velo-store-map velo-pickup-store-map">
                        {if isset($search_as_move) && $search_as_move eq 1}
                            <div id="kb_map_checkbox" style="text-align: center;font-weight: bold;width: 200px;position: absolute;z-index: 999999;margin-left: 13%;background: white;">
                                <input type="checkbox" id="kb_move_map" name="kb_move_map" value="" checked>
                                <label for="kb_move_map">{l s='Search as I move the map' mod='alsernetshopping'}</label><br>
                            </div>
                        {/if}
                        <div id="map"></div>
                    </div>
                    <div class="velo-location-list velo-pickup-location-list col-md-5 col-sm-12">
                        <ul>
                            {if !empty($available_stores)}
                                {foreach $available_stores as $key => $store}
                                    {assign var=random value=132310|mt_rand:20323221}
                                    <li id="{$random|escape:'htmlall':'UTF-8'}"
                                        data-lat="{$store['latitude']|escape:'htmlall':'UTF-8'}"
                                        data-lng="{$store['longitude']|escape:'htmlall':'UTF-8'}">

                                        <input type="hidden" class="velo-add-longitude" name="velo-add-longitude"
                                               value="{$store['longitude']|escape:'htmlall':'UTF-8'}">
                                        <input type="hidden" class="velo-add-latitude"  name="velo-add-latitude"
                                               value="{$store['latitude']|escape:'htmlall':'UTF-8'}">

                                        <table>
                                            <tr>
                                                <td>
                                                    <div class="title-address">
                                                        {$store['name']|escape:'htmlall':'UTF-8'}
                                                    </div>
                                                    <div class="detail-address">
                                                        {$store['address1']|escape:'htmlall':'UTF-8'}
                                                        {if !empty($store['address2'])}
                                                            <br/>{$store['address2']|escape:'htmlall':'UTF-8'}
                                                        {/if}
                                                        {if !empty($store['state'])}
                                                            <br/>{$store['state']|escape:'htmlall':'UTF-8'}
                                                        {/if}
                                                        {if !empty($store['postcode'])}
                                                            <br/>{$store['postcode']|escape:'htmlall':'UTF-8'}
                                                        {/if}
                                                        {if !empty($store['city'])}
                                                            <br/>{$store['city']|escape:'htmlall':'UTF-8'}
                                                        {/if}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        <div class="velo-show-more">
                                            <a  class="button-gc-show-more button-show-more" >{l s='Show More' mod='alsernetshopping'}</a>
                                        </div>

                                        <div class="extra_content d-none">
                                            {if !empty($store['hours'])}
                                                <div  class="kb-store-hours">
                                                    <span class="velo_add_clock">{l s='Store Timing' mod='alsernetshopping'}</span>
                                                    <table class="mp-openday-list" style="display: table;">
                                                        <tbody>
                                                        {foreach $store['hours'] as $key => $time}

                                                            {if isset($time['hours'])}
                                                                <tr>
                                                                    <td class="mp-openday-list-title"><strong>{$time['day']|escape:'htmlall':'UTF-8'}</td>
                                                                    <td class="mp-openday-list-value">{if $time['hours'] == ''} <span style="color: red">{l s='Closed' mod='alsernetshopping'}</span>{else}{$time['hours']|escape:'htmlall':'UTF-8'}{/if}</td>
                                                                </tr>
                                                            {/if}
                                                        {/foreach}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            {/if}
                                            {if !empty($store['email'])}
                                                <span class="velo_add_number"><i class="material-icons">mail_outline</i> {$store['email']|escape:'htmlall':'UTF-8'}</span>
                                            {/if}
                                            {if $display_phone && !empty($store['phone'])}
                                                <div  class="kb-store-hours">
                                                    <span class="velo_add_clock">{l s='Store phone' mod='alsernetshopping'}</span>
                                                    <table class="mp-openday-list" style="display: table;">
                                                        <tbody>
                                                        <tr>
                                                            <td class="mp-openday-list-title">
                                                                <strong>{$store['phone']|escape:'htmlall':'UTF-8'}</strong>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            {/if}
                                            {if $is_enabled_website_link}
                                                <div  class="kb-store-hours">
                                                    <span class="velo_add_clock">{l s='Store website' mod='alsernetshopping'}</span>
                                                    <table class="mp-openday-list" style="display: table;">
                                                        <tbody>
                                                        <tr>
                                                            <td class="mp-openday-list-title">
                                                                <strong>
                                                                    <a href='{$index_page_link}'>
                                                                        {$index_page_link}
                                                                    </a>
                                                                </strong>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            {/if}
                                        </div>

                                        <a class="btn kb-gc-velo-store-select-link {if $default_store == $store['id_store']}store-selected{/if}" data-store-id="{$store['id_store']|escape:'htmlall':'UTF-8'}"  data-key="{$store['id_store']|escape:'htmlall':'UTF-8'}" href="javascript:void()">
                                            {if $default_store == $store['id_store']}
                                                {l s='Selected' mod='alsernetshopping'}
                                            {else}
                                                {l s='Select' mod='alsernetshopping'}
                                            {/if}
                                        </a>
                                        <a  class=" btn velo-directions-button" data-id="{$random}">
                                            {l s='Locate Store' mod='alsernetshopping'}
                                        </a>
                                        <input type="hidden" name="btn kb_av_store_details" value='{$av_store_detail|escape:'htmlall':'UTF-8'}'>
                                    </li>
                                {/foreach}
                            {else}
                            {/if}
                        </ul>
                    </div>
                    <div class="velo-field-inline velo-field-preferred-date col-md-7 col-sm-12" >
                        {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
                            <label>{l s='Please select a date when you want to pickup the package' mod='alsernetshopping'}:</label>
                            <input type="text" name="kb_pickup_select_date" id="kb_pickup_select_date"  placeholder="{l s='Pickup Date' mod='alsernetshopping'}" readonly="readonly" value="{if isset($cart_pickup['preferred_date'])}{if !empty($cart_pickup['preferred_date'])}{$cart_pickup['preferred_date']|escape:'htmlall':'UTF-8'}{/if}{/if}"/>
                        {/if}
                        <input type="hidden" name="kb_pickup_selected_store" class="kb_pickup_selected_store" id="kb_pickup_selected_store" value="{$default_store|escape:'htmlall':'UTF-8'}">
                        <input type="hidden" name="kb_store_select_date"  class="kb_store_select_date "id="kb_store_select_date" value='{$default_store_detail|escape:'htmlall':'UTF-8'}'>
                        {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
                            <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['preferred_date'])}{if !empty($cart_pickup['preferred_date'])}yes{/if}{/if}"/>
                        {else}
                            <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['id_store'])}{if !empty($cart_pickup['id_store'])}yes{/if}{/if}"/>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" class="page_controller" value='order'>

    </div>
</div>

<script>
    // CARGA DE GOOGLE MAPS (única)
    if (typeof google === 'undefined' && !window.googleMapsLoading) {
        window.googleMapsLoading = true;
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key={$map_api|escape:"htmlall":"UTF-8"}&callback=initialize&libraries=geometry';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    } else if (typeof google !== 'undefined') {
        setTimeout(function() {
            if (typeof window.initializeStorePickup === 'function') {
                window.initializeStorePickup();
            }
        }, 100);
    }
</script>

<script>
    (function($){
        'use strict';

        // =========================
        // VARIABLES GLOBALES
        // =========================
        var kb_all_stores = {if !empty($kb_all_stores)}{$kb_all_stores nofilter}{else}[]{/if};

        var default_latitude  = "{$default_latitude|escape:'htmlall':'UTF-8'}";
        var default_longitude = "{$default_longitude|escape:'htmlall':'UTF-8'}";
        var zoom_level        = {$zoom_level|escape:'htmlall':'UTF-8'};

        var locator_icon         = "{$locator_icon|escape:'javascript'}";
        var current_location_icon = "{$current_location_icon|escape:'javascript'}";

        var is_enabled_show_stores  = {if $is_enabled_show_stores}1{else}0{/if};
        var is_all_stores_disabled  = "{$is_all_stores_disabled|escape:'htmlall':'UTF-8'}";
        var is_enabled_date_selcetion = "{$is_enabled_date_selcetion}";
        var search_as_move          = {if isset($search_as_move) && $search_as_move eq 1}1{else}0{/if};

        var days_gap      = "{$days_gap|escape:'htmlall':'UTF-8'}";
        var maximum_days  = "{$maximum_days|escape:'htmlall':'UTF-8'} 23:59:59";
        var hours_gap     = "{$hours_gap|escape:'htmlall':'UTF-8'}";
        var hour_gap_date = "{$hour_gap_date|escape:'htmlall':'UTF-8'}";
        var hour_gap_hour = "{$hour_gap_hour|escape:'htmlall':'UTF-8'}";
        var hour_gap_month= "{$hour_gap_month|escape:'htmlall':'UTF-8'}";
        var time_slot     = "{$time_slot|escape:'htmlall':'UTF-8'}";

        var allowDateTime = {if $time_slot}1{else}2{/if};

        var date_format = "yyyy-mm-dd HH:ii";
        {if !empty($format)} date_format = "{$format|escape:'htmlall':'UTF-8'}"; {/if}

        var lang_iso        = "{$lang_iso|escape:'htmlall':'UTF-8'}";
        var kbgcs_store_url    = "{$kbgcs_store_url|escape:'javascript'}";
        var kb_pickup_carrier_id = "{$pickup_carrier_id|escape:'htmlall':'UTF-8'}";

// Traducciones
        var kb_selected               = "{l s='Selected' mod='alsernetshopping'}";
        var kb_select                 = "{l s='Select' mod='alsernetshopping'}";
        var show_more                 = "{l s='Show More' mod='alsernetshopping'}";
        var hide                      = "{l s='Hide' mod='alsernetshopping'}";
        var preferred_store_unselected= "{l s='Please first select Pickup Store to pickup the package.' mod='alsernetshopping'}";
        var preferred_date_empty      = "{l s='Pickup Date cannot be empty' mod='alsernetshopping'}";
        var add_preferred_time        = "{l s='Please select the Pickup Date' mod='alsernetshopping'}";
        var add_kb_preferred_time     = "{l s='Please add the Pickup Date' mod='alsernetshopping'}";
        var no_stores_available       = "{l s='Unfortunately ,No pickup stores are available at your location.Kindly choose a different carrier' mod='alsernetshopping'}";
        var success_preferred_time    = "{l s='Pickup Date is submitted successfully.' mod='alsernetshopping'}";
        var success_preferred_store   = "{l s='Pickup Store is submitted successfully.' mod='alsernetshopping'}";
        var error_preferred_time      = "{l s='Error while updating the Pickup Date.' mod='alsernetshopping'}";
        var kb_not_found              = "{l s='not found' mod='alsernetshopping'}";
        var kb_date_not_valid         = "{l s='Pickup Date or Time is not valid' mod='alsernetshopping'}";
        var selected_store_text       = "{l s='Selected Store:' mod='alsernetshopping'}";
        var selected_store_date       = "{l s='Selected Date:' mod='alsernetshopping'}";
        var updated_pickup_text       = "{l s='Update Pickup Store' mod='alsernetshopping'}";
        var page_name                 = "module-supercheckout-supercheckout";

// Dirección por defecto
        {if !empty($default_carrier)}
        {foreach $default_carrier as $key => $def_carrier}
        var kb_defualt_id_address = "{$key}";
        var kb_default_id_carrier = "{$def_carrier|replace:',':''}";
        {/foreach}
        {/if}
        {if !empty($current_selected_shipping)}
        var kb_default_id_carrier = "{$current_selected_shipping|replace:',':''}";
        {/if}


        var map = null;
        var locations = []; // [html, lat, lng]
        window._kbHasSearched = false;   // <- clave para no reinyectar kb_all_stores después de buscar
        window._kbSubmittingPreferred = false;
        var _kbSearchXHR = null;
        var _kbGeocoder  = null;
        var _kbSearching = false;

        // =========================
        // INICIALIZACIÓN
        // =========================
        // Store Pickup specific initialize function
        window.initializeStorePickup = function () {
            initKbptsMap();
        };

        // Set global initialize if not already set
        if (typeof window.initialize === 'undefined') {
            window.initialize = function () {
                if (typeof window.initializeStorePickup === 'function') {
                    window.initializeStorePickup();
                }
                if (typeof window.initializeGc === 'function') {
                    window.initializeGc();
                }
            };
        } else {
            var originalInitialize = window.initialize;
            window.initialize = function () {
                originalInitialize();
                if (typeof window.initializeStorePickup === 'function') {
                    window.initializeStorePickup();
                }
            };
        }

        function initKbptsMap() {
            if (typeof google === 'undefined' || !google.maps) {
                console.warn('Google Maps not available for Store Pickup initialization');
                return;
            }
            if (map) return;

            var $wrapper = $('.kbgcs-pts-carrier-wrapper');
            var el = $wrapper.find('#map')[0];
            if (!el) {
                console.warn('Map element not found for Store Pickup');
                return;
            }

            $wrapper.find('.button-show-more').off('click.kbpts').on('click.kbpts', function (e) {
                e.preventDefault();
                var $toggle = $(this).closest('.velo-show-more').next('.extra_content');
                $toggle.toggleClass('d-none');
                $(this).text($toggle.hasClass('d-none') ? show_more : hide);
            });

            var opts = {
                center: new google.maps.LatLng(
                    parseFloat(default_latitude),
                    parseFloat(default_longitude)
                ),
                zoom: parseInt(zoom_level, 10)
            };

            try {
                map = new google.maps.Map(el, opts);

                // SOLO al primer load pinta todas (si se debe). Luego, nunca más.
                if (!window._kbHasSearched && is_enabled_show_stores) {
                    locations = kb_all_stores;
                    setMarkers();
                }
            } catch (error) {
                console.error('Error initializing Store Pickup map:', error);
            }
        }


        $(document).on('click', `.delivery_option_select[data-id="{$carrier->id}"]`, function () {
            const $input = $(this);
            const idCarrier = parseInt($input.data('id'), 10);
            if (idCarrier === 78 && typeof google !== 'undefined' && google.maps) {
                initKbptsMap();
            }
        });

        // =========================
        // HELPERS UI / MAPA
        // =========================
        function clearPreferredAlerts() {
            $('.velo-location-list-alert').remove();
            $('#preferred-alert').remove();
            $('#kb_shipping_error').remove();
        }

        function getStoreId($el) {
            var id = $el.data('store-id');
            if (typeof id === 'undefined' || id === '' || id === null) id = $el.data('key');
            return (id !== undefined && id !== null) ? String(id) : '';
        }

        function setButtonsState(storeId, kb_select_text, kb_selected_text) {
            var $all = $('.kb-gc-velo-store-select-link');
            if (typeof kb_select_text !== 'undefined') {
                $all.removeClass('store-selected').html(kb_select_text);
            } else {
                $all.removeClass('store-selected');
            }
            var $targets = $('.kb-gc-velo-store-select-link[data-store-id="' + storeId + '"], .kb-gc-velo-store-select-link[data-key="' + storeId + '"]');
            if (typeof kb_selected_text !== 'undefined') {
                $targets.addClass('store-selected').html(kb_selected_text + ' <i class="icon-check"></i>');
            } else {
                $targets.addClass('store-selected');
            }
        }

        function updateHiddenInputs(storeId) {
            $('#delivery_confirmation').val('');
            $('input[name="kb_pickup_selected_store"]').val(storeId);
        }

        function updateStoreDetailsInput(storeId) {
            var $detailsInput = $('input[name="kb_av_store_details"]');
            if (!$detailsInput.length) return;
            try {
                var details = $.parseJSON($detailsInput.val() || '{}');
                if (details && Object.prototype.hasOwnProperty.call(details, storeId)) {
                    $('input[name="kb_store_select_date"]').val(JSON.stringify(details[storeId]));
                }
            } catch (err) {
                console.warn('Invalid kb_av_store_details JSON:', err);
            }
        }

        function refreshDateTimePicker() {
            var $dt = $('#kb_pickup_select_date');
            if ($dt.length && typeof $dt.datetimepicker === 'function') {
                try { $dt.datetimepicker('remove'); } catch (e1) {}
            }
            if (typeof showdatepicker === 'function') {
                showdatepicker();
            }
        }

        function setMarkers() {
            var iconCfg = {ldelim}
                url: locator_icon,
                scaledSize: new google.maps.Size(40, 40)
                {rdelim};

            if (!locations.length) {
                var center = new google.maps.LatLng(
                    parseFloat(default_latitude),
                    parseFloat(default_longitude)
                );
                new google.maps.Marker({ldelim}
                    map: map,
                    position: center,
                    icon: iconCfg,
                    animation: google.maps.Animation.DROP
                    {rdelim});
                map.setCenter(center);
                return;
            }

            locations.forEach(function(loc) {
                var html = loc[0], lat = parseFloat(String(loc[1]).replace(',', '.')), lng = parseFloat(String(loc[2]).replace(',', '.'));
                if (isNaN(lat) || isNaN(lng)) return;
                var pos  = new google.maps.LatLng(lat, lng);
                var marker = new google.maps.Marker({ldelim}
                    map: map,
                    position: pos,
                    icon: iconCfg,
                    animation: google.maps.Animation.DROP
                    {rdelim});
                var info = new google.maps.InfoWindow();
                marker.addListener('click', function() {
                    info.setContent(html);
                    info.open(map, marker);
                });
            });
        }

        function resetKbptsMap() {
            try { if (_kbSearchXHR && _kbSearchXHR.readyState !== 4) _kbSearchXHR.abort(); } catch (e) {}
            $('.velo-popup').remove();
            $('.gm-style-iw, .gm-style-iw-c').remove();
            if (typeof google !== 'undefined' && google.maps && map) {
                try { google.maps.event.clearInstanceListeners(map); } catch (e) {}
            }
            var $mapEl = $('#map');
            if ($mapEl.length) {
                $mapEl.empty();
                $mapEl.removeAttr('style');
            }
            locations = [];
            map = null;
        }

        // Marca “tu ubicación de búsqueda”
        function setMarkerSelfForSearch(localMap, arr) {
            for (var i = 0; i < arr.length; i++) {
                var label = arr[i][0];
                var lat  = parseFloat(String(arr[i][1]).replace(',', '.'));
                var lng  = parseFloat(String(arr[i][2]).replace(',', '.'));
                if (isNaN(lat) || isNaN(lng)) continue;

                var latlngset = new google.maps.LatLng(lat, lng);
                var marker = new google.maps.Marker({
                    map: localMap, title: label, position: latlngset, icon: current_location_icon, animation: google.maps.Animation.DROP
                });
                localMap.setCenter(marker.getPosition());
                var infowindow = new google.maps.InfoWindow();
                google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                    return function () {
                        infowindow.setContent(content);
                        infowindow.open(localMap, marker);
                    };
                })(marker, label, infowindow));
            }
        }

        // =========================
        // SELECCIÓN DE TIENDA (unificada)
        // =========================
        function selectStoreByIdUnified(storeId) {
            if (!storeId && storeId !== 0) return;

            clearPreferredAlerts();
            setButtonsState(
                storeId,
                (typeof kb_select !== 'undefined') ? kb_select : undefined,
                (typeof kb_selected !== 'undefined') ? kb_selected : undefined
            );

            updateHiddenInputs(storeId);

            var isEnabledDateSel = (typeof is_enabled_date_selcetion !== 'undefined') ? is_enabled_date_selcetion : 1;
            var pageName         = (typeof page_name !== 'undefined') ? page_name : '';
            var selOff = (String(isEnabledDateSel) === '0' || isEnabledDateSel === 0);

            if (selOff) {
                var mustAutoSubmit =
                    (pageName === 'module-supercheckout-supercheckout') ||
                    (pageName === 'checkout') ||
                    ((typeof kb_onepage !== 'undefined') && kb_onepage == 1);
                if (mustAutoSubmit) window._kbSubmitPreferred_debounced();
            }

            updateStoreDetailsInput(storeId);

            if (pageName !== 'module-supercheckout-supercheckout') {
                var $picker = $('#kb_pickup_select_date');
                if ($picker.length) {
                    $('html, body').animate({ldelim} scrollTop: $picker.offset().top - 200 {rdelim}, 300);
                }
            }
            refreshDateTimePicker();
        }

        // POPUP
        $(document).on('click', '.velo-popup .kb-gc-velo-store-select-link', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $btn = $(this);
            var storeId = $btn.data('store-id') || $btn.data('key');
            if (storeId) { selectStoreByIdUnified(String(storeId)); return; }

            var $root = $('.velo-location-list, #listsOffices, #lista_offices');
            var $listBtn = $root.find('.kb-gc-velo-store-select-link[data-store-id="' + storeId + '"]').first();
            if ($listBtn.length) {
                var sid = $listBtn.data('store-id') || $listBtn.data('key');
                if (sid) { selectStoreByIdUnified(String(sid)); return; }
            }

            var geoId = $btn.siblings('.velo-directions-button').data('id');
            if (geoId) {
                var $li = $('#' + geoId);
                if ($li.length) {
                    var $liBtn = $li.find('.kb-gc-velo-store-select-link').first();
                    var sid2 = $liBtn.data('store-id') || $liBtn.data('key');
                    if (sid2) { selectStoreByIdUnified(String(sid2)); return; }
                }
            }
            if (typeof window.storeSelected === 'function') { window.storeSelected(storeId); return; }
            if (typeof window.selectStoreById === 'function') { window.selectStoreById(storeId); return; }
            $(document).trigger('kb:storeSelected', {ldelim} id_store: storeId {rdelim});
        });


        // LISTA
        $(document).on('click', '.velo-location-list .kb-gc-velo-store-select-link', function (e) {
            e.preventDefault();
            if ($(this).closest('.velo-popup').length) return;
            var storeId = $(this).data('store-id') || $(this).data('key');
            if (!storeId && storeId !== 0) return;
            selectStoreByIdUnified(String(storeId));
        });

        window._kbSubmitPreferred_debounced = (function () {
            var t = null;
            return function () {
                clearTimeout(t);
                t = setTimeout(function () { submitPreferredGuard(); }, 120);
            };
        })();

        function submitPreferredGuard() {

            $('.updatePickupTime').closest('td').remove();
            $('#kb_selected_store_name').closest('td').remove();
            $('#kb_selected_date').closest('td').remove();

            $('#delivery_confirmation').val('');

            var isEnabledDateSel = (typeof is_enabled_date_selcetion !== 'undefined') ? is_enabled_date_selcetion : 1;
            var isDateRequired   = (String(isEnabledDateSel) === '1');

            var date = '';

            if (isDateRequired) {
                var raw = $('input[name="kb_pickup_select_date"]').val();
                date = raw ? String(raw).trim() : '';
            }

            var preferred_store = $('input[name="kb_pickup_selected_store"]').val();
            preferred_store = preferred_store ? String(preferred_store) : '';

            const payload = {
                id_carrier : 39,
                payload: {
                    preferred_store: preferred_store,
                    preferred_date: '',
                }
            };

            $.ajax({
                url   : window.checkoutManager.endpoints.checkout.selectdelivery,
                method: 'POST',
                data  : payload,
                dataType: 'json'
            }).done((data) => {
                if (data && data.status === 'success') {
                    $('#delivery_confirmation').val('yes');
                    $('.kb_pickup_selected_store').parent().parent().show();
                    window.checkoutManager.showToast('success', data.message);
                } else {
                    $('#delivery_confirmation').val('');
                    window.checkoutManager.showToast('warning', data.message);
                }
            }).fail(() => {
                if (typeof window.checkoutManager?.showToast === 'function') {
                    window.checkoutManager.showToast('error', 'Error de conexión');
                } else {
                    alert('Error de conexión');
                }
            }).always(() => {
                // Variables no definidas previamente, removiendo código problemático
                console.log('submitPreferredGuard completed');
            });

        }

        // =========================
        // BÚSQUEDA / GEOCODING
        // =========================
        $('#guard-search-store-form').validate({
            rules: {ldelim}
                address_search: {ldelim} required: true, minlength: 3 {rdelim}
                {rdelim},
            messages: {ldelim}
                address_search: {ldelim}
                    required: "Por favor ingresa una dirección.",
                    minlength: "Debes escribir al menos 3 caracteres."
                    {rdelim}
                {rdelim},
            errorElement: 'label',
            errorClass: 'error',
            highlight: function (el) { $(el).addClass('is-invalid').removeClass('is-valid'); },
            unhighlight: function (el) { $(el).removeClass('is-invalid').addClass('is-valid'); }
        });

        function debounce(fn, wait) {
            var t;
            return function() {
                clearTimeout(t);
                var ctx = this, args = arguments;
                t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
            };
        }

        // Función centralizada para ejecutar búsqueda
        function performSearch() {
            if ($.fn.validate && $('#guard-search-store-form').data('validator')) {
                if (!$('#guard-search-store-form').valid()) return;
            }
            if (_kbSearching) return;

            var $btn = $('.search-store');
            var address = ($('#address_search').val() || '').trim();

            if (!address) { alert(kb_not_found); return; }

            if (typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) {
                console.error('Google Maps Geocoder not available');
                alert('Google Maps not available for search');
                return;
            }

            if (!_kbGeocoder) _kbGeocoder = new google.maps.Geocoder();

            _kbSearching = true;
            $btn.prop('disabled', true).addClass('is-loading');

            geocodeAddress(address)
                .then(function (locationLatLng) {
                    return searchLocationsNear(locationLatLng, address);
                })
                .catch(function (err) {
                    console.warn('Geocode error:', err);
                    alert(address + ' ' + kb_not_found);
                })
                .finally(function () {
                    _kbSearching = false;
                    $btn.prop('disabled', false).removeClass('is-loading');
                });
        }

        // Submit del form (solo para Enter en el input)
        $(document).off('submit.searchStore', '#guard-search-store-form');
        $(document).on('submit.searchStore', '#guard-search-store-form', debounce(function (e) {
            e.preventDefault();
            e.stopPropagation();
            performSearch();
        }, 200));

        // Click en el botón (ejecución directa sin submit)
        $(document).off('click.searchStore', '.search-store');
        $(document).on('click.searchStore', '.search-store', function(e){
            e.preventDefault();
            e.stopPropagation();
            performSearch();
        });

        // Enter en input
        $(document).off('keydown.searchStore', '#address_search');
        $(document).on('keydown.searchStore', '#address_search', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#guard-search-store-form').trigger('submit');
            }
        });

        function geocodeAddress(address) {
            return new Promise(function (resolve, reject) {
                _kbGeocoder.geocode(
                    {ldelim} address: address {rdelim},
                    function (results, status) {
                        if (status === google.maps.GeocoderStatus.OK && results[0] && results[0].geometry && results[0].geometry.location) {
                            resolve(results[0].geometry.location);
                        } else {
                            reject(status || 'ZERO_RESULTS');
                        }
                    }
                );
            });
        }

        function searchLocationsNear(center, searchString) {
            // Defaults seguros si no existen inputs en el DOM
            var radius = parseFloat($('#velo_within_distance').val());
            if (isNaN(radius) || radius <= 0) radius = 25;

            var result_limit = parseInt($('#velo_limit').val(), 10);
            if (isNaN(result_limit) || result_limit <= 0) result_limit = 25;

            var filter_pickup = 1;
            if (typeof is_enabled_date_selcetion !== 'undefined' && String(is_enabled_date_selcetion) === '1') {
                filter_pickup = $('input[name="kb_pickup_select_date"]').length ? 1 : 0;
            }

            var lat = center.lat();
            var lng = center.lng();
            var controller = $('.page_controller').val() || '';

            var $body = $('body');
            var $list = $('.velo-location-list');
            var $modalLoader = $('#viewLocationDetails .loadingModel');

            try { if (_kbSearchXHR && _kbSearchXHR.readyState !== 4) _kbSearchXHR.abort(); } catch (e) {}

            $('#viewLocationDetails .ajax_alert').remove();
            $('#view-location-detail-div').empty();

            // Limpia mapa y lista para asegurar que solo se muestra lo nuevo
            resetKbptsMap();
            $list.empty();

            _kbSearchXHR = $.ajax({
                type: 'POST',
                url: kbgcs_store_url,
                dataType: 'json',
                cache: false,
                timeout: 20000,
                data: {ldelim}
                    searchStore: 1,
                    lat_val: lat,
                    lng_val: lng,
                    radius_val: radius,
                    result_limit: result_limit,
                    filter_pickup: filter_pickup,
                    page_controller: controller
                    {rdelim},
                beforeSend: function () {
                    $modalLoader.show();
                    $body.addClass('loading');
                },
                complete: function () {
                    $modalLoader.hide();
                    $body.removeClass('loading');
                    _kbSearching = false;
                    $('.search-store').prop('disabled', false).removeClass('is-loading');
                },
                success: function (resp) {
                    var bodyHtml = resp && resp.body ? resp.body : '';
                    $list.css('overflow', 'auto').html(bodyHtml);

                    // Reemplaza marcadores con SOLO los filtrados
                    var locationJson = safeParseJSON(resp && resp.location_arr_json);
                    var filteredLocations = Array.isArray(locationJson) ? locationJson : [];
                    window._kbHasSearched = true;       // <- ya hubo búsqueda
                    locations = filteredLocations;      // <- global usado por setMarkers()

                    // Crear mapa nuevo y pintar solo filtrados
                    var myOptions = {ldelim}
                        center: new google.maps.LatLng(
                            parseFloat(String(default_latitude).replace(',', '.')),
                            parseFloat(String(default_longitude).replace(',', '.'))
                        ),
                        zoom: parseInt(zoom_level, 10)
                        {rdelim};

                    var mapEl = document.getElementById('map');
                    if (mapEl) {
                        map = new google.maps.Map(mapEl, myOptions);
                        try { setMarkers(); } catch (e1) { console.warn('setMarkers error:', e1); }
                        try { setMarkerSelfForSearch(map, [[searchString || '', lat, lng]]); } catch (e2) { console.warn('setMarkerSelfForSearch error:', e2); }
                    } else {
                        console.warn('#map no encontrado');
                    }

                    if ($('.velo-popup .velo_add_distance').length) {
                        $('.velo-popup .velo_add_distance').hide();
                    }
                    if (typeof showHideContent === 'function') {
                        showHideContent();
                    }

                    if (!bodyHtml || $.trim(bodyHtml) === '') {
                        $list.html('<div class="alert alert-warning mb-2">No se encontraron tiendas en el radio seleccionado.</div>');
                    }
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    console.error('Search AJAX error:', status);
                    $('.velo-location-list').html('<div class="alert alert-danger mb-2">No se pudo cargar la búsqueda. Inténtalo de nuevo.</div>');
                }
            });
        }

        function showHideContent() {
            $('.button-show-more').off('click.listToggle').on('click.listToggle', function () {
                if ($(this).parent().parent().next().is(":visible")) {
                    $(this).html(show_more);
                    $(this).parent().parent().next().hide();
                } else {
                    $(this).html(hide);
                    $(this).parent().parent().next().show();
                }
            });
        }

        function safeParseJSON(str) {
            if (!str || typeof str !== 'string') return null;
            try { return JSON.parse(str); } catch (e) { return null; }
        }

        // =========================
        // RESTART / RESET BTN
        // =========================
        $(document).on("click", ".restart-store", function () {
            var $body = $("body");
            var filter_pickup = $('input[name="kb_pickup_select_date"]').length;
            var controller = $(".page_controller").val();

            // limpiar estado visual y mapa
            resetKbptsMap();
            window._kbHasSearched = false; // <- volvemos a estado inicial

            $.ajax({
                type: 'post',
                dataType: 'json',
                cache: false,
                beforeSend: function () {
                    $body.addClass("loading");
                },
                url: kbgcs_store_url,
                complete: function () {
                    $body.removeClass("loading");
                },
                data: {ldelim} searchStore: 1, reset: 1, filter_pickup: filter_pickup, page_controller: controller {rdelim},
                success: function (html) {
                    $(".velo-location-list").html('').html(html['body']).css("overflow", "scroll");

                    if ($('.velo-popup .velo_add_distance').length) {
                        $('.velo-popup .velo_add_distance').hide();
                    }
                    showHideContent();
                    $("#address_search").val('');
                    $("#velo_within_distance").val('25');
                    $("#velo_limit").val('25');
                    $('.velo-popup').remove();
                    resetKbptsMap();
                    if (typeof google !== 'undefined' && google.maps) {
                        initKbptsMap();;
                    }
                },
                error: function () {
                    $('.velo-popup').remove();
                    resetKbptsMap();
                }
            });
        });

        // =========================
        // TOGGLE BLOQUE CARRIER
        // =========================
        $(document).ready(toggleCarrierBlock);
        $(document).on('change', '.delivery_option_select', function(e){
            e.preventDefault();
            toggleCarrierBlock();
        });

        function toggleCarrierBlock() {
            const $input = $(this);
            const idCarrier = parseInt($input.data('id'), 10);

            if (!isNaN(idCarrier)) {
                const $deliveryItem = $('.delivery_option_select[value="' + idCarrier + '"]').closest('.delivery-option-item');
                const $content = $deliveryItem.find('.carrier-extra-contents');

                // FIX: faltaba el punto en find(
                const $block = $content.find('.delivery-content[data-carrier="' + idCarrier + '"]');
                $block.removeClass('d-none');
            }

            if (idCarrier === 78) {
                if (typeof google !== 'undefined' && google.maps) {
                    initKbptsMap();
                }
            }
        }

        // =========================
        // FOCUS POPUP DESDE LISTA
        // =========================
        $(document).on('click', '.velo-directions-button', function(e){
            e.preventDefault();
            var divId = $(this).closest('li').attr('id');
            focusPopup(divId);
        });

        function focusPopup(divId) {
            const $item = $('#' + divId);
            let latStr = $item.data('lat') || $item.attr('data-lat') || $item.find('.velo-add-latitude').val() || '';
            let lngStr = $item.data('lng') || $item.attr('data-lng') || $item.find('.velo-add-longitude').val() || '';

            latStr = String(latStr).replace(',', '.').trim();
            lngStr = String(lngStr).replace(',', '.').trim();

            let lat = parseFloat(latStr);
            let lng = parseFloat(lngStr);

            if (isNaN(lat) || isNaN(lng)) {
                lat = parseFloat(String(default_latitude).replace(',', '.'));
                lng = parseFloat(String(default_longitude).replace(',', '.'));
            }
            if (isNaN(lat) || isNaN(lng)) {
                console.error('Coordenadas inválidas incluso con fallback:', { ld: lat, lg: lng });
                return false;
            }

            const pos = {ldelim} lat: lat, lng: lng {rdelim};

            const $wrapper = $('.kbgcs-pts-carrier-wrapper:visible');
            const el = $wrapper.find('#map')[0];
            if (!el) {
                console.error('No se encontró el contenedor #map dentro de .kbgcs-pts-carrier-wrapper visible');
                return false;
            }

            const opts = {ldelim} center: pos, zoom: parseInt(zoom_level, 10) || 10 {rdelim};
            const localMap = new google.maps.Map(el, opts);

            const contentString = '<div class="velo-popup">' + $item.html() + '</div>';
            const infowindow = new google.maps.InfoWindow({ldelim} content: contentString {rdelim});

            const marker = new google.maps.Marker({
                position: pos,
                map: localMap,
                icon: {ldelim} url: locator_icon, scaledSize: new google.maps.Size(40, 40) {rdelim}
            });

            infowindow.open(localMap, marker);

            setTimeout(() => {
                $('.velo-popup #kb-store-image').show();
                $('.velo-popup .kb-store-hours').show();
            }, 300);

            return false;
        }

        // ========= Auto-initialize store selection from backend data =========
        function initializePreSelectedGuardPickupStore() {
            console.log('🛡️ Initializing GuardPickup store selection from backend data...');

            // Get cart guard pickup data from backend
            var cartGuardPickup = {if isset($cart_guard_pickup) && !empty($cart_guard_pickup)}{json_encode($cart_guard_pickup) nofilter}{else}null{/if};

            // Multiple ways to detect if carrier 39 is selected
            var carrierSelected = $('input[name^="delivery_option"][data-id="39"]:checked').length > 0 ||
                $('input[name^="delivery_option"][value*="39,"]:checked').length > 0 ||
                $('input[name="delivery_option[39]"]:checked').length > 0;

            console.log('🔍 Cart guard pickup data:', cartGuardPickup);
            console.log('🔍 Carrier 39 selected:', carrierSelected);
            console.log('🔍 All delivery option radios:', $('input[name^="delivery_option"]').length);
            console.log('🔍 Checked delivery option radios:', $('input[name^="delivery_option"]:checked').length);
            console.log('🔍 Carrier 39 radio elements (data-id):', $('input[name^="delivery_option"][data-id="39"]').length);
            console.log('🔍 Carrier 39 radio elements (value):', $('input[name^="delivery_option"][value*="39,"]').length);
            console.log('🔍 Carrier 39 radio elements (name):', $('input[name="delivery_option[39]"]').length);
            console.log('🔍 Currently checked delivery option:', $('input[name^="delivery_option"]:checked').attr('name'), $('input[name^="delivery_option"]:checked').val());

            if (cartGuardPickup && cartGuardPickup.id_store && carrierSelected) {
                console.log('✅ Found store data from backend, creating selected store view');

                var storeName = cartGuardPickup.store_name || 'Tienda #' + cartGuardPickup.id_store;
                var storeAddress = (cartGuardPickup.address1 || '') + (cartGuardPickup.address2 ? ', ' + cartGuardPickup.address2 : '');
                var storeCity = cartGuardPickup.city || '';
                var storePostcode = cartGuardPickup.postcode || '';
                var preferredDate = cartGuardPickup.preferred_date || '';

                // Create and show selected store card
                var selectedStoreCardHTML = '<div id="selectedStoreCard" class="col-12 selected-kb-pts-wrapper">' +
                    '<div class="selected-kb-pts">' +
                    '<div class="kb-pts-location">' +

                    '<div class="kb-pts-address">' +
                    '<div class="detail-address">' +
                    '<p class="title-address">' + storeName + '</p>' +
                    '<p>' + storeAddress + '</p>' +
                    '<p>' + (storePostcode ? storePostcode + ', ' : '') + storeCity + '</p>';

                if (preferredDate) {
                    selectedStoreCardHTML += '<li class="selected-date"><strong>Fecha seleccionada:</strong> ' + preferredDate + '</li>';
                }

                selectedStoreCardHTML += '</div>' +
                    '</div>' +
                    '</div>' +
                    '<a href="#" class="btn btn-primary mt-3 change-kb-pts" aria-label="Cambiar tienda de recogida">' +
                    updated_pickup_text +
                    '</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                // Hide search interface and show selected store
                $('.velo-search-container').addClass('d-none');
                $('.velo-pickup-stores').addClass('d-none');
                $('#kbgcs_pts_carrier_block').append(selectedStoreCardHTML);

                console.log('✅ GuardPickup store auto-selected:', storeName);

            } else {
                console.log('📝 No cart guard pickup data or carrier not selected, showing search interface');
                console.log('🔍 Reason analysis:');
                console.log('   - Has cartGuardPickup:', !!cartGuardPickup);
                console.log('   - Has id_store:', !!(cartGuardPickup && cartGuardPickup.id_store));
                console.log('   - Carrier selected:', carrierSelected);
                console.log('   - Full cartGuardPickup data:', cartGuardPickup);

                // Show search interface by default
                $('.velo-search-container').removeClass('d-none');
                $('.velo-pickup-stores').removeClass('d-none');
            }

            console.log('🔍 Final DOM state:');
            console.log('   - Search container visible:', !$('.velo-search-container').hasClass('d-none'));
            console.log('   - Pickup stores visible:', !$('.velo-pickup-stores').hasClass('d-none'));
            console.log('   - Selected store card visible:', $('#selectedStoreCard').length > 0);
        }

        // Event handler for changing selected store
        $(document).on('click', '.change-kb-pts', function(e) {
            e.preventDefault();

            // Remove selected store card and show search interface
            $('#selectedStoreCard').remove();
            $('.velo-search-container').removeClass('d-none');
            $('.velo-pickup-stores').removeClass('d-none');

            console.log('🔄 Switched back to store search interface');
        });

        // Make function available globally
        window.initializePreSelectedGuardPickupStore = initializePreSelectedGuardPickupStore;

        // Initialize on document ready with a proper delay to ensure delivery-step.js has set permissions
        $(document).ready(function() {
            console.log('🛡️ GuardPickup DOM ready, checking if should initialize auto-selection...');

            // Function to check if carrier 39 autoselect should be initialized
            function checkAndInitializeGuardPickup() {
                // Only initialize if this template is being loaded for the selected carrier
                // Check if the current delivery option container for carrier 39 is visible and selected
                var carrier39Container = $('[data-carrier="39"]');
                var carrier39Radio = $('input[name^="delivery_option"][data-id="39"], input[name^="delivery_option"][value*="39,"], input[name="delivery_option[39]"]');

                console.log('🔍 Carrier 39 container found:', carrier39Container.length);
                console.log('🔍 Carrier 39 radio found:', carrier39Radio.length);
                console.log('🔍 Carrier 39 radio checked:', carrier39Radio.is(':checked'));
                console.log('🔍 GuardPickup template container visible:', $('#kbgcs_pts_carrier_block:visible').length);

                // *** CONTROL CENTRALIZADO: Solo ejecutar si está permitido ***
                const isAutoselectAllowed = window.isCarrierAutoSelectAllowed && window.isCarrierAutoSelectAllowed(39);
                console.log('🔍 Autoselect permission check:', isAutoselectAllowed);
                console.log('🔍 DELIVERY_AUTOSELECT_CONTROL:', window.DELIVERY_AUTOSELECT_CONTROL);

                // Only proceed if:
                // 1. The carrier 39 radio exists and is checked
                // 2. This template container is visible (meaning carrier 39 is selected)
                // 3. Autoselection is permitted by central control
                if (isAutoselectAllowed && carrier39Radio.length > 0 && carrier39Radio.is(':checked') && $('#kbgcs_pts_carrier_block:visible').length > 0) {
                    console.log('✅ Carrier 39 is selected, template is visible, and autoselect is allowed, initializing auto-selection...');
                    initializePreSelectedGuardPickupStore();
                    return true;
                } else {
                    console.log('❌ Carrier 39 autoselect blocked or conditions not met, skipping auto-selection');
                    console.log('   - Autoselect allowed:', isAutoselectAllowed);
                    console.log('   - Radio checked:', carrier39Radio.is(':checked'));
                    console.log('   - Template visible:', $('#kbgcs_pts_carrier_block:visible').length > 0);
                    return false;
                }
            }

            // Try initialization with multiple attempts to handle timing issues
            function attemptInitialization(attempt, maxAttempts) {
                attempt = attempt || 1;
                maxAttempts = maxAttempts || 5;

                console.log('🔄 GuardPickup initialization attempt ' + attempt + '/' + maxAttempts);

                if (checkAndInitializeGuardPickup()) {
                    console.log('✅ GuardPickup initialization successful');
                    return;
                }

                if (attempt < maxAttempts) {
                    // Wait longer between attempts to allow delivery-step.js to set permissions
                    setTimeout(function() {
                        attemptInitialization(attempt + 1, maxAttempts);
                    }, 300 * attempt);
                } else {
                    console.log('⚠️ GuardPickup initialization failed after all attempts');
                }
            }

            // Start initialization attempts with a delay to ensure delivery-step.js loads first
            setTimeout(attemptInitialization, 250);
        });

    })(jQuery);
</script>


