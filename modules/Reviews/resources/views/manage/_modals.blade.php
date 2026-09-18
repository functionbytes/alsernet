{{-- Modales de la ficha. Botones apilados a ancho completo, el primario arriba,
     que es el patrón de diálogos del panel. --}}

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('reviews.reject', $review) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Retirar la opinión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Dejará de verse en la ficha del producto, también en los demás idiomas.</p>
                    <label class="form-label" for="rejectReason">Motivo</label>
                    <input type="text" class="form-control" id="rejectReason" name="reason" maxlength="255"
                           placeholder="Opcional: por qué se retira">
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Retirar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="answerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('reviews.answer', $review) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Responder públicamente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Se publica bajo la opinión, en la ficha del producto.</p>
                    <label class="form-label" for="answerText">Respuesta</label>
                    <textarea class="form-control" id="answerText" name="answer" rows="4" maxlength="2000" required>{{ $review->answer }}</textarea>
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Publicar respuesta</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editTranslationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" id="editTranslationForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Corregir la traducción <span id="editTranslationLang"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Al corregirla se retira su aprobación: habrá que volver a aprobarla para publicarla.</p>
                    <div class="mb-3">
                        <label class="form-label" for="editTranslationTitle">Título</label>
                        <input type="text" class="form-control" id="editTranslationTitle" name="title" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editTranslationComment">Texto</label>
                        <textarea class="form-control" id="editTranslationComment" name="comment" rows="5" maxlength="4000" required></textarea>
                    </div>
                    <div>
                        <label class="form-label" for="editTranslationAnswer">Respuesta</label>
                        <textarea class="form-control" id="editTranslationAnswer" name="answer" rows="3" maxlength="4000"></textarea>
                    </div>
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('editTranslationModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var t = event.relatedTarget;
        document.getElementById('editTranslationForm').action = t.getAttribute('data-action');
        document.getElementById('editTranslationLang').textContent = t.getAttribute('data-lang') || '';
        document.getElementById('editTranslationTitle').value = t.getAttribute('data-title') || '';
        document.getElementById('editTranslationComment').value = t.getAttribute('data-comment') || '';
        document.getElementById('editTranslationAnswer').value = t.getAttribute('data-answer') || '';
    });
});
</script>
@endpush
