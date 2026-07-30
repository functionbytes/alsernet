@extends('layouts.theme')

@section('title', 'Configuracion de agente')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuracion de agente'])
@endsection

@section('content')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.agent-settings.update', $agent) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuracion de agente</h5>
                        <small class="text-muted">{{ $agent->name }} &mdash; {{ $agent->email }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.agent-settings._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.agent-settings.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del agente</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Nombre</span>
                        <span class="small fw-semibold">{{ $agent->name }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Email</span>
                        <span class="small">{{ $agent->email }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Rol</span>
                        <span>
                            @foreach($agent->roles as $role)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $role->name }}</span>
                            @endforeach
                        </span>
                    </div>
                    @if($agentSettings->exists)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Ultima actualizacion</span>
                            <span class="small">{{ $agentSettings->updated_at->diffForHumans() }}</span>
                        </div>
                    @else
                        <p class="text-muted small mb-0">No hay configuracion guardada aun. Se usaran los valores por defecto.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

@endsection
