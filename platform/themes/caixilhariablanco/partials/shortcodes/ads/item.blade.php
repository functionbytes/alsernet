<div class="banner-img wow fadeIn animated {{ $class ?? '' }}">
    {!! AdsManager::displayAds($ads->key, ['class' => 'border-radius-10']) !!}
    <div class="banner-text">
        <span>{{ $ads->name }}</span>
        <h4>{!! BaseHelper::clean(nl2br($ads->getMetaData('subtitle', true) ?: '')) !!}</h4>
        <a href="{{ route('public.ads-click', $ads->key) }}">
            {{ $ads->getMetaData('button_text', true) ?: __('Shop Now') }} <i class="fa fa-arrow-right"></i>
        </a>
    </div>
</div>
