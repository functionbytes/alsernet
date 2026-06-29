@extends('layouts.theme')

@section('title', 'Editar traducción — '.strtoupper($locale))

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Traducción '.strtoupper($locale)])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('manager.helpcenter.articles.translations.update', ['article' => $article, 'locale' => $locale]) }}"
                      method="POST">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale }}">

                    <div class="card-header p-4 border-bottom border-light">
                        <h5 class="mb-1 fw-bold">Traducción: {{ strtoupper($locale) }}</h5>
                        <p class="small mb-0 text-muted">Artículo base: {{ $article->title }}</p>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="title">Título</label>
                                <input type="text"
                                       id="title"
                                       name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $translation->title) }}"
                                       required
                                       maxlength="255">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="slug">Slug</label>
                                <input type="text"
                                       id="slug"
                                       name="slug"
                                       class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $translation->slug) }}"
                                       required
                                       maxlength="255">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Si lo dejas en blanco se genera automáticamente desde el título.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="is_published">Estado</label>
                                <select id="is_published" name="is_published" class="form-select">
                                    <option value="1" @selected(old('is_published', $translation->is_published))>Publicada</option>
                                    <option value="0" @selected(! old('is_published', $translation->is_published))>Borrador</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="meta_description">Meta description</label>
                                <textarea id="meta_description"
                                          name="meta_description"
                                          class="form-control @error('meta_description') is-invalid @enderror"
                                          rows="2"
                                          maxlength="500">{{ old('meta_description', $translation->meta_description) }}</textarea>
                                @error('meta_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="meta_keywords">Palabras clave</label>
                                <input type="text"
                                       id="meta_keywords"
                                       name="meta_keywords"
                                       class="form-control @error('meta_keywords') is-invalid @enderror"
                                       value="{{ old('meta_keywords', $translation->meta_keywords) }}"
                                       maxlength="500">
                                @error('meta_keywords')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Separadas por comas.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="body">Contenido</label>
                                <textarea id="body"
                                          name="body"
                                          class="form-control @error('body') is-invalid @enderror"
                                          rows="16">{{ old('body', $translation->body) }}</textarea>
                                @error('body')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('manager.helpcenter.articles.translations.index', $article) }}"
                           class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar traducción
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Referencia en español</h6>
                    <p class="text-muted small mb-2">
                        <strong>Título:</strong> {{ $article->title }}
                    </p>
                    @if($article->meta_description)
                        <p class="text-muted small mb-2">
                            <strong>Meta:</strong> {{ $article->meta_description }}
                        </p>
                    @endif
                    @if($article->body)
                        <p class="text-muted small mb-0">
                            <strong>Contenido base:</strong><br>
                            {{ Str::limit(strip_tags($article->body), 200) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    // Auto-generate slug from title if slug is empty
    $('#title').on('blur', function () {
        if ($('#slug').val() === '') {
            const slug = $(this).val()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-');
            $('#slug').val(slug);
        }
    });
});
</script>
@endpush
