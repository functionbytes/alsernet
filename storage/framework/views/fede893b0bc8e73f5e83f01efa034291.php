<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validStyles = ['default', 'active', 'inline', 'square', 'rounded'];
$validSizes = ['sm', 'md', 'lg'];
$validAligns = ['left', 'center', 'right'];
$validColors = ['default', 'brand', 'grey', 'white'];
$validTargets = ['_blank', '_self'];

$style = in_array($attrs['style'] ?? 'default', $validStyles) ? ($attrs['style'] ?? 'default') : 'default';
$size = in_array($attrs['size'] ?? 'md', $validSizes) ? ($attrs['size'] ?? 'md') : 'md';
$align = in_array($attrs['align'] ?? 'left', $validAligns) ? ($attrs['align'] ?? 'left') : 'left';
$color = in_array($attrs['color'] ?? 'brand', $validColors) ? ($attrs['color'] ?? 'brand') : 'brand';
$target = in_array($attrs['target'] ?? '_blank', $validTargets) ? ($attrs['target'] ?? '_blank') : '_blank';
$extraClass = $attrs['class'] ?? null;

$faMap = [
    'facebook' => 'fab fa-facebook-f',
    'twitter' => 'fab fa-twitter',
    'x' => 'fab fa-x-twitter',
    'instagram' => 'fab fa-instagram',
    'linkedin' => 'fab fa-linkedin-in',
    'youtube' => 'fab fa-youtube',
    'pinterest' => 'fab fa-pinterest',
    'tiktok' => 'fab fa-tiktok',
    'whatsapp' => 'fab fa-whatsapp',
    'telegram' => 'fab fa-telegram',
    'github' => 'fab fa-github',
    'discord' => 'fab fa-discord',
    'email' => 'fas fa-envelope',
];

$raw = $attrs['networks'] ?? '{}';
$networksArray = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;

$classes = collect([
    'social-links',
    $style === 'default' ? 'social-default' : null,
    $style === 'active' ? 'social-link-active' : null,
    $style === 'inline' ? 'inline-links justify-content-'.$align : null,
    $style === 'square' ? 'square-link' : null,
    $style === 'rounded' ? 'rounded-link' : null,
    'social-size-'.$size,
    'social-color-'.$color,
    ($align !== 'left' && $style !== 'inline') ? 'text-'.$align : null,
    $extraClass,
])->filter()->implode(' ');

$rel = $target === '_blank' ? 'noopener noreferrer' : null;
?>

<div class="<?php echo e($classes); ?>">
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $networksArray;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $network => $url) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (isset($faMap[$network]) && ! empty($url)) { ?>
            <a href="<?php echo e(htmlspecialchars($url, ENT_QUOTES)); ?>"
               class="social-link social-<?php echo e($network); ?>"
               target="<?php echo e($target); ?>"
               <?php if ($rel) { ?> rel="<?php echo e($rel); ?>" <?php } ?>
               aria-label="<?php echo e(ucfirst($network)); ?>">
                <i class="<?php echo e($faMap[$network]); ?>" aria-hidden="true"></i>
            </a>
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
</div>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/social-links.blade.php ENDPATH**/ ?>