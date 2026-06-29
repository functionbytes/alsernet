{{--
  Partial: condition group
  Usage: cloned by JS. Do not render directly.
--}}
<div class="condition-group card border mb-3" data-group-index="">
    <div class="card-body pb-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="small fw-semibold text-muted">Operador del grupo:</span>
                <select class="form-select form-select-sm group-operator" style="width:80px">
                    <option value="and">AND</option>
                    <option value="or">OR</option>
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-group" title="Eliminar grupo">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="conditions-container">
            {{-- Condition rows injected by JS --}}
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-add-condition-in-group mt-1">
            <i class="fas fa-plus me-1"></i> Añadir condición
        </button>
    </div>
</div>
