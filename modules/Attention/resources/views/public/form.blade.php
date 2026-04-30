@extends('attention::layouts.public')

@section('title', 'Radicar peticiones')

@section('content')

    <div class="row">
        <div class="col-lg-8">

            {{-- Encabezado --}}
            <div class="text-center mb-4">
                <h2 class="fw-bold" style="color: #1a2030;">Radicar solicitud peticiones</h2>
                <p class="text-muted">
                    Complete el siguiente formulario para radicar una Peticion, Queja, Reclamo, Sugerencia o Felicitacion.
                    <br>Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </p>
            </div>

            {{-- Step Indicator --}}
            <div class="step-indicator d-none d-lg-flex mb-4">
                <div class="step-item active">
                    <span class="step-circle">1</span>
                    <span class="step-label">Clasificacion</span>
                </div>
                <div class="step-item">
                    <span class="step-circle">2</span>
                    <span class="step-label">Ciudadano</span>
                </div>
                <div class="step-item">
                    <span class="step-circle">3</span>
                    <span class="step-label">Solicitud</span>
                </div>
                <div class="step-item">
                    <span class="step-circle">4</span>
                    <span class="step-label">Adjuntos</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form id="formpeticiones" method="POST" action="{{ route('peticiones.submit') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Tipo, Categoria, Sede --}}
                        <div class="section-header mb-3">
                            <h5 class="fw-bold mb-1">Clasificacion de la solicitud</h5>
                            <p class="text-muted mb-0">Seleccione el tipo y categoria que mejor describa su solicitud</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label" for="type_id">Tipo de peticiones <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="type_id" id="type_id" required>
                                    <option value="">Seleccione un tipo</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="category_id">Categoria <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="category_id" id="category_id" required>
                                    <option value="">Seleccione una categoria</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sede_id">Sede</label>
                                <select class="form-select select2" name="sede_id" id="sede_id">
                                    <option value="">Seleccione una sede</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('sede_id') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sede_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="section-divider my-4"></div>

                        {{-- Informacion del ciudadano --}}
                        <div class="section-header mb-3">
                            <h5 class="fw-bold mb-1">Informacion del ciudadano</h5>
                            <p class="text-muted mb-0">Proporcione sus datos de contacto para recibir respuesta</p>
                        </div>

                        <div class="alert alert-info border-0 mb-4" style="background-color: #e8f4fd;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous"
                                       value="1" {{ old('is_anonymous') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_anonymous">
                                    <i class="fas fa-user-secret me-2"></i>
                                    <strong>Solicitud anonima</strong> — Marque esta opcion si desea mantener su identidad privada
                                </label>
                            </div>
                        </div>

                        <div id="citizenInfo">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_firstname">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('customer_firstname') is-invalid @enderror"
                                           name="customer_firstname" id="customer_firstname"
                                           value="{{ old('customer_firstname') }}" placeholder="Ingrese nombres">
                                    @error('customer_firstname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_lastname">Apellidos <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('customer_lastname') is-invalid @enderror"
                                           name="customer_lastname" id="customer_lastname"
                                           value="{{ old('customer_lastname') }}" placeholder="Ingrese apellidos">
                                    @error('customer_lastname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_dni">Documento de identidad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('customer_dni') is-invalid @enderror"
                                           name="customer_dni" id="customer_dni"
                                           value="{{ old('customer_dni') }}" placeholder="Ingrese documento">
                                    @error('customer_dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_email">Correo electronico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('customer_email') is-invalid @enderror"
                                           name="customer_email" id="customer_email"
                                           value="{{ old('customer_email') }}" placeholder="correo@ejemplo.com">
                                    @error('customer_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_cellphone">Telefono / Celular</label>
                                    <input type="text" class="form-control @error('customer_cellphone') is-invalid @enderror"
                                           name="customer_cellphone" id="customer_cellphone"
                                           value="{{ old('customer_cellphone') }}" placeholder="3001234567">
                                    @error('customer_cellphone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_address">Direccion</label>
                                    <input type="text" class="form-control @error('customer_address') is-invalid @enderror"
                                           name="customer_address" id="customer_address"
                                           value="{{ old('customer_address') }}" placeholder="Ingrese direccion">
                                    @error('customer_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="section-divider my-4"></div>

                        {{-- Contenido de la solicitud --}}
                        <div class="section-header mb-3">
                            <h5 class="fw-bold mb-1">Contenido de la solicitud</h5>
                            <p class="text-muted mb-0">Describa de forma clara y detallada su peticion, queja, reclamo, sugerencia o felicitacion</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label" for="subject">Asunto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                       name="subject" id="subject"
                                       value="{{ old('subject') }}" placeholder="Resuma brevemente su solicitud" required>
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Descripcion detallada <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description" id="description" rows="5"
                                          placeholder="Describa detalladamente su solicitud (minimo 20 caracteres)" required>{{ old('description') }}</textarea>
                                <div class="form-text">Sea lo mas especifico posible para que podamos atender mejor su solicitud.</div>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="section-divider my-4"></div>

                        {{-- Tipo de respuesta --}}
                        <div class="section-header mb-3">
                            <h5 class="fw-bold mb-1">Tipo de respuesta preferido</h5>
                            <p class="text-muted mb-0">Seleccione como desea recibir la respuesta a su solicitud</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="response-type-grid">
                                    <label class="response-type-card">
                                        <input type="radio" name="response_type" value="email" {{ old('response_type', 'email') === 'email' ? 'checked' : '' }}>
                                        <div class="response-type-content">
                                            <i class="fas fa-envelope"></i>
                                            <span>Correo electronico</span>
                                        </div>
                                    </label>
                                    <label class="response-type-card">
                                        <input type="radio" name="response_type" value="presencial" {{ old('response_type') === 'presencial' ? 'checked' : '' }}>
                                        <div class="response-type-content">
                                            <i class="fas fa-building"></i>
                                            <span>Presencial</span>
                                        </div>
                                    </label>
                                    <label class="response-type-card">
                                        <input type="radio" name="response_type" value="correo_fisico" {{ old('response_type') === 'correo_fisico' ? 'checked' : '' }}>
                                        <div class="response-type-content">
                                            <i class="fas fa-mailbox"></i>
                                            <span>Correo fisico</span>
                                        </div>
                                    </label>
                                    <label class="response-type-card">
                                        <input type="radio" name="response_type" value="telefono" {{ old('response_type') === 'telefono' ? 'checked' : '' }}>
                                        <div class="response-type-content">
                                            <i class="fas fa-phone"></i>
                                            <span>Telefono</span>
                                        </div>
                                    </label>
                                    <label class="response-type-card">
                                        <input type="radio" name="response_type" value="no_requiere" {{ old('response_type') === 'no_requiere' ? 'checked' : '' }}>
                                        <div class="response-type-content">
                                            <i class="fas fa-ban"></i>
                                            <span>No requiere respuesta</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider my-4"></div>

                        {{-- Documentos adjuntos --}}
                        <div class="section-header mb-3">
                            <h5 class="fw-bold mb-1">Documentos adjuntos <small class="text-muted fw-normal">(Opcional)</small></h5>
                            <p class="text-muted mb-0">Adjunte documentos que respalden su solicitud</p>
                        </div>

                        <div class="mb-4">
                            <div class="file-upload-area">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                <p class="mb-2"><strong>Haga clic o arrastre archivos aqui</strong></p>
                                <p class="text-muted mb-0">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, ZIP, RAR</p>
                                <p class="text-muted">Maximo 5 archivos, 10MB cada uno</p>
                                <input type="file" class="form-control file-upload-input @error('attachments.*') is-invalid @enderror"
                                       name="attachments[]" id="attachments" multiple
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
                            </div>
                            @error('attachments.*')
                                <div class="text-danger mt-2">
                                    <small><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</small>
                                </div>
                            @enderror
                            @error('attachments')
                                <div class="text-danger mt-2">
                                    <small><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</small>
                                </div>
                            @enderror
                        </div>

                        <div class="section-divider my-4"></div>

                        {{-- Captcha --}}
                        @if(\Modules\Captcha\Facades\Captcha::isEnabled() && config('attention.captcha.enabled', false))
                        <div class="mb-4">
                            <label class="form-label fw-bold">Verificacion de seguridad <span class="text-danger">*</span></label>
                            <div class="mt-2">
                                {!! \Modules\Captcha\Facades\Captcha::display() !!}
                            </div>
                            @error('g-recaptcha-response')
                                <div class="text-danger mt-2">
                                    <small><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</small>
                                </div>
                            @enderror
                        </div>
                        @endif

                        {{-- Submit --}}
                        <div class="submit-section">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-paper-plane me-2"></i>Radicar solicitud
                                </button>
                                <a href="{{ route('peticiones.tracking') }}" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="fas fa-magnifying-glass me-2"></i>Consultar estado
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

        </div>

        {{-- Sidebar (Desktop only) --}}
        <div class="col-lg-4 d-none d-lg-block">
            <div class="sticky-top" style="top: 6rem;">

                {{-- Info Panel --}}
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-circle-info text-primary me-2"></i>¿Que es una peticiones?
                        </h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <strong class="text-primary">Peticion:</strong> Solicitud de informacion o de acceso a un servicio.
                            </li>
                            <li class="mb-2">
                                <strong class="text-primary">Queja:</strong> Manifestacion de inconformidad por un mal servicio o atencion.
                            </li>
                            <li class="mb-2">
                                <strong class="text-primary">Reclamo:</strong> Solicitud de correccion o solucion de un problema.
                            </li>
                            <li class="mb-2">
                                <strong class="text-primary">Sugerencia:</strong> Propuesta para mejorar el servicio.
                            </li>
                            <li class="mb-0">
                                <strong class="text-primary">Felicitacion:</strong> Reconocimiento por un buen servicio.
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Response Times --}}
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-clock text-success me-2"></i>Tiempos de respuesta
                        </h6>
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-bolt text-success"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 small"><strong>Peticiones</strong></p>
                                <p class="mb-0 text-muted">Hasta 15 dias habiles</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-message text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 small"><strong>Quejas y Reclamos</strong></p>
                                <p class="mb-0 text-muted">Hasta 15 dias habiles</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px;">
                                    <i class="fas fa-lightbulb text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 small"><strong>Sugerencias</strong></p>
                                <p class="mb-0 text-muted">Evaluacion y respuesta</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Help --}}
                <div class="alert alert-light border mb-0">
                    <p class="small mb-2"><i class="fas fa-question-circle text-primary me-2"></i><strong>¿Necesita ayuda?</strong></p>
                    <p class="text-muted mb-0">
                        Si tiene dudas sobre como radicar su solicitud, puede comunicarse con nuestra linea de atencion.
                    </p>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* Section Headers with Left Accent */
        .section-header {
            padding-left: 1rem;
            border-left: 4px solid #b10100;
            background: #b10100 0%, transparent 100%);
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .section-header h5 {
            color: #1a2030;
        }

        /* Section Divider */
        .section-divider {
            height: 1px;
            background: #b10100;
            margin: 2rem 0;
        }

        /* Response Type Grid */
        .response-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }

        .response-type-card {
            cursor: pointer;
            position: relative;
        }

        .response-type-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .response-type-content {
            padding: 1.25rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.75rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #fff;
        }

        .response-type-content i {
            display: block;
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .response-type-content span {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #495057;
        }

        .response-type-card:hover .response-type-content {
            border-color: #b10100;
            box-shadow: 0 4px 12px rgba(144, 187, 19, 0.15);
        }

        .response-type-card input[type="radio"]:checked ~ .response-type-content {
            border-color: #b10100;
            background: rgba(144, 187, 19, 0.05);
        }

        .response-type-card input[type="radio"]:checked ~ .response-type-content i {
            color: #b10100;
        }

        .response-type-card input[type="radio"]:checked ~ .response-type-content span {
            color: #b10100;
        }

        /* File Upload Area */
        .file-upload-area {
            position: relative;
            border: 2px dashed #d0d5dd;
            border-radius: 0.75rem;
            padding: 2.5rem;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #b10100;
            background: rgba(144, 187, 19, 0.02);
        }

        .file-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        /* Submit Section */
        .submit-section {
            background: #b10100;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-top: 2rem;
        }

        /* Enhanced Form Controls */
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #d0d5dd;
            padding: 0.625rem 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control:hover, .form-select:hover {
            border-color: #b10100;
        }

        textarea.form-control {
            resize: vertical;
        }

        /* Sidebar Styling */
        .sticky-top {
            transition: top 0.3s ease;
        }

        @media (max-width: 991px) {
            .response-type-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .response-type-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var anonymousCheckbox = document.getElementById('is_anonymous');
            var citizenInfo = document.getElementById('citizenInfo');
            var requiredFields = citizenInfo.querySelectorAll('#customer_firstname, #customer_lastname, #customer_email');

            function toggleCitizenInfo() {
                if (anonymousCheckbox.checked) {
                    citizenInfo.style.display = 'none';
                    requiredFields.forEach(function(field) { field.removeAttribute('required'); });
                } else {
                    citizenInfo.style.display = '';
                    requiredFields.forEach(function(field) { field.setAttribute('required', 'required'); });
                }
            }

            anonymousCheckbox.addEventListener('change', toggleCitizenInfo);
            toggleCitizenInfo();
        });
    </script>
@endpush
