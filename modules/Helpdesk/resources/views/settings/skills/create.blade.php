@extends('layouts.theme')

@section('title', 'Nuevo skill')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo skill'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.skills.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo skill</h5>
                        <small class="text-muted">Define una habilidad que podra asignarse a los agentes</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.skills._form', ['skill' => null])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar skill
                        </button>
                        <a href="{{ route('settings.helpdesk.skills.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los skills</h6>
                    <p class="card-text text-muted small">
                        Los skills representan habilidades o especialidades que pueden asignarse a los agentes del helpdesk.
                    </p>
                    <hr>
                    <h6 class="mb-2">Enrutamiento automatico</h6>
                    <p class="card-text text-muted small">
                        Las conversaciones pueden enrutarse automaticamente a agentes segun los skills requeridos para resolver el caso.
                    </p>
                    <hr>
                    <h6 class="mb-2">Slug</h6>
                    <p class="card-text text-muted small">
                        El slug se genera automaticamente desde el nombre y se usa como identificador interno.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
