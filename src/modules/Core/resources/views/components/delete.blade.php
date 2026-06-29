<div id="delete-modal" class="modal fade">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="delete-form" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-success mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="my-0">¿Estás seguro de eliminar esto?</h4>
                    <p>Esta acción no se puede deshacer. Todos los datos relacionados pueden eliminarse.</p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            Confirmar eliminación
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
