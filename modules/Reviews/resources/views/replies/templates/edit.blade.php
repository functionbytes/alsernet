@extends('layouts.theme')

@section('title', 'Editar plantilla de respuesta')

@section('content')
    <div class="row">
        <!-- Formulario principal (izquierda) -->
        <div class="col-lg-8">
            <div class="card w-100">
                <form action="{{ route('settings.reviews.templates.update', $template) }}" method="POST" id="template-form">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <h5 class="mb-2">Editar plantilla de respuesta</h5>
                        <p class="card-subtitle mb-3">
                            Modifica la plantilla para responder automáticamente a las reseñas de tus clientes. Utiliza variables dinámicas para personalizar cada respuesta.
                        </p>

                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="name" class="control-label col-form-label">Nombre de la plantilla</label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $template->name) }}"
                                           placeholder="Ej: Respuesta a reseña positiva"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Categoría -->
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label for="category" class="control-label col-form-label">Categoría</label>
                                    <select class="form-select select2 @error('category') is-invalid @enderror"
                                            id="category"
                                            name="category"
                                            data-placeholder="Seleccionar categoría"
                                            required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="positive" {{ old('category', $template->category) === 'positive' ? 'selected' : '' }}>Positiva</option>
                                        <option value="negative" {{ old('category', $template->category) === 'negative' ? 'selected' : '' }}>Negativa</option>
                                        <option value="neutral" {{ old('category', $template->category) === 'neutral' ? 'selected' : '' }}>Neutral</option>
                                        <option value="general" {{ old('category', $template->category) === 'general' ? 'selected' : '' }}>General</option>
                                    </select>
                                    <small class="form-text text-muted">Ayuda a organizar las plantillas por tipo de reseña</small>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Cuerpo de la plantilla -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="body" class="control-label col-form-label">Cuerpo de la respuesta</label>
                                    <textarea class="form-control @error('body') is-invalid @enderror"
                                              id="body"
                                              name="body"
                                              rows="10"
                                              placeholder="Escribe la plantilla de respuesta..."
                                              required>{{ old('body', $template->body) }}</textarea>
                                    <small class="form-text text-muted">Mínimo 10 caracteres, máximo 4000 caracteres</small>
                                    @error('body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Estado activo -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="is_active" class="control-label col-form-label">Estado de la plantilla</label>
                                    <select class="form-select select2"
                                            id="is_active"
                                            name="is_active"
                                            data-placeholder="Seleccionar estado">
                                        <option value="1" {{ old('is_active', $template->is_active) == 1 ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active', $template->is_active) == 0 ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    <small class="form-text text-muted">Solo las plantillas activas estarán disponibles para usar</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer gap-2">
                            <button type="submit" class="btn btn-info px-4 waves-effect waves-light w-100 mb-1">
                                Guardar cambios
                            </button>
                            <a href="{{ route('settings.reviews.templates.index') }}" class="btn btn-secondary  w-100  mb-1">
                                Cancelar
                            </a>
                            <button type="button" class="btn btn-secondary ms-auto  w-100  mb-1" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                Eliminar plantilla
                            </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel informativo (derecha) -->
        <div class="col-lg-4">
            <!-- Variables disponibles -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Variables disponibles
                    </h6>
                    <p class="card-text text-muted mb-3">
                        Haz clic en cualquier variable para insertarla en el cursor del editor.
                    </p>
                    <div class="small">
                        <div class="mb-2">
                            <code class="variable-tag">{reviewer_name}</code>
                            <span class="text-muted d-block ps-2">Nombre del reviewer</span>
                        </div>
                        <div class="mb-2">
                            <code class="variable-tag">{location_name}</code>
                            <span class="text-muted d-block ps-2">Nombre del negocio/ubicación</span>
                        </div>
                        <div class="mb-2">
                            <code class="variable-tag">{star_rating}</code>
                            <span class="text-muted d-block ps-2">Calificación en estrellas</span>
                        </div>
                        <div class="mb-2">
                            <code class="variable-tag">{comment_summary}</code>
                            <span class="text-muted d-block ps-2">Resumen del comentario</span>
                        </div>
                        <div class="mb-0">
                            <code class="variable-tag">{date}</code>
                            <span class="text-muted d-block ps-2">Fecha de la reseña</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Ejemplo de uso
                    </h6>
                    <div class=" text-muted bg-light p-3 rounded">
                        <p class="mb-0">
                            Hola {reviewer_name}, muchas gracias por tu reseña de {star_rating} estrellas en {location_name}.
                            Tu opinión es muy importante para nosotros y nos ayuda a mejorar cada día.
                        </p>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        Consejos para escribir respuestas
                    </h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Sé personal y auténtico en tus respuestas</li>
                        <li class="mb-2">Agradece siempre el tiempo dedicado</li>
                        <li class="mb-2">Responde específicamente a los comentarios</li>
                        <li class="mb-2">Mantén un tono profesional y amable</li>
                        <li>Usa las variables para personalizar</li>
                    </ul>
                </div>

            <!-- Estadísticas de uso -->
            @if($template->usage_count > 0)
                    <hr>
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            Estadísticas de uso
                        </h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-chart-line text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <p class="mb-0  text-muted">Veces utilizada</p>
                                <h4 class="mb-0">{{ $template->usage_count }}</h4>
                            </div>
                        </div>
                    </div>
            @endif
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar esta plantilla?</p>
                    <p class="text-muted mb-0">
                        <strong>{{ $template->name }}</strong>
                    </p>
                    @if($template->usage_count > 0)
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Esta plantilla ha sido utilizada {{ $template->usage_count }} {{ $template->usage_count === 1 ? 'vez' : 'veces' }}
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('settings.reviews.templates.destroy', $template) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .variable-tag {
        background-color: #f0f0f0;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-block;
        margin-bottom: 0.25rem;
    }
    .variable-tag:hover {
        background-color: #90bb13;
        color: white;
        transform: translateY(-1px);
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('#category, #is_active').select2({
        minimumResultsForSearch: Infinity, // Ocultar buscador para pocas opciones
        width: '100%'
    });

    // Auto-focus primer campo
    $('#name').focus();

    // Contador de caracteres
    const $body = $('#body');
    const maxLength = 4000;

    function updateCharCount() {
        const current = $body.val().length;
        const remaining = maxLength - current;
        const color = remaining < 100 ? 'text-danger' : 'text-muted';

        let $counter = $('#char-counter');
        if (!$counter.length) {
            $counter = $('<small id="char-counter" class="form-text d-block mt-1"></small>');
            $body.parent().append($counter);
        }

        $counter
            .removeClass('text-muted text-danger')
            .addClass(color)
            .text(`${current} / ${maxLength} caracteres`);
    }

    $body.on('input', updateCharCount);
    updateCharCount();

    // Insertar variable al hacer clic
    $(document).on('click', '.variable-tag', function() {
        const variable = $(this).text();
        const $textarea = $('#body');
        const cursorPos = $textarea[0].selectionStart;
        const textBefore = $textarea.val().substring(0, cursorPos);
        const textAfter = $textarea.val().substring(cursorPos);

        $textarea.val(textBefore + variable + textAfter);
        $textarea[0].selectionStart = $textarea[0].selectionEnd = cursorPos + variable.length;
        $textarea.focus();
        updateCharCount();

        // Visual feedback
        const $this = $(this);
        const originalBg = $this.css('background-color');
        $this.css('background-color', '#13C672');
        setTimeout(() => {
            $this.css('background-color', originalBg);
        }, 300);

        // Mostrar notificación
        toastr.success('Variable insertada en el editor', '', {
            timeOut: 1500,
            closeButton: false,
            progressBar: false
        });
    });
});
</script>
@endpush
