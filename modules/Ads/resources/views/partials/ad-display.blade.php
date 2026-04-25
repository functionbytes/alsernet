@php
    $ads = \Modules\Ads\Models\Ads::query()
        ->published()
        ->byLocation($location ?? 'default')
        ->orderBy('order')
        ->get();
@endphp

@foreach($ads as $ad)
    @if ($ad->ads_type->value === 'google_adsense' && $ad->google_adsense_slot_id)
        <div class="ad-container mb-3">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="{{ config('ads.google_adsense_client_id', '') }}"
                 data-ad-slot="{{ $ad->google_adsense_slot_id }}"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        </div>
        @continue
    @endif

    @continue(! $ad->image)

    <div class="ad-container mb-3">
        @if ($ad->url)
            <a href="{{ route('ads.click', $ad->key) }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener" @endif title="{{ $ad->name }}">
        @endif
            <picture>
                @if($ad->tablet_image)
                    <source srcset="{{ $ad->tabletImageUrl() }}" media="(min-width: 768px) and (max-width: 1199px)">
                @endif
                @if($ad->mobile_image)
                    <source srcset="{{ $ad->mobileImageUrl() }}" media="(max-width: 767px)">
                @endif
                <img src="{{ $ad->imageUrl() }}" alt="{{ $ad->name }}" class="img-fluid rounded" style="max-width:100%;">
            </picture>
        @if($ad->url)
            </a>
        @endif
    </div>
@endforeach
