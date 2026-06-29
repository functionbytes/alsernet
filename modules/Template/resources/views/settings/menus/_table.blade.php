@php $enableBulk = $enableBulk ?? false; @endphp

@if($items->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 search-table">
            <thead class="table-light">
                <tr>
                    @if($enableBulk)
                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                    @endif
                    <th>Nombre</th>
                    <th>Ubicacion</th>
                    <th class="text-center">Items</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $menu)
                    <tr class="search-items">
                        @if($enableBulk)
                            <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $menu->id }}"></td>
                        @endif
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <h6 class="mb-0 fw-semibold">{{ $menu->name }}</h6>
                                    <small class="text-muted">{{ $menu->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($menu->location)
                                <span class="badge bg-info-subtle text-info">
                                    {{ config("template.menu.locations.{$menu->location}", $menu->location) }}
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
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                    <i class="fa fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.menus.edit', $menu) }}">Editar</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item delete-btn"
                                           href="javascript:void(0)"
                                           data-bs-toggle="modal"
                                           data-bs-target="#delete-modal"
                                           data-url="{{ route('settings.menus.destroy', $menu) }}"
                                           data-title="Eliminar: {{ $menu->name }}">Eliminar</a>
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
            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                <i class="fas fa-bars fs-7"></i>
            </div>
            <h6 class="mb-1">
                @if(request('search') || request('status'))
                    No se encontraron menús
                @else
                    No hay menús registrados
                @endif
            </h6>
            <p class="text-muted mb-3">
                @if(request('search') || request('status'))
                    No hay resultados para los criterios de búsqueda
                @else
                    Crea un menú para organizar la navegación del sitio
                @endif
            </p>
            @if(!request('search') && !request('status'))
                <a href="{{ route('settings.menus.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Nuevo menú
                </a>
            @endif
        </div>
    </div>
@endif
