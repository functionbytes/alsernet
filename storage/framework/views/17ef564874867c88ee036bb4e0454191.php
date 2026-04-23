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



                        <?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

echo $__env->make('notification::components.notifications', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <li class="nav-item dropdown">
                            <?php
                                                        $initials = strtoupper(substr(Auth::user()->firstname, 0, 1).substr(Auth::user()->lastname, 0, 1));
                        ?>
                            <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                <div class="d-flex align-items-center gap-2 lh-base">
                                    <div class="rounded-circle rounded-profile d-flex align-items-center justify-content-center text-white fw-bold bg-light">
                                        <?php echo e($initials); ?>

                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop1">
                                <div class="position-relative px-4 pt-3 pb-2">
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                        <a class="nav-link" href="javascript:void(0)" aria-expanded="false">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold bg-light rounded-profile">
                                                <?php echo e($initials); ?>

                                            </div>
                                        </a>
                                        <div>
                                            <h5 class="mb-1 fs-3"><?php echo e(Auth::user()->firstname); ?> <?php echo e(Auth::user()->lastname); ?></h5>
                                            <span class="mb-0 d-block text-muted">
                                                <?php echo e(Auth::user()->email); ?>

                                            </span>
                                        </div>
                                    </div>
                                    <div class="message-body">
                                        <a href="<?php echo e(route('settings.auth.profile')); ?>" class="p-2 dropdown-item h6 rounded-1">
                                            Configuración
                                        </a>
                                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (config('auth.auth-policy.lock_screen.enabled', true)) { ?>
                                            <form method="POST" action="<?php echo e(route('auth.lock.lock')); ?>" class="mb-2">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-outline-secondary px-4 waves-effect waves-light w-100">
                                                    <i class="fas fa-lock me-1"></i> Bloquear sesión
                                                </button>
                                            </form>
                                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                        <form method="POST" action="<?php echo e(route('auth.logout')); ?>">
                                            <?php echo csrf_field(); ?>
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

<script>
(function () {
    const input = document.getElementById('global-search-input');
    const resultsBox = document.getElementById('global-search-results');
    if (!input || !resultsBox) return;

    const searchUrl = '<?php echo e(route('search.global')); ?>';
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

        function esc(str) {
            const d = document.createElement('span');
            d.textContent = str ?? '';
            return d.innerHTML;
        }

        resultsBox.innerHTML = results.map(function (r) {
            const colorClass = colorMap[r.color] || 'text-secondary';
            const subtitle = r.subtitle ? '<div class="text-muted" style="font-size:.75rem">' + esc(r.subtitle) + '</div>' : '';
            return '<a href="' + esc(r.url) + '" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none text-dark border-bottom search-result-item">'
                + '<div class="flex-shrink-0"><i class="' + esc(r.icon) + ' ' + colorClass + '"></i></div>'
                + '<div class="flex-grow-1 min-w-0"><div class="fw-semibold small text-truncate">' + esc(r.title) + '</div>' + subtitle + '</div>'
                + '<div class="flex-shrink-0"><span class="badge bg-light text-dark" style="font-size:.65rem">' + esc(r.type_label) + '</span></div>'
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
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/theme/includes/header.blade.php ENDPATH**/ ?>