'use strict'

$(() => {
    window.CookieConsent = (function () {

        const $banner = $('#cookie-banner')

        if (!$banner.length) { return {} }

        const COOKIE_NAME   = $banner.data('cookie-name') || 'cookie_for_consent'
        const COOKIE_DOMAIN = $banner.data('cookie-domain') || window.location.hostname
        const COOKIE_SECURE = $banner.data('cookie-secure') === '1' ? ';secure' : ''
        const COOKIE_DAYS   = parseInt($banner.data('cookie-lifetime'), 10) || 365

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

        // ── GA consent ──────────────────────────────────────────

        function updateGtagConsent(categories) {
            if (typeof gtag !== 'function') { return }

            gtag('consent', 'update', {
                ad_storage: categories.includes('marketing') ? 'granted' : 'denied',
                analytics_storage: categories.includes('analytics') ? 'granted' : 'denied',
                personalization_storage: categories.includes('preferences') ? 'granted' : 'denied',
                functionality_storage: 'granted',
                security_storage: 'granted',
            })

            if (categories.includes('analytics') && window._cookieGaId) {
                gtag('config', window._cookieGaId)
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

        function logConsent(action, categories, isUpdate) {
            const csrfToken = $('meta[name="csrf-token"]').attr('content')
            if (!csrfToken) { return }

            $.ajax({
                url: '/cookie/consent',
                method: 'POST',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: JSON.stringify({ action, accepted_categories: categories, is_update: isUpdate }),
            })
        }

        // ── Save consent ─────────────────────────────────────────

        function saveConsent(action, categories, isUpdate) {
            setCookie(COOKIE_NAME, JSON.stringify({ action, categories }), COOKIE_DAYS)
            logConsent(action, categories, isUpdate)
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
            $banner.removeClass('d-none')
        }

        function hideBanner() {
            $banner.addClass('d-none')
        }

        // ── Actions ──────────────────────────────────────────────

        function acceptAll(isUpdate) {
            const allCategories = []
            $('.cookie-category-toggle').each(function () {
                allCategories.push($(this).data('category'))
            })
            saveConsent('accept_all', allCategories, isUpdate || false)
        }

        function rejectAll() {
            saveConsent('reject_all', [], false)
        }

        function savePreferences() {
            const selected = []
            $('.cookie-category-toggle:checked:not(:disabled)').each(function () {
                selected.push($(this).data('category'))
            })
            saveConsent('custom', selected, !!getCookie(COOKIE_NAME))
        }

        // ── Init ─────────────────────────────────────────────────

        if (!getCookie(COOKIE_NAME)) {
            showBanner()
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

        $(document).on('click', '.js-cookie-accept-modal', function (e) {
            e.preventDefault()
            acceptAll(!!getCookie(COOKIE_NAME))
        })

        $(document).on('click', '.js-cookie-save-preferences', function (e) {
            e.preventDefault()
            savePreferences()
        })

        // ── Public API ───────────────────────────────────────────

        return { acceptAll, rejectAll, savePreferences, showBanner, hideBanner, getCookie, setCookie }

    })()
})
