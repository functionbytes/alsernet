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
        <ul class="nav" id="appMenubarTabs" role="tablist" aria-orientation="vertical">

            @foreach($mainSidebars as $sidebarId => $sidebar)
                @php
                    $miniItem  = $navMiniItems[$sidebarId] ?? null;
                    $iconClass = $miniItem['icon'] ?? 'fa-duotone fa-thin fa-circle-dot';
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
                <li class="nav-item" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $label }}">
                    @if($allItems->count() === 1)
                        <a class="menu-link{{ $isActive ? ' active' : '' }}" href="{{ $directUrl }}">
                            <i class="{{ $iconClass }}"></i>
                        </a>
                    @else
                        <a class="menu-link{{ $isActive ? ' active' : '' }}"
                           href="#tab-{{ $sidebarId }}"
                           role="tab"
                           aria-controls="tab-{{ $sidebarId }}"
                           aria-selected="{{ $isActive ? 'true' : 'false' }}"
                           data-bs-toggle="tab">
                            <i class="{{ $iconClass }}"></i>
                        </a>
                    @endif
                </li>
            @endforeach

            @if($settingsSidebar)
                @php
                    $settingsMiniItem = $navMiniItems['settings'] ?? null;
                    $settingsIcon     = $settingsMiniItem['icon'] ?? 'fa-duotone fa-thin fa-gear';
                    $settingsLabel    = $settingsMiniItem['tooltip'] ?? 'Configuración';
                    $settingsActive   = $activeSidebarId === 'settings';
                @endphp
                <li class="nav-item-hr"></li>
                <li class="nav-item" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ $settingsLabel }}">
                    <a class="menu-link{{ $settingsActive ? ' active' : '' }}"
                       href="#tab-settings"
                       role="tab"
                       aria-controls="tab-settings"
                       aria-selected="{{ $settingsActive ? 'true' : 'false' }}"
                       data-bs-toggle="tab">
                        <i class="{{ $settingsIcon }}"></i>
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
                                    @if($section['title'])
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
                                    @if($section['title'])
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
        });
    });

    document.querySelectorAll('#appMenubarTabsContent .tab-pane.active [data-simplebar]').forEach(function (el) {
        var instance = SimpleBar.instances.get(el);
        if (instance) { instance.recalculate(); }
    });
}());
</script>
@endpush
