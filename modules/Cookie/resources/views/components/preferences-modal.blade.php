@php
    $categories = config('Cookie.general.cookie_categories', []);
    $saveText   = cookie_option('save_btn_text', 'Guardar preferencias');
    $acceptText = cookie_option('accept_btn_text', 'Aceptar todo');
    $btnColor   = cookie_option('btn_color', '#90bb13');
@endphp

<div class="modal fade" id="cookie-preferences-modal" tabindex="-1"
     aria-labelledby="cookiePreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="cookiePreferencesTitle">
                    <i class="fas fa-shield me-2 text-muted"></i>Configuración de cookies
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted mb-4">
                    Puedes elegir qué tipos de cookies deseas permitir. Las cookies necesarias siempre están activas ya que son imprescindibles para el correcto funcionamiento del sitio.
                </p>

                @foreach($categories as $key => $category)
                    <div class="d-flex align-items-start justify-content-between py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3">
                            <div class="fw-semibold mb-1">{{ $category['name'] ?? $key }}</div>
                            <p class="text-muted mb-0">{{ $category['description'] ?? '' }}</p>
                        </div>
                        <div class="form-check form-switch flex-shrink-0 ms-3 mt-1">
                            <input class="form-check-input cookie-category-toggle"
                                   type="checkbox"
                                   id="category-{{ $key }}"
                                   data-category="{{ $key }}"
                                   @if(!empty($category['required'])) checked disabled @else checked @endif
                                   style="cursor: {{ !empty($category['required']) ? 'not-allowed' : 'pointer' }};">
                            @if(!empty($category['required']))
                                <span class="badge bg-secondary ms-1 small">Requerida</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary js-cookie-accept-modal">
                        {{ $acceptText }}
                    </button>
                    <button type="button" class="btn btn-sm js-cookie-save-preferences"
                            style="background-color:{{ $btnColor }};border-color:{{ $btnColor }};color:#fff;">
                        <i class="fas fa-save me-1"></i>{{ $saveText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
