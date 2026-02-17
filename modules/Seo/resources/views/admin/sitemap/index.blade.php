@extends('layouts.theme')

@section('page_title', __('seo::sitemap.admin_title'))

@section('content')
    <div class="row g-4">
        {{-- Columna principal --}}
        <div class="col-lg-8">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">{{ __('seo::sitemap.admin_title') }}</h5>
                    <p class="text-muted small mb-0">{{ __('seo::sitemap.admin_description') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('setting.seo.sitemap.generate') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>{{ __('seo::sitemap.regenerate') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('setting.seo.sitemap.clear-cache') }}">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-trash-alt me-2"></i>{{ __('seo::sitemap.clear_cache') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Sitemaps Grid --}}
            <div class="row g-3">
                @foreach ($sitemaps as $sitemap)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-1">
                                    <i class="{{ $sitemap['icon'] }} me-2 text-primary"></i>{{ $sitemap['name'] }}
                                </h6>
                                <p class="small text-muted mb-3">{{ $sitemap['description'] }}</p>
                                <div class="bg-light rounded p-2 mb-3">
                                    <code class="small text-break">{{ $sitemap['url'] }}</code>
                                </div>
                                <a href="{{ $sitemap['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>{{ __('seo::sitemap.view_sitemap') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            {{-- Estado --}}
            <div class="card mb-4">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-semibold mb-0">{{ __('seo::sitemap.status') }}</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="mb-0">
                        <dt class="small text-muted fw-semibold mb-1">{{ __('seo::sitemap.cache_enabled') }}</dt>
                        <dd class="mb-3">
                            <span class="badge {{ $cacheEnabled ? 'bg-success' : 'bg-danger' }}">
                                {{ $cacheEnabled ? __('seo::sitemap.yes') : __('seo::sitemap.no') }}
                            </span>
                        </dd>

                        <dt class="small text-muted fw-semibold mb-1">{{ __('seo::sitemap.cache_duration') }}</dt>
                        <dd class="mb-3">{{ $cacheDuration }} {{ __('seo::sitemap.seconds') }}</dd>

                        <dt class="small text-muted fw-semibold mb-1">{{ __('seo::sitemap.file_exists') }}</dt>
                        <dd class="{{ $lastModified ? 'mb-3' : 'mb-0' }}">
                            <span class="badge {{ $fileExists ? 'bg-success' : 'bg-warning' }}">
                                {{ $fileExists ? __('seo::sitemap.yes') : __('seo::sitemap.no') }}
                            </span>
                        </dd>

                        @if ($fileExists && $lastModified)
                            <dt class="small text-muted fw-semibold mb-1">{{ __('seo::sitemap.last_generated') }}</dt>
                            <dd class="mb-0">{{ \Carbon\Carbon::createFromTimestamp($lastModified)->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Mensajes de sesion --}}
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>
@endsection
