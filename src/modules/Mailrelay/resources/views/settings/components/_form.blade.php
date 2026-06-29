<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="fas fa-info-circle me-2"></i>Información básica
                </h6>

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $component->name ?? '') }}"
                        required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror select2" id="type" name="type" required>
                        <option value="">Selecciona un tipo...</option>
                        <option value="layout" {{ old('type', $component->type ?? '') === 'layout' ? 'selected' : '' }}>Layout</option>
                        <option value="partial" {{ old('type', $component->type ?? '') === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="component" {{ old('type', $component->type ?? '') === 'component' ? 'selected' : '' }}>Component</option>
                    </select>
                    <small class="form-text text-muted">
                        <strong>Layout:</strong> Plantilla completa con {CONTENT}.
                        <strong>Partial:</strong> Fragmento reutilizable.
                        <strong>Component:</strong> Bloque independiente.
                    </small>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3">{{ old('description', $component->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Contenido HTML <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace @error('content') is-invalid @enderror"
                        id="content"
                        name="content"
                        rows="15"
                        required>{{ old('content', $component->content ?? '') }}</textarea>
                    <small class="form-text text-muted">
                        Para layouts, usa <code>{CONTENT}</code> donde se insertará el contenido del template.
                        Puedes usar variables como <code>{CUSTOMER_NAME}</code>, <code>{ORDER_ID}</code>, etc.
                    </small>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-info" id="preview-btn">
                        <i class="fas fa-eye me-2"></i>Vista previa
                    </button>
                    <button type="button" class="btn btn-secondary" id="format-btn">
                        <i class="fas fa-magic me-2"></i>Formatear HTML
                    </button>
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
                        <option value="active" {{ old('status', $component->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status', $component->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
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
                        value="{{ old('sort_order', $component->sort_order ?? 0) }}"
                        min="0">
                    <small class="form-text text-muted">Orden de visualización (menor número = primero)</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="is_default">Layout por defecto</label>
                    <select class="form-select" name="is_default" id="is_default">
                        <option value="1" {{ old('is_default', $component->is_default ?? false) == 1 ? 'selected' : '' }}>Activado</option>
                        <option value="0" {{ old('is_default', $component->is_default ?? false) == 0 ? 'selected' : '' }}>Desactivado</option>
                    </select>
                    <small class="form-text text-muted">Solo aplica a layouts</small>
                </div>

                <hr>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <a href="{{ route('settings.mailrelay.components.index') }}" class="btn btn-light">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-lightbulb me-2"></i>Variables disponibles
                </h6>
                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <div class="list-group-item px-0">
                        <small class="text-muted fw-bold">Sistema</small>
                        <div class="mt-1">
                            <code class="small d-block">{SYSTEM_NAME}</code>
                            <code class="small d-block">{SYSTEM_URL}</code>
                            <code class="small d-block">{CURRENT_DATE}</code>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <small class="text-muted fw-bold">Cliente</small>
                        <div class="mt-1">
                            <code class="small d-block">{CUSTOMER_NAME}</code>
                            <code class="small d-block">{CUSTOMER_EMAIL}</code>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <small class="text-muted fw-bold">Pedido</small>
                        <div class="mt-1">
                            <code class="small d-block">{ORDER_ID}</code>
                            <code class="small d-block">{ORDER_TOTAL}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content" style="min-height: 400px;">
                    <div class="text-center py-5">
                        <p class="text-muted">Haz clic en "Vista previa" para ver el resultado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Preview
    $('#preview-btn').on('click', function() {
        const content = $('#content').val();

        if (!content) {
            toastr.warning('Ingresa contenido HTML primero');
            return;
        }

        $('#previewModal').modal('show');
        $('#preview-content').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

        $.ajax({
            url: '{{ isset($component) ? route("settings.mailrelay.components.preview-ajax", $component->id) : "#" }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { content: content },
            success: function(response) {
                if (response.success) {
                    $('#preview-content').html(response.html);
                } else {
                    $('#preview-content').html('<div class="alert alert-danger">Error: ' + response.error + '</div>');
                }
            },
            error: function() {
                $('#preview-content').html('<div class="alert alert-danger">Error al cargar la vista previa</div>');
            }
        });
    });

    // Format HTML
    $('#format-btn').on('click', function() {
        const content = $('#content').val();

        if (!content) {
            toastr.warning('Ingresa contenido HTML primero');
            return;
        }

        $.ajax({
            url: '{{ route("settings.mailrelay.templates.format-html") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { html: content },
            success: function(response) {
                if (response.success) {
                    $('#content').val(response.html);
                    toastr.success('HTML formateado correctamente');
                } else {
                    toastr.error('Error: ' + response.error);
                }
            },
            error: function() {
                toastr.error('Error al formatear HTML');
            }
        });
    });

    // Auto-adjust textarea height
    $('#content').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>
@endpush
