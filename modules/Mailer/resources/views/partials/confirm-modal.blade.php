{{-- Modal de confirmacion reutilizable --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmActionModalLabel">Confirmar accion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="confirmActionMessage">¿Estas seguro de que deseas realizar esta accion?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let targetForm = null;

    $(document).on('click', '[data-confirm]', function(e) {
        e.preventDefault();
        targetForm = $(this).closest('form');
        const message = $(this).data('confirm');
        const title = $(this).data('confirm-title') || 'Confirmar accion';
        const btnClass = $(this).data('confirm-btn-class') || 'btn-danger';
        const btnText = $(this).data('confirm-btn-text') || 'Confirmar';

        $('#confirmActionModalLabel').text(title);
        $('#confirmActionMessage').text(message);
        $('#confirmActionBtn').attr('class', 'btn ' + btnClass).text(btnText);
        $('#confirmActionModal').modal('show');
    });

    $('#confirmActionBtn').on('click', function() {
        if (targetForm) {
            targetForm.submit();
        }
        $('#confirmActionModal').modal('hide');
    });
});
</script>
@endpush
