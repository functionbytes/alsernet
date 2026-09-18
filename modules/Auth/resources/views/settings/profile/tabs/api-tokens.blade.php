<div class="row g-4">
    <div class="col-12">
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h6 class="mb-1 fw-bold">Tokens de API personales</h6>
                <p class="text-muted mb-0">Crea tokens para aplicaciones o scripts que accedan a la API en tu nombre</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewToken">
                <i class="fas fa-plus me-1"></i> Nuevo token
            </button>
        </div>

        <div class="card border">
            <div class="card-body p-4">
                <div id="tokens-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>

                <div id="tokens-list" class="d-none"></div>

                <div class="alert alert-warning border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-triangle-exclamation fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Cuida tus tokens</h6>
                            <p class="mb-0 small">
                                Los tokens permiten acceso total a tu cuenta vía API. Nunca los compartas
                                ni los incluyas en repositorios públicos. Revoca cualquier token que no reconozcas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Nuevo token --}}
<div class="modal fade" id="newTokenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear token de API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNewToken" onsubmit="return false">
                    @csrf
                    <div class="mb-3">
                        <label for="token_name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="token_name" name="name"
                               placeholder="Ej. App móvil, Script backup..." maxlength="100" required>
                        <small class="text-muted">Solo para identificarlo después. No afecta el token en sí.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-block">
                <button type="button" class="btn btn-primary w-100 mb-2" id="btnCreateToken">
                    Generar token
                </button>
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Token generado --}}
<div class="modal fade" id="showTokenModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle text-success me-1"></i> Token creado</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-0 small mb-3">
                    <strong>Copia este token ahora.</strong> Por seguridad, no podrás volver a verlo.
                </div>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace small" id="generated_token" readonly>
                    <button type="button" class="btn btn-outline-secondary" id="btnCopyToken">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer d-block">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" id="btnTokenDone">
                    Ya lo copié
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {

    function formatDate(iso) {
        if (!iso) { return 'Nunca'; }
        try {
            return new Date(iso).toLocaleString('es-ES', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) { return iso; }
    }

    function renderTokens(tokens) {
        const $list = $('#tokens-list');
        $list.empty();

        if (!tokens.length) {
            $list.append('<p class="text-muted text-center py-4">No tienes tokens generados aún.</p>');
            return;
        }

        tokens.forEach(function (t) {
            $list.append(
                '<div class="d-flex align-items-start justify-content-between py-3 border-bottom token-item" data-id="' + t.id + '">' +
                '<div class="d-flex align-items-start gap-3 flex-grow-1">' +
                '<div class="bg-light-primary rounded-1 p-3 d-flex align-items-center justify-content-center">' +
                '<i class="fas fa-key text-primary"></i>' +
                '</div>' +
                '<div class="flex-grow-1">' +
                '<h6 class="mb-1">' + $('<div>').text(t.name).html() + '</h6>' +
                '<small class="text-muted d-block"><i class="fas fa-calendar me-1"></i>Creado: ' + formatDate(t.created_at) + '</small>' +
                '<small class="text-muted d-block mt-1"><i class="fas fa-clock me-1"></i>Último uso: ' + formatDate(t.last_used_at) + '</small>' +
                '</div>' +
                '</div>' +
                '<div class="ms-2">' +
                '<div class="dropdown">' +
                '<button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><a class="dropdown-item btn-token-revoke" href="#" data-id="' + t.id + '">Revocar</a></li>' +
                '</ul></div>' +
                '</div>' +
                '</div>'
            );
        });
    }

    function loadTokens() {
        $('#tokens-loading').removeClass('d-none');
        $('#tokens-list').addClass('d-none');

        $.get("{{ route('settings.auth.api-tokens.list') }}", function (response) {
            $('#tokens-loading').addClass('d-none');
            $('#tokens-list').removeClass('d-none');
            renderTokens(response.tokens || []);
        }).fail(function () {
            $('#tokens-loading').addClass('d-none');
            $('#tokens-list').removeClass('d-none').html('<p class="text-danger">Error al cargar tokens.</p>');
        });
    }

    $('#btnNewToken').on('click', function () {
        $('#token_name').val('');
        new bootstrap.Modal(document.getElementById('newTokenModal')).show();
    });

    $('#btnCreateToken').on('click', function () {
        const name = $('#token_name').val().trim();
        if (!name) {
            toastr.warning('Ingresa un nombre para el token.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');

        $.ajax({
            url: "{{ route('settings.auth.api-tokens.store') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { name: name },
            success: function (response) {
                bootstrap.Modal.getInstance(document.getElementById('newTokenModal')).hide();
                $('#generated_token').val(response.token);
                new bootstrap.Modal(document.getElementById('showTokenModal')).show();
                loadTokens();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al crear token.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-key me-1"></i> Generar token');
            }
        });
    });

    $('#btnCopyToken').on('click', function () {
        const $input = $('#generated_token');
        $input.select();
        document.execCommand('copy');
        toastr.success('Token copiado al portapapeles.');
    });

    $(document).on('click', '.btn-token-revoke', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Revocar este token? Cualquier aplicación que lo use dejará de funcionar.')) { return; }

        $.ajax({
            url: "{{ url('panel/setting/auth/api-tokens') }}/" + id,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'DELETE'
            },
            data: { _method: 'DELETE' },
            success: function (response) {
                toastr.success(response.message);
                loadTokens();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al revocar.');
            }
        });
    });

    const tokensTabEl = document.getElementById('api-tokens-tab');
    if (tokensTabEl) {
        tokensTabEl.addEventListener('shown.bs.tab', loadTokens);
    }

    @if ($activeTab === 'api-tokens')
    loadTokens();
    @endif
});
</script>
@endpush
