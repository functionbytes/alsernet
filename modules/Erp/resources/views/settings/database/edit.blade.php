@extends('layouts.theme')

@section('title', 'Configuración de Oracle Database')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de Oracle Database'])
@endsection

@section('content')

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card">

                <form id="formOracleDb" method="POST" action="{{ route('settings.erp.database.update') }}">

                    {{ csrf_field() }}
                    @method('PUT')

                    <div class="card-header">
                        <h5 class="mb-1">Configuración de Oracle Database</h5>
                        <p class="text-muted mb-0" style="font-size:.875rem">Configura los parámetros de conexión a la base de datos Oracle utilizados para acceder directamente a tu ERP.</p>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <!-- Host -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Host/Dirección IP</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_host') is-invalid @enderror"
                                        id="oracle_host"
                                        name="oracle_host"
                                        value="{{ old('oracle_host', $settings['oracle_host'] ?? '') }}"
                                        placeholder="192.168.253.8"
                                        required>
                                    @error('oracle_host')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Puerto -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Puerto</label>
                                    <input
                                        type="number"
                                        class="form-control @error('oracle_port') is-invalid @enderror"
                                        id="oracle_port"
                                        name="oracle_port"
                                        value="{{ old('oracle_port', $settings['oracle_port'] ?? 1521) }}"
                                        placeholder="1521"
                                        min="1"
                                        max="65535"
                                        required>
                                    @error('oracle_port')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Database Name -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre de Base de Datos</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_database') is-invalid @enderror"
                                        id="oracle_database"
                                        name="oracle_database"
                                        value="{{ old('oracle_database', $settings['oracle_database'] ?? 'GESTCENT') }}"
                                        placeholder="GESTCENT"
                                        required>
                                    @error('oracle_database')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Service Name -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Service Name</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_service_name') is-invalid @enderror"
                                        id="oracle_service_name"
                                        name="oracle_service_name"
                                        value="{{ old('oracle_service_name', $settings['oracle_service_name'] ?? 'GESTCENT') }}"
                                        placeholder="GESTCENT"
                                        required>
                                    @error('oracle_service_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Usuario</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_username') is-invalid @enderror"
                                        id="oracle_username"
                                        name="oracle_username"
                                        value="{{ old('oracle_username', $settings['oracle_username'] ?? '') }}"
                                        placeholder="lectura"
                                        required>
                                    @error('oracle_username')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Contraseña</label>
                                    <input
                                        type="password"
                                        class="form-control @error('oracle_password') is-invalid @enderror"
                                        id="oracle_password"
                                        name="oracle_password"
                                        value="{{ old('oracle_password', $settings['oracle_password'] ?? '') }}"
                                        placeholder="••••••••"
                                        required>
                                    @error('oracle_password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Schema -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Schema</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_schema') is-invalid @enderror"
                                        id="oracle_schema"
                                        name="oracle_schema"
                                        value="{{ old('oracle_schema', $settings['oracle_schema'] ?? 'DEVELOPER') }}"
                                        placeholder="DEVELOPER"
                                        required>
                                    @error('oracle_schema')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Charset -->
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Character Set</label>
                                    <input
                                        type="text"
                                        class="form-control @error('oracle_charset') is-invalid @enderror"
                                        id="oracle_charset"
                                        name="oracle_charset"
                                        value="{{ old('oracle_charset', $settings['oracle_charset'] ?? 'AL32UTF8') }}"
                                        placeholder="AL32UTF8"
                                        required>
                                    @error('oracle_charset')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <!-- Performance Optimization Section -->
                        <div class="row mt-4">


                            <!-- Enable Cache -->
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    Caché Redis de consultas
                                                </h6>
                                                <p class="text-muted small mb-0">
                                                    Cachea resultados de consultas en Redis durante 60 segundos para mejorar la velocidad de respuesta.
                                                    <br>
                                                    <span class="badge bg-success-subtle text-success mt-1">
                                                        Mejora: de 500ms a 150ms (~70% más rápido)
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="form-check form-switch ms-3">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    id="oracle_enable_cache"
                                                    name="oracle_enable_cache"
                                                    value="1"
                                                    style="width: 3rem; height: 1.5rem;"
                                                    {{ old('oracle_enable_cache', $settings['oracle_enable_cache'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label ms-2" for="oracle_enable_cache">

                                                </label>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-0 mb-0 px-1">
                                            <small>
                                                <strong>Nota:</strong> El caché se limpia automáticamente cada 60 segundos. Desactívalo solo si necesitas datos en tiempo real absoluto.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="card-footer">
                            <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2 w-100">
                                Guardar
                            </button>
                            <a href="{{ route('settings.erp.database.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                                Volver
                            </a>
                    </div>

                </form>
            </div>
        </div>

        <!-- Sidebar de Ayuda -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Información importante</h6>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">
                            <strong>¿Qué configuras aquí?</strong>
                        </small>
                        <ul class="small text-muted mb-0 ps-3">
                            <li class="mb-2">Parámetros de conexión a Oracle Database</li>
                            <li class="mb-2">Verifica que el servidor esté accesible</li>
                            <li class="mb-2">Asegúrate de tener permisos adecuados</li>
                            <li class="mb-2">Charset recomendado: AL32UTF8</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-0 py-2 px-3">
                        <small class="mb-0">
                            <i class="fa fa-lightbulb me-1"></i>
                            Prueba la conexión después de guardar los cambios desde la página de información.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Formato de Valores -->
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Formato de valores</h6>
                    <div class="bg-light p-3 rounded">
                        <code class="d-block mb-2">
                            <strong>Host:</strong> {{ $settings['oracle_host'] ?? '—' }}
                        </code>
                        <code class="d-block mb-2">
                            <strong>Port:</strong> {{ $settings['oracle_port'] ?? 1521 }}
                        </code>
                        <code class="d-block mb-2">
                            <strong>Database:</strong> {{ $settings['oracle_database'] ?? '—' }}
                        </code>
                        <code class="d-block mb-2">
                            <strong>Service Name:</strong> {{ $settings['oracle_service_name'] ?? '—' }}
                        </code>
                        <code class="d-block mb-2">
                            <strong>Username:</strong> {{ $settings['oracle_username'] ?? '—' }}
                        </code>
                        <code class="d-block mb-2">
                            <strong>Schema:</strong> {{ $settings['oracle_schema'] ?? '—' }}
                        </code>
                        <code class="d-block">
                            <strong>Charset:</strong> AL32UTF8
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update cache status badge when toggle changes
    $('#oracle_enable_cache').on('change', function() {
        const isEnabled = $(this).is(':checked');
        $('#cache-status').text(isEnabled ? 'Activado' : 'Desactivado');
    });
});
</script>
@endpush
