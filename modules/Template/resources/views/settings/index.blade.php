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
                        <p class="small mb-0 text-muted">
                            Plantilla activa: <strong class="text-success">{{ $activeTemplate }}</strong>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.templates.import.page') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Importar ZIP
                        </a>
                        <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> {{ __('template::template.create_new') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if(count($templates) > 0)
                    <div class="row g-4">
                        @foreach($templates as $slug => $template)
                            @php
                                $isActive    = $activeTemplate === $slug;
                                $inheritFrom = $template['inherit'] ?? null;
                                $inDb        = $template['_in_db'] ?? true;
                                $screenshot  = app('Modules\Template\Services\TemplateManager')->getScreenshot($slug);
                                $hasImage    = $screenshot && !str_ends_with($screenshot, 'placeholder');
                            @endphp

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="card overflow-hidden hover-img">

                                    {{-- Imagen --}}
                                    <div class="position-relative">
                                        @if($hasImage)
                                            <img src="{{ $screenshot }}" class="card-img-top" alt="{{ $template['name'] }}"
                                                 style="height:200px;object-fit:cover;">
                                        @else
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                                 style="height:200px;">
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-image fa-3x mb-2 opacity-25"></i>
                                                    <p class="small mb-0">Sin previsualización</p>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Version badge (bottom-right) --}}
                                        <span class="badge text-bg-light text-dark fs-2 lh-sm position-absolute bottom-0 end-0 mb-2 me-2 py-1 px-2 fw-semibold">
                                            v{{ $template['version'] ?? '1.0.0' }}
                                        </span>

                                        {{-- Estado badge (bottom-left) --}}
                                        @if($isActive)
                                            <span class="badge bg-success fs-2 lh-sm position-absolute bottom-0 start-0 mb-2 ms-2 py-1 px-2 fw-semibold">
                                                <i class="fas fa-check me-1"></i> Activa
                                            </span>
                                        @elseif($inheritFrom)
                                            <span class="badge bg-info fs-2 lh-sm position-absolute bottom-0 start-0 mb-2 ms-2 py-1 px-2 fw-semibold">
                                                <i class="fas fa-code-branch me-1"></i> Hereda
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Card body --}}
                                    <div class="card-body p-4">
                                        {{-- Categoría / tipo --}}
                                        @if($inheritFrom)
                                            <span class="badge text-bg-light fs-2 py-1 px-2 lh-sm">Hereda: {{ $inheritFrom }}</span>
                                        @elseif(!$inDb)
                                            <span class="badge text-bg-warning fs-2 py-1 px-2 lh-sm">Sin registrar</span>
                                        @else
                                            <span class="badge text-bg-light fs-2 py-1 px-2 lh-sm">Plantilla</span>
                                        @endif

                                        <h6 class="d-block mt-3 mb-2 fw-semibold text-dark">{{ $template['name'] }}</h6>
                                        <p class="text-muted small mb-3">
                                            {{ Str::limit($template['description'] ?? 'Sin descripción', 80) }}
                                        </p>

                                        {{-- Footer row --}}
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="d-flex align-items-center gap-2 text-muted small">
                                                <i class="fas fa-user"></i>
                                                {{ $template['author'] ?? 'N/A' }}
                                            </div>
                                            <div class="ms-auto">
                                                @if($isActive)
                                                    <span class="badge bg-success-subtle text-success px-3 py-2">
                                                        <i class="fas fa-check me-1"></i> Activa
                                                    </span>
                                                @else
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-primary btn-trigger-activate-template"
                                                                data-url="{{ route('settings.templates.activate') }}"
                                                                data-template="{{ $slug }}">
                                                            <i class="fas fa-check me-1"></i> Activar
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger btn-trigger-remove-template"
                                                                data-url="{{ route('settings.templates.remove') }}"
                                                                data-template="{{ $slug }}"
                                                                data-name="{{ $template['name'] }}"
                                                                title="Eliminar plantilla">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty py-5">
                        <div class="empty-icon mb-3">
                            <i class="fas fa-paint-brush fa-3x text-muted opacity-50"></i>
                        </div>
                        <p class="empty-title">No hay plantillas instaladas</p>
                        <p class="empty-subtitle text-muted">
                            Importa una plantilla desde un archivo ZIP o crea una nueva.
                        </p>
                        <div class="empty-action d-flex gap-2 justify-content-center">
                            <a href="{{ route('settings.templates.import.page') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-import me-1"></i> Importar ZIP
                            </a>
                            <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> {{ __('template::template.create') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: Confirmar eliminación --}}
    <div class="modal fade" id="remove-template-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-triangle-exclamation fa-3x text-danger mb-3"></i>
                    <h4 class="mb-1">Eliminar plantilla</h4>
                    <p id="remove-template-modal-text" class="text-muted">
                        ¿Estás seguro de que deseas eliminar esta plantilla?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger w-100 mb-2" id="confirm-remove-template-button"
                            data-template="" data-url="">
                        <i class="fas fa-trash me-1"></i> Eliminar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('modules/template/js/template.js') }}"></script>
@endpush
