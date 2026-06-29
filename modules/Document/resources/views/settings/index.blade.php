@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Documentos'])


        @if ($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-checkme-2"></i> {{ $message }}
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

                    <div class="card-body  pb-0">
                        <p class="text-muted mb-3">
                            Configura el comportamiento general del sistema de documentos que se aplica a <strong>TODOS</strong>
                            los tipos de documento.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Solicitud inicial</strong>
                                <br>
                                <small class="text-muted">Habilitar/deshabilitar solicitud de documentos cuando se crea una orden. Incluye mensaje personalizado.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Recordatorios automáticos</strong>
                                <br>
                                <small class="text-muted">Enviar recordatorios automáticos después de X días. Especifica intervalo y mensaje personalizado.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Documentos específicos</strong>
                                <br>
                                <small class="text-muted">Permitir solicitar documentos específicos que los clientes deben re-cargar o corregir.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Estas configuraciones se aplican automáticamente a todos los tipos de documento sin excepción.
                        </p>
                    </div>

                    <div class="card-footer  border-top">
                        <a href="{{ route('settings.documents.configurations.global') }}" class="btn btn-primary w-100">
                            Ir a configuración global
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Tipos de Documentos -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Tipos de documentos
                            </h6>
                            <span class="badge bg-light-secondary">Específico</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura los <strong>documentos específicos requeridos</strong> para cada tipo de solicitud
                            (Armas Cortas, Rifles, Escopetas, etc.). Crea nuevos tipos personalizados si lo necesitas.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Documentos por tipo</strong>
                                <br>
                                <small class="text-muted">Define qué documentos se requieren para cada tipo (DNI, Licencia, etc.).</small>
                            </li>
                            <li class="mb-2">
                                <strong>Crear tipos personalizados</strong>
                                <br>
                                <small class="text-muted">Crea nuevos tipos de documento con sus propios requisitos únicos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Editar configuraciones</strong>
                                <br>
                                <small class="text-muted">Modifica los documentos requeridos para cualquier tipo en cualquier momento.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Cada tipo tiene sus propios requisitos independientes. Los clientes verán solo los documentos
                            necesarios para su tipo específico.
                        </p>
                    </div>

                    <div class="card-footer  border-top">
                        <a href="{{ route('settings.documents.types.index') }}" class="btn btn-primary w-100">
                            Ir a tipos de documentos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 3: Grupos de Documentos -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Grupos de documentos
                            </h6>
                            <span class="badge bg-success">Asignación</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Organiza a los usuarios en <strong>grupos para la asignación automática</strong> de documentos
                            según diferentes estrategias (manual, round robin, balance de carga).
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Crear grupos de trabajo</strong>
                                <br>
                                <small class="text-muted">Define grupos de usuarios que gestionarán documentos.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Asignación automática</strong>
                                <br>
                                <small class="text-muted">Configura estrategias de asignación: manual, rotación o balance de carga.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Prioridades de usuarios</strong>
                                <br>
                                <small class="text-muted">Establece usuarios primarios y de respaldo en cada grupo.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Los grupos facilitan la distribución equitativa de trabajo y mejoran los tiempos de respuesta.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.documents.groups.index') }}" class="btn btn-primary w-100">
                            Ir a grupos de documentos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 4: Almacenamiento de Documentos -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Almacenamiento de documentos
                            </h6>
                            <span class="badge bg-light-warning">Almacenamiento</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura <strong>dónde se guardan los archivos</strong> de documentos subidos por clientes
                            y administradores (carpeta local, FTP, red compartida).
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Almacenamiento local</strong>
                                <br>
                                <small class="text-muted">Guarda archivos en el servidor donde está instalada la aplicación.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Servidor FTP/SFTP</strong>
                                <br>
                                <small class="text-muted">Conecta a un servidor FTP remoto para almacenar archivos de forma centralizada.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Carpeta compartida en red</strong>
                                <br>
                                <small class="text-muted">Usa una carpeta compartida montada (SMB/NFS) para almacenar archivos accesibles desde múltiples servidores.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Configura diferentes destinos de almacenamiento según el tipo de documento para mayor control.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.documents.configurations.storage') }}" class="btn btn-primary w-100">
                            Ir a configuración de almacenamiento
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 5: Sincronización de Bloqueos -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Sincronización de bloqueos
                            </h6>
                            <span class="badge bg-light-info">Automatización</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Programa la <strong>sincronización automática de bloqueos de productos</strong> desde PrestaShop.
                            Define el horario y el tipo de sincronización.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Horario de ejecución</strong>
                                <br>
                                <small class="text-muted">Selecciona la frecuencia: cada hora, diariamente, semanalmente o con expresión cron personalizada.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Tipo de sincronización</strong>
                                <br>
                                <small class="text-muted">Elige entre sincronización incremental (solo añade nuevos registros) o completa (trunca y reimporta todo).</small>
                            </li>
                            <li class="mb-2">
                                <strong>Habilitar / deshabilitar</strong>
                                <br>
                                <small class="text-muted">Activa o desactiva la sincronización automática sin perder la configuración del horario.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Requiere que el programador de Laravel esté activo (<code>schedule:run</code> en cron o <code>schedule:work</code>).
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.documents.configurations.sync-schedule') }}" class="btn btn-primary w-100">
                            Ir a programación de sincronización
                        </a>
                    </div>
                </div>
            </div>

            <!-- Endpoints / Integraciones -->
            <div class="col-md-6 mt-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                Endpoints / Integraciones
                            </h6>
                            <span class="badge bg-light-danger">Integraciones</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configura las <strong>URLs de los servicios externos</strong> que el módulo llama automáticamente
                            en respuesta a eventos del flujo de documentos.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Documentación OK</strong>
                                <br>
                                <small class="text-muted">Endpoint que se notifica cuando un pedido completa todas las etapas de validación.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Puedes probar cada endpoint desde el panel antes de guardar.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.documents.configurations.endpoints') }}" class="btn btn-primary w-100">
                            Ir a endpoints / integraciones
                        </a>
                    </div>
                </div>
            </div>

        </div>


@endsection
