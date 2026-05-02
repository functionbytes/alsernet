<?php
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Theme\Services\NavService;

['miniItems' => $miniItems, 'sidebars' => $allSidebars, 'activeSidebarId' => $activeSidebarId, 'activeItemRoute' => $activeItemRoute] = NavService::getNavDataForUser();

$siteName = getSiteName();
$initials = strtoupper(substr(Auth::user()->firstname, 0, 1).substr(Auth::user()->lastname, 0, 1));
$userName = Auth::user()->firstname.' '.Auth::user()->lastname;
$userEmail = Auth::user()->email;

try {
    $dashboardUrl = route('dashboard.index');
} catch (Exception $e) {
    $dashboardUrl = url('/panel/dashboard');
}
try {
    $profileUrl = route('settings.auth.profile');
} catch (Exception $e) {
    $profileUrl = '#';
}
try {
    $logoutUrl = route('auth.logout');
} catch (Exception $e) {
    $logoutUrl = '/logout';
}
try {
    $lockUrl = route('auth.lock.lock');
} catch (Exception $e) {
    $lockUrl = null;
}

$svgDefault = '<circle cx="10" cy="10" r="4.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/><circle cx="10" cy="10" r="4.5"/>';

// SVG icon paths (inner SVG elements) per main sidebar ID
$svgMainIcons = [
    'dashboard' => '<rect x="2" y="2" width="7" height="7" rx="1.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="2" y="2" width="7" height="7" rx="1.5"/>'.
        '<rect x="11" y="2" width="7" height="7" rx="1.5"/>'.
        '<rect x="2" y="11" width="7" height="7" rx="1.5"/>'.
        '<rect x="11" y="11" width="7" height="7" rx="1.5"/>',

    'users' => '<circle cx="10" cy="6.5" r="3" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="6.5" r="3"/>'.
        '<path d="M3.5 17.5c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6"/>',

    'campaigns' => '<path d="M3 8.5h1.5L13 4.5v11L4.5 11.5H3a1 1 0 01-1-1v-2a1 1 0 011-1z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M3 8.5h1.5L13 4.5v11L4.5 11.5H3a1 1 0 01-1-1v-2a1 1 0 011-1z"/>'.
        '<path d="M4.5 11.5l1.5 3"/>'.
        '<path d="M13 7c2.2 1 3.5 2 3.5 3s-1.3 2-3.5 3"/>',

    'campaign_sending_servers' => '<rect x="2" y="3" width="16" height="5.5" rx="1.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="2" y="3" width="16" height="5.5" rx="1.5"/>'.
        '<rect x="2" y="11.5" width="16" height="5.5" rx="1.5"/>'.
        '<circle cx="15.5" cy="5.75" r="1" fill="currentColor" stroke="none"/>'.
        '<circle cx="15.5" cy="14.25" r="1" fill="currentColor" stroke="none"/>',

    'mailers' => '<rect x="2" y="4.5" width="16" height="12" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="2" y="4.5" width="16" height="12" rx="2"/>'.
        '<path d="M2 7.5l8 5 8-5"/>',

    'reviews' => '<path d="M10 2.5l2 5.5H18l-4.5 3.5 1.5 5.5L10 14l-5 2.5 1.5-5.5L2 8h6z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M10 2.5l2 5.5H18l-4.5 3.5 1.5 5.5L10 14l-5 2.5 1.5-5.5L2 8h6z"/>',

    'attentions' => '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5c0 2-1.5 3.5-3.5 4.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5"/>'.
        '<path d="M4.5 11a2 2 0 00-2 2v1a2 2 0 002 2"/>'.
        '<path d="M15.5 11a2 2 0 012 2v1a2 2 0 01-2 2"/>'.
        '<path d="M10 17v2M8 19h4"/>',

    'helpdesk' => '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5c0 2-1.5 3.5-3.5 4.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5"/>'.
        '<path d="M4.5 11a2 2 0 00-2 2v1a2 2 0 002 2"/>'.
        '<path d="M15.5 11a2 2 0 012 2v1a2 2 0 01-2 2"/>'.
        '<path d="M10 17v2M8 19h4"/>',

    'forms-inbox' => '<rect x="3" y="2" width="14" height="16" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="3" y="2" width="14" height="16" rx="2"/>'.
        '<path d="M7 7h6M7 10.5h6M7 14h4"/>',

    'ecommerce' => '<path d="M2 2.5h2.5L7 13.5h10l1.5-8H5.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M2 2.5h2.5L7 13.5h10l1.5-8H5.5"/>'.
        '<circle cx="8.5" cy="17" r="1.5"/>'.
        '<circle cx="14.5" cy="17" r="1.5"/>',

    'blog' => '<rect x="3" y="2" width="14" height="16" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="3" y="2" width="14" height="16" rx="2"/>'.
        '<path d="M7 7h6M7 10.5h4"/>'.
        '<path d="M13 14l1.5-5 1.5 5M13.5 12.5h2"/>',

    'pages' => '<path d="M5 2h8l4 4v12a1 1 0 01-1 1H5a1 1 0 01-1-1V3a1 1 0 011-1z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M5 2h8l4 4v12a1 1 0 01-1 1H5a1 1 0 01-1-1V3a1 1 0 011-1z"/>'.
        '<path d="M13 2v4h4"/>'.
        '<path d="M7 9h6M7 12.5h6M7 16h4"/>',

    'media' => '<rect x="2" y="4" width="16" height="12" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="2" y="4" width="16" height="12" rx="2"/>'.
        '<circle cx="7.5" cy="8.5" r="1.5"/>'.
        '<path d="M2 13.5l4.5-4.5 3.5 3.5 2.5-2.5 5.5 5.5"/>',

    'notifications' => '<path d="M10 2a6 6 0 016 6v3.5l1.5 2h-15L4 11.5V8a6 6 0 016-6z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M10 2a6 6 0 016 6v3.5l1.5 2h-15L4 11.5V8a6 6 0 016-6z"/>'.
        '<path d="M8 13.5a2 2 0 004 0"/>',

    'ads' => '<path d="M2.5 7.5h10L17.5 10l-5 3h-10V7.5z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M2.5 7.5h10L17.5 10l-5 3h-10V7.5z"/>'.
        '<path d="M7 13.5l1 3M5.5 16.5h3"/>',

    'faqs' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<path d="M7.5 7.5C7.5 6 8.6 5 10 5s2.5 1 2.5 2.5c0 2-2.5 2.5-2.5 4"/>'.
        '<circle cx="10" cy="15" r=".75" fill="currentColor" stroke="none"/>',

    'locations' => '<circle cx="10" cy="8.5" r="3" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="8.5" r="3"/>'.
        '<path d="M16 8.5c0 5-6 9.5-6 9.5S4 13.5 4 8.5a6 6 0 1112 0z"/>',
];

