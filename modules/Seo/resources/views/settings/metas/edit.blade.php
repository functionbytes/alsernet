@extends('layouts.theme')

@section('title', 'Editar meta SEO')

@section('content')

    @include('core::components.card', ['title' => 'Editar meta SEO: ' . ($meta->seoable?->title ?? $meta->seoable?->name ?? ($meta->seoable_type ? class_basename($meta->seoable_type).' #'.$meta->seoable_id : ''))])

    @include('core::components.alerts')

    @if($siblingMetas->count() > 1 || $activeLocales->isNotEmpty())
    @php
        $localeNameMap = $activeLocales->pluck('name', 'code')->toArray();
        $existingLocaleCodes = $siblingMetas->keys()->filter(fn($k) => $k !== 'global')->toArray();
        $missingLocaleObjects = $activeLocales->filter(fn($l) => !in_array($l->code, $existingLocaleCodes));
    @endphp
    <div class="widget-content mb-0 pb-0">
        <ul class="nav nav-tabs border-0 user-profile-tab mb-3" role="tablist">
            @foreach($siblingMetas as $localeKey => $siblingMeta)
                @php
                    $isActive = $siblingMeta->id === $meta->id;
                    $label = $localeKey === 'global' ? 'Global' : ($localeNameMap[$localeKey] ?? strtoupper($localeKey));
                    $hasIssue = !$isActive && (empty($siblingMeta->title) || empty($siblingMeta->description));
                @endphp
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.seo.metas.edit', $siblingMeta) }}"
                       class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $isActive ? 'active' : '' }}"
                       role="tab">
                        <span class="d-none d-md-block">
                            {{ $label }}
                            @if($hasIssue)
                                <span class="badge bg-danger ms-1" title="Faltan campos obligatorios"><i class="fas fa-exclamation"></i></span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach

            @if($showMissingLocales && $missingLocaleObjects->isNotEmpty())
            <li class="nav-item" role="presentation">
                <div class="dropdown">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 dropdown-toggle border-0"
                            type="button" data-bs-toggle="dropdown">
                        <span class="d-none d-md-block"><i class="fas fa-plus me-1"></i>Añadir idioma</span>
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($missingLocaleObjects as $missingLocale)
                        <li>
                            <form action="{{ route('settings.seo.metas.create-locale', $meta) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $missingLocale->code }}">
                                <button type="submit" class="dropdown-item">{{ $missingLocale->name }}</button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            @endif
        </ul>
    </div>
    @endif

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">
        <div class="col-lg-8">
            <div class="card">
                <form action="{{ route('settings.seo.metas.update', $meta) }}" method="POST">
                    @if($meta->seoable instanceof \Modules\Page\Models\Page)
                    <div class="alert alert-info border-0 bg-info-subtle mb-0 p-3 rounded-0 rounded-top" style="border-bottom:1px solid #bee2fd!important;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
                            <div>
                                <strong>SEO de página</strong> — El título y descripción SEO de esta página se gestionan aquí (ya no en el editor de la página).
                                <a href="{{ route('pages.edit', $meta->seoable->id) }}" class="ms-2 fw-semibold alert-link">
                                    <i class="fas fa-external-link-alt me-1"></i>Editar contenido de la página
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                    @csrf
                    @method('PUT')
                    @if(isset($globalMeta) && $globalMeta)
                    <div class="alert alert-primary border-0 bg-primary-subtle mb-0 p-3 rounded-0" style="border-bottom:1px solid #b6d4fe!important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-language flex-shrink-0"></i>
                            <div>
                                <strong>Versión de idioma</strong> — Los campos vacíos heredan los valores del meta <a href="{{ route('settings.seo.metas.edit', $globalMeta) }}" class="alert-link">Global</a>. Rellena solo lo que quieras personalizar para este idioma.
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="card-body">

                        <h6 class="fw-bold text-dark mb-1">SEO basico</h6>
                        <p class="text-muted mb-3">Configura los metadatos básicos para mejorar el posicionamiento en buscadores.</p>

                        @if($meta->seoable !== null)
                        <button type="button" id="auto-generate-btn" class="btn btn-sm btn-outline-secondary mb-3"
                                data-model-class="{{ get_class($meta->seoable) }}"
                                data-model-id="{{ $meta->seoable_id }}">
                            <i class="fas fa-magic me-1"></i>Auto-generar desde contenido
                        </button>
                        @endif

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Titulo SEO</label>
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control @error('title') is-invalid @enderror"
                                           id="title"
                                           name="title"
                                           value="{{ old('title', $meta->title) }}"
                                           maxlength="255"
                                           placeholder="{{ $globalMeta?->title ?? 'Titulo optimizado para buscadores' }}">
                                    <button type="button" class="btn btn-outline-secondary copy-field-btn" data-target="#title" title="Copiar al portapapeles">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="mt-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><span id="title-chars">0</span>/60 caracteres</small>
                                        <small><span id="title-pixels" class="badge bg-success">0px / ~600px</span></small>
                                    </div>
                                    <div class="progress mt-1" style="height:3px;border-radius:2px;">
                                        <div id="title-inline-bar" class="progress-bar" style="width:0%;transition:width .2s,background .2s;"></div>
                                    </div>
                                </div>
                                @error('title')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- A/B Test section --}}
                            <div class="col-12 mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold text-primary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#ab-test-section"
                                            aria-expanded="{{ ($meta->title_b || $meta->description_b) ? 'true' : 'false' }}">
                                        <i class="fas fa-flask me-1"></i> A/B Test (opcional)
                                    </button>
                                    <hr class="flex-grow-1 my-0">
                                </div>
                                <div class="collapse{{ ($meta->title_b || $meta->description_b) ? ' show' : '' }}" id="ab-test-section">
                                    <div class="border rounded p-3">
                                            <p class="text-muted mb-3">Define una variante B para comparar rendimiento con GSC. Se servirá automáticamente rotando entre A y B.</p>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Título variante B</label>
                                                <input type="text" name="title_b" class="form-control @error('title_b') is-invalid @enderror"
                                                       value="{{ old('title_b', $meta->title_b) }}" maxlength="60"
                                                       placeholder="Título alternativo para probar">
                                                @error('title_b')
                                                    <span class="field-validation-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Descripción variante B</label>
                                                <textarea name="description_b" class="form-control @error('description_b') is-invalid @enderror"
                                                          rows="3" maxlength="160"
                                                          placeholder="Descripción alternativa">{{ old('description_b', $meta->description_b) }}</textarea>
                                                @error('description_b')
                                                    <span class="field-validation-error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            @if($meta->title_b || $meta->description_b)
                                            <div class="row g-3">
                                                <div class="col-md-4 text-center">
                                                    <div class="fw-bold">Variante A</div>
                                                    <div class="display-6 text-primary">{{ $meta->ab_impressions_a }}</div>
                                                    <small class="text-muted">impresiones</small>
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <div class="fw-bold">Variante B</div>
                                                    <div class="display-6 text-info">{{ $meta->ab_impressions_b }}</div>
                                                    <small class="text-muted">impresiones</small>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Ganador</label>
                                                    <select name="ab_winner" class="form-select">
                                                        <option value="">Rotación automática</option>
                                                        <option value="a" {{ old('ab_winner', $meta->ab_winner) === 'a' ? 'selected' : '' }}>Fijar variante A</option>
                                                        <option value="b" {{ old('ab_winner', $meta->ab_winner) === 'b' ? 'selected' : '' }}>Fijar variante B</option>
                                                    </select>
                                                </div>
                                            </div>
                                            @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                    <span>Descripcion SEO</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted copy-field-btn" data-target="#description" title="Copiar al portapapeles">
                                        <i class="fas fa-copy me-1"></i><small>Copiar</small>
                                    </button>
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description"
                                          name="description"
                                          rows="3"
                                          maxlength="500"
                                          placeholder="{{ $globalMeta?->description ?? 'Descripcion atractiva para motores de busqueda' }}">{{ old('description', $meta->description) }}</textarea>
                                <div class="mt-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><span id="desc-chars">{{ strlen(old('description', $meta->description ?? '')) }}</span>/160 caracteres</small>
                                        <small><span id="desc-pixels" class="badge bg-success">0px / ~920px</span></small>
                                    </div>
                                    <div class="progress mt-1" style="height:3px;border-radius:2px;">
                                        <div id="desc-inline-bar" class="progress-bar" style="width:0%;transition:width .2s,background .2s;"></div>
                                    </div>
                                </div>
                                @error('description')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Palabras clave</label>
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

                            {{-- DeepL auto-translation --}}
                            <div class="col-12 mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold text-primary" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#deepl-section">
                                        <i class="fas fa-language me-1"></i> Traducir con DeepL (opcional)
                                    </button>
                                    <hr class="flex-grow-1 my-0">
                                </div>
                                <div class="collapse" id="deepl-section">
                                    <div class="border rounded p-3">
                                            <p class="text-muted mb-3">Traduce automáticamente los campos SEO a otro idioma.</p>
                                            <div class="row g-2 align-items-end mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Idioma destino</label>
                                                    <select id="deepl-target-lang" class="form-select form-select-sm">
                                                        <option value="EN">Inglés (EN)</option>
                                                        <option value="ES">Español (ES)</option>
                                                        <option value="FR">Francés (FR)</option>
                                                        <option value="DE">Alemán (DE)</option>
                                                        <option value="IT">Italiano (IT)</option>
                                                        <option value="PT">Portugués (PT)</option>
                                                        <option value="NL">Neerlandés (NL)</option>
                                                        <option value="PL">Polaco (PL)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label small">Campos a traducir</label>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        @foreach(['title' => 'Título', 'description' => 'Descripción', 'og_title' => 'OG Título', 'og_description' => 'OG Descripción'] as $field => $label)
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input deepl-field-check" type="checkbox"
                                                                   value="{{ $field }}" id="deepl-field-{{ $field }}" checked>
                                                            <label class="form-check-label small" for="deepl-field-{{ $field }}">{{ $label }}</label>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-deepl-translate"
                                                            data-meta-id="{{ $meta->id }}">
                                                        <i class="fas fa-language me-1"></i> Traducir
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="deepl-results" class="d-none">
                                                <div class="alert alert-info p-2 small mb-2">
                                                    <i class="fas fa-info-circle me-1"></i> Revisa las traducciones y aplica las que desees.
                                                </div>
                                                <div id="deepl-translations"></div>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Keyword objetivo</label>
                                <input type="text" id="target_keyword" name="target_keyword" class="form-control @error('target_keyword') is-invalid @enderror"
                                       value="{{ old('target_keyword', $meta->target_keyword) }}"
                                       placeholder="ej: comprar zapatos online"
                                       maxlength="100"
                                       autocomplete="off">
                                <div id="keyword-suggestions" class="mt-2"></div>
                                <small class="text-muted">La palabra clave principal que quieres posicionar para esta página</small>
                                @error('target_keyword')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Robots</label>
                                <select class="form-select @error('robots') is-invalid @enderror select2" name="robots">
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

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">URL canonica</label>
                                <div class="input-group">
                                    <input type="url"
                                           class="form-control @error('canonical_url') is-invalid @enderror"
                                           id="canonical_url"
                                           name="canonical_url"
                                           value="{{ old('canonical_url', $meta->canonical_url) }}"
                                           maxlength="500"
                                           placeholder="https://ejemplo.com/pagina-canonica">
                                    @if(empty($meta->canonical_url))
                                    <button type="button" class="btn btn-outline-secondary" id="btn-generate-canonical"
                                            data-meta-id="{{ $meta->id }}">
                                        <i class="fas fa-magic"></i> Auto-generar
                                    </button>
                                    @endif
                                </div>
                                <small class="text-muted">Evita contenido duplicado</small>
                                @error('canonical_url')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Idioma</label>
                                <select class="form-select @error('locale') is-invalid @enderror" name="locale">
                                    <option value="">— Global (todos los idiomas) —</option>
                                    @foreach($activeLocales as $loc)
                                        <option value="{{ $loc->code }}" @selected(old('locale', $meta->locale) === $loc->code)>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Asocia este meta a un idioma específico, o déjalo en Global para que aplique a todos.</small>
                                @error('locale')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Open Graph</h6>
                        <p class="text-muted mb-3">Controla cómo se comparte el contenido en Facebook, LinkedIn y otras redes sociales.</p>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">OG Titulo</label>
                                <input type="text"
                                       class="form-control @error('og_title') is-invalid @enderror"
                                       id="og_title"
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
                                <label class="form-label fw-semibold">OG Descripcion</label>
                                <textarea class="form-control @error('og_description') is-invalid @enderror"
                                          id="og_description"
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

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">OG Tipo</label>
                                <select class="form-select @error('og_type') is-invalid @enderror select2" name="og_type">
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

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Imagen Open Graph</label>
                                @php
                                    $ogVal = old('og_image', $meta->og_image);
                                    $ogPreview = $ogVal ? preg_replace('#^https?://[^/]+#', request()->getSchemeAndHttpHost(), $ogVal) : '';
                                @endphp
                                <div class="seo-img-card {{ $ogVal ? 'has-image' : 'is-empty' }}"
                                     id="seo-img-card-og" data-key="og_image" data-label="Imagen Open Graph">
                                    <div class="seo-img-card__preview">
                                        <img src="{{ $ogPreview }}" alt="Imagen Open Graph" id="seo-img-og" class="{{ $ogVal ? '' : 'd-none' }}">
                                        <div class="seo-img-card__empty {{ $ogVal ? 'd-none' : '' }}">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                            <span>Click para subir · Recomendado 1200×630 px</span>
                                        </div>
                                        <div class="seo-img-card__overlay">
                                            <span><i class="fas fa-image me-1"></i> Cambiar</span>
                                        </div>
                                    </div>
                                    <div class="seo-img-card__body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold">Imagen para compartir en redes</div>
                                                <small class="text-muted">Aparece en Facebook, LinkedIn, WhatsApp, etc.</small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link seo-img-clear-btn {{ $ogVal ? '' : 'd-none' }}"
                                                    data-target="og" title="Quitar imagen">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden"
                                           class="@error('og_image') is-invalid @enderror"
                                           id="og_image"
                                           name="og_image"
                                           value="{{ $ogVal }}"
                                           data-preview="#og-image-preview">
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-generate-og"
                                            data-meta-id="{{ $meta->id }}" data-template="default">
                                        <i class="fas fa-image me-1"></i> Generar OG automática
                                    </button>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <small class="text-muted me-2 align-self-center">Plantilla:</small>
                                        @foreach(['default' => 'Rojo', 'dark' => 'Oscura', 'minimal' => 'Minimal'] as $tpl => $label)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary og-template-btn {{ $tpl === 'default' ? 'active' : '' }}"
                                                data-template="{{ $tpl }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Genera una imagen 1200×630 con el título de la página.</small>
                                <div id="og-image-preview" class="d-none"></div>
                                @error('og_image')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="fw-bold text-dark mb-0">Twitter Card</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copy-og-to-twitter">
                                <i class="fas fa-copy me-1"></i>Igual que OG
                            </button>
                        </div>
                        <p class="text-muted mb-3">Personaliza la vista previa al compartir en Twitter/X.</p>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Twitter Card tipo</label>
                                <select class="form-select @error('twitter_card') is-invalid @enderror select2" name="twitter_card">
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

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Imagen Twitter Card</label>
                                @php
                                    $twVal = old('twitter_image', $meta->twitter_image);
                                    $twPreview = $twVal ? preg_replace('#^https?://[^/]+#', request()->getSchemeAndHttpHost(), $twVal) : '';
                                @endphp
                                <div class="seo-img-card {{ $twVal ? 'has-image' : 'is-empty' }}"
                                     id="seo-img-card-twitter" data-key="twitter_image" data-label="Imagen Twitter Card">
                                    <div class="seo-img-card__preview">
                                        <img src="{{ $twPreview }}" alt="Imagen Twitter" id="seo-img-twitter" class="{{ $twVal ? '' : 'd-none' }}">
                                        <div class="seo-img-card__empty {{ $twVal ? 'd-none' : '' }}">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                            <span>Click para subir · Recomendado 1200×630 px</span>
                                        </div>
                                        <div class="seo-img-card__overlay">
                                            <span><i class="fas fa-image me-1"></i> Cambiar</span>
                                        </div>
                                    </div>
                                    <div class="seo-img-card__body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold">Imagen para Twitter/X</div>
                                                <small class="text-muted">Si la dejas vacía, usará la de Open Graph.</small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link seo-img-clear-btn {{ $twVal ? '' : 'd-none' }}"
                                                    data-target="twitter" title="Quitar imagen">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden"
                                           class="@error('twitter_image') is-invalid @enderror"
                                           id="twitter_image"
                                           name="twitter_image"
                                           value="{{ $twVal }}">
                                </div>
                                <div id="twitter-image-preview" class="d-none"></div>
                                @error('twitter_image')
                                    <span class="field-validation-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Twitter Titulo</label>
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
                                <label class="form-label fw-semibold">Twitter Descripcion</label>
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
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Guardar cambios
                        </button>
                        <a href="{{ route('settings.seo.schema-org.edit', $meta) }}" class="btn btn-outline-primary w-100 mb-2">
                            Editar Schema.org
                        </a>
                        <a href="{{ route('settings.seo.metas.show', $meta) }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">

            {{-- Live SERP preview --}}
            <x-serp-preview :meta="$meta" />

            {{-- Score badge --}}
            <div class="card mb-3" id="score-summary-card" style="display:none!important;">
                <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Score SEO</div>
                        <div class="fw-bold" id="score-summary-label" style="font-size:1.4rem;">—</div>
                    </div>
                    <div id="score-summary-circle"
                         style="width:56px;height:56px;border-radius:50%;border:4px solid #dee2e6;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#6c757d;">
                        —
                    </div>
                </div>
            </div>

            @php
                $seoable = $meta->seoable;
                $isPage  = $seoable instanceof \Modules\Page\Models\Page;
                $previewTrans = $isPage
                    ? ($seoable->translations->load('localeModel')->first(fn ($t) => $t->localeModel?->is_default) ?? $seoable->translations->first())
                    : null;
            @endphp

            <div class="card">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Modelo asociado</h6>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted small fw-normal">Tipo</dt>
                        <dd class="fw-semibold mb-2">{{ class_basename($meta->seoable_type) }}</dd>

                        <dt class="text-muted small fw-normal">ID</dt>
                        <dd class="fw-semibold mb-2">#{{ $meta->seoable_id }}</dd>

                        <dt class="text-muted small fw-normal">Nombre</dt>
                        <dd class="fw-semibold mb-0">{{ $seoable?->title ?? $seoable?->name ?? 'N/A' }}</dd>
                    </dl>

                    @if($isPage)
                    <div class="seo-info-note mt-3 mb-0">
                        <strong>Todo el SEO</strong> de esta página se gestiona desde aquí.
                    </div>
                    @endif
                </div>
                @if($isPage)
                <div class="card-footer d-flex flex-column gap-2">
                    <a href="{{ route('pages.edit', $seoable->id) }}"
                       class="btn btn-sm btn-outline-primary w-100">
                        Editar página
                    </a>
                    @if($previewTrans?->slug)
                    <a href="{{ url($previewTrans->slug) }}" target="_blank"
                       class="btn btn-sm btn-outline-secondary w-100">
                        Ver página pública
                    </a>
                    @endif
                </div>
                @endif
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Fechas</h6>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted small fw-normal">Creado</dt>
                        <dd class="fw-semibold mb-2">
                            {{ $meta->created_at->format('d/m/Y H:i') }}
                            <small class="text-muted fw-normal">({{ $meta->created_at->diffForHumans() }})</small>
                        </dd>

                        <dt class="text-muted small fw-normal">Actualizado</dt>
                        <dd class="fw-semibold mb-0">
                            {{ $meta->updated_at->format('d/m/Y H:i') }}
                            <small class="text-muted fw-normal">({{ $meta->updated_at->diffForHumans() }})</small>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3" id="preview-card">
                <div class="card-header p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">Vista previa en tiempo real</h6>
                    <div class="btn-group btn-group-sm" role="group" id="preview-device-toggle">
                        <button type="button" class="btn btn-outline-secondary active" data-device="desktop" title="Escritorio">
                            <i class="fas fa-desktop"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-device="mobile" title="Móvil">
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <ul class="nav nav-pills nav-fill gap-1 mb-3 seo-platform-tabs" id="previewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-google" data-bs-toggle="tab" data-bs-target="#pane-google" type="button" role="tab" title="Google" aria-label="Google">
                                <i class="fab fa-google"></i>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-facebook" data-bs-toggle="tab" data-bs-target="#pane-facebook" type="button" role="tab" title="Facebook" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-twitter" data-bs-toggle="tab" data-bs-target="#pane-twitter" type="button" role="tab" title="Twitter/X" aria-label="Twitter/X">
                                <i class="fab fa-x-twitter"></i>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-whatsapp" data-bs-toggle="tab" data-bs-target="#pane-whatsapp" type="button" role="tab" title="WhatsApp" aria-label="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="previewTabContent">

                        {{-- Google SERP --}}
                        <div class="tab-pane fade show active" id="pane-google" role="tabpanel">
                            <div id="google-desktop-preview">
                                <div class="p-3 rounded border bg-white" style="font-family:'Google Sans',Roboto,Arial,sans-serif; max-width:600px;">
                                    {{-- Site breadcrumb line --}}
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div style="width:26px;height:26px;border-radius:50%;background:#f1f3f4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-globe" style="font-size:11px;color:#5f6368;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:14px;color:#202124;line-height:1.2;" id="prev-google-sitename">{{ theme_option('site_title') ?: config('app.name') }}</div>
                                            <div style="font-size:12px;color:#5f6368;line-height:1.2;" id="prev-google-url-crumb">{{ old('canonical_url', $meta->canonical_url) ?: url('/') }}</div>
                                        </div>
                                    </div>
                                    {{-- Title --}}
                                    <div id="prev-google-title"
                                         class="prev-title-text"
                                         style="color:#1a0dab;font-size:20px;line-height:1.3;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        {{ old('title', $meta->title) ?: 'Título de la página' }}
                                    </div>
                                    {{-- Description --}}
                                    <div id="prev-google-desc"
                                         style="color:#4d5156;font-size:14px;line-height:1.58;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        {{ old('description', $meta->description) ?: 'Descripción de la página que aparece en los resultados de búsqueda de Google.' }}
                                    </div>
                                </div>
                            </div>
                            <div id="google-mobile-preview" class="d-none">
                                <div class="border rounded overflow-hidden bg-white" style="max-width:360px;font-family:'Google Sans',Roboto,Arial,sans-serif;">
                                    <div class="p-2 d-flex align-items-center gap-2" style="background:#f8f9fa;border-bottom:1px solid #e0e0e0;">
                                        <div style="width:20px;height:20px;border-radius:50%;background:#e0e0e0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-globe" style="font-size:9px;color:#5f6368;"></i>
                                        </div>
                                        <div style="font-size:12px;color:#5f6368;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="prev-google-mobile-crumb">{{ old('canonical_url', $meta->canonical_url) ?: url('/') }}</div>
                                    </div>
                                    <div class="p-3">
                                        <div id="prev-google-mobile-title" style="color:#1a0dab;font-size:18px;line-height:1.3;margin-bottom:4px;">{{ old('title', $meta->title) ?: 'Título de la página' }}</div>
                                        <div id="prev-google-mobile-desc" style="color:#4d5156;font-size:13px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">{{ old('description', $meta->description) ?: 'Descripción de la página que aparece en los resultados.' }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Counters --}}
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold">Título SEO</small>
                                    <small id="counter-title-badge" class="badge bg-secondary">0 car.</small>
                                </div>
                                <div class="progress mb-1" style="height:6px;border-radius:3px;">
                                    <div id="bar-title" class="progress-bar" role="progressbar" style="width:0%;transition:width .2s,background .2s;"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">0</small>
                                    <small class="text-warning">30</small>
                                    <small class="text-success">60</small>
                                    <small class="text-danger">70+</small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                    <small class="fw-semibold">Meta descripción</small>
                                    <small id="counter-desc-badge" class="badge bg-secondary">0 car.</small>
                                </div>
                                <div class="progress mb-1" style="height:6px;border-radius:3px;">
                                    <div id="bar-desc" class="progress-bar" role="progressbar" style="width:0%;transition:width .2s,background .2s;"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">0</small>
                                    <small class="text-warning">70</small>
                                    <small class="text-success">160</small>
                                    <small class="text-danger">320+</small>
                                </div>
                            </div>
                        </div>

                        {{-- Facebook / OG --}}
                        <div class="tab-pane fade" id="pane-facebook" role="tabpanel">
                            <div class="border rounded overflow-hidden" style="background:#f0f2f5;font-family:Helvetica,Arial,sans-serif;">
                                {{-- Image area --}}
                                <div id="prev-fb-image-wrap"
                                     style="background:#e4e6ea;height:200px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                                    <i class="fas fa-image fa-3x text-muted" id="prev-fb-img-placeholder"></i>
                                    <img id="prev-fb-img" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                                </div>
                                {{-- Meta bar --}}
                                <div class="px-3 py-2" style="background:#f0f2f5;border-top:1px solid #cdd0d4;">
                                    <div style="font-size:11px;color:#65676b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;" id="prev-fb-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'sitio.com' }}</div>
                                    <div style="font-size:16px;font-weight:600;color:#050505;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="prev-fb-title">
                                        {{ old('og_title', $meta->og_title) ?: old('title', $meta->title) ?: 'Título Open Graph' }}
                                    </div>
                                    <div style="font-size:14px;color:#65676b;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;" id="prev-fb-desc">
                                        {{ old('og_description', $meta->og_description) ?: old('description', $meta->description) ?: 'Descripción que aparece al compartir en Facebook y LinkedIn.' }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <i class="fas fa-info-circle text-muted"></i>
                                <small class="text-muted">Imagen recomendada: <strong>1200×630 px</strong>. Usa el campo OG Imagen para personalizar.</small>
                            </div>
                        </div>

                        {{-- Twitter/X --}}
                        <div class="tab-pane fade" id="pane-twitter" role="tabpanel">
                            <div id="prev-tw-card-large" class="border rounded overflow-hidden" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
                                <div id="prev-tw-image-wrap"
                                     style="background:#2f3336;height:180px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                                    <i class="fas fa-image fa-3x" style="color:#657786;" id="prev-tw-img-placeholder"></i>
                                    <img id="prev-tw-img" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                                </div>
                                <div class="p-3" style="background:#15202b;">
                                    <div style="font-size:15px;font-weight:700;color:#e7e9ea;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="prev-tw-title">
                                        {{ old('twitter_title', $meta->twitter_title) ?: old('title', $meta->title) ?: 'Título Twitter Card' }}
                                    </div>
                                    <div style="font-size:14px;color:#8899a6;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-top:4px;" id="prev-tw-desc">
                                        {{ old('twitter_description', $meta->twitter_description) ?: old('description', $meta->description) ?: 'Descripción que aparece al compartir en Twitter/X.' }}
                                    </div>
                                    <div style="font-size:13px;color:#657786;margin-top:6px;" id="prev-tw-domain">
                                        <i class="fas fa-link me-1"></i>{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'sitio.com' }}
                                    </div>
                                </div>
                            </div>
                            <div id="prev-tw-card-summary" class="d-none">
                                <div class="border rounded d-flex overflow-hidden" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#15202b;">
                                    <div style="width:100px;min-height:100px;background:#2f3336;flex-shrink:0;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                                        <i class="fas fa-image text-muted" id="prev-tw-summary-img-placeholder"></i>
                                        <img id="prev-tw-summary-img" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                                    </div>
                                    <div class="p-2 flex-fill">
                                        <div style="font-size:14px;font-weight:700;color:#e7e9ea;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="prev-tw-sum-title">{{ old('twitter_title', $meta->twitter_title) ?: old('title', $meta->title) ?: 'Título' }}</div>
                                        <div style="font-size:12px;color:#8899a6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;" id="prev-tw-sum-desc">{{ old('twitter_description', $meta->twitter_description) ?: old('description', $meta->description) ?: 'Descripción' }}</div>
                                        <div style="font-size:11px;color:#657786;margin-top:4px;" id="prev-tw-sum-domain">
                                            <i class="fas fa-link me-1"></i>{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'sitio.com' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted" id="tw-card-type-label">
                                    Tipo activo: <strong>{{ old('twitter_card', $meta->twitter_card) ?: 'summary' }}</strong>
                                </small>
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div class="tab-pane fade" id="pane-whatsapp" role="tabpanel">
                            <div style="background:#0b141a;padding:12px;border-radius:8px;">
                                <div class="rounded overflow-hidden" style="background:#1f2c34;max-width:320px;">
                                    <div id="prev-wa-image-wrap" style="background:#2a3942;height:170px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                                        <i class="fas fa-image fa-2x" style="color:#8696a0;" id="prev-wa-img-placeholder"></i>
                                        <img id="prev-wa-img" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;">
                                    </div>
                                    <div class="p-2">
                                        <div style="font-size:14px;font-weight:600;color:#e9edef;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="prev-wa-title">
                                            {{ old('og_title', $meta->og_title) ?: old('title', $meta->title) ?: 'Título' }}
                                        </div>
                                        <div style="font-size:13px;color:#8696a0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;" id="prev-wa-desc">
                                            {{ old('og_description', $meta->og_description) ?: old('description', $meta->description) ?: 'Descripción del enlace compartido en WhatsApp.' }}
                                        </div>
                                        <div style="font-size:11px;color:#00a884;margin-top:4px;" id="prev-wa-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'sitio.com' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    @if($meta->canonical_url)
                    <hr class="my-3">
                    <p class="small text-muted mb-2 fw-semibold">Validar en herramientas externas:</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="https://developers.facebook.com/tools/debug/?q={{ urlencode($meta->canonical_url) }}"
                           target="_blank" class="btn btn-sm btn-outline-secondary">OG Debugger</a>
                        <a href="https://www.linkedin.com/post-inspector/inspect/{{ urlencode($meta->canonical_url) }}"
                           target="_blank" class="btn btn-sm btn-outline-secondary">LinkedIn</a>
                        <a href="https://search.google.com/test/rich-results?url={{ urlencode($meta->canonical_url) }}"
                           target="_blank" class="btn btn-sm btn-outline-secondary">Rich Results</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Recomendaciones</h6>
                </div>
                <div class="card-body">
                    <ul class="seo-tips-list mb-0 small">
                        <li>Título entre 50-60 caracteres para mejor visibilidad</li>
                        <li>Descripción entre 120-160 caracteres ideal</li>
                        <li>Usa palabras clave naturalmente en el texto</li>
                        <li>Incluye imagen OG de al menos 1200×630 px</li>
                        <li>El canonical URL evita contenido duplicado</li>
                        <li>Usa noindex solo en páginas que no quieras en Google</li>
                    </ul>
                </div>
            </div>

            {{-- SEO Checklist Card --}}
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Checklist SEO</h6>
                </div>
                <div class="card-body">
                    <div id="checklist-container">
                        <p class="text-muted small mb-0">Haz clic en "Verificar" para comprobar el estado SEO de esta página.</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-outline-primary w-100" id="btn-load-checklist">
                        Verificar
                    </button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Evolución del score SEO</h6>
                </div>
                <div class="card-body">
                    <canvas id="scoreSparkline" height="60" style="max-width:100%;"></canvas>
                    <div id="sparkline-empty" class="text-muted small d-none mb-0">Sin historial de auditorías aún.</div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header p-0">
                    <button class="btn btn-link w-100 p-3 text-decoration-none text-dark fw-bold d-flex align-items-center justify-content-between"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#audit-history-collapse"
                            aria-expanded="false">
                        <span>Historial de auditorías</span>
                        <i class="fas fa-chevron-down text-muted small collapse-icon" style="transition:transform .2s;"></i>
                    </button>
                </div>
                <div id="audit-history-collapse" class="collapse">
                    <div class="card-body" id="audit-history-container">
                        <div class="text-center py-2">
                            <button class="btn btn-outline-secondary btn-sm" id="load-audit-history">
                                Cargar historial
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3 seo-danger-card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold seo-danger-title">Zona de peligro</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-0">Eliminar estos metadatos SEO no se puede deshacer. El modelo asociado no se verá afectado.</p>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger w-100 delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal">
                        Eliminar meta SEO
                    </button>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- widget-content --}}

    @include('core::components.delete')
