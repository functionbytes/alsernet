@extends('layouts.theme')

@section('title', 'Configuración de base de datos')

@section('page_header')
    @include('core::components.card', ['title' => 'Base de datos'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Formulario --}}
        <div class="col-lg-8">
            <div class="card">
                <form id="formDatabase" method="POST" action="{{ route('settings.database.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configuración de base de datos</h5>
                        <small class="text-muted">Parámetros de conexión al servidor de base de datos</small>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Tipo de conexión <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('db_connection') is-invalid @enderror"
                                        id="dbConnection" name="db_connection" required>
                                    <option value=""></option>
                                    <option value="mysql" {{ old('db_connection', $settings['db_connection']) === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
                                    <option value="pgsql" {{ old('db_connection', $settings['db_connection']) === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                                    <option value="sqlite" {{ old('db_connection', $settings['db_connection']) === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                                </select>
                                @error('db_connection')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Host / Servidor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('db_host') is-invalid @enderror"
                                       id="dbHost" name="db_host"
                                       value="{{ old('db_host', $settings['db_host']) }}"
                                       required placeholder="localhost" minlength="3" maxlength="255">
                                @error('db_host')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Dirección IP o nombre del servidor</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Puerto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('db_port') is-invalid @enderror"
                                       id="dbPort" name="db_port"
                                       value="{{ old('db_port', $settings['db_port']) }}"
                                       required placeholder="3306" min="1" max="65535">
                                @error('db_port')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Puerto de conexión (1–65535)</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Nombre de la base de datos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('db_database') is-invalid @enderror"
                                       id="dbDatabase" name="db_database"
                                       value="{{ old('db_database', $settings['db_database']) }}"
                                       required placeholder="mi_base_de_datos" minlength="1" maxlength="255">
                                @error('db_database')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Nombre exacto de la base de datos</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('db_username') is-invalid @enderror"
                                       id="dbUsername" name="db_username"
                                       value="{{ old('db_username', $settings['db_username']) }}"
                                       required placeholder="root" minlength="1" maxlength="255">
                                @error('db_username')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Usuario con acceso a la base de datos</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <input type="password" class="form-control @error('db_password') is-invalid @enderror"
                                       id="dbPassword" name="db_password"
                                       value="{{ old('db_password', $settings['db_password']) }}"
                                       placeholder="Dejar en blanco si no hay contraseña" maxlength="255">
                                @error('db_password')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Opcional</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Charset <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('db_charset') is-invalid @enderror"
                                        id="dbCharset" name="db_charset" required>
                                    <option value=""></option>
                                    <option value="utf8" {{ old('db_charset', $settings['db_charset']) === 'utf8' ? 'selected' : '' }}>UTF-8</option>
                                    <option value="utf8mb4" {{ old('db_charset', $settings['db_charset']) === 'utf8mb4' ? 'selected' : '' }}>UTF-8 MB4 (Emojis)</option>
                                    <option value="latin1" {{ old('db_charset', $settings['db_charset']) === 'latin1' ? 'selected' : '' }}>Latin1 (ISO-8859-1)</option>
                                    <option value="ascii" {{ old('db_charset', $settings['db_charset']) === 'ascii' ? 'selected' : '' }}>ASCII</option>
                                </select>
                                @error('db_charset')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Conjunto de caracteres</small>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Colación <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('db_collation') is-invalid @enderror"
                                        id="dbCollation" name="db_collation" required>
                                    <option value=""></option>
                                    <option value="utf8_unicode_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8_unicode_ci' ? 'selected' : '' }}>utf8_unicode_ci</option>
                                    <option value="utf8mb4_unicode_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8mb4_unicode_ci' ? 'selected' : '' }}>utf8mb4_unicode_ci</option>
                                    <option value="utf8mb4_general_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8mb4_general_ci' ? 'selected' : '' }}>utf8mb4_general_ci</option>
                                    <option value="latin1_swedish_ci" {{ old('db_collation', $settings['db_collation']) === 'latin1_swedish_ci' ? 'selected' : '' }}>latin1_swedish_ci</option>
                                </select>
                                @error('db_collation')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @else
                                    <small class="form-text text-muted">Orden de comparación de caracteres</small>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-header border-bottom border-top p-3 bg-white">
                        <h6 class="mb-0 fw-bold">Limpieza de base de datos</h6>
                        <small class="text-muted">Permite vaciar tablas desde el panel de configuración</small>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="cleanupEnabled" name="cleanup_enabled" value="1"
                                   {{ old('cleanup_enabled', $settings['cleanup_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cleanupEnabled">
                                Habilitar limpieza de base de datos
                            </label>
                        </div>
                        <small class="text-muted">Solo usuarios con permisos pueden ejecutar esta operación.</small>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            Guardar configuración
                        </button>
                        <a href="{{ route('settings.database.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Puertos por defecto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">MySQL / MariaDB</span>
                            <code>3306</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">PostgreSQL</span>
                            <code>5432</code>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">SQLite</span>
                            <code>—</code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Charset recomendado</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">utf8mb4</span>
                            <span class="badge bg-success-subtle text-success">Recomendado</span>
                        </div>
                        <span class="text-muted mt-2">Soporte completo de Unicode, incluyendo emojis. Ideal para aplicaciones modernas.</span>
                    </div>
                    <hr class="my-3">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">utf8mb4_unicode_ci</span>
                            <span class="badge bg-success-subtle text-success">Colación sugerida</span>
                        </div>
                        <span class="text-muted mt-2">Comparación unicode estándar, compatible con la mayoría de idiomas.</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Seguridad</h5>
                </div>
                <div class="card-body">
                    <ul class="text-muted  mb-0">
                        <li class="mb-2">Usa un usuario con permisos mínimos necesarios, no <code>root</code> en producción.</li>
                        <li class="mb-2">La contraseña se almacena en el archivo <code>.env</code> del servidor.</li>
                        <li>Restringe el acceso al servidor de base de datos por IP si es posible.</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header p-3 bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Limpieza de tablas</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Al habilitar esta opción, los administradores con permisos podrán vaciar tablas específicas desde la sección de <strong>limpieza</strong>. Úsala con precaución en producción.</p>
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#dbConnection, #dbCharset, #dbCollation').select2({
        allowClear: false,
        language: {
            noResults: function () { return 'Sin resultados'; },
            searching: function () { return 'Buscando...'; }
        }
    });

    $('#formDatabase').validate({
        rules: {
            db_connection: { required: true },
            db_host: { required: true, minlength: 3, maxlength: 255 },
            db_port: { required: true, number: true, min: 1, max: 65535 },
            db_database: { required: true, minlength: 1, maxlength: 255 },
            db_username: { required: true, minlength: 1, maxlength: 255 },
            db_password: { maxlength: 255 },
            db_charset: { required: true },
            db_collation: { required: true },
        },
        messages: {
            db_connection: { required: 'Selecciona un tipo de conexión' },
            db_host: { required: 'El host es obligatorio', minlength: 'Mínimo 3 caracteres', maxlength: 'Máximo 255 caracteres' },
            db_port: { required: 'El puerto es obligatorio', number: 'Debe ser un número', min: 'Puerto mínimo es 1', max: 'Puerto máximo es 65535' },
            db_database: { required: 'El nombre de la base de datos es obligatorio' },
            db_username: { required: 'El usuario es obligatorio' },
            db_charset: { required: 'Selecciona un charset' },
            db_collation: { required: 'Selecciona una colación' },
        },
        errorClass: 'error',
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
            if (['dbConnection', 'dbCharset', 'dbCollation'].includes(element.id)) {
                $(element).next('.select2-container').find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
            }
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
            if (['dbConnection', 'dbCharset', 'dbCollation'].includes(element.id)) {
                $(element).next('.select2-container').find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
            }
        },
        errorPlacement: function (error, element) {
            error.addClass('field-validation-error');
            if (['dbConnection', 'dbCharset', 'dbCollation'].includes(element.attr('id'))) {
                error.insertAfter(element.next('.select2-container'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) { form.submit(); }
    });

    $('#dbConnection, #dbCharset, #dbCollation').on('change', function () { $(this).valid(); });
});
</script>
@endpush
