@extends('layouts.theme')

@section('title', 'Editar plantilla de mensaje')

@section('content')

    <div class="card w-100">

        <form id="formMessageTemplate" method="POST" action="{{ route('settings.chat.message.update', $template) }}">

            @csrf
            @method('PATCH')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar plantilla de mensaje</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica el contenido y configuración de la plantilla de mensaje.
                </p>

                @if($template->usage_count > 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Esta plantilla ha sido usada <strong>{{ number_format($template->usage_count) }}</strong> vez/veces.
                        @if($template->last_used_at)
                            Último uso {{ $template->last_used_at->diffForHumans() }}.
                        @endif
                    </div>
                @endif

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y categoría de la plantilla de mensaje.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre de la plantilla
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $template->name) }}" required
                                   placeholder="Ej: Mensaje de bienvenida, Recordatorio de seguimiento">
                            <small class="form-text text-muted">Un nombre descriptivo para esta plantilla</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Categoría</label>
                            <select name="category" class="form-control select2 @error('category') is-invalid @enderror">
                                <option value="">Seleccionar categoría</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $template->category) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Atajo</label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input type="text" name="shortcut" id="shortcut"
                                       class="form-control @error('shortcut') is-invalid @enderror"
                                       value="{{ old('shortcut', $template->shortcut) }}"
                                       placeholder="Ej: bienvenida, seguimiento">
                            </div>
                            <small class="form-text text-muted">Solo minúsculas, números y guiones</small>
                            @error('shortcut')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Contenido</h6>
                        <p class="text-muted small mb-3">Escribe el contenido de la plantilla. Puedes insertar variables dinámicas que se reemplazarán automáticamente.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Contenido de la plantilla
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="content" id="content"
                                      class="form-control @error('content') is-invalid @enderror"
                                      rows="8" required
                                      placeholder="Ingresa el contenido de tu plantilla aquí. Usa las variables disponibles...">{{ old('content', $template->content) }}</textarea>
                            <small class="form-text text-muted">El mensaje que será insertado en las conversaciones</small>
                            @error('content')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Variable Buttons -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Variables disponibles</label>
                            <p class="text-muted small mb-2">Haz clic para insertar una variable en el contenido:</p>

                            @foreach($availableVariables as $group => $variables)
                                <div class="mb-3">
                                    <h6 class="text-uppercase small fw-bold text-muted mb-2">{{ ucfirst($group) }}</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($variables as $key => $label)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary insert-variable"
                                                    data-variable="{{ $group }}.{{ $key }}"
                                                    title="{{ $label }}">
                                                <i class="fas fa-code"></i> {{ '{{' . $group . '.' . $key . '}' . '}' }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Vista previa</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div id="preview-content" class="text-muted">
                                        Escribe el contenido de tu plantilla para ver una vista previa...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración</h6>
                        <p class="text-muted small mb-3">Configura la visibilidad de la plantilla.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_public" value="0">
                                <input type="checkbox" name="is_public" class="form-check-input" id="is_public" value="1" {{ old('is_public', $template->is_public) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">
                                    Hacer esta plantilla pública
                                </label>
                            </div>
                            <small class="form-text text-muted">Las plantillas públicas pueden ser usadas por todos los miembros del equipo</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.chat.message.index') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $contentTextarea = $('#content');
    const $previewContent = $('#preview-content');

    // Insert variable into textarea
    $('.insert-variable').on('click', function() {
        const variable = '{' + '{' + $(this).data('variable') + '}' + '}';
        const textarea = $contentTextarea[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();

        updatePreview();
    });

    // Update preview on content change
    $contentTextarea.on('input', updatePreview);

    // Update preview function
    function updatePreview() {
        let content = $contentTextarea.val();

        if (!content.trim()) {
            const $emptyMsg = $('<span>').addClass('text-muted').text('Escribe el contenido de tu plantilla para ver una vista previa...');
            $previewContent.empty().append($emptyMsg);
            return;
        }

        // Escape HTML to prevent XSS
        const escapeHtml = (text) => {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        // Escape user content first
        content = escapeHtml(content);

        // Now replace variables with example values (safe since content is already escaped)
        content = content
            .replace(/\{\{contact\.name\}\}/g, '<span class="badge bg-primary">John Doe</span>')
            .replace(/\{\{contact\.email\}\}/g, '<span class="badge bg-primary">john@example.com</span>')
            .replace(/\{\{contact\.phone\}\}/g, '<span class="badge bg-primary">+1234567890</span>')
            .replace(/\{\{agent\.name\}\}/g, '<span class="badge bg-success">Agente de Soporte</span>')
            .replace(/\{\{agent\.email\}\}/g, '<span class="badge bg-success">agente@empresa.com</span>')
            .replace(/\{\{conversation\.id\}\}/g, '<span class="badge bg-info">12345</span>')
            .replace(/\{\{conversation\.status\}\}/g, '<span class="badge bg-info">abierto</span>')
            .replace(/\{\{account\.name\}\}/g, '<span class="badge bg-warning text-dark">Nombre Empresa</span>')
            .replace(/\{\{system\.date\}\}/g, '<span class="badge bg-secondary">2024-01-15</span>')
            .replace(/\{\{system\.time\}\}/g, '<span class="badge bg-secondary">14:30:00</span>')
            .replace(/\{\{system\.datetime\}\}/g, '<span class="badge bg-secondary">2024-01-15 14:30:00</span>')
            .replace(/\n/g, '<br>');

        $previewContent.html(content);
    }

    // Sanitize shortcut input
    $('#shortcut').on('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    });

    // Initial preview
    updatePreview();

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
