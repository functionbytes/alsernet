# Modal Patterns

## 1. Filter Modal (advanced filters)

```blade
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('resource.index') }}" id="filterModalForm">
                {{-- Preserve search --}}
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select name="category_id" class="form-select select2" data-dropdown-parent="#filterModal">
                            <option value="">Todas</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rango de fechas</label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            <span class="text-muted">—</span>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                    <a href="{{ route('resource.index') }}" class="btn btn-secondary w-100">Limpiar filtros</a>
                </div>
            </form>
        </div>
    </div>
</div>
```

**Clave**: `modal-dialog-centered`, `modal-footer flex-column`, botones `w-100` stacked, primary con `mb-2`.

## 2. Bulk Action Modal

```blade
{{-- Floating bulk toolbar --}}
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
                <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> registro(s)</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Accion</label>
                    <select id="bulk-action-select" class="form-select">
                        <option value="">Seleccionar accion...</option>
                        <option value="delete">Eliminar</option>
                        <option value="activate">Activar</option>
                        <option value="deactivate">Desactivar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer flex-column">
                <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
```

## 3. Delete Confirmation Modal

**SIEMPRE usar el componente compartido**: `@include('core::components.delete')`

Si necesitas variantes, copia el patron:
```blade
<div id="delete-modal" class="modal fade">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="delete-form" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-success mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="my-0">¿Estas seguro de eliminar esto?</h4>
                    <p>Esta accion no se puede deshacer.</p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Confirmar eliminacion</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
```

## Reglas de Modales (SIEMPRE)

1. **`modal-dialog-centered`** en el dialog (nunca sin centered)
2. **`modal-footer flex-column`** para botones apilados
3. **Botones `w-100`** siempre
4. **Primary `mb-2`** arriba, secondary abajo
5. **Select2 en modal**: usar `data-dropdown-parent="#modalId"` o `dropdownParent` en JS
6. **NUNCA** poner `text-danger` en Eliminar (es un color, no un patron de accion)
7. **Header**: titulo + `btn-close` a la derecha
8. **Close**: `data-bs-dismiss="modal"` (no JS manual)
9. **Centered content** en delete: icon + title + description + grid de botones
