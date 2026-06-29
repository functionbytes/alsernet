@extends('layouts.theme')

@section('title', 'Tiendas conectadas')

@section('page_header')
    @include('core::components.card', ['title' => 'Tiendas conectadas'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tiendas conectadas</h5>
                        <p class="small mb-0 text-muted">Gestiona las integraciones con plataformas ecommerce</p>
                    </div>
                    <a href="{{ route('remarketing.stores.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Conectar tienda
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                @if($stores->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-store fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No hay tiendas conectadas todavía</p>
                        <a href="{{ route('remarketing.stores.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Conectar primera tienda
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tienda</th>
                                    <th>Plataforma</th>
                                    <th>Estado</th>
                                    <th>Última sincronización</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stores as $store)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $store->name }}</div>
                                            <small class="text-muted">{{ $store->domain }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $platformIcons = [
                                                    'shopify' => 'fab fa-shopify',
                                                    'woocommerce' => 'fab fa-wordpress',
                                                    'prestashop' => 'fas fa-shopping-bag',
                                                    'magento' => 'fas fa-store',
                                                    'bigcommerce' => 'fas fa-cart-plus',
                                                ];
                                                $icon = $platformIcons[$store->platform] ?? 'fas fa-store';
                                            @endphp
                                            <i class="{{ $icon }} me-1"></i>
                                            {{ ucfirst($store->platform) }}
                                        </td>
                                        <td>
                                            @if($store->status === 'active')
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @elseif($store->status === 'syncing')
                                                <span class="badge bg-info-subtle text-info">Sincronizando</span>
                                            @elseif($store->status === 'error')
                                                <span class="badge bg-danger-subtle text-danger">Error</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $store->status }}</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            {{ $store->last_synced_at?->diffForHumans() ?? 'Nunca' }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item btn-sync" data-id="{{ $store->id }}">Sincronizar</button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('remarketing.stores.edit', $store) }}">Editar</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('remarketing.stores.health', $store) }}">
                                                            Configuración DNS
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                                data-id="{{ $store->id }}"
                                                                data-name="{{ $store->name }}"
                                                                data-url="{{ route('remarketing.stores.destroy', $store) }}">
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
                @endif
            </div>

            @if($stores->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $stores->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- Modal de confirmación de eliminación --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar tienda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Estás seguro de eliminar la tienda <strong id="delete-name"></strong>? Se eliminarán todos los datos asociados.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar tienda</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-delete', function () {
        var name = $(this).data('name');
        var url  = $(this).data('url');
        $('#delete-name').text(name);
        $('#deleteForm').attr('action', url);
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-sync', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/stores/' + id + '/sync',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) { toastr.success(res.message || 'Sincronización iniciada'); },
            error: function ()    { toastr.error('Error al iniciar la sincronización'); }
        });
    });


});
</script>
@endpush
