<!-- Sidebar Start -->
<aside class="left-sidebar">
    <div>
        <nav class="sidebar-nav scroll-sidebar container-fluid">
            <ul id="sidebarnav">

                <!-- Home -->
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        <span><i class="fa-duotone fa-house"></i></span>
                        <span class="hide-menu">Inicio</span>
                    </a>
                </li>

                <!-- Tickets -->
                @php
                    $ticketRoutes = ['callcenter.tickets', 'callcenter.faqs'];
                    $canViewTickets = auth()->user()?->hasAnyPermission([
                        'tickets.view', 'tickets.view.assigned', 'tickets.create'
                    ]);
                @endphp
                @if ($canViewTickets)
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow {{ request()->routeIs($ticketRoutes) ? 'active' : '' }}" href="javascript:void(0)">
                            <span><i class="fa-duotone fa-headset"></i></span>
                            <span class="hide-menu">Tickets</span>
                        </a>
                        <ul class="collapse first-level {{ request()->routeIs($ticketRoutes) ? 'in' : '' }}">
                            <li class="sidebar-item">
                                <a href="{{ route('callcenter.tickets') }}" class="sidebar-link {{ request()->routeIs('callcenter.tickets') ? 'active' : '' }}">
                                    <span class="hide-menu">Tickets</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a href="{{ route('callcenter.faqs') }}" class="sidebar-link {{ request()->routeIs('callcenter.faqs') ? 'active' : '' }}">
                                    <span class="hide-menu">Preguntas frecuentes</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <!-- Devoluciones -->
                @php
                    use Illuminate\Support\Str;

                    $returnRoutes = [
                        'callcenter.returns', 'callcenter.returns.create',
                        'callcenter.returns.edit', 'callcenter.returns.update',
                        'callcenter.returns.destroy'
                    ];
                    $activeReturnRoute = Str::is($returnRoutes, request()->route()->getName());

                    $canViewReturns = auth()->user()?->hasAnyPermission([
                        'returns.view.own', 'returns.view.assigned', 'returns.view.all'
                    ]);
                    $canCreateReturns = auth()->user()?->can('returns.create');
                @endphp
                @if ($canViewReturns)
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow {{ $activeReturnRoute ? 'active' : '' }}" href="javascript:void(0)">
                            <span><i class="fa-duotone fa-boxes-packing"></i></span>
                            <span class="hide-menu">Devoluciones</span>
                        </a>
                        <ul class="collapse first-level {{ $activeReturnRoute ? 'in' : '' }}">
                            <li class="sidebar-item">
                                <a href="{{ route('callcenter.returns.index') }}" class="sidebar-link {{ request()->routeIs('callcenter.returns.index') ? 'active' : '' }}">
                                    <span class="hide-menu">Listado</span>
                                </a>
                            </li>
                            @if ($canCreateReturns)
                                <li class="sidebar-item">
                                    <a href="{{ route('callcenter.returns.create') }}" class="sidebar-link {{ request()->routeIs('callcenter.returns.create') ? 'active' : '' }}">
                                        <span class="hide-menu">Crear</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @php
                    $configRoutes = ['callcenter.settings.profile', 'callcenter.settings.notifications'];
                    $canAccessSettings = auth()->user()?->can('system.settings.manage');
                @endphp
                @if ($canAccessSettings)
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow {{ request()->routeIs($configRoutes) ? 'active' : '' }}" href="javascript:void(0)">
                            <span><i class="fa-duotone fa-gear"></i></span>
                            <span class="hide-menu">Configuración</span>
                        </a>
                        <ul class="collapse first-level {{ request()->routeIs($configRoutes) ? 'in' : '' }}">
                            <li class="sidebar-item">
                                <a href="{{ route('callcenter.settings.profile') }}" class="sidebar-link {{ request()->routeIs('callcenter.settings.profile') ? 'active' : '' }}">
                                    <span class="hide-menu">Perfil de usuario</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a href="{{ route('callcenter.settings.notifications') }}" class="sidebar-link {{ request()->routeIs('callcenter.settings.notifications') ? 'active' : '' }}">
                                    <span class="hide-menu">Notificaciones</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

            </ul>
        </nav>
    </div>
</aside>
<!-- Sidebar End -->
