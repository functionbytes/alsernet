/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to a newer
 * versions in the future. If you wish to customize this module for your needs
 * please refer to CustomizationPolicy.txt file inside our module for more information.
 *
 * @author Webkul IN
 * @copyright Since 2010 Webkul
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
$(document).ready(function () {

    /**=====================
     Quantity js
     ==========================**/
    $('.dashboard-right-sidebar .dashboard-refund .refund-table-section .order-tab-table tbody tr td .refund-return-qunatity .qty-right-plus').click(function () {
        if ($(this).prev().val() < 9) {
            $(this).prev().val(+$(this).prev().val() + 1);
        }
    });
    $('.dashboard-right-sidebar .dashboard-refund .refund-table-section .order-tab-table tbody tr td .refund-return-qunatity .qty-left-minus').click(function () {
        if ($(this).next().val() > 1) {
            if ($(this).next().val() > 1) $(this).next().val(+$(this).next().val() - 1);
        }
    });

    $('.dashboard-refund').find('select.select2').select2({
        width: '100%',
        placeholder: 'Selecciona una opción'
    });

    $('#submitMessage').on('click', function() {
        if ($('#txt_msg').val() != '') {
            $('#submitMessage').attr('style', 'pointer-events: none;opacity:0.3;');
        }
    });
});

