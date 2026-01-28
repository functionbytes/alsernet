{{-- Template Form Component --}}

<div class="row">
    <div class="col-12 col-md-8">
        {{-- Sección 1: Información básica --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Información básica
        </h6>

        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Nombre de plantilla <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           name="name"
                           value="{{ old('name', $template->name ?? '') }}"
                           maxlength="100"
                           placeholder="Ej: Bienvenida nuevos suscriptores"
                           required>
                    <small class="form-text text-muted d-block mt-1">Nombre interno para identificar la plantilla</small>
                    @error('name')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Tipo de plantilla <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('type') is-invalid @enderror"
                            name="type"
                            required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="welcome" {{ old('type', $template->type ?? '') == 'welcome' ? 'selected' : '' }}>Bienvenida</option>
                        <option value="newsletter" {{ old('type', $template->type ?? '') == 'newsletter' ? 'selected' : '' }}>Newsletter</option>
                        <option value="transactional" {{ old('type', $template->type ?? '') == 'transactional' ? 'selected' : '' }}>Transaccional</option>
                        <option value="custom" {{ old('type', $template->type ?? '') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              name="description"
                              rows="3"
                              maxlength="255"
                              placeholder="Descripción breve de la plantilla">{{ old('description', $template->description ?? '') }}</textarea>
                    <small class="form-text text-muted d-block mt-1">Información adicional sobre el propósito de esta plantilla</small>
                    @error('description')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 2: Configuración de email --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Configuración de email
        </h6>

        <div class="row mb-4">
            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Asunto del email <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('subject') is-invalid @enderror"
                           name="subject"
                           value="{{ old('subject', $template->subject ?? '') }}"
                           maxlength="200"
                           placeholder="Ej: Bienvenido a nuestro servicio"
                           required>
                    <small class="form-text text-muted d-block mt-1">El asunto que verán los destinatarios</small>
                    @error('subject')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Remitente (nombre) <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('sender_name') is-invalid @enderror"
                           name="sender_name"
                           value="{{ old('sender_name', $template->sender_name ?? '') }}"
                           maxlength="100"
                           placeholder="Ej: Mi Empresa"
                           required>
                    <small class="form-text text-muted d-block mt-1">Nombre que aparecerá como remitente</small>
                    @error('sender_name')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Correo del remitente <span class="text-danger">*</span>
                    </label>
                    <input type="email"
                           class="form-control @error('sender_email') is-invalid @enderror"
                           name="sender_email"
                           value="{{ old('sender_email', $template->sender_email ?? '') }}"
                           placeholder="Ej: noreply@miempresa.com"
                           required>
                    <small class="form-text text-muted d-block mt-1">Dirección de correo del remitente</small>
                    @error('sender_email')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 3: Contenido del email --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Contenido del email
        </h6>

        <div class="row mb-4">
            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Contenido HTML <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('content') is-invalid @enderror"
                              id="content-editor"
                              name="content"
                              rows="12"
                              placeholder="Ingrese el contenido HTML del email"
                              required>{{ old('content', $template->content ?? '') }}</textarea>
                    <small class="form-text text-muted d-block mt-1">Puede usar variables dinámicas como {{'{{'}} user.name {{'}}'}} o {{'{{'}} user.email {{'}}'}}</small>
                    @error('content')
                        <div class="invalid-feedback d-block mt-1">
                            <i class="fas fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- Sección 4: Variables disponibles --}}
        <h6 class="fw-bold mb-3 border-bottom pb-2">
            Variables disponibles
        </h6>

        <div class="alert alert-info mb-4">
            <p class="mb-2"><strong>Puede usar las siguientes variables en su plantilla:</strong></p>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <ul class="list-unstyled mb-0 small">
                        <li><code>{{'{{'}} user.name {{'}}'}}</code> - Nombre del usuario</li>
                        <li><code>{{'{{'}} user.email {{'}}'}}</code> - Email del usuario</li>
                        <li><code>{{'{{'}} user.phone {{'}}'}}</code> - Teléfono del usuario</li>
                        <li><code>{{'{{'}} company.name {{'}}'}}</code> - Nombre de la empresa</li>
                    </ul>
                </div>
                <div class="col-12 col-md-6">
                    <ul class="list-unstyled mb-0 small">
                        <li><code>{{'{{'}} company.email {{'}}'}}</code> - Email de la empresa</li>
                        <li><code>{{'{{'}} company.website {{'}}'}}</code> - Website de la empresa</li>
                        <li><code>{{'{{'}} date.today {{'}}'}}</code> - Fecha de hoy</li>
                        <li><code>{{'{{'}} date.year {{'}}'}}</code> - Año actual</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        {{-- Panel lateral: Configuración adicional --}}
        <div class="card border-light">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">Configuración</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               id="isActive"
                               value="1"
                               {{ old('is_active', $template->is_active ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">
                            Plantilla activa
                        </label>
                    </div>
                    <small class="form-text text-muted d-block mt-1">Solo las plantillas activas pueden ser utilizadas</small>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Variables personalizadas</label>
                    <div id="custom-vars-container">
                        @if(isset($template) && $template->custom_variables)
                            @foreach(json_decode($template->custom_variables, true) ?? [] as $key => $value)
                                <div class="mb-2 d-flex gap-2">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           placeholder="Variable"
                                           value="{{ $key }}"
                                           disabled>
                                    <button type="button" class="btn btn-sm btn-light-danger" onclick="removeCustomVar(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-light-primary w-100" onclick="addCustomVar()">
                        <i class="fas fa-plus me-1"></i>Agregar variable
                    </button>
                </div>

                <hr>

                <div class="mb-3">
                    <p class="small text-muted mb-2"><strong>Información:</strong></p>
                    @if(isset($template))
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>Creado:</strong> {{ $template->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Modificado:</strong> {{ $template->updated_at->format('d/m/Y H:i') }}
                            </li>
                            @if($template->created_by)
                                <li>
                                    <i class="fas fa-user me-2"></i>
                                    <strong>Por:</strong> {{ $template->createdBy->name ?? 'Usuario' }}
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addCustomVar() {
        const container = document.getElementById('custom-vars-container');
        const newVar = document.createElement('div');
        newVar.className = 'mb-2 d-flex gap-2';
        newVar.innerHTML = `
            <input type="text" class="form-control form-control-sm" placeholder="Variable" value="">
            <button type="button" class="btn btn-sm btn-light-danger" onclick="removeCustomVar(this)">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(newVar);
    }

    function removeCustomVar(btn) {
        btn.parentElement.remove();
    }
</script>
@endpush
