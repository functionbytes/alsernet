@extends('layouts.theme')

@section('content')

    @if (!auth()->user()->canDocument('import-documents') &&
         !auth()->user()->canDocument('sync-api'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-lock me-2"></i>
            <strong>Acceso denegado.</strong> No tienes permiso para importar documentos desde PrestaShop.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <a href="{{ route('documents.import') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver a opciones de importación
        </a>
    @else

    @include('core::components.card', ['title' => 'Importar desde PrestaShop'])

    <div class="widget-content">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">Selecciona las órdenes que deseas importar</h5>
                    <p class="text-muted mb-4">
                        Ingresa los IDs de las órdenes de PrestaShop que deseas importar. Se sincronizarán automáticamente
                        los datos del cliente (nombre, email, teléfono, DNI) y los productos del carrito.
                        El sistema detectará el tipo de documento según los productos de la orden.
                    </p>

                    <div class="form-group mb-3">
                        <label for="order_ids_input" class="form-label">IDs de órdenes a Importar</label>
                        <div class="input-group">
                            <input type="text" id="order_ids_input" class="form-control" placeholder="Ingresa los IDs separados por comas. Ej: 123,456,789">
                            <button type="button" class="btn btn-primary" id="add-order-btn"><i class="fa-duotone fa-plus"></i></button>
                        </div>
                        <small class="text-muted">Escribe los IDs de las órdenes que deseas importar (separados por comas) y haz clic en "Agregar" o presiona Enter</small>
                    </div>

                    <div class="form-group mb-3" id="orders_list_container" style="display: none;">
                        <label class="form-label">Órdenes agregadas</label>
                        <div id="orders_list" class="border rounded p-3" style="min-height: 60px; background-color: #f8f9fa;">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary  mb-2 w-100" id="import-btn" disabled>
                                Importar
                            </button>
                            <a href="{{ route('documents.import') }}" class="btn btn-secondary  w-100">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado de importación -->
    <div id="results-container" style="display: none; margin-top: 30px;">
        <div class="card card-body">
            <h5 class="mb-4">Resultados de importación</h5>
            <div id="results-content"></div>
            <div class="mt-4">
                <a href="{{ route('documents.index') }}" class="btn btn-primary w-100">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de importación -->
    <div id="import-confirm-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Importación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-primary"><i data-feather="download-cloud"></i></div>
                    <h4 class="my-3">¿Deseas importar estas órdenes?</h4>
                    <p id="import-count-text" class="text-muted"></p>
                    <div class="row justify-content-center mt-4">
                        <div class="col-sm-12 col-md-5">
                            <button type="button" id="confirm-import-btn" class="btn btn-primary w-100">Confirmar</button>
                        </div>
                        <div class="col-sm-12 col-md-5">
                            <button type="button" class="btn btn-light-danger w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const $orderIdsInput = $('#order_ids_input');
            const $addOrderBtn = $('#add-order-btn');
            const $ordersListContainer = $('#orders_list_container');
            const $ordersList = $('#orders_list');
            const $importBtn = $('#import-btn');
            const $resultsContainer = $('#results-container');
            const $resultsContent = $('#results-content');
            let selectedOrderIds = [];

            // Validar que los elementos existan
            if ($orderIdsInput.length === 0 || $addOrderBtn.length === 0 || $importBtn.length === 0) {
                console.error('No se encontraron los elementos del formulario de importación');
                return;
            }

            // Manejar clic en botón Agregar
            $addOrderBtn.on('click', function() {
                addOrderIds();
            });

            // Manejar entrada de IDs con Enter
            $orderIdsInput.on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addOrderIds();
                }
            });

            function addOrderIds() {
                const inputValue = $orderIdsInput.val().trim();

                if (!inputValue) {
                    return;
                }

                // Dividir por comas y procesar cada ID
                const ids = inputValue.split(',').map(id => id.trim()).filter(id => id && /^\d+$/.test(id));

                if (ids.length === 0) {
                    toastr.warning('Por favor ingresa IDs válidos separados por comas (solo números)', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Agregar IDs que no estén duplicados
                ids.forEach(id => {
                    if (!selectedOrderIds.includes(id)) {
                        selectedOrderIds.push(id);
                    }
                });

                $orderIdsInput.val(''); // Limpiar input
                updateOrdersList();
            }

            function updateOrdersList() {
                if (selectedOrderIds.length === 0) {
                    $ordersListContainer.hide();
                    $importBtn.prop('disabled', true);
                    return;
                }

                let html = '';
                selectedOrderIds.forEach(id => {
                    html += `
                        <div class="badge bg-primary me-2">
                            ${id}
                            <button type="button" class="btn-close btn-close-white ms-2 remove-order-btn" data-order-id="${id}" style="font-size: 0.7rem;"></button>
                        </div>
                    `;
                });

                $ordersList.html(html);
                $ordersListContainer.show();
                $importBtn.prop('disabled', false);
            }

            // Manejar eliminar orden (delegado)
            $(document).on('click', '.remove-order-btn', function() {
                const orderId = $(this).data('order-id');
                selectedOrderIds = selectedOrderIds.filter(oid => oid !== orderId);
                updateOrdersList();
            });

            // Importar órdenes
            $importBtn.on('click', function() {
                if (selectedOrderIds.length === 0) {
                    toastr.warning('Por favor ingresa al menos una orden para importar', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Mostrar modal de confirmación
                $('#import-count-text').text(`Se importarán ${selectedOrderIds.length} orden(es)`);
                const modal = new bootstrap.Modal(document.getElementById('import-confirm-modal'));
                modal.show();
            });

            // Confirmar importación desde el modal
            $(document).on('click', '#confirm-import-btn', function() {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('import-confirm-modal'));
                modal.hide();

                $importBtn.prop('disabled', true).html('<i class="fa-duotone fa-spinner fa-spin"></i> Importando...');
                importOrders(selectedOrderIds);
            });

            function importOrders(orderIds) {
                const totalOrders = orderIds.length;
                let importedCount = 0;
                let resultsHtml = `
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Detalles</th>
                                    <th class="text-end">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                const importNext = (index) => {
                    if (index >= orderIds.length) {
                        // Mostrar resultados
                        resultsHtml += '</tbody></table></div>';
                        showResults(resultsHtml, importedCount, totalOrders);
                        $importBtn.prop('disabled', false).html('Importar');
                        return;
                    }

                    const orderId = orderIds[index];

                    $.ajax({
                        url: `/api/documents/sync/by-order`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            order_id: orderId
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success') {
                                importedCount++;
                                const productsCount = data.data.products_count || 0;

                                resultsHtml += `
                                    <tr>
                                        <td><strong>${orderId}</strong></td>
                                        <td>${data.data.synced} sincronizados • ${productsCount} productos • ${data.data.customer_name || 'N/A'}</td>
                                        <td class="text-end"><span class="badge bg-success">Importada</span></td>
                                    </tr>
                                `;
                            } else {
                                const errorMessage = data.message || 'Error desconocido';
                                const additionalInfo = data.data && data.data.existing_documents
                                    ? ` • ${data.data.existing_documents} documentos existentes`
                                    : '';

                                resultsHtml += `
                                    <tr>
                                        <td><strong>${orderId}</strong></td>
                                        <td class="text-muted">${errorMessage}${additionalInfo}</td>
                                        <td class="text-end"><span class="badge bg-danger">Error</span></td>
                                    </tr>
                                `;
                            }

                            importNext(index + 1);
                        },
                        error: function() {
                            console.error('Error en la importación de orden:', orderId);

                            resultsHtml += `
                                <tr>
                                    <td><strong>${orderId}</strong></td>
                                    <td class="text-muted">No se pudo procesar la solicitud</td>
                                    <td class="text-end"><span class="badge bg-danger">Error</span></td>
                                </tr>
                            `;

                            importNext(index + 1);
                        }
                    });
                };

                importNext(0);
            }

            function showResults(html, imported, total) {
                const failed = total - imported;
                const resultsHtml = `
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row text-center g-3">
                                <div class="col-md-4">
                                    <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-dark fw-bold">${total}</h2>
                                        <p class="mb-0 text-muted small">Total</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-success fw-bold">${imported}</h2>
                                        <p class="mb-0 text-muted small">Importadas</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-success fw-bold">${failed}</h2>
                                        <p class="mb-0 text-muted small">Fallidas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h5 class="mb-3">Detalle de Importación</h5>
                    ${html}
                `;

                $resultsContent.html(resultsHtml);
                $resultsContainer.show();
                $resultsContainer[0].scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
@endpush
