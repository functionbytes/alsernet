@extends('layouts.theme')

@section('title', 'Sitemap XML')

@section('content')
    @include('core::components.card', ['title' => 'Sitemap XML'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Sitemap XML</h5>
                        <p class="small mb-0 text-muted">Gestiona y visualiza los mapas del sitio de tu web para los motores de búsqueda</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#clear-cache-modal">
                            <i class="fas fa-sync me-1"></i> Limpiar caché
                        </button>
                        <button type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#generate-modal">
                            <i class="fas fa-cog me-1"></i> Regenerar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Sitemaps disponibles</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($sitemaps) }}</h4>
                                        <small class="text-muted">Tipos de sitemap</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Caché</h6>
                                        <h4 class="mb-1 fw-bold">
                                            @if($cacheEnabled)
                                                <span class="badge bg-success-subtle text-white fs-6">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary fs-6">Inactivo</span>
                                            @endif
                                        </h4>
                                        <small class="text-muted">{{ $cacheDuration }} seg duración</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">Archivo XML</h6>
                                        <h4 class="mb-1 fw-bold">
                                            @if($fileExists)
                                                <span class="badge bg-success-subtle text-white fs-6">Generado</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning fs-6">Pendiente</span>
                                            @endif
                                        </h4>
                                        <small class="text-muted">public/sitemap.xml</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Última generación</h6>
                                        <h4 class="mb-1 fw-bold" style="font-size: 1rem;">
                                            {{ $lastModified ? \Carbon\Carbon::createFromTimestamp($lastModified)->format('d/m/Y H:i') : 'Nunca' }}
                                        </h4>
                                        <small class="text-muted">Fecha de generación</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sitemaps List -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Sitemaps disponibles</h6>
                        <p class="text-muted small mb-0">Visualiza y accede a cada uno de los mapas del sitio generados</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Información:</small>
                            <small class="d-block">Los sitemaps se actualizan automáticamente o puedes regenerarlos manualmente.</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>URL</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sitemaps as $sitemap)
                                <tr>
                                    <td>
                                        <i class="{{ $sitemap['icon'] }} me-2 text-primary"></i>
                                        <span class="fw-semibold">{{ $sitemap['name'] }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $sitemap['description'] }}</small>
                                    </td>
                                    <td>
                                        <code class="bg-light rounded px-2 py-1 small text-break">{{ $sitemap['url'] }}</code>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ $sitemap['url'] }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Ver XML
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal limpiar caché --}}
    <div id="clear-cache-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-sync fa-3x text-primary"></i>
                    </div>
                    <h4 class="mb-2">Limpiar caché del sitemap</h4>
                    <p class="text-muted mb-4">
                        Se limpiará toda la caché del sitemap almacenada. El sitemap se volverá a cachear automáticamente cuando se acceda.
                    </p>
                    <form method="POST" action="{{ route('setting.seo.sitemap.clear-cache') }}">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync me-2"></i>Confirmar y limpiar caché
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal regenerar sitemap --}}
    <div id="generate-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-cog fa-3x text-primary"></i>
                    </div>
                    <h4 class="mb-2">Regenerar sitemap</h4>
                    <p class="text-muted mb-4">
                        Se regenerará el archivo sitemap.xml con el contenido actual del sitio. Este proceso puede tardar unos segundos.
                    </p>
                    <form method="POST" action="{{ route('setting.seo.sitemap.generate') }}">
                        @csrf
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-cog me-2"></i>Confirmar y regenerar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
