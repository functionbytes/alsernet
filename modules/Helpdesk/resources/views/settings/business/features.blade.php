@extends('layouts.theme')

@section('title', 'Panel de administración')

@section('page_header')
    @include('core::components.card', ['title' => 'Panel de administración'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">

        <div class="col-12 col-lg-8">
            @include('helpdesk::settings.business._features-panel')
        </div>

        {{-- Instrucciones --}}
        <div class="col-12 col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Como encajan entre si</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Horarios de atencion manda sobre las otras dos</h6>
                    <p class="text-muted mb-3">
                        Es la que decide si el negocio esta abierto en este momento, y de esa respuesta dependen
                        Bienvenida y Fuera de horario. Con los horarios <strong>desactivados</strong> el sistema se
                        considera siempre abierto: la bienvenida se enviaria siempre y el mensaje de fuera de
                        horario no se enviaria nunca, aunque su interruptor este encendido.
                    </p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Bienvenida y Fuera de horario se excluyen</h6>
                    <p class="text-muted mb-3">
                        Ante una conversacion nueva se envia una u otra, nunca las dos: la bienvenida dentro del
                        horario, el mensaje de fuera de horario cuando esta cerrado.
                    </p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Despedida va aparte</h6>
                    <p class="text-muted mb-0">
                        No depende del horario. Se envia al cerrar una conversacion, a la hora que sea.
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-1 fw-semibold">Apagar no borra</h6>
                    <p class="text-muted mb-0">
                        Desactivar una funcion solo deja de enviar: los mensajes que tengas escritos siguen
                        guardados y vuelven a usarse tal cual al reactivarla.
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-1 fw-semibold">Configurar cada funcion</h6>
                    <p class="text-muted mb-2">
                        Este panel solo enciende y apaga. Los horarios y el texto de cada mensaje se editan en su
                        propia pagina.
                    </p>
                    <a href="{{ route('settings.helpdesk.business.hours') }}" class="btn btn-secondary w-100 mb-2">Horarios de atencion</a>
                    <a href="{{ route('settings.helpdesk.business.off-hours') }}" class="btn btn-secondary w-100 mb-2">Fuera de horario</a>
                    <a href="{{ route('settings.helpdesk.business.greeting') }}" class="btn btn-secondary w-100 mb-2">Bienvenida</a>
                    <a href="{{ route('settings.helpdesk.business.farewell') }}" class="btn btn-secondary w-100">Despedida</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Consejos de uso</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0 bf-tips">
                        <li class="mb-2">Si activas <strong>Fuera de horario</strong>, activa tambien los horarios: sin ellos no llega a dispararse.</li>
                        <li class="mb-2">Deja al menos un mensaje activo en cada funcion que enciendas, o no habra nada que enviar.</li>
                        <li class="mb-2">Un mensaje con idioma propio se envia tal cual; el marcado como "Automatico" se traduce al idioma del cliente.</li>
                        <li class="mb-0">Para dejar de responder solo un rato, apaga aqui la funcion en vez de borrar los mensajes.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('styles')
<style>
    .bf-tips {
        list-style: disc;
        padding-left: 1.1rem;
    }
</style>
@endpush

@push('scripts')
<script>
window.HdSettingsPageConfig = {
    flashSuccess: @json(session('success')),
    flashError: @json(session('error')),
};
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/settings-standard-bootstrap.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-standard-bootstrap.js')) }}" defer></script>
@endpush
