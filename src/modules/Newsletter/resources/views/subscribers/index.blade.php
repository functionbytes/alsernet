@extends('layouts.theme')

@section('page_title', 'Suscriptores del newsletter')

@section('page_header')
    @include('core::components.card', ['title' => 'Suscriptores del newsletter'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Suscriptores</h5>
                        <p class="small mb-0 text-muted">Gestiona los suscriptores del newsletter</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('newsletter.export') }}">Exportar CSV</a>
                                @if($search || $status !== null && $status !== '')
                                    <a class="dropdown-item" href="{{ route('newsletter.export', array_filter(['search' => $search, 'status' => $status])) }}">Exportar CSV (filtrado)</a>
                                @endif
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
                                <small class="text-muted">Registrados en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['subscribed'] }}</h4>
                                <small class="text-muted">Con suscripcion activa</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Desuscritos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['unsubscribed'] }}</h4>
                                <small class="text-muted">Han cancelado su suscripcion</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Nuevos este mes</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['new'] }}</h4>
                                <small class="text-muted">Registrados en {{ now()->translatedFormat('F Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('newsletter.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por email o nombre..."
                                       value="{{ $search ?? '' }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" {{ ($status ?? '') === '1' ? 'selected' : '' }}>Suscrito</option>
                                <option value="0" {{ ($status ?? '') === '0' ? 'selected' : '' }}>Desuscrito</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if($search || ($status !== null && $status !== ''))
                                <a href="{{ route('newsletter.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            @if ($subscribers->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="subscribers-table">
                        <thead class="table-light">
                            <tr>
                                <th width="3%">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th width="30%">Email</th>
                                <th width="20%">Nombre</th>
                                <th width="12%">Estado</th>
                                <th width="18%">Suscripcion</th>
                                <th width="17%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscribers as $subscriber)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input bulk-checkbox"
                                               value="{{ $subscriber->id }}">
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $subscriber->email }}</span>
                                    </td>
                                    <td>{{ $subscriber->name ?? '—' }}</td>
                                    <td>
                                        @if ($subscriber->status)
                                            <span class="badge bg-success-subtle text-success">Suscrito</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Desuscrito</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $subscriber->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if ($subscriber->status)
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-unsubscribe"
                                                                data-id="{{ $subscriber->id }}"
                                                                data-email="{{ $subscriber->email }}">
                                                            Desuscribir
                                                        </button>
                                                    </li>
                                                @else
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-resubscribe"
                                                                data-id="{{ $subscriber->id }}"
                                                                data-email="{{ $subscriber->email }}">
                                                            Resuscribir
                                                        </button>
                                                    </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item btn-delete"
                                                            data-url="{{ route('newsletter.destroy', $subscriber) }}"
                                                            data-email="{{ $subscriber->email }}">
                                                        Eliminar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay suscriptores</h5>
                    <p class="text-muted mb-4">
                        @if($search || ($status !== null && $status !== ''))
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Los suscriptores apareceran aqui una vez que alguien se registre.
                        @endif
                    </p>
                    @if($search || ($status !== null && $status !== ''))
                        <a href="{{ route('newsletter.index') }}" class="btn btn-secondary">Ver todos</a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if ($subscribers->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $subscribers->firstItem() }} – {{ $subscribers->lastItem() }} de {{ $subscribers->total() }} suscriptores
                        </div>
                        <div>
                            {{ $subscribers->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Form oculto para eliminar individual --}}
    <form id="delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

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
                    <p class="text-muted mb-3">
                        Se aplicará la acción sobre <strong><span class="bulk-count-label">0</span> suscriptor(es)</strong> seleccionados.
                    </p>
                    <div class="mb-3">
                        <label for="bulk-action-select" class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="resubscribe">Resuscribir</option>
                            <option value="unsubscribe">Desuscribir</option>
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
$(document).ready(function () {

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Bulk actions ───────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('show.bs.modal', function () {
        $('.bulk-count-label').text(bulk.getIds().length);
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción antes de continuar.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un suscriptor.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' suscriptor(es) seleccionados? Esta acción no se puede deshacer.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('newsletter.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Desuscribir / Resuscribir individual ──────────────────────────────
    $(document).on('click', '.btn-unsubscribe, .btn-resubscribe', function () {
        const id     = $(this).data('id');
        const email  = $(this).data('email');
        const action = $(this).hasClass('btn-unsubscribe') ? 'unsubscribe' : 'resubscribe';
        const label  = action === 'unsubscribe' ? 'desuscribir' : 'resuscribir';

        if (!confirm('¿' + label.charAt(0).toUpperCase() + label.slice(1) + ' a "' + email + '"?')) { return; }

        $.ajax({
            url: '{{ route('newsletter.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: [id], _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
            },
        });
    });

    // ── Eliminar individual ────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        const url   = $(this).data('url');
        const email = $(this).data('email');
        if (!confirm('¿Eliminar el suscriptor "' + email + '"?')) { return; }
        $('#delete-form').attr('action', url).submit();
    });
});
</script>
@endpush
