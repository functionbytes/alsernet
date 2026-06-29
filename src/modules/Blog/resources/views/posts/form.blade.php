@php
    $isEdit  = isset($post) && $post?->id;
    $formUrl = $isEdit
        ? route('blog.posts.update', $post->id)
        : route('blog.posts.store');
    $selectedCategoryIds = $isEdit ? $post->categories->pluck('id')->toArray() : [];
    $selectedTagIds      = $isEdit ? $post->tags->pluck('id')->toArray() : [];
    $defaultLocale       = app()->getLocale();
@endphp

<form action="{{ $formUrl }}" method="POST" id="post-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @include('core::components.alerts')

    <div class="row g-3">

        {{-- Columna principal --}}
        <div class="col-md-8">

            {{-- Tabs por idioma --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="langTabs" role="tablist">
                        @foreach($availableLocales as $locale => $label)
                        @php
                            $isDefLoc   = $locale === $defaultLocale;
                            $transTab   = (!$isDefLoc && $isEdit) ? $post->translation($locale) : null;
                            $hasTitle   = $isDefLoc ? !empty($post?->title) : !empty($transTab?->title);
                            $hasContent = $isDefLoc ? !empty($post?->content) : !empty($transTab?->content);
                            $hasSeo     = !$isDefLoc ? (!empty($transTab?->seo_title) && !empty($transTab?->seo_description)) : true;
                            $transStatus = $transTab?->status;

                            if ($isDefLoc) {
                                $badge = '';
                            } elseif (!$hasTitle && !$hasContent) {
                                $badgeIcon = 'fas fa-times';
                                $badgeColor = 'danger';
                                $badgeTooltip = 'Sin traducción';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            } elseif ($transStatus === \Modules\Blog\Enums\TranslationStatus::Stale) {
                                $badgeIcon = 'fas fa-exclamation-triangle';
                                $badgeColor = 'warning';
                                $badgeTooltip = 'Traducción obsoleta — el contenido fuente ha cambiado';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            } elseif ($transStatus === \Modules\Blog\Enums\TranslationStatus::AutoTranslated) {
                                $isComplete = $hasTitle && $hasContent && $hasSeo;
                                $badgeIcon = $isComplete ? 'fas fa-robot' : 'fas fa-robot';
                                $badgeColor = $isComplete ? 'info' : 'info';
                                $badgeTooltip = $isComplete ? 'Auto-traducido (completo)' : 'Auto-traducido (parcial — faltan campos SEO)';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            } elseif ($transStatus === \Modules\Blog\Enums\TranslationStatus::Reviewed) {
                                $badgeIcon = 'fas fa-check-circle';
                                $badgeColor = 'success';
                                $badgeTooltip = 'Revisado y aprobado';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            } elseif ($hasTitle && $hasContent && $hasSeo) {
                                $badgeIcon = 'fas fa-check';
                                $badgeColor = 'success';
                                $badgeTooltip = 'Traducción completa';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            } else {
                                $badgeIcon = 'fas fa-adjust';
                                $badgeColor = 'warning';
                                $badgeTooltip = 'Traducción parcial';
                                $badge = '<span class="badge bg-'.$badgeColor.' ms-1" style="font-size:.55rem" title="'.$badgeTooltip.'" data-badge-locale="'.$locale.'"><i class="'.$badgeIcon.'"></i></span>';
                            }
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                    id="tab-btn-{{ $locale }}"
                                    data-bs-toggle="tab"
                                    data-bs-target="#pane-{{ $locale }}"
                                    type="button" role="tab">
                                {{ $label }} {!! $badge !!}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-content" id="langTabsContent">
                    @foreach($availableLocales as $locale => $label)
                    @php
                        $isDefLoc  = $locale === $defaultLocale;
                        $trans     = (!$isDefLoc && $isEdit) ? $post->translation($locale) : null;

                        // Nombres de campos
                        $fTitle    = $isDefLoc ? 'title'             : "translations[{$locale}][title]";
                        $fSlug     = $isDefLoc ? 'slug'              : "translations[{$locale}][slug]";
                        $fDesc     = $isDefLoc ? 'description'       : "translations[{$locale}][description]";
                        $fContent  = $isDefLoc ? 'content'           : "translations[{$locale}][content]";
                        $fSeoTitle = $isDefLoc ? 'seo_title'         : "translations[{$locale}][seo_title]";
                        $fSeoDesc  = $isDefLoc ? 'seo_description'   : "translations[{$locale}][seo_description]";
                        $fSeoKws   = $isDefLoc ? 'seo_keywords'      : "translations[{$locale}][seo_keywords]";

                        // Valores actuales
                        $eTitle    = $isDefLoc ? old('title',            $post?->title)           : old("translations.{$locale}.title",           $trans?->title);
                        $eSlug     = $isDefLoc ? old('slug',             $post?->slug)            : old("translations.{$locale}.slug",            $trans?->slug);
                        $eDesc     = $isDefLoc ? old('description',      $post?->description)     : old("translations.{$locale}.description",     $trans?->description);
                        $eContent  = $isDefLoc ? old('content',          $post?->content)         : old("translations.{$locale}.content",         $trans?->content);
                        $eSeoTitle = $isDefLoc ? old('seo_title',        $post?->seo_title)       : old("translations.{$locale}.seo_title",       $trans?->seo_title);
                        $eSeoDesc  = $isDefLoc ? old('seo_description',  $post?->seo_description) : old("translations.{$locale}.seo_description", $trans?->seo_description);
                        $eSeoKws   = $isDefLoc ? old('seo_keywords',     $post?->seo_keywords)    : old("translations.{$locale}.seo_keywords",    $trans?->seo_keywords);

                        // Claves de error
                        $errTitle   = $isDefLoc ? 'title'                              : "translations.{$locale}.title";
                        $errSlug    = $isDefLoc ? 'slug'                               : "translations.{$locale}.slug";
                        $errDesc    = $isDefLoc ? 'description'                        : "translations.{$locale}.description";
                        $errContent = $isDefLoc ? 'content'                            : "translations.{$locale}.content";
                    @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="pane-{{ $locale }}" role="tabpanel">

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title-{{ $locale }}" class="form-label fw-semibold">
                                        Título @if($isDefLoc)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="text"
                                           id="title-{{ $locale }}"
                                           name="{{ $fTitle }}"
                                           class="form-control @error($errTitle) is-invalid @enderror"
                                           value="{{ $eTitle }}"
                                           @if($isDefLoc) required @endif
                                           maxlength="255"
                                           placeholder="Título del post en {{ $label }}">
                                    @error($errTitle)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="slug-{{ $locale }}" class="form-label fw-semibold">Slug (permalink)</label>
                                    <div class="input-group">
                                        <span class="input-group-text text-muted">{{ url('/blog') }}/</span>
                                        <input type="text"
                                               id="slug-{{ $locale }}"
                                               name="{{ $fSlug }}"
                                               class="form-control @error($errSlug) is-invalid @enderror"
                                               value="{{ $eSlug }}"
                                               placeholder="se-generara-automaticamente">
                                        <button type="button" class="btn btn-outline-secondary {{ $isDefLoc ? '' : 'btn-regenerate-trans-slug' }}"
                                                {{ $isDefLoc ? 'id=btn-regenerate-slug' : 'data-locale='.$locale }}
                                                title="Regenerar slug">
                                            <i class="fas fa-wand-magic-sparkles"></i>
                                        </button>
                                    </div>
                                    @error($errSlug)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="description-{{ $locale }}" class="form-label fw-semibold">Descripción</label>
                                    <textarea id="description-{{ $locale }}"
                                              name="{{ $fDesc }}"
                                              rows="3"
                                              class="form-control @error($errDesc) is-invalid @enderror"
                                              maxlength="500"
                                              placeholder="Resumen breve del post (máx. 500 caracteres)">{{ $eDesc }}</textarea>
                                    @if($isDefLoc)
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Se usa como extracto y descripción SEO.</small>
                                        <small class="text-muted"><span id="desc-count">0</span>/500</small>
                                    </div>
                                    @endif
                                    @error($errDesc)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Contenido</label>
                                    <p class="text-muted small mb-2">Cuerpo principal del post. Soporta texto enriquecido, imágenes, shortcodes y bloques de interfaz de usuario.</p>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="toggleEditor('{{ $locale }}')">
                                            Mostrar/Ocultar editor
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm add-media-btn"
                                                data-locale="{{ $locale }}">
                                            <i class="fas fa-image me-1"></i> Agregar
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm ui-blocks-btn"
                                                data-locale="{{ $locale }}"
                                                data-bs-toggle="modal" data-bs-target="#uiBlocksModal">
                                            <i class="fas fa-th-large me-1"></i> Bloques de interfaz de usuario
                                        </button>
                                    </div>
                                    <div id="editorWrapper-{{ $locale }}">
                                        <textarea id="content-{{ $locale }}"
                                                  name="{{ $fContent }}"
                                                  class="@error($errContent) is-invalid @enderror">{{ $eContent }}</textarea>
                                    </div>
                                    @error($errContent)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- SEO y metadatos --}}
                                <div class="col-12">
                                    <hr class="my-1">
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label class="form-label fw-semibold mb-0">SEO y metadatos</label>
                                            <p class="text-muted small mb-0">Optimiza cómo aparece este post en buscadores y redes sociales.</p>
                                        </div>
                                        <a href="#" class="small fw-semibold seo-toggle flex-shrink-0 ms-3" data-locale="{{ $locale }}">
                                            <span class="seo-toggle-text" data-locale="{{ $locale }}">Mostrar</span>
                                        </a>
                                    </div>
                                </div>

                                {{-- Vista previa Google --}}
                                <div class="col-12" id="seoPreview-{{ $locale }}">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="text-truncate text-danger h5 text-bold" >
                                            {{ $eSeoTitle ?: ($eTitle ?: 'Título del post') }}
                                        </div>
                                        <div style="color:#4d5156;font-size:14px;" class="mt-1">
                                            <span style="color:#202124;">{{ parse_url(url('/blog'), PHP_URL_HOST) }}</span>
                                            <span class="text-muted"> › blog › </span>
                                            <span style="color:#202124;">{{ $eSlug ?: 'slug-del-post' }}</span>
                                        </div>
                                        <div style="color:#4d5156;font-size:14px;line-height:1.5;" class="mt-1">
                                            {{ Str::limit($eSeoDesc ?: ($eDesc ?: 'Descripción que aparecerá en los resultados de búsqueda.'), 160) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Campos SEO --}}
                                <div class="col-12 seo-edit-section" id="seoEditSection-{{ $locale }}" style="display:none;">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Título SEO</label>
                                            <input type="text"
                                                   id="seo_title-{{ $locale }}"
                                                   name="{{ $fSeoTitle }}"
                                                   class="form-control"
                                                   value="{{ $eSeoTitle }}"
                                                   maxlength="255"
                                                   placeholder="Título para buscadores">
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-muted">Recomendado: 50-60 caracteres</small>
                                                <small class="text-muted seo-title-count-{{ $locale }}">{{ mb_strlen($eSeoTitle ?? '') }}/60</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Descripción SEO</label>
                                            <textarea id="seo_description-{{ $locale }}"
                                                      name="{{ $fSeoDesc }}"
                                                      class="form-control"
                                                      rows="3"
                                                      maxlength="500"
                                                      placeholder="Descripción para buscadores">{{ $eSeoDesc }}</textarea>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-muted">Recomendado: 150-160 caracteres</small>
                                                <small class="text-muted seo-desc-count-{{ $locale }}">{{ mb_strlen($eSeoDesc ?? '') }}/160</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Palabras clave</label>
                                            <input type="text"
                                                   id="seo_keywords-{{ $locale }}"
                                                   name="{{ $fSeoKws }}"
                                                   class="form-control"
                                                   value="{{ $eSeoKws }}"
                                                   maxlength="500"
                                                   placeholder="palabra1, palabra2, palabra3">
                                            <small class="text-muted">Separadas por comas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>

                <hr class="my-0">

                {{-- Configuración general (siempre visible) --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Configuración general</h6>
                    <p class="text-muted mb-3">Opciones que aplican a todos los idiomas.</p>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                            <select id="status" name="status"
                                    class="form-select select2 @error('status') is-invalid @enderror" required>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}"
                                            {{ old('status', $post?->status?->value ?? 'draft') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="published-at-wrapper"
                             class="col-md-12 {{ old('status', $post?->status?->value ?? 'draft') !== 'published' ? 'd-none' : '' }}">
                            <label for="published_at" class="form-label fw-semibold">Fecha de publicación</label>
                            <input type="datetime-local" id="published_at" name="published_at"
                                   class="form-control @error('published_at') is-invalid @enderror"
                                   value="{{ old('published_at', $post?->published_at?->format('Y-m-d\TH:i')) }}">
                            <small class="text-muted d-block mt-1">Dejar vacío para usar la fecha actual</small>
                            @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Publicación programada --}}
                        @php
                            $hasPublishAt    = (bool) $post?->publish_at;
                            $isDraftOrPending = in_array(old('status', $post?->status?->value ?? 'draft'), ['draft', 'pending']);
                        @endphp
                        <div id="schedule-toggle-wrapper" class="col-12 {{ !$isDraftOrPending ? 'd-none' : '' }}">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="enable-scheduling"
                                       {{ $hasPublishAt ? 'checked' : '' }}>
                                <label class="form-check-label small text-muted" for="enable-scheduling">
                                    <i class="fas fa-clock me-1"></i> Programar publicación automática
                                </label>
                            </div>
                        </div>

                        <div id="scheduled-wrapper" class="col-md-12 {{ !$hasPublishAt ? 'd-none' : '' }}">
                            <label for="publish_at" class="form-label fw-semibold">Fecha de publicación programada</label>
                            <input type="datetime-local" id="publish_at" name="publish_at"
                                   class="form-control @error('publish_at') is-invalid @enderror"
                                   value="{{ old('publish_at', $post?->publish_at?->format('Y-m-d\TH:i')) }}">
                            <small class="text-muted d-block mt-1">El post se publicará automáticamente en la fecha indicada.</small>
                            @error('publish_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Imagen destacada --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Imagen destacada</label>

                            {{-- Preview con botón remove --}}
                            <div class="position-relative mb-2" id="image-preview-wrapper"
                                 style="{{ ($isEdit && $post->image) || old('image') ? '' : 'display:none' }}">
                                <img id="image-preview"
                                     src="{{ old('image', $post?->image) }}"
                                     alt="Preview"
                                     class="img-fluid rounded border"
                                     style="max-height:200px; object-fit:cover; width:100%;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle"
                                        id="image-remove-btn" title="Eliminar imagen"
                                        style="width:28px; height:28px; padding:0; line-height:28px;">
                                    <i class="fas fa-times" style="font-size:12px;"></i>
                                </button>
                            </div>

                            {{-- Placeholder cuando no hay imagen --}}
                            <div class="d-flex flex-column align-items-center justify-content-center rounded-3 bg-light mb-2"
                                 id="image-placeholder"
                                 style="min-height:140px; border:2px dashed #dee2e6; cursor:pointer; {{ ($isEdit && $post->image) || old('image') ? 'display:none' : '' }}"
                                 onclick="document.getElementById('image-select-btn').click()">
                                <i class="fas fa-image fa-3x mb-2" style="color:#adb5bd;"></i>
                                <p class="mb-0 small" style="color:#6c757d;">Haz clic para seleccionar una imagen</p>
                            </div>

                            <div class="input-group">
                                <input type="url" id="image_url" name="image"
                                       class="form-control @error('image') is-invalid @enderror"
                                       value="{{ old('image', $post?->image) }}"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                                <button type="button" class="btn btn-outline-secondary" id="image-upload-btn" title="Subir imagen">
                                    <i class="fas fa-upload"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="image-select-btn" title="Seleccionar de galería">
                                    <i class="fas fa-images"></i>
                                </button>
                            </div>
                            <input type="file" id="image-file-input" accept="image/*" class="d-none">
                            @error('image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Categorías --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Categorías</label>
                            <select name="categories[]" class="form-select select2" multiple data-placeholder="Seleccionar categorías...">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                            {{ in_array($cat->id, old('categories', $selectedCategoryIds)) ? 'selected' : '' }}>
                                        {{ $cat->parent_id > 0 ? '— ' : '' }}{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Etiquetas --}}
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Etiquetas</label>
                            <select name="tags[]" id="tags-select" class="form-select select2" multiple data-placeholder="Seleccionar etiquetas...">
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}"
                                            {{ in_array($tag->id, old('tags', $selectedTagIds)) ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Formato --}}
                        <div class="col-md-12">
                            <label for="format_type" class="form-label fw-semibold">Formato</label>
                            <select id="format_type" name="format_type"
                                    class="form-select select2 @error('format_type') is-invalid @enderror">
                                <option value="standard" {{ old('format_type', $post?->format_type ?? 'standard') === 'standard' ? 'selected' : '' }}>Estándar</option>
                                <option value="video"    {{ old('format_type', $post?->format_type) === 'video'    ? 'selected' : '' }}>Video</option>
                                <option value="gallery"  {{ old('format_type', $post?->format_type) === 'gallery'  ? 'selected' : '' }}>Galería</option>
                            </select>
                            @error('format_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Opciones --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Opciones</label>
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input"
                                       id="is_featured" name="is_featured" value="1"
                                       {{ old('is_featured', $post?->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">Post destacado</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input"
                                       id="send_newsletter" name="send_newsletter" value="1"
                                       {{ old('send_newsletter', $post?->send_newsletter ?? false) ? 'checked' : '' }}
                                       {{ ($post?->newsletter_sent_at) ? 'disabled' : '' }}>
                                <label class="form-check-label" for="send_newsletter">Enviar a newsletter al publicar</label>
                                @if($post?->newsletter_sent_at)
                                    <small class="text-muted d-block">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        Enviado el {{ $post->newsletter_sent_at->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-grid gap-2">
                        @can('publish', $post ?? new \Modules\Blog\Models\BlogPost)
                            <button type="submit" name="status_override" value="published" class="btn btn-primary">
                                {{ $isEdit ? 'Actualizar y publicar' : 'Publicar' }}
                            </button>
                        @endcan
                        <button type="submit" class="btn btn-outline-secondary">
                            Guardar borrador
                        </button>
                    </div>
                    @if($isEdit)
                    <div class="text-center mt-2">
                        <small id="auto-save-indicator" class="text-muted" style="display:none;"></small>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Columna lateral --}}
        <div class="col-md-4">

            {{-- Acciones del post --}}
            @if($isEdit)
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="fw-bold mb-0">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('blog.posts.versions', $post) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-history me-1"></i> Versiones
                            @php $versionCount = $post->versions()->count(); @endphp
                            @if($versionCount > 0)
                                <span class="badge bg-secondary ms-1">{{ $versionCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('blog.posts.preview', $post) }}" target="_blank" class="btn btn-outline-info">
                            <i class="fas fa-eye me-1"></i> Vista previa
                        </a>
                        @can('create', \Modules\Blog\Models\BlogPost::class)
                        <form method="POST" action="{{ route('blog.posts.duplicate', $post) }}" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-copy me-1"></i> Duplicar
                            </button>
                        </form>
                        @endcan
                        <a href="{{ route('blog.posts.index') }}" class="btn btn-light">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- DeepL Traducción automática --}}
            @if($isEdit)
            <div class="card mb-3">
                <div class="card-header border-bottom p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-language me-1 text-primary"></i> Traducción automática
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Traduce el contenido guardado del post usando DeepL.</p>
                    <div class="mb-2">
                        <label for="deepl-target-lang" class="form-label small">Idioma destino</label>
                        <select id="deepl-target-lang" class="form-select form-select-sm">
                            @foreach($availableLocales as $locale => $label)
                                @if($locale !== $defaultLocale)
                                    <option value="{{ $locale }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" id="btn-deepl-translate" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-language me-1"></i> Traducir idioma seleccionado
                            <span id="deepl-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                        </button>
                        @if(count($availableLocales) > 1)
                        <button type="button" id="btn-deepl-auto-translate" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-globe me-1"></i> Traducir todos los idiomas
                            <span id="deepl-auto-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                        </button>
                        @endif
                        <button type="button" id="btn-save-and-translate" class="btn btn-sm btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar y traducir todo
                            <span id="save-translate-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                        </button>
                    </div>

                    {{-- Panel de progreso por idioma --}}
                    <div id="translation-progress" class="mt-3" style="display:none;">
                        <small class="fw-semibold text-muted d-block mb-2">Progreso de traducción</small>
                        @foreach($availableLocales as $locale => $label)
                            @if($locale !== $defaultLocale)
                            <div class="d-flex align-items-center mb-1 translation-progress-item" data-locale="{{ $locale }}">
                                <span class="me-2" style="width:14px;text-align:center;">
                                    <i class="fas fa-clock text-muted progress-icon" style="font-size:.75rem"></i>
                                </span>
                                <small class="flex-grow-1">{{ $label }}</small>
                                <small class="progress-status text-muted">Pendiente</small>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Leyenda de badges --}}
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-muted d-block mb-1 fw-semibold">Leyenda</small>
                        <div class="d-flex flex-wrap gap-2">
                            <small><span class="badge bg-success" style="font-size:.55rem"><i class="fas fa-check"></i></span> Completo</small>
                            <small><span class="badge bg-info" style="font-size:.55rem"><i class="fas fa-robot"></i></span> Auto</small>
                            <small><span class="badge bg-warning" style="font-size:.55rem"><i class="fas fa-exclamation-triangle"></i></span> Obsoleto</small>
                            <small><span class="badge bg-danger" style="font-size:.55rem"><i class="fas fa-times"></i></span> Vacío</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Cancelar (solo para create) --}}
            @if(!$isEdit)
            <div class="card mb-3">
                <div class="card-body">
                    <a href="{{ route('blog.posts.index') }}" class="btn btn-secondary w-100">Cancelar</a>
                </div>
            </div>
            @endif

        </div>
    </div>
