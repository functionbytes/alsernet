/**
 * Remarketing pixel — embeds tracking on external ecommerce stores.
 * Usage:
 *   <script>window._rmkEndpoint='/r/track';window._rmk=window._rmk||[];window._rmk.push(['store','STORE_TOKEN']);</script>
 *   <script src="/remarketing/pixel.js" async></script>
 */
(function () {
    var endpoint = window._rmkEndpoint || '/r/track';
    var queue = window._rmk || [];
    var storeToken = null;
    var visitorId = null;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
    }

    function uuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function ensureVisitor() {
        visitorId = getCookie('_rmk_vid');
        if (!visitorId) {
            visitorId = uuid();
            setCookie('_rmk_vid', visitorId, 365);
        }
    }

    function send(type, properties) {
        if (!storeToken) {
            return;
        }
        try {
            // Form-encoded para evitar preflight CORS y permitir cross-origin sin credenciales.
            var params = new URLSearchParams();
            params.append('store_token', storeToken);
            params.append('type', type);
            if (visitorId) {
                params.append('visitor_id', visitorId);
            }
            params.append('occurred_at', new Date().toISOString());
            params.append('properties', JSON.stringify(properties || {}));

            var body = params.toString();

            if (navigator.sendBeacon) {
                // sendBeacon con application/x-www-form-urlencoded no genera preflight CORS.
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/x-www-form-urlencoded' }));
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', endpoint, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.withCredentials = false;
                xhr.send(body);
            }
        } catch (e) {
            /* silent */
        }
    }

    function processCommand(cmd) {
        if (!cmd || !cmd.length) {
            return;
        }
        var op = cmd[0];
        if (op === 'store') {
            storeToken = cmd[1];
            ensureVisitor();
            send('page_view', {
                url: location.href,
                title: document.title,
                referrer: document.referrer || ''
            });
        } else if (op === 'identify') {
            send('identify', { email: cmd[1], first_name: cmd[2] || '' });
        } else if (op === 'track') {
            send(cmd[1], cmd[2] || {});
        }
    }

    while (queue.length) {
        processCommand(queue.shift());
    }

    window._rmk = { push: processCommand };
})();
