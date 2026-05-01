@extends('layouts.theme')

@section('title', 'GDPR — Gestión de datos')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-shield-halved me-2 text-primary"></i>
                GDPR — Gestión de datos
            </h4>
            <p class="text-muted mb-0">Herramientas de cumplimiento del Reglamento General de Protección de Datos</p>
        </div>
    </div>

    <div class="row g-4">

        {{-- Descripción del proceso --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Sobre el proceso GDPR</h6>
                    <p class="text-muted mb-3">
                        El RGPD (Reglamento General de Protección de Datos) otorga a los ciudadanos europeos
                        el derecho a acceder a sus datos personales, corregirlos y solicitar su eliminación.
                        Este panel centraliza las herramientas para atender dichas solicitudes.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary-subtle text-primary rounded-circle p-2">
                                        <i class="fas fa-file-export"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">Derecho de acceso (Art. 15)</h6>
                                    <p class="text-muted small mb-0">
                                        El cliente puede solicitar una copia de todos sus datos.
                                        Usa la opción "Descargar exportación" en el perfil del cliente.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-danger-subtle text-danger rounded-circle p-2">
                                        <i class="fas fa-user-slash"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">Derecho al olvido (Art. 17)</h6>
                                    <p class="text-muted small mb-0">
                                        El cliente puede solicitar la eliminación de sus datos.
                                        Puedes anonimizar (recomendado) o eliminar permanentemente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Cómo atender una solicitud</h6>
                    <ol class="text-muted ps-3 mb-0">
                        <li class="mb-2">
                            Ve a la lista de clientes y localiza al cliente que realizó la solicitud.
                        </li>
                        <li class="mb-2">
                            Abre el perfil del cliente y desplázate hasta la sección
                            <strong>Datos GDPR</strong> al final de la página.
                        </li>
                        <li class="mb-2">
                            Para <strong>exportar</strong>: haz clic en "Descargar exportación" y
                            entrega el archivo ZIP al cliente.
                        </li>
                        <li class="mb-2">
                            Para <strong>eliminar</strong>: selecciona el tipo de eliminación,
                            escribe <code>CONFIRMAR</code> y haz clic en "Proceder".
                        </li>
                        <li>
                            Documenta la acción realizada en tus registros de cumplimiento.
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Acciones rápidas --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Acciones rápidas</h6>
                    <a href="{{ route('manager.helpdesk.customers.index') }}"
                       class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-users me-1"></i> Ver clientes
                    </a>
                    <p class="text-muted small mb-0">
                        Desde el perfil de cada cliente encontrarás el panel GDPR con las
                        opciones de exportación y eliminación de datos.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Plazos de respuesta</h6>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-2">
                            <i class="fas fa-clock text-primary me-1"></i>
                            <strong>Solicitudes de acceso:</strong> máximo 30 días naturales.
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-danger me-1"></i>
                            <strong>Eliminación:</strong> máximo 30 días desde la solicitud.
                        </li>
                        <li>
                            <i class="fas fa-info-circle text-muted me-1"></i>
                            La anonimización marca los datos para eliminación definitiva a los 90 días.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
