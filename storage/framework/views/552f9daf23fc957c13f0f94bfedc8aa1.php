<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    /**
     * EXCEPCIÓN INLINE STYLE JUSTIFICADA:
     * El posicionamiento top/left es 100% dinámico y no puede expresarse
     * en CSS estático sin generar una regla por pin. Es la única excepción
     * aceptada a la regla "no inline styles" de este proyecto.
     */
$validStyles = ['plus', 'circle-filled', 'circle-outline', 'plus-filled'];
$validAnimations = ['spread', 'flash', 'none'];
$validTooltipPositions = ['top', 'bottom', 'left', 'right'];
$validButtonStyles = ['primary', 'dark', 'outline'];

$top = $attrs['top'] ?? '0%';
$left = $attrs['left'] ?? '0%';
$title = $attrs['title'] ?? '';
$pinStyle = in_array($attrs['style'] ?? 'plus', $validStyles) ? ($attrs['style'] ?? 'plus') : 'plus';
$animation = in_array($attrs['animation'] ?? 'spread', $validAnimations) ? ($attrs['animation'] ?? 'spread') : 'spread';
$tooltipPosition = in_array($attrs['tooltip-position'] ?? 'top', $validTooltipPositions) ? ($attrs['tooltip-position'] ?? 'top') : 'top';
$titleHref = $attrs['title-href'] ?? null;
$image = $attrs['image'] ?? null;
$price = $attrs['price'] ?? null;
$priceOld = $attrs['price-old'] ?? null;
$buttonText = $attrs['button-text'] ?? __('shortcode::shortcode.hotspot.view_product');
$buttonHref = $attrs['button-href'] ?? null;
$buttonStyle = in_array($attrs['button-style'] ?? 'primary', $validButtonStyles) ? ($attrs['button-style'] ?? 'primary') : 'primary';

$styleMap = [
    'plus' => 'hotspot-type1',
    'circle-filled' => 'hotspot-type2',
    'circle-outline' => 'hotspot-type3',
    'plus-filled' => 'hotspot-type4',
];

$classes = collect([
    'hotspot',
    $styleMap[$pinStyle],
    $animation === 'spread' ? 'hotspotspread' : null,
    $animation === 'flash' ? 'hotspot-style-flash' : null,
    'hotspot-'.$tooltipPosition,
    'text-center',
    ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
])->filter()->implode(' ');

$btnClasses = collect([
    'btn',
    'btn-radius',
    'btn-cart',
    $buttonStyle === 'primary' ? 'btn-primary' : null,
    $buttonStyle === 'dark' ? 'btn-dark' : null,
    $buttonStyle === 'outline' ? 'btn-outline btn-primary' : null,
])->filter()->implode(' ');

// Sanitizar top/left: solo valores de porcentaje válidos
$top = preg_match('/^\d+(\.\d+)?%$/', $top) ? $top : '0%';
$left = preg_match('/^\d+(\.\d+)?%$/', $left) ? $left : '0%';
?>


<div class="<?php echo e($classes); ?>" style="top: <?php echo e($top); ?>; left: <?php echo e($left); ?>;">

    <a href="<?php echo e(htmlspecialchars($titleHref ?? '#')); ?>"
       aria-label="<?php echo e(htmlspecialchars($title)); ?>"
       tabindex="0"></a>

    <div class="tooltip" role="tooltip">

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($image)) { ?>
            <figure class="tooltip-media">
                <img src="<?php echo e(htmlspecialchars($image)); ?>"
                     alt="<?php echo e(htmlspecialchars($title)); ?>"
                     width="300"
                     height="338"
                     loading="lazy">
            </figure>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <h2 class="tooltip-name">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($titleHref)) { ?>
                <a href="<?php echo e(htmlspecialchars($titleHref)); ?>"><?php echo e($title); ?></a>
            <?php } else { ?>
                <?php echo e($title); ?>

            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
        </h2>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($price)) { ?>
            <h4 class="tooltip-price">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($priceOld)) { ?>
                    <span class="old-price"><?php echo e($priceOld); ?></span>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                <span class="price"><?php echo e($price); ?></span>
            </h4>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($buttonHref)) { ?>
            <a href="<?php echo e(htmlspecialchars($buttonHref)); ?>" class="<?php echo e($btnClasses); ?>">
                <?php echo e($buttonText); ?>

            </a>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($content)) { ?>
            <div class="tooltip-content">
                <?php echo $content; ?>

            </div>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/hotspot-pin.blade.php ENDPATH**/ ?>