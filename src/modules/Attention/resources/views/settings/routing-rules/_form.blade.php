<div class="row g-3">
    {{-- Nombre --}}
    <div class="col-md-5">
        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $rule->name ?? '') }}" placeholder="Ej: Quejas a soporte" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Prioridad --}}
    <div class="col-md-2">
        <label class="form-label fw-semibold">Prioridad
            <span class="text-muted fw-normal">(1 = alta)</span>
        </label>
        <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror"
               value="{{ old('priority', $rule->priority ?? 10) }}" min="1" max="100">
        @error('priority')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Estado --}}
    <div class="col-md-2 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="is_active" {{ old('is_active', $rule->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Activa</label>
        </div>
    </div>
</div>

{{-- Condiciones --}}
<hr class="my-3">
<p class="text-muted mb-3 fw-semibold text-uppercase">
    Condiciones — dejar en blanco para que aplique a cualquier valor
</p>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tipo de peticiones</label>
        <select name="condition_type_id" class="form-select select2">
            <option value="">— Cualquier tipo —</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}"
                    {{ old('condition_type_id', $rule->condition_type_id ?? '') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Categoría</label>
        <select name="condition_category_id" class="form-select select2">
            <option value="">— Cualquier categoría —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                    {{ old('condition_category_id', $rule->condition_category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Sede</label>
        <select name="condition_sede_id" class="form-select select2">
            <option value="">— Cualquier sede —</option>
            @foreach($sedes as $sede)
                <option value="{{ $sede->id }}"
                    {{ old('condition_sede_id', $rule->condition_sede_id ?? '') == $sede->id ? 'selected' : '' }}>
                    {{ $sede->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

{{-- Acciones --}}
<hr class="my-3">
<p class="text-muted mb-3 fw-semibold text-uppercase">
    Acciones — al menos una debe estar definida
</p>
<div class="row g-3">
    <div class="col-md-5">
        <label class="form-label">Asignar a usuario</label>
        <select name="assign_to_user_id" class="form-select select2">
            <option value="">— Sin asignar —</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                    {{ old('assign_to_user_id', $rule->assign_to_user_id ?? '') == $user->id ? 'selected' : '' }}>
                    {{ $user->full_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-5">
        <label class="form-label">Asignar a departamento</label>
        <select name="assign_to_department_id" class="form-select select2">
            <option value="">— Sin asignar —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}"
                    {{ old('assign_to_department_id', $rule->assign_to_department_id ?? '') == $department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
