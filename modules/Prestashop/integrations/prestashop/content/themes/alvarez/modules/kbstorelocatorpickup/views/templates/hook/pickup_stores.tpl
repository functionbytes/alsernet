{if $is_all_stores_disabled == 0}
    {if isset($kb_superck)}
        <div id="kb-modal-backdropDiv" class="kb-modal-backdrop"></div>
        <div id="kb_pts_carrier_block" class="kb-box-modal kb-modal-open" style="display:none">
            <div id="kb-popup-content" class="kb-modal-contents kb-contentOpen">
                <a class="close-kb-modal" href="javascript:void(0);" onclick="showStuff('kb_pts_carrier_block')">x</a>
                <div class="velo-search-container kb-modal-body">
    {else}
        <div id="kb_pts_carrier_block" style="display:none">
            <div id="kb-pts-carrier-wrapper">
                <div class="velo-search-container">    
    {/if}
                    <input type="hidden" id="velo-add-longitude" name="velo-add-longitude" value={$store->longitude}>
                    <input type="hidden" id="velo-add-latitude" name="velo-add-longitude" value={$store->latitude}>
                    <div class="card velo-store-locator">
                        <form method="post" id="searchStoreForm" action>
                            <div class="velo-store-filters">
                                <div class="velo-search-bar velo-field-inline">
                                    <label>{l s='Find Stores Near' d='Shop.Theme.Global'}:</label>
                                    <input type="text" placeholder="{l s='Enter Street Address or City & State' d='Shop.Theme.Global'}" id="velo_address_search"/>
                                </div>
                                <div style="display: none;" class="velo-search-distance velo-field-inline">
                                    <label>{l s='Distance' d='Shop.Theme.Global'}:</label>
                                    <select id="velo_within_distance">
                                        <option value="5">5 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="10">10 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="15">15 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option selected="" value="25">25 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="50">50 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="100">100 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="250">250 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="500">500 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="1000">1000 {$distance_unit|escape:'htmlall':'UTF-8'}</option>
                                        <option value="0">{l s='No Limit' d='Shop.Theme.Global'}</option>
                                    </select>
                                </div>
                                <div style="display: none;" class="velo-search-limit velo-field-inline">
                                    <label>{l s='Results' d='Shop.Theme.Global'}</label>
                                    <select id="velo_limit" >
                                        <option value="25" selected="selected">25</option>
                                        <option value="50">50</option>
                                        <option value="75">75</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="velo-search-button velo-field-inline">
                                    <button type="button" id="searchStoreBtn"><i class="material-icons">search</i> <span>{l s='Search' d='Shop.Theme.Global'}</span></button>
                                </div>
                                <div class="velo-search-button velo-field-inline">
                                    <button type="button" id="resetStoreBtn"><i class="material-icons">autorenew</i> <span> <span>{l s='Reset' d='Shop.Theme.Global'}</span></button>
                                </div>
                            </div>
                        </form>
                        <!--Filters-->
                        <div class="velo-store-map velo-pickup-store-map">
                            {if isset($search_as_move) && $search_as_move eq 1}
                                <div id="kb_map_checkbox" style="text-align: center;font-weight: bold;width: 200px;position: absolute;z-index: 999999;margin-left: 13%;background: white;">
                                    <input type="checkbox" id="kb_move_map" name="kb_move_map" value="" checked>
                                    <label for="kb_move_map">{l s='Search as I move the map' d='Shop.Theme.Global'} </label><br>
                                </div>
                            {/if}
                            <div id="map"></div>
                        </div>
                        <div class="velo-location-list velo-pickup-location-list">
                            <ul>
                                {if !empty($available_stores)}
                                    {foreach $available_stores as $key => $store}
                                        {assign var=random value=132310|mt_rand:20323221}
                                        <li id="{$random|escape:'htmlall':'UTF-8'}">
                                            <table style="width: 100%;">
                                                <input type="hidden" id="velo-add-longitude" name="velo-add-longitude" value={$store['longitude']}>
                                                <input type="hidden" id="velo-add-latitude" name="velo-add-longitude" value={$store['latitude']}>    
                                                {if $is_enabled_store_image} 
                                                    <tr>                                       
                                                        <td>
                                                            <div id="kb-store-image">
                                                                {if !empty($store['image'])}{$store['image'] nofilter}{*escape not required*}{/if}
                                                            </div>
                                                            <div></div>
                                                        </td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td>
                                                        <div class="velo_add_name"> <span class="velo_store_icon" style="display:none;"><img class="velo-phone-icon" src="{$store_icon|escape:'htmlall':'UTF-8'}"/> </span>{$store['name']|escape:'htmlall':'UTF-8'}</div>
                                                        <div class="velo-add-address"> {$store['address1']|escape:'htmlall':'UTF-8'}
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
                                                            {if !empty($store['country'])} 
                                                                <br/>{$store['country']|escape:'htmlall':'UTF-8'}
                                                            {/if}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                            
                                            {*<div class="velo-w3-address-country">{$store['country']|escape:'htmlall':'UTF-8'}</div>*}
                                            {*<div class="velo-show-more"><strong><a href="javascript:void(0);" id="button-show-more-{$store['id_store']|escape:'htmlall':'UTF-8'}" class="button-show-more" >{l s='Show More' d='Shop.Theme.Global'}</a></strong></div>
                                            <div class="extra_content" style="display:none;">
                                                {if !empty($store['hours'])}
                                                    <div  class="kb-store-hours">
                                                        <span class="velo_add_clock"><img class="velo-phone-icon" src="{$clock_icon|escape:'htmlall':'UTF-8'}"/>{l s='Store Timing' d='Shop.Theme.Global'}</span>
                                                        <table class="mp-openday-list" style="display: table;">
                                                            <tbody>
                                                                {foreach $store['hours'] as $key => $time}
                                                                    {if isset($time['hours'])}
                                                                        <tr>
                                                                            <td class="mp-openday-list-title"><strong>{$time['day']|escape:'htmlall':'UTF-8'}</td>
                                                                            <td class="mp-openday-list-value">{if $time['hours'] == ''} <span style="color: red">{l s='Closed' d='Shop.Theme.Global'}</span>{else}{$time['hours']|escape:'htmlall':'UTF-8'}{/if}</td>
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
                                                    <span class="velo_add_number"><img class="velo-phone-icon" src="{$phone_icon|escape:'htmlall':'UTF-8'}"/> {$store['phone']|escape:'htmlall':'UTF-8'}</span>
                                                {/if}
                                                {if $is_enabled_website_link}
                                                    <div class="kb-store-url">
                                                        <span class="velo_add_number">
                                                            <img class="velo-web-icon" src="{$web_icon|escape:'htmlall':'UTF-8'}"/> <a href='{$index_page_link}'>{$index_page_link}</a>
                                                        </span>
                                                    </div>
                                                {/if}
                                            </div>*}
                                            {* changes over *}
                                            <div class="velo_add_distance"></div>
                                            {* changes by rishabh jain *}
                                
                                            {* changes over *}
                                            <a class="velo-store-select-link {if $default_store == $store['id_store']}store-selected{/if}" data-key="{$store['id_store']|escape:'htmlall':'UTF-8'}"  href="javascript:void()">{if $default_store == $store['id_store']}{l s='Selected' d='Shop.Theme.Global'}{* <i class="material-icons">check</i>*}{else}{l s='Select' d='Shop.Theme.Global'}{/if}</a>
                                            <button onclick="focus_and_popup({$random|escape:'htmlall':'UTF-8'})" class="velo-directions-button"><span>{l s='Locate Store' d='Shop.Theme.Global'}</span><i class="fa fa-map-marker"></i>{* <i class="material-icons">chevron_right</i>*}</button>
                                            {* changes by rishabh jain *}
                                            {if $is_enabled_direction_link}
                                                <a  target="_blank" class="velo-directions-link" href="http://google.com/maps/dir/?api=1&amp;destination={$store['latitude']|escape:'url'}%2C{$store['longitude']|escape:'url'}">{l s='Get Direction' d='Shop.Theme.Global'} <i class="material-icons">directions</i></a>
                                            {/if}
                                            {* changes over*}
                                            <input type="hidden" name="kb_av_store_details" value='{$av_store_detail|escape:'htmlall':'UTF-8'}' />
                                        </li>
                                    {/foreach}
                                {else}
                                {/if}
                            </ul>
                        </div>
                        <div class="clearfix"></div>
                        <div class="velo-store-filters velo-store-date-time-block ">
                            <div class="velo-field-inline velo-field-preferred-date" style="width: 70%;">
                                {* changes by rishabh jain *}
                                {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
                                    <label>{l s='Please select a date when you want to pickup the package' d='Shop.Theme.Global'}:</label>
                                    <input type="text" name="kb_pickup_select_date" id="kb_pickup_select_date"  placeholder="{l s='Pickup Date' d='Shop.Theme.Global'}" readonly="readonly" value="{if isset($cart_pickup['preferred_date'])}{if !empty($cart_pickup['preferred_date'])}{$cart_pickup['preferred_date']|escape:'htmlall':'UTF-8'}{else}{$smarty.now|date_format:"%Y-%m-%d"}{/if}{else}{$smarty.now|date_format:"%Y-%m-%d"}{/if}"/>
                                {/if}
                                <input type="hidden" name="kb_pickup_selected_store" id="kb_pickup_selected_store" value="{$default_store|escape:'htmlall':'UTF-8'}">
                                <input type="hidden" name="kb_store_select_date" id="kb_store_select_date" value='{$default_store_detail|escape:'htmlall':'UTF-8'}'>
                                {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
                                    <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['preferred_date'])}{if !empty($cart_pickup['preferred_date'])}yes{/if}{/if}"/>
                                {else}
                                    <input type="hidden" name="delivery_confirmation" id="delivery_confirmation" value="{if isset($cart_pickup['id_store'])}{if !empty($cart_pickup['id_store'])}yes{/if}{/if}"/>
                                {/if}
                            </div>
                            {if isset($is_enabled_date_selcetion) && $is_enabled_date_selcetion == 1}
                                <div class="velo-field-inline velo-search-button kb_pref_submit_btn">
                                    <button type="button" id="submitPrefrerredDateBtn" onclick="return submitPreferredTimebtn();">{l s='Submit' d='Shop.Theme.Global'}</button>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>            
{else}
            <div id="kb_no_store_available" style="display:none;clear:both;margin:15px;">
                <p class="alert alert-danger" >{l s='Unfortunately no pickup stores are available at your location.' d='Shop.Theme.Global'}</p>
            </div>
{/if}
            <input type="hidden" class="page_controller" value='order'>
            <script>
                {if !empty($default_carrier)}
                    {foreach $default_carrier as $key=>$def_carrier}
                        var kb_defualt_id_address = '{$key}';
                        var kb_default_id_carrier = '{$def_carrier|replace:',':''}';
                    {/foreach}
                {/if}
                {if !empty($current_selected_shipping)}
                    var kb_default_id_carrier = '{$current_selected_shipping|replace:',':''}'
                {/if}
                {*    {if !empty($current_selected_shipping)}*}
                //changes by tarun
                {if $is_enabled_show_stores eq 1}
                    var kb_all_stores = {$kb_all_stores nofilter}{*Variable contains html content, escape not required*};
                {else}
                    var kb_all_stores = '';
                {/if}    
                //changes over        
    
                var lang_iso = "{$lang_iso|escape:'htmlall':'UTF-8'}";
                {* changes by rishabh jain *}
                var is_all_stores_disabled = '{$is_all_stores_disabled|escape:'htmlall':'UTF-8'}';
                {* chnages over *}
                var days_gap = '{$days_gap|escape:'htmlall':'UTF-8'}';
                var maximum_days = '{$maximum_days|escape:'htmlall':'UTF-8'} 23:59:59';
                var hours_gap = '{$hours_gap|escape:'htmlall':'UTF-8'}';
                var hour_gap_date = '{$hour_gap_date|escape:'htmlall':'UTF-8'}';
                var hour_gap_hour = '{$hour_gap_hour|escape:'htmlall':'UTF-8'}';
                var hour_gap_month = '{$hour_gap_month|escape:'htmlall':'UTF-8'}';
                var kb_selected = "{l s='Selected' d='Shop.Theme.Global'}";
                var kb_select = "{l s='Select' d='Shop.Theme.Global'}";
                var allowDateTime = 2;
                {if $time_slot}
                    allowDateTime = 1;
                {/if}
                var time_slot = '{$time_slot|escape:'htmlall':'UTF-8'}';
                var date_format = "yyyy-mm-dd HH:ii";
                {if !empty($format)}
                    date_format = "{$format|escape:'htmlall':'UTF-8'}"
                {/if}
                var show_more = "{l s='Show More' d='Shop.Theme.Global'}";
                var hide = "{l s='Hide' d='Shop.Theme.Global'}";
                var preferred_store_unselected = "{l s='Please first select Pickup Store to pickup the package.' d='Shop.Theme.Global'}";
                var preferred_date_empty = "{l s='Pickup Date cannot be empty' d='Shop.Theme.Global'}";
                var add_preferred_time = "{l s='Please select the Pickup Date' d='Shop.Theme.Global'}";
                var add_kb_preferred_time = "{l s='Please add the Pickup Date' d='Shop.Theme.Global'}";
                var no_stores_available = "{l s='Unfortunately ,No pickup stores are available at your location.Kindly choose a different carrier' d='Shop.Theme.Global'}";
                var success_preferred_time = "{l s='Pickup Date is submitted successfully.' d='Shop.Theme.Global'}";
                var success_preferred_store = "{l s='Pickup Store is submitted successfully.' d='Shop.Theme.Global'}";
                var error_preferred_time = "{l s='Error while updating the Pickup Date.' d='Shop.Theme.Global'}";
                var kb_not_found = "{l s='not found' d='Shop.Theme.Global'}";
                var kb_date_not_valid = "{l s='Pickup Date or Time is not valid' d='Shop.Theme.Global'}";
    
                var selected_store_text = "{l s='Selected Store:' d='Shop.Theme.Global'}";
                var selected_store_date = "{l s='Selected Date:' d='Shop.Theme.Global'}";
                var updated_pickup_text = "{l s='Update Pickup Store' d='Shop.Theme.Global'}";
                var kb_pickup_carrier_id = '{$pickup_carrier_id|escape:'htmlall':'UTF-8'}';
                var kb_store_url = "{$kb_store_url nofilter}"; {* Variable contains URL, escape not required *}
                var locator_icon = "{$locator_icon|escape:'quotes':'UTF-8'}";
                var default_latitude = '{$default_latitude|escape:'htmlall':'UTF-8'}';
                var default_longitude = '{$default_longitude|escape:'htmlall':'UTF-8'}';
                var zoom_level = {$zoom_level|escape:'htmlall':'UTF-8'};
                var current_location_icon = "{$current_location_icon|escape:'quotes':'UTF-8'}";
                var locations = '';
                {if isset($search_as_move) && $search_as_move eq 1}
                    var search_as_move = 1;
                {else}    
                    var search_as_move = 0;
                {/if} 
                var is_enabled_date_selcetion = '{$is_enabled_date_selcetion}';
                {if $is_all_stores_disabled == 0}
                    {if !empty($selected_store)}
                        locations = [
                            ["<div class='velo-popup'><div class='gm_name'>{$selected_store['name']}</div><div class='gm_address'>{$selected_store['address1']}<br/>{$selected_store['address2']}</div><div class='gm_location'>{$selected_store['country']|escape:'htmlall':'UTF-8'}</div>{if $display_phone && !empty($selected_store['phone'])}<div class='gm_phone'><img class='velo-phone-icon' src='{$phone_icon}'>{$selected_store['phone']|escape:'htmlall':'UTF-8'}</div>{/if}</div><div class='gm_select_location'><a class='velo-store-select-link' style='display:none;' data-key='{$selected_store['id']|escape:'htmlall':'UTF-8'}'  href='#'><i class='material-icons'>check</i>{l s='Select' d='Shop.Theme.Global'}</a><a target='_blank' class='velo-directions-link' style='display:none;' href='http://google.com/maps/dir/?api=1&amp;destination={$selected_store['latitude']|escape:'url'}%2C{$selected_store['longitude']|escape:'url'}'>{l s='Locate Store' d='Shop.Theme.Global'} <i class='material-icons'>chevron_right</i></a></div>", {$selected_store['latitude']|escape:'htmlall':'UTF-8'}, {$selected_store['longitude']|escape:'htmlall':'UTF-8'}],
                        ];
                    {/if}
    

                    var default_content = "{l s='location' d='Shop.Theme.Global'}";
    
                    function initialize() {
                        var myOptions = {
                            center: new google.maps.LatLng({$default_latitude|escape:'htmlall':'UTF-8'}, {$default_longitude|escape:'htmlall':'UTF-8'}),
                            zoom: {$zoom_level|escape:'htmlall':'UTF-8'}
                        };
                        var map = new google.maps.Map(document.getElementById("map"), myOptions);
            
                        {if $is_enabled_show_stores eq 1}
                            locations = kb_all_stores;
                        {/if}
                        setMarkers(map, locations);
                    }

                    function setMarkers(map, locations) {
                        if (locations.length == 0) {
                            var lat = parseFloat(default_latitude);
                            var long = parseFloat(default_longitude);

                            latlngset = new google.maps.LatLng(lat, long);
                            var icon = {
                                url: locator_icon, // url
                                scaledSize: new google.maps.Size(40, 40), // scaled size
                            };
                            var marker = new google.maps.Marker({
                                map: map, position: latlngset, icon: icon, animation: google.maps.Animation.DROP
                            });
                            map.setCenter(marker.getPosition())
                            var content = default_content;
                            var infowindow = new google.maps.InfoWindow()

                            google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                                return function (results, status) {
                                    infowindow.setContent(content);
                                    infowindow.open(map, marker);
                                };
                            })(marker, content, infowindow));
                        } else {
                            var marker, i
                            for (i = 0; i < locations.length; i++) {
                                var loan = locations[i][0]
                                var lat = locations[i][1]
                                var long = locations[i][2]

                                latlngset = new google.maps.LatLng(lat, long);
                                var icon = {
                                    url: locator_icon, // url
                                    scaledSize: new google.maps.Size(40, 40), // scaled size
                                };
                                var marker = new google.maps.Marker({
                                    map: map, title: loan, position: latlngset, icon: icon, animation: google.maps.Animation.DROP
                                });
                                map.setCenter(marker.getPosition());
                                var content = loan;
                                var infowindow = new google.maps.InfoWindow()

                                google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                                    return function () {
                                        infowindow.setContent(content);
                                        infowindow.open(map, marker);
                                    };
                                })(marker, content, infowindow));
                            }
                        }
                        google.maps.event.addDomListener(window, "load", initialize);
                        google.maps.event.addListener(marker, 'mouseover', function (e) {
                            e.tb.target.parentElement.removeAttribute('title')
                        });

                        {*changes by vishal for adding search as i move marker functionality*}
                        {if isset($search_as_move) && $search_as_move eq 1}
                            google.maps.event.addListener(map, 'dragend', function() {
                                if (document.getElementById("kb_move_map").checked == true) {
                                    {*alert(map.getBounds());*}
                                    bounds = map.getBounds();
                                    if (typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion == 1) {
                                        var filter_pickup = $('input[name="kb_pickup_select_date"]').length;
                                    } else {
                                        var filter_pickup = 1;
                                    }
                                    var controller = $(".page_controller").val();
                                    $.ajax({
                                        type: 'POST',
                                        headers: { "cache-control": "no-cache" },
                                        url: kb_store_url,
                                        async: true,
                                        cache: false,
                                        dataType : "html",
                                        data: 'ajax=true'
                                            +'&method=updatestorelist&NEPoint='+bounds.getNorthEast()+'&SWPoint='+bounds.getSouthWest()+'&filter_pickup=' + filter_pickup + '&page_controller=' + controller,
                                        beforeSend: function() {
                                            $("#viewLocationDetails .ajax_alert").remove();
                                            $("#view-location-detail-div").html('');
                                            $("#viewLocationDetails .loadingModel").show();
                                            $body.addClass("loading");
                                            },
                                            complete: function () {
                                            $body.removeClass("loading");
                                        }, success: function(content) {
                                            content = JSON.parse(content);
                                            $(".velo-location-list").html('');
                                            $(".velo-location-list").html(content['body']);
                                            $(".velo-location-list").css("overflow", "scroll");
                                            if ($('.velo-popup .velo_add_distance').length) {
                                                $('.velo-popup .velo_add_distance').hide();
                                            }
                                            showHideContent();       
                                        },
                                    });
                                }    
                            });
                        {/if}
                        {*changes end*}       
                    }
                {/if}
            </script>
            {if $is_all_stores_disabled == 0}
                <script async defer
                    src="https://maps.googleapis.com/maps/api/js?key={$map_api|escape:'htmlall':'UTF-8'}&callback=initialize&senser=false">
                </script>
    
                <style>
                    .velo-location-list ul li{
                        background-image:url("{$locator_icon|escape:'quotes':'UTF-8'}"); 
                        background-size: 30px;
                    }
                    .velo-directions-link {
                        clear:none !important;
                    }
                    {if isset($kb_superck)}
                        .velo-field-inline label {
                            font-size: inherit;
                        }
                        .velo-location-list ul li a, .velo-location-list ul li {
                            font-size: inherit;
                        }
                        .kb_pref_submit_btn {
                            margin:0;
                        }
                        .velo-store-date-time-block button {
                            margin-left: 0px;
                        }
                        #velsof_supercheckout_form .velo-location-list .velo-store-select-link,
                        #velsof_supercheckout_form .velo-location-list .velo-directions-link  {
                            color:#fff !important;
                            text-decoration: none !important;
                        }
                    {/if}
                </style>
            </div>
            {if !isset($kb_superck)}
                <style>
                    {if isset($spinner)} 
                        /*.modal {
                            display:    none;
                            position:   fixed;
                            z-index:    1000;
                            top:        0;
                            left:       0;
                            height:     100%;
                            width:      100%;
                            background: rgba( 255, 255, 255, .8 )
                                url('{$spinner|escape:'quotes':'UTF-8'}')
                                50% 50%
                                no-repeat;
                        }*/

                        /* When the body has the loading class, we turn
                           the scrollbar off with overflow:hidden */
                        /*body.loading {
                            overflow: hidden;
                        }*/

                        /* Anytime the body has the loading class, our
                           modal element will be visible */
                        /*body.loading .modal {
                            display: block;
                        }*/
                    {/if}
                </style>
                <script></script>
                <div class="modal"></div>
            {/if}
        {/if}