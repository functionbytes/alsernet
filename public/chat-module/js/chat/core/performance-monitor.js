(function ($) {
    'use strict';

    var metrics = {
        messageRender: [],
        scrollOperations: [],
        domInsertions: [],
        conversationLoads: []
    };

    var enabled = false;

    /**
     * Inicia el monitoreo de performance
     */
    function enable() {
        enabled = true;
        console.log('[PerformanceMonitor] Enabled - tracking all operations');
    }

    /**
     * Detiene el monitoreo
     */
    function disable() {
        enabled = false;
        console.log('[PerformanceMonitor] Disabled');
    }

    /**
     * Registra el tiempo de una operación
     */
    function track(category, duration, metadata) {
        if (!enabled) { return; }

        if (!metrics[category]) {
            metrics[category] = [];
        }

        metrics[category].push({
            duration: duration,
            timestamp: Date.now(),
            metadata: metadata || {}
        });

        // Mantener solo últimas 100 mediciones por categoría
        if (metrics[category].length > 100) {
            metrics[category].shift();
        }
    }

    /**
     * Genera un reporte de performance
     */
    function report() {
        var summary = {};

        Object.keys(metrics).forEach(function (category) {
            var data = metrics[category];
            if (data.length === 0) {
                summary[category] = { count: 0 };
                return;
            }

            var durations = data.map(function (m) { return m.duration; });
            var sum = durations.reduce(function (a, b) { return a + b; }, 0);
            var avg = sum / durations.length;
            var sorted = durations.slice().sort(function (a, b) { return a - b; });
            var p50 = sorted[Math.floor(sorted.length * 0.5)];
            var p95 = sorted[Math.floor(sorted.length * 0.95)];
            var max = Math.max.apply(null, durations);

            summary[category] = {
                count: data.length,
                avg: avg.toFixed(2) + 'ms',
                p50: p50.toFixed(2) + 'ms',
                p95: p95.toFixed(2) + 'ms',
                max: max.toFixed(2) + 'ms',
                total: sum.toFixed(2) + 'ms'
            };
        });

        console.log('[PerformanceMonitor] Report:');
        console.table(summary);
        return summary;
    }

    /**
     * Limpia todas las métricas
     */
    function reset() {
        Object.keys(metrics).forEach(function (key) {
            metrics[key] = [];
        });
        console.log('[PerformanceMonitor] Metrics reset');
    }

    /**
     * Wrapper para medir funciones
     */
    function measure(category, fn, metadata) {
        if (!enabled) {
            return fn();
        }

        var start = performance.now();
        var result = fn();
        var duration = performance.now() - start;

        track(category, duration, metadata);

        if (duration > 50) {
            console.warn('[PerformanceMonitor] Slow operation detected:', {
                category: category,
                duration: duration.toFixed(2) + 'ms',
                metadata: metadata
            });
        }

        return result;
    }

    /**
     * Wrapper async para medir promesas
     */
    function measureAsync(category, fn, metadata) {
        if (!enabled) {
            return fn();
        }

        var start = performance.now();
        return Promise.resolve(fn()).then(function (result) {
            var duration = performance.now() - start;
            track(category, duration, metadata);

            if (duration > 100) {
                console.warn('[PerformanceMonitor] Slow async operation:', {
                    category: category,
                    duration: duration.toFixed(2) + 'ms',
                    metadata: metadata
                });
            }

            return result;
        });
    }

    /**
     * Obtiene métricas raw para análisis personalizado
     */
    function getMetrics() {
        return JSON.parse(JSON.stringify(metrics));
    }

    // Exponer API pública
    window.ChatPerformance = {
        enable: enable,
        disable: disable,
        track: track,
        report: report,
        reset: reset,
        measure: measure,
        measureAsync: measureAsync,
        getMetrics: getMetrics
    };

    // Auto-enable en desarrollo (detecta localhost/127.0.0.1)
    if (window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1' ||
        window.location.hostname.indexOf('.test') !== -1) {
        console.log('[PerformanceMonitor] Auto-enabled for development environment');
        console.log('[PerformanceMonitor] Use ChatPerformance.report() to view metrics');
        console.log('[PerformanceMonitor] Use ChatPerformance.disable() to turn off tracking');
        enable();
    }

})(jQuery);
