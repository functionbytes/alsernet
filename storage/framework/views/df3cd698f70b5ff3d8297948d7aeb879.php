<?php
use Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Modules\Blog\Models\BlogPost;

$validLayouts = ['grid', 'list', 'sm', 'mask', 'framed', 'carousel'];
$validOrderBy = ['published_at', 'views', 'title', 'created_at'];
$validOrderDir = ['asc', 'desc'];

$layout = in_array($attrs['layout'] ?? 'grid', $validLayouts) ? ($attrs['layout'] ?? 'grid') : 'grid';
$cols = min(4, max(1, (int) ($attrs['cols'] ?? 3)));
$colsTablet = min(4, max(1, (int) ($attrs['cols-tablet'] ?? 2)));
$colsMobile = min(2, max(1, (int) ($attrs['cols-mobile'] ?? 1)));
$source = in_array($attrs['source'] ?? 'latest', ['latest', 'category', 'featured', 'manual'])
    ? ($attrs['source'] ?? 'latest') : 'latest';
$category = $attrs['category'] ?? null;
$ids = $attrs['ids'] ?? null;
$limit = min(24, max(1, (int) ($attrs['limit'] ?? 6)));
$orderBy = in_array($attrs['order-by'] ?? 'published_at', $validOrderBy)
    ? ($attrs['order-by'] ?? 'published_at') : 'published_at';
$orderDir = in_array($attrs['order-dir'] ?? 'desc', $validOrderDir)
    ? ($attrs['order-dir'] ?? 'desc') : 'desc';

$showDate = filter_var($attrs['show-date'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showMeta = filter_var($attrs['show-meta'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showDescription = filter_var($attrs['show-description'] ?? true, FILTER_VALIDATE_BOOLEAN);
$showButton = filter_var($attrs['show-button'] ?? true, FILTER_VALIDATE_BOOLEAN);
$buttonText = $attrs['button-text'] ?? __('shortcode::shortcode.blog_posts.read_more');
$carousel = $layout === 'carousel' || filter_var($attrs['carousel'] ?? false, FILTER_VALIDATE_BOOLEAN);
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$autoplaySpeed = (int) ($attrs['autoplay-speed'] ?? 5000);
$extraClass = $attrs['class'] ?? null;

$colDesktop = (int) (12 / max($cols, 1));
$colTablet = (int) (12 / max($colsTablet, 1));
$colMobile = (int) (12 / max($colsMobile, 1));

$postClass = collect([
    'post',
    $layout === 'list' ? 'post-list overlay-dark' : null,
    $layout === 'sm' ? 'post-sm overlay-zoom' : null,
    $layout === 'mask' ? 'post-mask gradient' : null,
    $layout === 'framed' ? 'post-framed' : null,
    $layout === 'grid' ? 'text-center overlay-zoom overlay-dark' : null,
])->filter()->implode(' ');

// Query Blog posts (eager load to avoid N+1)
$posts = BlogPost::query()
    ->published()
    ->with(['user:id,name', 'categories:id,slug,name'])
    ->withCount('comments')
    ->when(
        $source === 'category' && ! empty($category),
        fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $category))
    )
    ->when($source === 'featured', fn ($q) => $q->featured())
    ->when(
        $source === 'manual' && ! empty($ids),
        fn ($q) => $q->whereIn('id', array_map('intval', explode(',', $ids)))
    )
    ->orderBy($orderBy, $orderDir)
    ->limit($limit)
    ->get();
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($posts->isNotEmpty()) { ?>
    <div class="blog-posts blog-posts-<?php echo e($layout); ?><?php echo e($extraClass ? ' '.$extraClass : ''); ?>">
        <div class="<?php echo e($carousel ? 'owl-carousel owl-theme' : 'row'); ?>"
             <?php if ($carousel) { ?>
                 data-owl-options="<?php echo e(json_encode([
                     'items' => $cols,
                     'loop' => $posts->count() > $cols,
                     'autoplay' => $autoplay,
                     'autoplayTimeout' => $autoplaySpeed,
                     'nav' => true,
                     'dots' => false,
                     'responsive' => [
                         '0' => ['items' => $colsMobile],
                         '768' => ['items' => $colsTablet],
                         '992' => ['items' => $cols],
                     ],
                 ])); ?>"
             <?php } ?>>

            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php SupportCompiledWireKeys::openLoop(); ?><?php } ?><?php $__currentLoopData = $posts;
    $__env->addLoop($__currentLoopData);
    foreach ($__currentLoopData as $post) {
        $__env->incrementLoopIndices();
        $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::startLoopIteration(); ?><?php } ?>
                <div class="<?php echo e($carousel ? '' : 'col-'.$colMobile.' col-md-'.$colTablet.' col-lg-'.$colDesktop); ?>">
                    <div class="<?php echo e($postClass); ?>">
                        <figure class="post-media">
                            <a href="<?php echo e($post->url); ?>">
                                <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($post->image) { ?>
                                    <img src="<?php echo e($post->image); ?>" width="380" height="200"
                                         alt="<?php echo e(e($post->title)); ?>" loading="lazy">
                                <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                            </a>
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showDate && $post->published_at && $layout !== 'mask') { ?>
                                <div class="post-calendar">
                                    <span class="post-day"><?php echo e($post->published_at->format('d')); ?></span>
                                    <span class="post-month"><?php echo e(strtoupper($post->published_at->format('M'))); ?></span>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </figure>

                        <div class="post-details">
                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showMeta) { ?>
                                <div class="post-meta">
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($post->published_at) { ?>
                                        <a class="post-date"><?php echo e($post->published_at->isoFormat('D MMM YYYY')); ?></a>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($post->comments_count > 0) { ?>
                                        | <a class="post-comment">
                                            <span><?php echo e($post->comments_count); ?></span>
                                            <?php echo e(__('shortcode::shortcode.blog_posts.comments')); ?>

                                        </a>
                                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                                </div>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                            <h4 class="post-title">
                                <a href="<?php echo e($post->url); ?>"><?php echo e($post->title); ?></a>
                            </h4>

                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showDescription && $post->excerpt) { ?>
                                <p class="post-content"><?php echo e($post->excerpt); ?></p>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

                            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($showButton) { ?>
                                <a href="<?php echo e($post->url); ?>"
                                   class="btn btn-link btn-underline btn-<?php echo e($layout === 'mask' ? 'white' : 'dark'); ?>">
                                    <?php echo e($buttonText); ?><i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                                </a>
                            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                        </div>
                    </div>
                </div>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><?php SupportCompiledWireKeys::endLoop(); ?><?php } ?><?php } $__env->popLoop();
    $loop = $__env->getLastLoop(); ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php SupportCompiledWireKeys::closeLoop(); ?><?php } ?>
        </div>
    </div>

    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($carousel) { ?>
        <?php if (! $__env->hasRenderedOnce('d5d018af-ec02-41ec-8ab9-de178d6f0b24')) {
            $__env->markAsRenderedOnce('d5d018af-ec02-41ec-8ab9-de178d6f0b24'); ?>
        <?php $__env->startPush('scripts'); ?>
        <script>
        $(document).ready(function () {
            $('.blog-posts .owl-carousel').each(function () {
                var opts = {};
                try { opts = JSON.parse($(this).attr('data-owl-options') || '{}'); } catch (e) {}
                $(this).owlCarousel(opts);
            });
        });
        </script>
        <?php $__env->stopPush(); ?>
        <?php } ?>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/blog-posts.blade.php ENDPATH**/ ?>