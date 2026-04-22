<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Forms\Models\Form;

Theme::set('page', $page); ?>

<?php $__env->startSection('seo_head'); ?>
    <?php echo $__env->make(Theme::getThemeNamespace().'::partials.seo-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>



    <?php
        $contactFormModule = Form::query()
            ->where('slug', 'contacto')
            ->active()
            ->with(['fields' => fn ($q) => $q->visible()->ordered()])
            ->first();

$processedContent = $transContent ?? '';
if ($contactFormModule && $processedContent) {
    $formHtml = view('forms::public.render', [
        'form' => $contactFormModule,
        'shortcodeConfig' => [],
    ])->render();
    $processedContent = preg_replace(
        '/<form\b[^>]*id=["\']formContacts["\'][^>]*>.*?<\/form>/si',
        $formHtml,
        $processedContent
    );
}
?>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($processedContent) { ?>
    <div class="ck-content">
        <?php echo $processedContent; ?>

    </div>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template::layouts.default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/views/templates/contact.blade.php ENDPATH**/ ?>