@extends('layouts.theme')

@section('title', 'Contenido IA - ' . ($content->generated_name ?? $content->model_id ?? $content->uid))

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
    <style>
        .shop-preview { line-height: 1.6; font-size: 0.95rem; max-height: 600px; overflow-y: auto; }
        .shop-preview h1, .shop-preview h2, .shop-preview h3, .shop-preview h4 { font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .shop-preview h2 { font-size: 1.25rem; }
        .shop-preview h3 { font-size: 1.1rem; }
        .shop-preview p { margin-bottom: 0.75rem; }
        .shop-preview ul, .shop-preview ol { margin-bottom: 0.75rem; padding-left: 1.5rem; }
        .shop-preview ul li, .shop-preview ol li { margin-bottom: 0.25rem; }
        .shop-preview table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
        .shop-preview th, .shop-preview td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .shop-preview th { background: #f8f9fa; font-weight: 600; }
        .shop-preview a { color: #0c7a64; }
        .shop-preview blockquote { border-left: 3px solid #90bb13; padding-left: 0.75rem; color: #5a6a85; margin: 0.75rem 0; }
        .shop-preview code { background: rgba(0,0,0,0.06); padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
        .btn-validate-green { background-color: #7cb01a; border-color: #7cb01a; border-radius: 8px; }
        .btn-validate-green:hover, .btn-validate-green:focus { background-color: #6a9817; border-color: #6a9817; }
        .btn-action-black { background-color: #111; border-color: #111; border-radius: 8px; }
        .btn-action-black:hover, .btn-action-black:focus { background-color: #2a2a2a; border-color: #2a2a2a; }
        .btn-action-red { border-radius: 8px; }
        .btn-action-blue { border-radius: 8px; }
        /* Editor enriquecido long_description */
        #longDescEditorTabs .nav-link { padding: 0.25rem 0.9rem; font-size: 0.85rem; }
        #longDescQuillWrap .ql-container { font-size: 0.95rem; min-height: 220px; max-height: 420px; overflow-y: auto; }
        #longDescCharCount { font-size: 0.78rem; color: #6c757d; }
    </style>
@endpush

@php
    $statusMap = [
        'pending_generation'       => ['label' => 'Pendiente',        'color' => 'warning'],
        'generating'               => ['label' => 'Generando',         'color' => 'info'],
        'pending_validation'       => ['label' => 'Por validar',       'color' => 'primary'],
        'in_review'                => ['label' => 'En revisión',       'color' => 'primary'],
        'needs_revision'           => ['label' => 'Necesita revisión', 'color' => 'warning'],
        'validated'                => ['label' => 'Validado',          'color' => 'success'],
        'published'                => ['label' => 'Publicado',         'color' => 'success'],
        'published_hidden'         => ['label' => 'No publicado',      'color' => 'secondary'],
        'rejected'                 => ['label' => 'Rechazado',         'color' => 'danger'],
        'error_insufficient_info'  => ['label' => 'Error: sin datos',  'color' => 'danger'],
        'error_source_unavailable' => ['label' => 'Error: fuente',     'color' => 'danger'],
        'error_generation_failed'  => ['label' => 'Error: generación', 'color' => 'danger'],
    ];
    $s             = $statusMap[$content->status] ?? ['label' => ucfirst($content->status), 'color' => 'secondary'];
    $canValidate   = in_array($content->status, ['pending_validation', 'in_review', 'needs_revision']);
    $canReject     = in_array($content->status, ['pending_validation', 'in_review', 'needs_revision']);
    $canPublish    = in_array($content->status, ['validated', 'published_hidden']);
    $canRegenerate = ! in_array($content->status, ['generating', 'published']);
    $meta          = $content->generation_metadata ?? [];
    $actionIcons   = [
        'created'              => ['icon' => 'fa-plus',        'color' => 'primary'],
        'generation_started'   => ['icon' => 'fa-rotate-right', 'color' => 'info'],
        'generation_completed' => ['icon' => 'fa-circle-check', 'color' => 'success'],
        'generation_failed'    => ['icon' => 'fa-circle-xmark', 'color' => 'danger'],
        'validated'            => ['icon' => 'fa-check-double', 'color' => 'success'],
        'rejected'             => ['icon' => 'fa-xmark',        'color' => 'danger'],
        'revision_requested'   => ['icon' => 'fa-pen',          'color' => 'warning'],
        'edited'               => ['icon' => 'fa-pen-to-square','color' => 'primary'],
        'published'            => ['icon' => 'fa-paper-plane',  'color' => 'success'],
        'synced_to_erp'        => ['icon' => 'fa-link',         'color' => 'info'],
    ];
@endphp

@php
    $duplicates = \Modules\Supplier\Models\Ai\AiContent::where('content_hash', $content->content_hash)
        ->where('id', '!=', $content->id)
        ->whereNotNull('content_hash')
        ->with('supplierProduct')
        ->limit(3)
        ->get();
@endphp

@section('content')

    @include('core::components.card', ['title' => 'Contenido IA'])

    <div class="widget-content searchable-container list">

@if($duplicates->count() > 0)
            <div class="alert alert-warning d-flex gap-2 mb-3 py-2">
                <i class="fas fa-copy mt-1 flex-shrink-0"></i>
                <div class="small">
                    <strong>Esta descripción es idéntica a {{ $duplicates->count() }} otro{{ $duplicates->count() > 1 ? 's' : '' }} producto{{ $duplicates->count() > 1 ? 's' : '' }}:</strong>
                    @foreach($duplicates as $dup)
                        <a href="{{ route('settings.suppliers.content.show', $dup->uid) }}" class="ms-2 text-warning-emphasis fw-semibold">
                            {{ $dup->supplierProduct?->name ?? $dup->generated_name ?? $dup->erp_reference ?? $dup->uid }}
                        </a>{{ ! $loop->last ? ',' : '' }}
                    @endforeach
                </div>
            </div>
        @endif

@if($content->rejection_reason)
            <div class="alert alert-danger d-flex gap-2 mb-3">
                <i class="fas fa-circle-exclamation mt-1"></i>
                <div><strong>Motivo de rechazo:</strong> {{ $content->rejection_reason }}</div>
            </div>
        @endif

        @if($content->error_message)
            <div class="alert alert-warning d-flex gap-2 mb-3">
                <i class="fas fa-triangle-exclamation mt-1"></i>
                <div><strong>Error:</strong> {{ $content->error_message }}</div>
            </div>
        @endif

        <div class="row g-3">

            {{-- ============================================================ --}}
            {{-- LEFT: main content                                           --}}
            {{-- ============================================================ --}}
            <div class="col-12 col-lg-8">
                <div class="card">

                    {{-- Header principal --}}
                    <div class="card-header d-flex align-items-center gap-2">
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="pb-header-title">Contenido generado</h5>
                            <p class="pb-header-sub">Revisión y gestión del contenido creado por IA</p>
                        </div>
                        @if($content->prompt)
                            <a href="{{ route('settings.suppliers.prompts.edit', $content->prompt->uid) }}"
                               class="pb-badge text-decoration-none" target="_blank">
                                {{ $content->prompt->label }}
                            </a>
                        @else
                            <span class="pb-badge">IA</span>
                        @endif
                        @php
                            $qs = $content->quality_score;
                            $qsColor = $qs >= 80 ? 'success' : ($qs >= 50 ? 'warning' : 'danger');
                        @endphp
                        </div>

                    {{-- Nombre generado --}}
                    @if($content->generated_name)
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold">Nombre generado</h6>
                                <p class="text-muted small mb-0 mt-1">Nombre del producto generado por la IA.</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btnEditName">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="nameViewMode">
                                <p class="mb-0 fw-semibold">{{ $content->generated_name }}</p>
                            </div>
                            <div id="nameEditMode" style="display:none">
                                <input type="text" class="form-control mb-2" id="inputGeneratedName"
                                       value="{{ $content->generated_name }}">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary w-100" id="btnSaveName">Guardar</button>
                                    <button type="button" class="btn btn-secondary w-100" id="btnCancelName">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Descripción corta --}}
                    @if($content->short_description)
                        @if($content->generated_name)<hr class="my-0">@endif
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold">Descripción corta</h6>
                                <p class="text-muted small mb-0 mt-1">Resumen breve para listados y previsualizaciones.</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btnEditShortDesc">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="shortDescViewMode">
                                <textarea class="form-control" rows="3" disabled>{{ $content->short_description }}</textarea>
                            </div>
                            <div id="shortDescEditMode" style="display:none">
                                <textarea class="form-control mb-2" id="inputShortDesc" rows="3">{{ $content->short_description }}</textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary w-100" id="btnSaveShortDesc">Guardar</button>
                                    <button type="button" class="btn btn-secondary w-100" id="btnCancelShortDesc">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Descripción larga --}}
                    @if($content->long_description)
                        @if($content->generated_name || $content->short_description)<hr class="my-0">@endif
                        <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold">Descripción larga</h6>
                                <p class="text-muted small mb-0 mt-1">Descripción completa del producto para fichas de detalle.</p>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-primary btn-sm" id="btnEditLongDesc">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="longDescViewMode">
                                <div id="long-description-render" class="border rounded p-3 bg-white shop-preview" style="display:block;">
                                    {!! \Modules\Supplier\Helpers\HtmlSanitizer::clean($content->long_description) !!}
                                </div>
                                <textarea class="form-control font-monospace small" id="long-description-source" readonly rows="14" style="display:none;">{{ $content->long_description }}</textarea>
                            </div>
                            <div id="longDescEditMode" style="display:none">
                                {{-- Pestañas Editor / HTML / Vista --}}
                                <ul class="nav nav-tabs mb-2" id="longDescEditorTabs">
                                    <li class="nav-item"><a class="nav-link active" data-tab="editor" href="#">Editor</a></li>
                                    <li class="nav-item"><a class="nav-link" data-tab="html" href="#">HTML</a></li>
                                    <li class="nav-item"><a class="nav-link" data-tab="vista" href="#">Vista</a></li>
                                </ul>
                                <div id="longDescEditorPane">
                                    <div id="longDescQuillWrap"><div id="longDescQuillEditor"></div></div>
                                </div>
                                <div id="longDescHtmlPane" style="display:none">
                                    <textarea class="form-control font-monospace small" id="htmlLongDescSource" rows="14"></textarea>
                                </div>
                                <div id="longDescVistaPane" style="display:none">
                                    <div class="border rounded p-3 bg-white shop-preview" id="longDescVistaRender"></div>
                                </div>
                                <div class="mt-2">
                                    <small id="longDescCharCount">0 caracteres</small>
                                    <div class="d-flex gap-2 mt-1">
                                        <button type="button" class="btn btn-primary w-100" id="btnSaveLongDesc">Guardar</button>
                                        <button type="button" class="btn btn-secondary w-100" id="btnCancelLongDesc">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Traducciones --}}
                    @php
                        $translations = $content->translations;
                        $translationLocales = is_array($translations) && count($translations) > 0
                            ? array_keys($translations)
                            : [];
                    @endphp
                    @if(count($translationLocales) > 0)
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <h6 class="mb-0 fw-bold">Traducciones</h6>
                            <p class="text-muted small mb-0 mt-1">Nombre y descripción del producto en otros idiomas.</p>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2 mb-3" id="translationTabs">
                                @foreach($translationLocales as $locale)
                                    <button type="button"
                                            class="btn btn-sm btn-primary translation-tab-btn {{ $loop->first ? 'active' : '' }}"
                                            data-locale="{{ $locale }}">
                                        {{ strtoupper($locale) }}
                                    </button>
                                @endforeach
                            </div>
                            @foreach($translationLocales as $locale)
                                @php $t = $translations[$locale]; @endphp
                                <div class="translation-panel" data-locale="{{ $locale }}" style="{{ $loop->first ? '' : 'display:none' }}">
                                    @if(!empty($t['name']))
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted mb-1">Nombre ({{ strtoupper($locale) }})</label>
                                            <p class="mb-0 fw-semibold">{{ $t['name'] }}</p>
                                        </div>
                                    @endif
                                    @if(!empty($t['description']))
                                        <div class="mb-0">
                                            <label class="form-label fw-semibold small text-muted mb-1">Descripción ({{ strtoupper($locale) }})</label>
                                            <div class="border rounded p-3 bg-white shop-preview" style="max-height:300px; overflow-y:auto;">
                                                {!! \Modules\Supplier\Helpers\HtmlSanitizer::clean($t['description']) !!}
                                            </div>
                                        </div>
                                    @endif
                                    @if(empty($t['name']) && empty($t['description']))
                                        <p class="text-muted fst-italic small mb-0">Sin contenido disponible para este idioma.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Fuentes web consultadas --}}
                    @if($content->sources_used && count($content->sources_used))
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <h6 class="mb-0 fw-bold">Fuentes web consultadas</h6>
                            <p class="text-muted small mb-0 mt-1">URLs consultadas por la IA durante la generación del contenido.</p>
                        </div>
                        <div class="card-body">
                            @php $webQuery = $meta['web_search_query'] ?? null; @endphp
                            @if($webQuery)
                                <div class="mb-2">
                                    <span class="badge bg-secondary text-white fw-normal" style="font-size:.75rem;">
                                        <i class="fas fa-magnifying-glass me-1"></i>Búsqueda: {{ $webQuery }}
                                    </span>
                                </div>
                            @endif
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Fuente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($content->sources_used as $srcIdx => $src)
                                            @php
                                                $srcUrl   = is_array($src) ? ($src['url']   ?? null) : $src;
                                                $srcTitle = is_array($src) ? ($src['title'] ?? null) : null;
                                                $srcHost  = $srcUrl ? (parse_url($srcUrl, PHP_URL_HOST) ?: $srcUrl) : null;
                                                $srcLabel = $srcTitle ?: $srcHost;
                                            @endphp
                                            @if($srcUrl)
                                                <tr>
                                                    <td class="small text-muted" style="width:32px">{{ $srcIdx + 1 }}</td>
                                                    <td class="small">
                                                        <a href="{{ $srcUrl }}" target="_blank" rel="noopener noreferrer"
                                                           class="text-decoration-none">
                                                            <i class="fas fa-arrow-up-right-from-square me-1 opacity-50" style="font-size:.65rem;"></i>{{ $srcLabel }}
                                                        </a>
                                                        @if($srcTitle && $srcHost && $srcHost !== $srcTitle)
                                                            <span class="text-muted ms-1" style="font-size:.75rem;">({{ $srcHost }})</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Historial de búsquedas anteriores --}}
                    @if($content->sources_history && count($content->sources_history))
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 fw-bold">Historial de búsquedas anteriores</h6>
                                    <p class="text-muted small mb-0 mt-1">Fuentes consultadas en regeneraciones previas (últimas {{ count($content->sources_history) }}).</p>
                                </div>
                                <button class="btn btn-sm btn-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#sourcesHistoryCollapse"
                                        aria-expanded="false" aria-controls="sourcesHistoryCollapse">
                                    <i class="fas fa-history"></i>
                                </button>
                            </div>
                        </div>
                        <div class="collapse" id="sourcesHistoryCollapse">
                            <div class="card-body pt-0">
                                @foreach(array_reverse($content->sources_history) as $histIndex => $histEntry)
                                    @php
                                        $histSources = $histEntry['sources_used'] ?? [];
                                        $histQuery   = $histEntry['web_search_query'] ?? null;
                                        $histDate    = $histEntry['archived_at'] ?? $histEntry['generated_at'] ?? null;
                                    @endphp
                                    <div class="border rounded mb-2 {{ $histIndex > 0 ? 'mt-2' : '' }}">
                                        <div class="px-3 py-2 bg-light-subtle d-flex align-items-center gap-2 border-bottom rounded-top">
                                            <i class="fas fa-clock-rotate-left text-muted small"></i>
                                            @if($histDate)
                                                <span class="small text-muted">{{ \Carbon\Carbon::parse($histDate)->format('d/m/Y H:i') }}</span>
                                            @endif
                                            @if($histQuery)
                                                <span class="badge bg-secondary fw-normal" style="font-size:.72rem;">
                                                    <i class="fas fa-magnifying-glass me-1"></i>{{ $histQuery }}
                                                </span>
                                            @endif
                                        </div>
                                        @if(count($histSources))
                                            <div class="px-3 py-2">
                                                <ul class="list-unstyled mb-0 small">
                                                    @foreach($histSources as $hSrc)
                                                        @php
                                                            $hUrl   = is_array($hSrc) ? ($hSrc['url']   ?? null) : $hSrc;
                                                            $hTitle = is_array($hSrc) ? ($hSrc['title'] ?? $hUrl)  : $hSrc;
                                                        @endphp
                                                        @if($hUrl)
                                                            <li class="mb-1">
                                                                <i class="fas fa-link text-muted me-1" style="font-size:.7rem;"></i>
                                                                <a href="{{ $hUrl }}" target="_blank" rel="noopener noreferrer"
                                                                   class="text-break text-decoration-none">{{ $hTitle }}</a>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <div class="px-3 py-2 text-muted small">Sin fuentes registradas en esta regeneración.</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Puntos destacados --}}
                    @if($content->bullet_points && count($content->bullet_points))
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <h6 class="mb-0 fw-bold">Puntos destacados</h6>
                            <p class="text-muted small mb-0 mt-1">Características principales del producto.</p>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush border rounded">
                                @foreach($content->bullet_points as $point)
                                    <li class="list-group-item bg-light-subtle py-2">
                                        <i class="fas fa-check text-success me-2"></i>{{ $point }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- SEO --}}
                    @if($content->seo_title || $content->seo_description || $content->seo_keywords)
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <h6 class="mb-0 fw-bold">SEO</h6>
                            <p class="text-muted small mb-0 mt-1">Meta título, descripción y palabras clave para posicionamiento.</p>
                        </div>

                        {{-- SEO Title --}}
                        @if($content->seo_title)
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">Meta título</h6>
                                    <p class="text-muted small mb-0 mt-1">Título SEO para buscadores.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnEditSeoTitle">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="seoTitleViewMode">
                                    <p class="mb-0 fw-semibold" id="seoTitleText">{{ $content->seo_title }}</p>
                                    <small class="text-muted" id="seoTitleCharCount">{{ mb_strlen($content->seo_title) }} caracteres</small>
                                </div>
                                <div id="seoTitleEditMode" style="display:none">
                                    <input type="text" class="form-control mb-1" id="inputSeoTitle"
                                           value="{{ $content->seo_title }}">
                                    <small class="text-muted d-block mb-2" id="seoTitleInputCount">{{ mb_strlen($content->seo_title) }} caracteres</small>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary w-100" id="btnSaveSeoTitle">Guardar</button>
                                        <button type="button" class="btn btn-secondary w-100" id="btnCancelSeoTitle">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- SEO Description --}}
                        @if($content->seo_description)
                            @if($content->seo_title)<hr class="my-0">@endif
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">Meta descripción</h6>
                                    <p class="text-muted small mb-0 mt-1">Descripción SEO para buscadores.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnEditSeoDesc">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="seoDescViewMode">
                                    <textarea class="form-control" rows="3" disabled id="seoDescText">{{ $content->seo_description }}</textarea>
                                    <small class="text-muted" id="seoDescCharCount">{{ mb_strlen($content->seo_description) }} caracteres</small>
                                </div>
                                <div id="seoDescEditMode" style="display:none">
                                    <textarea class="form-control mb-1" id="inputSeoDesc" rows="3">{{ $content->seo_description }}</textarea>
                                    <small class="text-muted d-block mb-2" id="seoDescInputCount">{{ mb_strlen($content->seo_description) }} caracteres</small>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary w-100" id="btnSaveSeoDesc">Guardar</button>
                                        <button type="button" class="btn btn-secondary w-100" id="btnCancelSeoDesc">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- SEO Keywords --}}
                        @if($content->seo_keywords)
                            @if($content->seo_title || $content->seo_description)<hr class="my-0">@endif
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">Keywords</h6>
                                    <p class="text-muted small mb-0 mt-1">Palabras clave separadas por comas.</p>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnEditSeoKeywords">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="seoKeywordsViewMode">
                                    <div class="d-flex flex-wrap gap-2" id="seoKeywordsBadges">
                                        @foreach(array_map('trim', explode(',', $content->seo_keywords)) as $kw)
                                            @if($kw)
                                                <span class="badge bg-primary-subtle text-primary">{{ $kw }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div id="seoKeywordsEditMode" style="display:none">
                                    <textarea class="form-control mb-2" id="inputSeoKeywords" rows="3">{{ $content->seo_keywords }}</textarea>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary w-100" id="btnSaveSeoKeywords">Guardar</button>
                                        <button type="button" class="btn btn-secondary w-100" id="btnCancelSeoKeywords">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Fuentes de datos --}}
                    @if($content->source_attributes)
                        <hr class="my-0">
                        <div class="card-header border-0 bg-transparent">
                            <h6 class="mb-0 fw-bold">Fuentes de datos</h6>
                            <p class="text-muted small mb-0 mt-1">Atributos originales utilizados para generar el contenido.</p>
                        </div>
                        <div class="card-body">
                            @if($content->source_attributes && count($content->source_attributes))
                                @php
                                $attrDescriptions = [
                                    'id'                 => ['label' => 'ID ERP',              'desc' => 'Identificador único del modelo en el sistema ERP Oracle.'],
                                    'code'               => ['label' => 'Código / SKU',         'desc' => 'Referencia interna del producto usada en almacén y compras.'],
                                    'name'               => ['label' => 'Nombre',               'desc' => 'Denominación comercial del modelo tal como viene del ERP.'],
                                    'description'        => ['label' => 'Descripción ERP',      'desc' => 'Texto descriptivo original almacenado en el ERP, antes de la generación IA.'],
                                    'web'                => ['label' => 'Estado web',           'desc' => 'Visibilidad en tienda: 0 = no publicar, 1 = publicar, 2 = pendiente de revisión.'],
                                    'available'          => ['label' => 'Disponible',           'desc' => 'Indica si el producto está activo y disponible para venta.'],
                                    'ean'                => ['label' => 'EAN / Código de barras','desc' => 'Código EAN-13 o UPC para identificación estándar del producto.'],
                                    'price'              => ['label' => 'Precio',               'desc' => 'Precio base del producto en el ERP (sin impuestos).'],
                                    'price_pvp'          => ['label' => 'PVP',                  'desc' => 'Precio de venta al público recomendado.'],
                                    'brand'              => ['label' => 'Marca',                'desc' => 'Marca o fabricante del producto.'],
                                    'season'             => ['label' => 'Temporada',            'desc' => 'Temporada comercial a la que pertenece el producto (ej. AW24, SS25).'],
                                    'gender'             => ['label' => 'Género',               'desc' => 'Público objetivo del producto: hombre, mujer, unisex, etc.'],
                                    'categorie'          => ['label' => 'Jerarquía',           'desc' => 'Deporte → Categoría → Familia → Subfamilia del producto.'],
                                    'sport'              => ['label' => 'Deporte',              'desc' => 'Deporte o actividad principal para la que está diseñado el producto.'],
                                    'supplier'           => ['label' => 'Proveedor',            'desc' => 'Proveedor principal del producto con sus datos de contacto e identificación.'],
                                    'suppliers'          => ['label' => 'Proveedores',          'desc' => 'Lista de todos los proveedores que suministran este modelo.'],
                                    'product_attributes' => ['label' => 'Atributos del producto','desc' => 'Características técnicas y de variante: talla, color, peso, material, etc.'],
                                    'statistics'         => ['label' => 'Estadísticas',         'desc' => 'Datos de stock, ventas o rotación del producto en el ERP.'],
                                    'last_sync_at'       => ['label' => 'Última sincronización','desc' => 'Fecha y hora en que los datos fueron sincronizados desde el ERP.'],
                                    'created_at'         => ['label' => 'Fecha de creación',    'desc' => 'Fecha en que el registro fue creado en el sistema.'],
                                    'updated_at'         => ['label' => 'Última actualización', 'desc' => 'Fecha de la última modificación del registro.'],
                                ];
                                @endphp
                                <label class="form-label fw-semibold mb-2">Atributos de origen</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:22%">Campo</th>
                                                <th style="width:30%">Descripción</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($content->source_attributes as $key => $value)
                                                @php $meta = $attrDescriptions[$key] ?? null; @endphp
                                                <tr>
                                                    <td class="small">
                                                        <span class="fw-semibold">{{ $meta['label'] ?? $key }}</span>
                                                        @if(! $meta)
                                                            <br><span class="font-monospace text-muted" style="font-size:.7rem">{{ $key }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">{{ $meta['desc'] ?? '—' }}</td>
                                                    <td class="small">
                                                        @if($key === 'categorie' && is_array($value))
                                                            {{-- Jerarquía completa: Deporte → Categoría → Familia → Subfamilia --}}
                                                            @php
                                                                $prodCat   = $content->supplierProduct?->category;
                                                                $prodSubfam = $content->supplierProduct?->subfamily;
                                                            @endphp
                                                            <div class="d-flex flex-column gap-1">
                                                                {{-- 1. Deporte --}}
                                                                @php $sportDesc = $value['sport']['description'] ?? $prodCat?->sport?->name; @endphp
                                                                @if($sportDesc)
                                                                    <span>
                                                                        <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:.65rem">Deporte</span>
                                                                        <strong>{{ $sportDesc }}</strong>
                                                                    </span>
                                                                @endif
                                                                {{-- 2. Categoría (erp_categoria) --}}
                                                                @if($prodCat?->erp_categoria_name)
                                                                    <span>
                                                                        <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:.65rem">Categoría</span>
                                                                        {{ $prodCat->erp_categoria_name }}
                                                                        <span class="text-muted font-monospace ms-1" style="font-size:.7rem">#{{ $prodCat->erp_categoria_id }}</span>
                                                                    </span>
                                                                @endif
                                                                {{-- 3. Familia --}}
                                                                @php $familiaDesc = $value['description'] ?? $prodCat?->name; @endphp
                                                                @if($familiaDesc)
                                                                    <span>
                                                                        <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:.65rem">Familia</span>
                                                                        {{ $familiaDesc }}
                                                                        @if(isset($value['id']))
                                                                            <span class="text-muted font-monospace ms-1" style="font-size:.7rem">#{{ $value['id'] }}</span>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                                {{-- 4. Subfamilia --}}
                                                                @php $subfamDesc = $prodSubfam?->name; @endphp
                                                                @if($subfamDesc)
                                                                    <span>
                                                                        <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:.65rem">Subfamilia</span>
                                                                        {{ $subfamDesc }}
                                                                        @if($prodSubfam->erp_id)
                                                                            <span class="text-muted font-monospace ms-1" style="font-size:.7rem">#{{ $prodSubfam->erp_id }}</span>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @elseif($key === 'product_attributes' && is_array($value))
                                                            {{-- Atributos: resumen de variantes --}}
                                                            <span class="fw-semibold">{{ count($value) }} variante(s)</span>
                                                            @foreach($value as $a)
                                                                <div class="mt-1 small border-top pt-1">
                                                                    @if(!empty($a['name'])) <div><span class="text-muted">Nombre:</span> {{ $a['name'] }}</div> @endif
                                                                    @if(!empty($a['code'])) <div><span class="text-muted">Código:</span> {{ $a['code'] }}</div> @endif
                                                                    @if(!empty($a['reference'])) <div><span class="text-muted">Ref:</span> {{ $a['reference'] }}</div> @endif
                                                                    @if(!empty($a['ean13'])) <div><span class="text-muted">EAN:</span> {{ $a['ean13'] }}</div> @endif
                                                                    @if(isset($a['available'])) <div><span class="text-muted">Disponible:</span> {{ $a['available'] ? 'Sí' : 'No' }}</div> @endif
                                                                    @if(!empty($a['subfamily_id'])) <div><span class="text-muted">Subfamilia ERP:</span> {{ $a['subfamily_id'] }}</div> @endif
                                                                </div>
                                                            @endforeach
                                                        @elseif($key === 'supplier' && is_array($value))
                                                            {{-- Proveedor principal --}}
                                                            <div>
                                                                <span class="fw-semibold">{{ $value['name'] ?? $value['label'] ?? '—' }}</span>
                                                                @if(!empty($value['cif'])) <span class="text-muted"> · {{ $value['cif'] }}</span> @endif
                                                            </div>
                                                            @if(!empty($value['email'])) <div class="small text-muted">{{ $value['email'] }}</div> @endif
                                                            @if(!empty($value['code'])) <div class="small text-muted">Código: {{ $value['code'] }}</div> @endif
                                                        @elseif($key === 'statistics' && is_array($value))
                                                            {{-- Estadísticas: mostrar cada métrica --}}
                                                            @foreach($value as $statKey => $statVal)
                                                                <div class="small">
                                                                    <span class="text-muted">{{ str_replace('_', ' ', ucfirst($statKey)) }}:</span>
                                                                    @if(is_array($statVal))
                                                                        @foreach($statVal as $sk => $sv)
                                                                            <span class="ms-1">{{ ucfirst($sk) }}: <strong>{{ $sv }}</strong></span>
                                                                        @endforeach
                                                                    @else
                                                                        <strong>{{ $statVal }}</strong>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @elseif(is_array($value))
                                                            {{-- Fallback genérico: tabla clave-valor --}}
                                                            @foreach($value as $vk => $vv)
                                                                <div class="small"><span class="text-muted">{{ $vk }}:</span> {{ is_array($vv) ? json_encode($vv) : $vv }}</div>
                                                            @endforeach
                                                        @elseif(is_bool($value))
                                                            <span class="badge bg-{{ $value ? 'success' : 'secondary' }}-subtle text-{{ $value ? 'success' : 'secondary' }}">
                                                                {{ $value ? 'Sí' : 'No' }}
                                                            </span>
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                {{-- Variantes / Atributos del producto --}}
                @php $productAttributes = $content->supplierProduct?->attributes ?? collect(); @endphp
                @if($productAttributes->isNotEmpty())
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0 fw-bold">Variantes / Atributos</h6>
                            <p class="text-muted small mb-0 mt-1">{{ $productAttributes->count() }} variante(s) asociadas al producto</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size:.82rem">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Código</th>
                                            <th>Referencia</th>
                                            <th>EAN</th>
                                            <th class="text-center">Disponible</th>
                                            <th class="text-center">Web</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productAttributes as $attr)
                                            <tr>
                                                <td>{{ $attr->name ?? '—' }}</td>
                                                <td class="font-monospace">{{ $attr->code ?? '—' }}</td>
                                                <td class="font-monospace">{{ $attr->reference ?? '—' }}</td>
                                                <td class="font-monospace">{{ $attr->ean13 ?? $attr->upc ?? '—' }}</td>
                                                <td class="text-center">
                                                    @if($attr->available)
                                                        <span class="badge bg-success-subtle text-success">Sí</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">No</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($attr->web_published)
                                                        <span class="badge bg-primary-subtle text-primary">Sí</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            {{-- ============================================================ --}}
            {{-- RIGHT: sidebar                                               --}}
            {{-- ============================================================ --}}
            <div class="col-12 col-lg-4">

                {{-- Acciones --}}
                @if($content->status !== 'published')
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-sliders"></i></span>
                        <div>
                            <h6 class="pb-section-title">Acciones</h6>
                            <p class="pb-section-sub">Validar, rechazar o ir al chat del producto</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <label class="form-label fw-semibold small">Descripción / Comentario</label>
                        <textarea class="form-control" id="action-description" rows="3" placeholder="Motivo de rechazo u observaciones (opcional)..."></textarea>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex flex-column gap-2">
                            @if($content->supplierProduct)
                                <a href="{{ route('settings.suppliers.products.chat', ['uid' => $content->supplierProduct->uid]) }}"
                                   class="btn btn-action-black w-100 fw-semibold text-white">
                                    Ir al chat del producto
                                </a>
                            @endif
                            @if($canRegenerate)
                                <button type="button"
                                        class="btn btn-secondary w-100 fw-semibold"
                                        id="btn-regenerate">
                                    Regenerar
                                </button>
                            @endif
                            @if($canValidate)
                                <button type="button"
                                        class="btn btn-validate-green w-100 fw-semibold text-white"
                                        id="btn-validate">
                                    Aprobar
                                </button>
                            @endif
                            @if($canReject)
                                <button type="button"
                                        class="btn btn-primary w-100 fw-semibold"
                                        id="btn-reject">
                                    Rechazar
                                </button>
                            @endif
                            @if($canPublish)
                                <button type="button"
                                        class="btn btn-primary btn-action-blue w-100 fw-semibold"
                                        id="btn-publish">
                                    Publicar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Traducción automática --}}
                <div class="card mb-3 d-none">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-language"></i></span>
                        <div>
                            <h6 class="pb-section-title">Traducción automática</h6>
                            <p class="pb-section-sub">Traduce el contenido generado con DeepL</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <select class="form-control select2 mb-3" id="translation-lang">
                            <option value="">Selecciona un idioma</option>
                            <option value="EN">Inglés</option>
                            <option value="FR">Francés</option>
                            <option value="DE">Alemán</option>
                            <option value="IT">Italiano</option>
                            <option value="PT">Portugués</option>
                            <option value="NL">Neerlandés</option>
                        </select>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-primary w-100" id="btn-translate-single">
                                Traducir idioma seleccionado
                            </button>
                            <button type="button" class="btn btn-primary w-100" id="btn-translate-all">
                                Traducir todos los idiomas
                            </button>
                        </div>
                    </div>
                </div>

                {{-- SEO Score --}}
                <div class="card mb-3 d-none">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-chart-line"></i></span>
                        <div>
                            <h6 class="pb-section-title">Puntuación SEO</h6>
                            <p class="pb-section-sub">Análisis de optimización para buscadores</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $seo = $content->seoScore(); @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">Score</span>
                            <span class="badge bg-{{ $seo['color'] }}-subtle text-{{ $seo['color'] }} fs-2 px-3">
                                {{ $seo['score'] }}%
                            </span>
                        </div>
                        <div class="progress mb-3" style="height:6px">
                            <div class="progress-bar bg-{{ $seo['color'] }}" style="width:{{ $seo['score'] }}%"></div>
                        </div>
                        <ul class="list-unstyled mb-0 small">
                            @foreach($seo['checks'] as $check)
                                <li class="d-flex align-items-center gap-2 py-1">
                                    <i class="fas fa-{{ $check['pass'] ? 'check text-success' : 'times text-danger' }}" style="width:14px"></i>
                                    <span class="text-{{ $check['pass'] ? 'body' : 'muted' }}">{{ $check['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-info-circle"></i></span>
                        <div>
                            <h6 class="pb-section-title">Estado</h6>
                            <p class="pb-section-sub">Estado actual del proceso de generación</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <span class="badge bg-{{ $s['color'] }}-subtle text-{{ $s['color'] }} fs-2 px-3 py-2">
                            {{ $s['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Información --}}
                @php
                    $sa = $content->source_attributes ?? [];
                    $saCategorie = is_array($sa['categorie'] ?? null) ? $sa['categorie'] : null;
                    $saSport = is_array($saCategorie['sport'] ?? null) ? $saCategorie['sport'] : null;
                    $saSupplier = is_array($sa['supplier'] ?? null) ? $sa['supplier'] : null;

                    $productName = $content->supplierProduct?->name ?? $content->generated_name ?? ($sa['name'] ?? null);
                    $productCode = $content->supplierProduct?->code ?? ($sa['code'] ?? null);
                    $supplierLabel = $content->supplierProduct?->supplier?->label
                        ?? $content->supplier?->label
                        ?? ($saSupplier['name'] ?? null);
                    $supplierCif = $content->supplierProduct?->supplier?->cif ?? ($saSupplier['cif'] ?? null);
                    $cat          = $content->supplierProduct?->category;

                    // source_attributes['categorie'] puede ser un array {id, description, sport}
                    $saCatObj     = is_array($saCategorie) && isset($saCategorie['description']) ? $saCategorie : null;
                    $saSportObj   = $saCatObj ? ($saCatObj['sport'] ?? null) : (is_array($saSport) ? $saSport : null);

                    $sportName    = $cat?->sport?->name
                        ?? ($saSportObj['description'] ?? null)
                        ?? ($saSport['description'] ?? null);

                    $catErpName   = $cat?->erp_categoria_name ?? null;

                    $familiaName  = $cat?->name
                        ?? ($saCatObj['description'] ?? null)
                        ?? ($saCategorie['description'] ?? null);

                    $subfamName   = $content->supplierProduct?->subfamily?->name ?? null;
                    $categoryName = $familiaName; // mantener compatibilidad
                    $brand = $sa['brand'] ?? null;
                    $season = $sa['season'] ?? null;
                    $gender = $sa['gender'] ?? null;
                    $webState = $sa['web'] ?? null;
                    $webLabels = [0 => 'No publicar', 1 => 'Publicado', 2 => 'Pendiente'];
                @endphp
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pb-side-icon"><i class="fas fa-circle-info"></i></span>
                        <div>
                            <h6 class="pb-section-title">Información</h6>
                            <p class="pb-section-sub">Datos del producto, proveedor y clasificación</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @if($productName)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Producto</span>
                                    <span class="small fw-semibold text-end">{{ $productName }}</span>
                                </li>
                            @endif
                            @if($productCode)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Código</span>
                                    <span class="small fw-semibold font-monospace text-end">{{ $productCode }}</span>
                                </li>
                            @endif
                            @if($content->erp_reference)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Referencia ERP</span>
                                    <span class="small fw-semibold font-monospace text-end">{{ $content->erp_reference }}</span>
                                </li>
                            @endif
                            @if($erpIdModelo)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">ID Gestión</span>
                                    <span class="small fw-semibold font-monospace text-end">{{ $erpIdModelo }}</span>
                                </li>
                            @endif
                            @if($content->ean)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">EAN</span>
                                    <span class="small fw-semibold font-monospace text-end">{{ $content->ean }}</span>
                                </li>
                            @endif
                            @if($supplierLabel)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Proveedor</span>
                                    <span class="small fw-semibold text-end">
                                        {{ $supplierLabel }}
                                        @if($supplierCif)<br><span class="text-muted font-monospace" style="font-size:.7rem">{{ $supplierCif }}</span>@endif
                                    </span>
                                </li>
                            @endif
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Deporte</span>
                                <span class="small fw-semibold text-end">{{ $sportName ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Categoría</span>
                                <span class="small fw-semibold text-end">{{ $catErpName ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Familia</span>
                                <span class="small fw-semibold text-end">{{ $familiaName ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Subfamilia</span>
                                <span class="small fw-semibold text-end">{{ $subfamName ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2 align-items-center">
                                <span class="text-muted small">Marca</span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="small fw-semibold text-end" id="brandDisplay">{{ $brand ?? '—' }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-muted" id="btnEditBrand" title="Editar marca">
                                        <i class="fas fa-pen" style="font-size:.7rem"></i>
                                    </button>
                                </div>
                            </li>
                            <li id="brandEditRow" class="py-2 border-bottom" style="display:none">
                                <label class="form-label small fw-semibold mb-1">Editar marca</label>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" id="inputBrand"
                                           value="{{ $brand ?? '' }}" placeholder="Marca...">
                                    <button type="button" class="btn btn-primary btn-sm" id="btnSaveBrand">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="btnCancelBrand">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </li>
                            @if($season)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Temporada</span>
                                    <span class="small fw-semibold text-end">{{ $season }}</span>
                                </li>
                            @endif
                            @if($gender)
                                <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                    <span class="text-muted small">Género</span>
                                    <span class="small fw-semibold text-end">{{ $gender }}</span>
                                </li>
                            @endif
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Prompt</span>
                                <span class="small fw-semibold text-end">{{ $content->prompt?->label ?? '—' }}</span>
                            </li>
                            @php $modeloUsado = $meta['model'] ?? $content->prompt?->ai_model ?? null; @endphp
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2">
                                <span class="text-muted small">Modelo IA</span>
                                <span class="small fw-semibold font-monospace text-end">{{ $modeloUsado ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-2 align-items-center">
                                <span class="text-muted small">Sincronizado al ERP</span>
                                @if($content->synced_to_erp_at)
                                    <span class="small fw-semibold text-end d-flex align-items-center gap-1">
                                        <i class="fas fa-circle-check text-success" style="font-size:.85rem;" title="Sincronizado"></i>
                                        {{ \Carbon\Carbon::parse($content->synced_to_erp_at)->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="small fw-semibold text-end d-flex align-items-center gap-1">
                                        <i class="fas fa-clock text-warning" style="font-size:.85rem;" title="Pendiente"></i>
                                        <span class="text-warning">Pendiente</span>
                                    </span>
                                @endif
                            </li>
                            <li class="d-flex justify-content-between py-2 gap-2">
                                <span class="text-muted small">Creado</span>
                                <span class="small fw-semibold text-end">{{ $content->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Generación IA --}}
                @if($meta)
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="pb-side-icon"><i class="fas fa-microchip"></i></span>
                            <div>
                                <h6 class="pb-section-title">Generación IA</h6>
                                <p class="pb-section-sub">Parámetros del modelo usado</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @if(isset($meta['model']))
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">Modelo</span>
                                        <span class="small fw-semibold font-monospace">{{ $meta['model'] }}</span>
                                    </li>
                                @endif
                                @if(isset($meta['tokens']))
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">Tokens entrada</span>
                                        <span class="small fw-semibold">{{ number_format($meta['tokens']['input'] ?? 0) }}</span>
                                    </li>
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">Tokens salida</span>
                                        <span class="small fw-semibold">{{ number_format($meta['tokens']['output'] ?? 0) }}</span>
                                    </li>
                                @endif
                                @if(isset($meta['latency_ms']))
                                    <li class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">Latencia</span>
                                        <span class="small fw-semibold">{{ number_format($meta['latency_ms'] / 1000, 2) }}s</span>
                                    </li>
                                @endif
                                @if(isset($meta['generated_at']))
                                    <li class="d-flex justify-content-between py-2">
                                        <span class="text-muted small">Generado</span>
                                        <span class="small fw-semibold">{{ \Carbon\Carbon::parse($meta['generated_at'])->format('d/m/Y H:i') }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Validación --}}
                @if($content->validator)
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="pb-side-icon"><i class="fas fa-user-check"></i></span>
                            <div>
                                <h6 class="pb-section-title">Validación</h6>
                                <p class="pb-section-sub">Información de la revisión manual</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted small">Validado por</span>
                                    <span class="small fw-semibold">{{ $content->validator->full_name }}</span>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted small">Fecha</span>
                                    <span class="small fw-semibold">{{ $content->validated_at?->format('d/m/Y H:i') ?? '—' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Historial --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2 border-bottom">
                        <span class="pb-side-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <div>
                            <h6 class="pb-section-title">Historial</h6>
                            <p class="pb-section-sub">Registro de acciones sobre este contenido</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @php
                        $logs = $content->logs()->with('user')->latest()->take(10)->get();
                        $actionDescriptions = [
                            'created'              => 'El registro de contenido fue creado en el sistema.',
                            'generation_started'   => 'Se inició el proceso de generación de contenido con IA.',
                            'generation_completed' => 'La IA generó el contenido correctamente.',
                            'generation_failed'    => 'Ocurrió un error durante la generación y no se pudo completar.',
                            'validated'            => 'El contenido fue revisado y aprobado manualmente.',
                            'rejected'             => 'El contenido fue rechazado y requiere nueva generación.',
                            'revision_requested'   => 'Se solicitó una revisión adicional antes de aprobar.',
                            'edited'               => 'El contenido fue modificado manualmente por un usuario.',
                            'published'            => 'El contenido fue publicado y enviado al canal destino.',
                            'synced_to_erp'        => 'El contenido fue sincronizado con el sistema ERP.',
                        ];
                        @endphp
                        @if($logs->isEmpty())
                            <p class="text-muted fst-italic mb-0">Sin entradas en el historial</p>
                        @else
                            <ul class="timeline-widget mb-0 position-relative">
                                @foreach($logs as $log)
                                    @php $ai = $actionIcons[$log->action] ?? ['icon' => 'fa-circle', 'color' => 'secondary']; @endphp
                                    <li class="timeline-item d-flex align-items-start pb-3 gap-2">
                                        <div class="flex-shrink-0">
                                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $ai['color'] }}-subtle text-{{ $ai['color'] }}"
                                                  style="width:28px;height:28px">
                                                <i class="fas {{ $ai['icon'] }} small"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start gap-1">
                                                <span class="small fw-semibold">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                                                <span class="text-muted" style="font-size:.7rem;white-space:nowrap">
                                                    {{ $log->created_at->format('d/m H:i') }}
                                                </span>
                                            </div>
                                            @if(isset($actionDescriptions[$log->action]))
                                                <div class="text-muted" style="font-size:.72rem">
                                                    {{ $actionDescriptions[$log->action] }}
                                                </div>
                                            @endif
                                            <div class="text-muted" style="font-size:.75rem">
                                                {{ $log->user?->name ?? 'Sistema' }}
                                                @if($log->previous_status && $log->new_status)
                                                    · {{ $log->new_status }}
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </div>

{{-- Modal Regenerar --}}
<div class="modal fade" id="modalRegenerar" tabindex="-1" aria-labelledby="modalRegenerarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegenerarLabel">Regenerar contenido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Estás seguro de que quieres <strong>regenerar</strong> este contenido con IA?</p>
                <p class="small text-muted mt-2 mb-0">El contenido actual se reemplazará y volverá al estado <em>Pendiente de validación</em>.</p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1">
                <button type="button" class="btn btn-secondary w-100 fw-semibold" id="btn-regenerate-confirm">
                    Sí, regenerar
                </button>
                <button type="button" class="btn btn-primary w-100 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Publicar --}}
<div class="modal fade" id="modalPublicar" tabindex="-1" aria-labelledby="modalPublicarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPublicarLabel">Publicar en tienda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Selecciona el estado de publicación que se enviará al ERP.</p>
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold mb-1" for="modal_nombre_pub">Nombre del producto</label>
                        <input type="text" class="form-control" id="modal_nombre_pub"
                               value="{{ $content->generated_name ?? $productName ?? '' }}"
                               placeholder="Nombre del producto...">
                        <small class="text-muted">Se enviará al ERP como campo <code>nombre</code>.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold mb-1" for="modal_marca_pub">Marca (ID ERP)</label>
                        <input type="text" class="form-control" id="modal_marca_pub"
                               value="{{ $brand ?? $content->supplierProduct?->metadata['erp_marca'] ?? '' }}"
                               placeholder="Vacío si no aplica...">
                        <small class="text-muted">Se enviará al ERP como campo <code>marca</code>. Deja vacío para omitirlo.</small>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="modal_publicar_pub">Estado en tienda</label>
                    <select class="form-select select2" id="modal_publicar_pub">
                        <option value="1" selected>1 — Publicar</option>
                        <option value="0">0 — No publicar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer d-flex flex-column gap-1">
                <button type="button" class="btn btn-primary btn-action-blue fw-semibold w-100" id="btn-publish-confirm">
                    Publicar y sincronizar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarLabel">
                    Confirmar rechazo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Estás seguro de que quieres <strong>rechazar</strong> este contenido?</p>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="modal_reject_reason">Motivo del rechazo</label>
                    <textarea class="form-control" id="modal_reject_reason" rows="3" placeholder="Indica el motivo del rechazo (opcional)..."></textarea>
                </div>
            </div>
            <div class="modal-footer d-flex flex-column gap-1">
                <button type="button" class="btn btn-secondary w-100 fw-semibold" id="btn-reject-confirm">
                    Rechazar contenido
                </button>
                <button type="button" class="btn btn-primary w-100 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Validar --}}
<div class="modal fade" id="modalValidar" tabindex="-1" aria-labelledby="modalValidarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalValidarLabel">
                   Confirmar validación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Estás seguro de que quieres <strong>validar</strong> este contenido y sincronizarlo con el ERP?</p>
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold  mb-1" for="modal_nombre">Nombre del producto</label>
                        <input type="text" class="form-control" id="modal_nombre"
                               value="{{ $content->generated_name ?? $productName ?? '' }}"
                               placeholder="Nombre del producto...">
                        <small class="text-muted">Se enviará al ERP como campo <code>nombre</code>.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold  mb-1" for="modal_marca">Marca (ID ERP)</label>
                        <input type="text" class="form-control" id="modal_marca"
                               value="{{ $brand ?? $content->supplierProduct?->metadata['erp_marca'] ?? '' }}"
                               placeholder="Vacío si no aplica...">
                        <small class="text-muted">Se enviará al ERP como campo <code>marca</code>. Deja vacío para omitirlo.</small>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="modal_publicar">Publicar en tienda</label>
                    <select class="form-select " id="modal_publicar">
                        <option value="1" selected>1 — Publicar</option>
                        <option value="0">0 — No publicar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer d-flex flex-column gap-1">
                <button type="button" class="btn btn-validate-green fw-semibold text-white w-100" id="btn-validate-confirm">
                    Validar y sincronizar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.8/dist/purify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
$(document).ready(function () {

    $('#translation-lang').select2({
        allowClear: true,
        placeholder: 'Selecciona un idioma',
        width: '100%',
    });

    $('#modal_publicar').select2({
        dropdownParent: $('#modalValidar'),
        width: '100%',
        minimumResultsForSearch: Infinity,
    });

    $('#modal_publicar_pub').select2({
        dropdownParent: $('#modalPublicar'),
        width: '100%',
        minimumResultsForSearch: Infinity,
    });

    const actionUrl   = '{{ route("settings.suppliers.content.action", $content->uid) }}';
    const publishUrl  = '{{ route("settings.suppliers.content.publish", $content->uid) }}';
    const erpTestUrl  = '{{ route("settings.suppliers.endpoints.test") }}';
    const erpModelUrl = '{{ $erpModeloUrl }}';
    const erpIdModelo = {!! json_encode((string) $erpIdModelo) !!};

    function doAction(action, label, payload) {
        payload = payload || {};
        if (! confirm('¿' + label + ' este contenido?')) return;

        const $btn = $('#btn-' + action).prop('disabled', true);

        $.ajax({
            url: actionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: Object.assign({ action: action }, payload),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Acción completada', 'Contenido IA');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message || 'Error', 'Error');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al procesar', 'Error');
                $btn.prop('disabled', false);
            }
        });
    }

    $('#btn-validate').on('click', function () {
        $('#modal_publicar').val('1').trigger('change');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalValidar')).show();
    });

    $('#btn-validate-confirm').on('click', function () {
        const publicar = parseInt($('#modal_publicar').val(), 10);
        const nombre   = $('#modal_nombre').val().trim();
        const marca    = $('#modal_marca').val().trim();
        const $btn     = $('#btn-validate-confirm').prop('disabled', true);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalValidar')).hide();
        $('#btn-validate').prop('disabled', true);

        const approvePayload = { action: 'approve', publicar: publicar, marca: marca };
        if (nombre) approvePayload.nombre = nombre;

        $.ajax({
            url: actionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: approvePayload,
            success: function (res) {
                if (! res.success) {
                    toastr.error(res.message || 'Error al validar', 'Error');
                    $('#btn-validate').prop('disabled', false);
                    $btn.prop('disabled', false);
                    return;
                }

                const erp = res.erp_result;
                if (erp && erp.success) {
                    toastr.success('Validado y sincronizado con ERP correctamente', 'Contenido IA');
                } else if (erp) {
                    toastr.warning(
                        'Contenido validado pero falló la sincronización con ERP: ' + (erp.message || 'Error desconocido'),
                        'Advertencia'
                    );
                } else {
                    toastr.success('Contenido validado correctamente', 'Contenido IA');
                }

                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al validar', 'Error');
                $('#btn-validate').prop('disabled', false);
                $btn.prop('disabled', false);
            }
        });
    });

    $('#btn-reject').on('click', function () {
        $('#modal_reject_reason').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRechazar')).show();
    });

    $('#btn-reject-confirm').on('click', function () {
        const reason = $('#modal_reject_reason').val().trim() || 'Rechazo manual';
        const $btn   = $('#btn-reject-confirm').prop('disabled', true);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRechazar')).hide();
        $('#btn-reject').prop('disabled', true);

        $.ajax({
            url: actionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { action: 'reject', reason: reason },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Contenido rechazado', 'Contenido IA');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message || 'Error', 'Error');
                    $('#btn-reject').prop('disabled', false);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al rechazar', 'Error');
                $('#btn-reject').prop('disabled', false);
                $btn.prop('disabled', false);
            }
        });
    });

    $('#btn-regenerate').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegenerar')).show();
    });

    $('#btn-regenerate-confirm').on('click', function () {
        const $btn = $('#btn-regenerate-confirm').prop('disabled', true);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegenerar')).hide();
        $('#btn-regenerate').prop('disabled', true);

        $.ajax({
            url: actionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { action: 'regenerate' },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Contenido en regeneración', 'Contenido IA');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message || 'Error', 'Error');
                    $('#btn-regenerate').prop('disabled', false);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al regenerar', 'Error');
                $('#btn-regenerate').prop('disabled', false);
                $btn.prop('disabled', false);
            }
        });
    });

    $('#btn-publish').on('click', function () {
        $('#modal_publicar_pub').val('1').trigger('change');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPublicar')).show();
    });

    $('#btn-publish-confirm').on('click', function () {
        const publicar = parseInt($('#modal_publicar_pub').val(), 10);
        const nombre   = $('#modal_nombre_pub').val().trim();
        const marca    = $('#modal_marca_pub').val().trim();
        const $btn     = $('#btn-publish-confirm').prop('disabled', true);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPublicar')).hide();
        $('#btn-publish').prop('disabled', true);

        const publishData = { action: 'publish_erp', publicar: publicar, marca: marca };
        if (nombre) publishData.nombre = nombre;

        $.ajax({
            url: actionUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: publishData,
            success: function (res) {
                if (res.success) {
                    toastr.success('Publicado y sincronizado con ERP correctamente', 'Publicar');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.warning(
                        'Error al sincronizar con ERP: ' + (res.message || 'Error desconocido'),
                        'Advertencia'
                    );
                    $('#btn-publish').prop('disabled', false);
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.warning(
                    'Error al sincronizar con ERP: ' + (xhr.responseJSON?.message || xhr.statusText),
                    'Advertencia'
                );
                $('#btn-publish').prop('disabled', false);
                $btn.prop('disabled', false);
            }
        });
    });

    $('#btn-translate-single').on('click', function () {
        const lang = $('#translation-lang').val();
        if (! lang) {
            toastr.warning('Selecciona un idioma primero', 'Traducción');
            return;
        }
        doTranslate([lang]);
    });

    $('#btn-translate-all').on('click', function () {
        doTranslate(['EN', 'FR', 'DE', 'IT', 'PT', 'NL']);
    });

    function doTranslate(langs) {
        const $btns = $('#btn-translate-single, #btn-translate-all').prop('disabled', true);
        $.ajax({
            url: '{{ route("settings.suppliers.content.translate", $content->uid) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { languages: langs },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Traducción completada', 'Traducción');
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message || 'Error al traducir', 'Error');
                    $btns.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al traducir', 'Error');
                $btns.prop('disabled', false);
            }
        });
    }

    const editUrl   = '{{ route("settings.suppliers.content.edit", $content->uid) }}';
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function saveField(field, value, onSuccess) {
        $.ajax({
            url: editUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { field: field, value: value },
            success: function (res) {
                if (res.success) {
                    toastr.success('Guardado correctamente', 'Contenido IA');
                    if (onSuccess) onSuccess();
                } else {
                    toastr.error(res.message || 'Error al guardar', 'Error');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar', 'Error');
            }
        });
    }

    // Inline edit: generated_name
    $('#btnEditName').on('click', function () {
        $('#nameViewMode').hide();
        $('#nameEditMode').show();
        $('#inputGeneratedName').trigger('focus');
    });
    $('#btnCancelName').on('click', function () {
        $('#inputGeneratedName').val($('#nameViewMode p').text());
        $('#nameEditMode').hide();
        $('#nameViewMode').show();
    });
    $('#btnSaveName').on('click', function () {
        const val = $('#inputGeneratedName').val().trim();
        if (!val) { toastr.warning('El nombre no puede estar vacío'); return; }
        saveField('generated_name', val, function () {
            $('#nameViewMode p').text(val);
            $('#nameEditMode').hide();
            $('#nameViewMode').show();
        });
    });

    // Inline edit: brand (stored in source_attributes)
    $('#btnEditBrand').on('click', function () {
        $('#brandEditRow').show();
        $('#inputBrand').trigger('focus');
    });
    $('#btnCancelBrand').on('click', function () {
        $('#brandEditRow').hide();
    });
    $('#btnSaveBrand').on('click', function () {
        const val = $('#inputBrand').val().trim();
        saveField('brand', val, function () {
            $('#brandDisplay').text(val || '—');
            $('#brandEditRow').hide();
        });
    });

    // Inline edit: short_description
    $('#btnEditShortDesc').on('click', function () {
        $('#shortDescViewMode').hide();
        $('#shortDescEditMode').show();
        $('#inputShortDesc').trigger('focus');
    });
    $('#btnCancelShortDesc').on('click', function () {
        $('#inputShortDesc').val($('#shortDescViewMode textarea').val());
        $('#shortDescEditMode').hide();
        $('#shortDescViewMode').show();
    });
    $('#btnSaveShortDesc').on('click', function () {
        const val = $('#inputShortDesc').val().trim();
        if (!val) { toastr.warning('La descripción no puede estar vacía'); return; }
        saveField('short_description', val, function () {
            $('#shortDescViewMode textarea').val(val);
            $('#shortDescEditMode').hide();
            $('#shortDescViewMode').show();
        });
    });

    // Inline edit: seo_title
    $('#btnEditSeoTitle').on('click', function () {
        $('#seoTitleViewMode').hide();
        $('#seoTitleEditMode').show();
        $('#inputSeoTitle').trigger('focus');
    });
    $('#btnCancelSeoTitle').on('click', function () {
        $('#inputSeoTitle').val($('#seoTitleText').text());
        $('#seoTitleEditMode').hide();
        $('#seoTitleViewMode').show();
    });
    $('#inputSeoTitle').on('input', function () {
        $('#seoTitleInputCount').text($(this).val().length + ' caracteres');
    });
    $('#btnSaveSeoTitle').on('click', function () {
        const val = $('#inputSeoTitle').val().trim();
        if (!val) { toastr.warning('El meta título no puede estar vacío'); return; }
        saveField('seo_title', val, function () {
            $('#seoTitleText').text(val);
            $('#seoTitleCharCount').text(val.length + ' caracteres');
            $('#seoTitleEditMode').hide();
            $('#seoTitleViewMode').show();
        });
    });

    // Inline edit: seo_description
    $('#btnEditSeoDesc').on('click', function () {
        $('#seoDescViewMode').hide();
        $('#seoDescEditMode').show();
        $('#inputSeoDesc').trigger('focus');
    });
    $('#btnCancelSeoDesc').on('click', function () {
        $('#inputSeoDesc').val($('#seoDescText').val());
        $('#seoDescEditMode').hide();
        $('#seoDescViewMode').show();
    });
    $('#inputSeoDesc').on('input', function () {
        $('#seoDescInputCount').text($(this).val().length + ' caracteres');
    });
    $('#btnSaveSeoDesc').on('click', function () {
        const val = $('#inputSeoDesc').val().trim();
        if (!val) { toastr.warning('La meta descripción no puede estar vacía'); return; }
        saveField('seo_description', val, function () {
            $('#seoDescText').val(val);
            $('#seoDescCharCount').text(val.length + ' caracteres');
            $('#seoDescEditMode').hide();
            $('#seoDescViewMode').show();
        });
    });

    // Inline edit: seo_keywords
    $('#btnEditSeoKeywords').on('click', function () {
        $('#seoKeywordsViewMode').hide();
        $('#seoKeywordsEditMode').show();
        $('#inputSeoKeywords').trigger('focus');
    });
    $('#btnCancelSeoKeywords').on('click', function () {
        $('#inputSeoKeywords').val(
            $('#seoKeywordsBadges .badge').map(function () { return $(this).text().trim(); }).get().join(', ')
        );
        $('#seoKeywordsEditMode').hide();
        $('#seoKeywordsViewMode').show();
    });
    $('#btnSaveSeoKeywords').on('click', function () {
        const val = $('#inputSeoKeywords').val().trim();
        if (!val) { toastr.warning('Las keywords no pueden estar vacías'); return; }
        saveField('seo_keywords', val, function () {
            const badges = val.split(',').map(k => k.trim()).filter(k => k)
                .map(k => '<span class="badge bg-primary-subtle text-primary">' + $('<span>').text(k).html() + '</span>')
                .join('');
            $('#seoKeywordsBadges').html(badges);
            $('#seoKeywordsEditMode').hide();
            $('#seoKeywordsViewMode').show();
        });
    });

    // Traducciones: toggle de pestañas por idioma
    $(document).on('click', '.translation-tab-btn', function () {
        var locale = $(this).data('locale');
        $('.translation-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.translation-panel').hide();
        $('.translation-panel[data-locale="' + locale + '"]').show();
    });

    // Rich editor: long_description
    let longDescQuill = null;

    function initLongDescQuill() {
        if (longDescQuill) return;
        longDescQuill = new Quill('#longDescQuillEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });
        longDescQuill.on('text-change', function () {
            const html = longDescQuill.root.innerHTML;
            $('#htmlLongDescSource').val(html);
            const text = html.replace(/<[^>]*>/g, '').trim();
            $('#longDescCharCount').text(text.length + ' caracteres');
        });
    }

    function longDescOpenEditor(html) {
        initLongDescQuill();
        $('#htmlLongDescSource').val(html || '');
        longDescQuill.clipboard.dangerouslyPasteHTML(html || '');
        const text = (html || '').replace(/<[^>]*>/g, '').trim();
        $('#longDescCharCount').text(text.length + ' caracteres');
        // Activar pestaña Editor
        $('#longDescEditorTabs .nav-link').removeClass('active');
        $('#longDescEditorTabs .nav-link[data-tab="editor"]').addClass('active');
        $('#longDescEditorPane').show();
        $('#longDescHtmlPane').hide();
        $('#longDescVistaPane').hide();
    }

    $(document).on('click', '#longDescEditorTabs .nav-link', function (e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        $('#longDescEditorTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        if (tab === 'editor') {
            const html = $('#htmlLongDescSource').val();
            if (longDescQuill) longDescQuill.clipboard.dangerouslyPasteHTML(html || '');
            $('#longDescEditorPane').show();
            $('#longDescHtmlPane').hide();
            $('#longDescVistaPane').hide();
        } else if (tab === 'html') {
            if (longDescQuill) $('#htmlLongDescSource').val(longDescQuill.root.innerHTML);
            $('#longDescEditorPane').hide();
            $('#longDescHtmlPane').show();
            $('#longDescVistaPane').hide();
        } else {
            if (longDescQuill) $('#htmlLongDescSource').val(longDescQuill.root.innerHTML);
            const html = DOMPurify.sanitize($('#htmlLongDescSource').val() || '');
            $('#longDescVistaRender').html(html);
            $('#longDescEditorPane').hide();
            $('#longDescHtmlPane').hide();
            $('#longDescVistaPane').show();
        }
    });

    $('#btnEditLongDesc').on('click', function () {
        $('#longDescViewMode').hide();
        $('#longDescEditMode').show();
        longDescOpenEditor($('#long-description-source').val());
    });

    $('#btnCancelLongDesc').on('click', function () {
        $('#longDescEditMode').hide();
        $('#longDescViewMode').show();
    });

    $('#btnSaveLongDesc').on('click', function () {
        const activeTab = $('#longDescEditorTabs .nav-link.active').data('tab');
        let html = activeTab === 'html'
            ? $('#htmlLongDescSource').val()
            : (longDescQuill ? longDescQuill.root.innerHTML : $('#htmlLongDescSource').val());
        html = DOMPurify.sanitize(html || '');
        if (!html || html === '<p><br></p>') {
            toastr.warning('La descripción no puede estar vacía');
            return;
        }
        saveField('long_description', html, function () {
            $('#long-description-source').val(html);
            $('#long-description-render').html(html);
            $('#longDescEditMode').hide();
            $('#longDescViewMode').show();
        });
    });
});
</script>
@endpush
