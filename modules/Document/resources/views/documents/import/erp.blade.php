@extends('layouts.theme')

@section('content')

    @if (!auth()->user()->canDocument('import-documents') &&
         !auth()->user()->canDocument('sync-erp'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-lock me-2"></i>
            <strong>Acceso denegado.</strong> No tienes permiso para importar documentos desde Gestión.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <a href="{{ route('documents.import') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver a opciones de importación
        </a>
    @else

    @include('core::components.card', ['title' => 'Importar desde gestión'])

    <div class="widget-content">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">Importar pedido desde gestión</h5>
                    <p class="text-muted mb-4">
                        Ingresa la serie (año) y el número de pedido del cliente en Gestión. Se sincronizarán automáticamente
                        los datos del cliente (nombre, email, teléfono, DNI/CIF) y las líneas del pedido con artículos y precios.
                        El pedido se identificará con la referencia Serie/Número.
                    </p>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="serie_input" class="form-label">Serie <span class="text-danger">*</span></label>
                                <input type="text" id="serie_input" class="form-control" placeholder="Ej: 2025" value="{{ date('Y') }}">
                                <small class="text-muted">Año/Serie del pedido</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="npedidocli_input" class="form-label">Número de Pedido <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="npedidocli_input" class="form-control" placeholder="Ej: 61550">
                                    <button type="button" class="btn btn-primary" id="add-order-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Número de pedido del cliente en Gestión</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3" id="orders_list_container" style="display: none;">
                        <label class="form-label">Pedidos a importar</label>
                        <div id="orders_list" class="border rounded p-3" style="min-height: 60px; background-color: #f8f9fa;">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary mb-2 w-100" id="import-btn" disabled>
                                Importar
                            </button>
                            <a href="{{ route('documents.import') }}" class="btn btn-secondary w-100">
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
                    Ver documentos
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de importación -->
    <div id="import-confirm-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar importación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-primary"></div>
                    <h4 class="my-3">¿Deseas importar estos pedidos del ERP?</h4>
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
            const $serieInput = $('#serie_input');
            const $npedidocliInput = $('#npedidocli_input');
            const $addOrderBtn = $('#add-order-btn');
            const $ordersListContainer = $('#orders_list_container');
            const $ordersList = $('#orders_list');
            const $importBtn = $('#import-btn');
            const $resultsContainer = $('#results-container');
            const $resultsContent = $('#results-content');

            // Array de objetos {serie, npedidocli}
            let selectedOrders = [];

            // Validar que los elementos existan
            if ($serieInput.length === 0 || $npedidocliInput.length === 0 || $addOrderBtn.length === 0 || $importBtn.length === 0) {
                console.error('No se encontraron los elementos del formulario de importación del ERP');
                return;
            }

            // Manejar clic en botón Agregar
            $addOrderBtn.on('click', function() {
                addOrder();
            });

            // Manejar entrada con Enter en número de pedido
            $npedidocliInput.on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addOrder();
                }
            });

            function addOrder() {
                const serie = $serieInput.val().trim();
                const npedidocli = $npedidocliInput.val().trim();

                if (!serie) {
                    toastr.warning('Por favor ingresa la serie del pedido', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    $serieInput.focus();
                    return;
                }

                if (!npedidocli) {
                    toastr.warning('Por favor ingresa el número de pedido', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    $npedidocliInput.focus();
                    return;
                }

                // Verificar si ya existe
                const exists = selectedOrders.some(o => o.serie === serie && o.npedidocli === npedidocli);
                if (exists) {
                    toastr.warning('Este pedido ya está en la lista', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Agregar a la lista
                selectedOrders.push({ serie, npedidocli });

                $npedidocliInput.val(''); // Limpiar solo número de pedido
                $npedidocliInput.focus();
                updateOrdersList();
            }

            function updateOrdersList() {
                if (selectedOrders.length === 0) {
                    $ordersListContainer.hide();
                    $importBtn.prop('disabled', true);
                    return;
                }

                let html = '';
                selectedOrders.forEach((order, index) => {
                    html += `
                        <div class="badge bg-primary me-2 mb-2" style="font-size: 0.9rem;">
                            ${order.serie}/${order.npedidocli}
                            <button type="button" class="btn-close btn-close-white ms-2 remove-order-btn" data-index="${index}" style="font-size: 0.6rem;"></button>
                        </div>
                    `;
                });

                $ordersList.html(html);
                $ordersListContainer.show();
                $importBtn.prop('disabled', false);
            }

            // Manejar eliminar orden (delegado)
            $(document).on('click', '.remove-order-btn', function() {
                const index = $(this).data('index');
                selectedOrders.splice(index, 1);
                updateOrdersList();
            });

            // Importar órdenes
            $importBtn.on('click', function() {
                if (selectedOrders.length === 0) {
                    toastr.warning('Por favor agrega al menos un pedido para importar', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                // Mostrar modal de confirmación
                $('#import-count-text').text(`Se importarán ${selectedOrders.length} pedido(s) del ERP`);
                const modal = new bootstrap.Modal(document.getElementById('import-confirm-modal'));
                modal.show();
            });

            // Confirmar importación desde el modal
            $(document).on('click', '#confirm-import-btn', function() {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('import-confirm-modal'));
                modal.hide();

                $importBtn.prop('disabled', true).html('Importando...');
                importOrders(selectedOrders);
            });

            function importOrders(orders) {
                const totalOrders = orders.length;
                let importedCount = 0;
                let resultsHtml = `
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Detalles</th>
                                    <th class="text-end">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                const importNext = (index) => {
                    if (index >= orders.length) {
                        // Mostrar resultados
                        resultsHtml += '</tbody></table></div>';
                        showResults(resultsHtml, importedCount, totalOrders);
                        $importBtn.prop('disabled', false).html('Importar');
                        return;
                    }

                    const order = orders[index];
                    const orderLabel = `${order.serie}/${order.npedidocli}`;

                    $.ajax({
                        url: '{{ route("documents.sync.from-erp") }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            serie: order.serie,
                            npedidocli: order.npedidocli
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success') {
                                importedCount++;
                                const productsCount = data.data.products_count || 0;
                                const customerName = data.data.customer_name || 'N/A';
                                const total = data.data.total || '0.00';

                                resultsHtml += `
                                    <tr>
                                        <td>
                                            <strong>${orderLabel}</strong>
                                            <br><small class="text-muted">ID: ${data.data.erp_order_id || '-'}</small>
                                        </td>
                                        <td>
                                            <div>${customerName}</div>
                                            <small class="text-muted">${productsCount} productos • €${total}</small>
                                        </td>
                                        <td class="text-end"><span class="badge bg-success">Importado</span></td>
                                    </tr>
                                `;
                            } else {
                                const errorMessage = data.message || 'Error desconocido';

                                resultsHtml += `
                                    <tr>
                                        <td><strong>${orderLabel}</strong></td>
                                        <td class="text-muted">${errorMessage}</td>
                                        <td class="text-end"><span class="badge bg-danger">Error</span></td>
                                    </tr>
                                `;
                            }

                            importNext(index + 1);
                        },
                        error: function(xhr) {
                            let errorMessage = 'No se pudo procesar la solicitud';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {}

                            console.error('Error en la importación de pedido del ERP:', orderLabel);

                            resultsHtml += `
                                <tr>
                                    <td><strong>${orderLabel}</strong></td>
                                    <td class="text-muted">${errorMessage}</td>
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
                                    < <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-dark fw-bold">${total}</h2>
                                        <p class="mb-0 text-muted small">Total</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-success fw-bold">${imported}</h2>
                                        <p class="mb-0 text-muted small">Importados</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded bg-light p-3">
                                        <h2 class="mb-1 text-success fw-bold">${failed}</h2>
                                        <p class="mb-0 text-muted small">Fallidos</p>
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
