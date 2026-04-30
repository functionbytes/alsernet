@extends('layouts.theme')

@section('title', 'Reporte SEO')

@section('page_header')
    @include('core::components.card', ['title' => 'Reporte SEO'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="row g-4 align-items-start">

            {{-- Columna izquierda --}}
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-body">

                        {{-- Resumen --}}
                        <h6 class="fw-bold text-dark mb-1">Resumen general</h6>
                        <p class="text-muted mb-3">Estado actual de las metas SEO y redirecciones del sitio.</p>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-2">Total metas SEO</h6>
                                        <h4 class="mb-0 fw-bold">{{ number_format($stats['total_metas']) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                @php
                                $scoreColor = match(true) {
                                    $stats['avg_score'] >= 90 => '#13C672',
                                    $stats['avg_score'] >= 75 => '#b10100',
                                    $stats['avg_score'] >= 60 => '#FEC90F',
                                    $stats['avg_score'] >= 40 => '#FA896B',
                                    default => '#dc3545',
                                };
                                @endphp
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-2">Score promedio</h6>
                                        <h4 class="mb-0 fw-bold" >
                                            {{ $stats['avg_score'] > 0 ? $stats['avg_score'] : '-' }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card bg-light-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-2">Total redirects</h6>
                                        <h4 class="mb-0 fw-bold">{{ number_format($stats['total_redirects']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <hr class="my-0">

                    {{-- Exportar datos --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Exportar datos</h6>
                        <p class="text-muted mb-3">Descarga los datos SEO del sitio en diferentes formatos.</p>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                                    <div>
                                        <div class="fw-semibold">Metas SEO (CSV)</div>
                                        <small class="text-muted">Título, descripción, keywords, Open Graph, Twitter Card, canonical, robots y score.</small>
                                    </div>
                                    <a href="{{ route('settings.seo.report.export') }}" class="btn btn-outline-primary btn-sm flex-shrink-0">
                                        Descargar
                                    </a>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                                    <div>
                                        <div class="fw-semibold">Reporte completo (CSV)</div>
                                        <small class="text-muted">Metas SEO + redirecciones activas e inactivas con estadísticas de uso.</small>
                                    </div>
                                    <a href="{{ route('settings.seo.report.export', ['format' => 'full']) }}" class="btn btn-outline-primary btn-sm flex-shrink-0">
                                        Descargar
                                    </a>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                                    <div>
                                        <div class="fw-semibold">Reporte PDF</div>
                                        <small class="text-muted">Resumen con tabla de páginas y código de colores por grado SEO.</small>
                                    </div>
                                    <a href="{{ route('settings.seo.report.export-pdf') }}" class="btn btn-outline-primary btn-sm flex-shrink-0">
                                        Descargar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Columna derecha --}}
            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Compatibilidad</h6>
                        <p class="text-muted mb-0">Los archivos CSV se exportan con BOM UTF-8 para garantizar la correcta visualización de caracteres especiales en Microsoft Excel.</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Metas SEO</h6>
                        <p class="text-muted mb-3">Administra las metas SEO de todas las páginas del sitio.</p>
                        <a href="{{ route('settings.seo.metas.index') }}" class="btn btn-outline-secondary w-100">
                            Ver metas SEO
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Dashboard SEO</h6>
                        <p class="text-muted mb-3">Vuelve al panel principal de SEO.</p>
                        <a href="{{ route('settings.seo.dashboard') }}" class="btn btn-outline-secondary w-100">
                            Ir al dashboard
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

@endsection
