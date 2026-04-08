@php
    $title      = cookie_option('banner_title', 'Usamos cookies');
    $message    = cookie_option('banner_message', 'Utilizamos cookies propias y de terceros para mejorar tu experiencia. Puedes aceptar todas o configurar tus preferencias.');
    $acceptText = cookie_option('accept_btn_text', 'Aceptar todo');
    $rejectText = cookie_option('reject_btn_text', 'Solo necesarias');
    $configText = cookie_option('config_btn_text', 'Configurar');
    $btnColor   = cookie_option('btn_color', '#90bb13');
    $position   = cookie_option('banner_position', 'bottom'); // bottom | top
@endphp

<div id="cookie-banner"
     class="cookie-banner position-fixed {{ $position === 'top' ? 'top-0' : 'bottom-0' }} start-0 end-0 z-3 shadow-lg"
     style="display:none; background:#fff; border-top: 3px solid {{ $btnColor }};">
    <div class="container-fluid px-4 py-3">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-7">
                <div class="d-flex align-items-start gap-3">
                    <i class="fas fa-cookie-bite fa-lg mt-1 flex-shrink-0" style="color:{{ $btnColor }};"></i>
                    <div>
                        <p class="fw-semibold mb-1 small">{{ $title }}</p>
                        <p class="text-muted mb-0">{{ $message }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary js-cookie-reject"
                            aria-label="{{ $rejectText }}">
                        {{ $rejectText }}
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary js-cookie-config"
                            data-bs-toggle="modal"
                            data-bs-target="#cookie-preferences-modal"
                            aria-label="{{ $configText }}">
                        <i class="fas fa-sliders-h me-1"></i>{{ $configText }}
                    </button>
                    <button type="button"
                            class="btn btn-sm js-cookie-accept"
                            style="background-color:{{ $btnColor }};border-color:{{ $btnColor }};color:#fff;"
                            aria-label="{{ $acceptText }}">
                        <i class="fas fa-check me-1"></i>{{ $acceptText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
