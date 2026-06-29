<div class="action-row card mb-2 border" data-index="{{ $index }}">
    <div class="card-body p-3">
        <div class="row g-2 align-items-start">

            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Accion</label>
                <select name="actions[{{ $index }}][type]" class="form-select form-select-sm js-action-type">
                    <option value="">— Selecciona —</option>
                    @foreach(\Modules\Helpdesk\Models\Macro::ACTION_TYPES as $key => $label)
                        <option value="{{ $key }}" @selected(($actionType ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-7">
                <label class="form-label small fw-semibold mb-1">Valor</label>
                {{-- Hidden input carries the initial value so JS can pre-select it --}}
                <input type="hidden" class="js-initial-value" value="{{ $actionValue ?? '' }}">
                <div class="js-action-input-placeholder">
                    <input type="text" class="form-control form-control-sm" disabled placeholder="Selecciona una accion primero">
                </div>
            </div>

            <div class="col-md-1 d-flex align-items-end justify-content-end pb-1">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-action" title="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

        </div>
    </div>
</div>
