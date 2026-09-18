@extends('layouts.theme')

@section('title', 'Nuevo canal de correo')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo canal de correo'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="channelForm" action="{{ route('manager.helpdesk.settings.email-channels.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo canal de correo</h5>
                        <small class="text-muted">Conecta un buzon para que sus correos entrantes generen tickets</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        @include('helpdesktickets::managers.settings.email-channels._form', ['channel' => null])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar canal</button>
                        <a href="{{ route('manager.helpdesk.settings.email-channels.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-12 col-lg-4">
            @include('helpdesktickets::managers.settings.email-channels._help')
        </div>

    </div>

@endsection