</form>

{{-- Side-by-side comparison modal --}}
@if($isEdit)
<div class="modal fade" id="side-by-side-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-columns me-2"></i>Vista comparativa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3"><i class="fas fa-flag me-1"></i>Fuente ({{ $availableLocales[$defaultLocale] ?? strtoupper($defaultLocale) }})</h6>
                        <div id="sbs-source-title" class="mb-3">
                            <label class="form-label small text-muted">Título</label>
                            <div class="form-control bg-light" style="min-height:38px"></div>
                        </div>
                        <div id="sbs-source-description" class="mb-3">
                            <label class="form-label small text-muted">Descripción</label>
                            <div class="form-control bg-light" style="min-height:60px"></div>
                        </div>
                        <div id="sbs-source-content" class="mb-3">
                            <label class="form-label small text-muted">Contenido</label>
                            <div class="form-control bg-light" style="min-height:150px;max-height:300px;overflow-y:auto"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3"><i class="fas fa-language me-1"></i>Traducción (<span id="sbs-target-label"></span>)</h6>
                        <div id="sbs-target-title" class="mb-3">
                            <label class="form-label small text-muted">Título</label>
                            <div class="form-control bg-light" style="min-height:38px"></div>
                        </div>
                        <div id="sbs-target-description" class="mb-3">
                            <label class="form-label small text-muted">Descripción</label>
                            <div class="form-control bg-light" style="min-height:60px"></div>
                        </div>
                        <div id="sbs-target-content" class="mb-3">
                            <label class="form-label small text-muted">Contenido</label>
                            <div class="form-control bg-light" style="min-height:150px;max-height:300px;overflow-y:auto"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Re-translate fields modal --}}
