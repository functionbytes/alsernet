@extends('layouts.theme')

@section('title', 'Editar redireccion')

@section('content')
    @include('core::components.card', ['title' => 'Editar redireccion'])

    <div class="row">
        <!-- Formulario principal -->
        <div class="col-lg-8">
            <div class="card w-100">
                <form id="formRedirect" action="{{ route('setting.seo.redirects.update', $redirect) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar redireccion</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-0">
                            Actualice la configuracion de la redireccion.
                        </p>

                        @include('core::components.alerts')

                        <div class="row">
                    <!-- Ruta origen -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Ruta origen
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('source_path') is-invalid @enderror"
                                   id="sourcePath"
                                   name="source_path"
                                   value="{{ old('source_path', $redirect->source_path) }}"
                                   required
                                   placeholder="ej: /pagina-antigua o /blog/post-viejo"
                                   minlength="1"
                                   maxlength="255">
                            @error('source_path')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">La ruta desde la que se redirige</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Ruta destino -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Ruta destino
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('target_path') is-invalid @enderror"
                                   id="targetPath"
                                   name="target_path"
                                   value="{{ old('target_path', $redirect->target_path) }}"
                                   required
                                   placeholder="ej: /pagina-nueva o https://ejemplo.com/pagina"
                                   minlength="1"
                                   maxlength="255">
                            @error('target_path')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">La ruta o URL completa de destino</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Tipo de redireccion -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de redireccion
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2 @error('status_code') is-invalid @enderror"
                                    id="statusCode"
                                    name="status_code"
                                    data-placeholder="Seleccionar tipo de redireccion..."
                                    required>
                                <option value=""></option>
                                <option value="301" {{ old('status_code', $redirect->status_code) == '301' ? 'selected' : '' }}>301 - Permanente</option>
                                <option value="302" {{ old('status_code', $redirect->status_code) == '302' ? 'selected' : '' }}>302 - Temporal</option>
                                <option value="307" {{ old('status_code', $redirect->status_code) == '307' ? 'selected' : '' }}>307 - Temporal (preserva metodo)</option>
                                <option value="308" {{ old('status_code', $redirect->status_code) == '308' ? 'selected' : '' }}>308 - Permanente (preserva metodo)</option>
                            </select>
                            @error('status_code')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">301/308 para cambios definitivos, 302/307 para temporales</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Estado
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('is_active') is-invalid @enderror"
                                    id="isActive"
                                    name="is_active"
                                    required>
                                <option value="1" {{ old('is_active', $redirect->is_active) == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active', $redirect->is_active) == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('is_active')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @else
                                <small class="form-text text-muted">Solo las redirecciones activas se aplican</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                            Guardar cambios
                        </button>
                        <a href="{{ route('setting.seo.redirects.index') }}"
                           class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                            Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel lateral -->
        <div class="col-lg-4">
            <!-- Estadisticas de uso -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-chart-line text-primary me-2"></i>Estadísticas de uso
                    </h6>
                    <p class="small text-muted mb-3">
                        Información sobre el uso y rendimiento de esta redireccion.
                    </p>

                    <div class="text-center mb-3 pb-3 border-bottom">
                        <h3 class="mb-0 fw-bold text-primary">{{ number_format($redirect->hits_count) }}</h3>
                        <small class="text-muted">Total de visitas</small>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-6 border-end">
                            <span class="badge {{ $redirect->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} mb-2">
                                {{ $redirect->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            <br><small class="text-muted">Estado</small>
                        </div>
                        <div class="col-6">
                            <span class="badge {{ $redirect->isPermanent() ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }} mb-2">
                                {{ $redirect->status_code }}
                            </span>
                            <br><small class="text-muted">Código</small>
                        </div>
                    </div>

                    <div class="small">
                        <div class="mb-2">
                            <span class="text-muted">Creado:</span><br>
                            <strong>{{ $redirect->created_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $redirect->created_at->diffForHumans() }}</small>
                        </div>
                        <div>
                            <span class="text-muted">Última actualización:</span><br>
                            <strong>{{ $redirect->updated_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $redirect->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guia rapida -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-info-circle text-info me-2"></i>Tipos de redireccion
                    </h6>

                    <div class="mb-3">
                        <span class="badge bg-success-subtle text-success mb-2">301 - Permanente</span>
                        <p class="small text-muted mb-0">
                            Indica cambio permanente. Transfiere el 90-99% del valor SEO.
                        </p>
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-info-subtle text-info mb-2">302 - Temporal</span>
                        <p class="small text-muted mb-0">
                            Indica cambio temporal. No transfiere valor SEO.
                        </p>
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-warning-subtle text-warning mb-2">307 - Temporal (preserva método)</span>
                        <p class="small text-muted mb-0">
                            Similar a 302 pero mantiene el método HTTP.
                        </p>
                    </div>

                    <div class="mb-0">
                        <span class="badge bg-primary-subtle text-primary mb-2">308 - Permanente (preserva método)</span>
                        <p class="small text-muted mb-0">
                            Similar a 301 pero mantiene el método HTTP.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Zona de peligro -->
            <div class="card border-danger">
                <div class="card-body bg-danger-subtle">
                    <h6 class="mb-2 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Zona de peligro
                    </h6>
                    <p class="small text-muted mb-3">
                        Eliminar esta redireccion no se puede deshacer. Se perderán todas las estadísticas.
                    </p>
                    <button type="button" class="btn btn-danger w-100 delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('setting.seo.redirects.destroy', $redirect) }}"
                            data-title="Eliminar: {{ $redirect->source_path }}">
                        <i class="fas fa-trash me-2"></i>Eliminar redireccion
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#statusCode').select2({
                allowClear: false,
                language: {
                    noResults: function() {
                        return 'Sin resultados';
                    },
                    searching: function() {
                        return 'Buscando...';
                    }
                }
            });

            // Validar formulario con jQuery Validate
            $('#formRedirect').validate({
                rules: {
                    source_path: {
                        required: true,
                        minlength: 1,
                        maxlength: 255
                    },
                    target_path: {
                        required: true,
                        minlength: 1,
                        maxlength: 255
                    },
                    status_code: {
                        required: true
                    },
                    is_active: {
                        required: true
                    }
                },
                messages: {
                    source_path: {
                        required: 'La ruta origen es obligatoria',
                        minlength: 'Ingresa una ruta válida',
                        maxlength: 'Máximo 255 caracteres'
                    },
                    target_path: {
                        required: 'La ruta destino es obligatoria',
                        minlength: 'Ingresa una ruta válida',
                        maxlength: 'Máximo 255 caracteres'
                    },
                    status_code: {
                        required: 'Selecciona un tipo de redireccion'
                    },
                    is_active: {
                        required: 'Selecciona un estado'
                    }
                },
                errorClass: 'error',
                highlight: function(element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');

                    // Para Select2
                    if (element.id === 'statusCode') {
                        $(element).next('.select2-container').find('.select2-selection')
                            .addClass('is-invalid')
                            .removeClass('is-valid');
                    }
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid').addClass('is-valid');

                    // Para Select2
                    if (element.id === 'statusCode') {
                        $(element).next('.select2-container').find('.select2-selection')
                            .removeClass('is-invalid')
                            .addClass('is-valid');
                    }
                },
                errorPlacement: function(error, element) {
                    error.addClass('field-validation-error');

                    // Para Select2, colocar error después del contenedor
                    if (element.attr('id') === 'statusCode') {
                        error.insertAfter(element.next('.select2-container'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });

            // Validar Select2 al cambiar
            $('#statusCode').on('change', function() {
                $(this).valid();
            });

            // Manejo del modal de eliminación
            $('.delete-btn').on('click', function() {
                $('#delete-modal .modal-title').text($(this).data('title'));
                $('#delete-form').attr('action', $(this).data('url'));
            });
        });
    </script>
@endsection
