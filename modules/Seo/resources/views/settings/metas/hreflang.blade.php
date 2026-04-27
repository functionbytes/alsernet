@extends('layouts.theme')

@section('title', 'Gestión de hreflang')

@section('content')
    @include('core::components.card', ['title' => 'Gestión de hreflang'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda: contenido principal --}}
        <div class="col-lg-8">

            <div class="card">

                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Gestión de hreflang</h5>
                    <p class="small mb-0 text-muted">Administra las etiquetas de idioma alternativo agrupadas por locale</p>
                </div>

                <div class="card-body">

                    {{-- Sin locale configurado --}}
                    @if($withoutLocale > 0)
                        <div class="alert alert-warning border-0 bg-warning-subtle mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <div>
                                    <strong>{{ number_format($withoutLocale) }} páginas sin idioma configurado.</strong>
                                    <a href="{{ route('settings.seo.metas.index') }}" class="ms-1 alert-link">Ver todas las metas</a> para asignarles un locale.
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Conflictos --}}
                    @if(!empty($conflicts))
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Conflictos detectados</h6>
                            <p class="text-muted mb-3">
                                URLs duplicadas con el mismo idioma que pueden causar problemas de indexación.
                                <span class="ms-1">
                                    <span class="badge bg-danger-subtle text-danger">{{ count($conflicts) }} conflicto(s)</span>
                                </span>
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>URL duplicada</th>
                                            <th class="text-center">Entradas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($conflicts as $conflict)
                                            <tr>
                                                <td><code class="text-danger">{{ $conflict['path'] ?: '(vacío)' }}</code></td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">{{ $conflict['count'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="my-4">
                    @endif

                    {{-- Metas agrupadas por locale --}}
                    @forelse($grouped as $locale => $items)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">
                                Idioma: <span class="badge bg-primary ms-1">{{ $locale }}</span>
                            </h6>
                            <p class="text-muted mb-3">
                                {{ $items->count() }} página(s) configuradas con este locale.
                                <span class="ms-1">
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                </span>
                            </p>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Título</th>
                                            <th>URL canónica</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $meta)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ class_basename($meta->seoable_type) }}</span>
                                                    <small class="text-muted ms-1">#{{ $meta->seoable_id }}</small>
                                                </td>
                                                <td>
                                                    <span class="small">{!! $meta->title ?: '<em class="text-muted">Sin título</em>' !!}</span>
                                                </td>
                                                <td>
                                                    @if($meta->canonical_url)
                                                        <code class="small text-primary">{{ $meta->canonical_url }}</code>
                                                    @else
                                                        <span class="text-muted"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Sin URL canónica</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('settings.seo.metas.edit', $meta) }}">
                                                                    Editar
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
                        </div>

                        @if(!$loop->last)
                            <hr class="my-4">
                        @endif
                    @empty
                        <div class="text-center py-5">
                            <h6 class="mb-1">No hay páginas con locale configurado</h6>
                            <p class="text-muted mb-3">Asigna un idioma (hreflang) a tus páginas SEO para configurar etiquetas de idioma alternativo</p>
                            <a href="{{ route('settings.seo.metas.index') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-list me-1"></i> Ver todas las metas SEO
                            </a>
                        </div>
                    @endforelse

                </div>

            </div>

        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Con locale asignado</span>
                        <span class="fw-bold">{{ $metas->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Sin locale</span>
                        <span class="fw-bold {{ $withoutLocale > 0 ? 'text-warning' : 'text-success' }}">{{ $withoutLocale }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Idiomas distintos</span>
                        <span class="fw-bold text-primary">{{ $grouped->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Conflictos</span>
                        <span class="fw-bold {{ count($conflicts) > 0 ? 'text-danger' : 'text-success' }}">{{ count($conflicts) }}</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div>
                        <h6 class="mb-1 fw-semibold">Metas SEO</h6>
                        <p class="text-muted mb-0">
                            Administra los títulos, descripciones y configuración SEO de todas las páginas.
                        </p>
                    </div>
                    <a href="{{ route('settings.seo.metas.index') }}" class="btn btn-primary w-100 mt-2">Ver metas</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas prácticas hreflang</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Usa códigos de idioma BCP 47: <code>es</code>, <code>en</code>, <code>es-MX</code>, <code>pt-BR</code>.</li>
                        <li class="mb-2">Cada página debe tener su propia URL canónica única.</li>
                        <li class="mb-2">Si tienes una página universal, usa <code>x-default</code> como locale.</li>
                        <li class="mb-2">Las etiquetas hreflang deben ser recíprocas: si A apunta a B, B debe apuntar a A.</li>
                        <li>Evita más de una entrada con el mismo idioma apuntando a la misma URL.</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>
@endsection
