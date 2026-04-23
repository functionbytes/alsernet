<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $template = caixilhariablanco_get_current_template() ?? '';
$isHome = $template === 'homepage';
$isService = request()->is('servicios/*');
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isHome) { ?>
    
    <link rel="preload" as="image" href="<?php echo e(asset('themes/caixilhariablanco/images/hero-bg.webp')); ?>" fetchpriority="high">
    
    <link rel="preload" as="image" href="<?php echo e(asset('media/slider/header-img4.webp?v=2')); ?>" fetchpriority="high">
<?php } elseif ($isService) { ?>
    
    <link rel="preload" as="image" href="<?php echo e(asset('pages/images/bg/header-bg1.webp')); ?>" fetchpriority="high">
<?php } else { ?>
    
    <link rel="preload" as="image" href="<?php echo e(asset('themes/caixilhariablanco/images/page-header-bg.webp')); ?>" fetchpriority="high">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>


<style>
  /* Improve contrast for proceso-number (WCAG 3:1 for large text) */
  .proceso-number {
    color: rgba(177, 1, 0, 0.55) !important;
  }

  /* Font display swap to prevent invisible text during load */
  @font-face {
    font-display: swap !important;
  }
</style>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/critical-css.blade.php ENDPATH**/ ?>