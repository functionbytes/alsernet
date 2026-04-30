@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de Mailrelay'])
@endsection

@section('content')

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

            <!-- Opción 1: Configuración General -->
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Configuración general
                            </h6>
                            <span class="badge bg-primary">General</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura opciones básicas del módulo Mailrelay que se aplican a <strong>todas</strong> las funcionalidades.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Remitente predeterminado</strong>
                                <br>
                                <small class="text-muted">Nombre y email que aparece como remitente por defecto.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Configuraciones de visualización</strong>
                                <br>
                                <small class="text-muted">Opciones generales de la interfaz y preferencias del sistema.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Comportamiento del módulo</strong>
                                <br>
                                <small class="text-muted">Habilitar/deshabilitar funcionalidades específicas del módulo.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Estas configuraciones afectan el comportamiento global del módulo Mailrelay.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.general.index') }}" class="btn btn-primary w-100">
                            Ir a configuración general
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Configuración API -->
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Configuración API
                            </h6>
                            <span class="badge bg-info">API</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Conecta tu cuenta de <strong>Mailrelay</strong> mediante API para sincronizar suscriptores y enviar campañas.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Credenciales API</strong>
                                <br>
                                <small class="text-muted">API Key y dominio de tu cuenta Mailrelay.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Autenticación</strong>
                                <br>
                                <small class="text-muted">Verifica y configura el acceso a los servicios de Mailrelay.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Prueba de conexión</strong>
                                <br>
                                <small class="text-muted">Valida que las credenciales funcionan correctamente.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            La conexión API es esencial para usar todas las funcionalidades del módulo.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.api.index') }}" class="btn btn-primary w-100">
                            Ir a configuración API
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 3: Plantillas de Email -->
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Plantillas de email
                            </h6>
                            <span class="badge bg-success">Plantillas</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Crea y gestiona <strong>plantillas personalizadas</strong> con diseño responsive para tus campañas de email.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Diseño responsive</strong>
                                <br>
                                <small class="text-muted">Editor visual para crear emails que se ven bien en todos los dispositivos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Variables dinámicas</strong>
                                <br>
                                <small class="text-muted">Personaliza emails con datos del suscriptor (nombre, email, etc.).</small>
                            </li>
                            <li class="mb-2">
                                <strong>Biblioteca de plantillas</strong>
                                <br>
                                <small class="text-muted">Guarda y reutiliza plantillas para diferentes campañas.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Las plantillas permiten enviar emails profesionales y consistentes en todas tus campañas.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.templates.index') }}" class="btn btn-primary w-100">
                            Ir a plantillas de email
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 4: Grupos de Suscriptores -->
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Grupos de suscriptores
                            </h6>
                            <span class="badge bg-warning">Grupos</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Organiza a tus suscriptores en <strong>grupos y listas</strong> para segmentar tu audiencia y enviar campañas dirigidas.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Crear grupos</strong>
                                <br>
                                <small class="text-muted">Define grupos para organizar suscriptores por intereses o categorías.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Segmentación de audiencia</strong>
                                <br>
                                <small class="text-muted">Filtra suscriptores para enviar mensajes específicos a cada grupo.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Gestión de listas</strong>
                                <br>
                                <small class="text-muted">Sincroniza grupos con Mailrelay y gestiona suscripciones.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            La segmentación mejora la efectividad de tus campañas al enviar contenido relevante.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.groups.index') }}" class="btn btn-primary w-100">
                            Ir a grupos de suscriptores
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 5: Campos Personalizados -->
            <div class="col-md-3 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Campos personalizados
                            </h6>
                            <span class="badge bg-secondary">Campos</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Define <strong>campos adicionales</strong> para almacenar datos personalizados de cada suscriptor más allá de nombre y email.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Campos adicionales</strong>
                                <br>
                                <small class="text-muted">Crea campos como teléfono, empresa, ciudad, intereses, etc.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Tipos de datos</strong>
                                <br>
                                <small class="text-muted">Define si el campo es texto, número, fecha, selección múltiple, etc.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Mapeo con Mailrelay</strong>
                                <br>
                                <small class="text-muted">Sincroniza campos personalizados entre tu sistema y Mailrelay.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Los campos personalizados permiten segmentación avanzada y personalización de mensajes.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.custom-fields.index') }}" class="btn btn-primary w-100">
                            Ir a campos personalizados
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 6: Automatizaciones -->
            <div class="col-md-3 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Automatizaciones
                            </h6>
                            <span class="badge bg-purple text-white">Automatización</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Crea <strong>flujos automáticos</strong> de envío de emails basados en eventos, acciones o condiciones específicas.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Triggers y eventos</strong>
                                <br>
                                <small class="text-muted">Define qué acciones activan el envío automático de emails.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Secuencias de email</strong>
                                <br>
                                <small class="text-muted">Programa series de emails que se envían automáticamente en intervalos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Condiciones y filtros</strong>
                                <br>
                                <small class="text-muted">Establece reglas para determinar quién recibe qué emails.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Las automatizaciones ahorran tiempo y mejoran la comunicación con tus suscriptores.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.automations.index') }}" class="btn btn-primary w-100">
                            Ir a automatizaciones
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 7: Webhooks -->
            <div class="col-md-3 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Webhooks
                            </h6>
                            <span class="badge bg-dark">Webhooks</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Recibe <strong>notificaciones en tiempo real</strong> cuando ocurren eventos en Mailrelay (aperturas, clicks, bajas, etc.).
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>URLs de callback</strong>
                                <br>
                                <small class="text-muted">Define endpoints que Mailrelay llamará cuando ocurran eventos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Eventos de Mailrelay</strong>
                                <br>
                                <small class="text-muted">Selecciona qué eventos activarán las notificaciones webhook.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Validación y seguridad</strong>
                                <br>
                                <small class="text-muted">Configura tokens y verificación para asegurar las peticiones.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            Los webhooks permiten integración en tiempo real con otros sistemas y automatizaciones.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.webhooks.index') }}" class="btn btn-primary w-100">
                            Ir a webhooks
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 8: Permisos -->
            <div class="col-md-3 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Permisos
                            </h6>
                            <span class="badge bg-danger">Permisos</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Gestiona <strong>permisos y control de acceso</strong> al módulo Mailrelay para diferentes roles de usuario.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Asignar a roles</strong>
                                <br>
                                <small class="text-muted">Define qué roles pueden acceder a funcionalidades del módulo.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Niveles de acceso</strong>
                                <br>
                                <small class="text-muted">Configura permisos de lectura, escritura, edición y eliminación.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Control granular</strong>
                                <br>
                                <small class="text-muted">Establece restricciones específicas por sección del módulo.</small>
                            </li>
                        </ul>

                        <p class="text-muted border-top pt-3">
                            El control de permisos asegura que solo usuarios autorizados gestionen campañas y suscriptores.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.mailrelay.permissions.index') }}" class="btn btn-primary w-100">
                            Ir a permisos
                        </a>
                    </div>
                </div>
            </div>

        </div>


@endsection
