<?php
$validStyles = ['simple', 'solid', 'outline', 'outline2', 'inverse', 'boxed-simple'];
$validOrientations = ['horizontal', 'vertical'];
$validAligns = ['left', 'center', 'right'];
$validShapes = ['square', 'round'];

$style = in_array($attrs['style'] ?? 'simple', $validStyles) ? ($attrs['style'] ?? 'simple') : 'simple';
$orientation = in_array($attrs['orientation'] ?? 'horizontal', $validOrientations) ? ($attrs['orientation'] ?? 'horizontal') : 'horizontal';
$align = in_array($attrs['align'] ?? 'left', $validAligns) ? ($attrs['align'] ?? 'left') : 'left';
$shape = in_array($attrs['shape'] ?? 'square', $validShapes) ? ($attrs['shape'] ?? 'square') : 'square';

$classes = collect([
    'tab',
    in_array($style, ['simple', 'boxed-simple']) ? 'tab-nav-simple' : null,
    $style === 'solid' ? 'tab-nav-boxed tab-nav-solid' : null,
    $style === 'outline' ? 'tab-boxed tab-nav-boxed tab-outline' : null,
    $style === 'outline2' ? 'tab-boxed tab-nav-boxed tab-outline2' : null,
    $style === 'inverse' ? 'tab-boxed tab-nav-boxed tab-simple tab-inverse' : null,
    $style === 'boxed-simple' ? 'tab-nav-boxed' : null,
    $orientation === 'vertical' ? 'tab-vertical' : null,
    $align === 'center' ? 'tab-nav-center' : null,
    $align === 'right' ? 'tab-nav-right' : null,
    $shape === 'round' ? 'tab-nav-round' : null,
    ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
])->filter()->implode(' ');

// Extraer nav-items y tab-panes de los marcadores emitidos por [tab]
$navItems = '';
$tabPanes = '';
$firstActive = false;

preg_match_all('/SC_TAB_NAV_START(.*?)SC_TAB_NAV_END/s', $content, $navMatches);
preg_match_all('/SC_TAB_PANE_START(.*?)SC_TAB_PANE_END/s', $content, $paneMatches);

$navMetas = $navMatches[1] ?? [];
$panes = $paneMatches[1] ?? [];

foreach ($navMetas as $i => $metaJson) {
    $meta = json_decode($metaJson, true);
    if (! $meta) {
        continue;
    }

    // Si ninguna pestaña tiene active=true, activar la primera
    $isActive = $meta['isActive'];
    if (! $firstActive && $isActive) {
        $firstActive = true;
    }
    if (! $firstActive && $i === 0) {
        $isActive = true;
        $firstActive = true;
    }

    $id = htmlspecialchars($meta['id'] ?? 'tab-'.$i);
    $title = htmlspecialchars($meta['title'] ?? '');
    $icon = $meta['icon'] ?? null;

    $activeClass = $isActive ? 'active' : '';
    $ariaSelected = $isActive ? 'true' : 'false';

    $iconHtml = $icon ? '<i class="'.htmlspecialchars($icon).' me-1" aria-hidden="true"></i>' : '';

    $navItems .= '<li class="nav-item" role="presentation">'
        .'<a class="nav-link '.$activeClass.'" '
        .'id="'.$id.'-tab" '
        .'href="#'.$id.'" '
        .'data-bs-toggle="tab" '
        .'role="tab" '
        .'aria-controls="'.$id.'" '
        .'aria-selected="'.$ariaSelected.'">'
        .$iconHtml.$title
        .'</a></li>';

    // Ajustar active en el pane si es la primera pestaña sin active explicito
    $paneHtml = $panes[$i] ?? '';
    if (! $meta['isActive'] && $isActive) {
        $paneHtml = preg_replace('/class="tab-pane\s+/', 'class="tab-pane active show ', $paneHtml, 1);
    }

    $tabPanes .= $paneHtml;
}
?>

<div class="<?php echo e($classes); ?>">
    <ul class="nav nav-tabs" role="tablist">
        <?php echo $navItems; ?>

    </ul>
    <div class="tab-content">
        <?php echo $tabPanes; ?>

    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/tabs.blade.php ENDPATH**/ ?>