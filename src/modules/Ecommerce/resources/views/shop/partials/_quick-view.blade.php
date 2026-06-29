<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0 position-relative">
                <button type="button"
                        class="btn-close position-absolute top-0 end-0 m-3"
                        style="z-index:10"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                <div id="quickViewContent" class="p-4">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-3 mb-0 text-muted">Cargando producto...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.js-quick-view', function (e) {
    e.preventDefault();

    var productId = $(this).data('product-id');
    var $modal    = $('#quickViewModal');
    var $content  = $('#quickViewContent');

    $content.html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal($modal[0]).show();

    $.get('/tienda/producto/' + productId + '/quick')
        .done(function (html) { $content.html(html); })
        .fail(function () {
            $content.html('<div class="alert alert-danger m-3">Error al cargar el producto</div>');
        });
});
</script>
