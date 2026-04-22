@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formReply" method="POST" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.update', $reply->id) }}">

            {{ csrf_field() }}
            @method('PUT')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar respuesta predefinida</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica la configuración de esta respuesta predefinida.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el título y contenido de la respuesta.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Título
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $reply->title) }}" required placeholder="Ej: Saludo de bienvenida">
                            <small class="form-text text-muted">Nombre identificativo de la respuesta</small>
                            @error('title')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Atajo rápido</label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input type="text" name="shortcut" class="form-control" value="{{ old('shortcut', $reply->shortcut) }}" placeholder="greeting">
                            </div>
                            <small class="form-text text-muted">Escribe /atajo en la respuesta para insertar rápidamente (ej: /greeting)</small>
                            @error('shortcut')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Contenido (texto plano)
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="body" class="form-control" rows="5" required placeholder="Escribe el contenido de la respuesta...">{{ old('body', $reply->body) }}</textarea>
                            <small class="form-text text-muted">Versión en texto plano de la respuesta</small>
                            @error('body')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Contenido HTML (opcional)</label>
                            <textarea name="html_body" class="form-control" rows="5" placeholder="<p>Contenido con formato HTML...</p>">{{ old('html_body', $reply->html_body) }}</textarea>
                            <small class="form-text text-muted">Versión con formato HTML (opcional)</small>
                            @error('html_body')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Categories and Tags -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Categorías y etiquetas</h6>
                        <p class="text-muted mb-3">Asocia la respuesta con categorías y etiquetas específicas.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Categorías de tickets</label>
                            <select name="ticket_categories[]" class="form-select" multiple size="5">
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ in_array($category->id, old('ticket_categories', $reply->ticket_categories ?? [])) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Categorías donde esta respuesta estará disponible (vacío = todas)</small>
                            @error('ticket_categories')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Etiquetas (JSON)</label>
                            <textarea name="tags" class="form-control font-monospace" rows="5" placeholder='["soporte", "tecnico", "bienvenida"]'>{{ old('tags', is_array($reply->tags) ? json_encode($reply->tags) : $reply->tags) }}</textarea>
                            <small class="form-text text-muted">Array JSON de etiquetas para organización</small>
                            @error('tags')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Opciones</h6>
                        <p class="text-muted mb-3">Configura la visibilidad y disponibilidad de la respuesta.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_global" value="0">
                                <input type="checkbox" name="is_global" class="form-check-input" id="globalCheck" value="1" {{ old('is_global', $reply->is_global) ? 'checked' : '' }}>
                                <label class="form-check-label" for="globalCheck">
                                    <strong>Respuesta global</strong>
                                    <small class="d-block text-muted">Disponible para todos los agentes. Si se desactiva, solo tú podrás usarla.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', $reply->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Respuesta activa</strong>
                                    <small class="d-block text-muted">Permite que esta respuesta esté disponible para usar.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Volver
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Respuesta actualizada');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
