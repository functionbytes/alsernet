@extends('layouts.theme')

@section('title', 'Ubicaciones de tienda')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Ubicaciones'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Ubicaciones de tienda</h5>
                        <p class="small mb-0 text-muted">Administra las ubicaciones fisicas</p>
                    </div>
                    <a href="{{ route('ecommerce.store-locators.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva ubicacion
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($storeLocators->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Direccion</th>
                                    <th>Telefono</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($storeLocators as $storeLocator)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.store-locators.edit', $storeLocator) }}" class="text-decoration-none fw-semibold">{{ $storeLocator->name }}</a>
                                            @if($storeLocator->is_primary)<span class="badge bg-warning ms-1">Principal</span>@endif
                                        </td>
                                        <td><small class="text-muted">{{ Str::limit($storeLocator->address, 40) ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $storeLocator->phone ?? '—' }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('ecommerce.store-locators.edit', $storeLocator) }}" class="dropdown-item">Editar</a>
                                                    </li>
                                                    @if(!$storeLocator->is_primary)
                                                        <li>
                                                            <button type="button" class="dropdown-item btn-set-primary"
                                                                data-url="{{ route('ecommerce.store-locators.set-primary', $storeLocator) }}">
                                                                Marcar como principal
                                                            </button>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <form action="{{ route('ecommerce.store-locators.destroy', $storeLocator) }}" method="POST" onsubmit="return confirm('Eliminar esta ubicacion?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item">Eliminar</button>
                                                        </form>
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
                        <i class="fas fa-map-marker-alt fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay ubicaciones</h5>
                        <a href="{{ route('ecommerce.store-locators.create') }}" class="btn btn-primary">Crear primera ubicacion</a>
                    </div>
                @endif
            </div>

            @if($storeLocators->hasPages())
                <div class="card-footer">{{ $storeLocators->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-set-primary', function () {
        var btn = $(this);

        $.ajax({
            url: btn.data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                location.reload();
            },
            error: function () {
                toastr.error('No se pudo marcar como principal');
            }
        });
    });
});
</script>
@endpush
