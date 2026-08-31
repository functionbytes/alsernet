@extends('layouts.theme')

@section('title', 'Nueva drip campaign')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva drip campaign'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.drip-campaigns.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva drip campaign</h5>
                        <small class="text-muted">Configura una secuencia automatizada de mensajes</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.drip-campaigns._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar campaña
                        </button>
                        <a href="{{ route('settings.helpdesk.drip-campaigns.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las drip campaigns</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Las drip campaigns envian mensajes automaticos a los clientes en momentos especificos basados en disparadores.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Disparadores disponibles</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-1"><strong>Etiqueta agregada</strong> — se activa al añadir una etiqueta especifica</li>
                        <li class="mb-1"><strong>Conversacion cerrada</strong> — se activa al cerrar una conversacion</li>
                        <li class="mb-1"><strong>CSAT bajo</strong> — se activa cuando la calificacion CSAT es ≤ 2</li>
                        <li class="mb-1"><strong>Inicio manual</strong> — se activa manualmente por un agente</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Pasos</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Cada paso define un mensaje con su tiempo de espera y canal de envio. Los pasos se ejecutan en orden secuencial.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
