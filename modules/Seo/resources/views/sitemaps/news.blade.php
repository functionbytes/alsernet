<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
@foreach($posts as $post)
    <url>
        <loc>{{ e(url('/blog/' . ($post->slug ?? $post->id))) }}</loc>
        <news:news>
            <news:publication>
                <news:name>{{ e($siteName) }}</news:name>
                <news:language>{{ e($language) }}</news:language>
            </news:publication>
            <news:publication_date>{{ $post->published_at?->toAtomString() }}</news:publication_date>
            <news:title>{{ e($post->title ?? '') }}</news:title>
        </news:news>
    </url>
@endforeach
</urlset>
