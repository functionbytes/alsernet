@php
    $user         = Auth::user();
    $userInitials = strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1));
    $roleSlug     = $user->getRoleNames()->first() ?? '';
    $roleLabels   = [
        'super-settings'  => 'Super Admin',
        'super-admin'     => 'Super Admin',
        'admin'           => 'Administrador',
        'administrative'  => 'Administrativo',
        'manager'         => 'Gerente',
        'settings'        => 'Configurador',
        'helpdesk-admin'  => 'Admin Helpdesk',
        'helpdesk-agent'  => 'Agente Helpdesk',
        'customer'        => 'Cliente',
        'support'         => 'Soporte',
        'callcenter'      => 'Call Center',
        'blog-admin'      => 'Admin Blog',
        'blog-editor'     => 'Editor',
        'blog-author'     => 'Autor',
        'ecommerce-admin' => 'Admin Tienda',
        'ecommerce-manager' => 'Gerente Tienda',
    ];
    $userRoleLabel = $roleLabels[$roleSlug] ?? ucwords(str_replace('-', ' ', $roleSlug));
@endphp

<header class="app-header">
    <div class="app-header-inner">
        <button class="app-toggler" type="button" aria-label="app toggler">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.66699 12.6668L3.66699 8.00016L7.66699 3.3335" stroke="#1C274C" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                <path opacity="0.5" d="M12.667 12.6668L8.66699 8.00016L12.667 3.3335" stroke="#1C274C" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div class="app-header-start d-none d-md-flex">
            <button type="button" id="gs-trigger"
                    class="d-flex align-items-center gap-2 form-control form-control-fill text-muted">
                <i class="fas fa-search gs-trigger-icon"></i>
                <span>Buscar en el sistema…</span>
                <kbd class="ms-auto gs-trigger-kbd">⌘K</kbd>
            </button>
        </div>

        {{-- ── Global search modal ── --}}
        <div id="gs-backdrop" class="gs-backdrop"></div>
        <div id="gs-dialog" class="gs-dialog" role="dialog" aria-modal="true" aria-label="Búsqueda global">
            <div class="gs-head">
                <div class="gs-head-icon"><i class="fas fa-search"></i></div>
                <div class="gs-head-text">
                    <div class="gs-head-label">SISTEMA · BÚSQUEDA</div>
                    <div class="gs-head-title">Buscar en el sistema</div>
                </div>
                <button class="gs-close" type="button" id="gs-close-btn" aria-label="Cerrar">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="gs-body">
                <div class="gs-search-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="gs-input"
                           placeholder="Buscar usuarios, tickets, conversaciones…"
                           autocomplete="off">
                </div>
                <div id="gs-results">
                    <div class="gs-hint">
                        <i class="fas fa-search"></i>
                        Escribe al menos 3 caracteres para buscar
                    </div>
                </div>
            </div>
            <div class="gs-foot">
                <span class="gs-foot-hint">
                    <kbd>↑↓</kbd> navegar &nbsp;
                    <kbd>↵</kbd> abrir &nbsp;
                    <kbd>Esc</kbd> cerrar
                </span>
            </div>
        </div>

        <div class="app-header-end">
            <div class="vr my-3"></div>
            <div class="d-flex align-items-center gap-sm-2 gap-0 px-lg-4 px-sm-2 px-1">
                {{-- Notifications --}}
                <div class="dropdown text-end"
                     id="notifications-dropdown"
                     data-api-index-route="{{ route('api.notifications.index') }}"
                     data-api-read-route="{{ url('/api/notifications/{id}/read') }}"
                     data-mark-all-read-route="{{ route('api.notifications.mark-all-read') }}"
                     data-refresh-interval="60000"
                     data-limit="4">
                    <button type="button"
                            class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false">
                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.7491 10.2096V9.50497C18.7491 5.63623 15.7274 2.5 12 2.5C8.27256 2.5 5.25087 5.63623 5.25087 9.50497V10.2096C5.25087 11.0552 5.00972 11.8818 4.5578 12.5854L3.45036 14.3095C2.43882 15.8843 3.21105 18.0249 4.97036 18.5229C9.57274 19.8257 14.4273 19.8257 19.0296 18.5229C20.789 18.0249 21.5612 15.8843 20.5496 14.3095L19.4422 12.5854C18.9903 11.8818 18.7491 11.0552 18.7491 10.2096Z" stroke="var(--bs-heading-color)" stroke-width="2"/>
                            <path opacity="0.5" d="M7.5 19.5C8.15503 21.2478 9.92246 22.5 12 22.5C14.0775 22.5 15.845 21.2478 16.5 19.5" stroke="var(--bs-heading-color)" stroke-width="2" stroke-linecap="round"/>
                            <path opacity="0.5" d="M12 6.5V10.5" stroke="var(--bs-heading-color)" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <div id="notification-badge" class="badge-pulse"></div>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end notif-panel-dd">
                        <div class="notif-panel-head">
                            <div class="notif-panel-icon"><i class="far fa-bell"></i></div>
                            <div class="notif-panel-title-wrap">
                                <div class="notif-panel-label">CUENTA</div>
                                <div class="notif-panel-title">
                                    Notificaciones
                                    <span class="notif-chip" id="unread-count-text" style="display:none"></span>
                                </div>
                            </div>
                            <button class="notif-panel-close" id="btn-notif-close" type="button">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>

                        <div class="notif-panel-body">
                            <div id="notifications-loading" class="notif-panel-loading">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Cargando…</span>
                                </div>
                                <p class="text-muted mt-2 mb-0 small">Cargando notificaciones…</p>
                            </div>
                            <div id="notifications-list" style="display:none"></div>
                            <div id="notifications-empty" class="notif-panel-empty" style="display:none">
                                <i class="far fa-bell-slash"></i>
                                <h6 class="fw-semibold mb-1">Sin notificaciones</h6>
                                <p class="text-muted mb-0 small">No tienes notificaciones nuevas</p>
                            </div>
                        </div>

                        <div id="notif-extra" class="notif-extra" style="display:none"></div>

                        <div class="notif-panel-foot">
                            <a href="{{ route('notifications.index') }}" class="btn btn-primary">Ver todas las notificaciones</a>
                            <button class="btn btn-outline-secondary" id="btn-mark-all-read" type="button">Marcar todas como leídas</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vr my-3"></div>

            {{-- User dock --}}
            <div class="dock-wrap ms-sm-3 ms-2 ms-lg-4" id="user-dock-wrap">
                <button class="dock-anchor" id="user-dock-trigger" type="button">
                    <div class="body">
                        <span class="nm">{{ $user->firstname }} {{ $user->lastname }}</span>
                        <span class="role"><i class="fas fa-chevron-down"></i> {{ $userRoleLabel }}</span>
                    </div>
                    <div class="av">{{ $userInitials }}<span class="pres"></span></div>
                </button>

                <div class="dock" id="user-dock">
                    <div class="dock-head">
                        <div class="av">{{ $userInitials }}</div>
                        <div class="body">
                            <span class="nm">{{ $user->firstname }} {{ $user->lastname }}</span>
                            <span class="em">{{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="dock-list">
                        <a href="{{ route('settings.auth.profile') }}" class="dock-item">
                            <i class="far fa-user"></i> Ver perfil
                        </a>
                        <a href="{{ route('settings.auth.profile') }}" class="dock-item">
                            <i class="fas fa-gear"></i> Configuración
                        </a>
                        <a href="{{ route('notifications.index') }}" class="dock-item">
                            <i class="far fa-bell"></i> Notificaciones
                        </a>
                        <div class="dock-divider"></div>
                        <button class="dock-item" type="button" id="btn-toggle-theme">
                            <i class="fas fa-circle-half-stroke"></i> Cambiar tema
                        </button>
                        <div class="dock-divider"></div>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="dock-item danger">
                                <i class="fas fa-arrow-right-from-bracket"></i> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ─── Global search modal script ──────────────────────────── --}}
