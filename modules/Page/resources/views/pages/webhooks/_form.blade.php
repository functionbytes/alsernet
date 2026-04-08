@php
    $events = ['published' => 'Publicación', 'updated' => 'Actualización', 'deleted' => 'Eliminación'];
    $timeouts = [5 => '5 segundos', 10 => '10 segundos', 30 => '30 segundos'];
    $selectedEvents = old('events', $webhook->events ?? []);
@endphp

<div class="mb-3">
    <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control @error('name') is-invalid @enderror"
           id="name"
           name="name"
           value="{{ old('name', $webhook->name ?? '') }}"
           placeholder="Nombre descriptivo del webhook"
           required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="url" class="form-label fw-semibold">URL del webhook <span class="text-danger">*</span></label>
    <input type="url"
           class="form-control @error('url') is-invalid @enderror"
           id="url"
           name="url"
           value="{{ old('url', $webhook->url ?? '') }}"
           placeholder="https://ejemplo.com/webhook"
           required>
    @error('url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="secret" class="form-label fw-semibold">Secret <span class="text-muted fw-normal">(opcional)</span></label>
    <div class="input-group">
        <input type="text"
               class="form-control @error('secret') is-invalid @enderror"
               id="secret"
               name="secret"
               value="{{ old('secret', $webhook->secret ?? '') }}"
               placeholder="Clave para verificar la firma HMAC-SHA256">
        <button type="button" class="btn btn-info" id="btn-generate-secret" title="Generar secret aleatorio">
            <i class="fas fa-sync-alt me-1"></i>
        </button>
    </div>
    <div class="form-text">Si se configura, cada petición incluirá el header <code>X-Webhook-Signature: sha256=...</code></div>
    @error('secret')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="events" class="form-label fw-semibold">Eventos <span class="text-danger">*</span></label>
    @error('events')
        <div class="text-danger small mb-1">{{ $message }}</div>
    @enderror
    <select class="form-select select2" id="events" name="events[]" multiple required>
        @foreach($events as $value => $label)
            <option value="{{ $value }}" {{ in_array($value, $selectedEvents, true) ? 'selected' : '' }}>
                {{ $value }} — {{ $label }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Selecciona uno o más eventos que activarán este webhook.</div>
</div>

<div class="mb-3">
    <label for="timeout" class="form-label fw-semibold">Timeout de conexión</label>
    <select class="form-select @error('timeout') is-invalid @enderror" id="timeout" name="timeout">
        @foreach($timeouts as $seconds => $label)
            <option value="{{ $seconds }}" {{ (int) old('timeout', $webhook->timeout ?? 10) === $seconds ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('timeout')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="is_active" class="form-label fw-semibold">Estado</label>
    <select class="form-select select2" id="is_active" name="is_active">
        <option value="1" {{ old('is_active', $webhook->is_active ?? true) ? 'selected' : '' }}>Activo</option>
        <option value="0" {{ !old('is_active', $webhook->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
    </select>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#events').select2({ width: '100%' });
    $('#timeout').select2({ width: '100%', minimumResultsForSearch: Infinity });
    $('#is_active').select2({ width: '100%', minimumResultsForSearch: Infinity });

    $('#btn-generate-secret').on('click', function () {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let secret = '';
        for (let i = 0; i < 32; i++) {
            secret += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#secret').val(secret);
    });
});
</script>
@endpush
