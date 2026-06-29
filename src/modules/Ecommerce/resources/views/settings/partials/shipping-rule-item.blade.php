<div class="accordion-item" id="rule-item-{{ $rule->id }}" data-rule-id="{{ $rule->id }}" data-zone-id="{{ $zone->id }}">
    <div class="accordion-header">
        <button class="accordion-button collapsed py-2" type="button"
            data-bs-toggle="collapse" data-bs-target="#collapse-rule-{{ $rule->id }}">
            <div class="w-100 d-flex justify-content-between align-items-center pe-2">
                <div>
                    <span class="fw-semibold rule-name-label">{{ $rule->name }}</span>
                    <div class="small text-muted rule-type-label">{{ $rule->type }}</div>
                </div>
                @if($rule->type !== 'based_on_location')
                    <span class="rule-price-label text-muted small">$ {{ number_format($rule->price, 0, ',', '.') }}</span>
                @endif
            </div>
        </button>
    </div>
    <div id="collapse-rule-{{ $rule->id }}" class="accordion-collapse collapse">
        <div class="accordion-body">
            <form class="rule-edit-form row g-2 align-items-end"
                data-rule-id="{{ $rule->id }}"
                data-zone-id="{{ $zone->id }}">
                @csrf

                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Nombre</label>
                    <input type="text" name="name" class="form-control form-control-sm"
                        value="{{ $rule->name }}" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select name="type" class="form-select form-select-sm rule-type-select">
                        <option value="based_on_price" {{ $rule->type === 'based_on_price' ? 'selected' : '' }}>Basado en el monto total</option>
                        <option value="based_on_weight" {{ $rule->type === 'based_on_weight' ? 'selected' : '' }}>Basado en el peso</option>
                        <option value="based_on_zipcode" {{ $rule->type === 'based_on_zipcode' ? 'selected' : '' }}>Basado en codigo postal</option>
                        <option value="based_on_location" {{ $rule->type === 'based_on_location' ? 'selected' : '' }}>Basado en ubicacion</option>
                    </select>
                </div>

                <div class="rule-from-to-fields col-12 col-md-3 {{ $rule->type === 'based_on_location' ? 'd-none' : '' }}">
                    <div class="row g-1">
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1">Desde</label>
                            <input type="number" name="from" class="form-control form-control-sm"
                                value="{{ $rule->from }}" min="0" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-1">Hasta</label>
                            <input type="number" name="to" class="form-control form-control-sm"
                                value="{{ $rule->to }}" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="rule-price-field col-12 col-md-2 {{ $rule->type === 'based_on_location' ? 'd-none' : '' }}">
                    <label class="form-label form-label-sm mb-1">Precio</label>
                    <input type="number" name="price" class="form-control form-control-sm"
                        value="{{ $rule->price }}" min="0" step="0.01">
                </div>

                <div class="col-12 col-md-1 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-delete-rule"
                        data-rule-id="{{ $rule->id }}" data-zone-id="{{ $zone->id }}"
                        data-rule-name="{{ $rule->name }}">
                        Eliminar
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                </div>
            </form>

            <div class="items-container mt-3" data-rule-id="{{ $rule->id }}" data-zone-id="{{ $zone->id }}"
                {{ $rule->type !== 'based_on_location' ? 'style=display:none' : '' }}>
                <div class="items-table-wrapper mb-2">
                    <p class="text-muted small">Cargando tarifas...</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary btn-add-city-rate"
                    data-rule-id="{{ $rule->id }}" data-zone-id="{{ $zone->id }}">
                    Agregar tarifa de ciudad
                </button>
            </div>
        </div>
    </div>
</div>
