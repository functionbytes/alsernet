@php
    $csrfToken = csrf_token();
    $storeUrl = route('ecommerce.reviews.reply.store', $review);
    $updateUrl = route('ecommerce.reviews.reply.update', $review);
    $destroyUrl = route('ecommerce.reviews.reply.destroy', $review);
@endphp

<div id="reply-container">
    @if($review->reply)
        <div id="reply-display">
            <div class="p-3 bg-light rounded mb-3">
                <p class="mb-1">{{ $review->reply }}</p>
                <small class="text-muted">Respondido el {{ $review->replied_at?->format('d/m/Y H:i') }}</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-edit-reply">
                    Editar respuesta
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-delete-reply">
                    Eliminar respuesta
                </button>
            </div>
        </div>

        <div id="reply-edit-form" class="d-none">
            <div class="mb-3">
                <label class="form-label">Editar respuesta</label>
                <textarea id="reply-edit-text" class="form-control" rows="4">{{ $review->reply }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="btn-save-edit">Guardar cambios</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-edit">Cancelar</button>
            </div>
        </div>
    @else
        <div id="reply-new-form">
            <div class="mb-3">
                <label for="reply-new-text" class="form-label">Respuesta</label>
                <textarea id="reply-new-text" class="form-control" rows="4"
                          placeholder="Escribe una respuesta al cliente..."></textarea>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btn-save-reply">
                Guardar respuesta
            </button>
        </div>

        <div id="reply-saved" class="d-none">
            <div class="p-3 bg-light rounded mb-3">
                <p class="mb-1" id="reply-saved-text"></p>
                <small class="text-muted" id="reply-saved-date"></small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-edit-reply-new">
                    Editar respuesta
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-delete-reply-new">
                    Eliminar respuesta
                </button>
            </div>
            <div id="reply-edit-form-new" class="mt-3 d-none">
                <div class="mb-3">
                    <label class="form-label">Editar respuesta</label>
                    <textarea id="reply-edit-text-new" class="form-control" rows="4"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-save-edit-new">Guardar cambios</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-edit-new">Cancelar</button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
$(document).ready(function () {
    var csrfToken = '{{ $csrfToken }}';
    var storeUrl  = '{{ $storeUrl }}';
    var updateUrl = '{{ $updateUrl }}';
    var destroyUrl = '{{ $destroyUrl }}';

    // Edit existing reply
    $('#btn-edit-reply').on('click', function () {
        $('#reply-display').addClass('d-none');
        $('#reply-edit-form').removeClass('d-none');
    });

    $('#btn-cancel-edit').on('click', function () {
        $('#reply-edit-form').addClass('d-none');
        $('#reply-display').removeClass('d-none');
    });

    $('#btn-save-edit').on('click', function () {
        var reply = $('#reply-edit-text').val().trim();
        if (!reply) { toastr.warning('La respuesta no puede estar vacia.'); return; }

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function () {
                toastr.success('Respuesta actualizada.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function () { toastr.error('No se pudo actualizar la respuesta.'); }
        });
    });

    $('#btn-delete-reply').on('click', function () {
        if (!confirm('Eliminar la respuesta?')) { return; }

        $.ajax({
            url: destroyUrl,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Respuesta eliminada.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function () { toastr.error('No se pudo eliminar la respuesta.'); }
        });
    });

    // New reply
    $('#btn-save-reply').on('click', function () {
        var reply = $('#reply-new-text').val().trim();
        if (!reply) { toastr.warning('La respuesta no puede estar vacia.'); return; }

        $.ajax({
            url: storeUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function (data) {
                toastr.success('Respuesta guardada.');
                $('#reply-new-form').addClass('d-none');
                $('#reply-saved-text').text(reply);
                $('#reply-saved-date').text('Respondido el ' + (data.replied_at || 'ahora'));
                $('#reply-edit-text-new').val(reply);
                $('#reply-saved').removeClass('d-none');
            },
            error: function () { toastr.error('No se pudo guardar la respuesta.'); }
        });
    });

    $('#btn-edit-reply-new').on('click', function () {
        $('#reply-edit-form-new').removeClass('d-none');
    });

    $('#btn-cancel-edit-new').on('click', function () {
        $('#reply-edit-form-new').addClass('d-none');
    });

    $('#btn-save-edit-new').on('click', function () {
        var reply = $('#reply-edit-text-new').val().trim();
        if (!reply) { toastr.warning('La respuesta no puede estar vacia.'); return; }

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { reply: reply },
            success: function () {
                toastr.success('Respuesta actualizada.');
                $('#reply-saved-text').text(reply);
                $('#reply-edit-form-new').addClass('d-none');
            },
            error: function () { toastr.error('No se pudo actualizar la respuesta.'); }
        });
    });

    $('#btn-delete-reply-new').on('click', function () {
        if (!confirm('Eliminar la respuesta?')) { return; }

        $.ajax({
            url: destroyUrl,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                toastr.success('Respuesta eliminada.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function () { toastr.error('No se pudo eliminar la respuesta.'); }
        });
    });
});
</script>
@endpush
