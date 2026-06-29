@php
    $categories = config('Cookie.general.cookie_categories', []);
    $saveText   = __('cookie::messages.modal.save');
    $acceptText = __('cookie::messages.modal.accept_all');
    $btnColor   = '#90bb13';
    $inventory  = \Modules\Cookie\Models\CookieInventory::query()
        ->active()
        ->ordered()
        ->get()
        ->groupBy('category');

    $categoryIcons = [
        'essential'  => 'fa-shield-halved',
        'analytics'  => 'fa-chart-line',
        'marketing'  => 'fa-bullhorn',
        'functional' => 'fa-sliders',
        'performance'=> 'fa-gauge-high',
    ];
@endphp

<div class="modal fade cookiex-modal" id="cookie-preferences-modal" tabindex="-1"
     aria-labelledby="cookiePreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" style="--cookiex-brand: {{ $btnColor }};">
        <div class="modal-content cookiex-content">

            <div class="cookiex-header">
                <div class="cookiex-header-body">
                    <h5 class="cookiex-title" id="cookiePreferencesTitle">
                        {{ __('cookie::messages.modal.title') }}
                    </h5>
                    <p class="cookiex-subtitle">{{ __('cookie::messages.modal.description') }}</p>
                </div>
                <button type="button" class="cookiex-close" data-bs-dismiss="modal" aria-label="{{ __('cookie::messages.modal.cancel') }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="cookiex-body">
                @foreach($categories as $key => $category)
                    @php
                        $required = !empty($category['required']);
                        $icon     = $categoryIcons[$key] ?? 'fa-circle-info';
                        $hasList  = $inventory->has($key) && $inventory[$key]->count() > 0;
                    @endphp

                    <div class="cookiex-cat {{ $required ? 'cookiex-cat--required' : '' }}">
                        <div class="cookiex-cat-main">
                            <div class="cookiex-cat-ico">
                                <i class="fa-solid {{ $icon }}"></i>
                            </div>
                            <div class="cookiex-cat-text">
                                <div class="cookiex-cat-head">
                                    <h6 class="cookiex-cat-name">{{ __($category['name'] ?? $key) }}</h6>
                                    @if($required)
                                        <span class="cookiex-chip cookiex-chip--required">
                                            <i class="fa-solid fa-lock"></i>{{ __('cookie::messages.modal.required') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="cookiex-cat-desc">{{ __($category['description'] ?? '') }}</p>

                                @if($hasList)
                                    <a class="cookiex-cat-toggle-list" data-bs-toggle="collapse"
                                       href="#cookies-{{ $key }}" role="button" aria-expanded="false">
                                        <i class="fa-solid fa-chevron-down cookiex-cat-caret"></i>
                                        <span>Ver cookies utilizadas</span>
                                        <span class="cookiex-count">{{ $inventory[$key]->count() }}</span>
                                    </a>
                                @endif
                            </div>
                            <label class="cookiex-switch {{ $required ? 'cookiex-switch--locked' : '' }}" for="category-{{ $key }}">
                                <input class="cookiex-switch-input cookie-category-toggle {{ $required ? 'cookie-toggle--required' : 'cookie-toggle--optional' }}"
                                       type="checkbox"
                                       id="category-{{ $key }}"
                                       data-category="{{ $key }}"
                                       @checked(true) @disabled($required)>
                                <span class="cookiex-switch-slider"></span>
                            </label>
                        </div>

                        @if($hasList)
                            <div class="collapse cookiex-cat-list" id="cookies-{{ $key }}">
                                <div class="cookiex-cat-list-inner">
                                    <div class="cookiex-table-wrap">
                                        <table class="cookiex-table">
                                            <thead>
                                                <tr>
                                                    <th>Cookie</th>
                                                    <th>Proveedor</th>
                                                    <th>Duración</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($inventory[$key] as $cookie)
                                                    <tr>
                                                        <td class="cookiex-table-name">{{ $cookie->name }}</td>
                                                        <td>{{ $cookie->provider }}</td>
                                                        <td>{{ $cookie->duration }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="cookiex-footer">
                <button type="button" class="cookiex-btn cookiex-btn--primary js-cookie-accept-modal">
                    {{ $acceptText }}
                </button>
                <button type="button" class="cookiex-btn cookiex-btn--outline js-cookie-save-preferences">
                    {{ $saveText }}
                </button>
                <button type="button" class="cookiex-btn cookiex-btn--ghost" data-bs-dismiss="modal">
                    {{ __('cookie::messages.modal.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

