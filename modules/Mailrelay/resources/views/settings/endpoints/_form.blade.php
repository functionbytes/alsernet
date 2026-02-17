<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-info-circle me-2"></i>Información básica
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $endpoint->name ?? '') }}"
                        placeholder="Endpoint de bienvenida"
                        required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="endpoint_key" class="form-label">Clave del endpoint <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">key:</span>
                        <input type="text"
                            class="form-control font-monospace @error('endpoint_key') is-invalid @enderror"
                            id="endpoint_key"
                            name="endpoint_key"
                            value="{{ old('endpoint_key', $endpoint->endpoint_key ?? '') }}"
                            placeholder="welcome_email"
                            pattern="[a-z0-9_]+"
                            style="text-transform: lowercase;"
                            required
                            {{ isset($endpoint) ? 'readonly' : '' }}>
                        @error('endpoint_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="form-text text-muted">
                        Solo letras minúsculas, números y guiones bajos. No se puede modificar después de crearlo.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="template_id" class="form-label">Template asociado <span class="text-danger">*</span></label>
                    <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id" required>
                        <option value="">Selecciona un template...</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" {{ old('template_id', $endpoint->template_id ?? '') == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        El template que se usará para enviar emails desde este endpoint
                    </small>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Descripción del propósito de este endpoint...">{{ old('description', $endpoint->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2 mt-4">
                    <i class="fas fa-shield-alt me-2"></i>Seguridad y restricciones
                </h6>

                <div class="mb-3">
                    <label for="allowed_ips" class="form-label">IPs permitidas</label>
                    <textarea class="form-control font-monospace @error('allowed_ips') is-invalid @enderror"
                        id="allowed_ips"
                        name="allowed_ips"
                        rows="3"
                        placeholder="192.168.1.1, 10.0.0.1, ...">{{ old('allowed_ips', isset($endpoint) && is_array($endpoint->allowed_ips) ? implode(', ', $endpoint->allowed_ips) : '') }}</textarea>
                    @error('allowed_ips')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Direcciones IP separadas por comas. Dejar vacío para permitir todas las IPs.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="rate_limit" class="form-label">Rate limit <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number"
                            class="form-control @error('rate_limit') is-invalid @enderror"
                            id="rate_limit"
                            name="rate_limit"
                            value="{{ old('rate_limit', $endpoint->rate_limit ?? 60) }}"
                            min="1"
                            max="1000"
                            required>
                        <span class="input-group-text">requests / minuto</span>
                        @error('rate_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="form-text text-muted">
                        Número máximo de requests permitidos por minuto desde la misma IP
                    </small>
                </div>

                @if(isset($endpoint) && session('api_key'))
                    <div class="alert alert-warning">
                        <h6 class="alert-heading fw-bold">
                            <i class="fas fa-key me-2"></i>API Key generada
                        </h6>
                        <p class="mb-2">Esta es tu API key. <strong>Cópiala ahora</strong>, no podrás verla de nuevo:</p>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="generated-api-key" value="{{ session('api_key') }}" readonly>
                            <button type="button" class="btn btn-warning" id="copy-api-key">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-cog me-2"></i>Configuración
                </h6>

                <div class="mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" {{ old('status', $endpoint->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $endpoint->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="webhook_url" class="form-label">Webhook URL</label>
                    <input type="url"
                        class="form-control font-monospace @error('webhook_url') is-invalid @enderror"
                        id="webhook_url"
                        name="webhook_url"
                        value="{{ old('webhook_url', $endpoint->webhook_url ?? '') }}"
                        placeholder="https://example.com/webhook">
                    <small class="form-text text-muted">
                        URL a notificar después de enviar el email
                    </small>
                    @error('webhook_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if(isset($endpoint))
                    <div class="mb-3">
                        <button type="button" class="btn btn-warning w-100" id="regenerate-key-btn">
                            <i class="fas fa-sync me-2"></i>Regenerar API key
                        </button>
                        <small class="form-text text-muted mt-2 d-block">
                            La key actual será invalidada inmediatamente
                        </small>
                    </div>
                @endif

                <hr>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a href="{{ route('settings.mailrelay.endpoints.index') }}" class="btn btn-light">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-code me-2"></i>Uso del endpoint
                </h6>
                <div class="small">
                    <p class="mb-2"><strong>URL:</strong></p>
                    <code class="d-block bg-light p-2 rounded mb-3 small">
                        POST {{ url('/api/mailrelay/send') }}/{{ $endpoint->endpoint_key ?? '{endpoint_key}' }}
                    </code>

                    <p class="mb-2"><strong>Headers:</strong></p>
                    <code class="d-block bg-light p-2 rounded mb-3 small">
                        X-API-Key: {tu_api_key}<br>
                        Content-Type: application/json
                    </code>

                    <p class="mb-2"><strong>Body ejemplo:</strong></p>
                    <code class="d-block bg-light p-2 rounded small" style="white-space: pre-wrap;">{
  "to": "user@example.com",
  "variables": {
    "CUSTOMER_NAME": "John Doe",
    "ORDER_ID": "12345"
  }
}</code>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-convert to lowercase and remove invalid chars
    $('#endpoint_key').on('input', function() {
        if (!$(this).prop('readonly')) {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
        }
    });

    // Auto-generate endpoint_key from name
    $('#name').on('blur', function() {
        const name = $(this).val();
        const currentKey = $('#endpoint_key').val();

        // Only auto-generate if key is empty and field is not readonly
        if (!currentKey && name && !$('#endpoint_key').prop('readonly')) {
            const generatedKey = name
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_');
            $('#endpoint_key').val(generatedKey);
        }
    });

    // Copy API key to clipboard
    $('#copy-api-key').on('click', function() {
        const input = $('#generated-api-key');
        input.select();
        document.execCommand('copy');
        toastr.success('API Key copiada al portapapeles');
    });

    // Regenerate API key
    @if(isset($endpoint))
    $('#regenerate-key-btn').on('click', function() {
        if (!confirm('¿Estás seguro de regenerar la API key? La key actual será invalidada inmediatamente.')) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Regenerando...');

        $.ajax({
            url: '{{ route("settings.mailrelay.endpoints.regenerate-key", $endpoint->id ?? 0) }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Show new API key in a modal or alert
                    const modal = `
                        <div class="modal fade" id="apiKeyModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning">
                                        <h5 class="modal-title">Nueva API Key generada</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-2">Esta es tu nueva API key. <strong>Cópiala ahora</strong>, no podrás verla de nuevo:</p>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-monospace" id="new-api-key" value="${response.api_key}" readonly>
                                            <button type="button" class="btn btn-warning" onclick="navigator.clipboard.writeText('${response.api_key}'); toastr.success('Copiado');">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(modal);
                    $('#apiKeyModal').modal('show');
                    $('#apiKeyModal').on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                    toastr.success(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error al regenerar la API key');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-sync me-2"></i>Regenerar API key');
            }
        });
    });
    @endif

    // Validate IPs format
    $('#allowed_ips').on('blur', function() {
        const ips = $(this).val().trim();
        if (ips) {
            const ipArray = ips.split(',').map(ip => ip.trim());
            const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;

            const invalidIps = ipArray.filter(ip => !ipPattern.test(ip));
            if (invalidIps.length > 0) {
                toastr.warning('Algunas IPs podrían tener formato inválido: ' + invalidIps.join(', '));
            }
        }
    });
});
</script>
@endpush
