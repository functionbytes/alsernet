@extends('layouts.theme')

@section('title', 'Nueva regla de enrutamiento')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva regla de enrutamiento'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.routing-rules.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva regla de enrutamiento</h5>
                        <small class="text-muted">Define la palabra clave y el destino de asignacion automatica</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Condicion de disparo</h6>
                        <p class="text-muted small mb-3">Palabra clave que activara esta regla en los mensajes entrantes</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">Palabra clave <span class="text-danger">*</span></label>
                                    <input type="text" name="keyword"
                                           class="form-control @error('keyword') is-invalid @enderror"
                                           value="{{ old('keyword') }}"
                                           placeholder="Ej: urgente"
                                           required>
                                    @error('keyword')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de coincidencia <span class="text-danger">*</span></label>
                                    <select name="match_type" class="form-select @error('match_type') is-invalid @enderror" required>
                                        @foreach(\Modules\Helpdesk\Models\RoutingRule::MATCH_TYPES as $value => $label)
                                            <option value="{{ $value }}" {{ old('match_type', 'contains') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('match_type')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Asignacion</h6>
                        <p class="text-muted small mb-3">Agente o equipo al que se asignara la conversacion cuando se cumpla la regla</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Asignar a agente</label>
                                    <select name="assign_to_user_id" class="form-select @error('assign_to_user_id') is-invalid @enderror">
                                        <option value="">Sin asignacion especifica</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ old('assign_to_user_id') == $agent->id ? 'selected' : '' }}>
                                                {{ trim($agent->firstname . ' ' . $agent->lastname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assign_to_user_id')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Prioridad y estado de la regla</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Prioridad</label>
                                    <input type="number" name="priority"
                                           class="form-control @error('priority') is-invalid @enderror"
                                           value="{{ old('priority', 0) }}"
                                           min="0" max="9999">
                                    <small class="form-text text-muted">Menor numero = mayor prioridad</small>
                                    @error('priority')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active', '1') === '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar regla</button>
                        <a href="{{ route('settings.helpdesk.routing-rules.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las reglas de enrutamiento</h6>
                    <p class="card-text text-muted">
                        Cuando un cliente envia un mensaje que coincide con la palabra clave, la conversacion se asigna automaticamente al agente o equipo configurado.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de coincidencia</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><span class="fw-semibold">Contiene:</span> la palabra aparece en cualquier parte del mensaje</li>
                        <li class="mb-2 text-muted small"><span class="fw-semibold">Exacta:</span> el mensaje es exactamente igual a la palabra clave</li>
                        <li class="text-muted small"><span class="fw-semibold">Regex:</span> patron de expresion regular avanzado</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
