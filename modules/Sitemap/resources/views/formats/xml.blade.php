@php
$hasAlternates = ! empty($alternates ?? []) && collect($items)->some(fn ($item) => ! empty($alternates[$item['loc']] ?? []));
echo '<?xml version="1.0" encoding="UTF-8"?>';
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"@if($hasAlternates) xmlns:xhtml="http://www.w3.org/1999/xhtml"@endif>
@foreach($items as $item)
    <url>
        <loc>{{ \Modules\Sitemap\Helpers\SitemapHelper::escapeXml($item['loc']) }}</loc>
        @if($item['lastmod'])
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        @endif
        <changefreq>{{ $item['changefreq'] }}</changefreq>
        <priority>{{ $item['priority'] }}</priority>
        @foreach($alternates[$item['loc']] ?? [] as $alt)
        <xhtml:link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ \Modules\Sitemap\Helpers\SitemapHelper::escapeXml($alt['href']) }}"/>
        @endforeach
    </url>
@endforeach
</urlset>
