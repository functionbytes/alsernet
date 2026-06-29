@extends('layouts.theme')

@section('title', 'Integraciones del helpdesk')

@section('content')

@include('core::components.alerts')

<div class="row g-4 align-items-start">

    {{-- Columna principal --}}
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.helpdesk.integrations.update') }}">
            @csrf
            @method('PUT')

            <div class="card">

                {{-- HelpdeskTickets --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">HelpdeskTickets</h6>
                    <p class="text-muted mb-3">
                        Sistema completo de tickets con SLA, automatizaciones, tickets recurrentes y portal de cliente.
                        Cuando esta habilitado aparece la pestaña Tickets en las conversaciones y el panel del agente.
                    </p>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded border bg-light mb-3">
                        <div>
                            <span class="fw-semibold">
                                <i class="fas fa-ticket text-success me-2"></i>HelpdeskTickets
                            </span>
                            <small class="d-block text-muted mt-1">
                                Integración con el módulo de tickets del helpdesk
                            </small>
                        </div>
                        <div class="form-check form-switch mb-0 ms-4">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                role="switch"
                                id="tickets_integration_enabled"
                                name="tickets_integration_enabled"
                                value="1"
                                @checked($ticketsIntegrationEnabled)
                                @if(! $ticketsModuleInstalled || ! $ticketsModuleEnabled || ! $ticketsConfigEnabled)
                                    disabled
                                @endif
                            >
                        </div>
                    </div>

                    {{-- Diagnóstico --}}
                    <h6 class="fw-semibold mb-2">Diagnóstico de requisitos</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="p-2 rounded border text-center">
                                <small class="text-muted d-block mb-1">Módulo instalado</small>
                                @if($ticketsModuleInstalled)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check me-1"></i>Sí
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="fas fa-xmark me-1"></i>No
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 rounded border text-center">
                                <small class="text-muted d-block mb-1">Módulo habilitado</small>
                                @if($ticketsModuleEnabled)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check me-1"></i>Sí
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="fas fa-pause me-1"></i>Deshabilitado
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 rounded border text-center">
                                <small class="text-muted d-block mb-1">Config (.env)</small>
                                @if($ticketsConfigEnabled)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check me-1"></i>Permitido
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="fas fa-ban me-1"></i>Bloqueado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(! $ticketsModuleInstalled)
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-circle-info me-1"></i>
                            El módulo <strong>HelpdeskTickets</strong> no está instalado. Instálalo primero para poder habilitar esta integración.
                        </div>
                    @elseif(! $ticketsModuleEnabled)
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-circle-info me-1"></i>
                            El módulo HelpdeskTickets está instalado pero deshabilitado. Habilítalo desde
                            <strong>Panel → Módulos</strong> o ejecuta
                            <code>php artisan module:enable HelpdeskTickets</code>.
                        </div>
                    @elseif(! $ticketsConfigEnabled)
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-circle-info me-1"></i>
                            El flag <code>HELPDESK_TICKETS_ENABLED=false</code> en <code>.env</code> está bloqueando
                            la integración. El toggle solo es efectivo cuando este flag no fuerza el modo deshabilitado.
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        Guardar cambios
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Como activar una integración</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Para que el toggle funcione deben cumplirse los 3 requisitos en orden.</p>
                <ol class="text-muted ps-3 mb-0">
                    <li class="mb-2">
                        <strong>Instalar el módulo</strong><br>
                        <small>Copia el módulo a <code>modules/</code> y registra su namespace en <code>composer.json</code>, luego ejecuta <code>composer dump-autoload</code>.</small>
                    </li>
                    <li class="mb-2">
                        <strong>Habilitar el módulo</strong><br>
                        <small>Ejecuta <code>php artisan module:enable NombreModulo</code> o actívalo desde el panel de módulos.</small>
                    </li>
                    <li class="mb-2">
                        <strong>Verificar el .env</strong><br>
                        <small>Asegúrate de que la variable de entorno correspondiente no esté en <code>false</code>.</small>
                    </li>
                    <li>
                        <strong>Activar el toggle</strong><br>
                        <small>Una vez cumplidos los pasos anteriores, el toggle se habilitará y podrás guardarlo.</small>
                    </li>
                </ol>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Variables de entorno</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Variable</th>
                            <th>Efecto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-3"><code>HELPDESK_TICKETS_ENABLED</code></td>
                            <td>
                                <code>true</code> permite el toggle.<br>
                                <code>false</code> deshabilita la integración sin importar el toggle.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom">
                <h6 class="mb-0 fw-bold">Que habilita cada integración</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-semibold mb-2">
                    <i class="fas fa-ticket text-success me-1"></i> HelpdeskTickets
                </h6>
                <ul class="text-muted small ps-3 mb-0">
                    <li class="mb-1">Pestaña <strong>Tickets</strong> en las conversaciones</li>
                    <li class="mb-1">Botón <strong>Crear ticket</strong> desde una conversación</li>
                    <li class="mb-1">Panel de tickets del agente con SLA y prioridades</li>
                    <li class="mb-1">Automatizaciones y reglas de asignación</li>
                    <li class="mb-1">Tickets recurrentes y plantillas</li>
                    <li>Portal de cliente para ver y seguir sus tickets</li>
                </ul>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Guardado');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
