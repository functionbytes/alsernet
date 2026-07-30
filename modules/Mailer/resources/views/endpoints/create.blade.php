@extends('layouts.theme')

@section('title', 'Crear Email Endpoint')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear Email Endpoint'])
@endsection

@section('content')

    <div class="card w-100">

        <form method="POST" action="{{ route('mailers.endpoints.store') }}" id="formCreate">
            @csrf

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="mb-0">Crear nuevo endpoint de email</h5>
                        <p class="card-subtitle mb-0 mt-2">Configura un endpoint para recibir solicitudes de envío de emails desde sistemas externos como PrestaShop, Shopify u otros.</p>
                    </div>
                    <a href="{{ route('mailers.endpoints.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Atrás
                    </a>
                </div>

                @include('core::components.alerts')

                <div class="row">

                    {{-- Basic Information Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Información básica
                        </h6>
                        <p class="text-muted mb-3">Define el nombre, slug único y la fuente del endpoint. El slug se usará en la URL de la API.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del endpoint <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Ej: PrestaShop Password Reset" required maxlength="255">
                            <small class="form-text text-muted">Nombre descriptivo para identificar este endpoint</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Slug (único) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" value="{{ old('slug') }}"
                                   placeholder="prestashop_password_reset" required maxlength="255"
                                   pattern="[a-z0-9\-]+">
                            <small class="form-text text-muted">
                                <i class="fas fa-link me-1"></i>
                                URL: <code>/api/email-endpoints/<span id="slugPreview">slug</span>/send</code>
                            </small>
                            @error('slug')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Fuente (Sistema) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('source') is-invalid @enderror" id="source" name="source" required>
                                <option value="">Seleccionar tipo de fuente...</option>
                                <option value="internal" {{ old('source') === 'internal' ? 'selected' : '' }}>Internal</option>
                                <option value="webhook" {{ old('source') === 'webhook' ? 'selected' : '' }}>Webhook</option>
                                <option value="api" {{ old('source') === 'api' ? 'selected' : '' }}>API</option>
                            </select>
                            <small class="form-text text-muted">Sistema origen de las peticiones</small>
                            @error('source')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tipo de correo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="transactional" {{ old('type') === 'transactional' ? 'selected' : '' }}>Transactional</option>
                                <option value="notification" {{ old('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                            </select>
                            <small class="form-text text-muted">Categoría del email (para organización)</small>
                            @error('type')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="2"
                                      placeholder="Descripción opcional del endpoint">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este endpoint</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    <strong>Endpoint activo</strong>
                                    <small class="d-block text-muted">Los endpoints inactivos rechazarán las peticiones entrantes</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Template & Language Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-palette me-2 text-primary"></i>
                            Plantilla e idioma
                        </h6>
                        <p class="text-muted mb-3">Selecciona la plantilla de email que se usará y el idioma predeterminado para los envíos.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Plantilla de email</label>
                            <select class="form-select select2 @error('mailer_template_id') is-invalid @enderror"
                                    id="mailer_template_id" name="mailer_template_id">
                                <option value="">-- Seleccionar plantilla --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @if(old('mailer_template_id') == $template->id) selected @endif>
                                        {{ $template->name }} ({{ $template->lang?->title ?? 'Sin idioma' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Plantilla que se usará para enviar los emails</small>
                            @error('mailer_template_id')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Idioma Por defecto</label>
                            <select class="form-select select2 @error('lang_id') is-invalid @enderror"
                                    id="lang_id" name="lang_id">
                                <option value="">-- Seleccionar idioma --</option>
                                @foreach($langs as $lang)
                                    <option value="{{ $lang->id }}" @if(old('lang_id') == $lang->id) selected @endif>
                                        {{ $lang->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Idioma predeterminado de los emails enviados</small>
                            @error('lang_id')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Expected Variables Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-code me-2 text-primary"></i>
                            Variables esperadas
                        </h6>
                        <p class="text-muted mb-3">Define las variables que esperas recibir en el JSON desde el sistema externo. Estas variables estarán disponibles para usar en la plantilla.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="alert alert-light border mb-3">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>
                                <strong>Ejemplo:</strong> Si tu JSON envía <code>{"customer_email": "user@example.com"}</code>, agrega <code>customer_email</code> como variable.
                            </div>
                            <div id="expectedVariablesContainer" class="mb-3"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addExpectedVariable">
                                <i class="fas fa-plus me-2"></i>Agregar variable
                            </button>
                        </div>
                    </div>

                    {{-- Required Variables Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-asterisk me-2 text-primary"></i>
                            Variables obligatorias
                        </h6>
                        <p class="text-muted mb-3">Marca las variables que son obligatorias en cada request. Si faltan, la petición será rechazada.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div id="requiredVariablesContainer">
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Primero agrega variables esperadas arriba</small>
                            </div>
                        </div>
                    </div>

                    {{-- Variable Mappings Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>
                            Mapeo de variables (opcional)
                        </h6>
                        <p class="text-muted mb-3">Si los nombres de las variables en el JSON no coinciden con los de la plantilla, puedes mapearlos aquí.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="alert alert-light border mb-3">
                                <i class="fas fa-info-circle me-2 text-info"></i>
                                <strong>Ejemplo:</strong> Si tu JSON envía <code>user.email</code> pero la plantilla usa <code>{email}</code>, mapea: <code>email</code> → <code>user.email</code>
                            </div>
                            <div class="bg-light p-3 rounded">
                                <div id="mappingsContainer">
                                    <div class="row g-2 mb-2">
                                        <div class="col-5">
                                            <label class="form-label small fw-bold text-uppercase">
                                                <i class="fas fa-file-code me-1"></i> Variable plantilla
                                            </label>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small fw-bold text-uppercase">
                                                <i class="fas fa-arrow-right me-1"></i> Ruta JSON
                                            </label>
                                        </div>
                                        <div class="col-2"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMapping">
                                    <i class="fas fa-plus me-2"></i>Agregar mapeo
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Help Info --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="alert alert-info border-0">
                            <h6 class="alert-heading fw-semibold">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Información útil
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0 small">
                                        <li>El <strong>token API</strong> se genera automáticamente al crear el endpoint</li>
                                        <li>Podrás ver <strong>estadísticas</strong> y <strong>logs</strong> de uso</li>
                                        <li>Los endpoints inactivos <strong>rechazarán</strong> todas las peticiones</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <p class="small mb-1"><strong>Ejemplo de request:</strong></p>
                                    <pre class="bg-black text-light p-2 rounded mb-0" style="font-size: 10px;"><code>POST /api/email-endpoints/slug/send
Header: X-API-Token: abc123...
{"email": "user@example.com"}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info px-4 waves-effect waves-light">
                        <i class="fas fa-save me-2"></i>Crear endpoint
                    </button>
                    <a href="{{ route('mailers.endpoints.index') }}" class="btn btn-light px-4">
                        Cancelar
                    </a>
                </div>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script src="{{ asset('js/modules/mailer-endpoint-edit.js') }}"></script>
<script>
$(document).ready(function() {
    MailerEndpointEdit.init();

    @if (session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endpush
