/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 * We offer the best and most useful modules PrestaShop and modifications for your online store.
 *
 * @author    knowband.com <support@knowband.com>
 * @copyright 2018 Knowband
 * @license   see file: LICENSE.txt
 * @category  PrestaShop Module
 *
 *
 */

if (typeof kb_pickup_carrier_id !== 'undefined' && $('#delivery_option_' + kb_pickup_carrier_id).length > 0) {
    var is_disabled_checkout = 0;

    function showStuff(id) {
        document.getElementById('kb-popup-content').classList.remove('kb-contentOpen');
        document.getElementById(id).classList.remove('kb-modal-open');
        $('body').removeClass('popupmodal-open'); /* 28062018 */
        document.getElementById(id).style.display = 'none';
        document.getElementById('kb-modal-backdropDiv').style.display = 'none';
    }

    function displayPickupStorePopup(attr)
    {
        $('.kb-modal-backdrop').show();
        $('#kb_pts_carrier_block').show();
        $('body').addClass('popupmodal-open');
        $('.kb-box-modal').addClass('kb-modal-open');
        $('.kb-modal-contents').addClass('kb-contentOpen');
    }

    jQuery(window).on("load", function () {
        showdatepicker();

    });



    var page_name = 'module-supercheckout-supercheckout';
    $(document).ready(function () {
        
        //code added for removing html text on hover
        $('.velo-store-map').mouseover(function () {
            $('.velo-store-map').find('div').each(function () {
                if ($(this).attr('title') != '' && $(this).find('img').length > 0) {
                    $(this).attr('title', '');
                }
            });
        });
        
        if (typeof updated_pickup_text == 'undefined') {
            updated_pickup_text = 'Update Pickup Store';
        }
    //    if(parseInt($('input[id^="delivery_option_"]:checked').val()) == parseInt(kb_pickup_carrier_id)){
    //        $('button[name="confirmDeliveryOption"]').hide();
    //    }
        // chnages by rishabh jain
        $('.button-show-more').on('click', function () {
            if ($(this).parent().parent().next().is(":visible")) {
                $(this).html(show_more);
                $(this).parent().parent().next().hide();
            } else {
                $(this).html(hide);
                $(this).parent().parent().next().show();
            }
        });
    // changes over

         if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion == '0'){
             $('#kb_pickup_selected_store').parent().parent().hide();
         }
        $('#order-summary-content .summary-selected-carrier').append($('.kb-pickup-confirmation-block'));
        $('.kb-pickup-confirmation-block').css('margin-top', '10px');
        $('#velo_address_search').keypress(function (e) {
            if (e.which == 13)
                return false;
            if (e.which == 13)
                e.preventDefault();
        });

        if (typeof (is_all_stores_disabled) != "undefined" && is_all_stores_disabled == 1) {
            $('button[name="confirmDeliveryOption"]').click(function () {
                if ($('#preferred-alert').length) {
                    $('#preferred-alert').remove();
                }
                var key = $('input[id^="delivery_option_"]:checked').val().replace(",", "");
                if (key) {
                    if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                        $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -current')
                        $('#checkout-payment-step').attr('class', 'checkout-step -unreachable');
                        $('#hook-display-before-carrier').before('<div id="preferred-alert" class="alert alert-danger"><span>' + no_stores_available + '</span></div>');
                        if ($('#preferred-alert').length) {
                            $('html, body').animate({
                                scrollTop: $("#preferred-alert").offset().top - 200
                            }, 1000);
                        }
                        return false;
                    } else {
                        $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -complete');
                        $('#checkout-payment-step').attr('class', 'checkout-step -reachable -current');
                    }
                }
            });

            $('#checkout-payment-step').on('click', function () {
                $('#preferred-alert').remove();
                var id_address = prestashop.cart.id_address_delivery;
                var key = $('#delivery_option_' + id_address).val().replace(",", "");
                if (key) {
                    if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                        $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -current')
                        $('#checkout-payment-step').attr('class', 'checkout-step -unreachable');
                        $('#hook-display-before-carrier').before('<div id="preferred-alert" class="alert alert-danger"><span>' + no_stores_available + '</span></div>');
                        if ($('#preferred-alert').length) {
                            $('html, body').animate({
                                scrollTop: $("#preferred-alert").offset().top - 200
                            }, 1000);
                        }
                    }
                } else {
                    $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -complete');
                    $('#checkout-payment-step').attr('class', 'checkout-step -reachable -current');
                }
            });
        }
        if ($('#delivery_confirmation').length) {
            $('button[name="confirmDeliveryOption"]').click(function () {
                $('#preferred-alert').each(function () {
                    $(this).remove();
                });

                if ($('#delivery_confirmation').val() != 'yes') {
                    $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -current')
                    $('#checkout-payment-step').attr('class', 'checkout-step -unreachable');

                    if ($('input[name="kb_pickup_selected_store"]').val() == '') {
                        //$('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
                        $('.velo-store-date-time-block').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');

                    }/* else if ($('#kb_pickup_select_date').val() == '') {
                        $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_date_empty + '</span></div>');
                    }*/

                    //$('#hook-display-before-carrier').before('<div id="preferred-alert" class="alert alert-danger"><span>' + add_kb_preferred_time + '</span></div>');

                    if ($('#preferred-alert').length) {
                        $('html, body').animate({
                            scrollTop: $("#preferred-alert").offset().top - 200
                        }, 1000);
                    }
                    return false;
                } else {
                    $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -complete');
                    $('#checkout-payment-step').attr('class', 'checkout-step -reachable -current');
                }
            });


            $('#checkout-payment-step').on('click', function () {
                $('#preferred-alert').remove();
                if ($('#delivery_confirmation').val() != 'yes') {
                    $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -current')
                    $('#checkout-payment-step').attr('class', 'checkout-step -unreachable');

                    //$('#hook-display-before-carrier').before('<div id="preferred-alert" class="alert alert-danger"><span>' + add_kb_preferred_time + '</span></div>');
                    $('#hook-display-before-carrier').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
                    if ($('#preferred-alert').length) {
                        $('html, body').animate({
                            scrollTop: $("#preferred-alert").offset().top - 200
                        }, 1000);
                    }
                } else {
                    $('#checkout-delivery-step').attr('class', 'checkout-step -reachable -complete');
                    $('#checkout-payment-step').attr('class', 'checkout-step -reachable -current');
                }
            });
        }

        if (typeof prestashop.page.page_name != 'undefined') {
            page_name = prestashop.page.page_name;

        }



        $('.velo-pickup-store-map #map').bind("DOMSubtreeModified", function () {
            $('button.gm-ui-hover-effect').click(function () {
                event.preventDefault();
                return false;
            });
            if ($('.velo-popup .kb-store-hours').length) {
                $('.velo-popup .kb-store-hours').hide();
            }
            if ($('.velo-popup .velo-show-more').length) {
                $('.velo-popup .velo-show-more').hide();
            }
            if ($('.velo-popup .kb-store-url').length) {
                $('.velo-popup .kb-store-url').show();
            }
            if ($('.velo-popup .velo_store_icon').length) {
                $('.velo-popup .velo_store_icon').show();
            }
            if ($('.velo-popup .velo-directions-button').length) {
                $('.velo-popup .velo-directions-button').hide();
            }
            if ($('.velo-popup .velo-directions-link').length) {
                $('.velo-popup .velo-directions-link').hide();
            }
            if ($('.velo-popup .velo_add_distance').length) {
                $('.velo-popup .velo_add_distance').hide();
            }
            if ($('.velo-popup .extra_content .kb-store-url').length) {
                $('.velo-popup .extra_content .kb-store-url').show();
            }
            if ($('.velo-popup .extra_content .velo_add_number').length) {
                $('.velo-popup .extra_content .velo_add_number').show();
            }
            if ($('.velo-popup .extra_content').length) {
                $('.velo-popup .extra_content').show();
            }
            $('.velo-popup .velo-store-select-link ').hide();
    //        $('.velo-popup #kb-store-image').show();
        });


        $('.velsof_supercheckout_form').bind("DOMSubtreeModified", function () {
            showdatepicker();

            if ($('input[name="kb_pickup_select_date"]').val() != '') {
                $('.kb_pickup_carrier').closest('.highlight').append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+updated_pickup_text+'</a></td>');
            }
        });

        $("#supercheckout_confirm_order").unbind("click");
        $('#supercheckout_confirm_order').click(function (event) {
            $(this).removeClass('kb_pickup_carrier');
            if ($('#preferred-alert').length) {
                $('#preferred-alert').remove();
            }
            if (typeof (is_all_stores_disabled) != "undefined" && is_all_stores_disabled == 1) {
                if ($('input.delivery_option_radio').is(':checked')) {
                    $('input.delivery_option_radio:checked').each(function () {
                        var key = $(this).val();
                        var id_address = $(this).prop('name').match(/\d+/).toString();
                        if ((page_name == 'module-supercheckout-supercheckout') && key) {
                            if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                               // alert('hi');
                                $('#supercheckout-empty-page-content').before('<div id="preferred-alert" class="alert alert-danger"><span>' + no_stores_available + '</span></div>');
                                $("html, body").animate({
                                    scrollTop: 0
                                }, "fast");
                                return false;
                            } else {
                                placeOrder();
                            }
                        }
                    });
                } else {
                    placeOrder();
                }
            } else {
                if ($('#delivery_confirmation').val() == '') {
                    if ($('input.delivery_option_radio').is(':checked')) {
                        $('input.delivery_option_radio:checked').each(function () {
                            var key = $(this).val();
                            var id_address = $(this).prop('name').match(/\d+/).toString();
                            if ((page_name == 'module-supercheckout-supercheckout') && key) {
                                if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                                    if (page_name == 'module-supercheckout-supercheckout') {
                                        $(this).addClass('kb_pickup_carrier');
                                        $('form[id="velsof_supercheckout_form"]').attr('onsubmit', 'return validate_shipping_delivery()');
                                    } else {
        //                                placeOrder();
                                    }
                                    displayPickupStorePopup();
                                    return false;
                                } else {
                                    placeOrder();
                                }
                            }
                        });
                } else {
                    placeOrder();
                }
                } else {
                    placeOrder();
                }
            }
            });
    //    }

        if (typeof page_name != 'undefined') {
            if (page_name == 'checkout') {
                var id_address = prestashop.cart.id_address_delivery;

                if (typeof kb_default_id_carrier != 'undefined') {
                    if (typeof (is_all_stores_disabled) != "undefined" && is_all_stores_disabled == 1) {
                        if (parseInt(kb_default_id_carrier) == parseInt(kb_pickup_carrier_id)) {
                            $('#delivery_option_' + kb_pickup_carrier_id).closest('.delivery-option').append($('#kb_no_store_available'));
                            $('#kb_no_store_available').show();
                        }
                    } else {
                        $('#kb_pts_carrier_block').hide();
                        if (kb_default_id_carrier) {
                            if (parseInt(kb_default_id_carrier) == parseInt(kb_pickup_carrier_id)) {
                                if ($('#kb_pickup_selected_store').val() == '' && $('#kb_pickup_select_date').val() == '') {
                                    $('#delivery_confirmation').val('');
                                }

                                $('#delivery_option_' + kb_pickup_carrier_id).closest('.delivery-option').append($('#kb_pts_carrier_block'));
                                $('form[name="carrier_area"]').attr('onsubmit', 'return validate_shipping_delivery()');
                                $('#kb_pts_carrier_block').show();
                                $('#map').on('pageshow', function () {
                                    google.maps.event.trigger(map, "resize");
                                });
                            } else {
                                $('#delivery_confirmation').val('yes');
                            }
                        }
                    }
                } else {
                    if ($('#delivery_confirmation').length > 0) {
                        $('#delivery_confirmation').val('yes');
                    }
                }

                $(document).on('change', 'input[id^="delivery_option_"]', function () {
                    var key = $(this).val().replace(",", "");
                    if ($('#kb_pts_carrier_block').length > 0) {
                        $('#kb_pts_carrier_block').hide();
                    }
                    if ($('#kb_no_store_available').length > 0) {
                        $('#kb_no_store_available').hide();
                    }
    //                if(parseInt(key) == parseInt(kb_pickup_carrier_id)){
    //                    $('[name="confirmDeliveryOption"]').hide();
    //                }else{
    //                    $('[name="confirmDeliveryOption"]').show();
    //                }
                    if (key) {
                        if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                            // changes by rishabh jain
                            if (typeof (is_all_stores_disabled) != "undefined" && is_all_stores_disabled == 1) {
                                $(this).closest('.delivery-option').append($('#kb_no_store_available'));
                                $('#kb_no_store_available').show();
                            } else {
                                // changes over

                                /*if ($('#kb_pickup_selected_store').val() == '' && $('#kb_pickup_select_date').val() == '') {
                                    $('#delivery_confirmation').val('');
                                }*/
                                $('#delivery_confirmation').val('');
                                $(this).closest('.delivery-option').append($('#kb_pts_carrier_block'));
                                $('form[name="carrier_area"]').attr('onsubmit', 'return validate_shipping_delivery()');
                                $('#kb_pts_carrier_block').show();
                                $('#map').on('pageshow', function () {
                                    google.maps.event.trigger(map, "resize");
                                });
                            }
    //                        return;
                        } else {
                            if ($('#delivery_confirmation').length > 0) {
                                $('#delivery_confirmation').val('yes');
                            }
                        }
                    }
                });
    //            }

                $('#carrier_area form[name="carrier_area"]').append($('.cart_navigation'));

            } else if (page_name == 'module-supercheckout-supercheckout') {
                if ($('input[name="kb_pickup_select_date"]').val() != '') {
                    $('.kb_pickup_carrier').closest('.highlight').append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+ updated_pickup_text +'</a></td>');
                }
                //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
            } else if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                 if ($('input[name="kb_pickup_select_date"]').val() != '') {
                    $('.delivery_option_'+kb_pickup_carrier_id).append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+ updated_pickup_text +'</a></td>');
            }
                //chanegs end
        }
        }

        $('#order-confirmation .cart_navigation').before($('.kb-pickup-confirmation-block'));
        $('form[name="carrier_area"]').removeAttr('onsubmit');




        $(document).on("click", "#searchStoreBtn", function () {
            searchLocations();
        });
        $(document).on("click", "#resetStoreBtn", function () {
            resetLocations();
        });


        $(document).on('click', '.velo-store-select-link', function () {
            event.preventDefault();

            $('.velo-store-select-link').removeClass('store-selected');
            $('.velo-store-select-link').html(kb_select);
            $('#delivery_confirmation').val('');
            var id_store = $(this).data('key');
            $(this).addClass('store-selected');
            //$(this).html(kb_selected + ' <i class="icon-check"></i>');
            $(this).html(kb_selected);
            $('input[name="kb_pickup_selected_store"]').val(id_store);
            
            /* Start changes done by Vishal on 10th August 2019 : to submit form on store selection  */
            
             if(is_enabled_date_selcetion==0){
                 if(page_name == 'checkout'){
    //                 $('[name="confirmDeliveryOption"]').hide();
                 }
                 if(page_name == 'module-supercheckout-supercheckout'){
                    submitPreferredTimebtn();
                 }
                  if(page_name == 'checkout'){
                      if(is_enabled_date_selcetion == '0'){
                  submitPreferredTimebtn();
                    $('form[name="carrier_area"]').attr('onsubmit', 'return validate_shipping_delivery()');
    //              $('#js-delivery').submit();
    //              $('#checkout-payment-step').children().click();
              }
              }
              //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
              if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                    submitPreferredTimebtn();
            }
              //changes end
            }
            
            /* End changes done by Vishal on 10th August 2019 : to submit form on store selection  */
            
            var av_store_details = $.parseJSON($('input[name="kb_av_store_details"]').val());
            for (var i in av_store_details) {
                if (id_store == i) {
                    $('input[name="kb_store_select_date"]').val(JSON.stringify(av_store_details[i]));
                }
            }
            if ($("#kb_pickup_select_date").length && page_name != 'module-supercheckout-supercheckout') {
                $('html, body').animate({
                    scrollTop: $("#kb_pickup_select_date").offset().top - 200
                }, 1000);
            }
            //changes by vishal on 24 dec 2020 to resolve the date time picker doesn't changes on selecting the store issue
            $('#kb_pickup_select_date').datetimepicker('remove');
            showdatepicker();
            //changes end
        });

        var day_select = '';
        if (typeof date_format != 'undefined') {
            showdatepicker();
        }

        $body = $("body");
        $(document).on({
        });
        
                
    });

    function showHideExtraContent(e, id_store) {
        var element_id = 'button-show-more-' + id_store;
        if ($('#' + element_id).parent().parent().next().is(":visible")) {
            $('#' + element_id).html(show_more);
            $('#' + element_id).parent().parent().next().hide();
    //        if ($('.velo-popup .extra_content .velo_add_number').length) {
    //            $('.velo-popup .extra_content .velo_add_number').show();
    //        }

        } else {
            $('#' + element_id).html(hide);
            $('#' + element_id).parent().parent().next().show();
    //        if ($('.velo-popup .extra_content .velo_add_number').length) {
    //            $('.velo-popup .extra_content .velo_add_number').show();
    //        }
        }


    }
    function submitPreferredTimebtn()
    {
        if (page_name == 'module-supercheckout-supercheckout') {
            $('.updatePickupTime').closest('td').remove();
            $('#kb_selected_store_name').closest('td').remove();
            $('#kb_selected_date').closest('td').remove();
            hideGeneralError();

        }
        //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
        if ((typeof kb_onepage !== 'undefined') && kb_onepage == 1) {
            $('.updatePickupTime').closest('td').remove();
            $('#kb_selected_store_name').closest('td').remove();
            $('#kb_selected_date').closest('td').remove();

        }
        //changes end
        $('#delivery_confirmation').val('');
        $('#preferred-alert').remove();
        // changes by rishabh jain
        
        // Start : code added by vishal on 8th august 2019  : to implement data selection functionality

        var date = '';
        if (typeof is_enabled_date_selcetion != 'undefined') {
            if (is_enabled_date_selcetion == '0') {
                var date = '';
            } else {
        var date = $('input[name="kb_pickup_select_date"]').val().trim();
            }
        }

        // End : code added by vishal on 8th august 2019  : to implement data selection functionality

        var preferred_store = $('input[name="kb_pickup_selected_store"]').val();
        if (preferred_store == '' || preferred_store == '0') {
            if (page_name == 'module-supercheckout-supercheckout') {
                displayGeneralError(preferred_store_unselected);
                showStuff('kb_pts_carrier_block');
                //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
            } else if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                $('#thecheckout-shipping .inner-area').prepend('<div id="kb_shipping_error" class="error-msg">'+ preferred_store_unselected +'</div>');
                showStuff('kb_pts_carrier_block');
                //changes end
            }else {
                return $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
            }
        }
        
        //changes done on 10th august 2019 by vishal :  replace (date != '') to (preferred_store != '') for implement date selection functionality
        var seperator = '?';
        if (kb_store_url.indexOf('?') >= 0) { seperator = '&'; }
        if (preferred_store != '' && (is_enabled_date_selcetion == 1)?(date != ""):(1)) {
            $.ajax({
                type: 'post',
                dataType: 'json',
                cache: false,
                async: false,
                beforeSend: function () {
                    if (page_name == 'module-supercheckout-supercheckout') {
                        $('.updatePickupTime').closest('td').remove();
                    }
                    //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
                    if ((typeof kb_onepage !== 'undefined') && kb_onepage == 1) {
                        $('.updatePickupTime').closest('td').remove();
                    }
                    //changes end
                },
                url: kb_store_url + seperator +'pickupStoreMapping=1',
                data: {preferred_date: date, preferred_store: preferred_store},
                success: function (rec) {
                    if (rec['status']) {
                        $('#delivery_confirmation').val('yes');
                        if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                            $('.delivery_option_'+kb_pickup_carrier_id).append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+updated_pickup_text+'</a></td>');

                            if (typeof rec.store[0].store_name != 'undefined' && rec.store[0].store_name != "")
                                $('.delivery_option_'+kb_pickup_carrier_id).append('<td style="display: block;padding-left: 5%;margin-bottom: 3%;"><b>'+ selected_store_text + ' </b><span id="kb_selected_store_name">' + rec.store[0].store_name + '<span></td>');

                            if (typeof rec.store[0].preferred_date != 'undefined' && rec.store[0].preferred_date != "")
                                $('.delivery_option_'+kb_pickup_carrier_id).append('<td style="display: block;padding-left: 5%;margin-bottom: 3%;"><b>'+ selected_store_date + ' </b><span id="kb_selected_date">' + rec.store[0].preferred_date + '<span></td>');
                             if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion==0){
                                 $('#kb_pickup_selected_store').parent().parent().show();
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_store + '</span></div>');
                            }else{
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_time + '</span></div>');
                            }
                            showStuff('kb_pts_carrier_block');
                            //changes end
                        } else if (page_name == 'module-supercheckout-supercheckout') {
                            $('.kb_pickup_carrier').closest('.highlight').append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+updated_pickup_text+'</a></td>');

                            if (typeof rec.store[0].store_name != 'undefined' && rec.store[0].store_name != "")
                                $('.kb_pickup_carrier').closest('.highlight').append('<td style="display: block;padding-left: 5%;margin-bottom: 3%;"><b>'+ selected_store_text + ' </b><span id="kb_selected_store_name">' + rec.store[0].store_name + '<span></td>');

                            if (typeof rec.store[0].preferred_date != 'undefined' && rec.store[0].preferred_date != "")
                                $('.kb_pickup_carrier').closest('.highlight').append('<td style="display: block;padding-left: 5%;margin-bottom: 3%;"><b>'+ selected_store_date + ' </b><span id="kb_selected_date">' + rec.store[0].preferred_date + '<span></td>');
                             if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion==0){
                                 $('#kb_pickup_selected_store').parent().parent().show();
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_store + '</span></div>');
                            }else{
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_time + '</span></div>');
                            }
                            showStuff('kb_pts_carrier_block');
                        } else {
                            if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion==0){
                                $('#kb_pickup_selected_store').parent().parent().show();
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_store + '</span></div>');
                            }else{
                                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-success"><span>' + success_preferred_time + '</span></div>');
                            }    
                        }
                    } else {
                        $('#delivery_confirmation').val('');
                        if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                            $('#thecheckout-shipping .inner-area').prepend('<div id="kb_shipping_error" class="error-msg">'+ error_preferred_time +'</div>');
                            showStuff('kb_pts_carrier_block');
                            //changes end
                        } else if (page_name == 'module-supercheckout-supercheckout') {
                            displayGeneralError(error_preferred_time);
                            showStuff('kb_pts_carrier_block');
                        } else {
                            $('#kb_pickup_selected_store').parent().parent().show();
                            $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + error_preferred_time + '</span></div>');

                        }
                    }

                }
            });
        } else {
            if (page_name == 'module-supercheckout-supercheckout') {
                displayGeneralError(preferred_date_empty);
                showStuff('kb_pts_carrier_block');
                //changes by vishal for custom change (conpatibility with onepagecheckoutps checkout module)
            } else if((typeof kb_onepage !== 'undefined') && kb_onepage == 1){
                $('#thecheckout-shipping .inner-area').prepend('<div id="kb_shipping_error" class="error-msg">'+ preferred_date_empty +'</div>');
                showStuff('kb_pts_carrier_block');
                //changes end
            }else {
                $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_date_empty + '</span></div>');
            }
        }
        if ($('#preferred-alert').length && page_name != 'module-supercheckout-supercheckout') {
            $('html, body').animate({
                scrollTop: $("#preferred-alert").offset().top - 200
            }, 1000);
        }
    }

    function showHideContent() {
        $('.button-show-more').on('click', function () {
            if ($(this).parent().parent().next().is(":visible")) {
                $(this).html(show_more);
                $(this).parent().parent().next().hide();
            } else {
                $(this).html(hide);
                $(this).parent().parent().next().show();
            }
        });
    }

    function showdatepicker()
    {
        if (typeof date_format != 'undefined') {
            $('#kb_pickup_select_date').datetimepicker({
                language: lang_iso,
                weekStart: 1,
                format: date_format,
                todayBtn: false,
                autoclose: 1,
                todayHighlight: 0,
                fontAwesome: 1,
                startDate: days_gap,
                endDate: maximum_days,
                minView: allowDateTime,
                forceParse: 0,
                onRenderDay: function (date) {
                    if ((date.getUTCDate() < parseInt(hour_gap_date)) && (date.getUTCMonth() < parseInt(hour_gap_month))) {
                        return ['disabled'];
                    }
                    var day = date.getUTCDay() - 1;
                    if (day == -1) {
                        day = 6;
                    }
                    if ($('input[name="kb_store_select_date"]').val() != '') {
                        var select_date = JSON.parse($('input[name="kb_store_select_date"]').val());
                        if (select_date != '') {
                            for (var i = 0 in select_date) {
                                    if (typeof select_date[i].hours == 'undefined' || select_date[i].hours == '') {
                                    if (i == day) {
                                        return ['disabled'];
                                    }
                                }
                            }
                        }
                    }
                },
                onRenderHour: function (date) {
                    if (time_slot == 1) {
                        var day = date.getUTCDay() - 1;
                        if (day == -1) {
                            day = 6;
                        }
                        if (date.getUTCHours() <= parseInt(hour_gap_hour) && date.getUTCDate() <= parseInt(hour_gap_date) && date.getUTCMonth() <= parseInt(hour_gap_month)) {
                            return ['disabled'];
                        }

                        if ($('input[name="kb_store_select_date"]').val() != '') {
                            var select_date = JSON.parse($('input[name="kb_store_select_date"]').val());
                            if (select_date != '') {
                                for (var i in select_date) {
                                    if (typeof select_date[i].hours == 'undefined') {
                                        if (i == day) {
                                            return ['disabled'];
                                        }
                                    } else {
                                        if (day == i) {
                                            var select_hrs = select_date[i].hours.replace(/\s/g, '');
                                            select_hrs = select_hrs.split('-');
                                            var hr1 = '';
                                            hr1 = Number(select_hrs[0].match(/^(\d+)/)[1]);
                                            if (select_hrs[0].toLowerCase().indexOf('pm') >= 0 || select_hrs[0].toLowerCase().indexOf('am') >= 0) {
                                                hr1 = convertTimeTo24(select_hrs[0], true, false);
                                            }

                                            var hr2 = Number(select_hrs[1].match(/^(\d+)/)[1]);
                                            if (select_hrs[1].toLowerCase().indexOf('pm') >= 0 || select_hrs[1].toLowerCase().indexOf('am') >= 0) {
                                                hr2 = convertTimeTo24(select_hrs[1], true, false);
                                            }
                                            var myArray = [];
                                            if ((hr1 < hr2) && hr2 <= 23 && hr1 >= 0) {
                                                for (var m = hr1; m <= hr2; m++) {
                                                    myArray.push(m);
                                                }
                                            }
                                            if ($.inArray(date.getUTCHours(), myArray) == -1) {
                                                return ['disabled'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
            }).on('changeDate', function (ev) {
                $('#preferred-alert').remove();
                if (ev != '') {
                    if (ev.date != '') {
                        var date2 = new Date(ev.date);
                        $.ajax({
                            type: 'post',
                            dataType: 'json',
                            cache: false,
                            beforeSend: function () {
                                $('body').addClass("loading");
                            },
                            url: kb_store_url + '?validatePickupDate=1',
                            data: {date: ev.date, id_store: $('input[name="kb_pickup_selected_store"]').val(), selectedDay: date2.getDay(), selectedHour: date2.getHours()},
                            success: function (rec) {
                                $('#preferred-alert').remove();
                                if (rec == '0') {
                                    $('#kb_pickup_select_date').val('');
                                    //changes by tarun
                                    if ($('input[name="kb_pickup_selected_store"]').val()) {
                                        $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
                                    } else {
                                        $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
                                    }
                                    //changes over
                                }
                            },
                            complete: function () {
                                $('body').removeClass("loading");
                            }
                        });
                    }
                    if ($('#preferred-alert').length) {
                        $('html, body').animate({
                            scrollTop: $("#preferred-alert").offset().top - 200
                        }, 1000);
                    }
                }
            });
        }
    }

    $(document).ajaxComplete(function (event, xhr, options) {
        if (options.url.indexOf('supercheckout') != -1 && (page_name == 'module-supercheckout-supercheckout')) {
            if ($('#velsof_supercheckout_form').length) {
                showdatepicker();
                $('input.delivery_option_radio:checked').each(function () {
                    var key = $(this).val();
                    var id_address = $(this).prop('name').match(/\d+/).toString();
                    if (key) {
                        if (parseInt(key) == parseInt(kb_pickup_carrier_id)) {
                            if (page_name == 'module-supercheckout-supercheckout') {
                                $(this).addClass('kb_pickup_carrier');
                            }
                        }
                    }
                });
            }
        }
        
        if ($('input[name="kb_pickup_select_date"]').val() != '' && !$('.kb_pickup_carrier').closest('.highlight').find('.updatePickupTime').length) {
            $('.kb_pickup_carrier').closest('.highlight').append('<td><a href="javascript:displayPickupStorePopup();" class="updatePickupTime btn btn-alert" style="color:#fff;">'+updated_pickup_text+'</a></td>');
        }
    });

    function validate_shipping_delivery()
    {
        $('#preferred-alert').remove();
        if ($('#delivery_confirmation').val() == 'yes') {
            return true;
        } else {
            $('#kb_pickup_selected_store').parent().parent().show();
            //$('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + add_kb_preferred_time + '</span></div>');
            $('.velo-field-preferred-date').before('<div id="preferred-alert" class="alert alert-danger"><span>' + preferred_store_unselected + '</span></div>');
            return false;
        }

    }

    function convertTimeTo24(select_hrs, hrs, min)
    {
        var sel_hours = Number(select_hrs.match(/^(\d+)/)[1]);
        var sel_minutes = Number(select_hrs.match(/:(\d+)/)[1]);
        var ishoursAM = select_hrs.toLowerCase().indexOf('am');
        var ishoursPM = select_hrs.toLowerCase().indexOf('pm');
        var AMPM = '';
        if (ishoursAM >= 0) {
            AMPM = 'am';
        }
        if (ishoursPM >= 0) {
            AMPM = 'pm';
        }
    //    var AMPM = select_hrs.match(/\s(.*)$/)[1];
        if (AMPM == "pm" && sel_hours < 12) {
            sel_hours = sel_hours + 12;
        }
        if (AMPM == "am" && sel_hours == 12) {
            sel_hours = sel_hours - 12;
        }
        var sHours = sel_hours.toString();
        var sMinutes = sel_minutes.toString();
        if (sel_hours < 10) {
            sHours = "0" + sHours;
        }
        if (sel_minutes < 10) {
            sMinutes = "0" + sMinutes;
        }
        if (hrs == true) {
            return sHours;
        }
        if (min == true) {
            return sMinutes;
        }
    }

    function searchLocations() {
        var address = $("#velo_address_search").val().trim();
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({address: address}, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                searchLocationsNear(results[0].geometry.location);
            } else {
                alert(address + ' ' + kb_not_found);
            }
        });
    }


    function searchLocationsNear(center) {
        var radius = $("#velo_within_distance").val();
        var result_limit = $("#velo_limit").val();
        
        /* Start changes done by Vishal on 13th August 2019 : set filter_pickup according to condition  */
        
        if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion == 1){
        var filter_pickup = $('input[name="kb_pickup_select_date"]').length;
        }else{
             var filter_pickup = 1;
        }
        
        /* End changes done by Vishal on 13th August 2019 : set filter_pickup according to condition  */
        
        var lat = center.lat();
        var lng = center.lng();
        var search_string = $("#velo_address_search").val();
        var controller = $(".page_controller").val();
        $body = $("body");
        $.ajax({
            type: 'post',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $("#viewLocationDetails .ajax_alert").remove();
                $("#view-location-detail-div").html('');
                $("#viewLocationDetails .loadingModel").show();
                $body.addClass("loading");
            },
            url: kb_store_url,
            complete: function () {
                $body.removeClass("loading");
            },
            data: {searchStore: 1, lat_val: lat, lng_val: lng, radius_val: radius, result_limit: result_limit, filter_pickup: filter_pickup, page_controller: controller},
            success: function (html) {
                $(".velo-location-list").html('');
                $(".velo-location-list").html(html['body']);
                $(".velo-location-list").css("overflow", "scroll");


                var self_location = [
                    [search_string, lat, lng]
                ];


                var html_new = JSON.parse(html['location_arr_json']);
                var locations = html_new;
                var myOptions = {
                    center: new google.maps.LatLng(default_latitude, default_longitude),
                    zoom: zoom_level
                };
                var map = new google.maps.Map(document.getElementById("map"),
                    myOptions);
                setMarkers(map, locations)
                setMarkerSelfForSearch(map, self_location);
                if ($('.velo-popup .velo_add_distance').length) {
                    $('.velo-popup .velo_add_distance').hide();
                }
                showHideContent();
    //google.maps.event.addDomListener(window, "load", initialize);

            }
        });
    }

    function resetLocations() {
        $body = $("body");
        var filter_pickup = $('input[name="kb_pickup_select_date"]').length;
        var controller = $(".page_controller").val();
        $.ajax({
            type: 'post',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $body.addClass("loading");
            },
            url: kb_store_url,
            complete: function () {
                $body.removeClass("loading");
            },
            data: {searchStore: 1, reset: 1, filter_pickup: filter_pickup, page_controller: controller},
            success: function (html) {
                $(".velo-location-list").html('');
                $(".velo-location-list").html(html['body']);
                $(".velo-location-list").css("overflow", "scroll");
                if ($('.velo-popup .velo_add_distance').length) {
                    $('.velo-popup .velo_add_distance').hide();
                }
                showHideContent();
                $("#velo_address_search").val('');
                $("#velo_within_distance").val('25');
                $("#velo_limit").val('25');
                initializeKb();
    //google.maps.event.addDomListener(window, "load", initialize);

            }
        });
    }
    function setMarkerSelfForSearch(map, locations) {
        var marker, i
        for (i = 0; i < locations.length; i++)
        {
            var loan = locations[i][0]
            var lat = locations[i][1]
            var long = locations[i][2]
            latlngset = new google.maps.LatLng(lat, long);
            var marker = new google.maps.Marker({
                map: map, title: loan, position: latlngset, icon: current_location_icon, animation: google.maps.Animation.DROP
            });
            map.setCenter(marker.getPosition())
            marker.setPosition(latlngset);
            var content = loan
            var infowindow = new google.maps.InfoWindow()
            google.maps.event.addListener(marker, 'click', (function (marker, content, infowindow) {
                return function () {
                    infowindow.setContent(content);
                    infowindow.open(map, marker);
                };
            })(marker, content, infowindow));
            google.maps.event.addListener(marker, 'mouseover', function (e) {
                    e.ya.target.parentElement.removeAttribute('title')
                });
        }
    }

    (function ($) {
        $(window).on("load", function () {
            if ($(".velo-location-list").length > 0) {
                /*$(".velo-location-list").mCustomScrollbar({
                    theme: "minimal"
                });*/
            }

        });
    })(jQuery);

    function defaultLocation(latlngset, map, infowindow, marker) {
        var geocoder = new google.maps.Geocoder;
        var content = '';
        return geocoder.geocode({'location': latlngset}, function (results, status) {
            if (status === 'OK') {
                if (results[0]) {
                    map.setZoom(11);
                    content = results[0].formatted_address;
                }
            }
            infowindow.setContent(content);
            infowindow.open(map, marker);
        });
    }



    function handleLocationError(browserHasGeolocation, infoWindow, pos) {
        infoWindow.setPosition(pos);
        infoWindow.setContent(browserHasGeolocation ?
            'Error: The Geolocation service failed.' :
            'Error: Your browser doesn\'t support geolocation.');
        infoWindow.open(map);
    }

    function hidepopupcontent() {
        $('.velo-popup .velo-store-select-link').remove();

        if ($('.velo-popup .velo-directions-link').length) {
            $('.velo-popup .velo-directions-link').hide();
        }
        if ($('.velo-popup .velo_add_distance').length) {
            $('.velo-popup .velo_add_distance').hide();
        }
        if ($('.velo-popup .extra_content .kb-store-url').length) {
            $('.velo-popup .extra_content .kb-store-url').show();
        }
        if ($('.velo-popup .extra_content .velo_add_number').length) {
            $('.velo-popup .extra_content .velo_add_number').show();
        }
        if ($('.velo-popup .extra_content').length) {
            $('.velo-popup .extra_content').show();
        }
        if ($('.velo-popup .kb-store-hours').length) {
            $('.velo-popup .kb-store-hours').hide();
        }
        if ($('.velo-popup .velo-show-more').length) {
            $('.velo-popup .velo-show-more').hide();
        }
        if ($('.velo-popup .velo-directions-button').length) {
            $('.velo-popup .velo-directions-button').hide();
        }
        if ($('.kb-store-url').length) {
            $('.velo-popup .kb-store-url').show();

        }
        if ($('.velo_store_icon').length) {
            $('.velo-popup .velo_store_icon').show();

        }
    }
    function focus_and_popup(DivId)
    {
    //    var placeName = $('#' + DivId).find('.velo_add_name').text();
    //    var placeAddress1 = $('#' + DivId).find('.velo-add-address').text();
    ////    $('#kb-store-image').hide();
    //
    //
    //    var placeAddress = $('#' + DivId).html();
    //    if (placeAddress1 != '') {
    //        var geocoder = new google.maps.Geocoder();
    //        geocoder.geocode({'address': placeAddress1}, function (results, status) {
    //
    //            var lat = results[0].geometry.location.lat();
    //            var lng = results[0].geometry.location.lng();
    //            var myLatLng = {lat: lat, lng: lng}
    //            var map = new google.maps.Map(document.getElementById('map'), {
    //                zoom: zoom_level,
    //                center: myLatLng
    //            });
    //            var contentString = '<div class="velo-popup">' + placeAddress + '</div>';
    //
    //            hidepopupcontent();
    //            var infowindow = new google.maps.InfoWindow({
    //                content: contentString
    //            });
    //
    //            var icon = {
    //                url: locator_icon, // url
    //                scaledSize: new google.maps.Size(40, 40), // scaled size
    //            };
    //
    //            var marker = new google.maps.Marker({
    //                position: myLatLng,
    //                map: map,
    //                icon: icon
    //            });
    //
    ////        setMarkers(map, locations);
    //            infowindow.open(map, marker);
    //
    //        });
    //    }

        var placeName = $('#' + DivId).find('.velo_add_name').text();
        var placeAddress1 = $('#' + DivId).find('.velo-add-address').text();
        $('#kb-store-image').hide();
        $('.kb-store-hours').hide();

        var placeAddress = $('#' + DivId).html();
    //    if (placeAddress1 != '') {
    //        var geocoder = new google.maps.Geocoder();
    //        geocoder.geocode({'address': placeAddress1}, function (results, status) {

        var lat = parseFloat($('#' + DivId).find('#velo-add-latitude').val());
        var lng = parseFloat($('#' + DivId).find('#velo-add-longitude').val());
        var myLatLng = {lat: lat, lng: lng}
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: zoom_level,
            center: myLatLng
        });
        var contentString = '<div class="velo-popup">' + placeAddress + '</div>';

        var infowindow = new google.maps.InfoWindow({
            content: contentString
        });

        var icon = {
            url: locator_icon, // url
            scaledSize: new google.maps.Size(40, 40), // scaled size
        };

        var marker = new google.maps.Marker({
            position: myLatLng,
            map: map,
            icon: icon
        });

    //        setMarkers(map, locations);
        infowindow.open(map, marker);
        $('.velo-popup .velo-store-select-link').remove();
        event.preventDefault();
        $('.velo-popup #kb-store-image').show();
        $('.velo-popup .kb-store-hours').show();
    //        });
        return false
    }

    function initializeKb() {
        var lat = parseFloat(default_latitude);
        var lng = parseFloat(default_longitude);
        var myLatLng = {lat: lat, lng: lng}
        var myOptions = {
            center: myLatLng,
            zoom: zoom_level
        };
        var map = new google.maps.Map(document.getElementById("map"), myOptions);
        locations = kb_all_stores;
        setMarkerskb(map, locations);
    }
           
    function setMarkerskb(map, locations) {
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
            for (i = 0; i < locations.length; i++)
            {

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
        //changes by vishal for adding search as i move marker functionality  
        if(search_as_move == 1){
       google.maps.event.addListener(map, 'dragend', function() {  
           if (document.getElementById("kb_move_map").checked == true){
            bounds = map.getBounds();
            if(typeof (is_enabled_date_selcetion) != "undefined" && is_enabled_date_selcetion == 1){
        var filter_pickup = $('input[name="kb_pickup_select_date"]').length;
        }else{
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
            },
                success: function(content)
                {
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
        }
    }
}