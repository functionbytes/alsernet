{{--
    Variables:
    $webhook – ReviewWebhookSubscription|null (null para crear)
--}}

<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">URL del endpoint <span class="text-danger">*</span></label>
            <input type="url" class="form-control @error('url') is-invalid @enderror"
                   name="url" value="{{ old('url', $webhook?->url) }}"
                   maxlength="500" required placeholder="https://hooks.zapier.com/...">
            <small class="form-text text-muted">URL donde se enviaran los eventos (POST JSON).</small>
            @error('url')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Eventos <span class="text-danger">*</span></label>
            @error('events')
                <span class="field-validation-error d-block mb-1"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
            @php
                $selectedEvents = old('events', $webhook?->events ?? ['review.created']);
            @endphp
            <select class="form-select select2" name="events[]" multiple required>
                @foreach(['review.created' => 'Nueva reseña detectada', 'reply.published' => 'Respuesta publicada en Google', 'review.anomaly' => 'Anomalia en volumen de reseñas'] as $value => $label)
                    <option value="{{ $value }}" {{ in_array($value, $selectedEvents) ? 'selected' : '' }}>
                        {{ $value }} — {{ $label }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">Selecciona uno o mas eventos que activaran este webhook.</small>
        </div>
    </div>

    @if(! $webhook)
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Secreto HMAC <small class="text-muted">(opcional)</small></label>
            <input type="text" class="form-control font-monospace @error('secret') is-invalid @enderror"
                   name="secret" value="{{ old('secret') }}"
                   maxlength="64" placeholder="Clave para verificar la firma X-Webhook-Signature">
            <small class="form-text text-muted">Se enviara la firma <code>HMAC-SHA256</code> en el header <code>X-Webhook-Signature</code>.</small>
            @error('secret')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select select2" name="is_active">
                <option value="1" {{ old('is_active', $webhook ? ($webhook->is_active ? '1' : '0') : '1') === '1' ? 'selected' : '' }}>Activa</option>
                <option value="0" {{ old('is_active', $webhook ? ($webhook->is_active ? '1' : '0') : '1') === '0' ? 'selected' : '' }}>Inactiva</option>
            </select>
        </div>
    </div>
</div>
