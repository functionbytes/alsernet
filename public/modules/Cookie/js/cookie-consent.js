'use strict'

$(() => {
    window.CookieConsent = (function () {

        const $banner = $('#cookie-banner')

        if (!$banner.length) { return {} }

        const COOKIE_NAME   = $banner.data('cookie-name') || 'cookie_for_consent'
        const COOKIE_DOMAIN = $banner.data('cookie-domain') || window.location.hostname
        const COOKIE_SECURE = $banner.data('cookie-secure') === '1' ? ';secure' : ''
        const COOKIE_DAYS   = 365

        // ── Cookie helpers ──────────────────────────────────────

        function setCookie(name, value, days) {
            const expires = new Date()
            expires.setDate(expires.getDate() + days)
            document.cookie =
                name + '=' + encodeURIComponent(value) +
                ';expires=' + expires.toUTCString() +
                ';domain=' + COOKIE_DOMAIN +
                ';path=/;SameSite=Lax' + COOKIE_SECURE
        }

        function getCookie(name) {
            const value = '; ' + document.cookie
            const parts = value.split('; ' + name + '=')
            if (parts.length === 2) {
                return decodeURIComponent(parts.pop().split(';').shift())
            }
            return null
        }

        // ── GA consent (Google Consent Mode v2) ─────────────────

        function updateGtagConsent(categories) {
            if (typeof gtag !== 'function') { return }

            const marketing  = categories.includes('marketing')
            const analytics  = categories.includes('analytics')
            const prefs      = categories.includes('preferences')

            gtag('consent', 'update', {
                ad_storage:              marketing ? 'granted' : 'denied',
                analytics_storage:       analytics ? 'granted' : 'denied',
                ad_user_data:            marketing ? 'granted' : 'denied',
                ad_personalization:      marketing ? 'granted' : 'denied',
                functionality_storage:   'granted',
                personalization_storage: prefs     ? 'granted' : 'denied',
                security_storage:        'granted',
            })

            if (analytics && window._cookieGaId) {
                gtag('config', window._cookieGaId, { send_page_view: true })
            }
        }

        // ── Facebook Pixel (lazy init) ───────────────────────────

        function initFacebookPixel() {
            if (!window._cookieFbPixelId || window.fbq) { return }
            ;(function (f, b, e, v, n, t, s) {
                n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) }
                if (!f._fbq) { f._fbq = n }
                n.push = n; n.loaded = true; n.version = '2.0'; n.queue = []
                t = b.createElement(e); t.async = true; t.src = v
                s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js'))
            fbq('init', window._cookieFbPixelId)
            fbq('track', 'PageView')
        }

        // ── Microsoft UET ────────────────────────────────────────

        function initMicrosoftUet() {
            if (!window._cookieMsUetId || window.uetq) { return }
            ;(function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[];f=function(){var o={ti:r,enableAutoSoftConversion:true};
            o.q=w[u];w[u]=new UET(o);w[u].push('pageLoad')};n=d.createElement(t);n.src='//bat.bing.com/bat.js';
            n.async=1;n.onload=n.onreadystatechange=function(){var s=this.readyState;
            s&&s!=='loaded'&&s!=='complete'||(f(),n.onload=n.onreadystatechange=null)};
            i=d.getElementsByTagName(t)[0];i.parentNode.insertBefore(n,i)}
            )(window,document,'script','//bat.bing.com/bat.js','uetq')
            window.uetq = window.uetq || []
            window.uetq.push('event', '', { event_category: 'pageview' })
        }

        // ── LinkedIn Insight Tag ──────────────────────────────────

        function initLinkedIn() {
            if (!window._cookieLinkedInPartnerId || window._linkedin_data_partner_ids) { return }
            window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || []
            window._linkedin_data_partner_ids.push(window._cookieLinkedInPartnerId)
            ;(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};
            window.lintrk.q=[]}var s=document.getElementsByTagName('script')[0];
            var b=document.createElement('script');b.type='text/javascript';b.async=true;
            b.src='https://snap.licdn.com/li.lms-analytics/insight.min.js';
            s.parentNode.insertBefore(b,s)})(window.lintrk)
        }

        // ── TikTok Pixel ─────────────────────────────────────────

        function initTikTok() {
            if (!window._cookieTikTokPixelId || window.ttq) { return }
            ;(function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
            ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];
            ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
            for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
            ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
            ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';
            ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;
            ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');
            o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;
            var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};
            ttq.load(window._cookieTikTokPixelId);ttq.page()})(window,document,'ttq')
        }

        // ── Twitter/X Pixel ──────────────────────────────────────

        function initTwitter() {
            if (!window._cookieTwitterPixelId || window.twq) { return }
            ;(function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments)};
            s.version='1.1';s.queue=[];u=t.createElement(n);u.async=!0;u.src='https://static.ads-twitter.com/uwt.js';
            a=t.getElementsByTagName(n)[0];a.parentNode.insertBefore(u,a)})(window,document,'script')
            twq('config', window._cookieTwitterPixelId)
        }

        // ── Log to server ────────────────────────────────────────

        function logConsent(action, categories) {
            const csrfToken = $('meta[name="csrf-token"]').attr('content')
            if (!csrfToken) { return }

            $.ajax({
                url: '/cookie/consent',
                method: 'POST',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: JSON.stringify({ action, accepted_categories: categories }),
            })
        }

        // ── Save consent ─────────────────────────────────────────

        function saveConsent(action, categories) {
            setCookie(COOKIE_NAME, JSON.stringify({ action, categories, version: _currentVersion }), COOKIE_DAYS)
            logConsent(action, categories)
            updateGtagConsent(categories)

            if (categories.includes('marketing')) {
                initFacebookPixel()
                initMicrosoftUet()
                initLinkedIn()
                initTikTok()
                initTwitter()
                unblockEmbeds()
            }

            hideBanner()

            const modal = document.getElementById('cookie-preferences-modal')
            if (modal) {
                const instance = bootstrap.Modal.getInstance(modal)
                if (instance) { instance.hide() }
            }
        }

        // ── Banner visibility ────────────────────────────────────

        function showBanner() {
            $banner.removeClass('d-none')
            requestAnimationFrame(() => $banner.addClass('cookie-consent--visible'))
        }

        function hideBanner() {
            $banner.removeClass('cookie-consent--visible')
            setTimeout(() => $banner.addClass('d-none'), 350)
        }

        // ── Actions ──────────────────────────────────────────────

        function acceptAll() {
            const allCategories = []
            $('.cookie-category-toggle').each(function () {
                allCategories.push($(this).data('category'))
            })
            saveConsent('accept_all', allCategories)
        }

        function rejectAll() {
            saveConsent('reject_all', [])
        }

        function savePreferences() {
            const selected = []
            $('.cookie-category-toggle:checked:not(:disabled)').each(function () {
                selected.push($(this).data('category'))
            })
            saveConsent('custom', selected)
        }

        // ── Embed blocking ───────────────────────────────────────

        function blockEmbeds() {
            $('iframe[data-cookie-src]').each(function () {
                const $iframe = $(this)
                if ($iframe.data('replaced')) { return }
                $iframe.data('replaced', true)

                const category = $iframe.data('cookie-category') || 'marketing'
                const label    = $iframe.data('cookie-label') || 'Contenido bloqueado'

                const $placeholder = $('<div class="cookie-embed-placeholder"></div>')
                    .append('<div class="cookie-embed-placeholder__icon"><i class="fas fa-cookie-bite"></i></div>')
                    .append('<p class="cookie-embed-placeholder__text">' + label + '</p>')
                    .append('<p class="cookie-embed-placeholder__sub">Acepta las cookies de <strong>' + category + '</strong> para ver este contenido.</p>')
                    .append('<button class="cookie-embed-placeholder__btn js-cookie-accept">' +
                            ($banner.find('.js-cookie-accept').text().trim() || 'Aceptar cookies') + '</button>')

                $iframe.before($placeholder).hide()
            })
        }

        function unblockEmbeds() {
            $('iframe[data-cookie-src]').each(function () {
                const $iframe = $(this)
                $iframe.attr('src', $iframe.data('cookie-src')).show()
                $iframe.prev('.cookie-embed-placeholder').remove()
            })
        }

        // ── Init ─────────────────────────────────────────────────

        const _saved = getCookie(COOKIE_NAME)
        const _currentVersion = $banner.data('consent-version') || '1.0'

        let _savedParsed = null
        try { _savedParsed = _saved ? JSON.parse(_saved) : null } catch (_) {}

        const _versionMismatch = _savedParsed && (_savedParsed.version !== _currentVersion)

        if (!_saved || _versionMismatch) {
            showBanner()
        } else {
            // Restore consent signals for returning visitors so GCM fires correctly
            try {
                const { categories } = JSON.parse(_saved)
                updateGtagConsent(Array.isArray(categories) ? categories : [])
                if (Array.isArray(categories) && categories.includes('marketing')) {
                    initFacebookPixel()
                    initMicrosoftUet()
                    initLinkedIn()
                    initTikTok()
                    initTwitter()
                    unblockEmbeds()
                }
            } catch (_) {}
        }

        blockEmbeds()

        // ── Events ───────────────────────────────────────────────

        $(document).on('click', '.js-cookie-accept', function (e) {
            e.preventDefault()
            acceptAll()
        })

        $(document).on('click', '.js-cookie-reject', function (e) {
            e.preventDefault()
            rejectAll()
        })

        $(document).on('click', '.js-cookie-accept-modal', function (e) {
            e.preventDefault()
            acceptAll()
        })

        $(document).on('click', '.js-cookie-save-preferences', function (e) {
            e.preventDefault()
            savePreferences()
        })

        // ── Public API ───────────────────────────────────────────

        return { acceptAll, rejectAll, savePreferences, showBanner, hideBanner, getCookie, setCookie }

    })()
})
