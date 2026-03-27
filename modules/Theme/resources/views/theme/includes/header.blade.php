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

                        <li class="nav-item d-flex align-items-center me-2">
                            <div class="search-global position-relative" style="width:260px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text"
                                           id="global-search-input"
                                           class="form-control border-start-0 ps-0"
                                           placeholder="Buscar en todo el sistema..."
                                           autocomplete="off">
                                </div>
                                <div id="global-search-results"
                                     class="position-absolute top-100 start-0 end-0 bg-white border rounded-2 shadow-lg mt-1 d-none"
                                     style="z-index:9999;max-height:400px;overflow-y:auto;">
                                </div>
                            </div>
                        </li>

                        <li class="nav-item d-flex align-items-center me-1">
                            <button id="darkModeToggle" class="btn btn-sm btn-outline-secondary rounded-circle p-1"
                                    title="Cambiar tema" style="width:32px;height:32px;line-height:1;">
                                <i class="fas fa-moon" id="darkModeIcon" style="font-size:0.8rem;"></i>
                            </button>
                        </li>

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
                                        <form method="POST" action="{{ route('auth.logout') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-info px-4 waves-effect waves-light w-100">Salir</button>
                                        </form>
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

<style>
#global-search-results .search-result-item:last-child {
    border-bottom: none !important;
}
#global-search-results .search-result-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
(function () {
    const input = document.getElementById('global-search-input');
    const resultsBox = document.getElementById('global-search-results');
    if (!input || !resultsBox) return;

    const searchUrl = '{{ route("search.global") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const colorMap = {
        warning: 'text-warning',
        primary: 'text-primary',
        success: 'text-success',
        danger: 'text-danger',
        info: 'text-info',
        secondary: 'text-secondary',
    };

    let timer;

    function renderResults(results) {
        if (!results.length) {
            resultsBox.innerHTML = '<div class="p-3 text-muted text-center small">Sin resultados</div>';
            return;
        }

        resultsBox.innerHTML = results.map(function (r) {
            const colorClass = colorMap[r.color] || 'text-secondary';
            const subtitle = r.subtitle ? '<div class="text-muted" style="font-size:.75rem">' + r.subtitle + '</div>' : '';
            return '<a href="' + r.url + '" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none text-dark border-bottom search-result-item">'
                + '<div class="flex-shrink-0"><i class="' + r.icon + ' ' + colorClass + '"></i></div>'
                + '<div class="flex-grow-1 min-w-0"><div class="fw-semibold small text-truncate">' + r.title + '</div>' + subtitle + '</div>'
                + '<div class="flex-shrink-0"><span class="badge bg-light text-dark" style="font-size:.65rem">' + r.type_label + '</span></div>'
                + '</a>';
        }).join('');
    }

    function doSearch(q) {
        if (q.length < 2) {
            resultsBox.classList.add('d-none');
            return;
        }

        $.get(searchUrl, { q: q }, function (data) {
            renderResults(data.results || []);
            resultsBox.classList.remove('d-none');
        }).fail(function () {
            resultsBox.classList.add('d-none');
        });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        timer = setTimeout(function () { doSearch(q); }, 300);
    });

    input.addEventListener('focus', function () {
        if (this.value.trim().length >= 2) {
            resultsBox.classList.remove('d-none');
        }
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });

    resultsBox.addEventListener('mouseover', function (e) {
        const item = e.target.closest('.search-result-item');
        if (item) item.classList.add('bg-light');
    });

    resultsBox.addEventListener('mouseout', function (e) {
        const item = e.target.closest('.search-result-item');
        if (item) item.classList.remove('bg-light');
    });
}());
</script>
