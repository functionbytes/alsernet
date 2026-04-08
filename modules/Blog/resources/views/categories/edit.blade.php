@extends('layouts.theme')

@section('title', 'Editar categoría')

@section('content')
    @include('core::components.card', ['title' => 'Editar categoría'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Formulario --}}
        <div class="col-lg-8">
            <form action="{{ route('blog.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">

                    {{-- Tabs de idiomas --}}
                    <div class="card-header border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" id="categoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab"
                                        data-bs-target="#lang-es" type="button" role="tab">
                                    Español
                                </button>
                            </li>
                            @if(!empty($availableLocales))
                                @foreach($availableLocales as $locale => $label)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab"
                                                data-bs-target="#lang-{{ $locale }}" type="button" role="tab">
                                            {{ $label }}
                                            @if($category->hasTranslation($locale))
                                                <span class="badge bg-success-subtle text-success ms-1" style="font-size:.65rem;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    {{-- Contenido traducible por idioma --}}
                    <div class="card-body">
                        <div class="tab-content" id="categoryTabsContent">

                            {{-- Español (idioma principal) --}}
                            <div class="tab-pane fade show active" id="lang-es" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', $category->name) }}"
                                               required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="slug" class="form-label fw-semibold">Slug (permalink)</label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted">{{ url('/blog/category') }}/</span>
                                            <input type="text" id="slug" name="slug"
                                                   class="form-control @error('slug') is-invalid @enderror"
                                                   value="{{ old('slug', $category->slug) }}">
                                            <button type="button" class="btn btn-outline-secondary" id="btn-regenerate-slug" title="Regenerar desde nombre">
                                                <i class="fas fa-wand-magic-sparkles"></i>
                                            </button>
                                        </div>
                                        @error('slug')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label fw-semibold">Descripción</label>
                                        <textarea id="description" name="description" rows="3"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  placeholder="Descripción breve de la categoría (opcional)">{{ old('description', $category->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Otros idiomas --}}
                            @if(!empty($availableLocales))
                                @foreach($availableLocales as $locale => $label)
                                    @php $trans = $category->translation($locale); @endphp
                                    <div class="tab-pane fade" id="lang-{{ $locale }}" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                                <input type="text"
                                                       name="translations[{{ $locale }}][name]"
                                                       class="form-control trans-name-input"
                                                       data-locale="{{ $locale }}"
                                                       value="{{ old('translations.'.$locale.'.name', $trans?->name) }}"
                                                       maxlength="120"
                                                       placeholder="Nombre en {{ $label }}">
                                                @error('translations.'.$locale.'.name')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Slug (permalink)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text text-muted">{{ url('/blog/category') }}/</span>
                                                    <input type="text"
                                                           name="translations[{{ $locale }}][slug]"
                                                           class="form-control trans-slug-input"
                                                           data-locale="{{ $locale }}"
                                                           value="{{ old('translations.'.$locale.'.slug', $trans?->slug) }}"
                                                           maxlength="255"
                                                           placeholder="slug-en-{{ $locale }}">
                                                    <button type="button" class="btn btn-outline-secondary btn-regenerate-trans-slug" data-locale="{{ $locale }}" title="Regenerar desde nombre">
                                                        <i class="fas fa-wand-magic-sparkles"></i>
                                                    </button>
                                                </div>
                                                @error('translations.'.$locale.'.slug')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Descripción</label>
                                                <textarea name="translations[{{ $locale }}][description]"
                                                          class="form-control"
                                                          rows="3"
                                                          maxlength="400"
                                                          placeholder="Descripción en {{ $label }}">{{ old('translations.'.$locale.'.description', $trans?->description) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Configuración general (siempre visible) --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Configuración general</h6>
                        <p class="text-muted mb-3">Opciones que aplican a todos los idiomas.</p>

                        <div class="row g-3">

                            {{-- Categoría padre --}}
                            <div class="col-md-6">
                                <label for="parent_id" class="form-label fw-semibold">Categoría padre</label>
                                <select id="parent_id" name="parent_id" class="form-select select2">
                                    <option value="0" {{ old('parent_id', $category->parent_id) == 0 ? 'selected' : '' }}>
                                        Ninguna (categoría raíz)
                                    </option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}"
                                                {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">Estado</label>
                                <select id="status" name="status" class="form-select select2">
                                    <option value="published" {{ old('status', $category->status?->value ?? $category->status) === 'published' ? 'selected' : '' }}>Publicada</option>
                                    <option value="draft"     {{ old('status', $category->status?->value ?? $category->status) === 'draft'     ? 'selected' : '' }}>Borrador</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Icono --}}
                            <div class="col-md-6">
                                <label for="icon" class="form-label fw-semibold">Icono</label>
                                <input type="text" id="icon" name="icon"
                                       class="form-control @error('icon') is-invalid @enderror"
                                       value="{{ old('icon', $category->icon) }}"
                                       placeholder="ej: fas fa-folder">
                                <small class="text-muted d-block mt-1">Clase de Font Awesome (opcional)</small>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Orden --}}
                            <div class="col-md-6">
                                <label for="order" class="form-label fw-semibold">Orden</label>
                                <input type="number" id="order" name="order"
                                       class="form-control @error('order') is-invalid @enderror"
                                       value="{{ old('order', $category->order) }}" min="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Opciones --}}
                            @php
                                $oldFlags   = old('flags');
                                $isFeatured = $oldFlags !== null ? in_array('is_featured', (array) $oldFlags) : $category->is_featured;
                                $isDefault  = $oldFlags !== null ? in_array('is_default', (array) $oldFlags)  : $category->is_default;
                            @endphp
                            <div class="col-12">
                                <label for="flags" class="form-label fw-semibold">Opciones</label>
                                <select id="flags" name="flags[]" class="form-select select2" multiple data-placeholder="Ninguna">
                                    <option value="is_featured" {{ $isFeatured ? 'selected' : '' }}>Destacada</option>
                                    <option value="is_default"  {{ $isDefault  ? 'selected' : '' }}>Predeterminada</option>
                                </select>
                                <small class="text-muted d-block mt-1">Seleccione una o ambas opciones si aplica.</small>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('blog.categories.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                    </div>

                </div>
            </form>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Información</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Las categorías agrupan los posts por temática, facilitando la navegación y organización del contenido del blog.</p>
                </div>
            </div>

            @if($category->posts_count ?? $category->posts()->count())
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Posts asociados</h6>
                        <p class="text-muted mb-0">
                            <span class="fw-bold">{{ $category->posts_count ?? $category->posts()->count() }}</span> posts en esta categoría.
                        </p>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Precauciones al editar</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Cambiar el slug afecta las URLs existentes.</li>
                        <li class="mb-2">No asignes una categoría como su propio padre.</li>
                        <li>Los posts asociados conservarán esta categoría.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function toSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    $('#btn-regenerate-slug').on('click', function () {
        var name = $('#name').val();
        if (!name) return;

        $.ajax({
            url: '{{ route("blog.categories.ajax.slug") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { name: name, ignoreId: {{ $category->id }} },
            success: function (res) { $('#slug').val(res.slug); },
            error: function () { $('#slug').val(toSlug(name)); }
        });
    });

    // Auto-generate slug per locale from translated name
    $(document).on('input', '.trans-name-input', function () {
        var locale    = $(this).data('locale');
        var slugInput = $('[name="translations[' + locale + '][slug]"]');
        if (slugInput.val() === '') {
            slugInput.val(toSlug($(this).val()));
        }
    });

    // Regenerate translation slug button
    $(document).on('click', '.btn-regenerate-trans-slug', function () {
        var locale    = $(this).data('locale');
        var nameInput = $('[name="translations[' + locale + '][name]"]');
        var slugInput = $('[name="translations[' + locale + '][slug]"]');
        var name = nameInput.val();
        if (!name) return;
        slugInput.val(toSlug(name));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
