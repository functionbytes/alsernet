@extends('layouts.theme')

@section('title', 'Nuevo agente IA')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.ai-agents.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo agente IA</h5>
                        <small class="text-muted">Configura un agente autonomo para atender conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.ai-agents._form', ['aiAgent' => null])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar agente
                        </button>
                        <a href="{{ route('settings.helpdesk.ai-agents.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los agentes IA</h6>
                    <p class="card-text text-muted small">
                        Los agentes IA atienden conversaciones de forma autonoma usando inteligencia artificial, respondiendo preguntas frecuentes y filtrando casos que necesitan atencion humana.
                    </p>
                    <hr>
                    <h6 class="mb-2">Prompt del sistema</h6>
                    <p class="card-text text-muted small">
                        Define la personalidad y las reglas del agente. Incluye informacion sobre tu empresa, los productos que soportas y el tono de comunicacion deseado.
                    </p>
                    <hr>
                    <h6 class="mb-2">Escalacion automatica</h6>
                    <p class="card-text text-muted small">
                        El agente transferira la conversacion a un humano cuando detecte palabras clave de escalacion o supere el limite de mensajes configurado.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
