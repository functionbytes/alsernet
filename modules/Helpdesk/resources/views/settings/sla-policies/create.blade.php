@extends('layouts.theme')

@section('title', 'Nueva politica SLA')

@section('page_header')
    @include('core::components.card', ['title' => 'Nueva politica SLA'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.sla-policies.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva politica SLA</h5>
                        <small class="text-muted">Define los tiempos de respuesta y resolucion para las conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.sla-policies._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar politica SLA
                        </button>
                        <a href="{{ route('settings.helpdesk.sla-policies.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre las politicas SLA</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Un SLA (Service Level Agreement) define los compromisos de tiempo de respuesta y resolucion que el equipo de soporte debe cumplir con los clientes.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Primera respuesta</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Tiempo maximo desde que se crea una conversacion hasta que un agente responde por primera vez.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Resolucion</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Tiempo maximo total para resolver completamente una conversacion desde su creacion.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Escalacion</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Si se habilita, las conversaciones sin resolucion en el tiempo configurado seran marcadas para escalacion automatica.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
