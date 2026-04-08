@extends('layouts.theme')

@section('title', 'Nueva respuesta predefinida')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <div>
            <h4 class="mb-0 fw-bold">Nueva respuesta predefinida</h4>
            <p class="text-muted mb-0">Crea una respuesta reutilizable para los agentes</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.canned-replies.store') }}">
            @csrf

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Informacion basica</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" placeholder="Ej: Saludo inicial" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Atajo <small class="text-muted fw-normal">(opcional)</small></label>
                        <input type="text" name="shortcut" class="form-control @error('shortcut') is-invalid @enderror"
                               value="{{ old('shortcut') }}" placeholder="Ej: /saludo">
                        <div class="form-text">Escribe este atajo en el chat para insertar la respuesta rápidamente</div>
                        @error('shortcut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Contenido <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror"
                                  rows="8" placeholder="Escribe el contenido de la respuesta..." required>{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Etiquetas <small class="text-muted fw-normal">(separadas por coma)</small></label>
                        <input type="text" name="tags_input" class="form-control @error('tags') is-invalid @enderror"
                               value="{{ old('tags_input', is_array(old('tags')) ? implode(', ', old('tags')) : '') }}"
                               placeholder="Ej: bienvenida, soporte, facturación">
                        <div class="form-text">Ayudan a encontrar la respuesta más fácilmente</div>
                        @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categorías de ticket <small class="text-muted fw-normal">(opcional)</small></label>
                        <select name="ticket_categories[]" class="form-select @error('ticket_categories') is-invalid @enderror" multiple>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ in_array($cat->id, old('ticket_categories', [])) ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('ticket_categories')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_global" value="0">
                            <input type="checkbox" name="is_global" class="form-check-input" id="isGlobal"
                                   value="1" {{ old('is_global') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isGlobal">
                                <strong>Global</strong>
                                <small class="d-block text-muted">Visible para todos los agentes</small>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                                   value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">
                                <strong>Activa</strong>
                                <small class="d-block text-muted">Disponible para usar en tickets</small>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.index') }}" class="btn btn-secondary px-4">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // Convert comma-separated tags to array on submit
    $('form').on('submit', function () {
        const tagsInput = $('input[name="tags_input"]').val();
        if (tagsInput) {
            const tags = tagsInput.split(',').map(t => t.trim()).filter(t => t);
            tags.forEach(function (tag, i) {
                $('<input>').attr({ type: 'hidden', name: 'tags[]', value: tag }).appendTo($('form'));
            });
        }
    });
});
</script>
@endpush
