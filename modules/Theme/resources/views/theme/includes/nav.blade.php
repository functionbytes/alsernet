@php
    use Modules\Theme\Services\NavService;
    use Illuminate\Support\Facades\Route;

    ['miniItems' => $miniItems, 'sidebars' => $allSidebars, 'activeSidebarId' => $activeSidebarId] = NavService::getNavDataForUser();

    $navMiniItems    = collect($miniItems)->keyBy('sidebar_id')->all();
    $settingsSidebar = $allSidebars['settings'] ?? null;
    $mainSidebars    = collect($allSidebars)
        ->except('settings')
        ->filter(fn ($sidebar, $sidebarId) => isset($navMiniItems[$sidebarId]))
        ->sortBy(fn ($v, $k) => $navMiniItems[$k]['order'] ?? 999)
        ->all();

    $panelIsOpen = $activeSidebarId !== null
        && isset($navMiniItems[$activeSidebarId])
        && collect($allSidebars[$activeSidebarId]['sections'] ?? [])
            ->flatMap(fn ($s) => $s['items'] ?? [])
            ->count() > 1;
@endphp

<!-- begin::Sidebar Menu -->
<aside class="app-menubar-tabs{{ !$panelIsOpen ? ' no-sidebar-open' : '' }}" id="appMenubar">

    <div class="app-navbar-tabs" data-simplebar="">
        <ul class="nav" id="appMenubarTabs" role="list" aria-orientation="vertical">

            @foreach($mainSidebars as $sidebarId => $sidebar)
                @php
                    $miniItem  = $navMiniItems[$sidebarId] ?? null;
                    $iconKey   = $miniItem['icon'] ?? 'dot';
                    $label     = $miniItem['tooltip'] ?? ucfirst(str_replace(['-', '_'], ' ', $sidebarId));
                    $allItems  = collect($sidebar['sections'] ?? [])->flatMap(fn ($s) => $s['items'] ?? []);

                    if ($allItems->count() === 1) {
                        $directRoute = $miniItem['url'] ?? ($allItems->first()['route'] ?? '');
                        $directUrl   = $directRoute && Route::has($directRoute) ? route($directRoute) : '#';
                        $isActive    = $directRoute && request()->routeIs($directRoute . '*');
                    } else {
                        $isActive = $activeSidebarId === $sidebarId;
                    }
                @endphp
                <li class="nav-item" role="presentation" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $label }}">
                    @if($allItems->count() === 1)
                        <a class="menu-link{{ $isActive ? ' active' : '' }}" href="{{ $directUrl }}" aria-label="{{ $label }}">
                            <span class="nav-icon">{!! \Modules\Theme\Helpers\NavIconHelper::render($iconKey) !!}</span>
                        </a>
                    @else
                        <a class="menu-link{{ $isActive ? ' active' : '' }}"
                           href="#tab-{{ $sidebarId }}"
                           aria-controls="tab-{{ $sidebarId }}"
                           aria-label="{{ $label }}"
                           data-bs-toggle="tab">
                            <span class="nav-icon">{!! \Modules\Theme\Helpers\NavIconHelper::render($iconKey) !!}</span>
                        </a>
                    @endif
                </li>
            @endforeach

            @if($settingsSidebar)
                @php
                    $settingsMiniItem = $navMiniItems['settings'] ?? null;
                    $settingsIcon     = $settingsMiniItem['icon'] ?? 'sliders';
                    $settingsLabel    = $settingsMiniItem['tooltip'] ?? 'Configuración';
                    $settingsActive   = $activeSidebarId === 'settings';
                @endphp
                <li class="nav-item-hr" role="presentation"></li>
                <li class="nav-item" role="presentation" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $settingsLabel }}">
                    <a class="menu-link{{ $settingsActive ? ' active' : '' }}"
                       href="#tab-settings"
                       aria-controls="tab-settings"
                       aria-label="{{ $settingsLabel }}"
                       data-bs-toggle="tab">
                        <span class="nav-icon">{!! \Modules\Theme\Helpers\NavIconHelper::render($settingsIcon) !!}</span>
                    </a>
                </li>
            @endif

        </ul>
    </div>

    <div class="app-tab-content">
        <div class="app-content-inner">
            <div class="tab-content" id="appMenubarTabsContent">

                @foreach($mainSidebars as $sidebarId => $sidebar)
                    @php
                        $allItems = collect($sidebar['sections'] ?? [])->flatMap(fn ($s) => $s['items'] ?? []);
                    @endphp
                    @if($allItems->count() === 1)
                        @continue
                    @endif
                    @php $isPaneActive = $activeSidebarId === $sidebarId; @endphp
                    <div class="tab-pane{{ $isPaneActive ? ' show active' : '' }}"
                         id="tab-{{ $sidebarId }}"
                         role="tabpanel"
                         tabindex="0">
                        <nav class="app-navbar" data-simplebar="">
                            <ul class="side-menubar">
                                @foreach($sidebar['sections'] ?? [] as $section)
                                    @if($section['title'] && !empty($section['items']))
                                        <li class="menu-heading">
                                            <span class="menu-label">{{ $section['title'] }}</span>
                                        </li>
                                    @endif
                                    @foreach($section['items'] ?? [] as $item)
                                        @php
                                            $cr   = $item['route'] ?? '';
                                            $cUrl = ($cr && Route::has($cr)) ? route($cr) : '#';
                                            $cAct = $cr && request()->routeIs($cr . '*');
                                        @endphp
                                        <li class="menu-item">
                                            <a class="menu-link{{ $cAct ? ' active' : '' }}" href="{{ $cUrl }}" role="button">
                                                <span class="menu-label">{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </nav>
                    </div>
                @endforeach

                @if($settingsSidebar)
                    @php $isSettingsActive = $activeSidebarId === 'settings'; @endphp
                    <div class="tab-pane{{ $isSettingsActive ? ' show active' : '' }}"
                         id="tab-settings"
                         role="tabpanel"
                         tabindex="0">
                        <nav class="app-navbar" data-simplebar="">
                            <ul class="side-menubar">
                                @foreach($settingsSidebar['sections'] ?? [] as $section)
                                    @if($section['title'] && !empty($section['items']))
                                        <li class="menu-heading">
                                            <span class="menu-label">{{ $section['title'] }}</span>
                                        </li>
                                    @endif
                                    @foreach($section['items'] ?? [] as $item)
                                        @php
                                            $cr   = $item['route'] ?? '';
                                            $cUrl = ($cr && Route::has($cr)) ? route($cr) : '#';
                                            $cAct = $cr && request()->routeIs($cr . '*');
                                        @endphp
                                        <li class="menu-item">
                                            <a class="menu-link{{ $cAct ? ' active' : '' }}" href="{{ $cUrl }}" role="button">
                                                <span class="menu-label">{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </nav>
                    </div>
                @endif

            </div>
        </div>
    </div>

</aside>
<!-- end::Sidebar Menu -->

@push('scripts')
<script>
(function () {
    var menubar = document.getElementById('appMenubar');

    // El ítem activo ya llega marcado con .active desde el servidor
    // (request()->routeIs() arriba), pero eso solo pinta la clase — nada
    // movía el scroll del menú para que quedara a la vista. En secciones
    // largas (p. ej. Ajustes > Helpdesk) el usuario tenía que desplazarse a
    // mano para encontrar en qué pantalla estaba parado.
    function scrollActiveIntoView(pane) {
        var active = pane.querySelector('.menu-link.active');
        if (!active) { return; }

        var scrollEl = active.closest('[data-simplebar]');
        if (!scrollEl) { return; }

        var instance = SimpleBar.instances.get(scrollEl);
        var container = instance ? instance.getScrollElement() : scrollEl;

        var containerRect = container.getBoundingClientRect();
        var activeRect = active.getBoundingClientRect();
        var margin = 24;

        // Ya visible con margen razonable: no tocar el scroll.
        if (activeRect.top >= containerRect.top + margin && activeRect.bottom <= containerRect.bottom - margin) {
            return;
        }

        var offset = (activeRect.top - containerRect.top) + container.scrollTop
            - (containerRect.height / 2) + (activeRect.height / 2);
        container.scrollTop = Math.max(0, offset);
    }

    document.querySelectorAll('#appMenubarTabs [data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('show.bs.tab', function () {
            menubar.classList.remove('no-sidebar-open');
        });

        tab.addEventListener('shown.bs.tab', function (e) {
            var pane = document.querySelector(e.target.getAttribute('href'));
            if (!pane) { return; }
            pane.querySelectorAll('[data-simplebar]').forEach(function (el) {
                var instance = SimpleBar.instances.get(el);
                if (instance) {
                    instance.recalculate();
                } else {
                    new SimpleBar(el);
                }
            });
            scrollActiveIntoView(pane);
        });
    });

    document.querySelectorAll('#appMenubarTabsContent .tab-pane.active').forEach(function (pane) {
        pane.querySelectorAll('[data-simplebar]').forEach(function (el) {
            // A diferencia del cambio de pestaña, en la carga inicial nada
            // más crea la instancia todavía — sin el `else` de abajo,
            // scrollActiveIntoView() mide el elemento crudo (sin envolver
            // por SimpleBar) y el scroll que fija se pierde en cuanto
            // main.js crea la instancia real más tarde.
            var instance = SimpleBar.instances.get(el);
            if (instance) {
                instance.recalculate();
            } else {
                new SimpleBar(el);
            }
        });
        scrollActiveIntoView(pane);
    });
}());
</script>
@endpush
