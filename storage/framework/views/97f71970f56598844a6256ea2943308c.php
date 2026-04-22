<?php

use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Page\Models\Page;

$seoPage = $page ?? null;
$seoMeta = ($seoPage instanceof Page) ? $seoPage->seoMeta : null;
$seoLocale = $detectedLocale ?? app()->getLocale();
$seoLocaleMap = ['es' => 'es_ES', 'en' => 'en_US', 'fr' => 'fr_FR', 'pt' => 'pt_PT'];
$seoOgLocale = $seoLocaleMap[$seoLocale] ?? (strtolower($seoLocale).'_'.strtoupper($seoLocale));

$seoTitle = $seoMeta?->title ?: ($seoPage?->seo_title ?: ($transTitle ?? ($seoPage?->title ?? config('app.name'))));
$seoDesc = $seoMeta?->description ?: ($seoPage?->seo_description ?: ($transDescription ?? ($seoPage?->description ?? '')));
$seoKeywords = $seoMeta?->keywords ?: ($seoPage?->seo_keywords ?: ($transKeywords ?? null));
$seoRobots = $seoMeta?->robots ?: (($seoPage?->seo_noindex ?? false) ? 'noindex,nofollow' : 'index,follow');
$seoCanonical = $seoMeta?->canonical_url ?: ($canonicalUrl ?? url()->current());

$seoOgTitle = $seoMeta?->og_title ?: $seoTitle;
$seoOgDesc = $seoMeta?->og_description ?: $seoDesc;
$seoOgType = $seoMeta?->og_type ?: 'website';
$seoOgImage = $seoMeta?->og_image ?: (($featuredImage ?? null) ?: config('Seo.default_og_image', 'https://caixilhariablanco.pt/media/seo/og-default.png'));

$seoTwCard = $seoMeta?->twitter_card ?: ($seoOgImage ? 'summary_large_image' : 'summary');
$seoTwTitle = $seoMeta?->twitter_title ?: $seoOgTitle;
$seoTwDesc = $seoMeta?->twitter_description ?: $seoOgDesc;
$seoTwImage = $seoMeta?->twitter_image ?: $seoOgImage;
?>

<title><?php echo e($seoTitle); ?></title>
<meta name="description" content="<?php echo e($seoDesc); ?>">
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($seoKeywords)) { ?>
    <meta name="keywords" content="<?php echo e($seoKeywords); ?>">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<meta name="robots" content="<?php echo e($seoRobots); ?>">
<link rel="canonical" href="<?php echo e($seoCanonical); ?>">

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($langLinks)) { ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $langLinks;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $lang => $info) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($info['url']) && $info['published']) { ?>
            <link rel="alternate" hreflang="<?php echo e($lang); ?>" href="<?php echo e($info['url']); ?>">
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($xDefaultUrl)) { ?>
        <link rel="alternate" hreflang="x-default" href="<?php echo e($xDefaultUrl); ?>">
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<meta property="og:type" content="<?php echo e($seoOgType); ?>">
<meta property="og:title" content="<?php echo e($seoOgTitle); ?>">
<meta property="og:description" content="<?php echo e($seoOgDesc); ?>">
<meta property="og:url" content="<?php echo e($seoCanonical); ?>">
<meta property="og:site_name" content="<?php echo e(theme_option('site_title') ?: config('app.name')); ?>">
<meta property="og:locale" content="<?php echo e($seoOgLocale); ?>">
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($seoOgImage) { ?>
    <meta property="og:image" content="<?php echo e($seoOgImage); ?>">
    <meta property="og:image:alt" content="<?php echo e($seoOgTitle); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($langLinks)) { ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $langLinks;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $lang => $info) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
        <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($lang !== $seoLocale && ! empty($info['url']) && $info['published']) { ?>
            <meta property="og:locale:alternate" content="<?php echo e($seoLocaleMap[$lang] ?? (strtolower($lang).'_'.strtoupper($lang))); ?>">
        <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<meta name="twitter:card" content="<?php echo e($seoTwCard); ?>">
<meta name="twitter:title" content="<?php echo e($seoTwTitle); ?>">
<meta name="twitter:description" content="<?php echo e($seoTwDesc); ?>">
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($seoTwImage) { ?>
    <meta name="twitter:image" content="<?php echo e($seoTwImage); ?>">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (theme_option('twitter_handle')) { ?>
    <meta name="twitter:site" content="<?php echo e('@'.ltrim(theme_option('twitter_handle'), '@')); ?>">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (! empty($featuredImage)) { ?>
    <link rel="preload" as="image" href="<?php echo e($featuredImage); ?>">
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/platform/themes/caixilhariablanco/partials/seo-head.blade.php ENDPATH**/ ?>