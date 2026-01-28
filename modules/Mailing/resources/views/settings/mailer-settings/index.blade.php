@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de remitente'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.mailing.settings.mailer.store') }}" id="formMailerSettings">
        @csrf

        {{-- From Email Configuration Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Configuración del remitente</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> Configure el email y nombre que aparecerá como remitente en tus campañas.
                </div>

                <div class="row">
                    {{-- From Name --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="from_name" class="form-label">
                                Nombre del remitente <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('from_name') is-invalid @enderror"
                                   id="from_name"
                                   name="from_name"
                                   value="{{ old('from_name', $settings['from_name'] ?? '') }}"
                                   placeholder="Ej: Mi Empresa"
                                   required>
                            @error('from_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Nombre que aparecerá como remitente en los emails</small>
                        </div>
                    </div>

                    {{-- From Email --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="from_email" class="form-label">
                                Email del remitente <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('from_email') is-invalid @enderror"
                                   id="from_email"
                                   name="from_email"
                                   value="{{ old('from_email', $settings['from_email'] ?? '') }}"
                                   placeholder="noreply@ejemplo.com"
                                   required>
                            @error('from_email')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Debe ser un email verificado en tu servidor SMTP</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reply and Return Path Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Respuestas y rutas de retorno</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> Configure dónde irán las respuestas de los usuarios y dónde regresar los emails no entregables.
                </div>

                <div class="row">
                    {{-- Reply To Email --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="reply_to_email" class="form-label">
                                Email de respuesta <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('reply_to_email') is-invalid @enderror"
                                   id="reply_to_email"
                                   name="reply_to_email"
                                   value="{{ old('reply_to_email', $settings['reply_to_email'] ?? '') }}"
                                   placeholder="respuesta@ejemplo.com"
                                   required>
                            @error('reply_to_email')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Email a donde irán las respuestas de los usuarios</small>
                        </div>
                    </div>

                    {{-- Reply To Name --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="reply_to_name" class="form-label">
                                Nombre para respuestas
                            </label>
                            <input type="text"
                                   class="form-control @error('reply_to_name') is-invalid @enderror"
                                   id="reply_to_name"
                                   name="reply_to_name"
                                   value="{{ old('reply_to_name', $settings['reply_to_name'] ?? '') }}"
                                   placeholder="Equipo de soporte">
                            @error('reply_to_name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Nombre a mostrar en el campo "Responder a"</small>
                        </div>
                    </div>

                    {{-- Return Path Email --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="return_path_email" class="form-label">
                                Email de ruta de retorno <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('return_path_email') is-invalid @enderror"
                                   id="return_path_email"
                                   name="return_path_email"
                                   value="{{ old('return_path_email', $settings['return_path_email'] ?? '') }}"
                                   placeholder="bounce@ejemplo.com"
                                   required>
                            @error('return_path_email')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Email para rebotes y errores de entrega</small>
                        </div>
                    </div>

                    {{-- Return Path Domain --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="return_path_domain" class="form-label">
                                Dominio de ruta de retorno
                            </label>
                            <input type="text"
                                   class="form-control @error('return_path_domain') is-invalid @enderror"
                                   id="return_path_domain"
                                   name="return_path_domain"
                                   value="{{ old('return_path_domain', $settings['return_path_domain'] ?? '') }}"
                                   placeholder="bounce.ejemplo.com">
                            @error('return_path_domain')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Dominio alternativo para procesar rebotes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DKIM Configuration Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Configuración DKIM</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-warning alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i> DKIM mejora la entregabilidad al firmar digitalmente tus emails. Requiere configuración en tu proveedor DNS.
                </div>

                <div class="row">
                    {{-- Enable DKIM --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_dkim"
                                   name="enable_dkim"
                                   value="1"
                                   {{ old('enable_dkim', $settings['enable_dkim'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_dkim">
                                Habilitar DKIM
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Activar la firma DKIM en los emails</small>
                    </div>

                    {{-- DKIM Domain --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="dkim_domain" class="form-label">
                                Dominio DKIM
                            </label>
                            <input type="text"
                                   class="form-control @error('dkim_domain') is-invalid @enderror"
                                   id="dkim_domain"
                                   name="dkim_domain"
                                   value="{{ old('dkim_domain', $settings['dkim_domain'] ?? '') }}"
                                   placeholder="ejemplo.com">
                            @error('dkim_domain')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Dominio para la firma DKIM</small>
                        </div>
                    </div>

                    {{-- DKIM Selector --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="dkim_selector" class="form-label">
                                Selector DKIM
                            </label>
                            <input type="text"
                                   class="form-control @error('dkim_selector') is-invalid @enderror"
                                   id="dkim_selector"
                                   name="dkim_selector"
                                   value="{{ old('dkim_selector', $settings['dkim_selector'] ?? '') }}"
                                   placeholder="default">
                            @error('dkim_selector')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Identificador único para el registro DKIM en DNS</small>
                        </div>
                    </div>

                    {{-- DKIM Private Key --}}
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label for="dkim_private_key" class="form-label">
                                Clave privada DKIM
                            </label>
                            <textarea class="form-control @error('dkim_private_key') is-invalid @enderror"
                                      id="dkim_private_key"
                                      name="dkim_private_key"
                                      rows="4"
                                      placeholder="-----BEGIN RSA PRIVATE KEY-----">{{ old('dkim_private_key', $settings['dkim_private_key'] ?? '') }}</textarea>
                            @error('dkim_private_key')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Clave privada para firmar emails (mantener confidencial)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SPF and DMARC Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">SPF y DMARC</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> SPF y DMARC protegen tu dominio contra suplantación y mejoran la entregabilidad.
                </div>

                <div class="row">
                    {{-- Enable SPF --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_spf"
                                   name="enable_spf"
                                   value="1"
                                   {{ old('enable_spf', $settings['enable_spf'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_spf">
                                Habilitar validación SPF
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Verificar registros SPF en tu dominio</small>
                    </div>

                    {{-- Enable DMARC --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="enable_dmarc"
                                   name="enable_dmarc"
                                   value="1"
                                   {{ old('enable_dmarc', $settings['enable_dmarc'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_dmarc">
                                Habilitar validación DMARC
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Verificar políticas DMARC en tu dominio</small>
                    </div>

                    {{-- SPF Record --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="spf_record" class="form-label">
                                Registro SPF
                            </label>
                            <textarea class="form-control @error('spf_record') is-invalid @enderror"
                                      id="spf_record"
                                      name="spf_record"
                                      rows="2"
                                      readonly
                                      placeholder="Se generará automáticamente">{{ old('spf_record', $settings['spf_record'] ?? '') }}</textarea>
                            @error('spf_record')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Copia este valor en tu DNS (registro TXT)</small>
                        </div>
                    </div>

                    {{-- DMARC Record --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="dmarc_record" class="form-label">
                                Registro DMARC
                            </label>
                            <textarea class="form-control @error('dmarc_record') is-invalid @enderror"
                                      id="dmarc_record"
                                      name="dmarc_record"
                                      rows="2"
                                      readonly
                                      placeholder="Se generará automáticamente">{{ old('dmarc_record', $settings['dmarc_record'] ?? '') }}</textarea>
                            @error('dmarc_record')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Copia este valor en tu DNS (registro TXT en _dmarc)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Authentication Headers Card --}}
        <div class="card mb-3">
            <div class="card-header p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold">Encabezados de autenticación</h6>
            </div>

            <div class="card-body">
                <div class="alert alert-info alert-sm py-2 px-3 mb-3" role="alert">
                    <i class="fa fa-info-circle me-2"></i> Encabezados adicionales para mejorar la autenticación y validación.
                </div>

                <div class="row">
                    {{-- Add List-Unsubscribe Header --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="add_list_unsubscribe_header"
                                   name="add_list_unsubscribe_header"
                                   value="1"
                                   {{ old('add_list_unsubscribe_header', $settings['add_list_unsubscribe_header'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="add_list_unsubscribe_header">
                                Agregar encabezado List-Unsubscribe
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Permitir desuscripción con un clic en clientes de email</small>
                    </div>

                    {{-- Add List-Help Header --}}
                    <div class="col-12 col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="add_list_help_header"
                                   name="add_list_help_header"
                                   value="1"
                                   {{ old('add_list_help_header', $settings['add_list_help_header'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="add_list_help_header">
                                Agregar encabezado List-Help
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4 mb-3">Proporcionar información de ayuda al usuario</small>
                    </div>

                    {{-- Custom Headers --}}
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="custom_headers" class="form-label">
                                Encabezados personalizados
                            </label>
                            <textarea class="form-control @error('custom_headers') is-invalid @enderror"
                                      id="custom_headers"
                                      name="custom_headers"
                                      rows="3"
                                      placeholder="X-Custom-Header: valor&#10;X-Another-Header: otro-valor">{{ old('custom_headers', $settings['custom_headers'] ?? '') }}</textarea>
                            @error('custom_headers')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            <small class="text-muted">Encabezados adicionales (uno por línea: Nombre: valor)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="card">
            <div class="card-body d-flex gap-2 justify-content-end">
                <a href="{{ route('settings.mailing.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-times me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-2"></i>Guardar configuración
                </button>
            </div>
        </div>

    </form>

@endsection