// SVG icon paths per settings section title
$svgSettingsIcons = [
    'Historial de actividad' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<path d="M10 6v4l3 3"/>',

    'Analytics' => '<polyline points="2,16 6,10 10,13 14,5 18,10"/>'.
        '<path d="M2 16h16"/>',

    'Configuraciones' => '<circle cx="10" cy="10" r="3" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="3"/>'.
        '<path d="M10 2v2.5M10 15.5V18M2 10h2.5M15.5 10H18M4.1 4.1l1.8 1.8M14.1 14.1l1.8 1.8M4.1 15.9l1.8-1.8M14.1 5.9l1.8-1.8"/>',

    'Configuración de cookies' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<circle cx="8" cy="8" r="1" fill="currentColor" stroke="none"/>'.
        '<circle cx="12.5" cy="7.5" r="1" fill="currentColor" stroke="none"/>'.
        '<circle cx="7" cy="12.5" r="1" fill="currentColor" stroke="none"/>'.
        '<circle cx="13" cy="12" r="1" fill="currentColor" stroke="none"/>',

    'Localización' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<path d="M10 2.5c-2.5 2.5-3 4.5-3 7.5s.5 5 3 7.5"/>'.
        '<path d="M10 2.5c2.5 2.5 3 4.5 3 7.5s-.5 5-3 7.5"/>'.
        '<path d="M2.5 10h15"/>',

    'Marketing' => '<path d="M3 9h1.5L13 5v10l-8.5-4H3a1 1 0 01-1-1v-1a1 1 0 011-1z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M3 9h1.5L13 5v10l-8.5-4H3a1 1 0 01-1-1v-1a1 1 0 011-1z"/>'.
        '<path d="M13 7.5c2 1 3 2 3 2.5s-1 1.5-3 2.5"/>',

    'Reseñas' => '<path d="M10 2.5l2 5.5H18l-4.5 3.5 1.5 5.5L10 14l-5 2.5 1.5-5.5L2 8h6z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M10 2.5l2 5.5H18l-4.5 3.5 1.5 5.5L10 14l-5 2.5 1.5-5.5L2 8h6z"/>',

    'SEO' => '<circle cx="9" cy="9" r="6.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="9" cy="9" r="6.5"/>'.
        '<path d="M14 14l4 4"/>',

    'Roles y Permisos' => '<path d="M10 2L4 5v5c0 4 2.7 7.7 6 9 3.3-1.3 6-5 6-9V5z" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M10 2L4 5v5c0 4 2.7 7.7 6 9 3.3-1.3 6-5 6-9V5z"/>'.
        '<path d="M7.5 10l2 2 3-3.5"/>',

    'Plantillas' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<circle cx="10" cy="10" r="3"/>'.
        '<path d="M10 5v2M10 13v2M5 10h2M13 10h2"/>',

    'Configuración de Email' => '<rect x="2" y="4.5" width="16" height="12" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="2" y="4.5" width="16" height="12" rx="2"/>'.
        '<path d="M2 7.5l8 5 8-5"/>',

    'Ecommerce' => '<path d="M2 2.5h2.5L7 13.5h10l1.5-8H5.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M2 2.5h2.5L7 13.5h10l1.5-8H5.5"/>'.
        '<circle cx="8.5" cy="17" r="1.5"/>'.
        '<circle cx="14.5" cy="17" r="1.5"/>',

    'Gestión de base de datos' => '<ellipse cx="10" cy="5.5" rx="7" ry="2.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<ellipse cx="10" cy="5.5" rx="7" ry="2.5"/>'.
        '<path d="M3 5.5v4c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-4"/>'.
        '<path d="M3 9.5v4c0 1.4 3.1 2.5 7 2.5s7-1.1 7-2.5v-4"/>',

    'Atenciones' => '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5c0 2-1.5 3.5-3.5 4.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<path d="M4 7.5C4 5 6.7 3 10 3s6 2 6 4.5"/>'.
        '<path d="M4.5 11a2 2 0 00-2 2v1a2 2 0 002 2"/>'.
        '<path d="M15.5 11a2 2 0 012 2v1a2 2 0 01-2 2"/>'.
        '<path d="M10 17v2M8 19h4"/>',

    'Estado del sistema' => '<circle cx="10" cy="10" r="7.5" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<circle cx="10" cy="10" r="7.5"/>'.
        '<path d="M6.5 10l3 3 4-5"/>',

    'Formularios' => '<rect x="3" y="2" width="14" height="16" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="3" y="2" width="14" height="16" rx="2"/>'.
        '<path d="M7 7h6M7 10.5h6M7 14h4"/>',

    'Copias de seguridad' => '<rect x="3" y="3" width="14" height="11" rx="2" fill="var(--color-teal)" opacity="0.75" stroke="none"/>'.
        '<rect x="3" y="3" width="14" height="11" rx="2"/>'.
        '<path d="M7 3v4h6V3"/>'.
        '<path d="M5 17h10M8 14v3M12 14v3"/>',
];

