@extends('layouts.theme')

@section('title', 'Nuevo Flujo - Agente IA')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-3">Crear nuevo flujo</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manager.chats.ai-agent.flows.index') }}">Flujos</a></li>
                    <li class="breadcrumb-item active">Crear</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('manager.chats.ai-agent.flows.store') }}">
                        @csrf

                        <div class="mb-3">
            <label class="form-label fw-semibold">Nombre del flujo</label>
                            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                placeholder="Ej: Saludo inicial, Información de productos, etc."
                                value="{{ old('name') }}" required autofocus>
                            <small class="text-muted d-block mt-2">Nombre único que identifique este flujo de conversación</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción (opcional)</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                placeholder="Describe el propósito de este flujo..." rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Trigger (activador)</label>
                            <p class="text-muted small mb-3">¿Cuándo se debe activar este flujo?</p>

                            <div class="row">
                                @foreach ($triggers as $key => $label)
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input type="radio" name="trigger" id="trigger_{{ $key }}" value="{{ $key }}"
                                                class="form-check-input" {{ old('trigger') === $key ? 'checked' : '' }}>
                                            <label class="form-check-label" for="trigger_{{ $key }}">
                                                <strong>{{ $label }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    @switch($key)
                                                        @case('message')
                                                            Se activa cuando llega un mensaje específico
                                                        @break
                                                        @case('intent')
                                                            Se activa cuando se detecta una intención
                                                        @break
                                                        @case('keyword')
                                                            Se activa cuando se menciona una palabra clave
                                                        @break
                                                        @case('conversation_start')
                                                            Se activa al iniciar una conversación
                                                        @break
                                                    @endswitch
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error('trigger')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-check"></i> Crear flujo
                            </button>
                            <a href="{{ route('manager.chats.ai-agent.flows.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">ℹ️ ¿Qué es un flujo?</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Un flujo es una secuencia de pasos que el agente IA sigue para responder a los clientes:</p>
                    <ul>
                        <li><strong>Nodo de entrada:</strong> Inicia el flujo</li>
                        <li><strong>Nodo de prompt:</strong> Envía una pregunta o instrucción al agente</li>
                        <li><strong>Nodo de condición:</strong> Toma decisiones basadas en respuestas</li>
                        <li><strong>Nodo de acción:</strong> Realiza acciones (guardar datos, enviar emails, etc.)</li>
                        <li><strong>Nodo de salida:</strong> Finaliza el flujo</li>
                    </ul>
                    <p class="text-muted small mt-3">Después de crear el flujo, podrás diseñar los nodos de forma visual.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
