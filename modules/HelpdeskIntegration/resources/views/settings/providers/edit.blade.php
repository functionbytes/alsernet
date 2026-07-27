@extends('layouts.theme')

@section('title', 'Editar proveedor de integración')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar proveedor de integración'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdeskintegration.providers.update', $provider) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $provider->label }}</h5>
                        <small class="text-muted">
                            @if($provider->isNative())
                                Proveedor nativo (driver: {{ $provider->driver }}) — el identificador no se puede cambiar
                            @else
                                Proveedor custom
                            @endif
                        </small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdeskintegration::settings.providers._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdeskintegration.providers.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
