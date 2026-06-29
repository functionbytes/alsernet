@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sitemaps as $sitemap)
    <sitemap>
        <loc>{{ \Modules\Sitemap\Helpers\SitemapHelper::escapeXml($sitemap['loc']) }}</loc>
        @if($sitemap['lastmod'])
        <lastmod>{{ $sitemap['lastmod'] }}</lastmod>
        @endif
    </sitemap>
@endforeach
</sitemapindex>
