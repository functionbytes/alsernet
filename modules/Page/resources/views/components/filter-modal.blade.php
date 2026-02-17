<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="fas fa-filter"></i> Filtros de búsqueda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="GET" action="{{ route('pages.index') }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="search" class="form-label">
                                <i class="fas fa-search"></i> Buscar
                            </label>
                            <input type="text" class="form-control" id="search" name="search"
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Buscar por título, contenido o slug...">
                            <small class="form-text text-muted">Busca en el título, contenido y slug de las páginas</small>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                <i class="fas fa-circle-check"></i> Estado
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos los estados</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="template" class="form-label">
                                <i class="fas fa-file-code"></i> Plantilla
                            </label>
                            <select class="form-select" id="template" name="template">
                                <option value="">Todas las plantillas</option>
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['template'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('pages.index') }}" class="btn btn-light">
                        <i class="fas fa-times"></i> Limpiar filtros
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background-color: #90bb13; border-color: #90bb13;">
                        <i class="fas fa-filter"></i> Aplicar filtros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
