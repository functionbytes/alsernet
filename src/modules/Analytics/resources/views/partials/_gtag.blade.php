@php
    $gaMeasurementId = setting('google_analytics_measurement_id');
    $gaEnabled       = (bool) setting('google_analytics_enable');
@endphp

@if($gaEnabled && $gaMeasurementId)
<!-- Google Analytics GA4 — {{ $gaMeasurementId }} -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $gaMeasurementId }}', {
        anonymize_ip: true,
        cookie_flags: 'SameSite=None;Secure'
    });
</script>
@endif
