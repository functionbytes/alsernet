@extends('layouts.theme')

@section('title', 'Nuevo proveedor de integración')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo proveedor de integración'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdeskintegration.providers.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo proveedor</h5>
                        <small class="text-muted">Añade una plataforma custom al catálogo de integraciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdeskintegration::settings.providers._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar proveedor
                        </button>
                        <a href="{{ route('settings.helpdeskintegration.providers.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Proveedores custom</h6>
                    <p class="small text-muted mb-0">
                        Un proveedor custom queda registrado en el catálogo (visible en el modal de integraciones del
                        cliente) pero no tiene lógica de búsqueda propia — sirve para documentar y guardar credenciales
                        de una integración externa que se gestiona de otra forma.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
