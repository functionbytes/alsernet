@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Limpieza de Base de Datos'])
@endsection

@section('content')

        @include('core::components.alerts')

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fa fa-lock" style="font-size: 4rem; color: #000;"></i>
                        </div>
                        <h4 class="mb-3">Esta característica aún no está habilitada</h4>
                        <p class="text-muted mb-4">
                            Por seguridad, la limpieza de base de datos está deshabilitada Por defecto.
                        </p>

                        <div class="alert bg-light" role="alert">
                            <h5 class="alert-heading">
                                <i class="fa fa-triangle-exclamation"></i> Importante
                            </h5>
                            <p class="mb-2">
                                <strong>Antes de habilitar esta característica:</strong>
                            </p>
                            <ul class="mb-0 text-center">
                                <li>Realiza una copia de seguridad de tu base de datos</li>
                                <li>Realiza una copia de seguridad de tus archivos de script</li>
                                <li>Ten en cuenta que esta operación eliminará datos de forma permanente</li>
                            </ul>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('settings.database.edit') }}" class="btn btn-primary">
                                <i class="fa fa-gear"></i> Ir a configuración de base de datos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Seguridad</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            <i class="fa fa-shield-check text-success"></i>
                            <strong>Protección contra eliminación accidental</strong>
                        </p>
                        <p class=" text-muted">
                            Esta característica requiere una confirmación explícita en el archivo de configuración para
                            prevenir la eliminación accidental de datos.
                        </p>

                        <hr>

                        <p class="mb-3">
                            <i class="fa fa-lock text-warning"></i>
                            <strong>Requiere permisos de administrador</strong>
                        </p>
                        <p class=" text-muted">
                            Solo usuarios con rol de administrador podrán usar esta función.
                        </p>

                        <hr>

                        <p class="mb-0">
                            <i class="fa fa-history text-info"></i>
                            <strong>Auditoria registrada</strong>
                        </p>
                        <p class=" text-muted">
                            Todas las operaciones de limpieza se registran en el historial de actividades.
                        </p>
                    </div>
                </div>
            </div>
        </div>


    <style>
        .card {
            border-radius: 8px;
        }

        .card-header {
            border-bottom: 1px solid #e3e6f0;
            border-radius: 8px 8px 0 0;
        }

        code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e83e8c;
        }
    </style>
@endsection
