@extends('layouts.theme')

@section('page_title', __('template::template.view'))

@section('page_header')
    @include('core::components.card', ['title' => $template->name])
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="row g-4">
                {{-- Contenido Principal --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h3 class="card-title">{{ $template->name }}</h3>
                        </div>

                        <div class="card-body">
                            {{-- Metadatos --}}
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <small class="text-muted">{{ __('template::template.slug') }}</small>
                                        <p class="font-monospace">{{ $template->slug }}</p>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <small class="text-muted">{{ __('template::template.author') }}</small>
                                        <p>{{ $template->author ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <small class="text-muted">{{ __('template::template.status') }}</small>
                                        <p>
                                            @if($template->isActive())
                                                <span class="badge bg-success">{{ __('template::template.active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('template::template.inactive') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <small class="text-muted">{{ __('template::template.version') }}</small>
                                        <p>{{ $template->version }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Descripción --}}
                            @if($template->description)
                                <div class="mb-4">
                                    <small class="text-muted">{{ __('template::template.description') }}</small>
                                    <p>{{ $template->description }}</p>
                                </div>
                            @endif

                            {{-- Contenido --}}
                            <div class="mb-4">
                                <small class="text-muted">{{ __('template::template.content') }}</small>
                                <div class="card bg-light">
                                    <div class="card-body font-monospace small" style="max-height: 400px; overflow-y: auto;">
                                        <pre>{{ $template->content }}</pre>
                                    </div>
                                </div>
                            </div>

                            {{-- Herencia --}}
                            @if($template->inherit)
                                <div class="mb-4">
                                    <small class="text-muted">{{ __('template::template.inherit') }}</small>
                                    <p>
                                        <a href="{{ route('settings.templates.show', $template->parent) }}">
                                            {{ $template->parent->name ?? $template->inherit }}
                                        </a>
                                    </p>
                                </div>
                            @endif

                            {{-- Información de creación --}}
                            <div class="alert alert-light mb-0">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-2"></i>
                                    Creado: {{ $template->created_at->format('d/m/Y H:i') }} |
                                    <i class="fas fa-sync me-2"></i>
                                    Actualizado: {{ $template->updated_at->format('d/m/Y H:i') }}
                                    @if($template->user)
                                        | Por: {{ $template->user->name }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Barra Lateral --}}
                <div class="col-lg-4">
                    {{-- Acciones --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="btn-list">
                                <a href="{{ route('settings.templates.edit', $template) }}"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-edit me-2"></i>{{ __('template::template.edit') }}
                                </a>

                                <a href="{{ route('settings.templates.index') }}"
                                   class="btn btn-secondary w-100">
                                    {{ __('core::core.back') }}
                                </a>

                                <form action="{{ route('settings.templates.destroy', $template) }}"
                                      method="POST" class="needs-confirm"
                                      data-confirm-msg="{{ __('template::template.confirm_delete') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>{{ __('template::template.delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Versiones --}}
                    @if($template->hasVersions())
                        <div class="card">
                            <div class="card-header border-bottom">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>Versiones
                                </h5>
                            </div>

                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Total: <strong>{{ $template->getTotalVersions() }}</strong> versiones
                                </p>

                                @if($versions && $versions->count() > 0)
                                    <div class="list-group list-group-sm">
                                        @foreach($versions->take(5) as $version)
                                            <a href="{{ route('settings.templates.versions.show', [$template, $version->version]) }}"
                                               class="list-group-item list-group-item-action">
                                                <div class="d-flex justify-content-between">
                                                    <span>v{{ $version->version }}</span>
                                                    <small class="text-muted">
                                                        {{ $version->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>

                                    @if($versions->count() > 5)
                                        <a href="{{ route('settings.templates.versions.index', $template) }}"
                                           class="btn btn-outline-secondary btn-sm w-100 mt-3">
                                            {{ __('template::template.view') }} todas las versiones
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
