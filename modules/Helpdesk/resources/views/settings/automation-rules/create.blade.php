@extends('layouts.theme')

@section('title', 'Nueva regla de automatizacion')


@section('page_header')
    @include('core::components.card', ['title' => 'Nueva regla de automatizacion'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.rules.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva regla</h5>
                        <small class="text-muted">Define cuando y que acciones se ejecutan automaticamente</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.automation-rules._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar regla
                        </button>
                        <a href="{{ route('settings.helpdesk.rules.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las automatizaciones</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Las reglas de automatizacion permiten ejecutar acciones automaticamente cuando ocurren eventos en las conversaciones, sin necesidad de intervencion manual.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Como funcionan</h6>
                </div>
                <div class="card-body">
                    <ol class="small text-muted ps-3">
                        <li class="mb-1">Selecciona el <strong>evento</strong> que dispara la regla</li>
                        <li class="mb-1">Define <strong>condiciones</strong> opcionales para filtrar cuando aplicar</li>
                        <li class="mb-1">Agrega las <strong>acciones</strong> a ejecutar automaticamente</li>
                    </ol>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Solo una vez</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Si activas "Solo una vez por conversacion", la regla no volvera a ejecutarse para la misma conversacion aunque el evento ocurra de nuevo.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Orden de ejecucion</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Cuando hay multiples reglas para el mismo evento, se ejecutan en orden ascendente segun el numero de orden definido.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
