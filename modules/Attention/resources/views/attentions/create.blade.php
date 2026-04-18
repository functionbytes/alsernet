@extends('layouts.theme')

@section('title', 'Radicar nuevo peticiones')

@section('content')

    @include('core::components.card', ['title' => 'Radicar nuevo peticiones'])

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formAttention" enctype="multipart/form-data" method="POST" action="{{ route('attention.store') }}">
                    @csrf

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center mb-4">
                            <h5 class="mb-0">Formulario de Radicación peticiones</h5>
                        </div>
                        <p class="card-subtitle mb-3">
                            Complete el siguiente formulario para radicar una Petición, Queja, Reclamo, Sugerencia o Felicitación.
                            Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                        </p>

                        <!-- Tipo y Categoría -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tipo de peticiones <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="type_id" id="type_id" required>
                                        <option value="">Seleccione un tipo</option>
                                        @foreach($types ?? [] as $type)
                                            <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="category_id" id="category_id" required>
                                        <option value="">Seleccione una categoría</option>
                                        @foreach($categories ?? [] as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Sede <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="sede_id" id="sede_id" required>
                                        <option value="">Seleccione una sede</option>
                                        @foreach($sedes ?? [] as $sede)
                                            <option value="{{ $sede->id }}" {{ old('sede_id') == $sede->id ? 'selected' : '' }}>
                                                {{ $sede->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sede_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Información del Ciudadano -->
                        <h5 class="mb-3">Información del Ciudadano</h5>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous"
                                           value="1" {{ old('is_anonymous') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_anonymous">
                                        <strong>Solicitud Anónima</strong> - Marque esta opción si desea mantener su identidad privada
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="citizenInfo">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Nombres <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_firstname" id="customer_firstname"
                                               value="{{ old('customer_firstname') }}" placeholder="Ingrese nombres">
                                        @error('customer_firstname')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_lastname" id="customer_lastname"
                                               value="{{ old('customer_lastname') }}" placeholder="Ingrese apellidos">
                                        @error('customer_lastname')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Documento de Identidad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_dni" id="customer_dni"
                                               value="{{ old('customer_dni') }}" placeholder="Ingrese documento">
                                        @error('customer_dni')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="customer_email" id="customer_email"
                                               value="{{ old('customer_email') }}" placeholder="correo@ejemplo.com">
                                        @error('customer_email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Teléfono/Celular <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_cellphone" id="customer_cellphone"
                                               value="{{ old('customer_cellphone') }}" placeholder="Ingrese teléfono">
                                        @error('customer_cellphone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Dirección</label>
                                        <input type="text" class="form-control" name="customer_address" id="customer_address"
                                               value="{{ old('customer_address') }}" placeholder="Ingrese dirección">
                                        @error('customer_address')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Contenido de la Solicitud -->
                        <h5 class="mb-3">Contenido de la Solicitud</h5>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Asunto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="subject" id="subject"
                                           value="{{ old('subject') }}" placeholder="Resuma brevemente su solicitud" required>
                                    @error('subject')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción Detallada <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="description" id="description" rows="6"
                                              placeholder="Describa detalladamente su solicitud" required>{{ old('description') }}</textarea>
                                    <small class="text-muted">
                                        Sea lo más específico posible para que podamos atender mejor su solicitud.
                                    </small>
                                    @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Documentos Adjuntos -->
                        <h5 class="mb-3">Documentos Adjuntos (Opcional)</h5>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Adjuntar Archivos</label>
                                    <input type="file" class="form-control" name="attachments[]" id="attachments" multiple
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <small class="text-muted">
                                        Puede adjuntar documentos que soporten su solicitud. Formatos permitidos: PDF, DOC, DOCX, JPG, PNG. Tamaño máximo: 5MB por archivo.
                                    </small>
                                    @error('attachments.*')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="border-top pt-3 mt-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary px-4 waves-effect waves-light">
                                            <i class="fa-duotone fa-save"></i> Radicar peticiones
                                        </button>
                                        <a href="{{ route('attention.index') }}" class="btn btn-outline-secondary px-4">
                                            <i class="fa-duotone fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5'
            });

            // Handle anonymous checkbox
            $('#is_anonymous').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#citizenInfo').slideUp();
                    $('#citizenInfo input').prop('required', false);
                } else {
                    $('#citizenInfo').slideDown();
                    $('#customer_firstname, #customer_lastname, #customer_dni, #customer_email, #customer_cellphone').prop('required', true);
                }
            });

            // Trigger change on page load to set initial state
            $('#is_anonymous').trigger('change');

            // Form validation
            $('#formAttention').on('submit', function(e) {
                let valid = true;

                // Validate required fields
                $(this).find('[required]').each(function() {
                    if (!$(this).val()) {
                        valid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Por favor complete todos los campos obligatorios',
                    });
                }
            });

            // Remove invalid class on input
            $('input, select, textarea').on('change', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
@endpush
