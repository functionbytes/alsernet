<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($metas as $meta)
    @if($meta->og_image)
    <url>
        <loc>{{ e($meta->canonical_url ?: url('/')) }}</loc>
        <image:image>
            <image:loc>{{ e($meta->og_image) }}</image:loc>
            @if($meta->title)
            <image:title>{{ e($meta->title) }}</image:title>
            @endif
        </image:image>
    </url>
    @endif
@endforeach
</urlset>
