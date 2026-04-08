@extends('layouts.theme')

@section('title', 'Historial de exportaciones')

@section('content')
    @include('core::components.card', ['title' => 'Historial de exportaciones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Historial de exportaciones</h5>
                        <p class="small mb-0 text-muted">Consulta y descarga tus exportaciones recientes</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('reviews.index') }}">
                                    Nueva exportación
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Exportaciones registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Completadas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['completed'] }}</h4>
                                <small class="text-muted">Disponibles para descarga</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">En proceso</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['processing'] }}</h4>
                                <small class="text-muted">Pendientes o en generación</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Fallidas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['failed'] }}</h4>
                                <small class="text-muted">Con errores de generación</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($exports->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-history fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay exportaciones registradas</h6>
                            <p class="text-muted mb-0">Las exportaciones que realices aparecerán aquí</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Fecha</th>
                                    <th>Formato</th>
                                    <th>Filtros</th>
                                    <th>Filas</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exports as $export)
                                    <tr data-id="{{ $export->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $export->id }}"></td>
                                        <td>{{ $export->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ strtoupper($export->format) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $export->filters ? (collect($export->filters)->filter()->keys()->map(fn($k) => str_replace('_', ' ', $k))->implode(', ') ?: 'Sin filtros') : 'Sin filtros' }}
                                            </small>
                                        </td>
                                        <td>{{ $export->row_count !== null ? number_format($export->row_count) : '—' }}</td>
                                        <td>
                                            @switch($export->status)
                                                @case('completed')
                                                    @if($export->isExpired())
                                                        <span class="badge bg-warning">Expirado</span>
                                                    @else
                                                        <span class="badge bg-success">Completado</span>
                                                    @endif
                                                    @break
                                                @case('processing')
                                                    <span class="badge bg-info">
                                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                                        Procesando
                                                    </span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge bg-danger" title="{{ $export->error_message }}">Fallido</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">Pendiente</span>
                                            @endswitch
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($export->isDownloadable())
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('reviews.exports.history.download', $export) }}">
                                                                Descargar
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('reviews.exports.history.destroy', $export) }}"
                                                           data-title="Eliminar exportacion">
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
                @endif
            </div>

            @if($exports->hasPages())
                <div class="card-footer">
                    {{ $exports->links() }}
                </div>
            @endif

        </div>
    </div>

    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> exportación(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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

@endsection

@push('scripts')
<script>
$(function () {
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
        if (!ids.length) { toastr.warning('Selecciona al menos una exportación.'); return; }
        if (!confirm('¿Eliminar las ' + ids.length + ' exportación(es) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('reviews.exports.history.bulk-delete') }}',
            method: 'DELETE',
            data: JSON.stringify({ ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' exportación(es) eliminada(s).');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('#delete-modal').on('show.bs.modal', function (e) {
        var $trigger = $(e.relatedTarget);
        $(this).find('.modal-title').text($trigger.data('title'));
        $('#delete-form').attr('action', $trigger.data('url'));
    });
});
</script>
@endpush

@include('core::components.delete')
