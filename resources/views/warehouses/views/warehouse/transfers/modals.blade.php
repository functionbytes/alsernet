<!-- Modal de Transferencia -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt"></i> Transferir Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- Sección origen (readonly) -->
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-arrow-up text-danger"></i> Sección Origen
                    </label>
                    <div id="fromSectionDisplay" class="alert alert-danger mb-0">
                        Selecciona un producto primero
                    </div>
                </div>

                <!-- Cantidad disponible -->
                <div class="mb-4">
                    <label class="form-label">Cantidad Disponible</label>
                    <div class="alert alert-info mb-0">
                        <strong id="availableQuantity">0</strong> unidades
                    </div>
                </div>

                <!-- Cantidad a transferir -->
                <div class="mb-4">
                    <label for="quantityInput" class="form-label">
                        <i class="fas fa-boxes"></i> Cantidad a Transferir
                    </label>
                    <div class="input-group input-group-lg">
                        <button class="btn btn-outline-secondary" type="button" id="decreaseBtn">-</button>
                        <input
                            type="number"
                            class="form-control text-center"
                            id="quantityInput"
                            value="1"
                            min="1"
                            max="1"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="increaseBtn">+</button>
                    </div>
                    <small class="form-text text-muted mt-2">
                        Usa los botones o escribe directamente
                    </small>
                </div>

                <!-- Sección destino -->
                <div class="mb-4">
                    <label for="toSectionId" class="form-label">
                        <i class="fas fa-arrow-down text-success"></i> Sección Destino
                    </label>
                    <select class="form-select form-select-lg" id="toSectionId">
                        <option value="">Selecciona sección destino...</option>
                    </select>
                    <small class="form-text text-muted mt-2">
                        Se muestra disponibilidad de espacios
                    </small>
                </div>

                <!-- Campos ocultos -->
                <input type="hidden" id="productId">
                <input type="hidden" id="fromSectionId">

                <!-- Información adicional -->
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> La transferencia se registrará automáticamente en el historial de movimientos
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="submitTransfer()">
                    <i class="fas fa-check"></i> Confirmar Transferencia
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantityInput');
    const decreaseBtn = document.getElementById('decreaseBtn');
    const increaseBtn = document.getElementById('increaseBtn');

    // Botones de cantidad
    decreaseBtn.addEventListener('click', function() {
        const current = parseInt(quantityInput.value);
        if (current > 1) {
            quantityInput.value = current - 1;
        }
    });

    increaseBtn.addEventListener('click', function() {
        const current = parseInt(quantityInput.value);
        const max = parseInt(quantityInput.max);
        if (current < max) {
            quantityInput.value = current + 1;
        }
    });

    // Validar que no exceda máximo
    quantityInput.addEventListener('change', function() {
        const max = parseInt(this.max);
        if (parseInt(this.value) > max) {
            this.value = max;
        }
        if (parseInt(this.value) < 1) {
            this.value = 1;
        }
    });
});
</script>
