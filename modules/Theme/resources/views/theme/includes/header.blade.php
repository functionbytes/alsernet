<header class="topbar">
    <div class="with-vertical">



        <nav class="navbar   p-0">
            <ul class="navbar-nav">
                <li class="nav-item d-flex d-xl-none">
                    <a class="nav-link nav-icon-hover-bg rounded-circle  sidebartoggler " id="headerCollapse" href="javascript:void(0)">
                        <i class="fa-duotone fa-bars fs-6"></i>
                    </a>
                </li>
            </ul>

            <div class="d-block d-lg-none py-9 py-xl-0">

            </div>


            <div class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle " id="navbarNav">
                <div class="d-flex justify-content-end w-100">
                    <ul class="navbar-nav flex-row">

                        @include('notification::components.notifications')

                        <li class="nav-item dropdown">
                            <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2 lh-base">
                                    @php
                                        $initials = strtoupper(substr(Auth::user()->firstname, 0, 1) . substr(Auth::user()->lastname, 0, 1));
                                        $colors = ['#cfcfcf', '#cfcfcf', '#cfcfcf', '#cfcfcf', '#cfcfcf'];
                                        $colorIndex = ord($initials[0]) % count($colors);
                                        $bgColor = $colors[$colorIndex];
                                    @endphp
                                    <div class="rounded-circle rounded-profile  d-flex align-items-center justify-content-center text-white fw-bold bg-light">
                                        {{ $initials }}
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop1">
                                <div class="position-relative px-4 pt-3 pb-2">
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                        <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                            @php
                                                $initials = strtoupper(substr(Auth::user()->firstname, 0, 1) . substr(Auth::user()->lastname, 0, 1));
                                                $colors = ['#cfcfcf', '#cfcfcf', '#cfcfcf', '#cfcfcf', '#cfcfcf'];
                                                $colorIndex = ord($initials[0]) % count($colors);
                                                $bgColor = $colors[$colorIndex];
                                            @endphp
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold bg-light rounded-profile">
                                                {{ $initials }}
                                            </div>
                                        </a>
                                        <div>
                                            <h5 class="mb-1 fs-3">{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}</h5>
                                            <span class="mb-0 d-block text-muted small">
                                                {{ Auth::user()->email }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="message-body">
                                        <a href="{{ route('settings.auth.profile') }}" class="p-2 dropdown-item h6 rounded-1">
                                            Configuración
                                        </a>
                                        <a href="{{ route('auth.logout') }}" class="btn btn-info px-4 waves-effect waves-light w-100">Salir</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>


    </div>
</header>



