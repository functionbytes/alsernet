@extends('layouts.theme')

@section('page_title', "Versión " . $templateVersion->version)

@section('content')
    @include('core::components.card', ['title' => "Versión " . $templateVersion->version])

    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h3 class="card-title">
                                <i class="fas fa-code-branch me-2"></i>v{{ $templateVersion->version }}
                            </h3>
                        </div>

                        <div class="card-body">
                            {{-- Metadatos --}}
                            <div class="row mb-4 pb-4 border-bottom">
                                <div class="col-sm-6">
                                    <small class="text-muted">Creado</small>
                                    <p>{{ $templateVersion->created_at->format('d/m/Y H:i:s') }}</p>
                                </div>

                                <div class="col-sm-6">
                                    <small class="text-muted">Por</small>
                                    <p>
                                        @if($templateVersion->createdBy)
                                            {{ $templateVersion->createdBy->name }}
                                        @else
                                            Sistema
                                        @endif
                                    </p>
                                </div>

                                <div class="col-sm-6">
                                    <small class="text-muted">Nombre del Template</small>
                                    <p>{{ $templateVersion->name }}</p>
                                </div>

                                <div class="col-sm-6">
                                    <small class="text-muted">Slug</small>
                                    <p class="font-monospace">{{ $templateVersion->slug }}</p>
                                </div>
                            </div>

                            {{-- Contenido --}}
                            <div>
                                <h5 class="mb-3">Contenido</h5>
                                <div class="card bg-light">
                                    <div class="card-body font-monospace small" style="max-height: 500px; overflow-y: auto;">
                                        <pre>{{ $templateVersion->content }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header border-bottom">
                            <h5 class="card-title">Acciones</h5>
                        </div>

                        <div class="card-body">
                            <div class="btn-list">
                                @if($templateVersion->version !== $template->getCurrentVersionNumber())
                                    <form action="{{ route('settings.templates.versions.restore', [$template, $templateVersion->version]) }}"
                                          method="POST" class="needs-confirm"
                                          data-confirm-msg="¿Restaurar esta versión? Se creará un snapshot de la versión actual.">
                                        @csrf
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="fas fa-undo me-2"></i>{{ __('template::template.restore') }}
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Esta es la versión actual
                                    </div>
                                @endif

                                <a href="{{ route('settings.templates.versions.index', $template) }}"
                                   class="btn btn-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Volver al historial
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title">{{ $template->name }}</h5>
                        </div>

                        <div class="card-body">
                            <small class="text-muted">Template actual</small>
                            <p>
                                <a href="{{ route('settings.templates.show', $template) }}">
                                    {{ $template->name }}
                                </a>
                            </p>

                            <small class="text-muted">Versión actual</small>
                            <p>v{{ $template->getCurrentVersionNumber() }}</p>

                            <small class="text-muted">Total de versiones</small>
                            <p>{{ $template->getTotalVersions() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
