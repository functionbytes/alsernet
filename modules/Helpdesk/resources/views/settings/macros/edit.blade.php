@extends('layouts.theme')

@section('title', 'Editar macro')


@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.macros.update', $macro) }}" method="POST" id="macro-form">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar macro</h5>
                        <small class="text-muted">Modifica las acciones y configuracion de este macro</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.macros._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.macros.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los macros</h6>
                    <p class="card-text text-muted small">
                        Los macros permiten a los agentes ejecutar multiples acciones sobre una conversacion con un solo clic, ahorrando tiempo en tareas repetitivas.
                    </p>
                    <hr>
                    <h6 class="mb-2">Estadisticas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="small text-muted mb-1">
                            <i class="fas fa-bolt me-1"></i>
                            Ejecutado <strong>{{ number_format($macro->usage_count) }}</strong>
                            {{ Str::plural('vez', $macro->usage_count) }}
                        </li>
                        <li class="small text-muted mb-1">
                            <i class="fas fa-calendar me-1"></i>
                            Creado {{ $macro->created_at->diffForHumans() }}
                        </li>
                        <li class="small text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Actualizado {{ $macro->updated_at->diffForHumans() }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
