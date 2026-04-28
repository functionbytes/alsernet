<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validTextAligns = ['left', 'center', 'right'];
$validTextColors = ['dark', 'light'];
$validVerticalPositions = ['top', 'middle', 'bottom'];
$validButtonStyles = ['primary', 'dark', 'outline-white', 'outline-dark'];

$bgColor = ! empty($attrs['bg-color']) ? $attrs['bg-color'] : null;
$image = $attrs['image'] ?? null;
$imageAlt = $attrs['image-alt'] ?? ($attrs['title'] ?? '');
$title = $attrs['title'] ?? null;
$subtitle = $attrs['subtitle'] ?? null;
$description = $attrs['description'] ?? null;
$buttonText = $attrs['button-text'] ?? null;
$buttonHref = $attrs['button-href'] ?? null;
$buttonStyle = in_array($attrs['button-style'] ?? 'dark', $validButtonStyles) ? ($attrs['button-style'] ?? 'dark') : 'dark';
$textAlign = in_array($attrs['text-align'] ?? 'left', $validTextAligns) ? ($attrs['text-align'] ?? 'left') : 'left';
$textColor = in_array($attrs['text-color'] ?? 'dark', $validTextColors) ? ($attrs['text-color'] ?? 'dark') : 'dark';
$verticalPosition = in_array($attrs['vertical-position'] ?? 'middle', $validVerticalPositions) ? ($attrs['vertical-position'] ?? 'middle') : 'middle';

$animationsRaw = $attrs['animations'] ?? null;
$animations = [];
if ($animationsRaw) {
    $decoded = json_decode($animationsRaw, true);
    $animations = is_array($decoded) ? $decoded : [];
}

$contentClasses = collect([
    'banner-content',
    $verticalPosition === 'middle' ? 'y-50' : null,
    $verticalPosition === 'bottom' ? 'y-bottom' : null,
    $textAlign === 'center' ? 'text-center' : null,
    $textAlign === 'right' ? 'text-right' : null,
    $textColor === 'light' ? 'text-white' : null,
])->filter()->implode(' ');

$btnClasses = collect([
    'btn',
    'slide-animate',
    'appear-animate',
    $buttonStyle === 'primary' ? 'btn-primary' : null,
    $buttonStyle === 'dark' ? 'btn-dark' : null,
    $buttonStyle === 'outline-white' ? 'btn-outline btn-white' : null,
    $buttonStyle === 'outline-dark' ? 'btn-outline btn-dark' : null,
])->filter()->implode(' ');

$extraClass = ! empty($attrs['class']) ? htmlspecialchars($attrs['class']) : '';
?>

<div class="banner banner-fixed slide-item <?php echo e($extraClass); ?>"
     <?php if ($bgColor) { ?> data-bg-color="<?php echo e(htmlspecialchars($bgColor)); ?>" <?php } ?>>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($image)) { ?>
        <figure>
            <img src="<?php echo e(htmlspecialchars($image)); ?>"
                 alt="<?php echo e(htmlspecialchars($imageAlt)); ?>"
                 width="1180"
                 height="444"
                 loading="eager">
        </figure>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

    <div class="container">
        <div class="<?php echo e($contentClasses); ?>">
            <div class="slide-animate">

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($subtitle)) { ?>
                    <h4 class="banner-subtitle text-uppercase slide-animate appear-animate mb-1"
                        <?php if (! empty($animations['subtitle'])) { ?>
                            data-animation-options='{"name":"<?php echo e($animations['subtitle']); ?>","duration":"1s","delay":".3s"}'
                        <?php } ?>>
                        <?php echo e($subtitle); ?>

                    </h4>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($title)) { ?>
                    <h3 class="banner-title font-weight-bold slide-animate appear-animate"
                        <?php if (! empty($animations['title'])) { ?>
                            data-animation-options='{"name":"<?php echo e($animations['title']); ?>","duration":"1s","delay":".5s"}'
                        <?php } ?>>
                        <?php echo nl2br(e($title)); ?>

                    </h3>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($description)) { ?>
                    <p class="slide-animate appear-animate mb-4"
                       <?php if (! empty($animations['description'])) { ?>
                           data-animation-options='{"name":"<?php echo e($animations['description']); ?>","duration":"1s","delay":".7s"}'
                       <?php } ?>>
                        <?php echo e($description); ?>

                    </p>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($buttonText) && ! empty($buttonHref)) { ?>
                    <a href="<?php echo e(htmlspecialchars($buttonHref)); ?>"
                       class="<?php echo e($btnClasses); ?>"
                       <?php if (! empty($animations['button'])) { ?>
                           data-animation-options='{"name":"<?php echo e($animations['button']); ?>","duration":"1s","delay":".9s"}'
                       <?php } ?>>
                        <?php echo e($buttonText); ?><i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                    </a>
                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

            </div>
        </div>
    </div>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/slide.blade.php ENDPATH**/ ?>