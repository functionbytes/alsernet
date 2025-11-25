@extends('layouts.administratives')

@section('content')

    @include('managers.includes.card', ['title' => 'Importar ordenes'])

    <div class="widget-content">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-4">Agrega las órdenes que deseas importar</h5>

                    <div class="form-group mb-3">
                        <label for="order_id_input" class="form-label">ID de la Orden</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="order_id_input" placeholder="Ej: 123456" min="1">
                            <button type="button" class="btn btn-primary" id="add-order-btn">
                                <i class="fa-duotone fa-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted">Ingresa el ID de la orden y haz clic en "Agregar"</small>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-hover" id="orders-table">
                            <thead class="header-item">
                                <tr>
                                    <th>Orden ID</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody">
                                <tr id="empty-row">
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No hay órdenes agregadas. Agrega una orden para comenzar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary  mb-2 w-100" id="import-btn" disabled>
                                Importar
                            </button>
                            <a href="{{ route('administrative.documents') }}" class="btn btn-secondary  w-100">
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
            <h5 class="mb-4">Resultados de Importación</h5>
            <div id="results-content"></div>
            <div class="mt-4">
                <a href="{{ route('administrative.documents') }}" class="btn btn-primary">
                    <i class="fa-duotone fa-arrow-left"></i> Volver a Documentos
                </a>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
(function() {
    // Array para almacenar órdenes
    let ordersToImport = [];

    // Función para inicializar cuando el DOM esté listo
    function initializeImportForm() {
        const orderInput = document.getElementById('order_id_input');
        const addOrderBtn = document.getElementById('add-order-btn');
        const importBtn = document.getElementById('import-btn');
        const ordersTableBody = document.getElementById('orders-tbody');
        const resultsContainer = document.getElementById('results-container');
        const resultsContent = document.getElementById('results-content');

        // Validar que los elementos existan
        if (!orderInput || !addOrderBtn || !importBtn || !ordersTableBody) {
            console.error('No se encontraron los elementos del formulario de importación');
            return;
        }

        // Agregar orden al hacer clic
        addOrderBtn.addEventListener('click', addOrder);

        // Agregar orden al presionar Enter
        orderInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addOrder();
            }
        });

        function addOrder() {
            const orderId = orderInput.value.trim();

            // Validar
            if (!orderId) {
                alert('Por favor ingresa un ID de orden válido');
                return;
            }

            // Verificar si la orden ya existe en la lista
            if (ordersToImport.find(o => o.id === orderId)) {
                alert('Esta orden ya fue agregada');
                return;
            }

            // Agregar a la lista
            const orderObj = {
                id: orderId,
                status: 'pendiente'
            };
            ordersToImport.push(orderObj);

            // Renderizar tabla
            renderTable();

            // Limpiar input
            orderInput.value = '';
            orderInput.focus();
        }

        function renderTable() {
            ordersTableBody.innerHTML = '';

            if (ordersToImport.length === 0) {
                ordersTableBody.innerHTML = `
                    <tr id="empty-row">
                        <td colspan="3" class="text-center text-muted py-4">
                            No hay órdenes agregadas. Agrega una orden para comenzar.
                        </td>
                    </tr>
                `;
                importBtn.disabled = true;
                return;
            }

            importBtn.disabled = false;

            ordersToImport.forEach((order) => {
                const row = document.createElement('tr');
                row.id = `order-row-${order.id}`;
                row.innerHTML = `
                    <td>
                        <strong>${order.id}</strong>
                    </td>
                    <td>
                        <span class="badge ${order.status === 'completado' ? 'bg-success' : order.status === 'error' ? 'bg-danger' : 'bg-warning'} rounded-3 py-2">
                            ${order.status === 'completado' ? '✓ Importado' : order.status === 'error' ? '✗ Error' : '⏳ Pendiente'}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-btn" data-order-id="${order.id}">
                            <i class="fa-duotone fa-trash"></i> Remover
                        </button>
                    </td>
                `;
                ordersTableBody.appendChild(row);
            });

            // Agregar event listeners a botones de remover
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    removeOrder(orderId);
                });
            });
        }

        function removeOrder(orderId) {
            ordersToImport = ordersToImport.filter(o => o.id !== orderId);
            renderTable();
        }

        // Importar órdenes
        importBtn.addEventListener('click', function() {
            if (ordersToImport.length === 0) {
                alert('No hay órdenes para importar');
                return;
            }

            if (!confirm(`¿Deseas importar ${ordersToImport.length} orden(es)?`)) {
                return;
            }

            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="fa-duotone fa-spinner fa-spin"></i> Importando...';

            // Importar cada orden
            importOrders();
        });

        function importOrders() {
            const totalOrders = ordersToImport.length;
            let importedCount = 0;
            let resultsHtml = '<div class="row">';

            const importNext = (index) => {
                if (index >= ordersToImport.length) {
                    // Mostrar resultados
                    resultsHtml += '</div>';
                    showResults(resultsHtml, importedCount, totalOrders);
                    importBtn.disabled = false;
                    importBtn.innerHTML = '<i class="fa-duotone fa-file-import"></i> Importar Órdenes';
                    return;
                }

                const order = ordersToImport[index];

                fetch(`/administrative/orders/sync/by-order?order_id=${order.id}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    const row = document.getElementById(`order-row-${order.id}`);

                    if (data.status === 'success') {
                        order.status = 'completado';
                        importedCount++;

                        resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-success mb-0">
                                    <strong>✓ Orden ${order.id}</strong><br>
                                    Sincronizados: ${data.data.synced}<br>
                                    Cliente: ${data.data.customer_name || 'N/A'}
                                </div>
                            </div>
                        `;

                        if (row) {
                            row.querySelector('span').className = 'badge bg-success rounded-3 py-2';
                            row.querySelector('span').innerHTML = '✓ Importado';
                        }
                    } else {
                        order.status = 'error';

                        resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-danger mb-0">
                                    <strong>✗ Orden ${order.id}</strong><br>
                                    Error: ${data.message || 'Error desconocido'}
                                </div>
                            </div>
                        `;

                        if (row) {
                            row.querySelector('span').className = 'badge bg-danger rounded-3 py-2';
                            row.querySelector('span').innerHTML = '✗ Error';
                        }
                    }

                    // Renderizar tabla actualizada
                    renderTable();

                    // Importar la siguiente orden
                    importNext(index + 1);
                })
                .catch(error => {
                    console.error('Error:', error);
                    order.status = 'error';

                    resultsHtml += `
                        <div class="col-md-6 mb-3">
                            <div class="alert alert-danger mb-0">
                                <strong>✗ Orden ${order.id}</strong><br>
                                Error: No se pudo procesar la solicitud
                            </div>
                        </div>
                    `;

                    const row = document.getElementById(`order-row-${order.id}`);
                    if (row) {
                        row.querySelector('span').className = 'badge bg-danger rounded-3 py-2';
                        row.querySelector('span').innerHTML = '✗ Error';
                    }

                    renderTable();
                    importNext(index + 1);
                });
            };

            importNext(0);
        }

        function showResults(html, imported, total) {
            resultsContent.innerHTML = `
                <div class="alert alert-info mb-4">
                    <strong>Resumen de Importación</strong><br>
                    Total: ${total} | Importadas: ${imported} | Fallidas: ${total - imported}
                </div>
                ${html}
            `;
            resultsContainer.style.display = 'block';
            resultsContainer.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Ejecutar cuando el DOM esté listo (múltiples estrategias de compatibilidad)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeImportForm);
    } else {
        initializeImportForm();
    }
})();
</script>
@endsection
