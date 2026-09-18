@extends('layouts.theme')

@section('title', 'Nuevo macro')

@push('styles')
<style>.hd-icon-dot { font-size: 0.5rem; }</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo macro'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.macros.store') }}" method="POST" id="macro-form">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo macro</h5>
                        <small class="text-muted">Define un conjunto de acciones predefinidas para ejecutar en conversaciones</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.macros._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar macro
                        </button>
                        <a href="{{ route('settings.helpdesk.macros.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre los macros</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Los macros permiten a los agentes ejecutar multiples acciones sobre una conversacion con un solo clic, ahorrando tiempo en tareas repetitivas.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Tipos de acciones disponibles</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        @foreach($actionTypes as $key => $label)
                            <li class="small text-muted mb-1">
                                <i class="fas fa-circle-dot hd-icon-dot me-1 text-primary"></i>
                                {{ $label }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Visibilidad</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        Los macros <strong>globales</strong> son visibles para todos los agentes. Los macros <strong>personales</strong> solo los ve el agente que los crea.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
