@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => isset($prompt) ? 'Editar Prompt' : 'Nuevo Prompt'])

    <div class="widget-content searchable-container list">

        <!-- Breadcrumb -->
        <div class="card card-body mb-3 bg-light-secondary">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('settings.suppliers.prompts.index') }}">
                            <i class="fas fa-arrow-left me-2"></i> Prompts
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ isset($prompt) ? 'Editar' : 'Nuevo' }}</li>
                </ol>
            </nav>
        </div>

        <form id="promptForm">
            @if(isset($prompt))
                <input type="hidden" name="id" value="{{ $prompt->id }}">
            @endif

            <div class="row g-3">

                <!-- Left Column: Configuration -->
                <div class="col-lg-4">
                    <div class="card card-body">
                        <h5 class="mb-3">Configuración</h5>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Etiqueta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label"
                                   value="{{ $prompt->label ?? '' }}" required>
                            <small class="text-muted">Nombre descriptivo del prompt</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alcance <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="scope" id="promptScope" required>
                                <option value="">Seleccionar...</option>
                                <option value="global" {{ isset($prompt) && $prompt->scope === 'global' ? 'selected' : '' }}>
                                    Global (Todos)
                                </option>
                                <option value="supplier" {{ isset($prompt) && $prompt->scope === 'supplier' ? 'selected' : '' }}>
                                    Por Proveedor
                                </option>
                                <option value="category" {{ isset($prompt) && $prompt->scope === 'category' ? 'selected' : '' }}>
                                    Por Categoría
                                </option>
                                <option value="source" {{ isset($prompt) && $prompt->scope === 'source' ? 'selected' : '' }}>
                                    Por Fuente
                                </option>
                            </select>
                        </div>

                        <div class="mb-3" id="supplierContainer" style="display: none;">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-control select2" name="supplier_id">
                                <option value="">Seleccionar...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ isset($prompt) && $prompt->supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="categoryContainer" style="display: none;">
                            <label class="form-label fw-semibold">Categoría</label>
                            <input type="text" class="form-control" name="category_filter"
                                   value="{{ $prompt->category_filter ?? '' }}"
                                   placeholder="Ej: Armas, Munición">
                        </div>

                        <div class="mb-3" id="sourceContainer" style="display: none;">
                            <label class="form-label fw-semibold">Fuente</label>
                            <select class="form-control select2" name="source_id">
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Contenido</label>
                            <select class="form-control select2" name="content_type">
                                <option value="description" {{ isset($prompt) && $prompt->content_type === 'description' ? 'selected' : '' }}>
                                    Descripción
                                </option>
                                <option value="title" {{ isset($prompt) && $prompt->content_type === 'title' ? 'selected' : '' }}>
                                    Título
                                </option>
                                <option value="specifications" {{ isset($prompt) && $prompt->content_type === 'specifications' ? 'selected' : '' }}>
                                    Especificaciones
                                </option>
                                <option value="seo" {{ isset($prompt) && $prompt->content_type === 'seo' ? 'selected' : '' }}>
                                    SEO
                                </option>
                                <option value="features" {{ isset($prompt) && $prompt->content_type === 'features' ? 'selected' : '' }}>
                                    Características
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Prioridad</label>
                            <input type="number" class="form-control" name="priority"
                                   value="{{ $prompt->priority ?? 10 }}" min="1" max="100">
                            <small class="text-muted">Mayor número = mayor prioridad</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-control select2" name="is_active">
                                <option value="1" {{ isset($prompt) && $prompt->is_active ? 'selected' : '' }}>
                                    Activo
                                </option>
                                <option value="0" {{ isset($prompt) && !$prompt->is_active ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <label class="form-label fw-semibold mb-0">Búsqueda web</label>
                                    <p class="text-muted small mb-0">Permite buscar información del producto en internet al generar el contenido</p>
                                </div>
                                <div class="form-check form-switch ms-3 mt-1">
                                    <input class="form-check-input" type="checkbox" name="enable_web_search" value="1" id="enable_web_search"
                                        {{ isset($prompt) && $prompt->enable_web_search ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info alert-sm py-2 px-3">
                            <strong>Variables disponibles:</strong><br>
                            <small>
                                <code>{{ product.name }}</code><br>
                                <code>{{ product.description }}</code><br>
                                <code>{{ product.price }}</code><br>
                                <code>{{ supplier.name }}</code><br>
                                <code>{{ category.name }}</code>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Template Editor -->
                <div class="col-lg-8">
                    <div class="card card-body">
                        <h5 class="mb-3">Template del Prompt</h5>

                        <div class="mb-3">
                            <textarea class="form-control font-monospace" name="template" id="promptTemplate"
                                      rows="20" required>{{ $prompt->template ?? '' }}</textarea>
                            <small class="text-muted">
                                Escribe tu prompt aquí. Usa <code>{{ variable }}</code> para reemplazar valores dinámicamente.
                            </small>
                        </div>

                        <!-- Preview Panel -->
                        <div class="border-top pt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="previewBtn">
                                <i class="fas fa-eye me-2"></i> Vista Previa
                            </button>

                            <div id="previewPanel" style="display: none;">
                                <h6>Vista previa con datos de ejemplo</h6>
                                <div class="bg-light p-3 rounded">
                                    <pre id="previewContent"></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Guardar Prompt
                                </button>
                                <button type="button" class="btn btn-secondary" id="testPromptBtn">
                                    <i class="fas fa-flask me-2"></i> Probar con AI
                                </button>
                                <a href="{{ route('settings.suppliers.prompts.index') }}" class="btn btn-light">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Scope change handler
    $('#promptScope').on('change', function() {
        const scope = $(this).val();

        $('#supplierContainer, #categoryContainer, #sourceContainer').hide();

        if (scope === 'supplier') {
            $('#supplierContainer').show();
        } else if (scope === 'category') {
            $('#categoryContainer').show();
        } else if (scope === 'source') {
            $('#sourceContainer').show();
        }
    });

    // Trigger on page load
    $('#promptScope').trigger('change');

    // Preview button
    $('#previewBtn').on('click', function() {
        const template = $('#promptTemplate').val();

        if (!template.trim()) {
            toastr.warning('El template está vacío', 'Vista Previa');
            return;
        }

        // Sample data for preview
        const sampleData = {
            product: {
                name: 'Rifle de Caza Benelli R1',
                description: 'Rifle semiautomático de alta precisión',
                price: '1.299,00 €'
            },
            supplier: {
                name: 'Armería España'
            },
            category: {
                name: 'Rifles'
            }
        };

        // Simple template replacement
        let preview = template;
        preview = preview.replace(/\{\{\s*product\.name\s*\}\}/g, sampleData.product.name);
        preview = preview.replace(/\{\{\s*product\.description\s*\}\}/g, sampleData.product.description);
        preview = preview.replace(/\{\{\s*product\.price\s*\}\}/g, sampleData.product.price);
        preview = preview.replace(/\{\{\s*supplier\.name\s*\}\}/g, sampleData.supplier.name);
        preview = preview.replace(/\{\{\s*category\.name\s*\}\}/g, sampleData.category.name);

        $('#previewContent').text(preview);
        $('#previewPanel').slideDown();
    });

    // Test prompt with AI
    $('#testPromptBtn').on('click', function() {
        const template = $('#promptTemplate').val();

        if (!template.trim()) {
            toastr.warning('El template está vacío', 'Prueba de AI');
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Probando...');

        $.ajax({
            url: '{{ route("backups.suppliers.prompts.test") }}',
            method: 'POST',
            data: {
                template: template,
                content_type: $('select[name="content_type"]').val()
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Resultado de la Prueba',
                        html: `<pre class="text-start">${response.result}</pre>`,
                        icon: 'success',
                        width: '80%'
                    });
                } else {
                    toastr.error(response.message, 'Prueba de AI');
                }
            },
            error: function() {
                toastr.error('Error al probar con AI', 'Error');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html(originalHtml);
            }
        });
    });

    // Save prompt
    $('#promptForm').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();
        const id = $('input[name="id"]').val();
        const url = id
            ? '{{ route("backups.suppliers.prompts.update", ":id") }}'.replace(':id', id)
            : '{{ route("backups.suppliers.prompts.store") }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Prompts');
                    setTimeout(() => {
                        window.location.href = '{{ route("backups.suppliers.prompts.index") }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error al guardar el prompt';
                toastr.error(message, 'Error');
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