<div class="modal fade" id="retranslate-fields-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-language me-2"></i>Re-traducir campos específicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Selecciona los campos que deseas re-traducir para el idioma <strong id="retranslate-locale-label"></strong>.</p>
                <input type="hidden" id="retranslate-locale" value="">
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="title" id="rf-title" checked>
                        <label class="form-check-label" for="rf-title">Título</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="description" id="rf-description" checked>
                        <label class="form-check-label" for="rf-description">Descripción</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="content" id="rf-content" checked>
                        <label class="form-check-label" for="rf-content">Contenido</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="seo_title" id="rf-seo-title">
                        <label class="form-check-label" for="rf-seo-title">Título SEO</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="seo_description" id="rf-seo-desc">
                        <label class="form-check-label" for="rf-seo-desc">Descripción SEO</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input retranslate-field" type="checkbox" value="seo_keywords" id="rf-seo-kws">
                        <label class="form-check-label" for="rf-seo-kws">Palabras clave SEO</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-retranslate-apply" class="btn btn-primary w-100 mb-1">
                    <i class="fas fa-language me-1"></i> Re-traducir campos seleccionados
                    <span id="retranslate-spinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- MODAL: UI Blocks --}}
@php
    $uiIconMap = [
        'button' => 'fa-hand-pointer', 'alert' => 'fa-exclamation-circle',
        'columns' => 'fa-table-columns', 'column' => 'fa-bars', 'youtube' => 'fa-youtube',
        'image' => 'fa-image', 'icon' => 'fa-icons', 'badge' => 'fa-tag',
        'card' => 'fa-id-card', 'accordion' => 'fa-layer-group',
        'accordion-item' => 'fa-list', 'quote' => 'fa-quote-left',
        'contact-form' => 'fa-envelope', 'form' => 'fa-file-alt',
        'our-offices' => 'fa-building', 'site-features' => 'fa-star',
        'reviews' => 'fa-star-half-alt', 'gallery' => 'fa-images',
        'spacer' => 'fa-arrows-alt-v', 'raw-html' => 'fa-code',
        'image-gallery' => 'fa-grip', 'video' => 'fa-video',
        'faq' => 'fa-question-circle', 'testimonials' => 'fa-comments',
        'cta' => 'fa-bullhorn', 'map' => 'fa-map-marker-alt',
        'ticker' => 'fa-scroll', 'slider' => 'fa-sliders',
    ];
    $uiCatIconMap = [
        'estructura'  => 'fa-table-columns',
        'contenido'   => 'fa-pen-nib',
        'media'       => 'fa-photo-film',
        'formularios' => 'fa-file-alt',
        'tema'        => 'fa-palette',
        'otros'       => 'fa-cubes',
    ];
    $blogShortcodes = [];
    $blogShortcodeCategories = collect();
    try {
        $blogShortcodes = app('shortcode')->getRegistered() ?? [];
        if ($blogShortcodes) {
            $dbCats = \Modules\Template\Models\Shortcode::query()
                ->whereIn('key', array_column($blogShortcodes, 'name'))
                ->pluck('category', 'key')->all();
            $blogShortcodes = array_map(function (array $sc) use ($dbCats): array {
                if (isset($dbCats[$sc['name']])) { $sc['category'] = $dbCats[$sc['name']]; }
                return $sc;
            }, $blogShortcodes);
        }
        $blogShortcodeCategories = \Modules\Template\Models\ShortcodeCategory::active()->get();
    } catch (\Throwable) {}
    $shortcodes = $blogShortcodes;
    $shortcodeCategories = $blogShortcodeCategories;
    $uiCategoryLabels = $shortcodeCategories->pluck('label', 'slug');
    $uiCategorySlugs  = $shortcodeCategories->pluck('slug');
    $uiGrouped = collect($shortcodes)->groupBy(fn($sc) => $sc['category'] ?? 'otros');
    $orderedCats = $uiCategorySlugs->filter(fn($s) => $uiGrouped->has($s))
        ->concat($uiGrouped->keys()->diff($uiCategorySlugs)->values())
        ->values();
    $uiTotalBlocks = collect($shortcodes)->count();
