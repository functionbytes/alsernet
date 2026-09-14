@extends('layouts.theme')

@section('title', 'Contactos')

@push('css')
    <style>
        .icon-instagram { color: #c13584; }
    </style>
    @if(helpdesk_integration_enabled())
        {{-- Framework visual .bv-modal del modal de búsqueda externa — mismo
             patrón ya usado fuera del inbox por helpdesk/customers/index.blade.php. --}}
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-commerce.css') }}">
    @endif
@endpush

@section('page_header')
    @include('core::components.card', [
        'title' => 'Gestión de contactos',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Helpdesk', 'url' => ''],
            ['label' => 'Contactos', 'active' => true],
        ],
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Contactos</h5>
                    <p class="small mb-0 text-muted">Vista 360 de los clientes del helpdesk</p>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Acciones
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('contacts.create')
                                <a class="dropdown-item" href="{{ route('contacts.import') }}">Importar</a>
                            @endcan
                            @can('contacts.update')
                                @if(helpdesk_integration_enabled())
                                    <button type="button" id="external-search-trigger" class="dropdown-item">Buscar en ERP/PrestaShop</button>
                                @endif
                            @endcan
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('contacts.export', request()->query()) }}">Exportar CSV</a>
                            <a class="dropdown-item" href="{{ route('contacts.reports') }}">Reportes</a>
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
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                            <small class="text-muted">Contactos registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Verificados</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['verified']) }}</h4>
                            <small class="text-muted">Con email verificado</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Suspendidos</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['banned']) }}</h4>
                            <small class="text-muted">Sin acceso al soporte</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Nuevos este mes</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($stats['new']) }}</h4>
                            <small class="text-muted">Creados en {{ now()->translatedFormat('F Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        @php
            $activeFilters = collect(['channel', 'verified', 'banned'])
                ->filter(fn ($k) => filled(request($k)))
                ->count();
        @endphp
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('contacts.index') }}" id="contactsFilterForm">
                <input type="hidden" name="channel"  id="ct-filter-channel"  value="{{ request('channel') }}">
                <input type="hidden" name="verified" id="ct-filter-verified" value="{{ request('verified') }}">
                <input type="hidden" name="banned"   id="ct-filter-banned"   value="{{ request('banned') }}">

                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="q" class="form-control flex-grow-1"
                           placeholder="Buscar por nombre, email, telefono o ID de ERP/PrestaShop..."
                           value="{{ request('q') }}">

                    <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#ct-filter-modal" title="Filtros avanzados">
                        <i class="fas fa-filter"></i>
                        @if($activeFilters > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary ts-filter-badge">
                                {{ $activeFilters }}
                            </span>
                        @endif
                    </button>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($activeFilters > 0 || filled(request('q')))
                            <a href="{{ route('contacts.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"><input type="checkbox" id="check-all" class="form-check-input"></th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Canales</th>
                            <th class="text-center">Conv.</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $convCount = $customer->total_conversations ?? 0;
                            @endphp
                            <tr>
                                <td class="ps-3"><input type="checkbox" class="form-check-input contact-check" value="{{ $customer->id }}"></td>
                                <td>
                                    <div class="d-block fw-semibold">
                                        {{ $customer->name ?: 'Sin nombre' }}
                                        @if($customer->email_verified_at ?? false)
                                            <i class="fas fa-circle-check text-success ms-1"></i>
                                        @endif
                                        @if($customer->banned_at ?? false)
                                            <span class="badge bg-danger-subtle text-danger ms-1">Suspendido</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $customer->email ?: '—' }}</small>
                                </td>
                                <td class="small">{{ $customer->phone ?: ($customer->whatsapp_phone ?? '—') }}</td>
                                <td class="small">
                                    {{-- Badges como los de estado del resto del panel: el icono
                                         suelto no decia por que canal se puede contactar. --}}
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($customer->email)
                                            <span class="badge bg-success-subtle text-success"><i class="fas fa-envelope me-1"></i>Email</span>
                                        @endif
                                        @if($customer->whatsapp_phone)
                                            <span class="badge bg-success-subtle text-success"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>
                                        @endif
                                        @if($customer->facebook_psid)
                                            <span class="badge bg-success-subtle text-success"><i class="fab fa-facebook-messenger me-1"></i>Facebook</span>
                                        @endif
                                        @if($customer->instagram_id)
                                            <span class="badge bg-success-subtle text-success"><i class="fab fa-instagram me-1"></i>Instagram</span>
                                        @endif
                                        @if(! $customer->email && ! $customer->whatsapp_phone && ! $customer->facebook_psid && ! $customer->instagram_id)
                                            <span class="badge bg-secondary-subtle text-secondary">Sin canal</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($convCount > 0)
                                        <span class="badge bg-primary-subtle text-primary">{{ $convCount }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('contacts.show', $customer) }}">Ver ficha 360</a>
                                            </li>
                                            <li>
                                                @if($customer->whatsapp_phone)
                                                    <a class="dropdown-item send-hsm-trigger" href="#"
                                                       data-customer-id="{{ $customer->id }}"
                                                       data-customer-name="{{ $customer->name ?: 'este contacto' }}">
                                                        Enviar plantilla WhatsApp
                                                    </a>
                                                @else
                                                    <span class="dropdown-item disabled" title="El contacto no tiene teléfono">
                                                        Enviar plantilla WhatsApp
                                                    </span>
                                                @endif
                                            </li>
                                            @can('contacts.update')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item delete-btn"
                                                            data-url="{{ route('contacts.destroy', $customer) }}"
                                                            data-title="¿Eliminar contacto {{ $customer->name ?: 'sin nombre' }}?">
                                                        Eliminar
                                                    </button>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-users fa-2x text-muted mb-3 d-block"></i>
                                    <p class="fw-semibold mb-1">Sin contactos</p>
                                    <p class="small text-muted mb-0">
                                        @if(filled(request('q')))
                                            No se encontraron resultados para "{{ request('q') }}"
                                        @else
                                            Aún no hay contactos registrados
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customers->hasPages() || $customers->total() > 15)
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Mostrar:</label>
                    <select class="form-select w-auto" id="per-page-select">
                        @foreach([15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="small text-muted">de {{ number_format($customers->total()) }}</span>
                </div>
                <div>{{ $customers->appends(request()->input())->links() }}</div>
            </div>
        @endif

    </div>

    @include('contacts::contacts.partials._send-hsm-modal')
    @if(helpdesk_integration_enabled())
        @include('contacts::contacts.partials._external-search-modal')
    @endif
    @include('core::components.delete')

    {{-- Bulk trigger (floating), igual patrón que settings/users --}}
    <div id="bulk-toolbar"
         class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none"
         style="z-index: 1050;">
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
                    <p class="text-muted mb-3">
                        Se aplicará la acción sobre <strong><span data-bulk-count>0</span> contacto(s)</strong> seleccionados.
                    </p>
                    <div class="mb-3">
                        <label for="bulk-action-select" class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select select2">
                            <option value="">Seleccionar acción...</option>
                            <option value="send-hsm">Enviar plantilla WhatsApp</option>
                            <option value="unban">Reactivar</option>
                            <option value="ban">Suspender</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" id="bulk-cancel-btn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Trigger oculto: reusa el flujo bulk ya construido en send-hsm-modal.js
         (mismo selector [data-bulk-action="send-hsm"], sin duplicar esa lógica). --}}
    <button type="button" data-bulk-action="send-hsm" class="d-none" aria-hidden="true"></button>

    {{-- Filtros avanzados --}}
    <div class="modal fade" id="ct-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Canal</label>
                        <select id="ct-modal-channel" class="form-control select2-filter-modal">
                            <option value="">Todos los canales</option>
                            <option value="email"     @selected(request('channel') === 'email')>Email</option>
                            <option value="whatsapp"  @selected(request('channel') === 'whatsapp')>WhatsApp</option>
                            <option value="facebook"  @selected(request('channel') === 'facebook')>Facebook</option>
                            <option value="instagram" @selected(request('channel') === 'instagram')>Instagram</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email verificado</label>
                        <select id="ct-modal-verified" class="form-control select2-filter-modal">
                            <option value="">Verificados y sin verificar</option>
                            <option value="yes" @selected(request('verified') === 'yes')>Solo verificados</option>
                            <option value="no"  @selected(request('verified') === 'no')>Solo sin verificar</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Acceso al soporte</label>
                        <select id="ct-modal-banned" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="yes" @selected(request('banned') === 'yes')>Solo suspendidos</option>
                            <option value="no"  @selected(request('banned') === 'no')>Solo con acceso</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="ct-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                    <button type="button" id="ct-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    $('#contactsFilterForm .select2').select2({ width: '100%' });

    var bulkUrl = '{{ route("contacts.bulk-action") }}';

    // Helper global (public/core/js/bulk.js, cargado en el layout) — mismo
    // patrón que settings/users: toolbar flotante + contador delegado.
    var bulk = window.BulkActions.init({ checkbox: '.contact-check' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#per-page-select').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción antes de continuar.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un contacto.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' contactos? Esta acción no se puede deshacer.')) { return; }

        if (action === 'send-hsm') {
            $('#bulk-modal').modal('hide');
            // send-hsm no pasa por bulkUrl: reusa el flujo bulk ya construido
            // en send-hsm-modal.js (mismo selector, sin duplicar esa lógica).
            $('[data-bulk-action="send-hsm"]').trigger('click');
            return;
        }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: bulkUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            contentType: 'application/json',
            data: JSON.stringify({ action: action, ids: ids }),
        }).done(function (resp) {
            $('#bulk-modal').modal('hide');
            toastr.success(resp.message || 'Acción aplicada');
            setTimeout(function () { location.reload(); }, 800);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al ejecutar la acción');
        }).always(function () {
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        });
    });

    $('.delete-btn').on('click', function (e) {
        e.preventDefault();
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });
});
</script>
<script src="{{ asset('modules/contacts/js/send-hsm-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/send-hsm-modal.js')) }}"></script>
@if(helpdesk_integration_enabled())
<script src="{{ asset('modules/contacts/js/external-search-modal.js') }}?v={{ filemtime(public_path('modules/contacts/js/external-search-modal.js')) }}"></script>
@endif
<script>
$(document).ready(function () {
    // El modal solo rellena los hidden del formulario de busqueda: asi el filtro
    // viaja por GET y la URL sigue siendo compartible.
    $('.select2-filter-modal').select2({ dropdownParent: $('#ct-filter-modal'), width: '100%' });

    $('#ct-filter-apply-btn').on('click', function () {
        $('#ct-filter-channel').val($('#ct-modal-channel').val());
        $('#ct-filter-verified').val($('#ct-modal-verified').val());
        $('#ct-filter-banned').val($('#ct-modal-banned').val());
        $('#ct-filter-modal').modal('hide');
        $('#contactsFilterForm').submit();
    });

    $('#ct-filter-clear-btn').on('click', function () {
        window.location = '{{ route('contacts.index') }}';
    });
});
</script>
@endpush

@push('styles')
<style>
    .ts-filter-badge { font-size: .6rem; }
</style>
@endpush
