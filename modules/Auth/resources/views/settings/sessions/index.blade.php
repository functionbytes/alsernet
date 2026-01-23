@extends('layouts.theme')

@section('title', 'Sesiones Activas')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Sesiones activas</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Gestiona las sesiones activas de tu cuenta.</p>

            @if (count($sessions) === 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay sesiones activas para mostrar.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dispositivo</th>
                                <th>IP</th>
                                <th>Última Actividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- TODO: Display sessions --}}
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
