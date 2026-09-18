<?php

use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

if (! function_exists('media_url')) {
    /**
     * Get the public URL for a media file path.
     * Returns CDN URL when CDN is enabled.
     */
    function media_url(string $url, ?string $disk = null): string
    {
        $disk = $disk ?? config('media.default_disk', 'media');

        if (config('media.cdn.enabled', false) && config('media.cdn.url')) {
            return rtrim(config('media.cdn.url'), '/').'/'.ltrim($url, '/');
        }

        return Storage::disk($disk)->url($url);
    }
}

if (! function_exists('is_media_image')) {
    /**
     * Check if a MIME type corresponds to an image.
     */
    function is_media_image(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }
}

if (! function_exists('media_human_size')) {
    /**
     * Format bytes into a human-readable size string.
     */
    function media_human_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;

        foreach ($units as $unit) {
            if ($size < 1024) {
                return round($size, 2).' '.$unit;
            }
            $size /= 1024;
        }

        return round($size, 2).' TB';
    }
}

if (! function_exists('media')) {
    /**
     * Get the public URL for a MediaFile by id.
     * Pass a $size key (e.g. 'thumb') to retrieve a thumbnail from metadata.
     */
    function media(int|string|null $id, ?string $size = null): ?string
    {
        if (! $id) {
            return null;
        }

        $file = MediaFile::query()->find($id);

        if (! $file) {
            return null;
        }

        if ($size && isset($file->metadata['thumbnails'][$size])) {
            $path = $file->metadata['thumbnails'][$size];

            return Storage::disk($file->disk)->url($path);
        }

        return route('media.indirect.url', [
            'hash' => hash('sha256', (string) $file->id.config('app.key')),
            'id' => $file->id,
        ]);
    }
}

if (! function_exists('media_srcset')) {
    /**
     * Generate a srcset string with responsive variants for a MediaFile.
     * Pass $format = 'avif'|'webp' to get a specific format srcset.
     */
    function media_srcset(int|string|null $id, ?string $format = null): ?string
    {
        if (! $id) {
            return null;
        }

        $file = MediaFile::query()->find($id);

        if (! $file || ! str_starts_with($file->mime_type, 'image/')) {
            return null;
        }

        $disk = Storage::disk($file->disk);
        $base = pathinfo($file->url, PATHINFO_FILENAME);
        $dir = pathinfo($file->url, PATHINFO_DIRNAME);
        $dir = $dir === '.' ? '' : rtrim($dir, '/').'/';
        $ext = $format ?: ($file->mime_type === 'image/avif' ? 'avif' : 'webp');
        $parts = [];

        foreach ([480, 768, 1024, 1920] as $w) {
            $variant = $dir.$base.'-'.$w.'w.'.$ext;
            if ($disk->exists($variant)) {
                $parts[] = $disk->url($variant).' '.$w.'w';
            }
        }

        $fallbackUrl = $disk->url($file->url);
        if ($file->mime_type === 'image/avif' && $ext === 'webp' && ! empty($file->metadata['fallback_webp'])) {
            $fallbackUrl = $disk->url($file->metadata['fallback_webp']);
        }

        if (! empty($parts)) {
            $parts[] = $fallbackUrl.' '.($file->metadata['width'] ?? 1920).'w';
        }

        return empty($parts) ? null : implode(', ', $parts);
    }
}

