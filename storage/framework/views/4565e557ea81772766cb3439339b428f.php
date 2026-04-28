<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validLayouts = ['default', 'inversed', 'centered', 'centered-border', 'centered-bg'];
$validBackgrounds = ['light', 'grey', 'dark', 'parallax'];
$validNavs = ['top', 'bottom', 'none'];

$layout = in_array($attrs['layout'] ?? 'default', $validLayouts)
    ? ($attrs['layout'] ?? 'default') : 'default';
$cols = min(4, max(1, (int) ($attrs['cols'] ?? 3)));
$carousel = filter_var($attrs['carousel'] ?? true, FILTER_VALIDATE_BOOLEAN);
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$nav = in_array($attrs['nav'] ?? 'top', $validNavs) ? ($attrs['nav'] ?? 'top') : 'top';
$dots = filter_var($attrs['dots'] ?? false, FILTER_VALIDATE_BOOLEAN);
$background = in_array($attrs['background'] ?? 'light', $validBackgrounds)
    ? ($attrs['background'] ?? 'light') : 'light';
$backgroundImage = $attrs['background-image'] ?? null;
$title = $attrs['title'] ?? null;
$extraClass = $attrs['class'] ?? null;

// Fall back from centered-bg parallax if no image provided
if ($layout === 'centered-bg' && empty($backgroundImage)) {
    $background = 'light';
}

// Build the card CSS class from layout
$cardClass = collect([
    'testimonial',
    $layout === 'inversed' ? 'testimonial-inversed' : null,
    $layout === 'centered' ? 'testimonial-centered' : null,
    $layout === 'centered-border' ? 'testimonial-centered testimonial-border' : null,
    $layout === 'centered-bg' ? 'testimonial-centered testimonial-bg' : null,
])->filter()->implode(' ');

$isCenteredLayout = in_array($layout, ['centered', 'centered-border', 'centered-bg']);

// Parse child [testimonial] items
$children = array_filter(
    array_map('trim', preg_split('/(?=<div class="testimonial-child)/', $content) ?: []),
    fn ($c) => $c !== ''
);
$childCount = count($children);

$owlClasses = collect([
    'owl-carousel',
    'owl-theme',
    $nav === 'bottom' ? 'owl-nav-bottom' : null,
])->filter()->implode(' ');

$owlOptions = json_encode([
    'autoplay' => $autoplay,
    'autoplayTimeout' => 6000,
    'loop' => $childCount > $cols,
    'nav' => $nav !== 'none',
    'dots' => $dots,
    'responsive' => [
        '0' => ['items' => 1],
        '768' => ['items' => $cols >= 2 ? 2 : 1],
        '992' => ['items' => $cols],
    ],
]);

// Section wrapper attributes
$sectionClass = collect([
    'testimonials-section',
    $background === 'grey' ? 'bg-light' : null,
    $background === 'dark' ? 'bg-dark text-white' : null,
    $background === 'parallax' ? 'parallax' : null,
    $extraClass,
])->filter()->implode(' ');
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($children)) { ?>
    <section class="<?php echo e($sectionClass); ?>"
             <?php if ($background === 'parallax' && $backgroundImage) { ?>
                 data-image-src="<?php echo e(htmlspecialchars($backgroundImage)); ?>"
             <?php } ?>>
        <div class="container">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title) { ?>
                <h3 class="testimonial-title text-center"><?php echo e($title); ?></h3>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            <div class="<?php echo e($carousel ? $owlClasses : 'row cols-'.$cols); ?>"
                 <?php if ($carousel) { ?> data-owl-options="<?php echo e($owlOptions); ?>" <?php } ?>>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $children;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $item) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <div class="<?php echo e($cardClass); ?>">
                        <?php
                    // Extract parts from child output
                    preg_match('/<blockquote>(.*?)<\/blockquote>/s', $item, $quoteMatch);
        preg_match('/<cite>(.*?)<\/cite>/s', $item, $citeMatch);
        preg_match('/<figure class="testimonial-author-thumbnail">(.*?)<\/figure>/s', $item, $thumbMatch);
        preg_match('/<div class="testimonial-rating"[^>]*>(.*?)<\/div>/s', $item, $ratingMatch);
        $quote = $quoteMatch[0] ?? '';
        $cite = $citeMatch[0] ?? '';
        $thumb = $thumbMatch[0] ?? '';
        $ratingHtml = $ratingMatch[0] ?? '';
        ?>

                        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isCenteredLayout) { ?>
                            <div class="testimonial-info">
                                <?php echo $thumb; ?>

                                <?php echo $quote; ?>

                                <?php echo $cite; ?>

                                <?php echo $ratingHtml; ?>

                            </div>
                        <?php } else { ?>
                            <?php echo $quote; ?>

                            <div class="testimonial-info">
                                <?php echo $thumb; ?>

                                <?php echo $cite; ?>

                                <?php echo $ratingHtml; ?>

                            </div>
                        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                    </div>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </div>
        </div>
    </section>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($carousel) { ?>
        <?php if (! $__env->hasRenderedOnce('d2bf9840-73a5-4edb-8a8a-920ef26c0903')) {
            $__env->markAsRenderedOnce('d2bf9840-73a5-4edb-8a8a-920ef26c0903'); ?>
        <?php $__env->startPush('scripts'); ?>
        <script>
        $(document).ready(function () {
            $('.testimonials-section .owl-carousel').each(function () {
                var opts = {};
                try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
                $(this).owlCarousel(opts);
            });

            // Apply parallax background image via JS (avoids inline style)
            $('.testimonials-section.parallax[data-image-src]').each(function () {
                var src = $(this).data('image-src');
                if (src) {
                    $(this).css('background-image', 'url(' + src + ')');
                }
            });
        });
        </script>
        <?php $__env->stopPush(); ?>
        <?php } ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/testimonials.blade.php ENDPATH**/ ?>