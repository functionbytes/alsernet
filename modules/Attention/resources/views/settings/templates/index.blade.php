@extends('layouts.theme')

@section('title', 'Plantillas de email')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de email'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda: listado --}}
            <div class="col-lg-8">

                <div class="card">

                    {{-- Plantillas disponibles --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Plantillas disponibles</h6>
                        <p class="text-muted mb-3">Plantillas del módulo Mailer utilizadas en las notificaciones.</p>

                        @if(count($availableTemplates) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableTemplates as $template)
                                            <tr>
                                                <td><strong>{{ $template['text'] ?? $template['name'] }}</strong></td>
                                                <td><small class="text-muted">{{ $template['description'] ?? 'Sin descripción' }}</small></td>
                                                <td class="text-center">
                                                    @if($template['is_active'] ?? true)
                                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                                    @else
                                                        <span class="badge bg-light text-black">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('mailers.templates.edit', $template['id']) }}" target="_blank">
                                                                    Editar en Mailer
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <h6 class="mb-1">No hay plantillas disponibles</h6>
                                    <p class="text-muted mb-3">Crea plantillas en el módulo Mailer para usarlas en las notificaciones de peticiones</p>
                                    <a href="{{ route('mailers.templates.create') }}" class="btn btn-sm btn-primary" target="_blank">
                                        Crear primera plantilla en Mailer
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr class="my-0">

                    {{-- Variables dinámicas --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Variables dinámicas</h6>
                        <p class="text-muted mb-3">Usa estas variables en tus plantillas para personalizar cada email.</p>

                        <div class="row g-3">
                            @php
                                $variables = [
                                    ['var' => 'radicado',    'desc' => 'Número de radicado único',    'example' => 'peticiones-2026-00123'],
                                    ['var' => 'tipo',        'desc' => 'Tipo de solicitud',           'example' => 'Petición'],
                                    ['var' => 'categoria',   'desc' => 'Categoría de la solicitud',   'example' => 'Servicios públicos'],
                                    ['var' => 'estado',      'desc' => 'Estado actual',               'example' => 'En proceso'],
                                    ['var' => 'ciudadano',   'desc' => 'Nombre del ciudadano',        'example' => 'Juan García'],
                                    ['var' => 'fecha',       'desc' => 'Fecha de creación',           'example' => '21/03/2026'],
                                    ['var' => 'asignado',    'desc' => 'Agente responsable',          'example' => 'María López'],
                                    ['var' => 'descripcion', 'desc' => 'Descripción de la solicitud', 'example' => 'Solicitud de información sobre...'],
                                ];
                            @endphp

                            @foreach($variables as $var)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start gap-3">
                                        <code class="bg-light px-2 py-1 rounded text-nowrap">@{{{{ $var['var'] }}}}</code>
                                        <div>
                                            <p class="mb-1 small">{{ $var['desc'] }}</p>
                                            <small class="text-muted">Ej: <em>{{ $var['example'] }}</em></small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('settings.attention.index') }}" class="btn btn-secondary w-100">
                            Volver
                        </a>
                    </div>

                </div>

            </div>

            {{-- Columna derecha: sidebar --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Gestionar plantillas</h6>
                        <p class="text-muted mb-3">Crea y edita plantillas de email desde el módulo Mailer.</p>
                        <a href="{{ route('mailers.templates.index') }}" class="btn btn-primary w-100" target="_blank">
                            Ir a Mailer
                        </a>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Configurar notificaciones</h6>
                        <p class="text-muted mb-3">Define qué plantilla usar para cada evento del ciclo de vida peticiones.</p>
                        <a href="{{ route('settings.attention.configurations.global') }}?tab=email" class="btn btn-outline-secondary w-100">
                            Configurar emails
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Eventos de notificación</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted mb-0">
                            <li class="mb-2"><strong>Recepción:</strong> cuando el ciudadano crea una solicitud.</li>
                            <li class="mb-2"><strong>Asignación:</strong> cuando se asigna a un responsable.</li>
                            <li class="mb-2"><strong>En proceso:</strong> cuando cambia a estado "En proceso".</li>
                            <li class="mb-2"><strong>Resolución:</strong> cuando se responde oficialmente.</li>
                            <li><strong>Cierre:</strong> cuando se cierra definitivamente.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
