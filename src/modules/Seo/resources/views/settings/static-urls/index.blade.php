@extends('layouts.theme')

@section('title', 'URLs estáticas del sitemap')

@section('page_header')
    @include('core::components.card', ['title' => 'URLs estáticas del sitemap'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">URLs estáticas del sitemap</h5>
                        <p class="small mb-0 text-muted">Gestiona las URLs estáticas que se incluirán en el sitemap XML</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.seo.static-urls.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva URL
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                                <small class="text-muted">URLs configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($totalActive) }}</h4>
                                <small class="text-muted">Incluidas en el sitemap</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($total - $totalActive) }}</h4>
                                <small class="text-muted">Excluidas del sitemap</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.seo.static-urls.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por URL o notas..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activas</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('settings.seo.static-urls.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($staticUrls->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>URL</th>
                                    <th class="text-center">Prioridad</th>
                                    <th class="text-center">Frecuencia</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staticUrls as $url)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $url->id }}"></td>
                                        <td>
                                            <code class="text-primary">{{ $url->url }}</code>
                                            @if($url->notes)
                                                <br><small class="text-muted">{{ $url->notes }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ number_format($url->priority, 1) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $url->changefreq }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($url->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-light text-black">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.static-urls.edit', $url) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item toggle-active" href="javascript:void(0)"
                                                           data-url="{{ route('settings.seo.static-urls.toggle-active', $url) }}">
                                                            {{ $url->is_active ? 'Desactivar' : 'Activar' }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.seo.static-urls.destroy', $url) }}"
                                                           data-title="Eliminar: {{ $url->url }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
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
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron URLs
                                @else
                                    No hay URLs estáticas configuradas
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Comienza agregando tu primera URL estática para incluirla en el sitemap
                                @endif
                            </p>
                            @if(!request('search') && !request('status'))
                                <a href="{{ route('settings.seo.static-urls.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus me-1"></i> Agregar primera URL
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($staticUrls->hasPages())
                <div class="card-footer">{{ $staticUrls->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> URL(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Delete modal
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Toggle active via AJAX
    $(document).on('click', '.toggle-active', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        $.ajax({
            url: url,
            method: 'POST',
            data: { _method: 'PATCH', _token: $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (data) {
            toastr.success(data.message || 'Estado actualizado.');
            location.reload();
        })
        .fail(function () {
            toastr.error('Error al cambiar el estado');
        });
    });

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una URL.'); return; }
        const doAction = function () {
            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('settings.seo.static-urls.bulk-action') }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message || res.count + ' URL(s) actualizadas.');
                    setTimeout(() => location.reload(), 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar las ' + ids.length + ' URL(s) seleccionadas?', doAction);
        } else {
            doAction();
        }
    });
});
</script>
@endpush
