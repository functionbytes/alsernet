// Plantilla del cargador de modales bajo demanda de tickets-app.min.js.
// NO se sirve tal cual: scripts/build-tickets-app.mjs sustituye el marcador de
// `var MAP` por el objeto "lazy" de manifest.json y lo añade al final del bundle.
//
// Cada modal 'lazy' sale del bundle inicial y se descarga la primera vez que
// se abre. Para que core.js siga llamando a openXxxModal() como siempre, se
// instala un stub con ese nombre en window; al llamarlo carga el fichero, cuyo
// `function openXxxModal` sustituye al stub (un <script> clásico puede
// redeclarar una función global), y reenvía la llamada. Solo funciona con
// funciones cuyo valor de retorno no se usa: el build lo comprueba en el
// mapa, y modal-14/32/49/50 —que comparten variables o helpers con core—
// nunca son 'lazy'.
//
// Solo se activa si la página declara window.TKT_LAZY = { base, v } (lo hace
// index.blade.php únicamente cuando sirve el bundle). Con los <script> sueltos
// de desarrollo no hay nada que cargar y este bloque no hace nada.
(function () {
    var cfg = window.TKT_LAZY;
    if (!cfg || !cfg.base) return;

    var MAP = __LAZY_MAP__;
    var base = String(cfg.base).replace(/\/+$/, '') + '/';
    var suffix = cfg.v ? '?v=' + encodeURIComponent(cfg.v) : '';
    var chunks = {};

    function chunkUrl(file) {
        return base + file + '.min.js' + suffix;
    }

    function reportFailure(file) {
        if (window.console && console.error) console.error('[tickets-app] no se pudo descargar ' + chunkUrl(file));

        try {
            openModal(modalShell({
                icon: 'fa-solid fa-triangle-exclamation',
                kicker: 'Gestión de tickets',
                title: 'No se pudo abrir este panel',
                width: 'sm',
                body: '<div class="tkt-empty-box">No se pudo descargar el panel. Comprueba la conexión y vuelve a intentarlo.</div>',
                foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
            }));
        } catch (e) {
            // Sin diálogo queda el error de consola; no hay nada más que hacer.
        }
    }

    function load(file, onReady) {
        var chunk = chunks[file];

        if (chunk && chunk.ready) {
            onReady();
            return;
        }

        if (chunk) {
            chunk.waiting.push(onReady);
            return;
        }

        chunk = chunks[file] = { ready: false, waiting: [onReady] };

        var script = document.createElement('script');
        script.src = chunkUrl(file);
        script.async = true;
        script.onload = function () {
            chunk.ready = true;
            var waiting = chunk.waiting;
            chunk.waiting = [];
            waiting.forEach(function (fn) { fn(); });
        };
        script.onerror = function () {
            var waiting = chunk.waiting;
            delete chunks[file];
            if (script.parentNode) script.parentNode.removeChild(script);
            waiting.forEach(function (fn) { fn(false); });
            reportFailure(file);
        };
        document.head.appendChild(script);
    }

    function makeStub(file, name) {
        var pending = false;

        var stub = function () {
            // Mientras baja el fichero no se encolan más llamadas: un segundo
            // clic impaciente abriría el modal dos veces.
            if (pending) return;
            pending = true;

            var self = this;
            var args = arguments;

            load(file, function (ok) {
                pending = false;
                if (ok === false) return;

                var real = window[name];
                if (typeof real !== 'function' || real === stub) {
                    if (window.console && console.error) console.error('[tickets-app] ' + file + ' no define ' + name + '()');
                    return;
                }

                real.apply(self, args);
            });
        };

        return stub;
    }

    Object.keys(MAP).forEach(function (file) {
        MAP[file].forEach(function (name) {
            window[name] = makeStub(file, name);
        });
    });

    // Con la pantalla ya pintada y el navegador en reposo se dejan los ficheros
    // en la caché HTTP (sin ejecutarlos): el primer clic en un modal no espera
    // a la red, pero el arranque no paga su descarga ni su parseo.
    function prefetchAll() {
        var conn = navigator.connection;
        if (conn && conn.saveData) return;

        Object.keys(MAP).forEach(function (file) {
            var link = document.createElement('link');
            link.rel = 'prefetch';
            link.as = 'script';
            link.href = chunkUrl(file);
            document.head.appendChild(link);
        });
    }

    function schedulePrefetch() {
        var run = function () {
            if (typeof window.requestIdleCallback === 'function') window.requestIdleCallback(prefetchAll, { timeout: 8000 });
            else window.setTimeout(prefetchAll, 4000);
        };

        if (document.readyState === 'complete') run();
        else window.addEventListener('load', run, { once: true });
    }

    schedulePrefetch();
})();
