{{--
    Web Vitals beacon — invoca la librería oficial de Google y envía cada
    métrica (LCP/INP/CLS/FCP/TTFB) al endpoint público del módulo.

    Uso en un layout del front:
        @seoWebVitalsBeacon

    Se carga con `defer` y usa `sendBeacon` cuando está disponible para no
    bloquear la descarga ni afectar las propias métricas.
--}}
@if(config('Seo.web_vitals.enabled', true))
<script defer>
(function () {
    'use strict';

    // Sample rate: skip most sessions to keep DB volume manageable.
    const SAMPLE_RATE = {{ (float) config('Seo.web_vitals.sample_rate', 0.1) }};
    if (Math.random() > SAMPLE_RATE) return;

    const ENDPOINT = @json(route('seo.web-vitals.store'));
    const CDN_URL = @json(config('Seo.web_vitals.cdn', 'https://unpkg.com/web-vitals@4/dist/web-vitals.iife.js'));
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
@endif
