@extends('layouts.theme')

@section('title', 'Analytics SEO')

@section('content')
    @include('core::components.card', ['title' => 'Analytics SEO'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Analytics SEO — Google Search Console</h5>
                        <p class="small mb-0 text-muted">Visualiza clics, impresiones y posiciones de tus páginas en Google</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('setting.seo.search-console.import') }}" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Importar datos
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total clics</h6>
                                <h4 class="mb-1 fw-bold" >{{ number_format($gscStats->total_clicks ?? 0) }}</h4>
                                <small class="text-muted">Clics registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total impresiones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($gscStats->total_impressions ?? 0) }}</h4>
                                <small class="text-muted">Impresiones registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Posición promedio</h6>
                                <h4 class="mb-1 fw-bold" >{{ number_format($gscStats->avg_position ?? 0, 1) }}</h4>
                                <small class="text-muted">Posición #1 = mejor</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Páginas con datos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($gscStats->pages_with_data ?? 0) }}</h4>
                                <small class="text-muted">Páginas en GSC</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Last updated notice --}}
            @if($lastUpdated)
                <div class="card-body border-bottom py-2">
                    <div class="alert alert-info border-0 py-2 mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <small>Datos actualizados: {{ \Carbon\Carbon::parse($lastUpdated)->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            @endif

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('setting.seo.analytics.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por título o URL..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('setting.seo.analytics.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($pages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Página</th>
                                    <th class="text-center">Clics</th>
                                    <th class="text-center">Impresiones</th>
                                    <th class="text-center">CTR</th>
                                    <th class="text-center">Posición</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    @php
                                        $ctr = $page->gsc_impressions > 0
                                            ? round($page->gsc_clicks / $page->gsc_impressions * 100, 2)
                                            : 0;
                                        $posColor = match(true) {
                                            ($page->gsc_position ?? 999) <= 3  => '#13C672',
                                            ($page->gsc_position ?? 999) <= 10 => '#90bb13',
                                            ($page->gsc_position ?? 999) <= 20 => '#FEC90F',
                                            default                            => '#FA896B',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="small fw-semibold">{{ Str::limit($page->title ?? 'Sin título', 50) }}</div>
                                            @if($page->canonical_url)
                                                <small class="text-muted text-truncate d-block" style="max-width:300px;">{{ $page->canonical_url }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-semibold" style="color:#90bb13;">{{ number_format($page->gsc_clicks ?? 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="small">{{ number_format($page->gsc_impressions ?? 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($ctr < 2 && $page->gsc_impressions > 100)
                                                <span class="badge bg-danger-subtle text-danger fw-semibold">{{ $ctr }}%</span>
                                            @else
                                                <span class="badge bg-light text-dark">{{ $ctr }}%</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill text-white fw-bold" style="background:{{ $posColor }};">
                                                #{{ number_format($page->gsc_position ?? 0, 1) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.edit', $page->id) }}">
                                                            Editar meta SEO
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <h6 class="mb-1">
                                @if(request('search'))
                                    No se encontraron páginas
                                @else
                                    Sin datos de Search Console
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Importa un CSV de Google Search Console para visualizar clics, impresiones y posiciones
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('setting.seo.search-console.import') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-upload me-1"></i> Importar datos de Search Console
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($pages->hasPages())
                <div class="card-footer">{{ $pages->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
