{{--
  Partial: automation step row
  Usage: cloned by JS. Do not render directly.
--}}
<div class="step-row card border mb-3" data-step-index="">
    <div class="card-body py-2 px-3">
        <div class="d-flex gap-2 align-items-start">

            {{-- Drag handle (visual only in MVP - no drag lib) --}}
            <div class="d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-sm btn-light btn-step-up p-1" title="Subir">
                    <i class="fas fa-chevron-up small"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light btn-step-down p-1" title="Bajar">
                    <i class="fas fa-chevron-down small"></i>
                </button>
            </div>

            <div class="flex-grow-1 row g-2">
                <div class="col-auto">
                    <label class="form-label small mb-1">Tipo</label>
                    <select class="form-select form-select-sm step-type" style="width:140px">
                        <option value="wait">Esperar</option>
                        <option value="send_email">Enviar email</option>
                    </select>
                </div>

                {{-- Config for "wait" --}}
                <div class="col step-config-wait">
                    <label class="form-label small mb-1">Horas de espera</label>
                    <input type="number" class="form-control form-control-sm step-wait-hours" min="1" max="8760" value="24" style="width:100px">
                </div>

                {{-- Config for "send_email" --}}
                <div class="col step-config-email d-none">
                    <div class="row g-2">
                        <div class="col-auto">
                            <label class="form-label small mb-1">Plantilla</label>
                            <select class="form-select form-select-sm step-template">
                                <option value="">Selecciona plantilla</option>
                                @foreach($templates ?? [] as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label small mb-1">Asunto (opcional, sobreescribe plantilla)</label>
                            <input type="text" class="form-control form-control-sm step-subject" placeholder="Deja vacío para usar el de la plantilla">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-step mt-4" title="Eliminar paso">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
