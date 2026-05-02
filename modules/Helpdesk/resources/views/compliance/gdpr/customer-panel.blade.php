{{--
    Panel GDPR del cliente.
    Recibe: $customer (Customer)
    Incluir en show del cliente con:
        @include('helpdesk::compliance.gdpr.customer-panel', ['customer' => $customer])
--}}
<div class="card mt-4">
    <div class="card-header border-bottom p-3">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-shield-halved me-2 text-muted"></i>
            Datos GDPR — {{ $customer->name ?? $customer->email }}
        </h6>
        <small class="text-muted">Gestión de datos personales conforme al Reglamento General de Protección de Datos</small>
    </div>

    {{-- Exportar datos --}}
    <div class="card-body border-bottom">
        <h6 class="fw-bold mb-1">Exportar datos</h6>
        <p class="text-muted small mb-3">
            Descarga un ZIP con toda la información de este cliente según GDPR.
            El archivo incluye conversaciones, mensajes, sesiones y datos de perfil.
        </p>
        <a href="{{ route('manager.helpdesk.customers.gdpr.export', $customer) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-zipper me-1"></i> Descargar exportación
        </a>
    </div>

    {{-- Eliminar datos --}}
    <div class="card-body">
        <h6 class="fw-bold mb-1">Eliminar datos</h6>
        <div class="alert alert-danger border-0 bg-danger-subtle mb-3 py-2 px-3">
            <i class="fas fa-triangle-exclamation me-1"></i>
            <small><strong>Advertencia:</strong> Esta acción elimina o anonimiza permanentemente los datos del cliente y no puede revertirse.</small>
        </div>

        <form id="gdpr-delete-form"
              action="{{ route('manager.helpdesk.customers.gdpr.delete', $customer) }}"
              method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Tipo de eliminación</label>
                <div class="d-flex flex-column gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="hard" value="0"
                               id="gdpr-soft-{{ $customer->id }}" checked>
                        <label class="form-check-label" for="gdpr-soft-{{ $customer->id }}">
                            <span class="fw-semibold">Anonimizar</span>
                            <small class="text-muted d-block">Reemplaza los datos personales por valores anónimos. Recomendado.</small>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="hard" value="1"
                               id="gdpr-hard-{{ $customer->id }}">
                        <label class="form-check-label" for="gdpr-hard-{{ $customer->id }}">
                            <span class="fw-semibold">Eliminar permanentemente</span>
                            <small class="text-muted d-block">Borra todos los registros del cliente de la base de datos.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="gdpr-confirm-{{ $customer->id }}">
                    Escribe <code>CONFIRMAR</code> para continuar
                </label>
                <input type="text" class="form-control form-control-sm"
                       id="gdpr-confirm-{{ $customer->id }}"
                       name="confirmation"
                       placeholder="CONFIRMAR"
                       autocomplete="off">
            </div>

            <button type="submit" class="btn btn-danger btn-sm w-100">
                <i class="fas fa-user-slash me-1"></i> Proceder con eliminación GDPR
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#gdpr-delete-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                setTimeout(function () { location.reload(); }, 2000);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.confirmation?.[0] || 'Error al procesar la solicitud.';
                toastr.error(msg);
            },
        });
    });
});
</script>
@endpush
