@extends('template::layouts.default')

@section('title', 'Radicar PQRSF')
@section('description', 'Radica tu Peticion, Queja, Reclamo, Sugerencia o Felicitacion')

@section('content')


<section class="py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2">Radicar solicitud PQRSF</h1>
            <p class="text-muted">
                Complete el formulario para radicar una Peticion, Queja, Reclamo, Sugerencia o Felicitacion.<br>
                Los campos marcados con <span class="text-danger">*</span> son obligatorios.
            </p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button aria-label="Cerrar" type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">

            {{-- Formulario principal --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form id="formPqrsf" method="POST" action="{{ route('pqrsf.submit') }}" enctype="multipart/form-data">
                            @csrf

                            {{-- Clasificacion --}}
                            <div class="section-header mb-3">
                                <h5 class="fw-bold mb-1">Clasificacion de la solicitud</h5>
                                <p class="text-muted small mb-0">Seleccione el tipo y categoria que mejor describa su solicitud</p>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label" for="type_id">Tipo de PQRSF <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type_id" id="type_id" required>
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
                                    <select class="form-select" name="category_id" id="category_id" required>
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
                                    <select class="form-select" name="sede_id" id="sede_id">
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

                            <hr class="my-4">

                            {{-- Informacion del ciudadano --}}
                            <div class="section-header mb-3">
                                <h5 class="fw-bold mb-1">Informacion del ciudadano</h5>
                                <p class="text-muted small mb-0">Proporcione sus datos de contacto para recibir respuesta</p>
                            </div>

                            <div class="alert alert-info border-0 mb-4 bg-alert-info">
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

                            <hr class="my-4">

                            {{-- Contenido de la solicitud --}}
                            <div class="section-header mb-3">
                                <h5 class="fw-bold mb-1">Contenido de la solicitud</h5>
                                <p class="text-muted small mb-0">Describa de forma clara y detallada su peticion, queja, reclamo, sugerencia o felicitacion</p>
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

                            <hr class="my-4">

                            {{-- Tipo de respuesta --}}
                            <div class="section-header mb-3">
                                <h5 class="fw-bold mb-1">Tipo de respuesta preferido</h5>
                                <p class="text-muted small mb-0">Seleccione como desea recibir la respuesta a su solicitud</p>
                            </div>

                            <div class="mb-4">
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
                                            <i class="fas fa-envelope-open"></i>
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

                            <hr class="my-4">

                            {{-- Adjuntos --}}
                            <div class="section-header mb-3">
                                <h5 class="fw-bold mb-1">Documentos adjuntos <small class="text-muted fw-normal">(Opcional)</small></h5>
                                <p class="text-muted small mb-0">Adjunte documentos que respalden su solicitud</p>
                            </div>

                            <div class="mb-4">
                                <div class="file-upload-area">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                    <p class="mb-1"><strong>Haga clic o arrastre archivos aqui</strong></p>
                                    <p class="text-muted small mb-0">PDF, DOC, DOCX, JPG, PNG, GIF, TXT, ZIP, RAR</p>
                                    <p class="text-muted small">Maximo 5 archivos, 10MB cada uno</p>
                                    <input type="file" class="file-upload-input @error('attachments.*') is-invalid @enderror"
                                           name="attachments[]" id="attachments" multiple
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
                                </div>
                                @error('attachments.*')
                                    <div class="text-danger mt-2 small">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                @error('attachments')
                                    <div class="text-danger mt-2 small">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <hr class="my-4">

                            {{-- Captcha --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Verificacion de seguridad <span class="text-danger">*</span></label>
                                <div class="mt-2">
                                    {!! Captcha::display() !!}
                                </div>
                                @error('g-recaptcha-response')
                                    <div class="text-danger mt-2 small">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="submit-section">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-paper-plane me-2"></i>Radicar solicitud
                                    </button>
                                    <a href="{{ route('pqrsf.tracking') }}" class="btn btn-outline-secondary btn-lg px-4">
                                        <i class="fas fa-magnifying-glass me-2"></i>Consultar estado
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            {{-- Sidebar (solo desktop) --}}
            <div class="col-lg-4 d-none d-lg-block">
                <div class="sticky-top sticky-top-6">

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-circle-info me-2 pqrsf-info-icon"></i>¿Que es una PQRSF?
                            </h6>
                            <ul class="list-unstyled small mb-0">
                                <li class="mb-2"><strong class="pqrsf-info-icon">Peticion:</strong> Solicitud de informacion o de acceso a un servicio.</li>
                                <li class="mb-2"><strong class="pqrsf-info-icon">Queja:</strong> Manifestacion de inconformidad por un mal servicio o atencion.</li>
                                <li class="mb-2"><strong class="pqrsf-info-icon">Reclamo:</strong> Solicitud de correccion o solucion de un problema.</li>
                                <li class="mb-2"><strong class="pqrsf-info-icon">Sugerencia:</strong> Propuesta para mejorar el servicio.</li>
                                <li class="mb-0"><strong class="pqrsf-info-icon">Felicitacion:</strong> Reconocimiento por un buen servicio.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-clock text-success me-2"></i>Tiempos de respuesta
                            </h6>
                            <div class="d-flex align-items-start mb-3">
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 pqrsf-sidebar-icon"
                                    >
                                    <i class="fas fa-bolt text-success"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="mb-0 small fw-bold">Peticiones</p>
                                    <p class="mb-0 text-muted small">Hasta 15 dias habiles</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 pqrsf-sidebar-icon"
                                    >
                                    <i class="fas fa-message text-warning"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="mb-0 small fw-bold">Quejas y Reclamos</p>
                                    <p class="mb-0 text-muted small">Hasta 15 dias habiles</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 pqrsf-sidebar-icon"
                                    >
                                    <i class="fas fa-lightbulb text-info"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="mb-0 small fw-bold">Sugerencias</p>
                                    <p class="mb-0 text-muted small">Evaluacion y respuesta</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        <p class="small mb-2"><i class="fas fa-question-circle me-2 pqrsf-help-icon"></i><strong>¿Necesita ayuda?</strong></p>
                        <p class="text-muted mb-0 small">Si tiene dudas, puede comunicarse con nuestra linea de atencion al ciudadano.</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>



@push('scripts')
<script>
(function () {
    var checkbox = document.getElementById('is_anonymous');
    var citizenInfo = document.getElementById('citizenInfo');
    var requiredFields = citizenInfo.querySelectorAll('#customer_firstname, #customer_lastname, #customer_email');

    function toggleCitizenInfo() {
        if (checkbox.checked) {
            citizenInfo.style.display = 'none';
            requiredFields.forEach(function (f) { f.removeAttribute('required'); });
        } else {
            citizenInfo.style.display = '';
            requiredFields.forEach(function (f) { f.setAttribute('required', 'required'); });
        }
    }

    checkbox.addEventListener('change', toggleCitizenInfo);
    toggleCitizenInfo();
}());
</script>
@endpush

@endsection
