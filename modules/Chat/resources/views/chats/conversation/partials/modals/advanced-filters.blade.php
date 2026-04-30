{{-- Variables: $teams, $labels --}}
<div class="modal fade" id="advancedFiltersModal" tabindex="-1" aria-labelledby="advancedFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('chat.conversations.index') }}" id="advanced-search-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="advancedFiltersModalLabel">
                        <i class="fas fa-filter me-2"></i>Filtros avanzados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Busqueda de texto -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Buscar texto</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Contacto, mensaje...">
                    </div>

                    <!-- Filtro de equipo -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Equipo</label>
                        <select class="form-control select2" name="team">
                            <option value="">Todos los equipos</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ request('team') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro de etiquetas -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Etiquetas</label>
                        <select class="form-control select2" name="labels">
                            <option value="">Todas las etiquetas</option>
                            @foreach($labels as $label)
                                <option value="{{ $label->slug }}" {{ request('labels') == $label->slug ? 'selected' : '' }}>
                                    {{ $label->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rango de fecha de creacion -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha de creacion</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" placeholder="Desde">
                                <small class="text-muted">Desde</small>
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" placeholder="Hasta">
                                <small class="text-muted">Hasta</small>
                            </div>
                        </div>
                    </div>

                    <!-- Rango de ultima actividad -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ultima actividad</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control" name="last_activity_from" value="{{ request('last_activity_from') }}" placeholder="Desde">
                                <small class="text-muted">Desde</small>
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="last_activity_to" value="{{ request('last_activity_to') }}" placeholder="Hasta">
                                <small class="text-muted">Hasta</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('chat.conversations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle me-1"></i> Limpiar filtros
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Aplicar filtros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
