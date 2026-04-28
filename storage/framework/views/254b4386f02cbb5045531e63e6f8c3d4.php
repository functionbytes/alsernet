<?php

use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

    $validModes = ['popup', 'inline', 'background'];
$validTextColors = ['light', 'dark'];

$src = $attrs['src'] ?? '';
$mode = in_array($attrs['mode'] ?? 'inline', $validModes) ? ($attrs['mode'] ?? 'inline') : 'inline';
$cover = $attrs['cover'] ?? null;
$coverAlt = $attrs['cover-alt'] ?? '';
$title = $attrs['title'] ?? null;
$subtitle = $attrs['subtitle'] ?? null;
$autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
$loop = filter_var($attrs['loop'] ?? false, FILTER_VALIDATE_BOOLEAN);
$muted = filter_var($attrs['muted'] ?? ($autoplay), FILTER_VALIDATE_BOOLEAN);
$controls = filter_var($attrs['controls'] ?? true, FILTER_VALIDATE_BOOLEAN);
$textColor = in_array($attrs['text-color'] ?? 'light', $validTextColors) ? ($attrs['text-color'] ?? 'light') : 'light';
$width = (int) ($attrs['width'] ?? 1843);
$height = (int) ($attrs['height'] ?? 606);
$extraClass = $attrs['class'] ?? null;

$isYouTube = str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be');
$isVimeo = str_contains($src, 'vimeo.com');
$isExternal = $isYouTube || $isVimeo;

// YouTube/Vimeo en mode=background → degradar a popup
if ($isExternal && $mode === 'background') {
    $mode = 'popup';
}

$videoId = 'video-'.substr(md5(uniqid()), 0, 8);
?>

<?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($mode === 'popup') { ?>

    <div id="<?php echo e($videoId); ?>"
         class="banner popup-video <?php echo e($extraClass); ?>"
         <?php if ($cover) { ?> data-bg-image="<?php echo e(htmlspecialchars($cover, ENT_QUOTES)); ?>" <?php } ?>>
        <div class="banner-content text-center <?php echo e($textColor === 'light' ? 'text-white' : ''); ?>">
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($subtitle) { ?>
                <h4 class="banner-subtitle"><?php echo e($subtitle); ?></h4>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($title) { ?>
                <h3 class="banner-title"><?php echo e($title); ?></h3>
            <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
            <a class="btn-dark btn-iframe btn-play"
               href="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>"
               data-toggle="modal"
               data-target="#<?php echo e($videoId); ?>-modal"
               aria-label="<?php echo e(__('Reproducir vídeo')); ?>">
                <i class="fas fa-play" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    
    <div class="modal fade video-modal"
         id="<?php echo e($videoId); ?>-modal"
         tabindex="-1"
         aria-labelledby="<?php echo e($videoId); ?>-modal-label"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0 pb-0">
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="<?php echo e(__('Cerrar')); ?>"></button>
                </div>
                <div class="modal-body p-0">
                    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isYouTube || $isVimeo) { ?>
                        <div class="video-iframe-wrapper">
                            <iframe src=""
                                    data-src="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>"
                                    frameborder="0"
                                    allow="autoplay; encrypted-media; fullscreen"
                                    allowfullscreen
                                    loading="lazy"
                                    id="<?php echo e($videoId); ?>-iframe"></iframe>
                        </div>
                    <?php } else { ?>
                        <video id="<?php echo e($videoId); ?>-player"
                               width="100%"
                               controls
                               preload="none"
                               <?php if ($cover) { ?> poster="<?php echo e(htmlspecialchars($cover, ENT_QUOTES)); ?>" <?php } ?>>
                            <source src="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>" type="video/mp4">
                            <?php echo e(__('Tu navegador no soporta vídeo HTML5.')); ?>

                        </video>
                    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
    (function () {
        'use strict';

        var $modal   = $('#<?php echo e($videoId); ?>-modal');
        var $trigger = $('#<?php echo e($videoId); ?> .btn-play');

        // Aplicar imagen de fondo via data-bg-image
        var $banner = $('#<?php echo e($videoId); ?>[data-bg-image]');
        if ($banner.length) {
            $banner.css('background-image', "url('" + $banner.data('bg-image') + "')");
        }

        // Abrir modal con Bootstrap 5
        $trigger.on('click', function (e) {
            e.preventDefault();
            var $iframe = $('#<?php echo e($videoId); ?>-iframe');

            if ($iframe.length) {
                $iframe.attr('src', $iframe.data('src'));
            }

            $modal.modal('show');
        });

        // Pausar vídeo/iframe al cerrar
        $modal.on('hidden.bs.modal', function () {
            var $iframe = $(this).find('iframe');
            var $video  = $(this).find('video')[0];

            if ($iframe.length) {
                $iframe.attr('src', '');
            }

            if ($video) {
                $video.pause();
            }
        });
    }());
    </script>
    <?php $__env->stopPush(); ?>

<?php } elseif ($mode === 'background') { ?>

    
    <div class="video-background-wrapper <?php echo e($extraClass); ?>">
        <video class="video-background"
               <?php if ($autoplay) { ?> autoplay <?php } ?>
               <?php if ($loop) { ?> loop <?php } ?>
               muted
               playsinline
               preload="<?php echo e($autoplay ? 'auto' : 'none'); ?>"
               width="<?php echo e($width); ?>"
               height="<?php echo e($height); ?>">
            <source src="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>" type="video/mp4">
        </video>
    </div>

<?php } else { ?>

    
    <?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if ($isYouTube || $isVimeo) { ?>
        <div class="video-iframe-wrapper <?php echo e($extraClass); ?>">
            <iframe src="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>"
                    frameborder="0"
                    allow="autoplay; encrypted-media; fullscreen"
                    allowfullscreen
                    loading="lazy"
                    title="<?php echo e($title ?? ''); ?>"></iframe>
        </div>

        <?php if (! $__env->hasRenderedOnce('41323be0-e387-47fe-b6ea-1d4d260edefd')) {
            $__env->markAsRenderedOnce('41323be0-e387-47fe-b6ea-1d4d260edefd'); ?>
        <?php $__env->startPush('styles'); ?>
        <style>
        .video-iframe-wrapper {
            position: relative;
            padding-top: 56.25%;
        }
        .video-iframe-wrapper iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        </style>
        <?php $__env->stopPush(); ?>
        <?php } ?>

    <?php } else { ?>
        <video class="video-inline <?php echo e($extraClass); ?>"
               <?php if ($autoplay) { ?> autoplay <?php } ?>
               <?php if ($loop) { ?> loop <?php } ?>
               <?php if ($muted) { ?> muted <?php } ?>
               <?php if ($controls) { ?> controls <?php } ?>
               playsinline
               preload="<?php echo e($autoplay ? 'auto' : 'none'); ?>"
               <?php if ($cover) { ?> poster="<?php echo e(htmlspecialchars($cover, ENT_QUOTES)); ?>" <?php } ?>
               width="<?php echo e($width); ?>"
               height="<?php echo e($height); ?>">
            <source src="<?php echo e(htmlspecialchars($src, ENT_QUOTES)); ?>" type="video/mp4">
            <?php echo e(__('Tu navegador no soporta vídeo HTML5.')); ?>

        </video>
    <?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>

<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Template/Templates/Riode/Resources/views/shortcodes/video.blade.php ENDPATH**/ ?>