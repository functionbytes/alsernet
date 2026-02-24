@extends('layouts.theme')

@section('title', 'Crear redireccion')

@section('content')
    @include('core::components.card', ['title' => 'Crear redireccion'])

    <div class="row">
        <!-- Formulario principal -->
        <div class="col-lg-8">
            <div class="card w-100">
                <form id="formRedirect" action="{{ route('setting.seo.redirects.store') }}" method="POST" novalidate>
                    @csrf

                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear redireccion</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-0">
                            Configure una nueva redireccion URL para mejorar el SEO del sitio.
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
                                   value="{{ old('source_path') }}"
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
                                   value="{{ old('target_path') }}"
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
                                <option value="301" {{ old('status_code', '301') == '301' ? 'selected' : '' }}>301 - Permanente</option>
                                <option value="302" {{ old('status_code') == '302' ? 'selected' : '' }}>302 - Temporal</option>
                                <option value="307" {{ old('status_code') == '307' ? 'selected' : '' }}>307 - Temporal (preserva metodo)</option>
                                <option value="308" {{ old('status_code') == '308' ? 'selected' : '' }}>308 - Permanente (preserva metodo)</option>
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
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactivo</option>
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
                            Crear redireccion
                        </button>
                        <a href="{{ route('setting.seo.redirects.index') }}"
                           class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                            Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel informativo -->
        <div class="col-lg-4">
            <!-- Guia rapida -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-info-circle text-primary me-2"></i>Guía rápida
                    </h5>
                    <p class="card-text  text-muted">
                        Las redirecciones permiten enviar automáticamente a los visitantes y motores de búsqueda de una URL antigua a una nueva.
                    </p>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Tipos de redireccion
                    </h6>

                    <div class="mb-3">
                        <span class="badge bg-info-subtle text-info mb-2">301 - Permanente</span>
                        <p class="text-muted mb-0">
                            Indica que la página se ha movido permanentemente. Transfiere el 90-99% del valor SEO. <strong>Recomendado para cambios definitivos.</strong>
                        </p>
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-info-subtle text-info mb-2">302 - Temporal</span>
                        <p class="text-muted mb-0">
                            Indica que la página se ha movido temporalmente. No transfiere valor SEO. Útil para mantenimiento o pruebas.
                        </p>
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-warning-subtle text-warning mb-2">307 - Temporal (preserva método)</span>
                        <p class="text-muted mb-0">
                            Similar a 302 pero garantiza que el método HTTP (POST, GET) se mantenga.
                        </p>
                    </div>

                    <div class="mb-0">
                        <span class="badge bg-primary-subtle text-primary mb-2">308 - Permanente (preserva método)</span>
                        <p class="text-muted mb-0">
                            Similar a 301 pero garantiza que el método HTTP se mantenga.
                        </p>
                    </div>
                </div>

                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                       Ejemplos de uso
                    </h6>

                    <div class="mb-3">
                        <strong class="small d-block mb-1">Cambio de estructura:</strong>
                        <code class="d-block small text-primary mb-1">/blog/articulo-viejo</code>
                        <i class="fas fa-arrow-down  text-muted"></i>
                        <code class="d-block  text-muted">/articulos/articulo-nuevo</code>
                    </div>

                    <div class="mb-3">
                        <strong class="small d-block mb-1">Redireccion externa:</strong>
                        <code class="d-block small text-primary mb-1">/contacto</code>
                        <i class="fas fa-arrow-down  text-muted"></i>
                        <code class="d-block  text-muted">https://ejemplo.com/contacto</code>
                    </div>

                    <div class="mb-0">
                        <strong class="small d-block mb-1">Página eliminada:</strong>
                        <code class="d-block small text-primary mb-1">/producto-descontinuado</code>
                        <i class="fas fa-arrow-down  text-muted"></i>
                        <code class="d-block  text-muted">/productos</code>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Mejores prácticas
                    </h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Usa 301 para cambios permanentes de contenido</li>
                        <li class="mb-2">Evita cadenas de redirecciones (A → B → C)</li>
                        <li class="mb-2">Redirige a contenido similar o relevante</li>
                        <li class="mb-2">Mantén las redirecciones activas al menos 1 año</li>
                        <li class="mb-0">Revisa periódicamente las redirecciones obsoletas</li>
                    </ul>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3 text-warning">
                        Advertencias
                    </h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Las redirecciones incorrectas pueden afectar el SEO negativamente</li>
                        <li class="mb-2">No redirijas todas las páginas a la home page</li>
                        <li class="mb-0">Verifica que la ruta destino existe antes de crear la redireccion</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
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
        });
    </script>
@endsection
