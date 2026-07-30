<div class="row g-3">

    {{-- Webhook URL --}}
    <div class="col-12">
        <label class="form-label">
            URL del webhook
            @if(!isset($integration) || !$integration?->id)
                <span class="text-danger">*</span>
            @endif
        </label>

        @if(isset($integration) && $integration?->id)
            {{-- Edit mode: masked with toggle --}}
            <div class="input-group">
                <input type="password" name="webhook_url" id="webhook_url"
                    class="form-control @error('webhook_url') is-invalid @enderror"
                    placeholder="Dejar vacio para mantener el webhook actual"
                    disabled>
                <button type="button" class="btn btn-outline-secondary" id="btn_change_webhook">
                    Cambiar
                </button>
            </div>
            <div class="form-text">Deja el campo vacio si no deseas cambiar el webhook actual.</div>
        @else
            {{-- Create mode --}}
            <input type="url" name="webhook_url" id="webhook_url"
                class="form-control @error('webhook_url') is-invalid @enderror"
                value="{{ old('webhook_url') }}"
                placeholder="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXX">
            <div class="form-text">Obten la URL desde tu aplicacion de Slack en <strong>Incoming Webhooks</strong>.</div>
        @endif

        @error('webhook_url')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Channel name --}}
    <div class="col-12">
        <label class="form-label">
            Canal de Slack <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text">#</span>
            <input type="text" name="channel_name"
                class="form-control @error('channel_name') is-invalid @enderror"
                value="{{ old('channel_name', $integration->channel_name ?? '') }}"
                placeholder="mi-canal">
        </div>
        <div class="form-text">Nombre del canal donde se enviaran las notificaciones (sin el #).</div>
        @error('channel_name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Events --}}
    <div class="col-12">
        <label class="form-label">
            Eventos <span class="text-danger">*</span>
        </label>
        <div class="border rounded p-3 @error('events')  @enderror">
            @foreach($availableEvents as $eventKey => $eventLabel)
                <div class="form-check mb-2">
                    <input type="checkbox"
                        class="form-check-input"
                        name="events[]"
                        value="{{ $eventKey }}"
                        id="event_{{ $eventKey }}"
                        @checked(in_array($eventKey, old('events', $integration->events ?? [])))>
                    <label class="form-check-label" for="event_{{ $eventKey }}">
                        {{ $eventLabel }}
                        <code class="ms-2 small text-muted">{{ $eventKey }}</code>
                    </label>
                </div>
            @endforeach
        </div>
        @error('events')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is active --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active"
                class="form-check-input"
                value="1"
                @checked(old('is_active', $integration->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Integracion activa
            </label>
            <div class="form-text">Si esta activa, Slack recibira notificaciones cuando ocurran los eventos seleccionados.</div>
        </div>
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#btn_change_webhook').on('click', function () {
        const input = $('#webhook_url');
        input.prop('disabled', false).attr('type', 'url').focus();
        $(this).hide();
    });
});
</script>
@endpush
