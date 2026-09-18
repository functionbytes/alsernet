@extends('layouts.theme')

@section('title', 'Catálogo de proveedores de integración')

@push('css')
    <style>
        .avatar-icon-32 { width: 32px; height: 32px; color: var(--icon-color, inherit); }
    </style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Catálogo de proveedores de integración'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card mb-4">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Verificación de identidad</h5>
                <p class="small mb-0 text-muted">
                    Canales disponibles para enviar el código de un solo uso antes de exponer las integraciones del cliente.
                </p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.helpdeskintegration.identity.update') }}" class="row g-3 align-items-end">
                    @csrf
                    @method('PATCH')
                    <div class="col-12 col-md-4">
                        <label for="identity_sms_enabled" class="form-label">Verificación por SMS</label>
                        <select name="identity_sms_enabled" id="identity_sms_enabled" class="form-select">
                            <option value="0" @selected(! $identitySmsEnabled)>Desactivada</option>
                            <option value="1" @selected($identitySmsEnabled)>Activada</option>
                        </select>
                        <div class="form-text">El canal email siempre está disponible.</div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">

            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Proveedores</h5>
                        <p class="small mb-0 text-muted">
                            Plataformas disponibles para vincular clientes del Helpdesk. Las nativas (PrestaShop, ERP)
                            solo se pueden activar/desactivar; los proveedores custom se pueden editar y eliminar.
                        </p>
                    </div>
                    <a href="{{ route('settings.helpdeskintegration.providers.create') }}" class="btn btn-primary">
                        Nuevo proveedor
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($providers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Origen</th>
                                    <th class="text-center">Vinculable</th>
                                    <th class="text-center">Crítico</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($providers as $provider)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-icon-32 d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
                                                      @if($provider->color) style="--icon-color: {{ $provider->color }}" @endif>
                                                    <i class="{{ $provider->icon ?: 'fas fa-plug' }}"></i>
                                                </span>
                                                <div>
                                                    <strong>{{ $provider->label }}</strong>
                                                    <div class="text-muted small">{{ $provider->platform }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($provider->isNative())
                                                <span class="badge bg-info-subtle text-info">Nativo ({{ $provider->driver }})</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Custom</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($provider->is_linkable)
                                                <span class="badge bg-success-subtle text-success">Sí</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($provider->is_critical)
                                                <span class="badge bg-warning-subtle text-warning"><i class="fas fa-shield-halved me-1"></i>Sí</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($provider->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdeskintegration.providers.edit', $provider) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-url="{{ route('settings.helpdeskintegration.providers.toggle', $provider) }}">
                                                            {{ $provider->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    @unless($provider->isNative())
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item btn-delete"
                                                                data-id="{{ $provider->id }}"
                                                                data-url="{{ route('settings.helpdeskintegration.providers.destroy', $provider) }}"
                                                                data-name="{{ $provider->label }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endunless
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
                                <i class="fas fa-plug fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay proveedores en el catálogo</h6>
                            <p class="text-muted mb-3">Añade un proveedor custom o revisa que los drivers nativos estén sembrados.</p>
                            <a href="{{ route('settings.helpdeskintegration.providers.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear primer proveedor
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Toggle form (hidden) --}}
    <form id="toggleForm" method="POST" class="d-none">
        @csrf
    </form>

    {{-- Delete confirmation modal --}}
    <div class="modal fade" id="hiDeleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="hiDeleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            ¿Eliminar el proveedor <strong id="hiDeleteItemName"></strong>? Esta acción no se puede deshacer.
                            Los clientes ya vinculados a esta plataforma conservarán su vínculo.
                        </p>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Eliminar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#hiDeleteForm').attr('action', url);
        $('#hiDeleteItemName').text(name);
        $('#hiDeleteModal').modal('show');
    });

    $(document).on('click', '.btn-toggle', function () {
        const url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
