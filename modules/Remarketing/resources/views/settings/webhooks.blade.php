@extends('layouts.theme')

@section('title', 'Webhooks salientes — Remarketing')

@section('page_header')
    @include('core::components.card', ['title' => 'Webhooks salientes'])
@endsection

@section('content')

    @include('core::components.alerts')

    @php
        $availableEvents = [
            'campaign.sent'        => 'Campaña enviada',
            'campaign.completed'   => 'Campaña completada',
            'message.opened'       => 'Email abierto',
            'message.clicked'      => 'Enlace clic',
            'message.bounced'      => 'Rebote',
            'customer.unsubscribed'=> 'Desuscripción',
            'automation.triggered' => 'Automatización disparada',
            '*'                    => 'Todos los eventos',
        ];
    @endphp

    <div class="row g-3">

        {{-- Webhook list --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fw-semibold mb-0">Endpoints registrados</h5>
                        <p class="card-subtitle mb-0 small text-muted">
                            Los webhooks envían un POST firmado con HMAC-SHA256 cuando ocurre un evento.
                        </p>
                    </div>
                    @can('remarketing.settings.update')
                        <button type="button" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#modalAddWebhook">
                            <i class="fas fa-plus me-1"></i> Añadir webhook
                        </button>
                    @endcan
                </div>
                <div class="card-body p-0">
                    @if($webhooks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>URL</th>
                                        <th>Tienda</th>
                                        <th>Eventos</th>
                                        <th>Estado</th>
                                        <th>Último envío</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($webhooks as $wh)
                                        <tr>
                                            <td class="small fw-semibold text-truncate" style="max-width:240px;">
                                                {{ $wh->url }}
                                            </td>
                                            <td class="small">{{ $wh->store?->name ?? '—' }}</td>
                                            <td>
                                                @foreach($wh->events as $ev)
                                                    <span class="badge bg-secondary-subtle text-secondary me-1">
                                                        {{ $availableEvents[$ev] ?? $ev }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($wh->is_active)
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Desactivado</span>
                                                @endif
                                                @if($wh->failure_count > 0)
                                                    <span class="badge bg-warning-subtle text-warning ms-1">
                                                        {{ $wh->failure_count }} fallos
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="small text-muted">
                                                {{ $wh->last_triggered_at?->diffForHumans() ?? 'Nunca' }}
                                            </td>
                                            <td class="text-end">
                                                @can('remarketing.settings.update')
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <button class="dropdown-item btn-edit-webhook"
                                                                        data-id="{{ $wh->id }}"
                                                                        data-url="{{ $wh->url }}"
                                                                        data-secret="{{ $wh->secret }}"
                                                                        data-events="{{ json_encode($wh->events) }}"
                                                                        data-active="{{ $wh->is_active ? '1' : '0' }}"
                                                                        data-action="{{ route('settings.remarketing.webhooks.update', $wh) }}">
                                                                    Editar
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <form method="POST"
                                                                      action="{{ route('settings.remarketing.webhooks.destroy', $wh) }}"
                                                                      onsubmit="return confirm('¿Eliminar este webhook?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        Eliminar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $webhooks->links() }}
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-webhook fa-2x mb-3 d-block"></i>
                            No hay webhooks configurados.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Docs card --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="fw-semibold mb-0">Cómo funciona</h6>
                </div>
                <div class="card-body small">
                    <p>Cuando ocurre un evento, el sistema envía un <strong>HTTP POST</strong> al endpoint registrado con el siguiente formato:</p>
                    <pre class="bg-light p-2 rounded small mb-3"><code>{
  "event": "campaign.sent",
  "occurred_at": "2026-01-01T10:00:00Z",
  "payload": { ... }
}</code></pre>
                    <p class="mb-1"><strong>Verificación de firma</strong></p>
                    <p>Si configuras un secreto, cada request incluye el header:</p>
                    <pre class="bg-light p-2 rounded small mb-3"><code>X-Remarketing-Signature: sha256=&lt;hmac&gt;</code></pre>
                    <p class="mb-0">El HMAC se calcula sobre el body JSON con tu secreto usando SHA-256.</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Add webhook modal --}}
    <div class="modal fade" id="modalAddWebhook" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('settings.remarketing.webhooks.store') }}" id="formAddWebhook">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Añadir webhook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tienda <span class="text-danger">*</span></label>
                            <select name="store_id" class="form-select">
                                <option value="">Selecciona una tienda</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL del endpoint <span class="text-danger">*</span></label>
                            <input type="url" name="url" class="form-control" placeholder="https://mi-sitio.com/webhook">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secreto <span class="text-muted small">(opcional)</span></label>
                            <input type="text" name="secret" class="form-control"
                                   placeholder="Clave para verificar la firma HMAC" maxlength="64">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Eventos <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @foreach($availableEvents as $value => $label)
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="events[]" value="{{ $value }}"
                                                   id="ev_add_{{ $loop->index }}">
                                            <label class="form-check-label small" for="ev_add_{{ $loop->index }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar webhook</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit webhook modal --}}
    <div class="modal fade" id="modalEditWebhook" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="formEditWebhook">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar webhook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">URL del endpoint <span class="text-danger">*</span></label>
                            <input type="url" name="url" id="edit_url" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secreto <span class="text-muted small">(dejar vacío para no cambiar)</span></label>
                            <input type="text" name="secret" id="edit_secret" class="form-control" maxlength="64">
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="edit_is_active" value="1">
                                <label class="form-check-label" for="edit_is_active">Webhook activo</label>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Eventos <span class="text-danger">*</span></label>
                            <div class="row g-2" id="edit_events_container">
                                @foreach($availableEvents as $value => $label)
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input edit-event-check" type="checkbox"
                                                   name="events[]" value="{{ $value }}"
                                                   id="ev_edit_{{ $loop->index }}">
                                            <label class="form-check-label small" for="ev_edit_{{ $loop->index }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.btn-edit-webhook', function () {
        var btn    = $(this);
        var url    = btn.data('url');
        var secret = btn.data('secret') || '';
        var events = btn.data('events') || [];
        var active = btn.data('active') === 1 || btn.data('active') === '1';
        var action = btn.data('action');

        $('#edit_url').val(url);
        $('#edit_secret').val('');
        $('#edit_is_active').prop('checked', active);
        $('#formEditWebhook').attr('action', action);

        $('.edit-event-check').each(function () {
            $(this).prop('checked', events.indexOf($(this).val()) !== -1);
        });

        var modal = new bootstrap.Modal(document.getElementById('modalEditWebhook'));
        modal.show();
    });
});
</script>
@endpush
