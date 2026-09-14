<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($articles as $article)
    <url>
        <loc>{{ route('public.helpcenter.show', $article->slug) }}</loc>
        <lastmod>{{ optional($article->updated_at)->toAtomString() ?? optional($article->created_at)->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        @if($article->relationLoaded('translations'))
            @foreach($article->translations as $tr)
                @if($tr->is_published)
                    <xhtml:link rel="alternate" hreflang="{{ $tr->locale }}" href="{{ route('public.helpcenter.show', $tr->slug) }}?locale={{ $tr->locale }}"/>
                @endif
            @endforeach
        @endif
    </url>
@endforeach
</urlset>
