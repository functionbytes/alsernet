@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formDatabase" method="POST" action="{{ route('settings.database.update') }}" novalidate>

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Configuración de base de datos</h5>
                </div>
                <p class="card-subtitle mb-3 mt-0">
                    Este espacio está diseñado para que puedas configurar los parámetros de conexión a la base de datos.
                    Asegúrate de ingresar valores correctos ya que esto afectará el funcionamiento de toda la
                    aplicación.
                </p>

                <div class="row">

                    <!-- Connection Type -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de conexión
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2 @error('db_connection') is-invalid @enderror"
                                    id="dbConnection"
                                    name="db_connection"
                                    data-placeholder="Seleccionar tipo de conexión..."
                                    required>
                                <option value=""></option>
                                <option value="mysql" {{ old('db_connection', $settings['db_connection']) === 'mysql' ? 'selected' : '' }}>
                                    MySQL
                                </option>
                                <option value="pgsql" {{ old('db_connection', $settings['db_connection']) === 'pgsql' ? 'selected' : '' }}>
                                    PostgreSQL
                                </option>
                                <option value="sqlite" {{ old('db_connection', $settings['db_connection']) === 'sqlite' ? 'selected' : '' }}>
                                    SQLite
                                </option>
                            </select>
                            @error('db_connection')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Host -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Host/Servidor
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('db_host') is-invalid @enderror"
                                   id="dbHost"
                                   name="db_host"
                                   value="{{ old('db_host', $settings['db_host']) }}"
                                   required
                                   placeholder="ej: localhost o 192.168.1.1"
                                   minlength="3"
                                   maxlength="255">
                            @error('db_host')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Dirección IP o nombre del servidor</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Port -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Puerto
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   class="form-control @error('db_port') is-invalid @enderror"
                                   id="dbPort"
                                   name="db_port"
                                   value="{{ old('db_port', $settings['db_port']) }}"
                                   required
                                   placeholder="3306"
                                   min="1"
                                   max="65535">
                            @error('db_port')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Puerto de conexión (1-65535)</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Database Name -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre de la base de datos
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('db_database') is-invalid @enderror"
                                   id="dbDatabase"
                                   name="db_database"
                                   value="{{ old('db_database', $settings['db_database']) }}"
                                   required
                                   placeholder="ej: Alsernet_db"
                                   minlength="1"
                                   maxlength="255">
                            @error('db_database')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Nombre de la base de datos</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Usuario
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('db_username') is-invalid @enderror"
                                   id="dbUsername"
                                   name="db_username"
                                   value="{{ old('db_username', $settings['db_username']) }}"
                                   required
                                   placeholder="ej: root"
                                   minlength="1"
                                   maxlength="255">
                            @error('db_username')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Usuario de acceso a la base de datos</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Contraseña
                            </label>
                            <input type="password"
                                   class="form-control @error('db_password') is-invalid @enderror"
                                   id="dbPassword"
                                   name="db_password"
                                   value="{{ old('db_password', $settings['db_password']) }}"
                                   placeholder="Dejar en blanco si no hay contraseña"
                                   maxlength="255">
                            @error('db_password')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Opcional</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Charset -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Charset
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2 @error('db_charset') is-invalid @enderror"
                                    id="dbCharset"
                                    name="db_charset"
                                    data-placeholder="Seleccionar charset..."
                                    required>
                                <option value=""></option>
                                <option value="utf8" {{ old('db_charset', $settings['db_charset']) === 'utf8' ? 'selected' : '' }}>
                                    UTF-8
                                </option>
                                <option value="utf8mb4" {{ old('db_charset', $settings['db_charset']) === 'utf8mb4' ? 'selected' : '' }}>
                                    UTF-8 MB4 (Emojis)
                                </option>
                                <option value="latin1" {{ old('db_charset', $settings['db_charset']) === 'latin1' ? 'selected' : '' }}>
                                    Latin1 (ISO-8859-1)
                                </option>
                                <option value="ascii" {{ old('db_charset', $settings['db_charset']) === 'ascii' ? 'selected' : '' }}>
                                    ASCII
                                </option>
                            </select>
                            @error('db_charset')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Conjunto de caracteres</small>
                                @enderror
                        </div>
                    </div>

                    <!-- Collation -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Colación
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2 @error('db_collation') is-invalid @enderror"
                                    id="dbCollation"
                                    name="db_collation"
                                    data-placeholder="Seleccionar colación..."
                                    required>
                                <option value=""></option>
                                <option value="utf8_unicode_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8_unicode_ci' ? 'selected' : '' }}>
                                    utf8_unicode_ci
                                </option>
                                <option value="utf8mb4_unicode_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8mb4_unicode_ci' ? 'selected' : '' }}>
                                    utf8mb4_unicode_ci
                                </option>
                                <option value="utf8mb4_general_ci" {{ old('db_collation', $settings['db_collation']) === 'utf8mb4_general_ci' ? 'selected' : '' }}>
                                    utf8mb4_general_ci
                                </option>
                                <option value="latin1_swedish_ci" {{ old('db_collation', $settings['db_collation']) === 'latin1_swedish_ci' ? 'selected' : '' }}>
                                    latin1_swedish_ci
                                </option>
                            </select>
                            @error('db_collation')
                            <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Orden de comparación</small>
                                @enderror
                        </div>
                    </div>

                </div>


            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('settings.database.index') }}"
                   class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>


        </form>

    </div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            // ========== Inicializar Select2 ==========
            $('#dbConnection, #dbCharset, #dbCollation').select2({
                allowClear: false,
                language: {
                    noResults: function () {
                        return 'Sin resultados';
                    },
                    searching: function () {
                        return 'Buscando...';
                    }
                }
            });

            // ========== Validar formulario con jQuery Validate ==========
            $('#formDatabase').validate({
                rules: {
                    db_connection: {
                        required: true
                    },
                    db_host: {
                        required: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    db_port: {
                        required: true,
                        number: true,
                        min: 1,
                        max: 65535
                    },
                    db_database: {
                        required: true,
                        minlength: 1,
                        maxlength: 255
                    },
                    db_username: {
                        required: true,
                        minlength: 1,
                        maxlength: 255
                    },
                    db_password: {
                        maxlength: 255
                    },
                    db_charset: {
                        required: true
                    },
                    db_collation: {
                        required: true
                    }
                },
                messages: {
                    db_connection: {
                        required: 'Selecciona un tipo de conexión'
                    },
                    db_host: {
                        required: 'El host es obligatorio',
                        minlength: 'Mínimo 3 caracteres',
                        maxlength: 'Máximo 255 caracteres'
                    },
                    db_port: {
                        required: 'El puerto es obligatorio',
                        number: 'Debe ser un número válido',
                        min: 'Puerto mínimo es 1',
                        max: 'Puerto máximo es 65535'
                    },
                    db_database: {
                        required: 'El nombre de la base de datos es obligatorio',
                        minlength: 'Ingresa un nombre válido',
                        maxlength: 'Máximo 255 caracteres'
                    },
                    db_username: {
                        required: 'El usuario es obligatorio',
                        minlength: 'Ingresa un usuario válido',
                        maxlength: 'Máximo 255 caracteres'
                    },
                    db_password: {
                        maxlength: 'Máximo 255 caracteres'
                    },
                    db_charset: {
                        required: 'Selecciona un charset'
                    },
                    db_collation: {
                        required: 'Selecciona una colación'
                    }
                },
                errorClass: 'error',
                highlight: function (element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');

                    // Para Select2
                    if (element.id === 'dbConnection' || element.id === 'dbCharset' || element.id === 'dbCollation') {
                        $(element).next('.select2-container').find('.select2-selection')
                            .addClass('is-invalid')
                            .removeClass('is-valid');
                    }
                },
                unhighlight: function (element) {
                    $(element).removeClass('is-invalid').addClass('is-valid');

                    // Para Select2
                    if (element.id === 'dbConnection' || element.id === 'dbCharset' || element.id === 'dbCollation') {
                        $(element).next('.select2-container').find('.select2-selection')
                            .removeClass('is-invalid')
                            .addClass('is-valid');
                    }
                },
                errorPlacement: function (error, element) {
                    error.addClass('field-validation-error');
                    error.insertAfter(element);

                    // Para Select2, colocar error después del contenedor
                    if (element.attr('id') === 'dbConnection' || element.attr('id') === 'dbCharset' || element.attr('id') === 'dbCollation') {
                        error.insertAfter(element.next('.select2-container'));
                    }
                },
                submitHandler: function (form) {
                    form.submit();
                }
            });

            // ========== Validar Select2 al cambiar ==========
            $('#dbConnection, #dbCharset, #dbCollation').on('change', function () {
                $(this).valid();
            });
        });
    </script>
@endsection
