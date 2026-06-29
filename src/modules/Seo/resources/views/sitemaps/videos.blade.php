<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach($metas as $meta)
    @if($meta->canonical_url)
    <url>
        <loc>{{ e($meta->canonical_url) }}</loc>
        <video:video>
            @if($meta->og_image)
            <video:thumbnail_loc>{{ e($meta->og_image) }}</video:thumbnail_loc>
            @endif
            <video:title>{{ e($meta->title ?? '') }}</video:title>
            <video:description>{{ e($meta->description ?? '') }}</video:description>
            <video:content_loc>{{ e($meta->canonical_url) }}</video:content_loc>
        </video:video>
    </url>
    @endif
@endforeach
</urlset>
