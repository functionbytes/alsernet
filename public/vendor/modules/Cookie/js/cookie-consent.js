'use strict'

$(() => {
    window.Cookie = (function () {

        const $banner     = $('.js-cookie-consent')
        const $categories = $('.cookie-consent__categories')

        const COOKIE_NAME     = $banner.data('cookie-name') || 'cookie_for_consent'
        const COOKIE_DOMAIN   = $banner.data('cookie-domain') || window.location.hostname
        const COOKIE_LIFETIME = parseInt($banner.data('cookie-lifetime') || 36000, 10)
        const SESSION_SECURE  = $banner.data('session-secure') || ''

        // ── Cookie helpers ──────────────────────────────────────

        function setCookie(name, value, days) {
            const date = new Date()
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000)
            document.cookie =
                name + '=' + encodeURIComponent(value) +
                ';expires=' + date.toUTCString() +
                ';domain=' + COOKIE_DOMAIN +
                ';path=/' + SESSION_SECURE +
                ';SameSite=Lax'
        }

        function getCookie(name) {
            const value = '; ' + document.cookie
            const parts = value.split('; ' + name + '=')
            if (parts.length === 2) {
                return decodeURIComponent(parts.pop().split(';').shift())
            }
            return null
        }

        function cookieExists(name) {
            return getCookie(name) !== null
        }

        // ── Banner visibility ───────────────────────────────────

        function showBanner() {
            $banner.show()
            setTimeout(() => $banner.addClass('cookie-consent--visible'), 10)
        }

        function hideBanner() {
            $banner.removeClass('cookie-consent--visible')
            setTimeout(() => $banner.hide(), 300)
        }

        // ── Customize view ──────────────────────────────────────

        function toggleCustomizeView() {
            $categories.slideToggle(250)
        }

        // ── Google Analytics consent update ─────────────────────

        function updateGtagConsent(categories) {
            if (typeof gtag !== 'function') {
                return
            }

            gtag('consent', 'update', {
                ad_storage: categories.marketing ? 'granted' : 'denied',
                analytics_storage: categories.analytics ? 'granted' : 'denied',
                functionality_storage: categories.essential ? 'granted' : 'denied',
                personalization_storage: categories.marketing ? 'granted' : 'denied',
                security_storage: 'granted'
            })
        }

        // ── Facebook Pixel consent update ────────────────────────

        function updateFbqConsent(categories) {
            if (typeof fbq !== 'function') {
                return
            }

            if (categories.marketing) {
                fbq('consent', 'grant')
            } else {
                fbq('consent', 'revoke')
            }
        }

        // ── Consent actions ─────────────────────────────────────

        function acceptAllCookies() {
            const categories = { essential: true }
            $('.js-cookie-category').each(function () {
                categories[$(this).val()] = true
            })
            setCookie(COOKIE_NAME, JSON.stringify(categories), COOKIE_LIFETIME)
            updateGtagConsent(categories)
            updateFbqConsent(categories)
            hideBanner()
        }

        function rejectAllCookies() {
            const categories = { essential: true }
            setCookie(COOKIE_NAME, JSON.stringify(categories), COOKIE_LIFETIME)
            updateGtagConsent(categories)
            updateFbqConsent(categories)
            hideBanner()
        }

        function savePreferences() {
            const categories = { essential: true }
            $('.js-cookie-category:checked').each(function () {
                categories[$(this).val()] = true
            })
            setCookie(COOKIE_NAME, JSON.stringify(categories), COOKIE_LIFETIME)
            updateGtagConsent(categories)
            updateFbqConsent(categories)
            hideBanner()
        }

        // ── Init ────────────────────────────────────────────────

        if (cookieExists(COOKIE_NAME)) {
            $banner.hide()
        } else {
            showBanner()
        }

        // ── Event listeners ─────────────────────────────────────

        $(document).on('click', '.js-cookie-consent-agree', function (e) {
            e.preventDefault()
            acceptAllCookies()
        })

        $(document).on('click', '.js-cookie-consent-reject', function (e) {
            e.preventDefault()
            rejectAllCookies()
        })

        $(document).on('click', '.js-cookie-consent-customize', function (e) {
            e.preventDefault()
            toggleCustomizeView()
        })

        $(document).on('click', '.js-cookie-consent-save', function (e) {
            e.preventDefault()
            savePreferences()
        })

        // ── Public API ──────────────────────────────────────────

        return {
            acceptAllCookies,
            rejectAllCookies,
            savePreferences,
            hideBanner,
            showBanner,
            toggleCustomizeView,
            getCookie,
            setCookie,
            cookieExists,
        }

    })()
})
