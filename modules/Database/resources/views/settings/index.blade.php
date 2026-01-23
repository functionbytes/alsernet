@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de Base de Datos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @if(isset($error))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-exclamation"></i> {{ $error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Configuración de Base de Datos</h5>
                        <p class="small mb-0 text-muted">Gestiona la conexión a la base de datos y verifica su
                            estado.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="testConnectionBtn">
                            <i class="fa fa-plug me-1"></i> Probar conexión
                        </button>
                        <a href="{{ route('settings.database.edit') }}" class="btn btn-secondary">
                            <i class="fa fa-pen-to-square me-1"></i> Editar configuración
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card  h-100  ">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">
                                            Servidor
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $settings['db_host'] ?? 'N/A' }}</h4>
                                        <small class="text-muted">Puerto: {{ $settings['db_port'] ?? '3306' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card  h-100  ">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Base de datos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $settings['db_database'] ?? 'N/A' }}</h4>
                                        <small class="text-muted">{{ strtoupper($settings['db_connection'] ?? 'N/A') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card  h-100  ">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">
                                            Usuario
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $settings['db_username'] ?? 'root' }}</h4>
                                        <small class="text-muted">Credenciales de acceso</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card  h-100  ">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">
                                            Charset
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $settings['db_charset'] ?? 'utf8mb4' }}</h4>
                                        <small class="text-muted">{{ $settings['db_collation'] ?? 'utf8mb4_unicode_ci' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Test Result -->
            <div class="card-body border-bottom" id="connectionStatusContainer" style="display: none;">
                <div id="connectionStatusContent"></div>
            </div>

            <!-- Configuration Details Table -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Detalles técnicos</h6>
                        <p class="text-muted small mb-0">Parámetros de configuración de la base de datos</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Advertencia:</small>
                            <small class="d-block">Cambiar estas configuraciones incorrectamente puede hacer que la
                                aplicación no funcione.</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Parámetro</th>
                            <th style="width: 25%;">Valor actual</th>
                            <th style="width: 50%;">Descripción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><code>db_connection</code></td>
                            <td><strong>{{ $settings['db_connection'] ?? 'mysql' }}</strong></td>
                            <td><small class="text-muted">Tipo de base de datos (mysql, pgsql, sqlite, etc)</small></td>
                        </tr>
                        <tr>
                            <td><code>db_host</code></td>
                            <td><strong>{{ $settings['db_host'] ?? 'localhost' }}</strong></td>
                            <td><small class="text-muted">Dirección del servidor de base de datos</small></td>
                        </tr>
                        <tr>
                            <td><code>db_port</code></td>
                            <td><strong>{{ $settings['db_port'] ?? '3306' }}</strong></td>
                            <td><small class="text-muted">Puerto de la conexión (3306 MySQL, 5432 PostgreSQL)</small>
                            </td>
                        </tr>
                        <tr>
                            <td><code>db_database</code></td>
                            <td><strong>{{ $settings['db_database'] ?? 'N/A' }}</strong></td>
                            <td><small class="text-muted">Nombre de la base de datos</small></td>
                        </tr>
                        <tr>
                            <td><code>db_username</code></td>
                            <td><strong>{{ $settings['db_username'] ?? 'root' }}</strong></td>
                            <td><small class="text-muted">Usuario para la conexión</small></td>
                        </tr>
                        <tr>
                            <td><code>db_charset</code></td>
                            <td><strong>{{ $settings['db_charset'] ?? 'utf8mb4' }}</strong></td>
                            <td><small class="text-muted">Conjunto de caracteres para almacenamiento</small></td>
                        </tr>
                        <tr>
                            <td><code>db_collation</code></td>
                            <td><strong>{{ $settings['db_collation'] ?? 'utf8mb4_unicode_ci' }}</strong></td>
                            <td><small class="text-muted">Reglas de comparación de caracteres</small></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Test Database Connection
            $('#testConnectionBtn').on('click', function () {
                var btn = $(this);
                btn.prop('disabled', true);
                btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Probando...');

                $.ajax({
                    url: '{{ route("settings.database.check-connection") }}',
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        if (data.success) {
                            // Solo mostrar toastr, sin panel de alerta
                            var versionInfo = data.version ? ' (Versión: ' + data.version + ')' : '';
                            toastr.success('Conexión establecida correctamente' + versionInfo, 'Base de Datos');
                            $('#connectionStatusContainer').hide();
                        } else {
                            // Mostrar panel de alerta con el error detallado
                            $('#connectionStatusContainer').show();
                            $('#connectionStatusContent').html(
                                '<div class="alert alert-danger border-0 bg-danger-subtle">' +
                                '    <div class="d-flex align-items-start gap-2">' +
                                '        <i class="fa fa-circle-xmark text-danger fs-5"></i>' +
                                '        <div>' +
                                '            <strong class="text-danger">Error de conexión</strong>' +
                                '            <small class="d-block">' + data.message + '</small>' +
                                '        </div>' +
                                '    </div>' +
                                '</div>'
                            );
                            toastr.error('No se pudo conectar a la base de datos', 'Error');
                        }
                    },
                    error: function (xhr, status, error) {
                        $('#connectionStatusContainer').show();
                        $('#connectionStatusContent').html(
                            '<div class="alert alert-danger border-0 bg-danger-subtle">' +
                            '    <div class="d-flex align-items-start gap-2">' +
                            '        <i class="fa fa-circle-xmark text-danger fs-5"></i>' +
                            '        <div>' +
                            '            <strong class="text-danger">Error en la solicitud</strong>' +
                            '            <small class="d-block">' + error + '</small>' +
                            '        </div>' +
                            '    </div>' +
                            '</div>'
                        );
                        toastr.error('Error al probar la conexión', 'Error');
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                        btn.html('<i class="fa fa-plug me-1"></i> Probar conexión');
                    }
                });
            });
        });
    </script>
@endpush
