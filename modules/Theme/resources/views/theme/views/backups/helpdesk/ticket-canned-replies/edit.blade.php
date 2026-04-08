@extends('layouts.theme')

@section('title', 'Editar respuesta: ' . $reply->title)

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('manager.helpdesk.backups.tickets.canned-replies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <div>
            <h4 class="mb-0 fw-bold">Editar: {{ $reply->title }}</h4>
            <p class="text-muted mb-0">Modifica el contenido de la respuesta predefinida</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.canned-replies.update', $reply->id) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Informacion basica</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $reply->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Atajo <small class="text-muted fw-normal">(opcional)</small></label>
                        <input type="text" name="shortcut" class="form-control @error('shortcut') is-invalid @enderror"
                               value="{{ old('shortcut', $reply->shortcut) }}" placeholder="Ej: /saludo">
                        @error('shortcut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Contenido <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror"
                                  rows="8" required>{{ old('body', $reply->body) }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Etiquetas <small class="text-muted fw-normal">(separadas por coma)</small></label>
                        <input type="text" name="tags_input" class="form-control @error('tags') is-invalid @enderror"
                               value="{{ old('tags_input', is_array($reply->tags) ? implode(', ', $reply->tags) : '') }}"
                               placeholder="Ej: bienvenida, soporte, facturación">
                        @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categorías de ticket <small class="text-muted fw-normal">(opcional)</small></label>
                        <select name="ticket_categories[]" class="form-select @error('ticket_categories') is-invalid @enderror" multiple>
                            @php $selectedCategoryIds = $reply->ticketCategories->pluck('id')->toArray(); @endphp
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ in_array($cat->id, old('ticket_categories', $selectedCategoryIds)) ? 'selected' : '' }}>
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
                                   value="1" {{ old('is_global', $reply->is_global) ? 'checked' : '' }}>
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
                                   value="1" {{ old('is_active', $reply->is_active) ? 'checked' : '' }}>
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
                    <i class="fas fa-save me-1"></i> Actualizar
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
    $('form').on('submit', function () {
        const tagsInput = $('input[name="tags_input"]').val();
        if (tagsInput) {
            const tags = tagsInput.split(',').map(t => t.trim()).filter(t => t);
            tags.forEach(function (tag) {
                $('<input>').attr({ type: 'hidden', name: 'tags[]', value: tag }).appendTo($('form'));
            });
        }
    });
});
</script>
@endpush
