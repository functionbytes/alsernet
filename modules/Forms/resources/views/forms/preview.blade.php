@extends('layouts.theme')

@section('title', 'Preview: ' . $form->name)

@push('css')
<style>
    /* Viewport background */
    .preview-viewport {
        background: #e9ecef;
        min-height: 700px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 32px 16px;
        overflow: auto;
    }

    /* Device wrappers */
    .device-wrapper { transition: width .3s ease; }

    .device-wrapper.device-desktop { width: 100%; }
    .device-wrapper.device-tablet  { width: 768px; }
    .device-wrapper.device-mobile  { width: 390px; }

    /* Desktop — browser chrome */
    .device-wrapper.device-desktop .device-shell {
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,.12);
    }

    /* Tablet frame */
    .device-wrapper.device-tablet .device-shell {
        background: #1c1c1e;
        border-radius: 20px;
        padding: 18px 10px;
        box-shadow: 0 0 0 2px #3a3a3c, 0 20px 48px rgba(0,0,0,.35);
    }
    .device-wrapper.device-tablet .device-iframe-wrap {
        border-radius: 8px;
        overflow: hidden;
    }

    /* Mobile — phone frame */
    .device-wrapper.device-mobile .device-shell {
        background: #1c1c1e;
        border-radius: 48px;
        padding: 0 8px;
        box-shadow: 0 0 0 2px #3a3a3c, 0 24px 56px rgba(0,0,0,.45);
    }
    .device-mobile-notch {
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .device-mobile-notch-bar {
        width: 90px;
        height: 6px;
        background: #3a3a3c;
        border-radius: 3px;
    }
    .device-mobile-home {
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .device-mobile-home-bar {
        width: 100px;
        height: 4px;
        background: #3a3a3c;
        border-radius: 2px;
    }
    .device-wrapper.device-mobile .device-iframe-wrap {
        border-radius: 36px;
        overflow: hidden;
    }

    /* Browser top bar (desktop only) */
    .device-browser-bar {
        background: #f1f3f4;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid #dee2e6;
    }
    .browser-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
    }
    .browser-dot-red   { background: #ff5f57; }
    .browser-dot-amber { background: #febc2e; }
    .browser-dot-green { background: #28c840; }
    .browser-url-bar {
        flex: 1;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 3px 10px;
        font-size: 11px;
        color: #6c757d;
        margin-left: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Iframe */
    #previewFrame {
        width: 100%;
        height: 680px;
        border: none;
        display: block;
        background: white;
    }
    .device-wrapper.device-desktop #previewFrame { height: 640px; }

    /* Active device button */
    .btn-device.active {
        background: #212529;
        color: white;
        border-color: #212529;
    }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Vista previa'])
@endsection

@section('content')

    <div class="row g-4 align-items-start">

        {{-- Preview area --}}
        <div class="col-lg-8">
            <div class="card">

                {{-- Toolbar --}}
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between gap-2">

                        {{-- Device switcher --}}
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-device active"
                                    data-device="desktop" title="Escritorio">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-device"
                                    data-device="tablet" title="Tablet">
                                <i class="fas fa-tablet-screen-button"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-device"
                                    data-device="mobile" title="Móvil">
                                <i class="fas fa-mobile-screen"></i>
                            </button>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefresh" title="Recargar">
                                <i class="fas fa-rotate-right"></i>
                            </button>
                            <a href="{{ route('forms.public.preview.public', [$form, $previewToken]) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-external-link-alt me-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Viewport --}}
                <div class="preview-viewport">
                    <div class="device-wrapper device-desktop" id="deviceWrapper">
                        <div class="device-shell">

                            {{-- Browser bar (desktop only) --}}
                            <div class="device-browser-bar" id="browserBar">
                                <span class="browser-dot browser-dot-red"></span>
                                <span class="browser-dot browser-dot-amber"></span>
                                <span class="browser-dot browser-dot-green"></span>
                                <span class="browser-url-bar">{{ route('forms.public.preview.public', [$form, $previewToken]) }}</span>
                            </div>

                            {{-- Mobile notch (hidden by default) --}}
                            <div class="device-mobile-notch d-none" id="mobileNotch">
                                <div class="device-mobile-notch-bar"></div>
                            </div>

                            {{-- Iframe --}}
                            <div class="device-iframe-wrap">
                                <iframe
                                    id="previewFrame"
                                    src="{{ route('forms.public.preview.public', [$form, $previewToken]) }}"
                                    title="Preview: {{ $form->name }}">
                                </iframe>
                            </div>

                            {{-- Mobile home bar (hidden by default) --}}
                            <div class="device-mobile-home d-none" id="mobileHome">
                                <div class="device-mobile-home-bar"></div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Vista previa con token seguro. El formulario no enviará datos reales.
                    </small>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Form info --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">{{ $form->name }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Slug</span>
                        <code class="small">{{ $form->slug }}</code>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Estado</span>
                        @if ($form->is_active)
                            <span class="badge bg-success-subtle text-success">Activo</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Campos</span>
                        <span class="fw-semibold small">{{ $form->fields->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Submissions</span>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="fw-semibold small text-decoration-none">
                            {{ $form->submissions()->count() }}
                        </a>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Categoría</span>
                        <span class="small">{{ $form->category->name ?? 'Sin categoría' }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Acciones</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-primary w-100 mb-2">
                        Editar formulario
                    </a>
                    <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary w-100 mb-2">
                        Ver respuestas
                    </a>
                    <a href="{{ route('forms.public.preview.public', [$form, $previewToken]) }}"
                       target="_blank"
                       class="btn btn-outline-secondary w-100">
                        Abrir en nueva ventana
                    </a>
                </div>
            </div>

            {{-- Share URL --}}
            @if (isset($previewToken))
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">URL de preview</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Comparte esta URL para que otros vean el formulario sin iniciar sesión.</p>
                    <div class="input-group input-group">
                        <input type="text" class="form-control"
                               id="previewUrl"
                               value="{{ route('forms.public.preview.public', [$form, $previewToken]) }}"
                               readonly>
                        <button type="button" class="btn btn-info" id="btnCopyUrl" title="Copiar URL">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {

    var deviceConfig = {
        desktop: { showBrowser: true,  showMobile: false },
        tablet:  { showBrowser: false, showMobile: false },
        mobile:  { showBrowser: false, showMobile: true  },
    };

    $('.btn-device').on('click', function () {
        var device = $(this).data('device');
        var cfg    = deviceConfig[device];

        $('.btn-device').removeClass('active');
        $(this).addClass('active');

        $('#deviceWrapper')
            .removeClass('device-desktop device-tablet device-mobile')
            .addClass('device-' + device);

        $('#browserBar').toggleClass('d-none', !cfg.showBrowser);
        $('#mobileNotch, #mobileHome').toggleClass('d-none', !cfg.showMobile);
    });

    $('#btnRefresh').on('click', function () {
        var frame = document.getElementById('previewFrame');
        frame.src = frame.src;
    });

    $('#btnCopyUrl').on('click', function () {
        navigator.clipboard.writeText($('#previewUrl').val()).then(function () {
            toastr.success('URL copiada al portapapeles');
        });
    });

});
</script>
@endpush
