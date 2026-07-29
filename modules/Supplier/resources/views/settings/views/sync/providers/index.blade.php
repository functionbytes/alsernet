@extends('layouts.theme')

@section('content')



<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
                        <p class="card-subtitle mb-0 mt-2">Proveedores sincronizados desde Oracle ERP</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" id="btn-sync-all-providers">
                            <i class="fas fa-sync-alt me-1"></i> Sincronizar todos
                        </button>
                    </div>
                </div>

                @include('core::components.alerts')

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total proveedores</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">En la base de datos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Proveedores habilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Con productos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['with_products'] }}</h4>
                                <small class="text-muted">Tienen productos asignados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-secondary mb-2">Última sincronización</h6>
                                <h4 class="mb-1 fw-bold">
                                    @if($stats['last_sync'])
                                        {{ $stats['last_sync']->diffForHumans() }}
                                    @else
                                        Nunca
                                    @endif
                                </h4>
                                <small class="text-muted">
                                    {{ $stats['last_sync'] ? $stats['last_sync']->format('d/m/Y H:i') : 'Sin sincronizar' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card border mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('settings.suppliers.providers.index') }}" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Buscar</label>
                                <input type="text" class="form-control" name="search"
                                       value="{{ $searchKey }}"
                                       placeholder="Nombre, código o CIF...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="is_active">
                                    <option value="">Todos</option>
                                    <option value="1" {{ $isActive === '1' ? 'selected' : '' }}>Activos</option>
                                    <option value="0" {{ $isActive === '0' ? 'selected' : '' }}>Inactivos</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="{{ route('settings.suppliers.providers.index') }}" class="btn btn-light">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Providers Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>CIF</th>
                                <th>Email</th>
                                <th class="text-center">Productos</th>
                                <th class="text-center">Estado</th>
                                <th>Última sincronización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $provider)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $provider->code ?? 'N/A' }}</span>
                                    </td>
                                    <td class="fw-semibold">{{ $provider->name }}</td>
                                    <td>{{ $provider->tax_id ?? 'N/A' }}</td>
                                    <td>{{ $provider->email ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $provider->provider_products_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($provider->is_active)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($provider->last_synced_at)
                                            <span title="{{ $provider->last_synced_at->format('d/m/Y H:i:s') }}">
                                                {{ $provider->last_synced_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">Nunca</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('settings.suppliers.providers.show', $provider->uid) }}"
                                               class="btn btn-light"
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-primary btn-sync-provider"
                                                    data-provider-id="{{ $provider->erp_provider_id }}"
                                                    data-provider-name="{{ $provider->name }}"
                                                    title="Sincronizar productos">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p class="mb-0">No se encontraron proveedores.</p>
                                            <button type="button" class="btn btn-primary mt-3" id="btn-first-sync">
                                                <i class="fas fa-sync-alt me-1"></i> Sincronizar desde ERP
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-white border-top py-2 mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            @if($providers->total() > 0)
                            <span class="text-muted small">
                                Mostrando {{ $providers->firstItem() }}–{{ $providers->lastItem() }} de {{ $providers->total() }}
                            </span>
                            @endif
                            <form method="GET" action="{{ route('settings.suppliers.providers.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <label class="text-muted small mb-0">Por página:</label>
                                <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    @foreach([10, 20, 50, 100] as $opt)
                                        <option value="{{ $opt }}" {{ request('per_page', 15) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                    <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                                </select>
                            </form>
                        </div>
                        @if($providers->hasPages())
                        <nav>{{ $providers->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sincronizando...</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span id="sync-status-text">Iniciando sincronización...</span>
                        <span id="sync-progress-percent">0%</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             id="sync-progress-bar"
                             style="width: 0%"></div>
                    </div>
                </div>
                <div id="sync-details" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" disabled id="btn-close-modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const syncProgressModal = new bootstrap.Modal(document.getElementById('syncProgressModal'));
    let progressInterval = null;

    // Sincronizar todos los proveedores
    $('#btn-sync-all-providers, #btn-first-sync').click(function() {
        if (confirm('¿Deseas sincronizar TODOS los proveedores desde el ERP? Esto puede tardar varios minutos.')) {
            startProviderSync(null, 'todos los proveedores');
        }
    });

    // Sincronizar un proveedor específico
    $(document).on('click', '.btn-sync-provider', function() {
        const providerId = $(this).data('provider-id');
        const providerName = $(this).data('provider-name');

        if (confirm(`¿Deseas sincronizar los productos del proveedor "${providerName}"?`)) {
            startProviderSync(providerId, providerName);
        }
    });

    function startProviderSync(providerId, providerName) {
        const url = providerId
            ? '{{ route("settings.suppliers.sync.erp.products") }}'
            : '{{ route("settings.suppliers.sync.erp.providers") }}';

        const data = providerId ? { erp_provider_id: providerId } : {};

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            beforeSend: function() {
                $('#sync-status-text').text(`Sincronizando ${providerName}...`);
                $('#sync-progress-bar').css('width', '0%');
                $('#sync-progress-percent').text('0%');
                $('#sync-details').html('');
                $('#btn-close-modal').prop('disabled', true);
                syncProgressModal.show();
            },
            success: function(response) {
                if (response.success) {
                    const batchId = response.batch_id;
                    monitorProgress(batchId);
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error al iniciar sincronización';
                showError(message);
            }
        });
    }

    function monitorProgress(batchId) {
        progressInterval = setInterval(function() {
            $.ajax({
                url: `/setting/suppliers/sync/progress/${batchId}`,
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                success: function(response) {
                    if (response.success) {
                        const batch = response.batch;
                        const progress = batch.progress_percentage || 0;

                        $('#sync-progress-bar').css('width', progress + '%');
                        $('#sync-progress-percent').text(Math.round(progress) + '%');

                        let details = `Procesados: ${batch.processed_items}/${batch.total_items}`;
                        if (batch.failed_items > 0) {
                            details += ` | Fallidos: ${batch.failed_items}`;
                        }
                        $('#sync-details').html(details);

                        if (batch.status === 'completed') {
                            clearInterval(progressInterval);
                            $('#sync-status-text').text('¡Sincronización completada!');
                            $('#sync-progress-bar').removeClass('progress-bar-animated');
                            $('#btn-close-modal').prop('disabled', false);

                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else if (batch.status === 'failed') {
                            clearInterval(progressInterval);
                            showError('La sincronización falló');
                        }
                    }
                },
                error: function() {
                    clearInterval(progressInterval);
                    showError('Error al obtener progreso');
                }
            });
        }, 2000);
    }

    function showError(message) {
        clearInterval(progressInterval);
        $('#sync-status-text').text('Error');
        $('#sync-progress-bar').removeClass('progress-bar-animated').addClass('bg-danger');
        $('#sync-details').html(`<span class="text-danger">${message}</span>`);
        $('#btn-close-modal').prop('disabled', false);
    }
});
</script>
@endpush
@endsection
