'use strict'

$(() => {
    window.CookieConsent = (function () {

        const $banner   = $('#cookie-banner')
        const $backdrop = $('#cookie-backdrop')

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
            setCookie(COOKIE_NAME, JSON.stringify({ action, categories }), COOKIE_DAYS)
            logConsent(action, categories)
            updateGtagConsent(categories)

            if (categories.includes('marketing')) {
                initFacebookPixel()
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
            $backdrop.fadeIn(200)
            $banner.removeClass('d-none')
            requestAnimationFrame(() => $banner.addClass('cookie-consent--visible'))
        }

        function hideBanner() {
            $backdrop.fadeOut(200)
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

        // ── Init ─────────────────────────────────────────────────

        const _saved = getCookie(COOKIE_NAME)

        if (!_saved) {
            showBanner()
        } else {
            // Restore consent signals for returning visitors so GCM fires correctly
            try {
                const { categories } = JSON.parse(_saved)
                updateGtagConsent(Array.isArray(categories) ? categories : [])
                if (Array.isArray(categories) && categories.includes('marketing')) {
                    initFacebookPixel()
                }
            } catch (_) {}
        }

        // ── Events ───────────────────────────────────────────────

        $(document).on('click', '.js-cookie-accept', function (e) {
            e.preventDefault()
            acceptAll()
        })

        $(document).on('click', '.js-cookie-reject', function (e) {
            e.preventDefault()
            rejectAll()
        })

        $(document).on('click', '.js-cookie-customize', function () {
            hideBanner()
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