<script>
(function () {
    var trigger   = document.getElementById('gs-trigger');
    var backdrop  = document.getElementById('gs-backdrop');
    var dialog    = document.getElementById('gs-dialog');
    var closeBtn  = document.getElementById('gs-close-btn');
    var input     = document.getElementById('gs-input');
    var results   = document.getElementById('gs-results');
    if (!trigger || !dialog) return;

    var searchUrl  = '{{ route("search.global") }}';
    var colorClass = { primary:'c-primary', info:'c-info', success:'c-success', warning:'c-warning', danger:'c-danger', secondary:'c-secondary' };
    var timer, activeIdx = -1, items = [];

    function esc(s) {
        var d = document.createElement('span');
        d.textContent = s != null ? String(s) : '';
        return d.innerHTML;
    }

    function open() {
        backdrop.classList.add('open');
        dialog.classList.add('open');
        setTimeout(function () { input.focus(); }, 50);
    }

    function close() {
        backdrop.classList.remove('open');
        dialog.classList.remove('open');
        input.value = '';
        activeIdx = -1;
        items = [];
        results.innerHTML = '<div class="gs-hint"><i class="fas fa-search"></i>Escribe al menos 3 caracteres para buscar</div>';
    }

    function setActive(idx) {
        items.forEach(function (el, i) {
            el.classList.toggle('active', i === idx);
        });
        activeIdx = idx;
    }

    function render(data) {
        if (!data.length) {
            results.innerHTML = '<div class="gs-empty"><i class="fas fa-search-minus"></i>Sin resultados para tu búsqueda</div>';
            items = [];
            return;
        }
        results.innerHTML = data.map(function (r) {
            var cc  = colorClass[r.color] || 'c-secondary';
            var sub = r.subtitle ? '<div class="gs-result-sub">' + esc(r.subtitle) + '</div>' : '';
            return '<a href="' + esc(r.url) + '" class="gs-result-item">' +
                '<span class="gs-result-av ' + cc + '"><i class="' + esc(r.icon) + '"></i></span>' +
                '<span class="gs-result-body">' +
                    '<div class="gs-result-title">' + esc(r.title) + '</div>' + sub +
                '</span>' +
                '<span class="gs-result-badge">' + esc(r.type_label) + '</span>' +
            '</a>';
        }).join('');
        items = Array.from(results.querySelectorAll('.gs-result-item'));
        activeIdx = -1;
    }

    function search(q) {
        if (q.length < 3) {
            results.innerHTML = '<div class="gs-hint"><i class="fas fa-search"></i>Escribe al menos 3 caracteres para buscar</div>';
            items = [];
            return;
        }
        results.innerHTML = '<div class="gs-loading"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
        $.get(searchUrl, { q: q }, function (data) {
            render(data.results || []);
        }).fail(function () {
            results.innerHTML = '<div class="gs-empty"><i class="fas fa-circle-exclamation"></i>Error al buscar. Intenta de nuevo.</div>';
        });
    }

    trigger.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = this.value.trim();
        timer = setTimeout(function () { search(q); }, 280);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { close(); return; }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIdx + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIdx - 1, 0));
        } else if (e.key === 'Enter') {
            var target = activeIdx >= 0 ? items[activeIdx] : items[0];
            if (target) window.location.href = target.getAttribute('href');
        }
    });

    // Cmd+K / Ctrl+K shortcut
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            dialog.classList.contains('open') ? close() : open();
        }
        if (e.key === 'Escape' && dialog.classList.contains('open')) close();
    });
}());
</script>

@push('scripts')
    <script src="{{ asset('modules/Notification/js/notifications.js') }}?v={{ md5_file(base_path('modules/Notification/public/js/notifications.js')) }}"></script>
@endpush
