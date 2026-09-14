@extends('layouts.theme')

@section('title', 'Campañas')

@section('page_header')
    @include('core::components.card', ['title' => 'Campañas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Campañas</h5>
                        <p class="small mb-0 text-muted">Gestiona las campañas de marketing para el chat en vivo</p>
                    </div>
                    <div class="ms-auto">
                        @can('create', Modules\HelpdeskCampaigns\Models\Campaign::class)
                            <a href="{{ route('helpdesk.campaigns.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva campaña
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($campaigns->total()) }}</h4>
                                <small class="text-muted">Campañas creadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($campaigns->where('status', 'active')->count()) }}</h4>
                                <small class="text-muted">En ejecucion</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($campaigns->where('status', 'draft')->count()) }}</h4>
                                <small class="text-muted">Sin publicar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Impresiones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($campaigns->sum('impressions_count') ?? 0) }}</h4>
                                <small class="text-muted">Total vistas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('helpdesk.campaigns.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar campañas..."
                                       value="{{ $filters['search'] ?? '' }}">
                            </div>
                        </div>
                        <select name="status" class="form-select flex-shrink-0" style="width: auto;">
                            <option value="">Todos los estados</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <select name="type" class="form-select flex-shrink-0" style="width: auto;">
                            <option value="">Todos los tipos</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['search', 'status', 'type']))
                            <a href="{{ route('helpdesk.campaigns.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Bulk action bar --}}
            <div id="bulk-bar" class="card-body border-bottom bg-light-warning d-none">
                <div class="d-flex align-items-center gap-2">
                    <strong><span id="bulk-count">0</span> campaña(s) seleccionada(s)</strong>
                    <div class="ms-auto d-flex gap-2">
                        <select id="bulk-action" class="form-select form-select-sm" style="width: auto;">
                            <option value="">— Acción —</option>
                            <option value="pause">Pausar</option>
                            <option value="end">Finalizar</option>
                            <option value="duplicate">Duplicar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                        <button id="bulk-apply" class="btn btn-sm btn-primary" disabled>Aplicar</button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($campaigns->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="40"><input type="checkbox" class="form-check-input" id="bulk-select-all"></th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th class="text-center">Impresiones</th>
                                    <th class="text-center">CTR</th>
                                    <th>Creada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaigns as $campaign)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-row" value="{{ $campaign->id }}"></td>
                                        <td>
                                            <div class="fw-semibold">
                                                <a href="{{ route('helpdesk.campaigns.show', $campaign) }}" class="text-dark text-decoration-none">
                                                    {{ Str::limit($campaign->name, 40) }}
                                                </a>
                                            </div>
                                            @if($campaign->description)
                                                <small class="text-muted">{{ Str::limit($campaign->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $campaign->type_label }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $campaign->status_color }}-subtle text-{{ $campaign->status_color }}">
                                                {{ $campaign->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ number_format($campaign->impressions_count ?? 0) }}</td>
                                        <td class="text-center">
                                            @php
                                                $ctr = ($campaign->impressions_count ?? 0) > 0
                                                    ? round(($campaign->clicks_count ?? 0) * 100 / $campaign->impressions_count, 1)
                                                    : 0;
                                            @endphp
                                            @if($ctr > 0)
                                                {{ $ctr }}%
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $campaign->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown"
                                                   data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('helpdesk.campaigns.show', $campaign) }}">
                                                            Ver estadisticas
                                                        </a>
                                                    </li>
                                                    @can('update', $campaign)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('helpdesk.campaigns.edit', $campaign) }}">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan

                                                    @can('update', $campaign)
                                                        @if($campaign->status === 'draft')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="{{ route('helpdesk.campaigns.publish', $campaign) }}">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">Publicar</button>
                                                                </form>
                                                            </li>
                                                        @elseif($campaign->status === 'active')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="{{ route('helpdesk.campaigns.pause', $campaign) }}">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">Pausar</button>
                                                                </form>
                                                            </li>
                                                        @elseif($campaign->status === 'paused')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="{{ route('helpdesk.campaigns.resume', $campaign) }}">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">Reanudar</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    @endcan

                                                    @can('create', Modules\HelpdeskCampaigns\Models\Campaign::class)
                                                        <li>
                                                            <form method="POST" action="{{ route('helpdesk.campaigns.duplicate', $campaign) }}">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Duplicar</button>
                                                            </form>
                                                        </li>
                                                    @endcan

                                                    @can('delete', $campaign)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item delete-btn" href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#delete-modal"
                                                               data-url="{{ route('helpdesk.campaigns.destroy', $campaign) }}"
                                                               data-title="Eliminar campaña: {{ $campaign->name }}">
                                                                Eliminar
                                                            </a>
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
                        <i class="fas fa-bullhorn fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['search', 'status', 'type']))
                                No se encontraron resultados
                            @else
                                No hay campañas creadas
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request()->hasAny(['search', 'status', 'type']))
                                No hay campañas con los filtros aplicados
                            @else
                                Crea tu primera campaña para comenzar a interactuar con tus visitantes
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'status', 'type']))
                            <a href="{{ route('helpdesk.campaigns.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            @can('create', Modules\HelpdeskCampaigns\Models\Campaign::class)
                                <a href="{{ route('helpdesk.campaigns.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Nueva campaña
                                </a>
                            @endcan
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($campaigns->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $campaigns->firstItem() }} - {{ $campaigns->lastItem() }} de {{ $campaigns->total() }}
                        </div>
                        <div>
                            {{ $campaigns->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Bulk actions
    function refreshBulkBar() {
        const checked = $('.bulk-row:checked').length;
        $('#bulk-count').text(checked);
        $('#bulk-bar').toggleClass('d-none', checked === 0);
        $('#bulk-apply').prop('disabled', checked === 0 || !$('#bulk-action').val());
    }

    $('#bulk-select-all').on('change', function () {
        $('.bulk-row').prop('checked', $(this).is(':checked'));
        refreshBulkBar();
    });

    $(document).on('change', '.bulk-row, #bulk-action', refreshBulkBar);

    $('#bulk-apply').on('click', function () {
        const action = $('#bulk-action').val();
        const ids = $('.bulk-row:checked').map((_, el) => parseInt(el.value, 10)).get();
        if (!action || ids.length === 0) return;

        if (action === 'delete' && !confirm(`¿Eliminar ${ids.length} campaña(s)?`)) return;

        $.ajax({
            url: '{{ route('helpdesk.campaigns.bulk-action') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { action, ids },
            success: function (res) {
                toastr.success(res.message || 'Acción aplicada');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error en la acción masiva');
            }
        });
    });
});
</script>
@endpush
