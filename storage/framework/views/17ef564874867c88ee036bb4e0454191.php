<?php
$initials = strtoupper(substr(Auth::user()->firstname, 0, 1).substr(Auth::user()->lastname, 0, 1));
?>

<header class="mc-topbar">

    
    <div class="mc-topbar-left">
        <button class="mc-sidebar-toggle" data-sidebar-smart-toggle="" aria-label="Toggle sidebar">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 3H7.5V17H4A2 2 0 012 15V5A2 2 0 014 3Z"
                      fill="var(--color-teal)" opacity="0.2" stroke="none"/>
                <rect x="2" y="3" width="16" height="14" rx="2"/>
                <path d="M7.5 3v14"/>
            </svg>
        </button>
    </div>

    
    <div class="mc-topbar-center mc-topbar-search-wrap">
        <div class="mc-topbar-search" style="position:relative;width:100%;max-width:360px;">
            <svg class="mc-topbar-search-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
                 width="14" height="14" style="flex-shrink:0;color:var(--color-text-muted);">
                <circle cx="7" cy="7" r="5"/>
                <path d="M11 11l3.5 3.5"/>
            </svg>
            <input type="text"
                   id="global-search-input"
                   placeholder="Buscar en el sistema..."
                   autocomplete="off"
                   style="flex:1;border:none;background:transparent;font-size:13px;color:var(--color-text);outline:none;">
            <div id="global-search-results"
                 style="position:absolute;top:calc(100% + 6px);left:0;right:0;background:var(--color-card-bg);border:1px solid var(--color-border);border-radius:var(--radius-card);box-shadow:var(--shadow-dropdown);z-index:999;max-height:400px;overflow-y:auto;display:none;">
            </div>
        </div>
    </div>

    
    <div class="mc-topbar-right">

        
        <div class="mc-dropdown" data-dropdown="" style="position:relative;">
            <button class="mc-topbar-icon-btn" aria-label="Tema" id="mc-theme-btn">
                <span id="theme-icon-light">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" width="18" height="18">
                        <circle cx="10" cy="10" r="4"/>
                        <path d="M10 2v2M10 16v2M2 10h2M16 10h2M4.2 4.2l1.4 1.4M14.4 14.4l1.4 1.4M4.2 15.8l1.4-1.4M14.4 5.6l1.4-1.4"/>
                    </svg>
                </span>
                <span id="theme-icon-dark" class="mc-hidden">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" width="18" height="18">
                        <path d="M17 12A7 7 0 118 3a5 5 0 009 9z"/>
                    </svg>
                </span>
            </button>
            <div class="mc-dropdown-menu mc-dropdown-menu-end" data-mc-theme-menu=""
                 style="min-width:140px;">
                <a href="#" class="mc-dropdown-item" data-mc-theme-mode="light">
                    <i class="fas fa-sun fa-fw me-2"></i> Claro
                </a>
                <a href="#" class="mc-dropdown-item" data-mc-theme-mode="dark">
                    <i class="fas fa-moon fa-fw me-2"></i> Oscuro
                </a>
                <a href="#" class="mc-dropdown-item" data-mc-theme-mode="system">
                    <i class="fas fa-desktop fa-fw me-2"></i> Sistema
                </a>
            </div>
        </div>

        
        <?php echo $__env->make('notification::components.notifications', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div class="mc-topbar-avatar-wrap"
             title="<?php echo e(Auth::user()->firstname); ?> <?php echo e(Auth::user()->lastname); ?>"
             onclick="document.querySelector('.mc-sidebar-user[data-dropdown]')?.click()">
            <?php echo e($initials); ?>

        </div>

    </div>
</header>


<script>
(function () {
    const input = document.getElementById('global-search-input');
    const box   = document.getElementById('global-search-results');
    if (!input || !box) return;

    const searchUrl = '<?php echo e(route('search.global')); ?>';
    const colorMap  = { warning:'text-warning', primary:'text-primary', success:'text-success',
                        danger:'text-danger', info:'text-info', secondary:'text-secondary' };
    let timer;

    function esc(s) {
        const d = document.createElement('span');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function render(results) {
        if (!results.length) {
            box.innerHTML = '<div style="padding:12px 16px;color:var(--color-text-muted);font-size:13px;text-align:center;">Sin resultados</div>';
            return;
        }
        box.innerHTML = results.map(r => {
            const cc = colorMap[r.color] || 'text-secondary';
            const sub = r.subtitle ? `<div style="font-size:11px;color:var(--color-text-muted)">${esc(r.subtitle)}</div>` : '';
            return `<a href="${esc(r.url)}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;text-decoration:none;color:var(--color-text);border-bottom:1px solid var(--color-border);" class="mc-sr-item">
                <span class="${cc}" style="width:16px;text-align:center"><i class="${esc(r.icon)}"></i></span>
                <span style="flex:1;min-width:0"><div style="font-size:13px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.title)}</div>${sub}</span>
                <span style="font-size:10px;padding:2px 6px;background:var(--color-hover-bg);border-radius:4px;color:var(--color-text-muted)">${esc(r.type_label)}</span>
            </a>`;
        }).join('');
    }

    function search(q) {
        if (q.length < 2) { box.style.display = 'none'; return; }
        $.get(searchUrl, { q }, data => {
            render(data.results || []);
            box.style.display = 'block';
        }).fail(() => { box.style.display = 'none'; });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        timer = setTimeout(() => search(q), 300);
    });

    input.addEventListener('focus', function () {
        if (this.value.trim().length >= 2) box.style.display = 'block';
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !box.contains(e.target)) box.style.display = 'none';
    });

    box.addEventListener('mouseover', e => {
        e.target.closest('.mc-sr-item')?.style.setProperty('background', 'var(--color-hover-bg)');
    });
    box.addEventListener('mouseout', e => {
        e.target.closest('.mc-sr-item')?.style.removeProperty('background');
    });
}());
</script>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/theme/includes/header.blade.php ENDPATH**/ ?>