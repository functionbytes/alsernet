<?php
$validEffects = ['fade', 'zoom', 'rotate', 'slide', 'none'];
$validNavStyles = ['bg', 'arrow', 'bg-arrow', 'simple'];
$validDotsStyles = ['default', 'inner', 'white'];
$validHeights = ['auto', 'sm', 'md', 'lg', 'full'];

$effect = in_array($attrs['effect'] ?? 'fade', $validEffects) ? ($attrs['effect'] ?? 'fade') : 'fade';
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$autoplayTimeout = max(1000, (int) ($attrs['autoplay-timeout'] ?? 5000));
$loop = filter_var($attrs['loop'] ?? false, FILTER_VALIDATE_BOOLEAN);
$nav = filter_var($attrs['nav'] ?? true, FILTER_VALIDATE_BOOLEAN);
$dots = filter_var($attrs['dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
$navStyle = in_array($attrs['nav-style'] ?? 'bg', $validNavStyles) ? ($attrs['nav-style'] ?? 'bg') : 'bg';
$dotsStyle = in_array($attrs['dots-style'] ?? 'default', $validDotsStyles) ? ($attrs['dots-style'] ?? 'default') : 'default';
$height = in_array($attrs['height'] ?? 'md', $validHeights) ? ($attrs['height'] ?? 'md') : 'md';

[$animIn, $animOut] = match ($effect) {
    'fade' => ['fadeIn', 'fadeOut'],
    'zoom' => ['zoomIn', 'zoomOut'],
    'rotate' => ['rotateInUpLeft', 'rotateOutUpLeft'],
    'slide' => ['slideInRight', 'slideOutLeft'],
    default => [null, null],
};

$owlOptions = [
    'items' => 1,
    'autoplay' => $autoplay,
    'autoplayTimeout' => $autoplayTimeout,
    'loop' => $loop,
    'nav' => $nav,
    'dots' => $dots,
];

if ($animIn) {
    $owlOptions['animateIn'] = $animIn;
    $owlOptions['animateOut'] = $animOut;
}

$classes = collect([
    'big-slider',
    'owl-carousel',
    'owl-theme',
    'row',
    'cols-1',
    'gutter-no',
    $effect !== 'none' ? 'animation-slider' : null,
    $nav && str_contains($navStyle, 'bg') ? 'owl-nav-bg' : null,
    $nav && str_contains($navStyle, 'arrow') ? 'owl-nav-arrow' : null,
    $dots && $dotsStyle === 'inner' ? 'owl-dot-inner' : null,
    $dots && $dotsStyle === 'white' ? 'owl-dot-white' : null,
    $height !== 'auto' ? 'slider-height-'.$height : null,
    ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
])->filter()->implode(' ');

$sliderId = 'slider-'.substr(md5(uniqid()), 0, 8);
$owlJson = json_encode($owlOptions, JSON_UNESCAPED_SLASHES);
?>

<?php if (! $__env->hasRenderedOnce('adfcf17f-3d4c-41dd-8a38-8ebeb0f72475')) {
    $__env->markAsRenderedOnce('adfcf17f-3d4c-41dd-8a38-8ebeb0f72475'); ?>
    <?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('modules/Theme/theme/libs/owl.carousel/dist/owl.carousel.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('modules/Theme/theme/libs/owl.carousel/dist/assets/owl.theme.default.min.css')); ?>">
    <?php $__env->stopPush(); ?>
<?php } ?>

<div id="<?php echo e($sliderId); ?>" class="<?php echo e($classes); ?>" data-owl-options='<?php echo $owlJson; ?>'>
    <?php echo $content; ?>

</div>

<?php if (! $__env->hasRenderedOnce('508963aa-07c9-4fb6-bb86-e589480fdcb0')) {
    $__env->markAsRenderedOnce('508963aa-07c9-4fb6-bb86-e589480fdcb0'); ?>
    <?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('modules/Theme/theme/libs/owl.carousel/dist/owl.carousel.min.js')); ?>"></script>
    <script>
    $(function () {
        // Inicializar todos los sliders shortcode que aún no estén iniciados
        $('.big-slider.owl-carousel').each(function () {
            if ($(this).hasClass('owl-loaded')) return;
            var opts = $(this).data('owl-options') || {};
            $(this).owlCarousel(opts);
        });

        // Aplicar color de fondo de slides via data-bg-color
        $('.slide-item[data-bg-color]').each(function () {
            $(this).css('background-color', $(this).data('bg-color'));
        });

        // Disparar animaciones de los elementos al cambiar de slide
        $('.big-slider').on('changed.owl.carousel', function (event) {
            var $items = $(event.target).find('.owl-item');
            var $current = $items.eq(event.item.index);
            $current.find('[data-animation-options]').each(function () {
                var opts = $(this).data('animation-options');
                if (opts && opts.name) {
                    var $el = $(this);
                    setTimeout(function () {
                        $el.addClass('animated ' + opts.name);
                    }, opts.delay ? parseFloat(opts.delay) * 1000 : 0);
                }
            });
        });
    });
    </script>
    <?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/slider.blade.php ENDPATH**/ ?>