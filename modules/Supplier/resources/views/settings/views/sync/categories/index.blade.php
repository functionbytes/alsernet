@extends('layouts.theme')

@section('content')



<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $pageTitle }}</h5>
                        <p class="card-subtitle mb-0 mt-2">Jerarquía de categorías sincronizada desde Oracle ERP</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" id="btn-sync-categories">
                            <i class="fas fa-sync-alt me-1"></i> Sincronizar categorías
                        </button>
                    </div>
                </div>

                @include('core::components.alerts')

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Categorías</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['categories'] }}</h4>
                                <small class="text-muted">Nivel 1</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Familias</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['families'] }}</h4>
                                <small class="text-muted">Nivel 2</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Subfamilias</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['subfamilies'] }}</h4>
                                <small class="text-muted">Nivel 3</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Grupos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['groups'] }}</h4>
                                <small class="text-muted">Nivel 4</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs for hierarchy levels -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'categories' ? 'active' : '' }}"
                           href="?type=categories">
                            <i class="fas fa-layer-group me-1"></i> Categorías
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'families' ? 'active' : '' }}"
                           href="?type=families">
                            <i class="fas fa-folder me-1"></i> Familias
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'subfamilies' ? 'active' : '' }}"
                           href="?type=subfamilies">
                            <i class="fas fa-folder-open me-1"></i> Subfamilias
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'groups' ? 'active' : '' }}"
                           href="?type=groups">
                            <i class="fas fa-boxes me-1"></i> Grupos
                        </a>
                    </li>
                </ul>

                <!-- Search -->
                <div class="card border mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="search"
                                       value="{{ $searchKey }}"
                                       placeholder="Buscar por nombre...">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="?type={{ $type }}" class="btn btn-light">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="100">ID ERP</th>
                                <th>Nombre</th>
                                @if($type === 'families')
                                    <th>Categoría</th>
                                @elseif($type === 'subfamilies')
                                    <th>Familia</th>
                                @elseif($type === 'groups')
                                    <th>Subfamilia</th>
                                    <th width="100">Prefijo</th>
                                @endif
                                <th width="150">Última sincronización</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            @if($type === 'categories')
                                                {{ $item->erp_category_id }}
                                            @elseif($type === 'families')
                                                {{ $item->erp_family_id }}
                                            @elseif($type === 'subfamilies')
                                                {{ $item->erp_subfamily_id }}
                                            @else
                                                {{ $item->erp_group_id }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="fw-semibold">{{ $item->name }}</td>

                                    @if($type === 'families')
                                        <td>{{ $item->category?->name ?? 'N/A' }}</td>
                                    @elseif($type === 'subfamilies')
                                        <td>{{ $item->family?->name ?? 'N/A' }}</td>
                                    @elseif($type === 'groups')
                                        <td>{{ $item->subfamily?->name ?? 'N/A' }}</td>
                                        <td><code>{{ $item->prefix }}</code></td>
                                    @endif

                                    <td>
                                        @if($item->last_synced_at)
                                            <span title="{{ $item->last_synced_at->format('d/m/Y H:i:s') }}">
                                                {{ $item->last_synced_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">Nunca</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p class="mb-0">No se encontraron {{ $title }}.</p>
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
                            @if($items->total() > 0)
                            <span class="text-muted small">
                                Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }}
                            </span>
                            @endif
                            <form method="GET" action="{{ url()->current() }}" class="d-inline-flex align-items-center gap-1 mb-0">
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
                        @if($items->hasPages())
                        <nav>{{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Progress Modal -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sincronizando categorías...</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span id="sync-status-text">Iniciando sincronización de jerarquía...</span>
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

    $('#btn-sync-categories, #btn-first-sync').click(function() {
        if (confirm('¿Deseas sincronizar la jerarquía completa de categorías desde el ERP? Esto puede tardar varios minutos.')) {
            startCategorySync();
        }
    });

    function startCategorySync() {
        $.ajax({
            url: '{{ route("settings.suppliers.sync.erp.categories") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            beforeSend: function() {
                $('#sync-status-text').text('Sincronizando categorías, familias, subfamilias y grupos...');
                $('#sync-progress-bar').css('width', '0%');
                $('#sync-progress-percent').text('0%');
                $('#sync-details').html('');
                $('#btn-close-modal').prop('disabled', true);
                syncProgressModal.show();
            },
            success: function(response) {
                if (response.success) {
                    monitorProgress(response.batch_id);
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.message || 'Error al iniciar sincronización');
            }
        });
    }

    function monitorProgress(batchId) {
        progressInterval = setInterval(function() {
            $.ajax({
                url: `/setting/suppliers/sync/progress/${batchId}`,
                method: 'GET',
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
                            setTimeout(() => location.reload(), 2000);
                        } else if (batch.status === 'failed') {
                            clearInterval(progressInterval);
                            showError('La sincronización falló');
                        }
                    }
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