var rmaRequest = {
    uploadindex: 1,
    init: function() {
        rmaRequest.createEvents();
        rmaRequest.dataTable();
    },
    createEvents: function() {
        console.log('.refund-request-product');

        $('body').on('click', '.refund-request-product', this.getReturnType);
        $('body').on('change', '#form-return-type', this.getReturnReason);

        this.addMultipleAttachment();

        $('.process-bar').on('mouseenter', function() {
            $(this).find('.state-history-tootltip').show();
        });

        $('.process-bar').on('mouseleave', function() {
            $(this).find('.state-history-tootltip').hide();
        });

        $('body').on('click', '#submit_refund_request', this.validateRequest);

        // Search customer order
        if ($('#rma-order-search').length > 0) {
            $('#rma-order-search').typeWatch({
                captureLength: 0,
                highlight: true,
                wait: 750,
                callback: function() { rmaRequest.searchCustomerOrder(); }
            });
        }

        $('#rma-order-search').on('keyup', function() {
            var str = $.trim($('#rma-order-search').val());
            if (str.length < 1) {
                $('.refund-order-table').parent().hide();
                $('#add-order-list').html('');
            }
        });

        // Search customer order
        if ($('#rma-orderProduct-search').length > 0) {
            $('#rma-orderProduct-search').typeWatch({
                captureLength: 0,
                highlight: true,
                wait: 750,
                callback: function() { rmaRequest.searchCustomerOrderByProduct(); }
            });
        }

        $('#rma-orderProduct-search').on('keyup', function() {
            var str = $.trim($('#rma-orderProduct-search').val());
            if (str.length < 1) {
                $('.refund-order-table').parent().hide();
                $('#add-order-list').html('');
            }
        });


        $('body').on('click', '.add-rma-order-request', this.getRmaRequestForm);
        $('body').on('click', '.refund-request-close', function() {
            if ($("#refund-order-request-detail").length) {
                $('#refund-order-request-detail').parent().hide();
                $('#refund-order-request-detail').html('');
            }
        });
    },
    dataTable: function() {
        if ($("#refund-request-list").length) {
            $('#refund-request-list').DataTable({
                "order": [],
                "columnDefs": [{
                    "targets": 'no-sort',
                    "orderable": false,
                }],
                "language": {
                    "lengthMenu": display_name + " _MENU_ " + records_name,
                    "zeroRecords": no_product,
                    "info": show_page + " _PAGE_ " + show_of + " _PAGES_ ",
                    "infoEmpty": no_record,
                    "infoFiltered": "(" + filter_from + " _MAX_ " + t_record + ")",
                    "sSearch": search_item,
                    "oPaginate": {
                        "sPrevious": p_page,
                        "sNext": n_page
                    }
                }
            });
        }
    },
    getReturnType: function(e) {

        var currentTarget = $(e.currentTarget);
        var idProduct = currentTarget.data('id-product');
        var idOrderDetail = currentTarget.data('id-order-detail');
        var id_order = $('#idOrder').val();
        $('.product-qunatity').removeClass('d-none');
        $('.refund-return-qunatity').addClass('d-none');
        $('#refund_request_' + idOrderDetail + ' .product-qunatity').addClass('d-none');
        $('#refund_request_' + idOrderDetail + ' .refund-return-qunatity').removeClass('d-none');

        $.ajax({
            url: refund_request_process_link,
            dataType: 'json',
            async: true,
            data: {
                ajax: '1',
                token: static_token,
                action: "getReturnType",
                id_product: idProduct,
                id_order_detail: idOrderDetail,
                id_order: id_order
            },
            success: function(result) {
                if (typeof result['status'] !== 'undefined' && result['status'] == 'success') {
                    $('#form-return-type').html('');
                    $(result['returnTypes']).each(function(index, value) {
                        var option = $('<option/>');
                        option.val(value.id_return_type);
                        option.html(value.name);
                        option.appendTo($('#form-return-type'));
                    });

                    $('#form-return-type').trigger('change');
                } else {
                    return $.growl.error({
                        title: "",
                        size: "large",
                        message: wk_something_wrong,
                    });
                }
            }
        });
    },
    getReturnReason: function(e) {
        var idReturnType = $(this).val();
        var idOrderDetail = $('input[name=id_order_detail]:checked').val();
        $.ajax({
            url: refund_request_process_link,
            dataType: 'json',
            async: true,
            data: {
                ajax: '1',
                token: static_token,
                action: "getReturnReason",
                id_order_detail: idOrderDetail,
                id_return_type: idReturnType
            },
            success: function(result) {
                if (typeof result['status'] !== 'undefined' && result['status'] == 'success') {
                    $('#form-return-reason').html('');
                    $(result['returnReasons']).each(function(index, value) {
                        var option = $('<option/>');
                        option.val(value.id_return_reason);
                        option.html(value.name);
                        option.appendTo($('#form-return-reason'));
                    });
                } else {
                    return $.growl.error({
                        title: "",
                        size: "large",
                        message: wk_something_wrong,
                    });
                }
            }
        });
    },
    changeQty: function(e) {
        //var currentTarget = $(e.currentTarget);
        var action = $(this).data('action');
        var qtyDiv = $(this).parent().prev();
        if (action == 'up') {
            var returnVal = parseInt(qtyDiv.val()) + 1;
            var maxQty = $(this).data('max-qty');
            if (returnVal <= parseInt(maxQty)) {
                qtyDiv.val(returnVal);
            }
        } else if (action == 'down') {
            var returnVal = parseInt(qtyDiv.val()) - 1;
            if (returnVal > 0) {
                qtyDiv.val(returnVal);
            }
        }
    },
    searchCustomerOrder: function(e) {
        $.ajax({
            dataType: 'json',
            async: true,
            data: {
                ajax: '1',
                token: static_token,
                action: "searchCustomerOrder",
                order_search: $('#rma-order-search').val().trim()
            },
            success: function(result) {
                $('#refund-order-request-detail').html('');
                $('.order-request-detail').removeClass('d-none');
                $('#add-order-list').html(result.order_tpl);
            }
        });
    },
    searchCustomerOrderByProduct: function(e) {
        if ($('#rma-orderProduct-search').val().trim().length >= 3) {
            $.ajax({
                dataType: 'json',
                async: true,
                data: {
                    ajax: '1',
                    token: static_token,
                    action: "searchCustomerOrderByProduct",
                    product_search: $('#rma-orderProduct-search').val().trim()
                },
                success: function(result) {
                    $('#refund-order-request-detail').html('');
                    $('.order-request-detail').removeClass('d-none');
                    $('#add-order-list').html(result.order_tpl);
                }
            });
        }
    },
    getRmaRequestForm: function(e) {
        e.preventDefault();
        var currentTarget = $(e.currentTarget);
        var idOrder = currentTarget.data('id-order');
        id_order = idOrder;
        $.ajax({
            dataType: 'json',
            async: true,
            data: {
                ajax: '1',
                token: static_token,
                action: "getRmaRequestForm",
                id_order: idOrder
            },
            success: function(result) {
                $('#refund-order-request-detail').parent().show();
                $('#refund-order-request-detail').html(result.order_detail_tpl);
            }
        });
    },
    validateRequest: function(e) {
        e.preventDefault();
        var id_order = $('#idOrder').val();
        var wkerror = false;
        const form = document.getElementById("refund-request-form");
        const formDataWhole = new FormData(form);
        formDataWhole.append("ajax", 1);
        formDataWhole.append("action", "validateReturnForm");
        formDataWhole.append("token", static_token);

        $.ajax({
            type: "POST",
            url: refund_request_process_link,
            data: formDataWhole,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function () {
                $('#submit_refund_request').addClass('disabled');
                $('.wk_save_loader').show();
            },
            success: function(result) {
                if (result.status == 'fail') {
                    $('#wk_refund_form_error').text(result.message).show('slow');
                    $('#submit_refund_request').removeClass('disabled');
                    $('.wk_save_loader').hide();
                    wkerror = true;
                } else {
                    $('#submit_refund_request_val').val('1');
                    $('#refund-request-form').submit();

                    window.location.href = "/index.php?controller=refund";

                }
            }
        });

        if (wkerror) {
            return false;
        }
    },
    addMultipleAttachment: function() {
        $(document).on('click', '.upload-file', function() {
            var div = $('<div/>');
            div.addClass('labelWidget upload-entry');

            var input = $('<input type="file" name="attachment[]" class="fileUpload d-none">');
            var filename = $('<span class="file-name">No file selected</span>');
            var removeBtn = $('<i class="material-icons remove-file" title="Eliminar archivo">&#xE872;</i>');

            // Evento al cambiar archivo
            input.on('change', function () {
                var file = this.files[0];
                filename.text(file ? file.name : 'No file selected');
            });

            // Evento para eliminar entrada
            removeBtn.on('click', function () {
                div.remove();
            });

            div.append(removeBtn, input, filename);
            $('.refund-attachment').append(div);

            // Abrir automáticamente el selector
            input.trigger('click');
        });
        $(document).on('change', 'input[name="attachment[]"]', function() {
            var maxsize = 300000;
            var file = this.files[0];
            var size = file.size / 1000;
            var fileName = file.name;
            if (this.type == 'file' && /^image/.test(file.type)) {
                $(this).closest('.labelWidget').find('.remove-file').removeClass('hidden');
                var file_extension = fileName.split('.').pop();
                var imageType = ['gif', 'png', 'jpg', 'jpeg', 'GIF', 'PNG', 'JPG', 'JPEG'];
                if ((jQuery.inArray(file_extension, imageType) == -1)) {
                    $(this).parent().remove();
                    return $.growl.error({
                        title: "",
                        size: "large",
                        message: wk_invalid_file + ' ' + fileName,
                    });
                }

                if (size < maxsize) {
                    var getImagePath = URL.createObjectURL(this.files[0]);
                    var label = $('<label/>');
                    label.addClass('attach-file pointer');
                    label.css('background-image', 'url(' + getImagePath + ')');
                    label.css('background-size', 'cover');
                    $(this).parent().append(label);
                    return true;
                } else {
                    $(this).parent().remove();
                    return $.growl.error({
                        title: "",
                        size: "large",
                        message: allowed_file_size_error,
                    });
                }
            } else {
                $(this).parent().remove();
                return $.growl.error({
                    title: "",
                    size: "large",
                    message: wk_invalid_file + ' ' + fileName,
                });
            }
        });
    },
    changePickupPoints: function() {
        $(document).on('change', 'input[name="pickup_point"]', function() {
            var pickupselection = $(this).val();
            console.log(pickupselection)
            if (pickupselection == 1) {
                $('#wk_collect_from_office').show();
                $('#wk_pickup_courrier').hide();
            } else if (pickupselection == 2) {
                $('#wk_collect_from_office').hide();
                $('#wk_pickup_courrier').show();
            }
        });
    },
    toggleSubmits: function(state = undefined) {

        $('#formRequest').on('change', '.form-check-input[required]', function () {
            const $form = $('#formRequest');
            const $submit = $form.find('#submit_refund_request');

            const allChecked = $form.find('.form-check-input[required]').toArray().every(function (input) {
                return $(input).is(':checked');
            });

            $submit.prop('disabled', !allChecked).toggleClass('disabled', !allChecked);
        });
    },
    showError: function() {
        $.growl.notice({ title: '', message: 'Position Update sucessfully' });
    },
    uploadAttachment: function() {
        $('.wk_refund_attachment_btn').click(function(e) {
            $('#fileUpload').trigger('click');
        })
        $("#fileUpload").on("change", function() {
            var file_name = $(this).val().split('/').pop().split('\\').pop();
            if (file_name !== '') {
                $(".wk_refund_attachement_name").text(file_name);
                if ((Math.round(this.files[0].size / 1024 / 1024) * 100) / 100) {
                    setTimeout(function() {
                        location.reload();
                    }, 300);
                    return $.growl.error({
                        title: "",
                        size: "large",
                        message: allowed_file_size_error,
                    });
                }
            }
        });
    }
};


$(function() {

    $('#pickup_toggle').change(function () {
        const isChecked = $(this).is(':checked');
        $('#pickup_point_hidden').val(isChecked ? 2 : 1);
        $('#pickup_toggle_label').text(
            isChecked ? 'Courier collection' : 'Drop off at our offices'
        );
    });

    rmaRequest.init();
    rmaRequest.uploadAttachment();
    rmaRequest.changePickupPoints();
    rmaRequest.toggleSubmits();

});
function confirmRemove(obj)
{
    if (confirm(wkconfirm)) {
        $(obj).parent().remove();
        return true;
    }
}