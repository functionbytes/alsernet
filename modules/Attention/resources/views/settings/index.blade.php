@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Atención al Ciudadano'])


        @if ($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3">

            <!-- Opción 1: Configuración Global -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Configuración global
                            </h6>
                            <span class="badge bg-black">Global</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura el comportamiento general del sistema de PQRSF que se aplica a <strong>TODOS</strong>
                            los tipos de solicitudes.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Prefijo de radicado</strong>
                                <br>
                                <small class="text-muted">Define el formato del número de radicado (ej: PQRSF-2024-0001).</small>
                            </li>
                            <li class="mb-2">
                                <strong>Tamaño máximo de archivos</strong>
                                <br>
                                <small class="text-muted">Límite de MB por archivo adjunto en las solicitudes.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Tiempos SLA</strong>
                                <br>
                                <small class="text-muted">Tiempos de respuesta para cada estado de la solicitud.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Notificaciones automáticas</strong>
                                <br>
                                <small class="text-muted">Emails automáticos para cada cambio de estado.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Estas configuraciones se aplican automáticamente a todos los tipos de PQRSF sin excepción.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.configurations.global') }}" class="btn btn-primary w-100">
                            Ir a configuración global
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Tipos de PQRSF -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Tipos de PQRSF
                            </h6>
                            <span class="badge bg-light-secondary">Específico</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura los <strong>tipos de solicitudes</strong> que pueden crear los ciudadanos
                            (Petición, Queja, Reclamo, Sugerencia, Felicitación).
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Gestionar tipos</strong>
                                <br>
                                <small class="text-muted">Define qué tipos de PQRSF estarán disponibles para los usuarios.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Crear tipos personalizados</strong>
                                <br>
                                <small class="text-muted">Agrega nuevos tipos según las necesidades de tu entidad.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Configurar campos</strong>
                                <br>
                                <small class="text-muted">Define campos específicos para cada tipo de solicitud.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Cada tipo tiene sus propios requisitos y flujos de trabajo independientes.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.types.index') }}" class="btn btn-primary w-100">
                            Ir a tipos de PQRSF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 3: Categorías -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Categorías
                            </h6>
                            <span class="badge bg-success">Clasificación</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Organiza las solicitudes en <strong>categorías temáticas</strong> para facilitar su
                            clasificación y enrutamiento automático.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Crear categorías</strong>
                                <br>
                                <small class="text-muted">Define categorías como Servicios Públicos, Infraestructura, Salud, etc.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Asignación por categoría</strong>
                                <br>
                                <small class="text-muted">Asigna automáticamente las solicitudes según la categoría seleccionada.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Estadísticas por categoría</strong>
                                <br>
                                <small class="text-muted">Genera reportes y métricas agrupadas por categoría temática.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Las categorías ayudan a organizar y distribuir eficientemente las solicitudes.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.categories.index') }}" class="btn btn-primary w-100">
                            Ir a categorías
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 4: Departamentos -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Departamentos
                            </h6>
                            <span class="badge bg-light-warning">Asignación</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura los <strong>departamentos o áreas</strong> de tu entidad que gestionarán
                            y darán respuesta a las solicitudes ciudadanas.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Crear departamentos</strong>
                                <br>
                                <small class="text-muted">Define las áreas como Atención al Ciudadano, Jurídica, Técnica, etc.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Asignar responsables</strong>
                                <br>
                                <small class="text-muted">Define usuarios responsables y coordinadores para cada departamento.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Flujos de derivación</strong>
                                <br>
                                <small class="text-muted">Configura reglas para derivar solicitudes entre departamentos.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Los departamentos permiten distribuir el trabajo según la estructura organizacional.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.departments.index') }}" class="btn btn-primary w-100">
                            Ir a departamentos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 5: Sedes -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Sedes
                            </h6>
                            <span class="badge bg-info">Ubicación</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Gestiona las <strong>sedes o puntos de atención</strong> donde los ciudadanos
                            pueden presentar solicitudes presenciales.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Registrar sedes</strong>
                                <br>
                                <small class="text-muted">Agrega sedes con dirección, horarios y contacto.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Asignar personal</strong>
                                <br>
                                <small class="text-muted">Define qué usuarios atienden en cada sede física.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Estadísticas por sede</strong>
                                <br>
                                <small class="text-muted">Genera reportes de solicitudes recibidas por ubicación.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Las sedes permiten gestionar atención presencial y reportes geográficos.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.sedes.index') }}" class="btn btn-primary w-100">
                            Ir a sedes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 6: Plantillas de correo -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Plantillas de correo
                            </h6>
                            <span class="badge bg-primary">Email</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura las <strong>plantillas de correo electrónico</strong> que se envían
                            automáticamente en cada cambio de estado de las solicitudes.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Plantillas por estado</strong>
                                <br>
                                <small class="text-muted">Define emails para Recibido, En proceso, Resuelto y Cerrado.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Integración con Mailer</strong>
                                <br>
                                <small class="text-muted">Usa plantillas avanzadas del módulo Mailer con variables dinámicas.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Personalización</strong>
                                <br>
                                <small class="text-muted">Configura asuntos, cuerpos y adjuntos para cada notificación.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Las plantillas mantienen informados a los ciudadanos sobre el estado de sus solicitudes.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.attention.templates.index') }}" class="btn btn-primary w-100">
                            Ir a plantillas
                        </a>
                    </div>
                </div>
            </div>

        </div>


@endsection
