@php
    use Modules\Theme\Services\NavService;

    // Obtener todos los datos de navegación procesados desde el backend
    // Esto incluye: miniItems filtrados, sidebars filtrados, y el ID del sidebar activo
    ['miniItems' => $miniItems, 'sidebars' => $allSidebars, 'activeSidebarId' => $activeSidebarId] = NavService::getNavDataForUser();
    $currentRoute = request()->route()?->getName() ?? '';
@endphp

<aside class="side-mini-panel with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
    <div class="iconbar">
        <div>
            <!-- ---------------------------------- -->
            <!-- Mini Navigation Icons -->
            <!-- ---------------------------------- -->
            <div class="mini-nav">
                <div class="brand-logo d-flex align-items-center justify-content-center">
                    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                        <i class="fa-duotone fa-bars fs-6"></i>
                    </a>
                </div>
                <ul class="mini-nav-ul" data-simplebar="">
                    @forelse($miniItems as $miniItem)
                        @php
                            $isActive = $activeSidebarId === $miniItem['sidebar_id'];
                        @endphp
                        <!-- --------------------------------------------------------------------------------------------------------- -->
                        <!-- {{ $miniItem['tooltip'] }} -->
                        <!-- --------------------------------------------------------------------------------------------------------- -->
                        <li class="mini-nav-item {{ $isActive ? 'selected' : '' }}"
                            id="mini-{{ $miniItem['id'] }}"
                            data-sidebar-id="{{ $miniItem['sidebar_id'] }}">
                            <a href="javascript:void(0)"
                               data-bs-toggle="tooltip"
                               data-bs-custom-class="custom-tooltip"
                               data-bs-placement="right"
                               data-bs-title="{{ $miniItem['tooltip'] }}">
                                <i class="fa {{ $miniItem['icon'] }} fs-5"></i>
                            </a>
                        </li>

                        @if(!empty($miniItem['divider_after']) && !$loop->last)
                            <li>
                                <span class="sidebar-divider lg"></span>
                            </li>
                        @endif
                    @empty
                        <li class="text-muted text-center p-3">
                            <small>No hay menús disponibles</small>
                        </li>
                    @endforelse
                </ul>

            </div>
            <!-- ---------------------------------- -->
            <!-- Sidebar Menus -->
            <!-- ---------------------------------- -->
            <div class="sidebarmenu">
                @forelse($allSidebars as $sidebarId => $sidebar)
                    @php
                        $sidebarIsActive = $activeSidebarId === $sidebarId;
                    @endphp
                    <!-- ---------------------------------- -->
                    <!-- Sidebar: {{ $sidebar['title'] ?? 'Menu' }} -->
                    <!-- ---------------------------------- -->
                    <nav class="sidebar-nav scroll-sidebar {{ $sidebarIsActive ? 'd-block' : '' }}"
                         id="menu-right-{{ $sidebarId }}"
                         data-simplebar="">
                        <ul class="sidebar-menu" id="sidebarnav-{{ $sidebarId }}">
                            @php
                                // Si el sidebar tiene secciones, usarlas; sino crear una sección única
                                $sections = isset($sidebar['sections']) && is_array($sidebar['sections'])
                                    ? $sidebar['sections']
                                    : [['title' => $sidebar['title'] ?? 'Menu', 'items' => $sidebar['items'] ?? []]];
                            @endphp

                            @forelse($sections as $section)
                                <!-- ---------------------------------- -->
                                <!-- {{ $section['title'] }} Section -->
                                <!-- ---------------------------------- -->
                                <li class="nav-small-cap">
                                    <span class="hide-menu">{{ $section['title'] }}</span>
                                </li>

                                <!-- Section Items -->
                                @forelse($section['items'] as $item)
                                    @php
                                        $currentRouteName = request()->route()?->getName() ?? '';
                                        $itemRoute = $item['route'] ?? '';

                                        // Usar comparación con wildcard para marcar rutas hijas como activas
                                        $isItemActive = $itemRoute && request()->routeIs($itemRoute . '*');

                                        // Validación de permisos del item
                                        $canAccessItem = true;
                                        if (!empty($item['permission'])) {
                                            $permissions = array_map('trim', explode('|', $item['permission']));
                                            $canAccessItem = false;

                                            // Verificar si el usuario tiene al menos uno de los permisos (OR logic)
                                            foreach ($permissions as $permission) {
                                                if (auth()->user()?->can($permission)) {
                                                    $canAccessItem = true;
                                                    break;
                                                }
                                            }
                                        }

                                        // Verificar si el item tiene sub-items (para has-arrow)
                                        $hasSubItems = !empty($item['children']) && is_array($item['children']);
                                    @endphp

                                    @if($canAccessItem)
                                        <!-- ---------------------------------- -->
                                        <!-- {{ $item['label'] }} -->
                                        <!-- ---------------------------------- -->
                                        <li class="sidebar-item {{ $isItemActive ? 'selected' : '' }}">
                                            @if($hasSubItems)
                                                <!-- Item with dropdown -->
                                                <a href="javascript:void(0)"
                                                   class="sidebar-link has-arrow {{ $isItemActive ? 'active' : '' }}"
                                                   aria-expanded="{{ $isItemActive ? 'true' : 'false' }}">
                                                    @if(!empty($item['icon']))
                                                        <i class="fa {{ $item['icon'] }}"></i>
                                                    @endif
                                                    <span class="hide-menu">{{ $item['label'] }}</span>
                                                </a>

                                                <!-- Sub-items -->
                                                <ul aria-expanded="{{ $isItemActive ? 'true' : 'false' }}"
                                                    class="collapse first-level {{ $isItemActive ? 'in' : '' }}">
                                                    @foreach($item['children'] as $child)
                                                        @php
                                                            $childRoute = $child['route'] ?? '';
                                                            $childIsActive = $childRoute && request()->routeIs($childRoute . '*');

                                                            // Validar permisos del child
                                                            $canAccessChild = true;
                                                            if (!empty($child['permission'])) {
                                                                $childPermissions = array_map('trim', explode('|', $child['permission']));
                                                                $canAccessChild = false;

                                                                foreach ($childPermissions as $perm) {
                                                                    if (auth()->user()?->can($perm)) {
                                                                        $canAccessChild = true;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        @endphp

                                                        @if($canAccessChild)
                                                            <li class="sidebar-item {{ $childIsActive ? 'active' : '' }}">
                                                                <a href="{{ route($childRoute) }}"
                                                                   class="sidebar-link {{ $childIsActive ? 'active' : '' }}">
                                                                    <span class="icon-small"></span>
                                                                    <span class="hide-menu">{{ $child['label'] }}</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @else
                                                <!-- Simple link without dropdown -->
                                                <a href="{{ $itemRoute ? route($itemRoute) : 'javascript:void(0)' }}"
                                                   class="sidebar-link {{ $isItemActive ? 'active' : '' }}"
                                                   aria-expanded="false"
                                                   data-current-route="{{ $currentRouteName }}"
                                                   data-item-route="{{ $itemRoute }}"
                                                   data-is-active="{{ $isItemActive ? 'true' : 'false' }}">
                                                    @if(!empty($item['icon']))
                                                        <i class="fa {{ $item['icon'] }}"></i>
                                                    @endif
                                                    <span class="hide-menu">{{ $item['label'] }}</span>

                                                    @if(!empty($item['badge']))
                                                        <span class="badge bg-{{ $item['badge']['color'] ?? 'primary' }} rounded ms-auto">
                                                            {{ $item['badge']['text'] }}
                                                        </span>
                                                    @endif
                                                </a>
                                            @endif
                                        </li>
                                    @endif
                                @empty
                                    <li class="sidebar-item">
                                        <span class="hide-menu text-muted ps-3">Sin opciones disponibles</span>
                                    </li>
                                @endforelse

                                <!-- Optional divider after section -->
                                @if(!$loop->last)
                                    <li>
                                        <span class="sidebar-divider"></span>
                                    </li>
                                @endif
                            @empty
                                <li class="sidebar-item">
                                    <span class="hide-menu text-muted ps-3">Sin secciones configuradas</span>
                                </li>
                            @endforelse
                        </ul>
                    </nav>
                @empty
                    <!-- No sidebars available -->
                    <div class="p-3 text-center text-muted">
                        <small>No hay menús configurados</small>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</aside>

@push('scripts')
<script>
    /**
     * Toggle sidebar visibility when clicking on mini nav items
     * Updates the active state and shows the corresponding sidebar
     */
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

        // Add click handlers to mini nav items
        document.querySelectorAll('.mini-nav-item').forEach(item => {
            item.addEventListener('click', function() {
                // Get the sidebar ID from data attribute
                const sidebarId = this.dataset.sidebarId;
                if (!sidebarId) {
                    console.warn('No sidebar ID found for mini item:', this.id);
                    return;
                }

                // Remove 'selected' class from all mini items
                document.querySelectorAll('.mini-nav-item').forEach(navItem => {
                    navItem.classList.remove('selected');
                });

                // Add 'selected' class to clicked mini item
                this.classList.add('selected');

                // Hide all sidebars
                document.querySelectorAll('.sidebar-nav').forEach(nav => {
                    nav.classList.remove('d-block');
                    nav.classList.add('d-none');
                });

                // Show the corresponding sidebar
                const targetSidebar = document.querySelector(`#menu-right-${sidebarId}`);
                if (targetSidebar) {
                    targetSidebar.classList.remove('d-none');
                    targetSidebar.classList.add('d-block');
                } else {
                    console.warn(`Sidebar not found: menu-right-${sidebarId}`);
                }

                // Set body attribute to full sidebar mode
                document.body.setAttribute('data-sidebartype', 'full');
            });
        });

        // Handle multilevel menu clicks
        document.querySelectorAll('.sidebar-link.has-arrow').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const isActive = this.classList.contains('active');
                const parentUl = this.closest('ul');
                const submenu = this.nextElementSibling;

                if (!isActive) {
                    // Close any open menus and remove all other classes
                    parentUl.querySelectorAll('ul').forEach(function(ul) {
                        ul.classList.remove('in');
                    });
                    parentUl.querySelectorAll('a').forEach(function(navLink) {
                        navLink.classList.remove('active');
                    });

                    // Open our new menu and add the open class
                    if (submenu) {
                        submenu.classList.add('in');
                    }
                    this.classList.add('active');
                } else {
                    this.classList.remove('active');
                    if (submenu) {
                        submenu.classList.remove('in');
                    }
                }
            });
        });
    });
</script>
@endpush
