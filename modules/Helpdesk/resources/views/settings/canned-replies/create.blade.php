@extends('layouts.theme')

@section('title', 'Nueva respuesta predefinida')


@section('page_header')
    @include('core::components.card', ['title' => 'Nueva respuesta predefinida'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.canned-replies.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva respuesta predefinida</h5>
                        <small class="text-muted">Crea un texto de respuesta rapida para usar en conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.canned-replies._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar respuesta
                        </button>
                        <a href="{{ route('settings.helpdesk.canned-replies.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las respuestas predefinidas</h6>
                    <p class="card-text text-muted small">
                        Las respuestas predefinidas permiten a los agentes insertar textos habituales con un solo clic o escribiendo el atajo configurado.
                    </p>
                    <hr>
                    <h6 class="mb-2">Atajos de teclado</h6>
                    <p class="card-text text-muted small">
                        Si configuras un atajo, los agentes podran escribir <code>/atajo</code> en el campo de respuesta para insertar el contenido automaticamente.
                    </p>
                    <hr>
                    <h6 class="mb-2">Alcance</h6>
                    <p class="card-text text-muted small">
                        Las respuestas <strong>globales</strong> estan disponibles para todos los agentes. Las <strong>personales</strong> solo son visibles para quien las crea.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
