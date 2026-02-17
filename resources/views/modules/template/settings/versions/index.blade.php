@extends('layouts.theme')

@section('page_title', __('template::template.version_history'))

@section('content')
    @include('core::components.card', ['title' => __('template::template.version_history')])

    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom d-flex align-items-center">
                            <h3 class="card-title">
                                <i class="fas fa-history me-2"></i>{{ __('template::template.version_history') }}
                            </h3>
                            <span class="ms-auto badge bg-blue-lt">{{ $template->getTotalVersions() }} versiones</span>
                        </div>

                        <div class="card-body">
                            @if($versions && $versions->count() > 0)
                                <div class="timeline">
                                    @foreach($versions as $version)
                                        <div class="timeline-event">
                                            <div class="timeline-event-icon bg-blue">
                                                <i class="fas fa-code-branch"></i>
                                            </div>
                                            <div class="timeline-event-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h4 class="mb-1">
                                                            Versión <strong>{{ $version->version }}</strong>
                                                            @if($version->version === $template->getCurrentVersionNumber())
                                                                <span class="badge bg-green">Actual</span>
                                                            @endif
                                                        </h4>
                                                        <p class="text-muted mb-2">
                                                            <i class="fas fa-clock me-1"></i>
                                                            {{ $version->created_at->format('d/m/Y H:i') }}
                                                            @if($version->createdBy)
                                                                por <strong>{{ $version->createdBy->name }}</strong>
                                                            @endif
                                                        </p>

                                                        @if($version->description)
                                                            <p class="text-muted small mb-2">
                                                                {{ $version->description }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="btn-list gap-1">
                                                        <a href="{{ route('settings.templates.versions.show', [$template, $version->version]) }}"
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>Ver
                                                        </a>

                                                        @if($version->version !== $template->getCurrentVersionNumber())
                                                            <form action="{{ route('settings.templates.versions.restore', [$template, $version->version]) }}"
                                                                  method="POST" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                                                        onclick="return confirm('¿Restaurar esta versión?')">
                                                                    <i class="fas fa-undo me-1"></i>Restaurar
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Paginación --}}
                                <div class="d-flex justify-content-center">
                                    {{ $versions->links() }}
                                </div>
                            @else
                                <div class="empty">
                                    <div class="empty-header">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <p class="empty-title">Sin versiones</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h3 class="card-title">{{ $template->name }}</h3>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta es la página de historial de versiones de la plantilla.
                            </p>

                            <div class="btn-list">
                                <a href="{{ route('settings.templates.edit', $template) }}"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </a>

                                <a href="{{ route('settings.templates.show', $template) }}"
                                   class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