@endphp
@include('theme::partials.ui-blocks-modal')

{{-- MODAL: Block Config --}}
<div class="modal fade" id="blockConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="blockConfigTitle">Configurar bloque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="blockConfigBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="insertBlockBtn">Insertar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts-head')
    <script src="{{ asset('core/tinymce/tinymce.min.js') }}"></script>
@endpush

@push('styles')
<style>
    @keyframes badge-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    .badge-translating {
        animation: badge-pulse 1s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script>
// TinyMCE
var initializedEditors = {};
var mediaUploadUrl = '{{ route("media.files.upload") }}';
var mediaBaseUrl = '{{ url("media") }}';
var mediaListUrl = '{{ route("media.list") }}';

function tinyMCEConfig(locale) {
    return {
        selector: '#content-' + locale,
        language: 'es',
        height: 500,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'wordcount', 'emoticons',
            'codesample', 'directionality', 'paste'
        ],
        toolbar1: 'formatselect | fontselect fontsizeselect | bold italic underline strikethrough | forecolor backcolor | link | bullist numlist',
        toolbar2: 'alignleft aligncenter alignright alignjustify | ltr rtl | outdent indent | blockquote | image media table | codesample code | undo redo | searchreplace removeformat | fullscreen',
        menubar: false,
        branding: false,
        promotion: false,
        resize: true,
        automatic_uploads: true,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var fd = new FormData();
                fd.append('file', blobInfo.blob(), blobInfo.filename());
                fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
                $.ajax({
                    url: mediaUploadUrl,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.success) {
                            resolve(mediaBaseUrl + '/' + res.file.url.replace(/^media\//, ''));
                        } else {
                            reject('Error al subir la imagen');
                        }
                    },
                    error: function () { reject('Error al subir la imagen'); }
                });
            });
        },
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
        setup: function (editor) {
            editor.on('change input keyup', function () {
                editor.save();
            });
        }
    };
}

function initEditorForLocale(locale) {
    if (initializedEditors[locale]) return;
    initializedEditors[locale] = true;
    tinymce.init(tinyMCEConfig(locale));
}

function toggleEditor(locale) {
    var $wrapper = $('#editorWrapper-' + locale);
    if ($wrapper.is(':visible')) {
        var editor = tinymce.get('content-' + locale);
        if (editor) {
            editor.save();
            editor.destroy();
            initializedEditors[locale] = false;
        }
        $wrapper.hide();
    } else {
        $wrapper.show();
        initEditorForLocale(locale);
    }
}

