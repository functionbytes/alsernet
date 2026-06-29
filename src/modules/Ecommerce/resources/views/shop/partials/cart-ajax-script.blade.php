<script>
window.refreshCartCount = function () {
    $.get('/api/v1/ecommerce/cart/count', function (data) {
        $('.js-cart-count').text(data.count);
    });
};

$(document).on('submit', '.js-add-to-cart', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $btn = $form.find('[type="submit"]');

    $btn.prop('disabled', true);

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (response) {
            toastr.success(response.message || 'Producto agregado al carrito');
            window.refreshCartCount();
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (field, messages) {
                    toastr.error(messages[0]);
                });
            } else {
                toastr.error('No se pudo agregar el producto al carrito');
            }
        },
        complete: function () {
            $btn.prop('disabled', false);
        },
    });
});
</script>
