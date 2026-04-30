@extends('layouts.theme')

@section('title', 'Nuevo tag')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo tag'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Formulario --}}
        <div class="col-lg-8">
            <form action="{{ route('blog.tags.store') }}" method="POST">
                @csrf

                <div class="card">

                    {{-- Tabs de idiomas --}}
                    <div class="card-header border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" id="tagTabs" role="tablist">
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
                                        </button>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    {{-- Contenido traducible por idioma --}}
                    <div class="card-body">
                        <div class="tab-content" id="tagTabsContent">

                            {{-- Español (idioma principal) --}}
                            <div class="tab-pane fade show active" id="lang-es" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name"
                                               class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name') }}"
                                               placeholder="ej: Laravel" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="slug" class="form-label fw-semibold">Slug (permalink)</label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted">{{ url('/blog/tag') }}/</span>
                                            <input type="text" id="slug" name="slug"
                                                   class="form-control @error('slug') is-invalid @enderror"
                                                   value="{{ old('slug') }}"
                                                   placeholder="se-generara-automaticamente">
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
                                                  placeholder="Descripción breve del tag (opcional)">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Otros idiomas --}}
                            @if(!empty($availableLocales))
                                @foreach($availableLocales as $locale => $label)
                                    <div class="tab-pane fade" id="lang-{{ $locale }}" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                                <input type="text"
                                                       name="translations[{{ $locale }}][name]"
                                                       class="form-control trans-name-input"
                                                       data-locale="{{ $locale }}"
                                                       value="{{ old('translations.'.$locale.'.name') }}"
                                                       maxlength="120"
                                                       placeholder="Nombre en {{ $label }}">
                                                @error('translations.'.$locale.'.name')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Slug (permalink)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text text-muted">{{ url('/blog/tag') }}/</span>
                                                    <input type="text"
                                                           name="translations[{{ $locale }}][slug]"
                                                           class="form-control"
                                                           data-locale="{{ $locale }}"
                                                           value="{{ old('translations.'.$locale.'.slug') }}"
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
                                                          placeholder="Descripción en {{ $label }}">{{ old('translations.'.$locale.'.description') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Configuración general --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Configuración general</h6>
                        <p class="text-muted mb-3">Opciones que aplican a todos los idiomas.</p>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="status" class="form-label fw-semibold">Estado</label>
                                <select id="status" name="status" class="form-select select2">
                                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publicado</option>
                                    <option value="draft"     {{ old('status') === 'draft'     ? 'selected' : '' }}>Borrador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar</button>
                        <a href="{{ route('blog.tags.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                    </div>

                </div>
            </form>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">¿Qué es un tag?</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Los tags son etiquetas que permiten clasificar los posts por temas específicos, facilitando la búsqueda y el filtrado de contenido.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Usa nombres cortos y descriptivos.</li>
                        <li class="mb-2">Evita tags duplicados o muy similares.</li>
                        <li>El slug se usa en la URL pública del tag.</li>
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
        return text.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    var slugTimer;
    var slugManual = false;

    $('#name').on('input', function () {
        if (slugManual) return;
        var name = $(this).val();
        if (!name) { $('#slug').val(''); return; }

        clearTimeout(slugTimer);
        slugTimer = setTimeout(function () {
            $.ajax({
                url: '{{ route("blog.tags.ajax.slug") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { name: name },
                success: function (res) { $('#slug').val(res.slug); },
                error: function () { $('#slug').val(toSlug(name)); }
            });
        }, 500);
    });

    $('#slug').on('input', function () { slugManual = true; });

    $('#btn-regenerate-slug').on('click', function () {
        var name = $('#name').val();
        if (!name) return;
        slugManual = false;

        $.ajax({
            url: '{{ route("blog.tags.ajax.slug") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { name: name },
            success: function (res) { $('#slug').val(res.slug); slugManual = true; },
            error: function () { $('#slug').val(toSlug(name)); slugManual = true; }
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
