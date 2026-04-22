
<?php
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if BLOCK]><![endif]--><?php } ?><?php if (seo_setting('web_vitals.enabled', config('Seo.web_vitals.enabled', true))) { ?>
<script defer>
(function () {
    'use strict';

    // Sample rate: skip most sessions to keep DB volume manageable.
    const SAMPLE_RATE = <?php echo e((float) seo_setting('web_vitals.sample_rate', config('Seo.web_vitals.sample_rate', 0.1))); ?>;
    if (Math.random() > SAMPLE_RATE) return;

    const ENDPOINT = <?php echo json_encode(route('seo.web-vitals.store'), 15, 512) ?>;
    const CDN_URL = <?php echo json_encode(config('Seo.web_vitals.cdn', 'https://unpkg.com/web-vitals@4/dist/web-vitals.iife.js'), 512) ?>;
    const send = function (metric) {
        try {
            const body = JSON.stringify({
                url: window.location.href,
                metric: metric.name,
                value: metric.value,
                rating: metric.rating,
                device: /Mobile|Android|iPhone|iPad|iPod/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
                connection: (navigator.connection && navigator.connection.effectiveType) || null,
                navigation_type: metric.navigationType || null,
            });

            if (navigator.sendBeacon) {
                navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }));
            } else {
                fetch(ENDPOINT, {
                    method: 'POST',
                    body: body,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    keepalive: true,
                }).catch(function () {});
            }
        } catch (e) { /* swallow — never block page on beacon failure */ }
    };

    const script = document.createElement('script');
    script.src = CDN_URL;
    script.defer = true;
    script.onload = function () {
        if (! window.webVitals) return;
        window.webVitals.onLCP(send);
        window.webVitals.onINP(send);
        window.webVitals.onCLS(send);
        window.webVitals.onFCP(send);
        window.webVitals.onTTFB(send);
    };
    document.head.appendChild(script);
})();
</script>
<?php } ?><?php if (ExtendBlade::isRenderingLivewireComponent()) { ?><!--[if ENDBLOCK]><![endif]--><?php } ?>
<?php /**PATH /Users/developerts/Herd/system/modules/Seo/app/Providers/../../resources/views/partials/web-vitals-beacon.blade.php ENDPATH**/ ?>