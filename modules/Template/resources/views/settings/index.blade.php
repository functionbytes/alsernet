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
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-file-import me-1"></i> Importar ZIP
                        </button>
                        <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> {{ __('template::template.create_new') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if(count($templates) > 0)
                    <div class="row row-cards g-3">
                        @foreach($templates as $slug => $template)
                            @php
                                $isActive = $activeTemplate === $slug;
                                $inheritFrom = $template['inherit'] ?? null;
                                $inDb = isset($template['_in_db']) ? $template['_in_db'] : true;
                            @endphp

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="card card-sm h-100 {{ $isActive ? 'border-success border-2' : '' }}">

                                    {{-- Badge activo --}}
                                    @if($isActive)
                                        <div class="ribbon ribbon-top bg-success">
                                            <i class="fas fa-check me-1"></i> Activa
                                        </div>
                                    @elseif($inheritFrom)
                                        <div class="ribbon ribbon-top bg-info">
                                            <i class="fas fa-code-branch me-1"></i> Hereda: {{ $inheritFrom }}
                                        </div>
                                    @endif

                                    {{-- Screenshot --}}
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                         style="height: 180px; overflow: hidden;">
                                        @php
                                            $screenshot = app('Modules\Template\Services\TemplateManager')->getScreenshot($slug);
                                        @endphp
                                        @if($screenshot && !str_ends_with($screenshot, 'placeholder'))
                                            <img src="{{ $screenshot }}" alt="{{ $template['name'] }}"
                                                 class="w-100 h-100" style="object-fit: cover;">
                                        @else
                                            <div class="text-center text-muted p-3">
                                                <i class="fas fa-image fa-3x mb-2 opacity-25"></i>
                                                <p class="small mb-0">Sin previsualización</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Card Body --}}
                                    <div class="card-body">
                                        <h5 class="card-title mb-1">{{ $template['name'] }}</h5>
                                        <p class="card-text text-muted small mb-2">
                                            {{ Str::limit($template['description'] ?? 'Sin descripción', 80) }}
                                        </p>
                                        <div class="d-flex gap-3 text-muted small">
                                            <span><i class="fas fa-user me-1"></i>{{ $template['author'] ?? 'N/A' }}</span>
                                            <span><i class="fas fa-tag me-1"></i>v{{ $template['version'] ?? '1.0.0' }}</span>
                                        </div>
                                    </div>

                                    {{-- Card Footer --}}
                                    <div class="card-footer bg-transparent">
                                        @if($isActive)
                                            <button class="btn btn-success w-100" disabled>
                                                <i class="fas fa-check me-2"></i>Plantilla Activa
                                            </button>
                                        @else
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-primary flex-grow-1 btn-trigger-activate-template"
                                                        data-url="{{ route('settings.templates.activate') }}"
                                                        data-template="{{ $slug }}">
                                                    <i class="fas fa-check me-1"></i>Activar
                                                </button>
                                                <button class="btn btn-outline-danger btn-trigger-remove-template"
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
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-file-import me-1"></i> Importar ZIP
                            </button>
                            <a href="{{ route('settings.templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> {{ __('template::template.create') }}
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
                    <h5 class="modal-title fw-bold" id="importModalLabel">
                        <i class="fas fa-file-import me-2"></i>Importar plantilla desde ZIP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="{{ route('settings.templates.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info small py-2">
                            <i class="fas fa-info-circle me-1"></i>
                            El ZIP debe contener una carpeta con <code>template.json</code> en su raíz con al menos los campos <code>name</code> y <code>slug</code>.
                        </div>
                        <div class="mb-0">
                            <label for="zip_file" class="form-label fw-semibold">Archivo ZIP</label>
                            <input type="file" class="form-control" id="zip_file" name="zip_file" accept=".zip" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                    </div>
                </form>
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
                    <p id="remove-template-modal-text" class="text-muted small">
                        ¿Estás seguro de que deseas eliminar esta plantilla?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirm-remove-template-button"
                            data-template="" data-url="">
                        <i class="fas fa-trash me-1"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('modules/template/js/template.js') }}"></script>
    @endpush
@endsection
