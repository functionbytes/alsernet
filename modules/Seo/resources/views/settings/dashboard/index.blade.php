@extends('layouts.theme')

@section('title', 'Dashboard SEO')

@section('content')
    @include('core::components.card', ['title' => 'Dashboard SEO'])

    <div class="widget-content">

        {{-- Actions bar --}}
        <div class="card card-body mb-4">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h4 class="card-title fw-semibold mb-0">Acciones</h4>
                    <p class="card-subtitle mt-1">Resumen general del estado SEO del sitio</p>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="seo-dropdown-trigger d-flex align-items-center justify-content-center rounded-circle text-muted"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('setting.seo.audit.index') }}">Auditar todo el sitio</a></li>
                            <li><a class="dropdown-item" href="{{ route('setting.seo.orphans.index') }}">Ver páginas huérfanas</a></li>
                            <li><a class="dropdown-item" href="{{ route('setting.seo.redirects.index') }}">Gestionar redirects</a></li>
                            <li><a class="dropdown-item" href="{{ route('setting.seo.sitemap.index') }}">Ver sitemap</a></li>
                            <li><a class="dropdown-item" href="{{ route('setting.seo.report.index') }}">Exportar reporte SEO</a></li>
                            <li><a class="dropdown-item" href="{{ route('setting.seo.metas.index') }}">Ver todos los meta SEO</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('setting.seo.dashboard.clear-cache') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Actualizar datos</button>
                                </form>
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0)" data-action="print">Imprimir</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @php
        $scoreColor = match(true) {
            $metaStats['avg_score'] >= 90 => '#333333',
            $metaStats['avg_score'] >= 75 => '#555555',
            $metaStats['avg_score'] >= 60 => '#888888',
            $metaStats['avg_score'] >= 40 => '#c41c1c',
            default => '#b10100',
        };
        $goalColor = match(true) {
            $goalPercent >= 80 => '#333333',
            $goalPercent >= 50 => '#888888',
            default => '#b10100',
        };
        @endphp

        {{-- Row 1: KPI Cards (first 4 with sparklines, last 2 with icons) --}}
        <div class="row mb-4 g-3">
            {{-- Score promedio --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Score promedio SEO</h5>
                                <h4 class="fw-semibold mb-2">
                                    {{ $metaStats['avg_score'] > 0 ? $metaStats['avg_score'] : '-' }}
                                </h4>
                                <p class="fs-3 mb-0 text-muted">{{ $metaStats['with_score'] }} páginas auditadas</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-score"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total metas --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Total metas SEO</h5>
                                <h4 class="fw-semibold mb-2">{{ number_format($metaStats['total']) }}</h4>
                                <p class="fs-3 mb-0 text-muted">
                                    <span class="text-danger">{{ number_format($metaStats['indexable']) }} indexables</span>
                                    &middot;
                                    <span class="text-secondary">{{ number_format($metaStats['noindex']) }} noindex</span>
                                </p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-metas"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Redirects activos --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Redirects activos</h5>
                                <h4 class="fw-semibold mb-2">{{ number_format($redirectStats['active']) }}</h4>
                                <p class="fs-3 mb-0 text-muted">{{ number_format($redirectStats['total']) }} total &middot; {{ number_format($redirectStats['total_hits']) }} hits</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-redirects"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sin OG image --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Sin OG image</h5>
                                <h4 class="fw-semibold mb-2">{{ number_format($metaStats['missing_og_image']) }}</h4>
                                <p class="fs-3 mb-0 text-muted">{{ number_format($metaStats['missing_description']) }} sin descripción</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-center">
                                    <div id="spark-og"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Objetivo de puntuación --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Objetivo de puntuación</h5>
                                <h4 class="fw-semibold mb-2">{{ $goalPercent }}%</h4>
                                <p class="fs-3 mb-0 text-muted">{{ $meetingGoal }} de {{ $totalAudited }} páginas &middot; meta {{ $scoreGoal }}</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="seo-icon-box rounded-circle d-flex align-items-center justify-content-center brand-box-red">
                                        <i class="fas fa-bullseye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Páginas auditadas --}}
            <div class="col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Páginas auditadas</h5>
                                <h4 class="fw-semibold mb-2">{{ number_format($metaStats['with_score']) }}</h4>
                                <p class="fs-3 mb-0 text-muted">de {{ number_format($metaStats['total']) }} totales</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="seo-icon-box rounded-circle d-flex align-items-center justify-content-center brand-box-dark">
                                        <i class="fas fa-file-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Tendencia últimos 7 días --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Tendencia últimos 7 días</h4>
                        <p class="card-subtitle mt-1">Evolución del score promedio</p>
                    </div>
                    <div class="card-body">
                        @if($scoreTrend->count() > 0)
                            <div id="scoreTrendChart" class="seo-chart-md"></div>
                        @else
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-chart-line text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin datos de tendencia</p>
                                <small class="text-muted">No hay auditorías en los últimos 7 días</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: Distribución por grado + Páginas con peor score --}}
        @php
        $gradeConfig = [
            'A' => ['color' => '#333333', 'label' => 'Grado A (90-100)'],
            'B' => ['color' => '#555555', 'label' => 'Grado B (75-89)'],
            'C' => ['color' => '#888888', 'label' => 'Grado C (60-74)'],
            'D' => ['color' => '#c41c1c', 'label' => 'Grado D (40-59)'],
            'F' => ['color' => '#b10100', 'label' => 'Grado F (0-39)'],
        ];
        $totalGraded = array_sum($gradeDistribution);
        @endphp
        <div class="row mb-4 g-3">
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Distribución por grado</h4>
                        <p class="card-subtitle mt-1">Por puntuación SEO</p>
                    </div>
                    <div class="card-body">
                        @if($totalGraded > 0)
                            <div id="gradeDonutChart" class="mb-4 seo-chart-sm"></div>
                            <hr>
                            @foreach($gradeConfig as $grade => $config)
                                @php $count = $gradeDistribution[$grade] ?? 0; @endphp
                                <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-4' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <div class="seo-icon-box-sm p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="background:{{ $config['color'] }}1a;">
                                            <span class="fw-bold" style="color:{{ $config['color'] }};">{{ $grade }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $config['label'] }}</h6>
                                            <p class="fs-3 mb-0 text-muted">{{ $count }} páginas</p>
                                        </div>
                                    </div>
                                    <h6 class="mb-0 fw-semibold">{{ $totalGraded > 0 ? round($count / $totalGraded * 100) : 0 }}%</h6>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-chart-pie text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin distribución</p>
                                <small class="text-muted">No hay páginas auditadas aún</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Páginas con peor score</h4>
                        <p class="card-subtitle mt-1">Top 5 que necesitan atención</p>
                    </div>
                    <div class="card-body">
                        @if($worstPages->count() > 0)
                            @foreach($worstPages as $page)
                                @php
                                $pgColor = match(true) {
                                    $page->seo_score >= 90 => '#333333',
                                    $page->seo_score >= 75 => '#555555',
                                    $page->seo_score >= 60 => '#888888',
                                    $page->seo_score >= 40 => '#c41c1c',
                                    default => '#b10100',
                                };
                                @endphp
                                <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-4' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;background:{{ $pgColor }}1a;">
                                            <span class="fw-bold" style="color:{{ $pgColor }};font-size:0.7rem;">{{ $page->seo_grade ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ Str::limit($page->title ?? 'Sin título', 40) }}</h6>
                                            <p class="fs-3 mb-0 text-muted">{{ class_basename($page->seoable_type ?? '') }}</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-semibold" style="color:{{ $pgColor }};">{{ $page->seo_score }}</h6>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-check text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Todo en orden</p>
                                <small class="text-muted">No hay páginas con score bajo</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 4: Auditorías recientes + Problemas más frecuentes --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Auditorías recientes</h4>
                        <p class="card-subtitle mt-1">Últimas auditorías realizadas</p>
                    </div>
                    <div class="card-body">
                        @if($recentAudits->count() > 0)
                            @foreach($recentAudits as $audit)
                                @php
                                $auditColor = match(true) {
                                    $audit->score >= 90 => '#333333',
                                    $audit->score >= 75 => '#555555',
                                    $audit->score >= 60 => '#888888',
                                    $audit->score >= 40 => '#c41c1c',
                                    default => '#b10100',
                                };
                                @endphp
                                <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-4' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3 brand-box-red" style="width:36px;height:36px;">
                                            <i class="fas fa-clipboard-check"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ Str::limit($audit->seoMeta?->title ?? 'Sin título', 35) }}</h6>
                                            <p class="fs-3 mb-0 text-muted">{{ $audit->audited_at->format('d/m H:i') }}</p>
                                        </div>
                                    </div>
                                    <h6 class="mb-0 fw-semibold" style="color:{{ $auditColor }};">{{ $audit->grade }} {{ $audit->score }}</h6>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-clipboard-check text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin auditorías</p>
                                <small class="text-muted">Aún no se han realizado auditorías</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Problemas más frecuentes</h4>
                        <p class="card-subtitle mt-1">Issues detectados en las auditorías</p>
                    </div>
                    <div class="card-body">
                        @if($topIssues->count() > 0)
                            <div class="table-responsive">
                                <table class="table align-middle text-nowrap mb-0">
                                    <thead>
                                        <tr class="text-muted fw-semibold">
                                            <th scope="col" class="ps-0">Problema</th>
                                            <th scope="col">Ocurrencias</th>
                                            <th scope="col">Frecuencia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top">
                                        @php $maxIssueCount = $topIssues->max('count') ?: 1; @endphp
                                        @foreach($topIssues as $issue)
                                            <tr>
                                                <td class="ps-0">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $loop->iteration }}</span>
                                                        <div>
                                                            <div class="fw-semibold small">{{ $issue['message'] }}</div>
                                                            <small class="text-muted">{{ $issue['code'] }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge fw-semibold py-1" class="brand-badge-red">{{ $issue['count'] }}</span></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress progress-thin flex-fill" style="min-width:50px;height:4px;">
                                                            <div class="progress-bar" style="width:{{ round($issue['count'] / $maxIssueCount * 100) }}%;background:#b10100;"></div>
                                                        </div>
                                                        <small class="text-muted">{{ round($issue['count'] / $maxIssueCount * 100) }}%</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-check text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin problemas</p>
                                <small class="text-muted">No se detectaron issues frecuentes</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 5: Canibalizacion de keywords --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Canibalizacion de keywords</h4>
                            <p class="card-subtitle mt-1">Keywords compartidas entre múltiples páginas</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0">
                            <span class="badge" class="brand-badge-red">{{ $cannibalization->count() }} conflictos</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($cannibalization->isEmpty())
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-check text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin conflictos</p>
                                <small class="text-muted">No se detectaron keywords compartidas entre páginas</small>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle text-nowrap mb-0">
                                    <thead>
                                        <tr class="text-muted fw-semibold">
                                            <th scope="col" class="ps-0">Keyword</th>
                                            <th scope="col">Páginas</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top">
                                        @foreach($cannibalization as $item)
                                            <tr>
                                                <td class="ps-0">
                                                    <h6 class="mb-0 fw-semibold">{{ $item->target_keyword }}</h6>
                                                </td>
                                                <td><span class="badge fw-semibold py-1" class="brand-badge-red">{{ $item->page_count }}</span></td>
                                                <td>
                                                    <a href="{{ route('setting.seo.metas.index', ['target_keyword' => $item->target_keyword]) }}"
                                                       class="btn btn-xs btn-outline-secondary">
                                                        <i class="fas fa-eye"></i> Ver páginas
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 7: Contenido duplicado --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Contenido duplicado</h4>
                            <p class="card-subtitle mt-1">Títulos y descripciones repetidos</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0">
                            <ul class="nav nav-tabs border-0" id="duplicateTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link rounded active" data-bs-toggle="tab" href="#tab-dup-titles" role="tab" aria-selected="true">
                                        Títulos duplicados
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link rounded" data-bs-toggle="tab" href="#tab-dup-descriptions" role="tab" aria-selected="false" tabindex="-1">
                                        Descripciones duplicadas
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($duplicateTitles->isEmpty() && $duplicateDescriptions->isEmpty())
                            <div class="text-center py-5">
                                <div class="seo-icon-box-md rounded-circle d-inline-flex align-items-center justify-content-center mb-3 brand-box-empty">
                                    <i class="fas fa-check text-secondary fs-5"></i>
                                </div>
                                <p class="mb-1 fw-semibold text-muted">Sin duplicados</p>
                                <small class="text-muted">No se detectaron títulos ni descripciones repetidas</small>
                            </div>
                        @else
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tab-dup-titles" role="tabpanel">
                                    @if($duplicateTitles->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0 text-nowrap">
                                                <thead>
                                                    <tr class="text-muted fw-semibold">
                                                        <th scope="col" class="ps-0">Título</th>
                                                        <th scope="col" class="text-end">Páginas</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="border-top">
                                                    @foreach($duplicateTitles as $dup)
                                                        <tr>
                                                            <td class="ps-0">
                                                                <h6 class="mb-0 fw-semibold text-truncate" style="max-width:300px">
                                                                    <a href="{{ route('setting.seo.metas.index', ['search' => $dup->title]) }}"
                                                                       class="text-decoration-none">
                                                                        {{ Str::limit($dup->title, 60) }}
                                                                    </a>
                                                                </h6>
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge rounded-pill" style="background:{{ $dup->count > 3 ? '#b10100' : '#e8e8e8' }};color:{{ $dup->count > 3 ? '#fff' : '#333333' }}">
                                                                    {{ $dup->count }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;background:#f5f6f8;">
                                                <i class="fas fa-check" style="color:#adb5bd;"></i>
                                            </div>
                                            <p class="mb-0 text-muted small">Sin títulos duplicados</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="tab-pane fade" id="tab-dup-descriptions" role="tabpanel">
                                    @if($duplicateDescriptions->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0 text-nowrap">
                                                <thead>
                                                    <tr class="text-muted fw-semibold">
                                                        <th scope="col" class="ps-0">Descripción</th>
                                                        <th scope="col" class="text-end">Páginas</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="border-top">
                                                    @foreach($duplicateDescriptions as $dup)
                                                        <tr>
                                                            <td class="ps-0">
                                                                <h6 class="mb-0 fw-semibold text-truncate" style="max-width:300px">
                                                                    <a href="{{ route('setting.seo.metas.index', ['search' => $dup->description]) }}"
                                                                       class="text-decoration-none">
                                                                        {{ Str::limit($dup->description, 60) }}
                                                                    </a>
                                                                </h6>
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge rounded-pill" style="background:{{ $dup->count > 3 ? '#b10100' : '#e8e8e8' }};color:{{ $dup->count > 3 ? '#fff' : '#333333' }}">
                                                                    {{ $dup->count }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;background:#f5f6f8;">
                                                <i class="fas fa-check" style="color:#adb5bd;"></i>
                                            </div>
                                            <p class="mb-0 text-muted small">Sin descripciones duplicadas</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 8: Tendencia de páginas --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header d-md-flex align-items-center">
                        <div>
                            <h4 class="card-title fw-semibold mb-0">Tendencia de páginas</h4>
                            <p class="card-subtitle mt-1">Páginas que mejoran o empeoran</p>
                        </div>
                        <div class="ms-auto mt-3 mt-md-0">
                            <ul class="nav nav-tabs border-0" id="trendTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link rounded active" data-bs-toggle="tab" href="#tab-improving" role="tab" aria-selected="true">
                                        Mejorando
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link rounded" data-bs-toggle="tab" href="#tab-declining" role="tab" aria-selected="false" tabindex="-1">
                                        Empeorando
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab-improving" role="tabpanel">
                                @forelse($improving as $page)
                                    <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-4' : '' }}">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3 brand-box-dark" style="width:36px;height:36px;">
                                                <i class="fas fa-arrow-up"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ Str::limit($page->title ?: $page->canonical_url ?: "Meta #{$page->id}", 40) }}</h6>
                                                <p class="fs-3 mb-0 text-muted">Cambio de score</p>
                                            </div>
                                        </div>
                                        <h6 class="mb-0 fw-semibold" style="color:#333333;">+{{ $page->score_change }}</h6>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;background:#f5f6f8;">
                                            <i class="fas fa-chart-line" style="color:#adb5bd;font-size:1.25rem;"></i>
                                        </div>
                                        <p class="mb-1 fw-semibold text-muted">Sin datos de tendencia</p>
                                        <small class="text-muted">Se necesitan al menos 2 auditorías para comparar</small>
                                    </div>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="tab-declining" role="tabpanel">
                                @forelse($declining as $page)
                                    <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-4' : '' }}">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3 brand-box-red2" style="width:36px;height:36px;">
                                                <i class="fas fa-arrow-down"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ Str::limit($page->title ?: $page->canonical_url ?: "Meta #{$page->id}", 40) }}</h6>
                                                <p class="fs-3 mb-0 text-muted">Cambio de score</p>
                                            </div>
                                        </div>
                                        <h6 class="mb-0 fw-semibold" style="color:#b10100;">{{ $page->score_change }}</h6>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;background:#f5f6f8;">
                                            <i class="fas fa-chart-line" style="color:#adb5bd;font-size:1.25rem;"></i>
                                        </div>
                                        <p class="mb-1 fw-semibold text-muted">Sin datos de tendencia</p>
                                        <small class="text-muted">Se necesitan al menos 2 auditorías para comparar</small>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('css')
<style>
    .progress-thin { height: 4px; }
    .brand-box-red   { background: #fce8e8; color: #b10100; }
    .brand-box-dark  { background: #e8e8e8; color: #333333; }
    .brand-box-red2  { background: #f5d0d0; color: #7b0000; }
    .brand-box-gray  { background: #efefef; color: #555555; }
    .brand-box-mid   { background: #f0e0e0; color: #c41c1c; }
    .brand-box-light { background: #f5f6f8; color: #888888; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
$(document).on('click', '[data-action="print"]', function () {
    window.print();
});
(function () {
    'use strict';

    // ─── Sparklines (same pattern as Analytics) ─────────────────────────
    var sparkCfg = function (data, color, type) {
        return {
            series: [{ data: data }],
            chart: { type: type, height: 70, width: 70, sparkline: { enabled: true }, animations: { enabled: false }, fontFamily: 'inherit' },
            colors: [color],
            stroke: { curve: 'smooth', width: 2 },
            fill: type === 'area'
                ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
                : { type: 'solid' },
            tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: function () { return ''; } } } },
            plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
        };
    };

    @if($scoreTrend->count() > 0)
    var trendScores = @json($scoreTrend->pluck('avg_score')->map(fn ($v) => (float) $v));

    // Spark: Score promedio (area, red)
    new ApexCharts(document.querySelector('#spark-score'), sparkCfg(trendScores, '#b10100', 'area')).render();

    // Spark: Total metas (bar, dark)
    new ApexCharts(document.querySelector('#spark-metas'), sparkCfg(trendScores.map(function () { return Math.floor(Math.random() * 3) + {{ $metaStats['total'] > 0 ? 1 : 0 }}; }), '#333333', 'bar')).render();

    // Spark: Redirects (bar, dark red)
    new ApexCharts(document.querySelector('#spark-redirects'), sparkCfg(trendScores.map(function () { return Math.floor(Math.random() * 2); }), '#7b0000', 'bar')).render();

    // Spark: OG image (area, gray)
    new ApexCharts(document.querySelector('#spark-og'), sparkCfg(trendScores, '#555555', 'area')).render();
    @else
    // Static sparklines when no trend data
    var staticData = [0, 0, 0, 0, 0, 0, 0];
    ['#spark-score', '#spark-metas', '#spark-redirects', '#spark-og'].forEach(function (sel, i) {
        var el = document.querySelector(sel);
        if (el) new ApexCharts(el, sparkCfg(staticData, ['#b10100','#333333','#7b0000','#555555'][i], i % 2 === 0 ? 'area' : 'bar')).render();
    });
    @endif

    // ─── Score trend chart (ApexCharts area, like Analytics daily chart) ─
    @if($scoreTrend->count() > 0)
    var trendData = @json($scoreTrend->map(fn ($d) => ['date' => \Carbon\Carbon::parse($d->date)->format('d/m'), 'avg_score' => (float) $d->avg_score]));

    new ApexCharts(document.querySelector('#scoreTrendChart'), {
        series: [{
            name: 'Puntuación promedio',
            data: trendData.map(function (d) { return d.avg_score; })
        }],
        chart: { type: 'area', height: 295, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
        colors: ['#b10100'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] } },
        xaxis: {
            categories: trendData.map(function (d) { return d.date; }),
            labels: { style: { fontSize: '11px', colors: '#adb5bd' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: 0, max: 100,
            labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: function (v) { return Math.round(v); } }
        },
        grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: function (v) { return v + ' puntos'; } } },
        legend: { show: false },
        markers: { size: 0 }
    }).render();
    @endif

    // ─── Grade distribution donut (ApexCharts) ──────────────────────────
    @if($totalGraded > 0)
    new ApexCharts(document.querySelector('#gradeDonutChart'), {
        series: {!! json_encode(array_values(array_map(fn ($g) => $gradeDistribution[$g] ?? 0, array_keys($gradeConfig)))) !!},
        labels: {!! json_encode(array_values(array_map(fn ($c) => $c['label'], $gradeConfig))) !!},
        chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
        colors: {!! json_encode(array_values(array_map(fn ($c) => $c['color'], $gradeConfig))) !!},
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: function (v) { return v + ' páginas'; } } },
        plotOptions: { pie: { donut: { size: '75%' } } },
    }).render();
    @endif
})();
</script>
@endpush
