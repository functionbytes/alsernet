@extends('layouts.theme')

@section('title', 'Editar respuesta: ' . $reply->title)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar respuesta'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="replyForm" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.update', $reply->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $reply->title }}</h5>
                        <small class="text-muted">Modifica el contenido de la respuesta predefinida</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Informacion basica --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
                        <p class="text-muted small mb-3">Titulo y atajo para usar esta respuesta rapidamente</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Titulo <span class="text-danger">*</span></label>
                                <input type="text" name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $reply->title) }}"
                                       required>
                                @error('title')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Atajo</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="short_code"
                                           class="form-control @error('short_code') is-invalid @enderror"
                                           value="{{ old('short_code', $reply->short_code) }}"
                                           placeholder="greet">
                                </div>
                                <small class="form-text text-muted">Escribe este atajo en el chat para insertar la respuesta rapidamente</small>
                                @error('short_code')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Contenido --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Contenido</h6>
                        <p class="text-muted small mb-3">Texto que se insertara al usar la respuesta. Puedes usar variables como {agent_name} o {ticket_number}</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Cuerpo <span class="text-danger">*</span></label>
                                <textarea name="content"
                                          class="form-control @error('content') is-invalid @enderror"
                                          rows="6"
                                          required>{{ old('content', $reply->content) }}</textarea>
                                @error('content')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cuerpo HTML <small class="text-muted fw-normal">(opcional, para emails enriquecidos)</small></label>
                                <textarea name="html_body"
                                          class="form-control @error('html_body') is-invalid @enderror"
                                          rows="4"
                                          placeholder="<p>Version HTML del contenido...</p>">{{ old('html_body', $reply->html_body) }}</textarea>
                                @error('html_body')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Clasificacion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Clasificacion</h6>
                        <p class="text-muted small mb-3">Categoria, etiquetas y categorias de ticket donde aplica</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Categoria</label>
                                <input type="text" name="category"
                                       class="form-control @error('category') is-invalid @enderror"
                                       value="{{ old('category', $reply->category) }}"
                                       placeholder="Ej: Saludos, Cierre, Escalado">
                                @error('category')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Tags</label>
                                <input type="text" id="tags_input"
                                       class="form-control @error('tags') is-invalid @enderror"
                                       value="{{ old('tags_string', is_array($reply->tags) ? implode(',', $reply->tags) : '') }}"
                                       placeholder="urgente, nuevo, email">
                                <div id="tags_hidden_container"></div>
                                <small class="form-text text-muted">Separados por coma: urgente,nuevo,email</small>
                                @error('tags')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                @php $selectedCategoryIds = old('ticket_categories', $reply->ticketCategories->pluck('id')->toArray()); @endphp
                                <label class="form-label">Categorias de ticket <small class="text-muted fw-normal">(opcional)</small></label>
                                <select name="ticket_categories[]" class="form-select @error('ticket_categories') is-invalid @enderror" multiple>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ in_array($cat->id, $selectedCategoryIds) ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ticket_categories')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuracion --}}
                        <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
                        <p class="text-muted small mb-3">Visibilidad y disponibilidad de la respuesta</p>
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label for="is_global" class="form-label">Visibilidad</label>
                                <select class="form-select @error('is_global') is-invalid @enderror" id="is_global" name="is_global">
                                    <option value="0" {{ old('is_global', $reply->is_global ? 1 : 0) == 0 ? 'selected' : '' }}>Personal — solo visible para mi</option>
                                    <option value="1" {{ old('is_global', $reply->is_global ? 1 : 0) == 1 ? 'selected' : '' }}>Global — visible para todo el equipo</option>
                                </select>
                                @error('is_global')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="is_active" class="form-label">Estado</label>
                                <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', $reply->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Activa — disponible para usar</option>
                                    <option value="0" {{ old('is_active', $reply->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactiva — oculta</option>
                                </select>
                                @error('is_active')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sobre las respuestas rapidas</h6>
                    <p class="card-text text-muted">
                        Las respuestas predefinidas permiten a los agentes insertar texto reutilizable en los tickets, ahorrando tiempo en respuestas frecuentes.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Buenas practicas</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa titulos descriptivos para encontrar la respuesta rapidamente</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Define atajos cortos y memorables como /greet o /close</li>
                        <li class="mb-2 text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Agrupa respuestas similares con la misma categoria</li>
                        <li class="text-muted small"><i class="fas fa-check-circle text-success me-2"></i> Usa variables como {agent_name} para personalizar el texto</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion del registro</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted small">
                            <span class="fw-semibold">Usada:</span> {{ number_format($reply->usage_count ?? 0) }} veces
                        </li>
                        <li class="mb-2 text-muted small">
                            <span class="fw-semibold">Creada:</span> {{ $reply->created_at->format('d/m/Y H:i') }}
                        </li>
                        <li class="text-muted small">
                            <span class="fw-semibold">Actualizada:</span> {{ $reply->updated_at->format('d/m/Y H:i') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#replyForm').on('submit', function () {
        $('#tags_hidden_container').empty();
        const raw = $('#tags_input').val().split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        raw.forEach(function (t) {
            $('#tags_hidden_container').append($('<input>').attr({ type: 'hidden', name: 'tags[]', value: t }));
        });
    });
});
</script>
@endpush
