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

                            <select name="inherit" class="form-select @error('inherit') is-invalid @enderror">
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
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>
                                    {{ isset($template) ? __('template::template.edit') : __('template::template.create') }}
                                </button>

                                <a href="{{ route('settings.templates.index') }}" class="btn btn-secondary w-100">
                                    {{ __('core::core.cancel') }}
                                </a>

                                @if(isset($template))
                                    <button type="button" class="btn btn-danger w-100"
                                            onclick="if(confirm('{{ __('template::template.confirm_delete') }}')) {
                                                    document.getElementById('delete-form').submit();
                                            }">
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

    @push('footer-scripts')
        <script>
            $(document).ready(function() {
                // Generar slug desde nombre
                $('#btn-generate-slug').click(function() {
                    const name = $('input[name="name"]').val();
                    if (!name) {
                        alert('Por favor ingresa un nombre primero');
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
            });
        </script>
    @endpush
@endsection
