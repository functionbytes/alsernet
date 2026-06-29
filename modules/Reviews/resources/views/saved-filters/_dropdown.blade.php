<div class="btn-group">
    <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        Filtros
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">Filtros guardados</h6></li>
        <div id="saved-filters-list"><li class="px-3 py-1"><small class="text-muted">Cargando...</small></li></div>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="#" id="btn-save-current-filter">
                Guardar filtros actuales
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('reviews.saved-filters.index') }}">
                Gestionar filtros
            </a>
        </li>
    </ul>
</div>
