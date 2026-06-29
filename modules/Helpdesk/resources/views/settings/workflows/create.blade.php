@extends('layouts.theme')

@section('title', 'Nuevo workflow')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.workflows.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo workflow</h5>
                        <small class="text-muted">Configura una automatizacion basada en eventos de conversacion</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.workflows._form', ['workflow' => null])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar workflow
                        </button>
                        <a href="{{ route('settings.helpdesk.workflows.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los workflows</h6>
                    <p class="card-text text-muted small">
                        Los workflows automatizan acciones en respuesta a eventos del sistema, como la creacion de una conversacion o la recepcion de un mensaje.
                    </p>
                    <hr>
                    <h6 class="mb-2">Triggers disponibles</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach(\Modules\Helpdesk\Models\Workflow::TRIGGER_TYPES as $key => $label)
                            <li class="mb-1">
                                <small class="text-muted"><code>{{ $key }}</code> — {{ $label }}</small>
                            </li>
                        @endforeach
                    </ul>
                    <hr>
                    <h6 class="mb-2">Acciones disponibles</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach(\Modules\Helpdesk\Models\Workflow::ACTION_TYPES as $key => $label)
                            <li class="mb-1">
                                <small class="text-muted"><code>{{ $key }}</code> — {{ $label }}</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
