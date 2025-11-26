@extends('layouts.administratives')

@section('content')

    @include('managers.includes.card', ['title' => 'Importar ordenes'])

    <div class="widget-content">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-4">Selecciona las órdenes que deseas importar</h5>

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
            <h5 class="mb-4">Resultados de importación</h5>
            <div id="results-content"></div>
            <div class="mt-4">
                <a href="{{ route('administrative.documents') }}" class="btn btn-primary w-100">
                    Volver
                </a>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        console.log('Script de import.blade.php cargado');

        (function() {
            console.log('IIFE ejecutándose');

            // Función para inicializar cuando el DOM esté listo
            function initializeImportForm() {
                console.log('initializeImportForm llamada');
                const orderIdsInput = document.getElementById('order_ids_input');
                const addOrderBtn = document.getElementById('add-order-btn');
                const ordersListContainer = document.getElementById('orders_list_container');
                const ordersList = document.getElementById('orders_list');
                const importBtn = document.getElementById('import-btn');
                const resultsContainer = document.getElementById('results-container');
                const resultsContent = document.getElementById('results-content');
                let selectedOrderIds = [];

                // Validar que los elementos existan
                if (!orderIdsInput || !addOrderBtn || !importBtn) {
                    console.error('No se encontraron los elementos del formulario de importación');
                    console.log('orderIdsInput:', orderIdsInput);
                    console.log('addOrderBtn:', addOrderBtn);
                    console.log('importBtn:', importBtn);
                    console.log('Todos los elementos encontrados:');
                    console.log('- order_ids_input:', document.getElementById('order_ids_input'));
                    console.log('- add-order-btn:', document.getElementById('add-order-btn'));
                    console.log('- import-btn:', document.getElementById('import-btn'));
                    return;
                }
                console.log('Script de importación inicializado correctamente');
                console.log('addOrderBtn encontrado:', addOrderBtn);

                // Manejar clic en botón Agregar
                addOrderBtn.addEventListener('click', function() {
                    addOrderIds();
                });

                // Manejar entrada de IDs con Enter
                orderIdsInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addOrderIds();
                    }
                });

                function addOrderIds() {
                    console.log('addOrderIds ejecutada');
                    const inputValue = orderIdsInput.value.trim();
                    console.log('inputValue:', inputValue);

                    if (!inputValue) {
                        console.log('Input vacío');
                        return;
                    }

                    // Dividir por comas y procesar cada ID
                    const ids = inputValue.split(',').map(id => {
                        return id.trim();
                    }).filter(id => id && /^\d+$/.test(id)); // Solo números

                    console.log('IDs procesados:', ids);

                    if (ids.length === 0) {
                        alert('Por favor ingresa IDs válidos separados por comas (solo números)');
                        return;
                    }

                    // Agregar IDs que no estén duplicados
                    ids.forEach(id => {
                        if (!selectedOrderIds.includes(id)) {
                            selectedOrderIds.push(id);
                        }
                    });

                    console.log('selectedOrderIds actualizado:', selectedOrderIds);
                    orderIdsInput.value = ''; // Limpiar input
                    updateOrdersList();
                }

                function updateOrdersList() {
                    if (selectedOrderIds.length === 0) {
                        ordersListContainer.style.display = 'none';
                        importBtn.disabled = true;
                        return;
                    }

                    ordersListContainer.style.display = 'block';
                    ordersList.innerHTML = selectedOrderIds.map(id => `
                        <div class="badge bg-primary me-2 " >
                           ${id}
                            <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.7rem;" onclick="removeOrderId('${id}')"></button>
                        </div>
                    `).join('');

                    importBtn.disabled = false;
                }

                window.removeOrderId = function(id) {
                    selectedOrderIds = selectedOrderIds.filter(oid => oid !== id);
                    updateOrdersList();
                };

                // Importar órdenes
                importBtn.addEventListener('click', function() {
                    if (selectedOrderIds.length === 0) {
                        alert('Por favor ingresa al menos una orden para importar');
                        return;
                    }

                    if (!confirm(`¿Deseas importar ${selectedOrderIds.length} orden(es)?`)) {
                        return;
                    }

                    importBtn.disabled = true;
                    importBtn.innerHTML = '<i class="fa-duotone fa-spinner fa-spin"></i> Importando...';

                    importOrders(selectedOrderIds);
                });

                function importOrders(orderIds) {
                    const totalOrders = orderIds.length;
                    let importedCount = 0;
                    let resultsHtml = '<div class="row">';

                    const importNext = (index) => {
                        if (index >= orderIds.length) {
                            // Mostrar resultados
                            resultsHtml += '</div>';
                            showResults(resultsHtml, importedCount, totalOrders);
                            importBtn.disabled = false;
                            importBtn.innerHTML = 'Importar';
                            return;
                        }

                        const orderId = orderIds[index];

                        fetch(`/administrative/orders/sync/by-order?order_id=${orderId}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Content-Type': 'application/json',
                            },
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    importedCount++;
                                    const productsCount = data.data.products_count || 0;

                                    resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-success mb-0">
                                    <strong>✓ Orden ${orderId}</strong><br>
                                    Sincronizados: ${data.data.synced}<br>
                                    Productos: ${productsCount}<br>
                                    Cliente: ${data.data.customer_name || 'N/A'}
                                </div>
                            </div>
                        `;
                                } else {
                                    const errorMessage = data.message || 'Error desconocido';
                                    const additionalInfo = data.data && data.data.existing_documents
                                        ? `<br>Documentos existentes: ${data.data.existing_documents}`
                                        : '';

                                    resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-danger mb-0">
                                    <strong>✗ Orden ${orderId}</strong><br>
                                    Error: ${errorMessage}${additionalInfo}
                                </div>
                            </div>
                        `;
                                }

                                importNext(index + 1);
                            })
                            .catch(error => {
                                console.error('Error:', error);

                                resultsHtml += `
                        <div class="col-md-6 mb-3">
                            <div class="alert alert-danger mb-0">
                                <strong>✗ Orden ${orderId}</strong><br>
                                Error: No se pudo procesar la solicitud
                            </div>
                        </div>
                    `;

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
            console.log('Verificando readyState:', document.readyState);
            if (document.readyState === 'loading') {
                console.log('Esperando DOMContentLoaded');
                document.addEventListener('DOMContentLoaded', initializeImportForm);
            } else {
                console.log('DOM ya está listo, ejecutando inmediatamente');
                initializeImportForm();
            }
        })();

        console.log('Script finalizado');
    </script>
@endpush
