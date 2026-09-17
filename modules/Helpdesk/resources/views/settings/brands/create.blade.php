@extends('layouts.theme')

@section('title', 'Nueva marca')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva marca'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.brands.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva marca</h5>
                        <small class="text-muted">Configura la identidad visual y los datos de correo para esta marca</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.brands._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar marca
                        </button>
                        <a href="{{ route('settings.helpdesk.brands.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las marcas</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Las marcas permiten segmentar el helpdesk por empresa o producto, cada una con su propia identidad visual y configuracion de correo.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Widget token</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Al crear la marca se genera automaticamente un token unico para integrar el widget de chat en cualquier sitio web.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Dominio</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Si configuras un dominio, el sistema puede detectar automaticamente la marca segun desde donde lleguen las solicitudes.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
