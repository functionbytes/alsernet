@extends('layouts.theme')

@section('page_title', isset($template) ? __('template::template.edit') : __('template::template.create'))

@section('content')
    @include('core::components.card', ['title' => isset($template) ? __('template::template.edit') : __('template::template.create')])

    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <form action="{{ isset($template) ? route('settings.templates.update', $template) : route('settings.templates.store') }}"
                  method="POST" class="row g-4">
                @csrf
                @if(isset($template))
                    @method('PUT')
                @endif

                {{-- Columna Principal (8) --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            {{-- Nombre --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('template::template.template_name') }} *</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       placeholder="{{ __('template::template.name_placeholder') }}"
                                       value="{{ old('name', $template->name ?? '') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Slug --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('template::template.slug') }}</label>
                                <div class="input-group">
                                    <input type="text" name="slug"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           placeholder="{{ __('template::template.slug_placeholder') }}"
                                           value="{{ old('slug', $template->slug ?? '') }}">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-generate-slug">
                                        <i class="fas fa-sync"></i> Generar
                                    </button>
                                    @error('slug')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Descripción --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('template::template.description') }}</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="2" placeholder="{{ __('template::template.description_placeholder') }}">{{ old('description', $template->description ?? '') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Contenido (Blade/HTML) --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('template::template.content') }} *</label>
                                <textarea name="content" class="form-control @error('content') is-invalid @enderror"
                                          rows="12" id="template-content" required
                                          placeholder="Ingresa contenido Blade o HTML...">{{ old('content', $template->content ?? '') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Puedes usar variables Blade: @{{ $title }}, @{{ $description }}, @{{ $content }}, etc.
                                </small>
                            </div>

                            {{-- Panel de Shortcodes --}}
                            @php $availableShortcodes = $shortcodes ?? collect(); @endphp
                            @if($availableShortcodes->isNotEmpty())
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold text-secondary" style="font-size:0.85rem;">
                                            <i class="fas fa-code me-1"></i>Shortcodes disponibles
                                        </span>
                                        <small class="text-muted">Clic para insertar en el contenido</small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($availableShortcodes as $sc)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary btn-insert-shortcode"
                                                    data-shortcode="{{ $sc->shortcode_template ?: '[' . $sc->key . ']' }}"
                                                    title="{{ $sc->description }}">
                                                @if($sc->icon)
                                                    <i class="{{ $sc->icon }} me-1"></i>
                                                @endif
                                                {{ $sc->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Columna Lateral (4) --}}
                <div class="col-lg-4">
                    {{-- Estado --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('template::template.status') }}</h5>

                            <div class="form-check mb-2">
                                <input type="radio" name="status" value="active" id="status-active"
                                       class="form-check-input"
                                       {{ old('status', $template->status ?? 'inactive') === 'active' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status-active">
                                    <i class="fas fa-check-circle text-success me-2"></i>{{ __('template::template.active') }}
                                </label>
                            </div>

                            <div class="form-check">
                                <input type="radio" name="status" value="inactive" id="status-inactive"
                                       class="form-check-input"
                                       {{ old('status', $template->status ?? 'inactive') === 'inactive' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status-inactive">
                                    <i class="fas fa-times-circle text-danger me-2"></i>{{ __('template::template.inactive') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Herencia --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('template::template.inherit') }}</h5>

                            <select name="inherit" class="select2 form-select @error('inherit') is-invalid @enderror">
                                <option value="">Sin herencia</option>
                                @foreach($templates as $slug => $name)
                                    <option value="{{ $slug }}"
                                        {{ old('inherit', $template->inherit ?? '') === $slug ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inherit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            @if(isset($template) && $template->children()->exists())
                                <div class="alert alert-info mt-2 mb-0">
                                    <small>
                                        <i class="fas fa-sitemap me-1"></i>
                                        {{ $template->children()->count() }} plantilla(s) heredan de esta
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Autor y Versión --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Metadatos</h5>

                            <div class="mb-3">
                                <label class="form-label">{{ __('template::template.author') }}</label>
                                <input type="text" name="author" class="form-control @error('author') is-invalid @enderror"
                                       placeholder="Nombre del autor"
                                       value="{{ old('author', $template->author ?? '') }}">
                                @error('author')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(isset($template))
                                <div class="mb-0">
                                    <label class="form-label">{{ __('template::template.version') }}</label>
                                    <input type="text" class="form-control" disabled
                                           value="{{ $template->version }}">
                                    <small class="text-muted">
                                        Versión actual: <strong>{{ $template->getCurrentVersionNumber() }}</strong>
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Versiones (si existe) --}}
                    @if(isset($template) && $template->hasVersions())
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>Historial
                                </h5>

                                <p class="text-muted mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Total de versiones: <strong>{{ $template->getTotalVersions() }}</strong>
                                </p>

                                <a href="{{ route('settings.templates.versions.index', $template) }}"
                                   class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-code-branch me-1"></i>Ver historial
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Botones de Acción --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-outline-info w-100" id="btn-preview-template">
                                    <i class="fas fa-eye me-2"></i>Vista previa
                                </button>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>
                                    {{ isset($template) ? __('template::template.edit') : __('template::template.create') }}
                                </button>

                                <a href="{{ route('settings.templates.index') }}" class="btn btn-secondary w-100">
                                    {{ __('core::core.cancel') }}
                                </a>

                                @if(isset($template))
                                    <button type="button" class="btn btn-danger w-100"
                                            data-bs-toggle="modal" data-bs-target="#modal-confirm-delete">
                                        <i class="fas fa-trash me-2"></i>{{ __('template::template.delete') }}
                                    </button>

                                    <form id="delete-form"
                                          action="{{ route('settings.templates.destroy', $template) }}"
                                          method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Vista Previa --}}
    <div class="modal fade" id="modal-preview" tabindex="-1" aria-labelledby="modal-preview-label">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-preview-label">
                        <i class="fas fa-eye me-2"></i>Vista previa del template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="min-height: 70vh;">
                    <div id="preview-loading" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted">Generando vista previa...</p>
                    </div>
                    <div id="preview-error" class="alert alert-danger m-3 d-none"></div>
                    <iframe id="preview-frame" style="width:100%;height:70vh;border:0;" class="d-none" sandbox="allow-same-origin allow-scripts"></iframe>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <i class="fas fa-info-circle me-1"></i>Vista previa generada con datos de ejemplo
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @if(isset($template))
    {{-- Modal confirmación eliminar --}}
    <div class="modal fade" id="modal-confirm-delete" tabindex="-1" aria-labelledby="modal-confirm-delete-label">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-confirm-delete-label">
                        {{ __('template::template.delete') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{ __('template::template.confirm_delete') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('core::core.cancel') }}
                    </button>
                    <button type="button" class="btn btn-danger"
                            onclick="document.getElementById('delete-form').submit()">
                        <i class="fas fa-trash me-1"></i>{{ __('template::template.delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('footer-scripts')
        <script>
            $(document).ready(function() {
                // Generar slug desde nombre
                $('#btn-generate-slug').click(function() {
                    const name = $('input[name="name"]').val();
                    if (!name) {
                        toastr.warning('Por favor ingresa un nombre primero');
                        return;
                    }

                    const slug = name
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');

                    $('input[name="slug"]').val(slug);
                });

                // Auto-generar slug cuando cambias el nombre
                $('input[name="name"]').on('change', function() {
                    if (!$('input[name="slug"]').val()) {
                        $('#btn-generate-slug').click();
                    }
                });

                // Insertar shortcode en el textarea al cursor
                $(document).on('click', '.btn-insert-shortcode', function() {
                    const shortcode = $(this).data('shortcode');
                    const textarea = document.getElementById('template-content');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const value = textarea.value;

                    textarea.value = value.substring(0, start) + shortcode + value.substring(end);
                    textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
                    textarea.focus();
                });

                // Vista previa del template
                $('#btn-preview-template').click(function() {
                    const content = $('#template-content').val().trim();

                    if (!content) {
                        toastr.warning('El contenido está vacío. Ingresa algo para previsualizar.');
                        return;
                    }

                    const $modal = new bootstrap.Modal(document.getElementById('modal-preview'));
                    $modal.show();

                    $('#preview-loading').removeClass('d-none');
                    $('#preview-error').addClass('d-none');
                    $('#preview-frame').addClass('d-none');

                    $.ajax({
                        url: '{{ route("settings.templates.preview") }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json',
                        },
                        data: { content: content },
                        success: function(response) {
                            const iframe = document.getElementById('preview-frame');
                            iframe.srcdoc = response.html;
                            $('#preview-loading').addClass('d-none');
                            $('#preview-frame').removeClass('d-none');
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.error || 'Error al generar la vista previa.';
                            $('#preview-loading').addClass('d-none');
                            $('#preview-error').text(msg).removeClass('d-none');
                        },
                    });
                });

                // Limpiar preview al cerrar el modal
                document.getElementById('modal-preview').addEventListener('hidden.bs.modal', function() {
                    $('#preview-frame').attr('srcdoc', '').addClass('d-none');
                    $('#preview-loading').removeClass('d-none');
                    $('#preview-error').addClass('d-none');
                });
            });
        </script>
    @endpush
@endsection
