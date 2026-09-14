@extends('layouts.theme')

@section('title', 'Dispositivos Autorizados')

@section('page_header')
    @include('core::components.card', ['title' => 'Dispositivos Autorizados'])
@endsection

@section('content')
<div class="px-3">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Dispositivos autorizados</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Gestiona los dispositivos que tienen acceso a tu cuenta.</p>

            @if (count($devices) === 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay dispositivos registrados para mostrar.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dispositivo</th>
                                <th>Navegador</th>
                                <th>Último Acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- TODO: Display devices --}}
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
