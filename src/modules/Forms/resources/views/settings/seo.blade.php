@extends('core::layouts.admin')

@section('title', 'SEO · '.$form->name)

@section('content')
<div class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">SEO del formulario</h4>
            <small class="text-muted">{{ $form->name }}</small>
        </div>
        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-outline-secondary btn-sm">
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('settings.forms.settings.seo.update', $form) }}">
        @csrf
        @method('PATCH')

        <div class="card mb-3">
            <div class="card-header"><strong>Meta básicos</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="seo-title">Título SEO</label>
                        <input id="seo-title" type="text" name="title" maxlength="255"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $form->seoMeta?->title) }}"
                               placeholder="{{ $form->name }}">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="seo-description">Meta description</label>
                        <textarea id="seo-description" name="description" rows="2" maxlength="500"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Descripción corta que aparecerá en resultados de búsqueda">{{ old('description', $form->seoMeta?->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="seo-keywords">Keywords</label>
                        <input id="seo-keywords" type="text" name="keywords" maxlength="500"
                               class="form-control @error('keywords') is-invalid @enderror"
                               value="{{ old('keywords', $form->seoMeta?->keywords) }}">
                        @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="seo-robots">Robots</label>
                        <select id="seo-robots" name="robots" class="form-select">
                            <option value="index,follow" @selected(old('robots', $form->seoMeta?->robots) === 'index,follow')>index, follow</option>
                            <option value="noindex,follow" @selected(old('robots', $form->seoMeta?->robots) === 'noindex,follow')>noindex, follow</option>
                            <option value="index,nofollow" @selected(old('robots', $form->seoMeta?->robots) === 'index,nofollow')>index, nofollow</option>
                            <option value="noindex,nofollow" @selected(old('robots', $form->seoMeta?->robots) === 'noindex,nofollow')>noindex, nofollow</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="seo-canonical">URL canónica</label>
                        <input id="seo-canonical" type="url" name="canonical_url" maxlength="500"
                               class="form-control @error('canonical_url') is-invalid @enderror"
                               value="{{ old('canonical_url', $form->seoMeta?->canonical_url) }}"
                               placeholder="{{ route('forms.public.show', $form->slug) }}">
                        @error('canonical_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Open Graph</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="og-title">OG title</label>
                        <input id="og-title" type="text" name="og_title" maxlength="255" class="form-control"
                               value="{{ old('og_title', $form->seoMeta?->og_title) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="og-type">OG type</label>
                        <input id="og-type" type="text" name="og_type" maxlength="50" class="form-control"
                               value="{{ old('og_type', $form->seoMeta?->og_type ?? 'website') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="og-description">OG description</label>
                        <textarea id="og-description" name="og_description" rows="2" maxlength="500" class="form-control">{{ old('og_description', $form->seoMeta?->og_description) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="og-image">OG image (URL)</label>
                        <input id="og-image" type="url" name="og_image" maxlength="500" class="form-control"
                               value="{{ old('og_image', $form->seoMeta?->og_image) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Twitter card</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="tw-card">Card</label>
                        <select id="tw-card" name="twitter_card" class="form-select">
                            <option value="summary" @selected(old('twitter_card', $form->seoMeta?->twitter_card ?? 'summary') === 'summary')>summary</option>
                            <option value="summary_large_image" @selected(old('twitter_card', $form->seoMeta?->twitter_card) === 'summary_large_image')>summary_large_image</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tw-title">Twitter title</label>
                        <input id="tw-title" type="text" name="twitter_title" maxlength="255" class="form-control"
                               value="{{ old('twitter_title', $form->seoMeta?->twitter_title) }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="tw-description">Twitter description</label>
                        <textarea id="tw-description" name="twitter_description" rows="2" maxlength="500" class="form-control">{{ old('twitter_description', $form->seoMeta?->twitter_description) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="tw-image">Twitter image (URL)</label>
                        <input id="tw-image" type="url" name="twitter_image" maxlength="500" class="form-control"
                               value="{{ old('twitter_image', $form->seoMeta?->twitter_image) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar SEO</button>
        </div>
    </form>
</div>
@endsection