$(document).ready(function () {

    // Initialize TinyMCE for first locale
    var firstLocale = '{{ array_key_first($availableLocales) }}';
    initEditorForLocale(firstLocale);

    // Initialize on tab change
    $('#langTabs button').on('shown.bs.tab', function (e) {
        var target = $(e.target).data('bs-target') || $(e.target).attr('data-bs-target');
        if (target) {
            var locale = target.replace('#pane-', '');
            initEditorForLocale(locale);
        }
    });

    // Flash messages
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    var defaultLocale = '{{ $defaultLocale }}';

    // Description character counter (default locale only)
    var $desc = $('#description-' + defaultLocale);
    function updateDescCount() {
        $('#desc-count').text($desc.val().length);
    }
    updateDescCount();
    $desc.on('input', updateDescCount);

    // Show/hide published_at
    $('#status').on('change', function () {
        $('#published-at-wrapper').toggleClass('d-none', $(this).val() !== 'published');
    });

    // SEO toggle
    $(document).on('click', '.seo-toggle', function (e) {
        e.preventDefault();
        var locale  = $(this).data('locale');
        var $section = $('#seoEditSection-' + locale);
        $section.slideToggle(200);
        $('.seo-toggle-text[data-locale="' + locale + '"]').text($section.is(':hidden') ? 'Mostrar' : 'Ocultar');
    });

    // SEO character counters
    $(document).on('input', '[id^="seo_title-"]', function () {
        var locale = this.id.replace('seo_title-', '');
        var len = $(this).val().length;
        $('.seo-title-count-' + locale).text(len + '/60').toggleClass('text-danger', len > 60);
    });

    $(document).on('input', '[id^="seo_description-"]', function () {
        var locale = this.id.replace('seo_description-', '');
        var len = $(this).val().length;
        $('.seo-desc-count-' + locale).text(len + '/160').toggleClass('text-danger', len > 160);
    });

    // Slug generation helpers
    function toSlug(str) {
        return str
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    function debounceSlug(fn, delay) {
        var timer;
        return function () {
            var ctx  = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    var slugManual = {{ $isEdit ? 'true' : 'false' }};

    $('#title-' + defaultLocale).on('keyup', debounceSlug(function () {
        if (slugManual) return;
        var title = $(this).val();
        if (!title) return;

        $.ajax({
            url: '{{ route("blog.posts.ajax.slug") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { title: title, ignoreId: '{{ $post?->id ?? 0 }}' },
            success: function (res) { $('#slug-' + defaultLocale).val(res.slug); },
            error: function () { $('#slug-' + defaultLocale).val(toSlug(title)); }
        });
    }, 500));

    $('#slug-' + defaultLocale).on('input', function () {
        slugManual = true;
    });

    $('#btn-regenerate-slug').on('click', function () {
        var title = $('#title-' + defaultLocale).val();
        if (!title) return;
        slugManual = false;

        $.ajax({
            url: '{{ route("blog.posts.ajax.slug") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { title: title, ignoreId: '{{ $post?->id ?? 0 }}' },
            success: function (res) { $('#slug-' + defaultLocale).val(res.slug); slugManual = true; },
            error: function () { $('#slug-' + defaultLocale).val(toSlug(title)); slugManual = true; }
        });
    });

    // Tag autocomplete
    var allTags = [];

    $.getJSON('{{ route("blog.tags.ajax.all") }}', function (data) {
        allTags = data;
    });

    $('#tag-search').on('input', function () {
        var query = $(this).val().trim().toLowerCase();
        var $sug  = $('#tag-suggestions').empty();

        if (!query) return;

        var matches = allTags.filter(function (t) {
            return t.name.toLowerCase().indexOf(query) !== -1;
        });

        if (!matches.length) {
            $sug.append('<div class="list-group-item list-group-item-action disabled"><small class="text-muted">Sin resultados</small></div>');
            return;
        }

        matches.slice(0, 10).forEach(function (tag) {
            if ($('.tag-input[data-id="' + tag.id + '"]').length) return;

            $sug.append(
                $('<button type="button" class="list-group-item list-group-item-action py-1"></button>')
                    .text(tag.name)
                    .on('click', function () {
                        addTag(tag);
                        $('#tag-search').val('');
                        $sug.empty();
                    })
            );
        });
    });

    function addTag(tag) {
        if ($('.tag-input[data-id="' + tag.id + '"]').length) return;

        $('#tags-badges').append(
            '<span class="badge bg-secondary me-1 mb-1 tag-badge" data-id="' + tag.id + '">' +
            tag.name +
            ' <a href="#" class="text-white ms-1 remove-tag" data-id="' + tag.id + '">×</a>' +
            '</span>'
        );
        $('#tags-hidden-inputs').append(
            '<input type="hidden" name="tags[]" value="' + tag.id + '" class="tag-input" data-id="' + tag.id + '">'
        );
    }

    $(document).on('click', '.remove-tag', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('.tag-badge[data-id="' + id + '"]').remove();
        $('.tag-input[data-id="' + id + '"]').remove();
    });

    // Show/hide publish_at scheduling
    $('#enable-scheduling').on('change', function () {
        $('#scheduled-wrapper').toggleClass('d-none', !this.checked);
        if (!this.checked) { $('#publish_at').val(''); }
    });

    $('#status').on('change', function () {
        var isDraftOrPending = ['draft', 'pending'].includes($(this).val());
        $('#schedule-toggle-wrapper').toggleClass('d-none', !isDraftOrPending);
        if (!isDraftOrPending) {
            $('#scheduled-wrapper').addClass('d-none');
            $('#publish_at').val('');
            $('#enable-scheduling').prop('checked', false);
        }
    });

    // DeepL translation
    @if($isEdit)
    var postId = {{ $post->id }};
    var translateUrl = '{{ route("blog.posts.translate", $post) }}';
    var autoTranslateUrl = '{{ route("blog.posts.auto-translate", $post) }}';
    var updateUrl = '{{ route("blog.posts.update", $post) }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Auto-save cada 60 segundos
    var autoSaveTimer  = null;
    var autoSaveEnabled = true;

    function triggerAutoSave() {
        if (!autoSaveEnabled) return;

        var formData = $('#post-form').serialize();

        $.ajax({
            url: updateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData + '&_method=PUT&auto_save=1',
            success: function (res) {
                if (res && res.auto_saved) {
                    var time = new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
                    $('#auto-save-indicator').text('Guardado automaticamente a las ' + time).show();
                    setTimeout(function () { $('#auto-save-indicator').fadeOut(1000); }, 4000);
                }
            },
            error: function () {}
        });
    }

    autoSaveTimer = setInterval(triggerAutoSave, 60000);

    $('#post-form').on('submit', function () {
        autoSaveEnabled = false;
        clearInterval(autoSaveTimer);
    });

    /**
     * Populate form fields for a locale tab with translation data.
     */
    function populateTranslationFields(locale, data) {
        $('#title-' + locale).val(data.title || '');
        $('#slug-' + locale).val(data.slug || '');
        $('#description-' + locale).val(data.description || '');
        $('#content-' + locale).val(data.content || '');
        $('#seo_title-' + locale).val(data.seo_title || '');
        $('#seo_description-' + locale).val(data.seo_description || '');
        $('#seo_keywords-' + locale).val(data.seo_keywords || '');
    }

    /**
     * Update the tab badge for a locale after translation.
     */
    function updateTabBadge(locale, status) {
        var $badge = $('[data-badge-locale="' + locale + '"]');
        var $tab = $('#tab-btn-' + locale);

        if (!$badge.length) {
            $tab.append(' <span class="badge ms-1" style="font-size:.55rem" data-badge-locale="' + locale + '"></span>');
            $badge = $('[data-badge-locale="' + locale + '"]');
        }

        if (status === 'auto_translated') {
            $badge.attr('class', 'badge bg-info ms-1').attr('style', 'font-size:.55rem').attr('title', 'Auto-traducido').html('<i class="fas fa-robot"></i>');
        } else if (status === 'ok') {
            $badge.attr('class', 'badge bg-success ms-1').attr('style', 'font-size:.55rem').attr('title', 'Completo').html('<i class="fas fa-check"></i>');
        } else if (status === 'error') {
            $badge.attr('class', 'badge bg-danger ms-1').attr('style', 'font-size:.55rem').attr('title', 'Error en traducción').html('<i class="fas fa-times"></i>');
        }
    }

    /**
     * Update a progress item for a locale.
     */
    function updateProgress(locale, state) {
        var $item = $('.translation-progress-item[data-locale="' + locale + '"]');
        var $icon = $item.find('.progress-icon');
        var $status = $item.find('.progress-status');

        if (state === 'translating') {
            $icon.attr('class', 'spinner-border spinner-border-sm text-primary progress-icon').css('font-size', '');
            $status.text('Traduciendo...').removeClass('text-muted text-success text-danger').addClass('text-primary');
        } else if (state === 'done') {
            $icon.attr('class', 'fas fa-check-circle text-success progress-icon').css('font-size', '.75rem');
            $status.text('Completado').removeClass('text-muted text-primary text-danger').addClass('text-success');
        } else if (state === 'error') {
            $icon.attr('class', 'fas fa-times-circle text-danger progress-icon').css('font-size', '.75rem');
            $status.text('Error').removeClass('text-muted text-primary text-success').addClass('text-danger');
        } else {
            $icon.attr('class', 'fas fa-clock text-muted progress-icon').css('font-size', '.75rem');
            $status.text('Pendiente').removeClass('text-primary text-success text-danger').addClass('text-muted');
        }
    }

    /**
     * Reset all progress items to pending state.
     */
    function resetProgress() {
        $('.translation-progress-item').each(function () {
            updateProgress($(this).data('locale'), 'pending');
        });
    }

    // Translate single locale
    $('#btn-deepl-translate').on('click', function () {
        var targetLocale = $('#deepl-target-lang').val();
        if (!targetLocale) { toastr.warning('Selecciona un idioma de destino'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#deepl-spinner').removeClass('d-none');

        $.ajax({
            url: translateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { target_locale: targetLocale },
            success: function (res) {
                if (res.translation) {
                    populateTranslationFields(targetLocale, res.translation);
                    updateTabBadge(targetLocale, 'auto_translated');

                    // Switch to translated tab
                    $('#tab-btn-' + targetLocale).tab('show');
                }
                toastr.success('Traducción al ' + targetLocale.toUpperCase() + ' completada.');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'Error al traducir con DeepL';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#deepl-spinner').addClass('d-none');
            }
        });
    });

    // Translate all locales with progress
    $('#btn-deepl-auto-translate').on('click', function () {
        

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#btn-deepl-translate').prop('disabled', true);
        $('#btn-save-and-translate').prop('disabled', true);
        $('#deepl-auto-spinner').removeClass('d-none');
        $('#translation-progress').show();
        resetProgress();

        $.ajax({
            url: autoTranslateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {},
            success: function (res) {
                // Populate form fields for each translated locale
                if (res.translations) {
                    $.each(res.translations, function (locale, data) {
                        populateTranslationFields(locale, data);
                        updateTabBadge(locale, 'auto_translated');
                        updateProgress(locale, 'done');
                    });
                }

                // Mark errors
                if (res.results) {
                    $.each(res.results, function (locale, status) {
                        if (status === 'error') {
                            updateProgress(locale, 'error');
                            updateTabBadge(locale, 'error');
                        }
                    });
                }

                if (res.success) {
                    toastr.success(res.message);
                } else {
                    toastr.warning(res.message);
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'Error al traducir con DeepL';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#btn-deepl-translate').prop('disabled', false);
                $('#btn-save-and-translate').prop('disabled', false);
                $('#deepl-auto-spinner').addClass('d-none');
            }
        });
    });

    // Save and Translate — saves form first, then triggers auto-translate
    $('#btn-save-and-translate').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#btn-deepl-translate').prop('disabled', true);
        $('#btn-deepl-auto-translate').prop('disabled', true);
        $('#save-translate-spinner').removeClass('d-none');
        $('#translation-progress').show();
        resetProgress();

        // Step 1: Save the form via AJAX
        var formData = $('#post-form').serialize();

        $.ajax({
            url: updateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData + '&_method=PUT&auto_save=1',
            success: function () {
                toastr.info('Post guardado. Iniciando traducciones...');

                // Step 2: Trigger auto-translate
                $.ajax({
                    url: autoTranslateUrl,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: {},
                    success: function (res) {
                        if (res.translations) {
                            $.each(res.translations, function (locale, data) {
                                populateTranslationFields(locale, data);
                                updateTabBadge(locale, 'auto_translated');
                                updateProgress(locale, 'done');
                            });
                        }

                        if (res.results) {
                            $.each(res.results, function (locale, status) {
                                if (status === 'error') {
                                    updateProgress(locale, 'error');
                                    updateTabBadge(locale, 'error');
                                }
                            });
                        }

                        if (res.success) {
                            toastr.success('Guardado y traducido correctamente.');
                        } else {
                            toastr.warning(res.message);
                        }
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.error
                            ? xhr.responseJSON.error
                            : 'Error al traducir con DeepL';
                        toastr.error(msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                        $('#btn-deepl-translate').prop('disabled', false);
                        $('#btn-deepl-auto-translate').prop('disabled', false);
                        $('#save-translate-spinner').addClass('d-none');
                    }
                });
            },
            error: function (xhr) {
                toastr.error('Error al guardar el post. Revisa los campos obligatorios.');
                $btn.prop('disabled', false);
                $('#btn-deepl-translate').prop('disabled', false);
                $('#btn-deepl-auto-translate').prop('disabled', false);
                $('#save-translate-spinner').addClass('d-none');
            }
        });
    });

    // =============================================
    // TRANSLATION TOOLBAR ACTIONS
    // =============================================

    // Mark as reviewed
    $(document).on('click', '.btn-mark-reviewed', function () {
        var locale = $(this).data('locale');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ url("panel/blog/posts") }}/' + postId + '/translations/' + locale + '/reviewed',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                updateTabBadge(locale, 'reviewed');
                // Update toolbar status text
                var $toolbar = $('#trans-toolbar-' + locale);
                $toolbar.find('small.text-muted').first().html(
                    '<i class="fas fa-check-circle me-1 text-success"></i>Marcado como revisado — ' +
                    '<span class="badge bg-success" style="font-size:.65rem"><i class="fas fa-check-circle me-1"></i>Revisado</span>'
                );
                toastr.success('Traducción marcada como revisada.');
            },
            error: function () { toastr.error('Error al marcar como revisada.'); },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    // Delete translation
    $(document).on('click', '.btn-delete-translation', function () {
        var locale = $(this).data('locale');
        

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ url("panel/blog/posts") }}/' + postId + '/translations/' + locale,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function () {
                // Clear form fields
                populateTranslationFields(locale, {});
                updateTabBadge(locale, 'error');
                // Update toolbar
                var $toolbar = $('#trans-toolbar-' + locale);
                $toolbar.find('small.text-muted').first().html('<i class="fas fa-info-circle me-1"></i>Sin traducción automática');
                $toolbar.find('.btn-mark-reviewed, .btn-delete-translation').remove();
                toastr.success('Traducción eliminada.');
            },
            error: function () { toastr.error('Error al eliminar la traducción.'); },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    // Copy source content to translation tab
    $(document).on('click', '.btn-copy-source', function () {
        var locale = $(this).data('locale');
        

        populateTranslationFields(locale, {
            title: $('#title-' + defaultLocale).val(),
            slug: $('#slug-' + defaultLocale).val(),
            description: $('#description-' + defaultLocale).val(),
            content: $('#content-' + defaultLocale).val(),
            seo_title: $('#seo_title-' + defaultLocale).val(),
            seo_description: $('#seo_description-' + defaultLocale).val(),
            seo_keywords: $('#seo_keywords-' + defaultLocale).val()
        });
        formDirty = true;
        toastr.info('Contenido fuente copiado al tab ' + locale.toUpperCase() + '.');
    });

    // Side-by-side comparison
    $(document).on('click', '.btn-side-by-side', function () {
        var locale = $(this).data('locale');
        var localeLabels = @json($availableLocales);

        $('#sbs-target-label').text(localeLabels[locale] || locale.toUpperCase());
        $('#sbs-source-title div').text($('#title-' + defaultLocale).val() || '—');
        $('#sbs-source-description div').text($('#description-' + defaultLocale).val() || '—');
        $('#sbs-source-content div').html($('#content-' + defaultLocale).val() || '<em class="text-muted">Sin contenido</em>');
        $('#sbs-target-title div').text($('#title-' + locale).val() || '—');
        $('#sbs-target-description div').text($('#description-' + locale).val() || '—');
        $('#sbs-target-content div').html($('#content-' + locale).val() || '<em class="text-muted">Sin contenido</em>');

        new bootstrap.Modal('#side-by-side-modal').show();
    });

    // Re-translate specific fields
    $(document).on('click', '.btn-retranslate-fields', function () {
        var locale = $(this).data('locale');
        var localeLabels = @json($availableLocales);
        $('#retranslate-locale').val(locale);
        $('#retranslate-locale-label').text(localeLabels[locale] || locale.toUpperCase());
        new bootstrap.Modal('#retranslate-fields-modal').show();
    });

    $('#btn-retranslate-apply').on('click', function () {
        var locale = $('#retranslate-locale').val();
        var fields = [];
        $('.retranslate-field:checked').each(function () { fields.push($(this).val()); });

        if (!fields.length) { toastr.warning('Selecciona al menos un campo.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#retranslate-spinner').removeClass('d-none');

        $.ajax({
            url: '{{ url("panel/blog/posts") }}/' + postId + '/translate-fields',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { target_locale: locale, fields: fields },
            success: function (res) {
                if (res.translation) {
                    // Only update the fields that were re-translated
                    fields.forEach(function (f) {
                        var val = res.translation[f] || '';
                        $('#' + f.replace('_', '_') + '-' + locale).val(val);
                    });
                    if (res.translation.slug && fields.includes('title')) {
                        $('#slug-' + locale).val(res.translation.slug);
                    }
                    updateTabBadge(locale, 'auto_translated');
                    updateSeoPreview(locale);
                }
                bootstrap.Modal.getInstance('#retranslate-fields-modal')?.hide();
                toastr.success('Campos re-traducidos correctamente.');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.error || 'Error al re-traducir campos.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#retranslate-spinner').addClass('d-none');
            }
        });
    });

    // =============================================
    // SEO PREVIEW LIVE UPDATE
    // =============================================
    function updateSeoPreview(locale) {
        var $preview = $('#seoPreview-' + locale);
        if (!$preview.length) return;

        var title = $('#seo_title-' + locale).val() || $('#title-' + locale).val() || 'Título del post';
        var slug = $('#slug-' + locale).val() || 'slug-del-post';
        var desc = $('#seo_description-' + locale).val() || $('#description-' + locale).val() || 'Descripción que aparecerá en los resultados de búsqueda.';

        $preview.find('div:eq(0)').text(title);
        $preview.find('div:eq(1)').text('{{ url("/blog") }}/' + slug);
        $preview.find('div:eq(2)').text(desc);
    }

    // Bind SEO preview updates to all locale fields
    $(document).on('input', '[id^="title-"], [id^="slug-"], [id^="description-"], [id^="seo_title-"], [id^="seo_description-"]', function () {
        var id = this.id;
        var locale = id.substring(id.lastIndexOf('-') + 1);
        updateSeoPreview(locale);
    });

    // =============================================
    // AUTO-SLUG IN TRANSLATION TABS
    // =============================================
    $(document).on('input', '[id^="title-"]', function () {
        var id = this.id;
        var locale = id.replace('title-', '');
        if (locale === defaultLocale) return;

        var $slug = $('#slug-' + locale);
        $slug.val(toSlug($(this).val()));
    });

    // =============================================
    // CHARACTER COUNTERS FOR ALL LOCALES
    // =============================================
    $(document).on('input', '[id^="description-"]', function () {
        var locale = this.id.replace('description-', '');
        var len = $(this).val().length;
        var $counter = $(this).closest('.mb-0').find('.desc-count-' + locale);
        // For non-default locales, update inline
        if (locale !== defaultLocale) {
            var maxLen = 500;
            var $small = $(this).next('small.trans-desc-count');
            if (!$small.length) {
                $(this).after('<small class="form-text text-muted trans-desc-count">' + len + '/500</small>');
            } else {
                $small.text(len + '/' + maxLen).toggleClass('text-danger', len > maxLen);
            }
        }
    });

    // =============================================
    // BEFOREUNLOAD — WARN UNSAVED CHANGES
    // =============================================
    var formDirty = false;
    var formSubmitting = false;

    $('#post-form').on('change input', 'input, textarea, select', function () {
        formDirty = true;
    });

    $('#post-form').on('submit', function () {
        formSubmitting = true;
    });

    $(window).on('beforeunload', function (e) {
        if (formDirty && !formSubmitting) {
            e.preventDefault();
            return '';
        }
    });

    // Check unsaved changes before translating
    function checkUnsavedBeforeTranslate() {
        return true;
    }

    // Wrap translate buttons with unsaved check
    var origTranslateClick = $('#btn-deepl-translate').data('events')?.click;
    $('#btn-deepl-translate').off('click').on('click', function () {
        if (!checkUnsavedBeforeTranslate()) return;
        var targetLocale = $('#deepl-target-lang').val();
        if (!targetLocale) { toastr.warning('Selecciona un idioma de destino'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#deepl-spinner').removeClass('d-none');
        updateProgress(targetLocale, 'translating');
        $('#translation-progress').show();

        $.ajax({
            url: translateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { target_locale: targetLocale },
            success: function (res) {
                if (res.translation) {
                    populateTranslationFields(targetLocale, res.translation);
                    updateTabBadge(targetLocale, 'auto_translated');
                    updateSeoPreview(targetLocale);
                    updateProgress(targetLocale, 'done');
                    $('#tab-btn-' + targetLocale).tab('show');
                }
                toastr.success('Traducción al ' + targetLocale.toUpperCase() + ' completada.');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.error || 'Error al traducir con DeepL';
                toastr.error(msg);
                updateProgress(targetLocale, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#deepl-spinner').addClass('d-none');
            }
        });
    });

    // =============================================
    // KEYBOARD SHORTCUTS
    // =============================================
    var localeKeys = @json(array_keys($availableLocales));

    $(document).on('keydown', function (e) {
        // Ctrl+1..9 to switch language tabs
        if (e.ctrlKey && !e.shiftKey && !e.altKey && e.key >= '1' && e.key <= '9') {
            var idx = parseInt(e.key) - 1;
            if (idx < localeKeys.length) {
                e.preventDefault();
                $('#tab-btn-' + localeKeys[idx]).tab('show');
            }
        }

        // Ctrl+Shift+T — translate selected locale
        if (e.ctrlKey && e.shiftKey && e.key === 'T') {
            e.preventDefault();
            $('#btn-deepl-translate').trigger('click');
        }

        // Ctrl+Shift+S — save form
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            $('#post-form').submit();
        }
    });

    // =============================================
    // BADGE ANIMATION DURING TRANSLATION
    // =============================================
    function animateBadge(locale, animating) {
        var $badge = $('[data-badge-locale="' + locale + '"]');
        if (animating) {
            $badge.addClass('badge-translating');
        } else {
            $badge.removeClass('badge-translating');
        }
    }

    // Override translate all to use per-locale progress with animation
    $('#btn-deepl-auto-translate').off('click').on('click', function () {
        if (!checkUnsavedBeforeTranslate()) return;
        

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#btn-deepl-translate').prop('disabled', true);
        $('#btn-save-and-translate').prop('disabled', true);
        $('#deepl-auto-spinner').removeClass('d-none');
        $('#translation-progress').show();
        resetProgress();

        // Translate per-locale sequentially for real progress feedback
        var targetLocales = localeKeys.filter(function (l) { return l !== defaultLocale; });
        var currentIdx = 0;

        function translateNext() {
            if (currentIdx >= targetLocales.length) {
                toastr.success('Traducción automática completada.');
                $btn.prop('disabled', false);
                $('#btn-deepl-translate').prop('disabled', false);
                $('#btn-save-and-translate').prop('disabled', false);
                $('#deepl-auto-spinner').addClass('d-none');
                return;
            }

            var locale = targetLocales[currentIdx];
            updateProgress(locale, 'translating');
            animateBadge(locale, true);

            $.ajax({
                url: translateUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: { target_locale: locale },
                success: function (res) {
                    if (res.translation) {
                        populateTranslationFields(locale, res.translation);
                        updateTabBadge(locale, 'auto_translated');
                        updateSeoPreview(locale);
                    }
                    updateProgress(locale, 'done');
                },
                error: function () {
                    updateProgress(locale, 'error');
                    updateTabBadge(locale, 'error');
                },
                complete: function () {
                    animateBadge(locale, false);
                    currentIdx++;
                    translateNext();
                }
            });
        }

        translateNext();
    });

    // Override save-and-translate to use per-locale progress
    $('#btn-save-and-translate').off('click').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#btn-deepl-translate').prop('disabled', true);
        $('#btn-deepl-auto-translate').prop('disabled', true);
        $('#save-translate-spinner').removeClass('d-none');
        $('#translation-progress').show();
        resetProgress();

        var formData = $('#post-form').serialize();

        $.ajax({
            url: updateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData + '&_method=PUT&auto_save=1',
            success: function () {
                formDirty = false;
                toastr.info('Post guardado. Iniciando traducciones...');

                var targetLocales = localeKeys.filter(function (l) { return l !== defaultLocale; });
                var currentIdx = 0;

                function translateNextSave() {
                    if (currentIdx >= targetLocales.length) {
                        toastr.success('Guardado y traducido correctamente.');
                        $btn.prop('disabled', false);
                        $('#btn-deepl-translate').prop('disabled', false);
                        $('#btn-deepl-auto-translate').prop('disabled', false);
                        $('#save-translate-spinner').addClass('d-none');
                        return;
                    }

                    var locale = targetLocales[currentIdx];
                    updateProgress(locale, 'translating');
                    animateBadge(locale, true);

                    $.ajax({
                        url: translateUrl,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: { target_locale: locale },
                        success: function (res) {
                            if (res.translation) {
                                populateTranslationFields(locale, res.translation);
                                updateTabBadge(locale, 'auto_translated');
                                updateSeoPreview(locale);
                            }
                            updateProgress(locale, 'done');
                        },
                        error: function () {
                            updateProgress(locale, 'error');
                            updateTabBadge(locale, 'error');
                        },
                        complete: function () {
                            animateBadge(locale, false);
                            currentIdx++;
                            translateNextSave();
                        }
                    });
                }

                translateNextSave();
            },
            error: function () {
                toastr.error('Error al guardar el post. Revisa los campos obligatorios.');
                $btn.prop('disabled', false);
                $('#btn-deepl-translate').prop('disabled', false);
                $('#btn-deepl-auto-translate').prop('disabled', false);
                $('#save-translate-spinner').addClass('d-none');
            }
        });
    });

    @endif

    // Image URL live preview
    $('#image_url').on('input', function () {
        var url = $(this).val().trim();
        if (url) {
            $('#image-preview').attr('src', url);
            $('#image-preview-wrapper').show();
            $('#image-placeholder').hide();
        } else {
            $('#image-preview-wrapper').hide();
            $('#image-placeholder').show();
        }
    });

    // Remove featured image
    $('#image-remove-btn').on('click', function () {
        $('#image_url').val('');
        $('#image-preview').attr('src', '');
        $('#image-preview-wrapper').hide();
        $('#image-placeholder').show();
    });

    // Upload featured image from file
    $('#image-upload-btn').on('click', function () {
        $('#image-file-input').click();
    });

    $('#image-file-input').on('change', function () {
        var file = this.files[0];
        if (!file) return;

        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

        var $btn = $('#image-upload-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: mediaUploadUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    var url = mediaBaseUrl + '/' + res.file.url.replace(/^media\//, '');
                    $('#image_url').val(url);
                    $('#image-preview').attr('src', url);
                    $('#image-preview-wrapper').show();
                    $('#image-placeholder').hide();
                    toastr.success('Imagen subida correctamente');
                } else {
                    toastr.error('Error al subir la imagen');
                }
            },
            error: function () {
                toastr.error('Error al subir la imagen');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-upload"></i>');
                $('#image-file-input').val('');
            }
        });
    });

    // =========================================================
    // MEDIA PICKER (usa componente global MediaPicker)
    // =========================================================
    var activeLocale = '{{ $defaultLocale }}';
    var mpUrls = {
        list:   mediaListUrl,
        upload: mediaUploadUrl,
        base:   mediaBaseUrl
    };

    // Botón "Agregar" — insertar imagen en editor via MediaPicker
    $(document).on('click', '.add-media-btn', function () {
        var locale = $(this).data('locale');
        window.MediaPicker.open({
            urls: mpUrls,
            filter: 'image',
            onSelect: function (url) {
                var tag = '<img src="' + url + '" alt="" style="max-width:100%">';
                var editor = tinymce.get('content-' + locale);
                if (editor) {
                    editor.insertContent(tag);
                } else {
                    var ta = document.getElementById('content-' + locale);
                    var pos = ta.selectionStart || ta.value.length;
                    ta.value = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
                }
            }
        });
    });

    // Botón "Galería" — seleccionar imagen destacada via MediaPicker
    $('#image-select-btn').on('click', function () {
        window.MediaPicker.open({
            urls: mpUrls,
            filter: 'image',
            onSelect: function (url) {
                $('#image_url').val(url).trigger('input');
            }
        });
    });

    // =========================================================
    // UI BLOCKS
    // =========================================================
    var uiBlocksData = @json($blogShortcodes ?? []);

    $(document).on('click', '.ui-blocks-btn', function () {
        activeLocale = $(this).data('locale');
    });

    // Categoría activa (sidebar buttons + mobile pills)
    $(document).on('click', '.ui-cat-btn, .ui-cat-pill', function () {
        var cat = $(this).data('cat');
        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('[data-cat="' + cat + '"]').addClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        $('.ui-blocks-pane[data-pane="' + cat + '"]').removeClass('d-none');
        $('#uiBlocksSearch').val('');
        $('#uiSearchClear').hide();
        $('#uiSearchMsg').hide();
        $('#uiEmptySearch').hide();
    });

    // Búsqueda
    $('#uiBlocksSearch').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        var $clear = $('#uiSearchClear');
        var $msg   = $('#uiSearchMsg');
        var $empty = $('#uiEmptySearch');

        $clear.toggle(q.length > 0);

        if (!q) {
            $msg.hide();
            $empty.hide();
            var activeCat = $('.ui-cat-btn.active, .ui-cat-pill.active').first().data('cat') || '__all__';
            $('.ui-blocks-pane').addClass('d-none');
            $('.ui-blocks-pane[data-pane="' + activeCat + '"]').removeClass('d-none');
            $('.ui-block-item').show();
            return;
        }

        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        var $allPane = $('.ui-blocks-pane[data-pane="__all__"]');
        $allPane.removeClass('d-none');

        var count = 0;
        $allPane.find('.ui-block-item').each(function () {
            var name = $(this).data('name') || '';
            var cat  = $(this).data('category') || '';
            var desc = $(this).find('.sc-desc').text().toLowerCase();
            var match = name.includes(q) || cat.includes(q) || desc.includes(q);
            $(this).toggle(match);
            if (match) { count++; }
        });

        if (count === 0) {
            $allPane.addClass('d-none');
            $empty.css('display', 'flex');
            $msg.hide();
        } else {
            $empty.hide();
            $msg.text(count + ' resultado' + (count !== 1 ? 's' : '') + ' para "' + q + '"').show();
        }
    });

    $('#uiSearchClear').on('click', function () {
        $('#uiBlocksSearch').val('').trigger('input');
    });

    // Clic en card → insertar bloque
    $(document).on('click', '.ui-block-card', function () {
        openBlockConfig($(this).data('block-key'), $(this).data('block-name'));
    });

    // Limpiar búsqueda al abrir el modal
    $('#uiBlocksModal').on('show.bs.modal', function () {
        $('#uiBlocksSearch').val('');
        $('#uiSearchClear').hide();
        $('#uiSearchMsg').hide();
        $('#uiEmptySearch').hide();
        $('.ui-cat-btn, .ui-cat-pill').removeClass('active');
        $('[data-cat="__all__"]').addClass('active');
        $('.ui-blocks-pane').addClass('d-none');
        $('.ui-blocks-pane[data-pane="__all__"]').removeClass('d-none');
        $('.ui-block-item').show();
    });

    function openBlockConfig(key, name) {
        var block = uiBlocksData.find(function (b) { return b.name === key; });
        var hasAttrs = block && block.attributes && block.attributes.length;
        if (!hasAttrs) {
            insertBlock(key, block);
            return;
        }
        $('#uiBlocksModal').modal('hide');
        $('#blockConfigTitle').text('Configurar: ' + name);
        var html = '';
        block.attributes.forEach(function (attr) {
            var id = 'attr-' + attr.name;
            html += '<div class="mb-3"><label class="form-label fw-semibold">' + (attr.label || attr.name) + '</label>';
            if (attr.type === 'select' && attr.options) {
                html += '<select class="form-select" id="' + id + '">';
                Object.entries(attr.options).forEach(function (entry) {
                    html += '<option value="' + entry[0] + '">' + entry[1] + '</option>';
                });
                html += '</select>';
            } else if (attr.type === 'textarea') {
                html += '<textarea class="form-control" id="' + id + '" rows="4" placeholder="' + (attr.placeholder || '') + '"></textarea>';
            } else {
                html += '<input type="' + (attr.type || 'text') + '" class="form-control" id="' + id + '" placeholder="' + (attr.placeholder || '') + '">';
            }
            html += '</div>';
        });
        $('#blockConfigBody').html(html);
        $('#insertBlockBtn').off('click').on('click', function () {
            insertBlock(key, block);
            $('#blockConfigModal').modal('hide');
        });
        setTimeout(function () { $('#blockConfigModal').modal('show'); }, 300);
    }

    function insertBlock(key, block) {
        var attrs = '';
        if (block && block.attributes && block.attributes.length) {
            attrs = block.attributes.map(function (attr) {
                var val = ($('#attr-' + attr.name).val() || '').replace(/"/g, '&quot;');
                return val ? attr.name + '="' + val + '"' : '';
            }).filter(Boolean).join(' ');
        }
        var shortcode = attrs ? '[' + key + ' ' + attrs + '][/' + key + ']' : '[' + key + '][/' + key + ']';
        insertContentAtEditor(activeLocale, shortcode);
        $('#uiBlocksModal').modal('hide');
    }

    function insertContentAtEditor(locale, content) {
        var editor = tinymce.get('content-' + locale);
        if (editor) {
            editor.insertContent(content);
        } else {
            var ta = document.getElementById('content-' + locale);
            if (ta) {
                var pos = ta.selectionStart || ta.value.length;
                ta.value = ta.value.substring(0, pos) + content + ta.value.substring(pos);
            }
        }
    }

});
</script>
@endpush
