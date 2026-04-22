@extends('layouts.theme')

@section('title', 'Nuevo flujo de IA')

@section('content')

    @include('core::components.card', ['title' => 'Nuevo flujo de IA'])

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk.ai.flows.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo flujo</h5>
                        <small class="text-muted">Define el flujo de automatizacion del agente IA</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Nombre y descripcion del flujo de IA</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Saludo inicial, Informacion de productos..."
                                       required autofocus>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion</label>
                                <textarea name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Describe el proposito de este flujo...">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Trigger</h6>
                        <p class="text-muted small mb-3">Evento que activa la ejecucion de este flujo</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Activador <span class="text-danger">*</span></label>
                                <select name="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror" required>
                                    <option value="">Seleccionar activador...</option>
                                    <option value="message" {{ old('trigger_type') === 'message' ? 'selected' : '' }}>
                                        Mensaje — se activa al recibir un mensaje especifico
                                    </option>
                                    <option value="intent" {{ old('trigger_type') === 'intent' ? 'selected' : '' }}>
                                        Intencion — se activa al detectar una intencion del usuario
                                    </option>
                                    <option value="keyword" {{ old('trigger_type') === 'keyword' ? 'selected' : '' }}>
                                        Palabra clave — se activa cuando se menciona una palabra clave
                                    </option>
                                    <option value="conversation_start" {{ old('trigger_type') === 'conversation_start' ? 'selected' : '' }}>
                                        Inicio de conversacion — se activa al comenzar una nueva sesion
                                    </option>
                                </select>
                                @error('trigger_type')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear flujo</button>
                        <a href="{{ route('manager.helpdesk.ai.flows.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los flujos de IA</h6>
                    <p class="card-text text-muted">
                        Un flujo es una secuencia de pasos que el agente IA sigue para responder a los clientes de forma automatizada.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de nodos</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Entrada — inicia el flujo</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Prompt — envia una instruccion al agente</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Condicion — ramifica segun la respuesta</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Accion — realiza tareas como guardar datos o enviar emails</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Salida — finaliza el flujo</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa nombres descriptivos que reflejen el objetivo del flujo</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Elige el trigger que mejor se adapte al contexto de activacion</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Despues de crear el flujo disenha los nodos en el editor visual</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
