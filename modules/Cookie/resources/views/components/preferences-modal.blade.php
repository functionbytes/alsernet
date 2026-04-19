@php
    $categories = config('Cookie.general.cookie_categories', []);
    $saveText   = cookie_option('save_btn_text',   __('cookie::messages.modal.save'));
    $acceptText = cookie_option('accept_btn_text', __('cookie::messages.modal.accept_all'));
    $btnColor   = cookie_option('btn_color', '#b10100');
    $inventory  = \Modules\Cookie\Models\CookieInventory::query()
        ->active()
        ->ordered()
        ->get()
        ->groupBy('category');
@endphp

<div class="modal fade" id="cookie-preferences-modal" tabindex="-1"
     style="--cookie-btn: {{ $btnColor }};"
     aria-labelledby="cookiePreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="cookiePreferencesTitle">
                    <i class="fas fa-shield me-2 text-muted"></i>{{ __('cookie::messages.modal.title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('cookie::messages.modal.cancel') }}"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted mb-4">{{ __('cookie::messages.modal.description') }}</p>

                @foreach($categories as $key => $category)
                    <div class="d-flex align-items-start justify-content-between py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3">
                            <div class="fw-semibold mb-1">{{ __($category['name'] ?? $key) }}</div>
                            <p class="text-muted mb-0">{{ __($category['description'] ?? '') }}</p>

                            @if($inventory->has($key) && $inventory[$key]->count() > 0)
                                <div class="mt-2">
                                    <a class="small text-muted text-decoration-none" data-bs-toggle="collapse"
                                       href="#cookies-{{ $key }}" role="button">
                                        <i class="fas fa-chevron-down me-1" style="font-size:.7rem"></i>
                                        Ver cookies ({{ $inventory[$key]->count() }})
                                    </a>
                                    <div class="collapse mt-2" id="cookies-{{ $key }}">
                                        <table class="table table-sm table-borderless small mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Cookie</th>
                                                    <th>Proveedor</th>
                                                    <th>Duración</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($inventory[$key] as $cookie)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $cookie->name }}</td>
                                                        <td class="text-muted">{{ $cookie->provider }}</td>
                                                        <td class="text-muted">{{ $cookie->duration }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-check form-switch flex-shrink-0 ms-3 mt-1">
                            <input class="form-check-input cookie-category-toggle {{ !empty($category['required']) ? 'cookie-toggle--required' : 'cookie-toggle--optional' }}"
                                   type="checkbox"
                                   id="category-{{ $key }}"
                                   data-category="{{ $key }}"
                                   @if(!empty($category['required'])) checked disabled @else checked @endif>
                            @if(!empty($category['required']))
                                <span class="badge bg-secondary ms-1 small">{{ __('cookie::messages.modal.required') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('cookie::messages.modal.cancel') }}
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary js-cookie-accept-modal">
                        {{ $acceptText }}
                    </button>
                    <button type="button" class="btn btn-sm js-cookie-save-preferences cookie-consent__save-button">
                        <i class="fas fa-save me-1"></i>{{ $saveText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
