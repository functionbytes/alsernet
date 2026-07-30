@extends('layouts.theme')

@section('title', 'Integraciones del helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Integraciones del helpdesk'])
@endsection

@section('content')

@include('core::components.alerts')

<div class="row g-4 align-items-start">

    {{-- Columna principal --}}
    <div class="col-lg-8">
        <form method="POST" action="{{ route('settings.helpdesk.integrations.update') }}">
            @csrf
            @method('PUT')

            <div class="card">

                @foreach($toggleableModules as $module)
                    <div class="card-body @unless($loop->last) border-bottom @endunless">
                        <h6 class="fw-bold text-dark mb-1">{{ $module['name'] }}</h6>
                        <p class="text-muted mb-3">{{ $module['description'] }}</p>

                        <div class="d-flex align-items-center justify-content-between p-3 rounded border bg-light mb-3">
                            <div>
                                <span class="fw-semibold">
                                    <i class="fas fa-plug text-success me-2"></i>{{ $module['name'] }}
                                </span>
                                <small class="d-block text-muted mt-1">
                                    Integración con el módulo {{ $module['name'] }} del helpdesk
                                </small>
                            </div>
                            <div class="form-check form-switch mb-0 ms-4">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    role="switch"
                                    id="{{ $module['key'] }}_integration_enabled"
                                    name="{{ $module['key'] }}_integration_enabled"
                                    value="1"
                                    @checked($module['toggleEnabled'])
                                    @unless($module['canToggle'])
                                        disabled
                                    @endunless
                                >
                            </div>
                        </div>

                        {{-- Diagnóstico --}}
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="p-2 rounded border text-center">
                                    <small class="text-muted d-block mb-1">Módulo instalado</small>
                                    @if($module['installed'])
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
                                    @if($module['moduleEnabled'])
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
                            @if(! is_null($module['configEnabled']))
                                <div class="col-md-4">
                                    <div class="p-2 rounded border text-center">
                                        <small class="text-muted d-block mb-1">Config (.env)</small>
                                        @if($module['configEnabled'])
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
                            @endif
                        </div>

                        @if(! $module['installed'])
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-circle-info me-1"></i>
                                El módulo <strong>{{ $module['name'] }}</strong> no está instalado. Instálalo primero para poder habilitar esta integración.
                            </div>
                        @elseif(! $module['moduleEnabled'])
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-circle-info me-1"></i>
                                El módulo {{ $module['name'] }} está instalado pero deshabilitado. Habilítalo desde
                                <strong>Panel → Módulos</strong> o ejecuta
                                <code>php artisan module:enable {{ $module['name'] }}</code>.
                            </div>
                        @elseif($module['configEnabled'] === false)
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-circle-info me-1"></i>
                                El flag <code>HELPDESK_TICKETS_ENABLED=false</code> en <code>.env</code> está bloqueando
                                la integración. El toggle solo es efectivo cuando este flag no fuerza el modo deshabilitado.
                            </div>
                        @endif
                    </div>
                @endforeach

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
                            <th scope="col" class="ps-3">Variable</th>
                            <th scope="col">Efecto</th>
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
                @foreach($toggleableModules as $module)
                    <h6 class="fw-semibold mb-2 @unless($loop->first) mt-3 @endunless">
                        <i class="fas fa-plug text-success me-1"></i> {{ $module['name'] }}
                    </h6>
                    <p class="text-muted small mb-0">{{ $module['description'] }}</p>
                @endforeach
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
