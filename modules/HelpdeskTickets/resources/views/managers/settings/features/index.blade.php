@extends('layouts.theme')

@section('title', 'Funcionalidades de tickets')

@section('page_header')
    @include('core::components.card', ['title' => 'Funcionalidades de tickets'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpdesk.settings.tickets.features.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Funcionalidades de la vista de ticket</h5>
                        <small class="text-muted">Habilita o deshabilita los botones y secciones disponibles al trabajar un ticket</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        @foreach($sections as $sectionKey => $section)

                        <h6 class="fw-semibold mb-1">{{ $section['title'] }}</h6>
                        <p class="text-muted small mb-3">{{ $section['desc'] }}</p>
                        <div class="row g-3 {{ !$loop->last ? 'mb-4' : '' }}">
                            @foreach($section['items'] as $slug => $label)
                                @include('helpdesktickets::managers.settings.features._toggle', [
                                    'field' => "feature_{$slug}_enabled",
                                    'label' => $label,
                                ])
                            @endforeach
                        </div>

                        @endforeach
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Estas opciones controlan qué ve un agente al abrir un ticket: la barra de redacción, las
                        acciones del panel derecho, la gestión/asignación y las pestañas del detalle.
                    </p>
                    <p class="card-text text-muted mb-0">
                        Es la contraparte, para Tickets, de <a href="{{ route('settings.helpdesk.features.index') }}">Funcionalidades de conversaciones</a>.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Desactiva solo lo que el equipo no use para simplificar la interfaz</li>
                        <li class="mb-2">"Respuesta"/"Enviar" y la pestaña "Hilo" no se pueden apagar: son la base de la pantalla</li>
                        <li class="mb-0">Los cambios aplican a todos los agentes de inmediato</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
