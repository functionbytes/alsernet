@extends('layouts.theme')

@section('title', 'Nueva vista guardada')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva vista guardada'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="viewForm" action="{{ route('manager.helpdesk.settings.ticket-views.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva vista</h5>
                        <small class="text-muted">Guarda un filtro del listado de tickets para reutilizarlo</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion visible de la vista</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Ej: Tickets urgentes sin asignar"
                                           required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Descripcion</label>
                                    <textarea name="description"
                                              class="form-control @error('description') is-invalid @enderror"
                                              rows="2"
                                              placeholder="Para que sirve esta vista">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Filtros</h6>
                        <p class="text-muted small mb-3">Condiciones que se aplican al listado de tickets. Deja en blanco lo que no quieras filtrar</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2" name="filters[status_id]">
                                        <option value="">Cualquiera</option>
                                        @foreach($statuses as $statusOption)
                                            <option value="{{ $statusOption->id }}" {{ old('filters.status_id') == $statusOption->id ? 'selected' : '' }}>
                                                {{ $statusOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select select2" name="filters[category_id]">
                                        <option value="">Cualquiera</option>
                                        @foreach($categories as $categoryOption)
                                            <option value="{{ $categoryOption->id }}" {{ old('filters.category_id') == $categoryOption->id ? 'selected' : '' }}>
                                                {{ $categoryOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select select2" name="filters[priority]">
                                        <option value="">Cualquiera</option>
                                        <option value="urgent" {{ old('filters.priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                        <option value="high" {{ old('filters.priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                        <option value="normal" {{ old('filters.priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                        <option value="low" {{ old('filters.priority') == 'low' ? 'selected' : '' }}>Baja</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Grupo</label>
                                    <select class="form-select select2" name="filters[group_id]">
                                        <option value="">Cualquiera</option>
                                        @foreach($groups as $groupOption)
                                            <option value="{{ $groupOption->id }}" {{ old('filters.group_id') == $groupOption->id ? 'selected' : '' }}>
                                                {{ $groupOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Origen</label>
                                    <select class="form-select select2" name="filters[source]">
                                        <option value="">Cualquiera</option>
                                        <option value="email" {{ old('filters.source') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="widget" {{ old('filters.source') == 'widget' ? 'selected' : '' }}>Widget web</option>
                                        <option value="wa" {{ old('filters.source') == 'wa' ? 'selected' : '' }}>WhatsApp</option>
                                        <option value="fb" {{ old('filters.source') == 'fb' ? 'selected' : '' }}>Facebook</option>
                                        <option value="ig" {{ old('filters.source') == 'ig' ? 'selected' : '' }}>Instagram</option>
                                        <option value="agent" {{ old('filters.source') == 'agent' ? 'selected' : '' }}>Agente</option>
                                        <option value="formulario" {{ old('filters.source') == 'formulario' ? 'selected' : '' }}>Formulario</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Asignacion</label>
                                    <select class="form-select select2" name="filters[assignee_id]">
                                        <option value="">Cualquiera</option>
                                        <option value="me" {{ old('filters.assignee_id') == 'me' ? 'selected' : '' }}>Asignados a mi</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sin asignar</label>
                                    <select class="form-select select2" name="filters[unassigned]">
                                        <option value="">No filtrar</option>
                                        <option value="1" {{ old('filters.unassigned') == '1' ? 'selected' : '' }}>Solo tickets sin asignar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Archivado</label>
                                    <select class="form-select select2" name="filters[is_archived]">
                                        <option value="">No filtrar</option>
                                        <option value="0" {{ old('filters.is_archived') === '0' ? 'selected' : '' }}>Solo activos</option>
                                        <option value="1" {{ old('filters.is_archived') === '1' ? 'selected' : '' }}>Solo archivados</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Orden</h6>
                        <p class="text-muted small mb-3">Como se ordenan los tickets al abrir esta vista</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ordenar por</label>
                                    <select class="form-select select2" name="sort_by">
                                        <option value="">Por defecto</option>
                                        <option value="created_at" {{ old('sort_by') == 'created_at' ? 'selected' : '' }}>Fecha de creacion</option>
                                        <option value="updated_at" {{ old('sort_by') == 'updated_at' ? 'selected' : '' }}>Ultima actualizacion</option>
                                        <option value="priority" {{ old('sort_by') == 'priority' ? 'selected' : '' }}>Prioridad</option>
                                        <option value="sla_resolution_due_at" {{ old('sort_by') == 'sla_resolution_due_at' ? 'selected' : '' }}>Vencimiento de SLA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Direccion</label>
                                    <select class="form-select select2" name="sort_direction">
                                        <option value="desc" {{ old('sort_direction', 'desc') == 'desc' ? 'selected' : '' }}>Descendente</option>
                                        <option value="asc" {{ old('sort_direction', 'desc') == 'asc' ? 'selected' : '' }}>Ascendente</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1">Visibilidad</h6>
                        <p class="text-muted small mb-3">Quien puede ver y usar esta vista</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="is_shared" class="form-label">Compartir con el equipo</label>
                                    <select class="form-select select2 @error('is_shared') is-invalid @enderror" id="is_shared" name="is_shared">
                                        <option value="0" {{ old('is_shared', '0') == '0' ? 'selected' : '' }}>No — solo yo la veo</option>
                                        <option value="1" {{ old('is_shared', '0') == '1' ? 'selected' : '' }}>Si — visible para todo el equipo</option>
                                    </select>
                                    @error('is_shared')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="is_default" class="form-label">Vista por defecto</label>
                                    <select class="form-select select2 @error('is_default') is-invalid @enderror" id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', '0') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_default', '0') == '1' ? 'selected' : '' }}>Si — se abre al entrar al listado</option>
                                    </select>
                                    @error('is_default')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar vista</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-views.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las vistas guardadas</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Una vista guardada es un filtro reutilizable del listado de tickets. Deja un filtro en blanco para no aplicarlo.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Comparte solo las vistas realmente utiles para todo el equipo</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Solo una vista puede ser tu vista por defecto</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> Combina varios filtros para acotar mejor el resultado</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // Los selects de filtros vacios no deben enviarse (evita filtrar por '').
    $('#viewForm').on('submit', function () {
        $(this).find('select[name^="filters["]').each(function () {
            if ($(this).val() === '') {
                $(this).prop('disabled', true);
            }
        });
    });
});
</script>
@endpush
