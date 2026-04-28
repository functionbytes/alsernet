<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $image = $attrs['image'] ?? null;
$alt = $attrs['alt'] ?? 'hotspot';
$width = max(1, (int) ($attrs['width'] ?? 1180));
$height = max(1, (int) ($attrs['height'] ?? 600));
$animation = $attrs['animation'] ?? null;

$classes = collect([
    'banner',
    'banner-hotspot',
    ! empty($animation) ? 'appear-animate' : null,
    ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : null,
])->filter()->implode(' ');

$hotspotId = 'hotspot-'.substr(md5(uniqid()), 0, 8);
?>

<div id="<?php echo e($hotspotId); ?>"
     class="<?php echo e($classes); ?>"
     <?php if (! empty($animation)) { ?>
         data-animation-options='{"name":"<?php echo e(htmlspecialchars($animation)); ?>"}'
     <?php } ?>>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($image)) { ?>
        <figure>
            <img src="<?php echo e(htmlspecialchars($image)); ?>"
                 alt="<?php echo e(htmlspecialchars($alt)); ?>"
                 width="<?php echo e($width); ?>"
                 height="<?php echo e($height); ?>"
                 loading="lazy">
        </figure>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div class="hotspot-container">
        <?php echo $content; ?>

    </div>
</div>

<?php if (! $__env->hasRenderedOnce('d11102e1-9b9c-4caf-a6cd-78de7abd4477')) {
    $__env->markAsRenderedOnce('d11102e1-9b9c-4caf-a6cd-78de7abd4477'); ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
    $(function () {
        // Hover: mostrar/ocultar tooltip
        $(document).on('mouseenter', '.banner-hotspot .hotspot', function () {
            $(this).addClass('hover');
        });
        $(document).on('mouseleave', '.banner-hotspot .hotspot', function () {
            $(this).removeClass('hover');
        });

        // Mobile touch: toggle on click, cerrar el resto
        $(document).on('click', '.banner-hotspot .hotspot > a', function (e) {
            e.preventDefault();
            var $hot = $(this).closest('.hotspot');
            $('.hotspot').not($hot).removeClass('hover');
            $hot.toggleClass('hover');
        });
    });
    </script>
    <?php $__env->stopPush(); ?>
<?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/hotspot.blade.php ENDPATH**/ ?>