// Fallback label per sidebar without mini-item
$sidebarFallbacks = [
    'campaign_sending_servers' => ['label' => 'Servidores de envío'],
    'campaigns' => ['label' => 'Campañas'],
    'helpdesk' => ['label' => 'Helpdesk'],
];

// Preferred display order for main nav
$navOrder = [
    'dashboard', 'users', 'campaigns', 'campaign_sending_servers', 'mailers',
    'reviews', 'attentions', 'helpdesk', 'forms-inbox', 'ecommerce',
    'blog', 'pages', 'media', 'notifications', 'ads', 'faqs', 'locations',
];

$settingsSidebar = $allSidebars['settings'] ?? null;
$mainSidebars = collect($allSidebars)
    ->except('settings')
    ->sortBy(fn ($v, $k) => (($p = array_search($k, $navOrder)) !== false) ? $p : 999)
    ->all();

$currentUser = auth()->user();

$renderIcon = function (string $paths) use ($svgDefault): string {
    return '<span class="mc-icon"><svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">'.($paths ?: $svgDefault).'</svg></span>';
};
?>

<aside class="mc-sidebar">

    
    <a href="<?php echo e($dashboardUrl); ?>" class="mc-sidebar-logo">
        <div class="mc-sidebar-favicon">
            <img src="<?php echo e(themeAsset('images/logo.svg')); ?>" alt="<?php echo e($siteName); ?>" style="width:28px;height:28px;object-fit:contain;">
        </div>
        <div class="mc-sidebar-logo-content">
            <img src="<?php echo e(themeAsset('images/logo.svg')); ?>" alt="<?php echo e($siteName); ?>" class="mc-logo-light" style="height:26px;width:auto;object-fit:contain;">
            <img src="<?php echo e(themeAsset('images/logo.svg')); ?>" alt="<?php echo e($siteName); ?>" class="mc-logo-dark"  style="height:26px;width:auto;object-fit:contain;filter:brightness(1.4);">
        </div>
    </a>

    
    <nav class="mc-sidebar-nav">

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (empty($mainSidebars) && ! $settingsSidebar) { ?>
            <div class="mc-nav-group">
                <div class="mc-nav-item" style="justify-content:center;font-size:12px;color:var(--color-text-muted);">
                    Sin menús disponibles
                </div>
            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $mainSidebars;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $sidebarId => $sidebar) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
            <?php
            $miniItem = NavService::getMiniItem($sidebarId);
    $iconPaths = $svgMainIcons[$sidebarId] ?? $svgDefault;
    $label = $miniItem['tooltip'] ?? ($sidebarFallbacks[$sidebarId]['label'] ?? ucfirst($sidebarId));

    $sections = $sidebar['sections'] ?? [['title' => '', 'items' => $sidebar['items'] ?? []]];
    $children = collect($sections)
        ->flatMap(fn ($s) => $s['items'] ?? [])
        ->filter(fn ($item) => NavService::userCanAccessItem($item, $currentUser))
        ->values();
    ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($children->isEmpty()) { ?>
                <?php continue; ?>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <?php
        $isAnyChildActive = $children->contains(fn ($c) => ($c['route'] ?? '') && request()->routeIs(($c['route'] ?? '').'*'));
    ?>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($children->count() === 1) { ?>
                <?php
            $singleItem = $children->first();
                $sr = $singleItem['route'] ?? '';
                $sUrl = ($sr && Route::has($sr)) ? route($sr) : '#';
                $sActive = $sr && request()->routeIs($sr.'*');
                ?>
                <a href="<?php echo e($sUrl); ?>" class="mc-nav-item<?php echo e($sActive ? ' active' : ''); ?>">
                    <span class="mc-nav-item-icon"><?php echo $renderIcon($iconPaths); ?></span>
                    <span class="mc-nav-item-label"><?php echo e($label); ?></span>
                </a>
            <?php } else { ?>
                <a href="javascript:void(0)"
                   class="mc-nav-item<?php echo e($isAnyChildActive ? ' active expanded' : ''); ?>"
                   data-nav-toggle="1">
                    <span class="mc-nav-item-icon"><?php echo $renderIcon($iconPaths); ?></span>
                    <span class="mc-nav-item-label"><?php echo e($label); ?></span>
                    <svg class="mc-nav-arrow" viewBox="0 0 16 16" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                         width="16" height="16" aria-hidden="true"><path d="M6 4l4 4-4 4"/></svg>
                </a>
                <div class="mc-nav-submenu">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
                $__env->addLoop($__currentLoopData);
                foreach ($__currentLoopData as $child) {
                    $__env->incrementLoopIndices();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                        <?php
                            $cr = $child['route'] ?? '';
                    $cUrl = ($cr && Route::has($cr)) ? route($cr) : '#';
                    $cAct = $cr && request()->routeIs($cr.'*');
                    ?>
                        <a href="<?php echo e($cUrl); ?>" class="mc-nav-item<?php echo e($cAct ? ' active' : ''); ?>">
                            <span class="mc-nav-item-icon"></span>
                            <span class="mc-nav-item-label"><?php echo e($child['label']); ?></span>
                        </a>
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                </div>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>

        
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($settingsSidebar) { ?>
            <div style="height:1px;background:var(--color-border);margin:8px 16px;"></div>
            <div class="mc-nav-group-label">Configuración</div>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $settingsSidebar['sections'] ?? [];
            $__env->addLoop($__currentLoopData);
            foreach ($__currentLoopData as $section) {
                $__env->incrementLoopIndices();
                $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <?php
                        $sectionTitle = $section['title'] ?? '';
                $sectionItems = collect($section['items'] ?? [])
                    ->filter(fn ($item) => NavService::userCanAccessItem($item, $currentUser))
                    ->values();
                $sectionIconPaths = $svgSettingsIcons[$sectionTitle] ?? $svgDefault;
                $isSectionActive = $sectionItems->contains(fn ($c) => ($c['route'] ?? '') && request()->routeIs(($c['route'] ?? '').'*'));
                ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($sectionItems->isEmpty()) { ?>
                    <?php continue; ?>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($sectionItems->count() === 1) { ?>
                    <?php
                        $onlyItem = $sectionItems->first();
                    $or = $onlyItem['route'] ?? '';
                    $oUrl = ($or && Route::has($or)) ? route($or) : '#';
                    $oAct = $or && request()->routeIs($or.'*');
                    ?>
                    <a href="<?php echo e($oUrl); ?>" class="mc-nav-item<?php echo e($oAct ? ' active' : ''); ?>">
                        <span class="mc-nav-item-icon"><?php echo $renderIcon($sectionIconPaths); ?></span>
                        <span class="mc-nav-item-label"><?php echo e($sectionTitle ?: $onlyItem['label']); ?></span>
                    </a>
                <?php } else { ?>
                    <a href="javascript:void(0)"
                       class="mc-nav-item<?php echo e($isSectionActive ? ' active expanded' : ''); ?>"
                       data-nav-toggle="1">
                        <span class="mc-nav-item-icon"><?php echo $renderIcon($sectionIconPaths); ?></span>
                        <span class="mc-nav-item-label"><?php echo e($sectionTitle); ?></span>
                        <svg class="mc-nav-arrow" viewBox="0 0 16 16" fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                             width="16" height="16" aria-hidden="true"><path d="M6 4l4 4-4 4"/></svg>
                    </a>
                    <div class="mc-nav-submenu">
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $sectionItems;
                    $__env->addLoop($__currentLoopData);
                    foreach ($__currentLoopData as $child) {
                        $__env->incrementLoopIndices();
                        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                            <?php
                                $cr = $child['route'] ?? '';
                        $cUrl = ($cr && Route::has($cr)) ? route($cr) : '#';
                        $cAct = $cr && request()->routeIs($cr.'*');
                        ?>
                            <a href="<?php echo e($cUrl); ?>" class="mc-nav-item<?php echo e($cAct ? ' active' : ''); ?>">
                                <span class="mc-nav-item-icon"></span>
                                <span class="mc-nav-item-label"><?php echo e($child['label']); ?></span>
                            </a>
                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
                    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
                    </div>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
            $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    </nav>

    
    <div class="mc-sidebar-footer">
        <div class="mc-sidebar-user mc-dropdown" data-dropdown="">

            <div class="mc-avatar mc-avatar-sm mc-avatar-6"><?php echo e($initials); ?></div>

            <div class="mc-sidebar-user-info">
                <div class="mc-sidebar-user-name"><?php echo e($userName); ?></div>
                <div class="mc-sidebar-user-email"><?php echo e($userEmail); ?></div>
            </div>

            <div class="mc-dropdown-menu mc-dropdown-menu-up mc-user-menu">

                <div class="mc-user-menu-header">
                    <div class="mc-avatar mc-avatar-md mc-avatar-6"><?php echo e($initials); ?></div>
                    <div class="mc-user-menu-info">
                        <div class="mc-user-menu-name"><?php echo e($userName); ?></div>
                        <div class="mc-user-menu-email"><?php echo e($userEmail); ?></div>
                    </div>
                </div>

                <div class="mc-dropdown-divider"></div>

                <a href="<?php echo e($profileUrl); ?>" class="mc-dropdown-item">
                    <i class="fas fa-user-gear fa-fw"></i>
                    Configuración
                </a>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($lockUrl && config('auth.auth-policy.lock_screen.enabled', true)) { ?>
                    <form method="POST" action="<?php echo e($lockUrl); ?>" style="margin:0">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="mc-dropdown-item">
                            <i class="fas fa-lock fa-fw"></i>
                            Bloquear sesión
                        </button>
                    </form>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <div class="mc-dropdown-divider"></div>
                <div class="mc-dropdown-item" style="cursor:default;pointer-events:none;font-size:11px;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.6px;">
                    Tema
                </div>
                <div data-mc-theme-menu="" style="display:flex;gap:4px;padding:6px 12px 10px;">
                    <button data-mc-theme-mode="light" class="mc-btn mc-btn-sm mc-btn-secondary" style="flex:1;font-size:12px;padding:5px 8px;">
                        <i class="fas fa-sun"></i> Claro
                    </button>
                    <button data-mc-theme-mode="dark" class="mc-btn mc-btn-sm mc-btn-secondary" style="flex:1;font-size:12px;padding:5px 8px;">
                        <i class="fas fa-moon"></i> Oscuro
                    </button>
                    <button data-mc-theme-mode="system" class="mc-btn mc-btn-sm mc-btn-secondary" style="flex:1;font-size:12px;padding:5px 8px;">
                        <i class="fas fa-desktop"></i> Sistema
                    </button>
                </div>

                <div class="mc-dropdown-divider"></div>

                <form method="POST" action="<?php echo e($logoutUrl); ?>" style="margin:0">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="mc-dropdown-item danger">
                        <i class="fas fa-right-from-bracket fa-fw"></i>
                        Salir
                    </button>
                </form>

            </div>
        </div>
    </div>

</aside>
<?php /**PATH /Users/developerts/Herd/system/modules/Theme/resources/views/theme/includes/nav.blade.php ENDPATH**/ ?>