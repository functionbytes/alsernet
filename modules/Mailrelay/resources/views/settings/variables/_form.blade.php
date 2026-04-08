<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-info-circle me-2"></i>Información de la variable
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $variable->name ?? '') }}"
                        placeholder="Nombre del cliente"
                        required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="variable_key" class="form-label">Clave de variable <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">{</span>
                        <input type="text"
                            class="form-control font-monospace @error('variable_key') is-invalid @enderror"
                            id="variable_key"
                            name="variable_key"
                            value="{{ old('variable_key', $variable->variable_key ?? '') }}"
                            placeholder="CUSTOMER_NAME"
                            pattern="[A-Z_]+"
                            style="text-transform: uppercase;"
                            required>
                        <span class="input-group-text">}</span>
                        @error('variable_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="form-text text-muted">
                        Solo letras mayúsculas y guiones bajos. Ejemplo: CUSTOMER_NAME, ORDER_ID
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select @error('category') is-invalid @enderror select2" id="category" name="category" required>
                            <option value="">Selecciona una categoría...</option>
                            <option value="system" {{ old('category', $variable->category ?? '') === 'system' ? 'selected' : '' }}>Sistema</option>
                            <option value="customer" {{ old('category', $variable->category ?? '') === 'customer' ? 'selected' : '' }}>Cliente</option>
                            <option value="order" {{ old('category', $variable->category ?? '') === 'order' ? 'selected' : '' }}>Pedido</option>
                            <option value="document" {{ old('category', $variable->category ?? '') === 'document' ? 'selected' : '' }}>Documento</option>
                            <option value="custom" {{ old('category', $variable->category ?? '') === 'custom' ? 'selected' : '' }}>Personalizada</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="module" class="form-label">Módulo</label>
                        <input type="text"
                            class="form-control @error('module') is-invalid @enderror"
                            id="module"
                            name="module"
                            value="{{ old('module', $variable->module ?? '') }}"
                            placeholder="core, orders, customers...">
                        <small class="form-text text-muted">Opcional. Ej: core, orders, customers</small>
                        @error('module')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Descripción de qué contiene esta variable...">{{ old('description', $variable->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="example_value" class="form-label">Valor de ejemplo</label>
                    <input type="text"
                        class="form-control @error('example_value') is-invalid @enderror"
                        id="example_value"
                        name="example_value"
                        value="{{ old('example_value', $variable->example_value ?? '') }}"
                        placeholder="John Doe">
                    <small class="form-text text-muted">
                        Este valor se usará en las vistas previas de templates
                    </small>
                    @error('example_value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-cog me-2"></i>Configuración
                </h6>

                <div class="mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select @error('status') is-invalid @enderror select2" id="status" name="status">
                        <option value="active" {{ old('status', $variable->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $variable->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Orden</label>
                    <input type="number"
                        class="form-control @error('sort_order') is-invalid @enderror"
                        id="sort_order"
                        name="sort_order"
                        value="{{ old('sort_order', $variable->sort_order ?? 0) }}"
                        min="0">
                    <small class="form-text text-muted">Orden de visualización</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                            type="checkbox"
                            id="is_system"
                            name="is_system"
                            value="1"
                            {{ old('is_system', $variable->is_system ?? false) ? 'checked' : '' }}
                            {{ isset($variable) && $variable->is_system ? 'disabled' : '' }}>
                        <label class="form-check-label" for="is_system">
                            Variable del sistema
                        </label>
                    </div>
                    <small class="form-text text-muted">Las variables del sistema no se pueden eliminar</small>
                </div>

                <hr>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a href="{{ route('settings.mailrelay.variables.index') }}" class="btn btn-light">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-lightbulb me-2"></i>Guía de categorías
                </h6>
                <div class="small">
                    <div class="mb-2">
                        <strong class="text-primary">Sistema:</strong> Variables globales como nombre del sistema, URL, fecha actual
                    </div>
                    <div class="mb-2">
                        <strong class="text-success">Cliente:</strong> Datos del cliente (nombre, email, teléfono)
                    </div>
                    <div class="mb-2">
                        <strong class="text-warning">Pedido:</strong> Información de pedidos (número, total, estado)
                    </div>
                    <div class="mb-2">
                        <strong class="text-info">Documento:</strong> Datos de documentos (facturas, albaranes)
                    </div>
                    <div class="mb-2">
                        <strong class="text-secondary">Personalizada:</strong> Variables específicas de tu aplicación
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-convert to uppercase
    $('#variable_key').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z_]/g, '');
    });

    // Auto-generate variable_key from name
    $('#name').on('blur', function() {
        const name = $(this).val();
        const currentKey = $('#variable_key').val();

        // Only auto-generate if key is empty
        if (!currentKey && name) {
            const generatedKey = name
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .replace(/\s+/g, '_');
            $('#variable_key').val(generatedKey);
        }
    });
});
</script>
@endpush
