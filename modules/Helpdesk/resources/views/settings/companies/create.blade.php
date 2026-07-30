@extends('layouts.theme')

@section('title', 'Nueva empresa')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.companies.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva empresa</h5>
                        <small class="text-muted">Registra una nueva empresa para organizar a tus clientes</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.companies._form', ['company' => null])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar empresa
                        </button>
                        <a href="{{ route('settings.helpdesk.companies.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las empresas</h6>
                    <p class="card-text text-muted small">
                        Las empresas te permiten agrupar clientes bajo una misma organizacion y hacer seguimiento del estado de la relacion comercial.
                    </p>
                    <hr>
                    <h6 class="mb-2">Dominio automatico</h6>
                    <p class="card-text text-muted small">
                        Si configuras un dominio de correo (ej: <code>acme.com</code>), los nuevos clientes con ese dominio se asociaran automaticamente a esta empresa.
                    </p>
                    <hr>
                    <h6 class="mb-2">Health score</h6>
                    <p class="card-text text-muted small">
                        El health score (0-100) indica la salud de la relacion. <strong>80+</strong> es saludable, <strong>50-79</strong> es regular y <strong>menos de 50</strong> esta en riesgo.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
