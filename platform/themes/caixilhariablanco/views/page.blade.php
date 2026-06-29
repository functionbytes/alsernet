@extends('template::layouts.default')

@php
    Theme::set('page', $page);
    $template = $page->template ?? 'default';
@endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')


    @if($template === 'default')
        <section class="mt-60 mb-60">
            <div class="ck-content">
                @if($transContent)
                    {!! $transContent !!}
                @else
                    <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
                @endif
            </div>
            @if(config('page.social_share.enabled', true) && view()->exists('template::partials.social-share'))
                @include('template::partials.social-share')
            @endif
        </section>
    @else
        <div class="ck-content">
            @if($transContent)
                {!! $transContent !!}
            @else
                <p class="text-muted text-center">{{ trans('page::messages.no_content') }}</p>
            @endif
        </div>
        @if(config('page.social_share.enabled', true) && view()->exists('template::partials.social-share'))
            @include('template::partials.social-share')
        @endif
    @endif
@endsection

{{-- TOC automática para páginas con múltiples headings --}}
@push('scripts')
<script>
// Time-on-page tracking
(function () {
    var pageId = {{ $page->id ?? 'null' }};
    var ipHash = '{{ hash("sha256", request()->ip()) }}';
    var trackUrl = '{{ url("/api/v1/pages/track-time") }}';

    if (!pageId) { return; }

    var startTime = Date.now();

    function sendTime(isUnload) {
        var seconds = Math.round((Date.now() - startTime) / 1000);
        if (seconds < 1) { return; }

        var data = JSON.stringify({ page_id: pageId, time_seconds: seconds, ip_hash: ipHash });
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        if (isUnload && navigator.sendBeacon) {
            navigator.sendBeacon(trackUrl, new Blob([data], { type: 'application/json' }));
            return;
        }

        fetch(trackUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: data,
            keepalive: true,
        }).catch(function () {});
    }

    window.addEventListener('beforeunload', function () { sendTime(true); });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendTime(false); }
    });
}());
</script>
@endpush
