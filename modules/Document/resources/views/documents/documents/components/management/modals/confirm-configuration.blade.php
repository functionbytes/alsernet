{{-- Modal: Confirmar Configuración del Documento --}}
<div class="modal fade" id="confirmConfigurationModal" tabindex="-1" role="dialog" aria-labelledby="confirmConfigurationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="confirmConfigurationModalLabel">
                   Confirmar cambios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="emailNotificationSection" style="display: none;">
                    <div class="alert alert-info border-0 rounded-2 bg-light-info mb-3">
                        <h6 class="fw-semibold text-info mb-2">
                            Email de notificación
                        </h6>
                        <p class="small mb-0" id="emailTypeDescription"></p>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sendEmailOnStatusChange" name="sendEmailOnStatusChange">
                        <label class="form-check-label" for="sendEmailOnStatusChange">
                            Enviar notificación al cliente sobre el cambio de estado
                        </label>
                    </div>
                </div>
                <p class="mb-0">
                    ¿Estás seguro de que deseas guardar estos cambios?
                </p>
            </div>
            <div class="modal-footer border-top">

                <button type="button" class="btn btn-primary confirm-configuration-btn mb-1 w-100">
                    Guardar cambios
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
