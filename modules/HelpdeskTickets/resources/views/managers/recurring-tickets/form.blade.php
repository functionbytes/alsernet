@extends('layouts.theme')

@php $isEdit = isset($recurringTicket) && $recurringTicket !== null && $recurringTicket->exists; @endphp

@section('title', $isEdit ? 'Editar ticket recurrente' : 'Nuevo ticket recurrente')

@section('content')

    @include('core::components.card', ['title' => $isEdit ? 'Editar ticket recurrente' : 'Nuevo ticket recurrente'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ $isEdit ? route('manager.helpdesk.recurring-tickets.update', $recurringTicket->id) : route('manager.helpdesk.recurring-tickets.store') }}"
                      method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Editar ticket recurrente' : 'Nuevo ticket recurrente' }}</h5>
                        <small class="text-muted">{{ $isEdit ? 'Modifica la configuracion del schedule' : 'Configura un nuevo ticket que se generara automaticamente' }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Informacion basica --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre interno, asunto y descripcion del ticket que se generara</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $recurringTicket->name ?? '') }}"
                                       placeholder="Ej: Revision mensual de servidores"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Asunto <span class="text-danger">*</span></label>
                                <input type="text" name="subject"
                                       class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $recurringTicket->subject ?? '') }}"
                                       placeholder="Asunto que tendra cada ticket generado"
                                       required>
                                @error('subject')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description" rows="4"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Descripcion que tendra cada ticket generado...">{{ old('description', $recurringTicket->description ?? '') }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Clasificacion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Clasificacion</h6>
                        <p class="text-muted small mb-3">Categoria, prioridad y agente responsable del ticket generado</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Categoria</label>
                                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">Sin categoria</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id', $recurringTicket->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Prioridad</label>
                                <select name="priority_id" class="form-select @error('priority_id') is-invalid @enderror">
                                    <option value="">Sin prioridad</option>
                                    @foreach($priorities as $prio)
                                        <option value="{{ $prio->id }}"
                                            {{ old('priority_id', $recurringTicket->priority_id ?? '') == $prio->id ? 'selected' : '' }}>
                                            {{ $prio->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Agente asignado</label>
                                <select name="assignee_id" class="form-select @error('assignee_id') is-invalid @enderror">
                                    <option value="">Sin asignar</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}"
                                            {{ old('assignee_id', $recurringTicket->assignee_id ?? '') == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->firstname . ' ' . $agent->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assignee_id')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Programacion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Programacion</h6>
                        <p class="text-muted small mb-3">Frecuencia con la que se generara automaticamente este ticket</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Frecuencia <span class="text-danger">*</span></label>
                                <select name="frequency" id="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                                    <option value="">Seleccionar frecuencia...</option>
                                    <option value="daily"   {{ old('frequency', $recurringTicket->frequency ?? '') === 'daily'   ? 'selected' : '' }}>Diario — cada dia</option>
                                    <option value="weekly"  {{ old('frequency', $recurringTicket->frequency ?? '') === 'weekly'  ? 'selected' : '' }}>Semanal — cada 7 dias</option>
                                    <option value="monthly" {{ old('frequency', $recurringTicket->frequency ?? '') === 'monthly' ? 'selected' : '' }}>Mensual — cada mes</option>
                                    <option value="custom"  {{ old('frequency', $recurringTicket->frequency ?? '') === 'custom'  ? 'selected' : '' }}>Personalizado — expresion cron</option>
                                </select>
                                @error('frequency')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6" id="cron-expression-group"
                                 @if(old('frequency', $recurringTicket->frequency ?? '') !== 'custom') style="display:none" @endif>
                                <label class="form-label">Expresion cron</label>
                                <input type="text" name="cron_expression"
                                       class="form-control font-monospace @error('cron_expression') is-invalid @enderror"
                                       value="{{ old('cron_expression', $recurringTicket->cron_expression ?? '') }}"
                                       placeholder="*/15 * * * *">
                                <div class="form-text">Formato: minuto hora dia mes dia_semana</div>
                                @error('cron_expression')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Primera ejecucion</label>
                                <input type="datetime-local" name="next_run_at"
                                       class="form-control @error('next_run_at') is-invalid @enderror"
                                       value="{{ old('next_run_at', isset($recurringTicket->next_run_at) ? $recurringTicket->next_run_at->format('Y-m-d\TH:i') : '') }}">
                                <div class="form-text">Dejar en blanco para ejecutar en el proximo ciclo del scheduler</div>
                                @error('next_run_at')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuracion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Activacion del ciclo de ticket recurrente</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $recurringTicket->is_active ?? 1) == 1 ? 'selected' : '' }}>
                                        Activa — se ejecutara segun la programacion
                                    </option>
                                    <option value="0" {{ old('is_active', $recurringTicket->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                        Inactiva — pausada
                                    </option>
                                </select>
                                @error('is_active')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            {{ $isEdit ? 'Guardar cambios' : 'Crear ticket recurrente' }}
                        </button>
                        <a href="{{ route('manager.helpdesk.recurring-tickets.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los tickets recurrentes</h6>
                    <p class="card-text text-muted">
                        Los tickets recurrentes se generan automaticamente segun la frecuencia configurada, eliminando la necesidad de crear tickets manualmente de forma periodica.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que identifiquen claramente el proposito del ticket</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Asigna categoria y prioridad para que los tickets se clasifiquen automaticamente</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa expresion cron solo si necesitas una frecuencia personalizada</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Desactiva el schedule en lugar de eliminarlo si es temporal</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#frequency').on('change', function () {
        $('#cron-expression-group').toggle(this.value === 'custom');
    });
});
</script>
@endpush
