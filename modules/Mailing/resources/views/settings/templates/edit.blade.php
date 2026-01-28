@extends('layouts.theme')

@section('title', 'Editar Plantilla de Email')

@section('head')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    .form-section {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }
    #email-content {
        min-height: 400px;
        background: #fff;
    }
    .ql-editor {
        min-height: 350px;
        font-size: 14px;
    }
    .variable-tag {
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.85rem;
    }
    .variable-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .preview-section {
        position: sticky;
        top: 20px;
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    .template-info {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <a href="{{ route('settings.mailrelay.templates.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <h1 class="h2 mb-0">Editar plantilla</h1>
        </div>
        <p class="text-muted mb-0">Modifica los detalles de la plantilla de email</p>
    </div>

    <!-- Template Info -->
    <div class="template-info">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted">Creada:</small>
                <strong class="d-block">{{ $template->created_at->diffForHumans() }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Última modificación:</small>
                <strong class="d-block">{{ $template->updated_at->diffForHumans() }}</strong>
            </div>
            @if(isset($campaignsCount) && $campaignsCount > 0)
            <div class="col-md-4">
                <small class="text-muted">Uso:</small>
                <strong class="d-block">{{ $campaignsCount }} {{ $campaignsCount === 1 ? 'campaña' : 'campañas' }}</strong>
            </div>
            @endif
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.templates.update', $template->id) }}" method="POST" id="templateForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Información básica
                    </h3>

                    <div class="mb-3">
                        <label for="name" class="form-label required-field">Nombre de la plantilla</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $template->name) }}"
                               placeholder="Ej: Bienvenida nuevos suscriptores" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Nombre interno para identificar la plantilla</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Breve descripción del propósito de esta plantilla">{{ old('description', $template->description) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label required-field">Tipo de plantilla</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">Selecciona un tipo</option>
                            <option value="welcome" {{ old('type', $template->type) == 'welcome' ? 'selected' : '' }}>Bienvenida</option>
                            <option value="newsletter" {{ old('type', $template->type) == 'newsletter' ? 'selected' : '' }}>Newsletter</option>
                            <option value="transactional" {{ old('type', $template->type) == 'transactional' ? 'selected' : '' }}>Transaccional</option>
                            <option value="custom" {{ old('type', $template->type) == 'custom' ? 'selected' : '' }}>Personalizado</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Email Content -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-envelope-open me-2"></i>Contenido del email
                    </h3>

                    <div class="mb-3">
                        <label for="subject" class="form-label required-field">Asunto del email</label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror"
                               id="subject" name="subject" value="{{ old('subject', $template->subject) }}"
                               placeholder="Ej: ¡Bienvenido a nuestra comunidad!" required>
                        @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Este será el asunto que verán los destinatarios</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Variables disponibles</label>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Haz clic en las variables para insertarlas en el contenido:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{name}}')">
                                    <i class="fas fa-plus"></i> {{name}}
                                </span>
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{email}}')">
                                    <i class="fas fa-plus"></i> {{email}}
                                </span>
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{first_name}}')">
                                    <i class="fas fa-plus"></i> {{first_name}}
                                </span>
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{last_name}}')">
                                    <i class="fas fa-plus"></i> {{last_name}}
                                </span>
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{company}}')">
                                    <i class="fas fa-plus"></i> {{company}}
                                </span>
                                <span class="badge bg-light text-dark variable-tag" onclick="insertVariable('{{unsubscribe_url}}')">
                                    <i class="fas fa-plus"></i> {{unsubscribe_url}}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label required-field">Contenido HTML</label>
                        <div id="email-content"></div>
                        <input type="hidden" name="content" id="content">
                        @error('content')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#previewModal">
                            <i class="fas fa-eye me-2"></i>Vista previa
                        </button>
                    </div>
                </div>

                <!-- Settings -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog me-2"></i>Configuración
                    </h3>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            <strong>Plantilla activa</strong>
                            <br><small class="text-muted">Las plantillas activas están disponibles para usar en campañas</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="preview-section">
                    <!-- Tips Card -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-lightbulb me-2"></i>Consejos útiles
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0 ps-3">
                                <li class="mb-2">Usa un asunto claro y atractivo</li>
                                <li class="mb-2">Personaliza con variables como {{name}}</li>
                                <li class="mb-2">Incluye siempre un enlace para darse de baja</li>
                                <li class="mb-2">Mantén el diseño simple y responsive</li>
                                <li class="mb-2">Prueba la plantilla antes de usarla</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Guardar cambios
                                </button>
                                <form action="{{ route('settings.mailrelay.templates.duplicate', $template) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="fas fa-copy me-2"></i>Duplicar plantilla
                                    </button>
                                </form>
                                <a href="{{ route('settings.mailrelay.templates.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Stats Card (Optional) -->
                    @if(isset($template->campaigns_count) && $template->campaigns_count > 0)
                    <div class="card mt-3">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas de uso
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <h3 class="text-primary mb-1">{{ $template->campaigns_count }}</h3>
                                <small class="text-muted">Campañas que usan esta plantilla</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2"></i>Vista previa de la plantilla
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Asunto:</strong>
                    <div class="border p-2 bg-light rounded" id="preview-subject">-</div>
                </div>
                <div>
                    <strong>Contenido:</strong>
                    <iframe id="preview-iframe" style="width: 100%; height: 400px; border: 1px solid #dee2e6;" frameborder="0"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    // Initialize Quill editor
    var quill = new Quill('#email-content', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'font': [] }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                ['clean']
            ]
        },
        placeholder: 'Escribe el contenido de tu email aquí...'
    });

    // Load existing content
    @if(old('content'))
    quill.root.innerHTML = {!! json_encode(old('content')) !!};
    @else
    quill.root.innerHTML = {!! json_encode($template->content ?? '') !!};
    @endif

    // Insert variable function
    function insertVariable(variable) {
        var range = quill.getSelection(true);
        if (range) {
            quill.insertText(range.index, variable);
            quill.setSelection(range.index + variable.length);
        } else {
            quill.insertText(quill.getLength(), variable);
        }
    }

    // Form submission
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        // Set hidden input with Quill content
        document.getElementById('content').value = quill.root.innerHTML;
    });

    // Preview functionality
    document.getElementById('previewModal').addEventListener('show.bs.modal', function() {
        var subject = document.getElementById('subject').value || 'Sin asunto';
        var content = quill.root.innerHTML;

        document.getElementById('preview-subject').textContent = subject;

        var iframe = document.getElementById('preview-iframe');
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        line-height: 1.6;
                    }
                </style>
            </head>
            <body>${content}</body>
            </html>
        `);
        iframeDoc.close();
    });
</script>
@endsection
