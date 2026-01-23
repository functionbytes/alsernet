@extends('layouts.theme')

@section('title', 'Editar Email Endpoint')

@section('content')

    <div class="card w-100">

        <form method="POST" action="{{ route('mailers.endpoints.update', $endpoint) }}" id="formEdit">
            @csrf
            @method('PATCH')

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="mb-0">Editar endpoint de email</h5>
                        <p class="card-subtitle mb-0 mt-2">Modifica la configuración del endpoint <strong>{{ $endpoint->name }}</strong>.</p>
                    </div>
                    <a href="{{ route('mailers.endpoints.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Atrás
                    </a>
                </div>

                @include('core::components.alerts')

                <div class="row">

                    {{-- API Token Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-key me-2 text-primary"></i>
                            Token API
                        </h6>
                        <p class="text-muted small mb-3">Este token es necesario para autenticar las peticiones al endpoint. Envíalo en el header <code>X-API-Token</code>.</p>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-lock text-primary"></i>
                                </span>
                                <input type="text" class="form-control font-monospace bg-light" id="tokenInput"
                                       value="{{ $endpoint->api_token }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="copyTokenBtn" title="Copiar token">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Header: <code>X-API-Token: {{ Str::limit($endpoint->api_token, 20) }}</code>
                            </small>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#regenerateTokenModal">
                                <i class="fas fa-sync me-2"></i>Regenerar token
                            </button>
                            <small class="form-text text-muted d-block text-center mt-1">
                                <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                El token anterior dejará de funcionar
                            </small>
                        </div>
                    </div>

                    {{-- Statistics Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            Estadísticas de uso
                        </h6>
                        <p class="text-muted small mb-3">Resumen del uso de este endpoint.</p>
                    </div>

                    @php
                        $last24h = $endpoint->logs()->where('created_at', '>=', now()->subDay())->count();
                        $successCount = $endpoint->successLogs()->count();
                        $failedCount = $endpoint->failedLogs()->count();
                        $total = $endpoint->requests_count;
                        $successRate = $total > 0 ? round(($successCount / $total) * 100, 1) : 0;
                    @endphp

                    <div class="col-6 col-md-3">
                        <div class="card bg-light-primary border-0 mb-3">
                            <div class="card-body p-3 text-center">
                                <h3 class="mb-1 fw-bold text-primary">{{ number_format($endpoint->requests_count) }}</h3>
                                <small class="text-muted">Total requests</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card bg-light-info border-0 mb-3">
                            <div class="card-body p-3 text-center">
                                <h3 class="mb-1 fw-bold text-info">{{ number_format($last24h) }}</h3>
                                <small class="text-muted">Últimas 24h</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card bg-light-success border-0 mb-3">
                            <div class="card-body p-3 text-center">
                                <h3 class="mb-1 fw-bold text-success">{{ number_format($successCount) }}</h3>
                                <small class="text-muted">Exitosos</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card bg-light-danger border-0 mb-3">
                            <div class="card-body p-3 text-center">
                                <h3 class="mb-1 fw-bold text-danger">{{ number_format($failedCount) }}</h3>
                                <small class="text-muted">Fallidos</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold">Tasa de éxito</small>
                                <small class="fw-semibold {{ $successRate >= 90 ? 'text-success' : ($successRate >= 70 ? 'text-warning' : 'text-danger') }}">{{ $successRate }}%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $successRate >= 90 ? 'bg-success' : ($successRate >= 70 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $successRate }}%"></div>
                            </div>
                            @if($endpoint->last_request_at)
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-clock me-1"></i>
                                    Última solicitud: {{ $endpoint->last_request_at->diffForHumans() }}
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <a href="{{ route('mailers.endpoints.logs', $endpoint) }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-history me-2"></i>Ver todos los logs
                            </a>
                        </div>
                    </div>

                    {{-- Basic Information Section --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Información básica
                        </h6>
                        <p class="text-muted small mb-3">Define el nombre, slug único y la fuente del endpoint.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre del endpoint <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $endpoint->name) }}"
                                   placeholder="Ej: PrestaShop Password Reset" required>
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
                                   id="slug" name="slug" value="{{ old('slug', $endpoint->slug) }}"
                                   placeholder="prestashop_password_reset" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-link me-1"></i>
                                URL: <code>/api/email-endpoints/<strong>{{ $endpoint->slug }}</strong>/send</code>
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
                            <input type="text" class="form-control @error('source') is-invalid @enderror"
                                   id="source" name="source" value="{{ old('source', $endpoint->source) }}"
                                   placeholder="Ej: prestashop, shopify, custom" required>
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
                            <input type="text" class="form-control @error('type') is-invalid @enderror"
                                   id="type" name="type" value="{{ old('type', $endpoint->type) }}"
                                   placeholder="Ej: password_reset, order_confirmation" required>
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
                                      placeholder="Descripción opcional del endpoint">{{ old('description', $endpoint->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre este endpoint</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $endpoint->is_active ? 'checked' : '' }}>
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
                        <p class="text-muted small mb-3">Selecciona la plantilla de email que se usará y el idioma predeterminado para los envíos.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Plantilla de email</label>
                            <select class="form-select select2 @error('mailer_template_id') is-invalid @enderror"
                                    id="mailer_template_id" name="mailer_template_id">
                                <option value="">-- Seleccionar plantilla --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ old('mailer_template_id', $endpoint->mailer_template_id) == $template->id ? 'selected' : '' }}>
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
                                    <option value="{{ $lang->id }}" {{ old('lang_id', $endpoint->lang_id) == $lang->id ? 'selected' : '' }}>
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
                        <p class="text-muted small mb-3">Define las variables que esperas recibir en el JSON desde el sistema externo.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="alert alert-light border mb-3">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>
                                <strong>Ejemplo:</strong> Si tu JSON envía <code>{"customer_email": "user@example.com"}</code>, agrega <code>customer_email</code> como variable.
                            </div>
                            <div id="expectedVariablesContainer" class="mb-3">
                                @foreach($endpoint->expected_variables ?? [] as $var)
                                    <div class="input-group mb-2">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-cube text-primary"></i>
                                        </span>
                                        <input type="text" class="form-control" name="expected_variables[]"
                                               placeholder="Ej: customer_email" value="{{ $var }}">
                                        <button type="button" class="btn btn-outline-danger remove-variable">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
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
                        <p class="text-muted small mb-3">Marca las variables que son obligatorias en cada request.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div id="requiredVariablesContainer">
                                @if(!empty($endpoint->expected_variables))
                                    @foreach($endpoint->expected_variables as $index => $var)
                                        <div class="form-check form-check-inline mb-2">
                                            <input class="form-check-input" type="checkbox" name="required_variables[]"
                                                   id="required_{{ $index }}" value="{{ $var }}"
                                                   {{ in_array($var, $endpoint->required_variables ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="required_{{ $index }}">
                                                <span class="badge bg-light text-dark border rounded-pill py-1 px-2">{{ $var }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Primero agrega variables esperadas arriba</small>
                                @endif
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
                        <p class="text-muted small mb-3">Si los nombres de las variables en el JSON no coinciden con los de la plantilla, puedes mapearlos aquí.</p>
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

                                    @php
                                        $mappings = $endpoint->variable_mappings ?? [];
                                    @endphp
                                    @if(!empty($mappings))
                                        @foreach($mappings as $templateVar => $jsonPath)
                                            <div class="row g-2 mb-2 mapping-row">
                                                <div class="col-5">
                                                    <input type="text" class="form-control form-control-sm mapping-template"
                                                           placeholder="email" value="{{ $templateVar }}">
                                                </div>
                                                <div class="col-5">
                                                    <input type="text" class="form-control form-control-sm mapping-json"
                                                           placeholder="user.email" value="{{ $jsonPath }}">
                                                </div>
                                                <div class="col-2">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-mapping w-100">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMapping">
                                    <i class="fas fa-plus me-2"></i>Agregar mapeo
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Recent Logs Preview --}}
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Logs recientes
                        </h6>
                        <p class="text-muted small mb-3">Últimas peticiones recibidas en este endpoint.</p>
                    </div>

                    <div class="col-12">
                        @php
                            $recentLogs = $endpoint->logs()->latest()->limit(5)->get();
                        @endphp

                        @if($recentLogs->count() > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Estado</th>
                                            <th>Email</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentLogs as $log)
                                            <tr>
                                                <td>
                                                    <span class="badge {{ $log->status === 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill">
                                                        <i class="fas {{ $log->status === 'success' ? 'fa-check' : 'fa-times' }} me-1"></i>
                                                        {{ ucfirst($log->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-muted">{{ Str::limit($log->recipient_email, 30) }}</td>
                                                <td class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border text-center">
                                <i class="fas fa-inbox fs-4 text-muted mb-2 d-block"></i>
                                <p class="text-muted small mb-0">No hay logs registrados aún</p>
                            </div>
                        @endif
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info px-4 waves-effect waves-light">
                        <i class="fas fa-save me-2"></i>Guardar cambios
                    </button>
                    <a href="{{ route('mailers.endpoints.index') }}" class="btn btn-light px-4">
                        Cancelar
                    </a>
                </div>
            </div>

        </form>

    </div>

    {{-- Regenerate Token Modal --}}
    <div class="modal fade" id="regenerateTokenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Regenerar token API
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0">
                        <strong><i class="fas fa-exclamation-circle me-1"></i> Advertencia:</strong>
                        Al regenerar el token, el token anterior dejará de funcionar inmediatamente.
                    </div>
                    <p>Todos los sistemas que usen el token anterior tendrán que actualizarse con el nuevo token.</p>
                    <p class="mb-0 fw-semibold">¿Deseas continuar?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('mailers.endpoints.regenerate-token', $endpoint) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-sync me-1"></i> Regenerar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            }
        }
    });

    // Copy Token Button
    $('#copyTokenBtn').on('click', function() {
        const token = $('#tokenInput');
        token.select();
        document.execCommand('copy');

        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-check text-success"></i>');

        setTimeout(function() {
            btn.html(originalHtml);
        }, 2000);

        toastr.success('Token copiado al portapapeles', 'Éxito');
    });

    // Add Expected Variable
    $('#addExpectedVariable').on('click', function() {
        var html = `
            <div class="input-group mb-2">
                <span class="input-group-text bg-light">
                    <i class="fas fa-cube text-primary"></i>
                </span>
                <input type="text" class="form-control" name="expected_variables[]" placeholder="Ej: customer_email">
                <button type="button" class="btn btn-outline-danger remove-variable">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        $('#expectedVariablesContainer').append(html);
        attachRemoveHandlers();
        updateRequiredVariablesCheckboxes();
    });

    // Add Mapping
    $('#addMapping').on('click', function() {
        var html = `
            <div class="row g-2 mb-2 mapping-row">
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm mapping-template" placeholder="email">
                </div>
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm mapping-json" placeholder="user.email">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-mapping w-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        $('#mappingsContainer').append(html);
        attachRemoveHandlers();
    });

    function attachRemoveHandlers() {
        $('.remove-variable').off('click').on('click', function() {
            $(this).closest('.input-group').remove();
            updateRequiredVariablesCheckboxes();
        });

        $('.remove-mapping').off('click').on('click', function() {
            $(this).closest('.mapping-row').remove();
        });
    }

    // Dynamically update required variables checkboxes
    function updateRequiredVariablesCheckboxes() {
        var expectedVars = [];
        $('input[name="expected_variables[]"]').each(function() {
            var val = $(this).val().trim();
            if (val.length > 0) {
                expectedVars.push(val);
            }
        });

        var currentChecked = [];
        $('#requiredVariablesContainer input[type="checkbox"]:checked').each(function() {
            currentChecked.push($(this).val());
        });

        $('#requiredVariablesContainer').empty();

        if (expectedVars.length === 0) {
            $('#requiredVariablesContainer').html('<small class="text-muted"><i class="fas fa-info-circle me-1"></i>Primero agrega variables esperadas arriba</small>');
            return;
        }

        $.each(expectedVars, function(index, variable) {
            var isChecked = currentChecked.indexOf(variable) !== -1;
            var html = `
                <div class="form-check form-check-inline mb-2">
                    <input class="form-check-input" type="checkbox" name="required_variables[]"
                           id="required_${index}" value="${variable}" ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="required_${index}">
                        <span class="badge bg-light text-dark border rounded-pill py-1 px-2">${variable}</span>
                    </label>
                </div>
            `;
            $('#requiredVariablesContainer').append(html);
        });
    }

    // Monitor expected variables for changes
    $(document).on('input', 'input[name="expected_variables[]"]', function() {
        updateRequiredVariablesCheckboxes();
    });

    // Form Submit - Prepare hidden fields for mappings
    $('#formEdit').on('submit', function() {
        var mappings = {};
        $('.mapping-row').each(function() {
            var templateVar = $(this).find('.mapping-template').val();
            var jsonPath = $(this).find('.mapping-json').val();
            if (templateVar && jsonPath) {
                mappings[templateVar] = jsonPath;
            }
        });

        if (Object.keys(mappings).length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'variable_mappings',
                value: JSON.stringify(mappings)
            }).appendTo(this);
        }
    });

    // Initialize
    attachRemoveHandlers();

    // Show toastr notifications
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
