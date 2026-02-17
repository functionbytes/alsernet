@extends('layouts.theme')

@section('title', 'Editar meta SEO')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form action="{{ route('setting.seo.metas.update', $meta) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-1 fw-bold">Editar meta SEO</h5>
                        <p class="mb-0 text-muted small">{{ class_basename($meta->seoable_type) }} #{{ $meta->seoable_id }} - {{ $meta->seoable?->title ?? 'N/A' }}</p>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-semibold mb-3 border-bottom pb-2">SEO basico</h6>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Titulo SEO</label>
                                <input type="text"
                                       class="form-control @error('title') is-invalid @enderror"
                                       name="title"
                                       value="{{ old('title', $meta->title) }}"
                                       maxlength="255"
                                       data-counter="255"
                                       placeholder="Titulo optimizado para buscadores">
                                <small class="text-muted">Aparece en los resultados de busqueda. Ideal: 50-60 caracteres</small>
                                @error('title')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Descripcion SEO</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description"
                                          rows="3"
                                          maxlength="500"
                                          data-counter="500"
                                          placeholder="Descripcion atractiva para motores de busqueda">{{ old('description', $meta->description) }}</textarea>
                                <small class="text-muted">Aparece debajo del titulo en Google. Ideal: 120-160 caracteres</small>
                                @error('description')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Palabras clave</label>
                                <input type="text"
                                       class="form-control @error('keywords') is-invalid @enderror"
                                       name="keywords"
                                       value="{{ old('keywords', $meta->keywords) }}"
                                       maxlength="500"
                                       placeholder="palabra1, palabra2, palabra3">
                                <small class="text-muted">Separadas por comas. Poco relevante para SEO moderno, pero util para organizacion</small>
                                @error('keywords')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Robots</label>
                                <select class="form-select @error('robots') is-invalid @enderror" name="robots">
                                    <option value="index,follow" {{ old('robots', $meta->robots) == 'index,follow' ? 'selected' : '' }}>index, follow</option>
                                    <option value="noindex,nofollow" {{ old('robots', $meta->robots) == 'noindex,nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                                    <option value="noindex,follow" {{ old('robots', $meta->robots) == 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                                    <option value="index,nofollow" {{ old('robots', $meta->robots) == 'index,nofollow' ? 'selected' : '' }}>index, nofollow</option>
                                </select>
                                <small class="text-muted">Controla indexacion y rastreo de buscadores</small>
                                @error('robots')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL canonica</label>
                                <input type="url"
                                       class="form-control @error('canonical_url') is-invalid @enderror"
                                       name="canonical_url"
                                       value="{{ old('canonical_url', $meta->canonical_url) }}"
                                       maxlength="500"
                                       placeholder="https://ejemplo.com/pagina-canonica">
                                <small class="text-muted">Evita contenido duplicado</small>
                                @error('canonical_url')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-semibold mb-3 border-bottom pb-2"><i class="fab fa-facebook me-2"></i>Open Graph</h6>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">OG Titulo</label>
                                <input type="text"
                                       class="form-control @error('og_title') is-invalid @enderror"
                                       name="og_title"
                                       value="{{ old('og_title', $meta->og_title) }}"
                                       maxlength="255"
                                       data-counter="255"
                                       placeholder="Titulo al compartir en redes sociales">
                                <small class="text-muted">Aparece al compartir en Facebook, LinkedIn, etc.</small>
                                @error('og_title')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">OG Descripcion</label>
                                <textarea class="form-control @error('og_description') is-invalid @enderror"
                                          name="og_description"
                                          rows="2"
                                          maxlength="500"
                                          data-counter="500"
                                          placeholder="Descripcion para redes sociales">{{ old('og_description', $meta->og_description) }}</textarea>
                                <small class="text-muted">Descripcion que aparece al compartir en redes sociales</small>
                                @error('og_description')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">OG Tipo</label>
                                <select class="form-select @error('og_type') is-invalid @enderror" name="og_type">
                                    <option value="website" {{ old('og_type', $meta->og_type) == 'website' ? 'selected' : '' }}>website</option>
                                    <option value="article" {{ old('og_type', $meta->og_type) == 'article' ? 'selected' : '' }}>article</option>
                                    <option value="product" {{ old('og_type', $meta->og_type) == 'product' ? 'selected' : '' }}>product</option>
                                    <option value="profile" {{ old('og_type', $meta->og_type) == 'profile' ? 'selected' : '' }}>profile</option>
                                </select>
                                <small class="text-muted">Tipo de contenido que se comparte</small>
                                @error('og_type')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">OG Imagen URL</label>
                                <input type="url"
                                       class="form-control @error('og_image') is-invalid @enderror"
                                       name="og_image"
                                       value="{{ old('og_image', $meta->og_image) }}"
                                       maxlength="500"
                                       data-preview="#og-image-preview"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                                <small class="text-muted">Recomendado: 1200x630px</small>
                                <div id="og-image-preview"></div>
                                @error('og_image')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-semibold mb-3 border-bottom pb-2"><i class="fab fa-twitter me-2"></i>Twitter Card</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Twitter Card tipo</label>
                                <select class="form-select @error('twitter_card') is-invalid @enderror" name="twitter_card">
                                    <option value="summary" {{ old('twitter_card', $meta->twitter_card) == 'summary' ? 'selected' : '' }}>summary</option>
                                    <option value="summary_large_image" {{ old('twitter_card', $meta->twitter_card) == 'summary_large_image' ? 'selected' : '' }}>summary_large_image</option>
                                    <option value="app" {{ old('twitter_card', $meta->twitter_card) == 'app' ? 'selected' : '' }}>app</option>
                                    <option value="player" {{ old('twitter_card', $meta->twitter_card) == 'player' ? 'selected' : '' }}>player</option>
                                </select>
                                <small class="text-muted">Formato de la tarjeta en Twitter</small>
                                @error('twitter_card')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Twitter Imagen URL</label>
                                <input type="url"
                                       class="form-control @error('twitter_image') is-invalid @enderror"
                                       name="twitter_image"
                                       value="{{ old('twitter_image', $meta->twitter_image) }}"
                                       maxlength="500"
                                       data-preview="#twitter-image-preview"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                                <small class="text-muted">Imagen para Twitter Card</small>
                                <div id="twitter-image-preview"></div>
                                @error('twitter_image')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Twitter Titulo</label>
                                <input type="text"
                                       class="form-control @error('twitter_title') is-invalid @enderror"
                                       name="twitter_title"
                                       value="{{ old('twitter_title', $meta->twitter_title) }}"
                                       maxlength="255"
                                       data-counter="255"
                                       placeholder="Titulo para Twitter Card">
                                <small class="text-muted">Titulo al compartir en Twitter/X</small>
                                @error('twitter_title')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Twitter Descripcion</label>
                                <textarea class="form-control @error('twitter_description') is-invalid @enderror"
                                          name="twitter_description"
                                          rows="2"
                                          maxlength="500"
                                          data-counter="500"
                                          placeholder="Descripcion para Twitter Card">{{ old('twitter_description', $meta->twitter_description) }}</textarea>
                                <small class="text-muted">Descripcion al compartir en Twitter/X</small>
                                @error('twitter_description')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="{{ route('setting.seo.metas.show', $meta) }}" class="btn btn-secondary w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold"><i class="fas fa-link me-1"></i> Modelo asociado</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <small class="text-muted">Tipo:</small><br>
                            <strong>{{ class_basename($meta->seoable_type) }}</strong>
                        </div>
                        <div class="col-12 mb-2">
                            <small class="text-muted">ID:</small><br>
                            <strong>#{{ $meta->seoable_id }}</strong>
                        </div>
                        <div class="col-12 mb-2">
                            <small class="text-muted">Nombre:</small><br>
                            <strong>{{ $meta->seoable?->title ?? $meta->seoable?->name ?? 'N/A' }}</strong>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <small class="text-muted">Creado:</small><br>
                            <strong>{{ $meta->created_at->format('d/m/Y H:i') }}</strong>
                            <small class="text-muted">({{ $meta->created_at->diffForHumans() }})</small>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Actualizado:</small><br>
                            <strong>{{ $meta->updated_at->format('d/m/Y H:i') }}</strong>
                            <small class="text-muted">({{ $meta->updated_at->diffForHumans() }})</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-1 fw-bold"><i class="fas fa-info-circle me-1"></i> Recomendaciones</h5>
                </div>
                <div class="card-body">
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Titulo: entre 50-60 caracteres para mejor visibilidad</li>
                        <li>Descripcion: entre 120-160 caracteres ideal</li>
                        <li>Usa palabras clave naturalmente</li>
                        <li>Incluye una imagen OG de al menos 1200x630px</li>
                        <li>El canonical URL evita contenido duplicado</li>
                        <li>Usa noindex solo en paginas que no quieras en Google</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3 bg-danger-subtle">
                    <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Zona de peligro</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Eliminar estos metadatos SEO no se puede deshacer. El modelo asociado no se vera afectado.</p>
                    <button type="button" class="btn btn-danger w-100 delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal">
                        <i class="fas fa-trash me-1"></i> Eliminar meta SEO
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Character counters
    $('[data-counter]').each(function() {
        var $input = $(this);
        var max = $input.data('counter');
        var $counter = $('<small class="text-muted float-end"></small>');
        $input.after($counter);

        function updateCounter() {
            var len = $input.val().length;
            $counter.text(len + '/' + max);
            $counter.toggleClass('text-danger', len > max);
        }

        $input.on('input', updateCounter);
        updateCounter();
    });

    // Image preview
    $('[data-preview]').on('input', function() {
        var url = $(this).val();
        var $preview = $($(this).data('preview'));
        if (url && url.match(/^https?:\/\/.+/)) {
            $preview.html('<img src="' + url + '" class="img-fluid rounded mt-2" style="max-height: 100px">');
        } else {
            $preview.empty();
        }
    }).trigger('input');

    // Delete modal
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text('Eliminar meta SEO');
        $('#delete-form').attr('action', '{{ route("setting.seo.metas.destroy", $meta) }}');
    });
});
</script>
@endpush
