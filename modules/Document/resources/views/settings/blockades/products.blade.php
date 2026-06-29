@extends('layouts.theme')

@section('title', 'Productos Bloqueados')

@section('content')

    @include('core::components.card', ['title' => 'Productos Bloqueados'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Actions Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Sincronización de productos</h5>
                        <p class="small mb-0 text-muted">Sincroniza productos individuales desde PrestaShop o vuelve a la configuración de etiquetas.</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @can('sync-document-blockades')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncProductModal" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Sincronizar producto" aria-label="Sincronizar producto">
                            <i class="fa-duotone fa-rotate me-2"></i>Sincronizar producto
                        </button>
                        @endcan
                        <a href="{{ route('settings.documents.blockades.index') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Volver a configuración" aria-label="Volver a configuración">
                            <i class="fa-duotone fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Productos bloqueados</h5>
                        <p class="small mb-0 text-muted">Listado completo de registros en la tabla de bloqueos</p>
                    </div>
                    <span class="badge bg-secondary text-white fs-2">{{ number_format($totalBlockades) }} registros</span>
                </div>
            </div>

            <!-- Products Search & Filter -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.documents.blockades.products') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por product_id, product_attribute_id o source_id..."
                                       value="{{ $search }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select select2">
                                <option value="">Todos los tipos</option>
                                @foreach($blockadeTypes as $type)
                                    <option value="{{ $type }}" {{ $typeFilter === $type ? 'selected' : '' }}>
                                        {{ strtoupper($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar" aria-label="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                                @if($search || $typeFilter)
                                    <a href="{{ route('settings.documents.blockades.products') }}" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Limpiar filtros" aria-label="Limpiar filtros">
                                        <i class="fa-duotone fa-xmark"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products List -->
            <div class="card-body border-bottom">
                @if($blockades->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" width="7%">ID</th>
                                    <th width="12%">Source ID</th>
                                    <th width="13%">Product ID</th>
                                    <th width="18%">Product Attribute ID</th>
                                    <th width="15%">Tipo</th>
                                    <th width="20%">Tipo de documento</th>
                                    <th width="10%">Creado</th>
                                    <th width="5%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($blockades as $blockade)
                                    <tr>
                                        <td class="ps-4 text-muted small">{{ $blockade->id }}</td>
                                        <td><code class="text-secondary">{{ $blockade->source_id }}</code></td>
                                        <td>
                                            @if($blockade->product_id)
                                                <code class="text-primary">{{ $blockade->product_id }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($blockade->product_attribute_id)
                                                <code class="text-info">{{ $blockade->product_attribute_id }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ strtoupper($blockade->blockade_type) }}</span>
                                        </td>
                                        <td>
                                            @if($blockade->documentType)
                                                <span class="badge bg-primary-subtle text-primary">{{ $blockade->documentType->label }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $blockade->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('sync-document-blockades')
                                                    <li>
                                                        <button type="button" class="dropdown-item sync-product-btn"
                                                                data-source-id="{{ $blockade->source_id }}">
                                                            Sincronizar producto
                                                        </button>
                                                    </li>
                                                    @endcan
                                                    @can('manage-document-blockades')
                                                    <li>
                                                        <button type="button" class="dropdown-item delete-blockade-btn"
                                                                data-id="{{ $blockade->id }}">
                                                            Quitar de bloqueos
                                                        </button>
                                                    </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-box-open fs-7"></i>
                            </div>
                            <h6 class="mb-1">Sin resultados</h6>
                            <p class="text-muted mb-0">No hay bloqueos que coincidan con los filtros aplicados</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($blockades->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $blockades->firstItem() }}</strong> a <strong>{{ $blockades->lastItem() }}</strong>
                            de <strong>{{ number_format($blockades->total()) }}</strong> registros
                        </div>
                        <nav>{{ $blockades->links() }}</nav>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Sync Product Modal -->
    <div class="modal fade" id="syncProductModal" tabindex="-1" aria-labelledby="syncProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="syncProductModalLabel">
                        Sincronizar producto específico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="syncSourceId" class="form-label">ID del producto</label>
                        <input type="number" class="form-control" id="syncSourceId" placeholder="Ej: 12345" autocomplete="off">
                        <small class="text-muted">Puedes ingresar el <strong>source_id</strong>, <strong>product_id</strong> o <strong>product_attribute_id</strong>. Se buscará en cualquiera de esos campos.</small>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> El producto debe existir en PrestaShop y tener una etiqueta asociada a un tipo de documento.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary mb-1 w-100" id="confirmSyncProductBtn">
                        <i class="fa-duotone fa-rotate me-2"></i>Sincronizar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Sync specific product from modal
    $('#confirmSyncProductBtn').on('click', function() {
        const sourceId = $('#syncSourceId').val().trim();

        if (!sourceId) {
            Swal.fire('Error', 'Debes ingresar un Source ID', 'error');
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sincronizando...');

        $.ajax({
            url: '{{ route('settings.documents.blockades.sync-product') }}',
            method: 'POST',
            data: {
                source_id: sourceId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Éxito', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Advertencia', response.message, 'warning');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al sincronizar el producto', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                $('#syncProductModal').modal('hide');
            }
        });
    });

    // Sync product from row button
    $(document).on('click', '.sync-product-btn', function() {
        const sourceId = $(this).data('source-id');
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ route('settings.documents.blockades.sync-product') }}',
            method: 'POST',
            data: {
                source_id: sourceId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Éxito', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Advertencia', response.message, 'warning');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al sincronizar el producto', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Delete blockade from row button
    $(document).on('click', '.delete-blockade-btn', function() {
        const blockadeId = $(this).data('id');
        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
            title: '¿Quitar de bloqueos?',
            text: 'El producto se eliminará de la lista de bloqueos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '{{ route('settings.documents.blockades.destroy', ['id' => '__ID__']) }}'.replace('__ID__', blockadeId),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE',
                    id: blockadeId
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Éxito', response.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Advertencia', response.message, 'warning');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error al quitar el bloqueo', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
});
</script>
@endpush
