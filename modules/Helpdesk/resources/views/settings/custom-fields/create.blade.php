@extends('layouts.theme')

@section('title', 'Nuevo campo personalizado')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo campo personalizado'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.custom-fields.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo campo personalizado</h5>
                        <small class="text-muted">Define un atributo adicional para clientes o conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.custom-fields._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar campo
                        </button>
                        <a href="{{ route('settings.helpdesk.custom-fields.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre los campos personalizados</h6>
                    <p class="card-text text-muted small">
                        Los campos personalizados permiten extender la informacion de clientes y conversaciones con atributos propios de tu negocio.
                    </p>
                    <hr>
                    <h6 class="mb-2">Clave automatica</h6>
                    <p class="card-text text-muted small">
                        La clave se genera automaticamente desde la etiqueta. Usa letras minusculas y guiones bajos.
                        Ejemplo: "Numero de cuenta" → <code>numero_de_cuenta</code>
                    </p>
                    <hr>
                    <h6 class="mb-2">Tipos disponibles</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li><strong>Texto</strong> — campo de texto libre</li>
                        <li><strong>Numero</strong> — solo valores numericos</li>
                        <li><strong>Fecha</strong> — selector de fecha</li>
                        <li><strong>Si/No</strong> — valor booleano</li>
                        <li><strong>Lista</strong> — seleccion unica de opciones</li>
                        <li><strong>Lista multiple</strong> — seleccion multiple</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
