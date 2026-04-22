@extends('layouts.theme')

@section('title', 'Aprobaciones de paginas')

@section('content')
    @include('core::components.card', ['title' => 'Aprobaciones de paginas'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Aprobaciones de paginas</h5>
                        <p class="small mb-0 text-muted">Revisa y gestiona las solicitudes de aprobacion enviadas por los editores</p>
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
                                <small class="text-muted">Solicitudes registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Pendientes</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                                <small class="text-muted">Esperando revision</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Aprobadas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['approved'] }}</h4>
                                <small class="text-muted">Revisadas y aprobadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Rechazadas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['rejected'] }}</h4>
                                <small class="text-muted">Devueltas al autor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('pages.approvals.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por titulo de pagina..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pendientes</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprobadas</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazadas</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('pages.approvals.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($approvals->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron aprobaciones
                                @else
                                    No hay aprobaciones registradas
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de busqueda
                                @else
                                    Las solicitudes de aprobacion apareceran aqui
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="approvals-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Pagina</th>
                                    <th>Solicitado por</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approvals as $approval)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $approval->id }}"></td>
                                        <td>
                                            <strong>
                                                @if($approval->page)
                                                    <a href="{{ route('pages.edit', $approval->page->id) }}" class="text-decoration-none">
                                                        {{ $approval->page->title }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">Pagina eliminada</span>
                                                @endif
                                            </strong>
                                            @if($approval->page)
                                                <br><small class="text-muted">/{{ $approval->page->slug }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($approval->requestedBy)
                                                <span>{{ $approval->requestedBy->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($approval->requested_at)
                                                <span title="{{ $approval->requested_at->format('d/m/Y H:i') }}">
                                                    {{ $approval->requested_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($approval->isPending())
                                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                            @elseif($approval->isApproved())
                                                <span class="badge bg-success-subtle text-success">Aprobada</span>
                                            @elseif($approval->isRejected())
                                                <span class="badge bg-danger-subtle text-danger">Rechazada</span>
                                            @else
                                                <span class="badge bg-light text-black">{{ $approval->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($approval->page)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('pages.edit', $approval->page->id) }}">
                                                                Ver pagina
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($approval->isPending())
                                                        <li>
                                                            <form action="{{ route('pages.approvals.approve', $approval->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Aprobar</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item reject-btn" href="javascript:void(0)"
                                                               data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                               data-approval-id="{{ $approval->id }}"
                                                               data-page-title="{{ $approval->page?->title }}">
                                                                Rechazar
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if($approval->notes)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <span class="dropdown-item-text small text-muted">
                                                                {{ Str::limit($approval->notes, 80) }}
                                                            </span>
                                                        </li>
                                                    @endif
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

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> aprobacion(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar accion...</option>
                            <option value="approve">Aprobar</option>
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

    {{-- Reject modal --}}
    <div class="modal fade" id="reject-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="reject-form" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Rechazar pagina</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Pagina: <strong id="reject-page-title"></strong>
                        </p>
                        <div class="mb-3">
                            <label for="reason" class="form-label">
                                Motivo del rechazo <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="reason" name="reason" rows="4"
                                      maxlength="1000" required
                                      placeholder="Explica al autor que debe corregir..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Confirmar rechazo</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal confirmar bulk delete --}}
    <div class="modal fade" id="modal-bulk-delete-confirm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Eliminar las <strong id="modal-bulk-delete-confirm-count">0</strong> aprobacion(es) seleccionadas? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" id="btn-confirm-bulk-delete" class="btn btn-primary w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
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

        if (!action) { toastr.warning('Selecciona una accion.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una aprobacion.'); return; }
        if (action === 'delete') {
            if (!window._bulkDeleteConfirmed) {
                $('#modal-bulk-delete-confirm-count').text(ids.length);
                $('#btn-confirm-bulk-delete').off('click').on('click', function () {
                    $('#modal-bulk-delete-confirm').modal('hide');
                    window._bulkDeleteConfirmed = true;
                    $('#bulk-apply-btn').trigger('click');
                });
                $('#modal-bulk-delete-confirm').modal('show');
                return;
            }
            window._bulkDeleteConfirmed = false;
        }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('pages.approvals.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' aprobacion(es) procesadas.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $(document).on('click', '.reject-btn', function () {
        const approvalId = $(this).data('approval-id');
        const pageTitle = $(this).data('page-title');
        $('#reject-form').attr('action', '{{ url("panel/pages/approvals") }}/' + approvalId + '/reject');
        $('#reject-page-title').text(pageTitle);
        $('#reason').val('');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
