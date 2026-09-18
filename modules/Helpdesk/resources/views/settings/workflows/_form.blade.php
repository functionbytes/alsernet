<div class="row g-3">

    {{-- Nombre --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $workflow->name ?? '') }}"
            placeholder="Ej: Asignar conversaciones urgentes">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Descripcion --}}
    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <textarea name="description" rows="2"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Describe brevemente que hace este workflow...">{{ old('description', $workflow->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <hr class="my-1">
        <h6 class="fw-semibold mb-0">Trigger</h6>
        <small class="text-muted">Define que evento dispara este workflow</small>
    </div>

    {{-- Tipo de trigger --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Tipo de trigger <span class="text-danger">*</span>
        </label>
        <select name="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror">
            <option value="">Seleccionar trigger...</option>
            @foreach(\Modules\Helpdesk\Models\Workflow::TRIGGER_TYPES as $key => $label)
                <option value="{{ $key }}" @selected(old('trigger_type', $workflow->trigger_type ?? '') === $key)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('trigger_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Trigger config --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Configuracion del trigger</label>
        <textarea name="trigger_config" rows="3"
            class="form-control font-monospace @error('trigger_config') is-invalid @enderror"
            placeholder='{"key": "value"}'>{{ old('trigger_config', $workflow?->trigger_config ? json_encode($workflow->trigger_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <div class="form-text">JSON con parametros adicionales del trigger (opcional)</div>
        @error('trigger_config')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <hr class="my-1">
        <h6 class="fw-semibold mb-0">Nodos (JSON)</h6>
        <small class="text-muted">Define la secuencia de acciones que ejecuta el workflow</small>
    </div>

    {{-- Nodos --}}
    <div class="col-12">
        <label class="form-label">Nodos</label>
        <textarea name="nodes" rows="10"
            class="form-control font-monospace @error('nodes') is-invalid @enderror"
            placeholder='[
  {"type": "action", "action": "assign_to_group", "config": {"group_id": 1}},
  {"type": "action", "action": "add_tag", "config": {"tag": "urgente"}}
]'>{{ old('nodes', $workflow?->nodes ? json_encode($workflow->nodes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <div class="form-text">
            Tipos de nodo disponibles:
            @foreach(\Modules\Helpdesk\Models\Workflow::NODE_TYPES as $key => $label)
                <code>{{ $key }}</code>@if(! $loop->last), @endif
            @endforeach
        </div>
        @error('nodes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Activo --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                id="is_active"
                @checked(old('is_active', $workflow->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Activar workflow
            </label>
            <div class="form-text">Si esta activo, se ejecutara automaticamente cuando se dispare el trigger configurado</div>
        </div>
    </div>

</div>