@endsection

@push('css')
<style>
.seo-img-card {
    position: relative;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    overflow: hidden;
    background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.seo-img-card:hover { border-color: #b10100; box-shadow: 0 2px 8px rgba(177,1,0,.12); }
.seo-img-card__preview {
    position: relative;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
    background-image:
        linear-gradient(45deg, #eef2f6 25%, transparent 25%, transparent 75%, #eef2f6 75%),
        linear-gradient(45deg, #eef2f6 25%, transparent 25%, transparent 75%, #eef2f6 75%);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
}
.seo-img-card__preview img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}
.seo-img-card__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    font-size: .85rem;
    pointer-events: none;
    text-align: center;
}
.seo-img-card__empty i { font-size: 1.75rem; margin-bottom: .35rem; }
.seo-img-card__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .65);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity .15s ease;
    font-weight: 600;
    pointer-events: none;
}
.seo-img-card.has-image .seo-img-card__preview:hover .seo-img-card__overlay { opacity: 1; }
.seo-img-card__body {
    padding: .625rem .75rem;
    background: #fff;
}
.seo-img-clear-btn {
    color: #b10100 !important;
    padding: .25rem .5rem;
}
.seo-img-clear-btn:hover { color: #7f0100 !important; }

/* Tips list — red dot marker, no duplicate checks */
.seo-tips-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
}
.seo-tips-list li {
    position: relative;
    padding: .35rem 0 .35rem 1.25rem;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

/* Info note (ex "Todo el SEO de esta página...") */
.seo-info-note {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #7f1d1d;
    padding: .6rem .75rem;
    border-radius: .5rem;
    font-size: .8rem;
    line-height: 1.4;
}

/* Platform tabs (Vista previa en tiempo real) — icon-only circular */
.seo-platform-tabs .nav-link {
    padding: .5rem 0;
    border-radius: .5rem;
    color: #6b7280;
    font-size: 1.05rem;
    transition: all .15s ease;
}
.seo-platform-tabs .nav-link:hover {
    background: #f9fafb;
    color: #374151;
}
.seo-platform-tabs .nav-link.active {
    background: #b10100;
    color: #fff;
}
.seo-platform-tabs .nav-link i { line-height: 1; }

/* SERP preview tabs */
.seo-preview-tabs .btn { padding: .3rem .55rem; min-width: 34px; }
.seo-preview-tabs .btn.active {
    background: #b10100;
    color: #fff;
    border-color: #b10100;
}

/* Card header/footer consistency */
.card > .card-header {
    background: #fff;
    border-bottom: 1px solid #f3f4f6;
    padding: .75rem 1rem;
}
.card > .card-footer {
    background: #fff;
    border-top: 1px solid #f3f4f6;
    padding: .75rem 1rem;
}

/* Zona de peligro */
.seo-danger-card {
    border: 1px solid #fecaca;
}
.seo-danger-card > .card-header {
    background: #fef2f2;
    border-bottom-color: #fecaca;
}
.seo-danger-title { color: #b10100; }
</style>
@endpush

@push('scripts')
<script>
// Load Chart.js on demand for score sparkline
(function() {
    if (typeof Chart === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';
        document.head.appendChild(s);
    }
})();

$(document).ready(function() {

    // ── Helpers ───────────────────────────────────────────────────────

    function extractDomain(url) {
        try { return new URL(url).hostname; } catch (e) { return url; }
    }

    function isValidUrl(url) {
        return /^https?:\/\/.+/.test(url);
    }

    function setPreviewImage(imgId, placeholderId, url) {
        if (isValidUrl(url)) {
            $('#' + imgId).attr('src', url).show();
            $('#' + placeholderId).hide();
        } else {
            $('#' + imgId).hide().attr('src', '');
            $('#' + placeholderId).show();
        }
    }

    // ── Device toggle ─────────────────────────────────────────────────
    $('#preview-device-toggle button').on('click', function () {
        $('#preview-device-toggle button').removeClass('active');
        $(this).addClass('active');
        var device = $(this).data('device');
        if (device === 'mobile') {
            $('#google-desktop-preview').addClass('d-none');
            $('#google-mobile-preview').removeClass('d-none');
        } else {
            $('#google-mobile-preview').addClass('d-none');
            $('#google-desktop-preview').removeClass('d-none');
        }
    });

    // ── Title / desc counters ─────────────────────────────────────────

    function updateTitleCounter(val) {
        var len = val.length;
        var pct, cls;
        if (len === 0)        { pct = 0;   cls = 'bg-secondary'; }
        else if (len < 30)    { pct = Math.round(len / 60 * 100); cls = 'bg-danger'; }
        else if (len <= 60)   { pct = Math.round(len / 60 * 100); cls = 'bg-success'; }
        else if (len <= 70)   { pct = 100; cls = 'bg-warning'; }
        else                  { pct = 100; cls = 'bg-danger'; }
        $('#bar-title').css('width', pct + '%').attr('class', 'progress-bar ' + cls);
        $('#title-inline-bar').css('width', pct + '%').attr('class', 'progress-bar ' + cls);
        var label = len === 0 ? '0 car.' : len + ' car.' + (len < 30 ? ' (muy corto)' : (len > 70 ? ' (muy largo)' : ''));
        $('#counter-title-badge').text(label).attr('class', 'badge ' + cls.replace('bg-', 'text-bg-'));
        $('#title-chars').text(len);
    }

    function updateDescCounter(val) {
        var len = val.length;
        var pct, cls;
        if (len === 0)        { pct = 0;   cls = 'bg-secondary'; }
        else if (len < 70)    { pct = Math.round(len / 160 * 100); cls = 'bg-danger'; }
        else if (len <= 160)  { pct = Math.round(len / 160 * 100); cls = 'bg-success'; }
        else if (len <= 320)  { pct = 100; cls = 'bg-warning'; }
        else                  { pct = 100; cls = 'bg-danger'; }
        $('#bar-desc').css('width', pct + '%').attr('class', 'progress-bar ' + cls);
        $('#desc-inline-bar').css('width', pct + '%').attr('class', 'progress-bar ' + cls);
        var label = len === 0 ? '0 car.' : len + ' car.' + (len < 70 ? ' (muy corto)' : (len > 320 ? ' (muy largo)' : ''));
        $('#counter-desc-badge').text(label).attr('class', 'badge ' + cls.replace('bg-', 'text-bg-'));
        $('#desc-chars').text(len);
    }

    // ── Twitter card type ─────────────────────────────────────────────

    function applyTwitterCardType(type) {
        $('#tw-card-type-label strong').text(type || 'summary');
        if (type === 'summary_large_image') {
            $('#prev-tw-card-large').removeClass('d-none');
            $('#prev-tw-card-summary').addClass('d-none');
        } else {
            $('#prev-tw-card-large').addClass('d-none');
            $('#prev-tw-card-summary').removeClass('d-none');
        }
    }

    $('[name="twitter_card"]').on('change', function () {
        applyTwitterCardType($(this).val());
    });
    applyTwitterCardType($('[name="twitter_card"]').val());

    // ── Pixel-width counters (inline under input fields) ──────────────

    function getTextWidth(text, font) {
        var c = document.createElement('canvas');
        c.getContext('2d').font = font;
        return c.getContext('2d').measureText(text).width;
    }

    function updatePixelCounters() {
        var title = $('#title').val() || '';
        var desc  = $('#description').val() || '';
        var titlePx = Math.round(getTextWidth(title, 'bold 20px Arial'));
        var descPx  = Math.round(getTextWidth(desc, '14px Arial'));
        $('#title-chars').text(title.length);
        $('#desc-chars').text(desc.length);
        var tb = $('#title-pixels');
        tb.text(titlePx + 'px / ~600px').removeClass('bg-success bg-warning bg-danger');
        tb.addClass(titlePx <= 500 ? 'bg-success' : (titlePx <= 600 ? 'bg-warning' : 'bg-danger'));
        var db = $('#desc-pixels');
        db.text(descPx + 'px / ~920px').removeClass('bg-success bg-warning bg-danger');
        db.addClass(descPx <= 750 ? 'bg-success' : (descPx <= 920 ? 'bg-warning' : 'bg-danger'));
    }

    $('#title, #description').on('input', updatePixelCounters);
    updatePixelCounters();

    // ── Image preview (small thumbnails under input) ──────────────────
    $('[data-preview]').on('input', function () {
        var url = $(this).val();
        var $p  = $($(this).data('preview'));
        $p.html(isValidUrl(url) ? '<img src="' + url + '" class="img-fluid rounded mt-2" style="max-height:80px;">' : '');
    }).trigger('input');

    // ── Main live preview refresh ─────────────────────────────────────

    function refreshPreview() {
        var title     = $('#title').val();
        var desc      = $('#description').val();
        var ogTitle   = $('[name="og_title"]').val();
        var ogDesc    = $('[name="og_description"]').val();
        var ogImage   = $('[name="og_image"]').val();
        var twTitle   = $('[name="twitter_title"]').val();
        var twDesc    = $('[name="twitter_description"]').val();
        var twImage   = $('[name="twitter_image"]').val();
        var canonical = $('[name="canonical_url"]').val();

        var fallbackTitle = title || 'Título de la página';
        var fallbackDesc  = desc  || 'Descripción de la página que aparece en los resultados de búsqueda.';

        // Google desktop
        $('#prev-google-title').text(fallbackTitle);
        $('#prev-google-desc').text(fallbackDesc);
        if (canonical) {
            $('#prev-google-url-crumb').text(canonical);
            $('#prev-google-mobile-crumb').text(canonical);
        }
        // Google mobile
        $('#prev-google-mobile-title').text(fallbackTitle);
        $('#prev-google-mobile-desc').text(fallbackDesc);

        // Counters
        updateTitleCounter(title);
        updateDescCounter(desc);

        // Facebook
        var fbTitle = ogTitle || title || 'Título Open Graph';
        var fbDesc  = ogDesc  || desc  || 'Descripción Open Graph';
        var fbImg   = ogImage || twImage;
        $('#prev-fb-title').text(fbTitle);
        $('#prev-fb-desc').text(fbDesc);
        setPreviewImage('prev-fb-img', 'prev-fb-img-placeholder', fbImg);
        if (canonical) { $('#prev-fb-domain').text(extractDomain(canonical)); }

        // Twitter
        var twFinalTitle = twTitle || title || 'Título Twitter Card';
        var twFinalDesc  = twDesc  || desc  || 'Descripción Twitter/X';
        var twFinalImg   = twImage || ogImage;
        $('#prev-tw-title, #prev-tw-sum-title').text(twFinalTitle);
        $('#prev-tw-desc, #prev-tw-sum-desc').text(twFinalDesc);
        setPreviewImage('prev-tw-img',         'prev-tw-img-placeholder',         twFinalImg);
        setPreviewImage('prev-tw-summary-img', 'prev-tw-summary-img-placeholder', twFinalImg);
        if (canonical) {
            var dom = extractDomain(canonical);
            $('#prev-tw-domain, #prev-tw-sum-domain').html('<i class="fas fa-link me-1"></i>' + dom);
        }

        // WhatsApp
        var waTitle = ogTitle || title || 'Título';
        var waDesc  = ogDesc  || desc  || 'Descripción del enlace compartido en WhatsApp.';
        var waImg   = ogImage || twImage;
        $('#prev-wa-title').text(waTitle);
        $('#prev-wa-desc').text(waDesc);
        setPreviewImage('prev-wa-img', 'prev-wa-img-placeholder', waImg);
        if (canonical) { $('#prev-wa-domain').text(extractDomain(canonical)); }
    }

    $('[name="title"],[name="description"],[name="og_title"],[name="og_description"],' +
      '[name="og_image"],[name="twitter_title"],[name="twitter_description"],' +
      '[name="twitter_image"],[name="canonical_url"]').on('input change', refreshPreview);

    refreshPreview();
    // Run pixel counters last so they always win over refreshPreview's counter update
    updatePixelCounters();

    // Delete modal
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text('Eliminar meta SEO');
        $('#delete-form').attr('action', '{{ route("settings.seo.metas.destroy", $meta) }}');
    });

    // ── Audit history loader ──────────────────────────────────────────

    var auditHistoryLoaded = false;

    $('#audit-history-collapse').on('show.bs.collapse', function () {
        if (!auditHistoryLoaded) {
            $('#load-audit-history').trigger('click');
        }
    });

    $('#load-audit-history').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');

        $.get('{{ route("settings.seo.audit.history.meta.json", $meta) }}', function (data) {
            auditHistoryLoaded = true;

            if (!data.length) {
                $('#audit-history-container').html('<p class="text-muted text-center py-3 mb-0">No hay auditorias registradas para este meta.</p>');
                return;
            }

            var gradeColor = { A: '#13C672', B: '#90bb13', C: '#FEC90F', D: '#FA896B', F: '#dc3545' };
            var rows = data.map(function (row) {
                var color = gradeColor[row.seo_grade] || '#6c757d';
                var issues = Array.isArray(row.issues) ? row.issues.length : 0;
                return '<tr>' +
                    '<td><small>' + row.audited_at + '</small></td>' +
                    '<td class="text-center"><span class="badge rounded-pill text-white fw-bold" style="background:' + color + ';">' + (row.seo_score ?? '-') + '</span></td>' +
                    '<td class="text-center"><span class="badge text-white fw-bold" style="background:' + color + ';">' + (row.seo_grade ?? '-') + '</span></td>' +
                    '<td class="text-center"><small class="text-muted">' + issues + '</small></td>' +
                    '</tr>';
            }).join('');

            $('#audit-history-container').html(
                '<div class="table-responsive">' +
                '<table class="table table-sm table-hover align-middle mb-0">' +
                '<thead class="table-light"><tr><th>Fecha</th><th class="text-center">Score</th><th class="text-center">Grado</th><th class="text-center">N&ordm; Issues</th></tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
                '</table></div>'
            );
        }).fail(function () {
            $('#audit-history-container').html('<p class="text-danger text-center py-3 mb-0">Error al cargar el historial.</p>');
            $btn.prop('disabled', false).html('<i class="fas fa-history me-1"></i> Cargar historial');
        });
    });

    // ── Auto-generate button ──────────────────────────────────────────

    $('#auto-generate-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generando...');

        $.ajax({
            url: '{{ route("settings.seo.orphans.generate") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                model_class: $btn.data('model-class'),
                model_id:    $btn.data('model-id')
            },
            success: function() {
                toastr.success('Metadatos generados correctamente');
                setTimeout(function() { location.reload(); }, 1500);
            },
            error: function() {
                toastr.error('No se pudo generar los metadatos');
                $btn.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Auto-generar desde contenido');
            }
        });
    });

    // ── SEO Checklist ─────────────────────────────────────────────────

    $('#btn-load-checklist').on('click', function () {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.get('{{ route("settings.seo.metas.checklist", $meta) }}', function (data) {
            var statusIcon = {
                pass:    '<i class="fas fa-check-circle text-success"></i>',
                warning: '<i class="fas fa-exclamation-triangle text-warning"></i>',
                fail:    '<i class="fas fa-times-circle text-danger"></i>'
            };
            var barClass = data.score >= 70 ? 'bg-success' : (data.score >= 50 ? 'bg-warning' : 'bg-danger');

            var html = '<div class="mb-2">'
                + '<div class="progress" style="height:8px;">'
                + '<div class="progress-bar ' + barClass + '" style="width:' + data.score + '%"></div>'
                + '</div>'
                + '<small class="text-muted">' + data.pass_count + '/' + data.total + ' verificaciones pasadas</small>'
                + '</div>';

            data.items.forEach(function (item) {
                var colorClass = item.status === 'pass' ? 'text-success' : (item.status === 'warning' ? 'text-warning' : 'text-danger');
                html += '<div class="d-flex align-items-start gap-2 py-1 border-bottom">'
                    + '<span class="flex-shrink-0 mt-1">' + statusIcon[item.status] + '</span>'
                    + '<div>'
                    + '<div class="small fw-semibold">' + item.label + '</div>'
                    + '<div class="small ' + colorClass + '">' + item.message + '</div>'
                    + '</div>'
                    + '</div>';
            });

            if (data.ready) {
                html += '<div class="alert alert-success mt-2 p-2 small mb-0"><i class="fas fa-check-circle me-1"></i> Página lista para publicar</div>';
            } else {
                html += '<div class="alert alert-warning mt-2 p-2 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Revisa los elementos pendientes antes de publicar</div>';
            }

            $('#checklist-container').html(html);
        }).fail(function () {
            toastr.error('Error al cargar el checklist SEO.');
        }).always(function () {
            $('#btn-load-checklist').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Verificar');
        });
    });

    // ── Generar imagen OG ─────────────────────────────────────────────

    $('.og-template-btn').on('click', function () {
        $('.og-template-btn').removeClass('active');
        $(this).addClass('active');
        $('#btn-generate-og').data('template', $(this).data('template'));
    });

    $('#btn-generate-og').on('click', function () {
        var metaId = $(this).data('meta-id');
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando...');

        $.post('{{ route("settings.seo.metas.generate-og-image", $meta) }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            text: $('#og_title').val() || $('#title').val() || '',
            template: $('#btn-generate-og').data('template') || 'default'
        }, function (data) {
            $('#og_image').val(data.url).trigger('input');
            toastr.success(data.message);
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Error al generar imagen OG.');
        }).always(function () {
            $('#btn-generate-og').prop('disabled', false).html('<i class="fas fa-image"></i> Generar imagen OG automática');
        });
    });

    // ── Auto-generar URL canónica ─────────────────────────────────────
    $('#btn-generate-canonical').on('click', function () {
        var metaId = $(this).data('meta-id');
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('{{ route("settings.seo.metas.generate-canonical", $meta) }}', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (data) {
            $('#canonical_url').val(data.canonical_url);
            toastr.success(data.message);
            $('#btn-generate-canonical').remove();
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON?.error || 'Error al generar la URL canónica.');
            $('#btn-generate-canonical').prop('disabled', false).html('<i class="fas fa-magic"></i> Auto-generar');
        });
    });

    // ── Auto-generar canónica en carga si está vacía ───────────────────
    if ($('#canonical_url').val() === '' && $('#btn-generate-canonical').length) {
        $('#btn-generate-canonical').trigger('click');
    }

    // ── Copiar OG → Twitter ───────────────────────────────────────────
    $('#btn-copy-og-to-twitter').on('click', function () {
        var ogTitle = $('[name="og_title"]').val() || $('[name="title"]').val();
        var ogDesc  = $('[name="og_description"]').val() || $('[name="description"]').val();
        var ogImg   = $('[name="og_image"]').val();
        if (ogTitle) $('[name="twitter_title"]').val(ogTitle).trigger('input');
        if (ogDesc)  $('[name="twitter_description"]').val(ogDesc).trigger('input');
        if (ogImg)   $('[name="twitter_image"]').val(ogImg).trigger('input change');
        toastr.success('Campos de Twitter copiados desde Open Graph');
    });

    // ── Keyword suggestions ───────────────────────────────────────────
    var kwTimeout;

    $('#target_keyword').on('input', function () {
        clearTimeout(kwTimeout);
        var q = $(this).val();
        if (q.length < 3) {
            $('#keyword-suggestions').empty().hide();
            return;
        }
        kwTimeout = setTimeout(function () {
            $.get('{{ route("settings.seo.metas.keyword-suggestions") }}', { q: q }, function (data) {
                var $list = $('#keyword-suggestions').empty();
                if (!data.suggestions.length && !data.variations.length) {
                    $list.hide();
                    return;
                }
                data.suggestions.forEach(function (s) {
                    $list.append(
                        '<button type="button" class="btn btn-sm btn-outline-success me-1 mb-1 kw-suggestion" data-value="' + $('<span>').text(s.keyword).html() + '">'
                        + $('<span>').text(s.keyword).html()
                        + ' <small class="ms-1 text-muted">' + s.clicks + ' clics</small>'
                        + '</button>'
                    );
                });
                data.variations.forEach(function (v) {
                    $list.append(
                        '<button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 kw-suggestion" data-value="' + $('<span>').text(v).html() + '">'
                        + $('<span>').text(v).html()
                        + '</button>'
                    );
                });
                $list.show();
            });
        }, 400);
    });

    $(document).on('click', '.kw-suggestion', function () {
        $('#target_keyword').val($(this).data('value'));
        $('#keyword-suggestions').hide();
    });

    // ── DeepL translation ─────────────────────────────────────────────

    $('#btn-deepl-translate').on('click', function() {
        var fields = $('.deepl-field-check:checked').map(function() { return this.value; }).get();
        var targetLang = $('#deepl-target-lang').val();
        var metaId = $(this).data('meta-id');

        if (fields.length === 0) { toastr.warning('Selecciona al menos un campo'); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Traduciendo...');

        $.post('{{ route("settings.seo.metas.translate", $meta) }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            target_lang: targetLang,
            fields: fields
        }, function(data) {
            var html = '';
            $.each(data.translations, function(field, text) {
                html += '<div class="mb-2 p-2 border rounded">' +
                    '<div class="d-flex justify-content-between align-items-center mb-1">' +
                    '<small class="fw-semibold text-capitalize">' + field.replace('_', ' ') + '</small>' +
                    '<button type="button" class="btn btn-sm btn-success apply-translation" ' +
                    'data-field="' + field + '" data-value="' + $('<span>').text(text).html().replace(/"/g, '&quot;') + '">' +
                    '<i class="fas fa-check me-1"></i> Aplicar</button>' +
                    '</div>' +
                    '<div class="small text-muted">' + $('<span>').text(text).html() + '</div>' +
                    '</div>';
            });
            $('#deepl-translations').html(html);
            $('#deepl-results').removeClass('d-none');
            toastr.success(data.message);
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Error al traducir.');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-language me-1"></i> Traducir');
        });
    });

    $(document).on('click', '.apply-translation', function() {
        var field = $(this).data('field');
        var value = $(this).data('value');
        $('[name="' + field + '"]').val(value).trigger('input');
        toastr.info('Campo "' + field + '" actualizado. Guarda el formulario para conservar los cambios.');
    });

    // ── Copy to clipboard ─────────────────────────────────────────────
    $(document).on('click', '.copy-field-btn', function () {
        var $target = $($(this).data('target'));
        var text = $target.val();
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        }).catch(function () {
            // Fallback for non-HTTPS
            var $tmp = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            toastr.success('Copiado al portapapeles');
        });
    });

    // ── Ctrl+S to save ────────────────────────────────────────────────
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('form[action*="metas"]').trigger('submit');
        }
    });

    // ── Score SEO badge (sidebar) ─────────────────────────────────────
    $.get('{{ route("settings.seo.metas.score-history", $meta) }}', function (data) {
        if (!data || !data.length) { return; }
        var last = data[data.length - 1];
        var score = last.score;
        var grade = last.grade || '';
        var color = score >= 80 ? '#13C672' : (score >= 60 ? '#90bb13' : (score >= 40 ? '#FEC90F' : '#FA896B'));
        $('#score-summary-card').css('display', '').show();
        $('#score-summary-label').text('Grado ' + grade);
        $('#score-summary-circle')
            .css({ 'border-color': color, 'color': color })
            .text(score);
    });

    // ── Historial collapse chevron ────────────────────────────────────
    $('#audit-history-collapse').on('show.bs.collapse hide.bs.collapse', function (e) {
        var $icon = $(this).closest('.card').find('.collapse-icon');
        $icon.css('transform', e.type === 'show' ? 'rotate(180deg)' : 'rotate(0deg)');
    });

    // ── Sticky save bar ───────────────────────────────────────────────
    (function () {
        var $bar = $('<div id="sticky-save-bar" class="d-none" style="position:fixed;bottom:0;left:0;right:0;z-index:1040;background:#fff;border-top:1px solid #dee2e6;padding:10px 16px;box-shadow:0 -2px 8px rgba(0,0,0,.08);">' +
            '<div class="d-flex align-items-center justify-content-between gap-2" style="max-width:900px;margin:0 auto;">' +
            '<small class="text-muted"><i class="fas fa-info-circle me-1"></i> Tienes cambios sin guardar</small>' +
            '<button type="button" id="sticky-save-btn" class="btn btn-primary btn-sm px-4">' +
            '<i class="fas fa-save me-1"></i> Guardar cambios</button>' +
            '</div></div>');
        $('body').append($bar);

        var $form = $('form[action*="metas"]');
        var isDirty = false;

        $form.on('input change', ':input', function () {
            if (!isDirty) {
                isDirty = true;
                $bar.removeClass('d-none');
            }
        });

        $('#sticky-save-btn').on('click', function () {
            $form.trigger('submit');
        });

        $form.on('submit', function () {
            $bar.addClass('d-none');
            isDirty = false;
        });
    })();

    // ── Score sparkline ───────────────────────────────────────────────

    function renderSparkline(data) {
        if (!data || data.length === 0) {
            $('#sparkline-empty').removeClass('d-none');
            $('#scoreSparkline').hide();
            return;
        }
        new Chart(document.getElementById('scoreSparkline'), {
            type: 'line',
            data: {
                labels: data.map(function(d) { return d.date; }),
                datasets: [{
                    data: data.map(function(d) { return d.score; }),
                    borderColor: '#90bb13',
                    backgroundColor: 'rgba(144,187,19,0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return 'Score: ' + ctx.parsed.y; } } }
                },
                scales: {
                    x: { display: false },
                    y: { display: false, min: 0, max: 100 }
                }
            }
        });
    }

    $.get('{{ route("settings.seo.metas.score-history", $meta) }}', function(data) {
        var waitForChart = function() {
            if (typeof Chart !== 'undefined') {
                renderSparkline(data);
            } else {
                setTimeout(waitForChart, 100);
            }
        };
        waitForChart();
    });
});

