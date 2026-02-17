@extends('layouts.theme')

@section('page_title', __('template::template.templates'))

@section('content')
    @include('core::components.card', ['title' => __('template::template.templates')])

    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ __('template::template.templates') }}</h5>
                        <p class="small mb-0 text-muted">{{ __('template::template.list') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                            Importar ZIP
                        </button>
                        <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                            {{ __('template::template.create_new') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Grid 3 columnas (Mercosan Theme pattern) --}}
            <div class="card-body">
                @if(count($templates) > 0)
                    <div class="row row-cards">
                        @foreach($templates as $slug => $template)
                            @php
                                $isActive = $activeTemplate === $slug;
                                $inheritFrom = $template['inherit'] ?? null;
                            @endphp

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="card card-sm">
                                    {{-- Ribbon si hereda --}}
                                    @if($inheritFrom)
                                        <div class="ribbon ribbon-top bg-danger">
                                            <i class="fas fa-code-branch me-1"></i>
                                            Hereda de: {{ $inheritFrom }}
                                        </div>
                                    @endif

                                    {{-- Screenshot --}}
                                    <div class="img-responsive card-img-top"
                                         style="background-image: url('{{ app('Modules\Template\Services\TemplateManager')->getScreenshot($slug) }}');
                                                 height: 200px;
                                                 background-size: cover;
                                                 background-position: center;">
                                    </div>

                                    {{-- Card Body --}}
                                    <div class="card-body">
                                        <h4 class="card-title">{{ $template['name'] }}</h4>
                                        <p class="card-text text-muted">{{ $template['description'] ?? 'Sin descripción' }}</p>

                                        <div class="small text-muted mt-2">
                                            <div>
                                                <i class="fas fa-user me-1"></i>
                                                {{ $template['author'] ?? 'N/A' }}
                                            </div>
                                            <div>
                                                <i class="fas fa-tag me-1"></i>
                                                v{{ $template['version'] ?? '1.0.0' }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Card Footer - Botones --}}
                                    <div class="card-footer">
                                        @if($isActive)
                                            <div class="btn-list">
                                                <button class="btn btn-success w-100" disabled>
                                                    <i class="fas fa-check me-2"></i>{{ __('template::template.activated') }}
                                                </button>
                                            </div>
                                        @else
                                            <div class="btn-list gap-2">
                                                <button class="btn btn-primary flex-grow-1 btn-trigger-activate-template"
                                                        data-url="{{ route('settings.templates.activate') }}"
                                                        data-template="{{ $slug }}">
                                                    <i class="fas fa-check me-1"></i>{{ __('template::template.activate') }}
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-trigger-remove-template"
                                                        data-url="{{ route('settings.templates.remove') }}"
                                                        data-template="{{ $slug }}"
                                                        data-name="{{ $template['name'] }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        <div class="empty-header">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <p class="empty-title">{{ __('template::template.no_templates') }}</p>
                        <p class="empty-subtitle">
                            {{ __('template::template.create_new') }}
                        </p>
                        <div class="empty-action">
                            <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                                {{ __('template::template.create') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: Importar ZIP --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="importModalLabel">Importar plantilla desde ZIP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('settings.templates.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            El ZIP debe contener una carpeta con <code>template.json</code> en su raíz con al menos los campos <code>name</code> y <code>slug</code>.
                        </p>
                        <div class="mb-0">
                            <label for="zip_file" class="form-label fw-semibold">Archivo ZIP</label>
                            <input type="file" class="form-control" id="zip_file" name="zip_file" accept=".zip" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Importar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminación --}}
    <div class="modal modal-blur fade" id="remove-template-modal" tabindex="-1" role="dialog"
         aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>

                <div class="modal-body text-center py-4">
                    <i class="fas fa-triangle-exclamation fa-3x text-danger mb-3"></i>
                    <h3>{{ __('template::template.confirm_remove') }}</h3>
                    <div id="remove-template-modal-text" class="text-muted">
                        ¿Estás seguro de que deseas eliminar esta plantilla?
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        {{ __('core::core.cancel') }}
                    </a>
                    <button type="button" class="btn btn-danger" id="confirm-remove-template-button"
                            data-template="" data-url="">
                        {{ __('template::template.remove') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('modules/template/js/template.js') }}"></script>
    @endpush
@endsection
