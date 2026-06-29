@php
    $title      = __('cookie::messages.banner.title');
    $message    = __('cookie::messages.banner.message');
    $acceptText = __('cookie::messages.banner.accept');
    $rejectText = __('cookie::messages.banner.reject');
    $configText = __('cookie::messages.banner.customize');
    $btnColor   = '#90bb13';
@endphp

<div id="cookie-backdrop"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1049;"></div>

<div id="cookie-banner"
     style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:1050;
            background:#fff; border-top:4px solid {{ $btnColor }}; border-radius:12px;
            width:90%; max-width:560px; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div style="padding:1.5rem;">
        <div class="d-flex align-items-start gap-3 mb-3">
            <i class="fas fa-cookie-bite fa-lg mt-1 flex-shrink-0" style="color:{{ $btnColor }};"></i>
            <div>
                <p class="fw-semibold mb-1 small">{{ $title }}</p>
                <p class="text-muted mb-0 small">{{ $message }}</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
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
