@extends('layouts.app')

@section('title', 'Transferencia de Productos')

@section('content')
<div class="container-fluid px-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Transferencia de Productos</h1>
                    <p class="text-muted mt-2">Traslada productos entre secciones del almacén</p>
                </div>
                <div>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario de búsqueda -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-search"></i> Buscar Producto</h5>
                </div>
                <div class="card-body">
                    <form id="searchProductForm">
                        <div class="mb-3">
                            <label for="productSearch" class="form-label">Código de Barras / Referencia</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                id="productSearch"
                                placeholder="Escanea o ingresa código"
                                autofocus
                            >
                            <small class="form-text text-muted mt-2">
                                Puedes usar código de barras, referencia o nombre del producto
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </form>

                    <!-- Resultado de búsqueda -->
                    <div id="searchResult" class="mt-4" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Producto Seleccionado</h6>
                                <div id="productInfo"></div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary w-100 mt-3"
                                    onclick="document.getElementById('productSearch').focus(); document.getElementById('searchResult').style.display='none';"
                                >
                                    Buscar otro producto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de ubicaciones con stock -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Stock por Sección</h5>
                </div>
                <div class="card-body" id="stockContainer" style="display: none;">
                    <div id="stockContent"></div>
                </div>
                <div class="card-body text-center text-muted" id="noProductMessage">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>Selecciona un producto para ver su stock en las diferentes secciones</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de transferencia -->
    @include('warehouses.views.warehouse.transfers.modals')
</div>

<style>
    .stock-item {
        padding: 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .stock-item:hover {
        background-color: #f8f9fa;
        border-color: #007bff;
    }

    .stock-badge {
        font-size: 1.1rem;
        font-weight: bold;
    }

    .form-control-lg {
        font-size: 1.1rem;
        height: 45px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchProductForm');
    const productSearch = document.getElementById('productSearch');
    let currentProduct = null;

    // Búsqueda de producto
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        searchProduct();
    });

    // Búsqueda mientras escribe (debounce)
    let searchTimeout;
    productSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        if (this.value.length >= 3) {
            searchTimeout = setTimeout(searchProduct, 500);
        }
    });

    function searchProduct() {
        const search = productSearch.value.trim();
        if (!search) return;

        fetch('{{ route("inventories.transfer.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ search })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentProduct = data.product;
                displayProductInfo(data.product);
                displayStock(data.locations);
            } else {
                showAlert('warning', data.message);
                document.getElementById('searchResult').style.display = 'none';
                document.getElementById('stockContainer').style.display = 'none';
                document.getElementById('noProductMessage').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error al buscar el producto');
        });
    }

    function displayProductInfo(product) {
        const html = `
            <dl class="row mb-0">
                <dt class="col-sm-4">Nombre:</dt>
                <dd class="col-sm-8"><strong>${product.title}</strong></dd>
                <dt class="col-sm-4">Referencia:</dt>
                <dd class="col-sm-8">${product.reference}</dd>
                <dt class="col-sm-4">Código:</dt>
                <dd class="col-sm-8"><code>${product.barcode}</code></dd>
            </dl>
        `;
        document.getElementById('productInfo').innerHTML = html;
        document.getElementById('searchResult').style.display = 'block';
    }

    function displayStock(locations) {
        const stockContent = document.getElementById('stockContent');
        let html = '';

        locations.forEach(location => {
            html += `
                <div class="mb-4">
                    <h6 class="text-secondary mb-2">
                        <strong>${location.warehouse_name}</strong> → ${location.location_code}
                    </h6>
                    <div class="stock-list">
            `;

            location.sections.forEach(section => {
                html += `
                    <div class="stock-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${section.section_code}</strong>
                            <span class="badge bg-info">Nivel ${section.section_level}</span>
                        </div>
                        <div class="text-end">
                            <span class="stock-badge text-success">${section.quantity} uds</span>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary ms-2"
                                data-bs-toggle="modal"
                                data-bs-target="#transferModal"
                                onclick="prepareTransfer('${section.section_id}', '${section.section_code}', ${section.quantity}, ${location.location_id})"
                            >
                                <i class="fas fa-arrow-right"></i> Transferir
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        stockContent.innerHTML = html;
        document.getElementById('stockContainer').style.display = 'block';
        document.getElementById('noProductMessage').style.display = 'none';
    }

    // Variable global para el modal
    window.prepareTransfer = async function(sectionId, sectionCode, currentQuantity, locationId) {
        // Llenar formulario
        document.getElementById('fromSectionDisplay').innerHTML = `<strong>${sectionCode}</strong> (${currentQuantity} uds)`;
        document.getElementById('fromSectionId').value = sectionId;
        document.getElementById('productId').value = currentProduct.id;
        document.getElementById('availableQuantity').textContent = currentQuantity;
        document.getElementById('quantityInput').max = currentQuantity;
        document.getElementById('quantityInput').value = 1;

        // Cargar secciones destino
        try {
            const response = await fetch('{{ route("inventories.transfer.available-sections") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    location_id: locationId,
                    exclude_section_id: sectionId
                })
            });

            const data = await response.json();
            if (data.success) {
                loadDestinationSections(data.sections);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    window.loadDestinationSections = function(sections) {
        const select = document.getElementById('toSectionId');
        select.innerHTML = '<option value="">Selecciona sección destino...</option>';

        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.id;
            option.textContent = `${section.code} (Nivel ${section.level}) - ${section.available_slots} espacios`;
            select.appendChild(option);
        });
    };

    window.submitTransfer = async function() {
        const productId = document.getElementById('productId').value;
        const fromSectionId = document.getElementById('fromSectionId').value;
        const toSectionId = document.getElementById('toSectionId').value;
        const quantity = parseInt(document.getElementById('quantityInput').value);

        if (!toSectionId) {
            showAlert('warning', 'Por favor selecciona una sección destino');
            return;
        }

        try {
            const response = await fetch('{{ route("inventories.transfer.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    product_id: productId,
                    from_section_id: fromSectionId,
                    to_section_id: toSectionId,
                    quantity: quantity
                })
            });

            const data = await response.json();
            if (data.success) {
                showAlert('success', data.message);
                // Cerrar modal y refrescar búsqueda
                bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
                setTimeout(() => {
                    searchProduct();
                }, 1000);
            } else {
                showAlert('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'Error al procesar la transferencia');
        }
    };

    function showAlert(type, message) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        alertContainer.innerHTML = alertHtml;
        document.body.appendChild(alertContainer);

        setTimeout(() => alertContainer.remove(), 5000);
    }
});
</script>
@endsection