// ============================================================
// SEO image cards (OG + Twitter) — MediaPicker integration
// ============================================================
(function () {
    function setImage(target, url) {
        var $card = $('#seo-img-card-' + target);
        var $img = $('#seo-img-' + target);
        var inputName = target === 'og' ? 'og_image' : 'twitter_image';
        var $input = $('#' + inputName);
        var $empty = $card.find('.seo-img-card__empty');
        var $clear = $card.find('.seo-img-clear-btn');

        $input.val(url).trigger('change');

        if (url) {
            $img.attr('src', url).removeClass('d-none');
            $empty.addClass('d-none');
            $card.addClass('has-image').removeClass('is-empty');
            $clear.removeClass('d-none');
        } else {
            $img.attr('src', '').addClass('d-none');
            $empty.removeClass('d-none');
            $card.removeClass('has-image').addClass('is-empty');
            $clear.addClass('d-none');
        }
    }

    $(document).on('click', '.seo-img-card__preview', function () {
        var $card = $(this).closest('.seo-img-card');
        var key = $card.data('key');
        var label = $card.data('label');
        var target = $card.attr('id').replace('seo-img-card-', '');

        if (typeof window.MediaPicker !== 'undefined' && window.MediaPicker.open) {
            window.MediaPicker.open({
                urls: {
                    list:   '{{ route("media.list") }}',
                    upload: '{{ route("media.files.upload") }}',
                    base:   '{{ url("media") }}',
                },
                filter: 'image',
                title: 'Elegir imagen — ' + label,
                onSelect: function (fullUrl) {
                    setImage(target, fullUrl);
                }
            });
        } else {
            var manual = prompt('Pega la URL de la imagen:', $('#' + key).val() || '');
            if (manual !== null) setImage(target, manual);
        }
    });

    $(document).on('click', '.seo-img-clear-btn', function (e) {
        e.stopPropagation();
        setImage($(this).data('target'), '');
    });
})();
</script>

@endpush
