@extends('layouts.theme')

@section('title', 'Menus')

@section('content')

    @include('core::components.card', ['title' => 'Gestion de menus'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gestión de menús</h5>
                        <p class="small mb-0 text-muted">Administra los menús de navegación del sitio</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('menu.create') }}" class="btn btn-primary">Nuevo menú</a>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchMenu" placeholder="Buscar menú...">
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicacion</th>
                            <th class="text-center">Items</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr class="search-items">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-bars text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $menu->name }}</h6>
                                            <small class="text-muted">{{ $menu->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($menu->location)
                                        <span class="badge bg-info-subtle text-info">
                                            {{ config("menu.locations.{$menu->location}", $menu->location) }}
                                        </span>
                                    @else
                                        <span class="text-muted fst-italic">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">{{ $menu->all_items_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($menu->status)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown dropstart">
                                        <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('menu.edit', $menu) }}">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </li>
                                            <li class="border-top my-2"></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3 text-danger delete-btn"
                                                   href="javascript:void(0)"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#delete-modal"
                                                   data-url="{{ route('menu.destroy', $menu) }}"
                                                   data-title="Eliminar: {{ $menu->name }}">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-bars fa-3x mb-3 d-block opacity-50"></i>
                                        <p class="mb-1">No se encontraron menus</p>
                                        <a href="{{ route('menu.create') }}" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-plus me-1"></i> Crear menu
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(function () {
    // Client-side search filter
    $('#searchMenu').on('keyup', function () {
        var value = $(this).val().toLowerCase();
        $('.search-items').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Delete modal
    $('.delete-btn').on('click', function () {
        var url = $(this).data('url');
        var title = $(this).data('title');
        $('#delete-modal .modal-title').text(title);
        $('#delete-form').attr('action', url);
    });
});
</script>
@endpush
