<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (! function_exists('rcInitials')) {
    function rcInitials(string $name): string
    {
        $words = array_filter(explode(' ', trim($name)));
        $parts = array_slice(array_values($words), 0, 2);

        return strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', $parts)));
    }
}

$locale = app()->getLocale();
$i18n = [
    'es' => ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'],
    'pt' => ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'],
    'en' => ['fallback_name' => 'Customer', 'verified' => 'Verified customer'],
    'fr' => ['fallback_name' => 'Client', 'verified' => 'Client vérifié'],
][$locale] ?? ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'];
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $reviews;
$__env->addLoop($__currentLoopData);
foreach ($__currentLoopData as $r) {
    $__env->incrementLoopIndices();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
    <?php
            $text = $r->translations->firstWhere('locale_code', strtoupper($locale))?->translated_text
                ?? $r->comment
                ?? '';
    $name = $r->reviewer_name ?? $i18n['fallback_name'];
    $initials = rcInitials($name);
    $rating = $r->star_rating->value();
    ?>
    <div class="col-lg-4 col-md-6">
        <div class="home-testimonial-card">
            <div class="ht-avatar"><?php echo e($initials); ?></div>
            <ul class="ht-stars">
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php for ($i = 1; $i <= $rating; $i++) { ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                    <li><i class="fa-solid fa-star"></i></li>
                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
            </ul>
            <p class="ht-text">&ldquo;<?php echo e($text); ?>&rdquo;</p>
            <span class="ht-name"><?php echo e($name); ?></span>
            <span class="ht-meta">
                <?php echo e($r->review_time?->diffForHumans() ?? $i18n['verified']); ?>

                <i class="fa-brands fa-google ms-1"></i>
            </span>
        </div>
    </div>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
$loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Reviews/resources/views/shortcodes/partials/cards.blade.php ENDPATH**/ ?>