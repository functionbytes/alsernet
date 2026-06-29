@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración ERP'])

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

        <div class="row g-3 mb-3">
            <!-- Dashboard -->
            <div class="col-12">
                <a href="{{ route('settings.erp.dashboard.index') }}" class="btn btn-lg btn-primary w-100 py-3">
                    <i class="fa fa-chart-line me-2"></i> Ver Panel de Control
                    <span class="float-end"><i class="fa fa-arrow-right"></i></span>
                </a>
            </div>
        </div>

        <div class="row g-3">

            <!-- Opción 1: API del ERP -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa fa-plug me-2"></i> API del ERP
                            </h6>
                            <span class="badge bg-primary">HTTP</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configure las <strong>URLs de los servicios HTTP</strong> del ERP para integrar funcionalidades mediante API REST, sincronización y otros protocolos.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>API REST</strong>
                                <br>
                                <small class="text-muted">Endpoint para consultas de clientes, pedidos, productos y otras operaciones mediante HTTP/REST.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Sincronización</strong>
                                <br>
                                <small class="text-muted">URL del servicio de sincronización automática de datos entre sistemas.</small>
                            </li>
                            <li class="mb-2">
                                <strong>XML-RPC</strong>
                                <br>
                                <small class="text-muted">Configurar endpoint para llamadas remotas usando protocolo XML-RPC.</small>
                            </li>
                            <li class="mb-2">
                                <strong>SMS</strong>
                                <br>
                                <small class="text-muted">Servicio para envío de mensajes SMS integrados con el ERP.</small>
                            </li>
                        </ul>

                        <p class="text-muted small border-top pt-3">
                            Estos servicios permiten la comunicación HTTP entre tu aplicación y el servidor ERP.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.erp.api.edit') }}" class="btn btn-primary w-100">
                            <i class="fa fa-arrow-right me-2"></i> Configurar API del ERP
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Oracle Database -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa fa-database me-2"></i> Oracle Database
                            </h6>
                            <span class="badge bg-light text-black">OCI8</span>
                        </div>
                    </div>

                    <div class="card-body pb-0">
                        <p class="text-muted mb-3">
                            Configure la <strong>conexión directa a la base de datos Oracle</strong> para consultas avanzadas y sincronización a nivel de datos.
                        </p>

                        <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                            <strong>¿Qué configuras aquí?</strong>
                        </div>

                        <ul class="list-unstyled ms-3 mb-4">
                            <li class="mb-2">
                                <strong>Servidor Oracle</strong>
                                <br>
                                <small class="text-muted">Configurar host, puerto, nombre de base de datos (SID) o service name.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Credenciales</strong>
                                <br>
                                <small class="text-muted">Usuario y contraseña para autenticación en la base de datos Oracle.</small>
                            </li>
                            <li class="mb-2">
                                <strong>Schema</strong>
                                <br>
                                <small class="text-muted">Definir el esquema predeterminado para las consultas (ej: DEVELOPER, PRODUCTION).</small>
                            </li>
                            <li class="mb-2">
                                <strong>Pruebas de conexión</strong>
                                <br>
                                <small class="text-muted">Verificar que la configuración funcione correctamente probando la conexión.</small>
                            </li>
                        </ul>

                        @if(config('database.connections.oracle.host'))
                            <div class="bg-light-secondary p-3 rounded mb-3">
                                <small class="text-muted">
                                    <strong>Configuración actual:</strong><br>
                                    Servidor: {{ config('database.connections.oracle.host') }}:{{ config('database.connections.oracle.port') }}<br>
                                    Base de datos: {{ config('database.connections.oracle.database') }}
                                </small>
                            </div>
                        @endif

                        <p class="text-muted small border-top pt-3">
                            Esta conexión utiliza el driver OCI8 de PHP para comunicarse directamente con Oracle.
                        </p>
                    </div>

                    <div class="card-footer border-top">
                        <a href="{{ route('settings.erp.database.index') }}" class="btn btn-primary w-100">
                            <i class="fa fa-arrow-right me-2"></i> Configurar Oracle Database
                        </a>
                    </div>
                </div>
            </div>

        </div>


@endsection