if (! function_exists('media_tag')) {
    /**
     * Generate an optimized <picture> or <img> tag for a MediaFile.
     *
     * For AVIF files emits a <picture> with AVIF source + WebP fallback.
     *
     * Options:
     *   - size: 'thumb'|'medium'|null
     *   - loading: 'lazy'|'eager' (default: lazy)
     *   - fetchpriority: 'high'|'low'|null
     *   - class: CSS classes
     *   - alt: override alt text
     *   - srcset: bool (default: true for images)
     *   - placeholder: bool (default: true)
     *   - sizes: string (default: '100vw')
     */
    function media_tag(int|string|null $id, array $options = []): ?string
    {
        if (! $id) {
            return null;
        }

        $file = MediaFile::query()->find($id);

        if (! $file) {
            return null;
        }

        $url = media($id, $options['size'] ?? null);
        $isImage = str_starts_with($file->mime_type, 'image/');

        $attrs = [
            'src' => $url,
            'alt' => e($options['alt'] ?? $file->alt ?? $file->name),
        ];

        $picture = '';
        $pictureEnd = '';

        if ($isImage) {
            $attrs['loading'] = $options['loading'] ?? 'lazy';
            $attrs['decoding'] = 'async';

            if (! empty($options['fetchpriority'])) {
                $attrs['fetchpriority'] = $options['fetchpriority'];
            }

            $width = $file->metadata['width'] ?? null;
            $height = $file->metadata['height'] ?? null;

            if ($width) {
                $attrs['width'] = (int) $width;
            }
            if ($height) {
                $attrs['height'] = (int) $height;
            }

            if ($options['srcset'] ?? true) {
                $srcset = media_srcset($id);
                if ($srcset) {
                    $attrs['srcset'] = $srcset;
                    $attrs['sizes'] = $options['sizes'] ?? '100vw';
                }
            }

            $placeholder = $file->metadata['placeholder'] ?? null;
            if (($options['placeholder'] ?? true) && $placeholder) {
                $attrs['style'] = 'background-image:url('.$placeholder.');background-size:cover;';
            }

            // AVIF: emit <picture> with AVIF source + WebP fallback
            if ($file->mime_type === 'image/avif') {
                $disk = Storage::disk($file->disk);
                $avifSrcset = media_srcset($id, 'avif');
                $webpSrcset = media_srcset($id, 'webp');
                $webpUrl = ! empty($file->metadata['fallback_webp'])
                    ? $disk->url($file->metadata['fallback_webp'])
                    : $url;

                $avifSource = $avifSrcset
                    ? 'srcset="'.e($avifSrcset).'" sizes="'.e($options['sizes'] ?? '100vw').'"'
                    : 'srcset="'.e($url).'"';

                $webpSource = $webpSrcset
                    ? 'srcset="'.e($webpSrcset).'" sizes="'.e($options['sizes'] ?? '100vw').'"'
                    : 'srcset="'.e($webpUrl).'"';

                $picture = '<picture>'
                    .'<source type="image/avif" '.$avifSource.'>'
                    .'<source type="image/webp" '.$webpSource.'>';
                $pictureEnd = '</picture>';

                // The <img> inside <picture> uses the WebP fallback as src
                $attrs['src'] = $webpUrl;
                unset($attrs['srcset'], $attrs['sizes']);
            }
        }

        if (! empty($options['class'])) {
            $attrs['class'] = $options['class'];
        }

        $html = $picture.'<img';
        foreach ($attrs as $key => $value) {
            $html .= ' '.$key.'="'.e((string) $value).'"';
        }
        $html .= '>'.$pictureEnd;

        return $html;
    }
}

if (! function_exists('media_preconnect')) {
    /**
     * Return a <link rel="preconnect"> tag for the active media disk or CDN URL.
     */
    function media_preconnect(): string
    {
        if (config('media.cdn.enabled', false) && config('media.cdn.url')) {
            $cdnUrl = parse_url(config('media.cdn.url'));
            $scheme = $cdnUrl['scheme'] ?? 'https';
            $host = $cdnUrl['host'] ?? request()->getHost();
        } else {
            $disk = config('media.default_disk', 'media');
            $url = parse_url(Storage::disk($disk)->url(''));
            $scheme = $url['scheme'] ?? 'https';
            $host = $url['host'] ?? request()->getHost();
        }

        return '<link rel="preconnect" href="'.$scheme.'://'.$host.'">';
    }
}

if (! function_exists('media_preload')) {
    /**
     * Generate a <link rel="preload" as="image"> tag for a MediaFile.
     * Use this for LCP (Largest Contentful Paint) images.
     *
     * Options:
     *   - size: 'thumb'|'medium'|null
     *   - fetchpriority: 'high'|'low'|'auto'
     *   - type: override MIME type
     */
    function media_preload(int|string|null $id, array $options = []): ?string
    {
        if (! $id) {
            return null;
        }

        $file = MediaFile::query()->find($id);

        if (! $file || ! str_starts_with($file->mime_type, 'image/')) {
            return null;
        }

        $url = media($id, $options['size'] ?? null);
        $mimeType = $options['type'] ?? $file->mime_type;

        $attrs = 'rel="preload" as="image" href="'.e($url).'" type="'.e($mimeType).'"';

        if ($file->mime_type === 'image/avif' && ! empty($file->metadata['fallback_webp'])) {
            $webpUrl = media_url($file->metadata['fallback_webp'], $file->disk);
            $attrs = 'rel="preload" as="image" href="'.e($webpUrl).'" type="image/webp"';
        }

        $srcset = media_srcset($id);
        if ($srcset) {
            $attrs .= ' imagesrcset="'.e($srcset).'" imagesizes="'.e($options['sizes'] ?? '100vw').'"';
        }

        if (! empty($options['fetchpriority'])) {
            $attrs .= ' fetchpriority="'.e($options['fetchpriority']).'"';
        }

        return '<link '.$attrs.'>';
    }
}

if (! function_exists('media_sw_register')) {
    /**
     * Return a <script> block that registers the media service worker.
     */
    function media_sw_register(): string
    {
        $swUrl = asset('sw-media-cache.js');

        return '<script>'.
            "if('serviceWorker' in navigator){".
            "navigator.serviceWorker.register('{$swUrl}').catch(()=>{});".
            '}'.
            '</script>';
    }
}

if (! function_exists('media_placeholder')) {
    /**
     * Get the base64 LQIP (Low Quality Image Placeholder) for a MediaFile.
     */
    function media_placeholder(int|string|null $id): ?string
    {
        if (! $id) {
            return null;
        }

        $file = MediaFile::query()->find($id);

        return $file?->metadata['placeholder'] ?? null;
    }
}
