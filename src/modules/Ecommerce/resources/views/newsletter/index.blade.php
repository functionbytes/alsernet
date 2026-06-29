@extends('layouts.theme')

@section('title', 'Newsletter')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Newsletter'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Suscriptores newsletter</h5>
                        <p class="small mb-0 text-muted">Administra los suscriptores del newsletter</p>
                    </div>
                    @can('newsletter.export')
                        <a href="{{ route('ecommerce.newsletter.export') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-export me-1"></i> Exportar CSV
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom py-3">
                <form action="{{ route('ecommerce.newsletter.index') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <input
                            type="text"
                            name="search"
                            class="form-control form-control-sm"
                            placeholder="Buscar por email o nombre..."
                            value="{{ request('search') }}"
                        >
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="subscribed" @selected(request('status') === 'subscribed')>Suscrito</option>
                            <option value="unsubscribed" @selected(request('status') === 'unsubscribed')>Dado de baja</option>
                            <option value="bounced" @selected(request('status') === 'bounced')>Rebotado</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('ecommerce.newsletter.index') }}" class="btn btn-sm btn-light ms-1">Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Bulk toolbar --}}
            <div id="bulk-toolbar" class="d-none card-body border-bottom py-2 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted"><span id="bulk-count">0</span> seleccionados</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-bulk-action" data-action="unsubscribe">
                        Dar de baja
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger js-bulk-action" data-action="delete">
                        Eliminar
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                @if($subscribers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="36">
                                        <input type="checkbox" class="form-check-input bulk-select-all">
                                    </th>
                                    <th>Email</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Origen</th>
                                    <th>Suscrito el</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscribers as $subscriber)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $subscriber->id }}">
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $subscriber->email }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $subscriber->name ?? '—' }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'subscribed'   => ['label' => 'Suscrito',     'class' => 'success'],
                                                    'unsubscribed' => ['label' => 'Dado de baja',  'class' => 'danger'],
                                                    'bounced'      => ['label' => 'Rebotado',      'class' => 'warning'],
                                                ];
                                                $st = $statusMap[$subscriber->status] ?? ['label' => $subscriber->status, 'class' => 'secondary'];
                                            @endphp
                                            <span class="badge bg-{{ $st['class'] }}">{{ $st['label'] }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $subscriber->source ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $subscriber->subscribed_at ? $subscriber->subscribed_at->format('d/m/Y H:i') : '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('newsletter.delete')
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item js-delete-subscriber"
                                                                data-id="{{ $subscriber->id }}"
                                                                data-url="{{ route('ecommerce.newsletter.destroy', $subscriber) }}"
                                                            >
                                                                Eliminar
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
                        <i class="fas fa-envelope fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay suscriptores</h5>
                        <p class="text-muted small">Los suscriptores aparecen aqui cuando alguien se suscribe al newsletter.</p>
                    </div>
                @endif
            </div>

            @if($subscribers->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $subscribers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal confirmacion eliminar --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Confirmar eliminacion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Esta seguro que desea eliminar este suscriptor? Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer d-block border-top">
                    <form id="deleteForm" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-primary w-100 mb-2">Confirmar eliminacion</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal bulk action --}}
    <div class="modal fade" id="bulkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="bulkModalTitle">Confirmar accion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="bulkModalMessage">¿Aplicar la accion a los seleccionados?</p>
                </div>
                <div class="modal-footer d-block border-top">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="bulkConfirmBtn">Confirmar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    var pendingBulkAction = null;
    var pendingBulkIds = [];

    // Select all checkbox
    $(document).on('change', '.bulk-select-all', function () {
        $('.bulk-checkbox').prop('checked', this.checked);
        updateBulkToolbar();
    });

    $(document).on('change', '.bulk-checkbox', function () {
        var total = $('.bulk-checkbox').length;
        var checked = $('.bulk-checkbox:checked').length;
        $('.bulk-select-all').prop('indeterminate', checked > 0 && checked < total);
        $('.bulk-select-all').prop('checked', checked === total && total > 0);
        updateBulkToolbar();
    });

    function updateBulkToolbar() {
        var count = $('.bulk-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-toolbar').removeClass('d-none');
            $('#bulk-count').text(count);
        } else {
            $('#bulk-toolbar').addClass('d-none');
        }
    }

    // Delete single
    $(document).on('click', '.js-delete-subscriber', function () {
        var url = $(this).data('url');
        $('#deleteForm').attr('action', url);
        $('#deleteModal').modal('show');
    });

    // Bulk action
    $(document).on('click', '.js-bulk-action', function () {
        var action = $(this).data('action');
        var ids = $('.bulk-checkbox:checked').map(function () { return $(this).val(); }).get();

        if (!ids.length) {
            return;
        }

        pendingBulkAction = action;
        pendingBulkIds = ids;

        var messages = {
            unsubscribe: 'Dar de baja a ' + ids.length + ' suscriptor(es)?',
            delete: 'Eliminar ' + ids.length + ' suscriptor(es)? Esta accion no se puede deshacer.'
        };

        $('#bulkModalMessage').text(messages[action] || '');
        $('#bulkModal').modal('show');
    });

    $('#bulkConfirmBtn').on('click', function () {
        if (!pendingBulkAction || !pendingBulkIds.length) {
            return;
        }

        var $btn = $(this).prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('ecommerce.newsletter.bulk-action') }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
            data: JSON.stringify({ action: pendingBulkAction, ids: pendingBulkIds }),
            success: function (r) {
                toastr.success(r.message || 'Accion aplicada.');
                $('#bulkModal').modal('hide');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al procesar la accion.';
                toastr.error(msg);
                $btn.prop('disabled', false).text('Confirmar');
            }
        });
    });
});
</script>
@endpush
