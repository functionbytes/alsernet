'use strict'

$(() => {
    window.Cookie = (function () {
        const COOKIE_NAME = $('div[data-site-cookie-name]').data('site-cookie-name')
        const COOKIE_DOMAIN = $('div[data-site-cookie-domain]').data('site-cookie-domain')
        const COOKIE_LIFETIME = $('div[data-site-cookie-lifetime]').data('site-cookie-lifetime')
        const SESSION_SECURE = $('div[data-site-session-secure]').data('site-session-secure')

        const $cookieDialog = $('.js-cookie-consent')
        const $cookieCategories = $('.cookie-consent__categories')
        const $customizeButton = $('.js-cookie-consent-customize')

        $cookieDialog.addClass('cookie-consent--visible')
        $cookieCategories.hide()

        /**
         * Set a cookie with the given name, value, and expiration
         */
        function setCookie(name, value, expirationInDays) {
            const date = new Date()
            date.setTime(date.getTime() + expirationInDays * 24 * 60 * 60 * 1000)
            document.cookie =
                name +
                '=' +
                value +
                ';expires=' +
                date.toUTCString() +
                ';domain=' +
                COOKIE_DOMAIN +
                ';path=/' +
                SESSION_SECURE
        }

        /**
         * Get a cookie by name
         */
        function getCookie(name) {
            const value = `; ${document.cookie}`
            const parts = value.split(`; ${name}=`)
            if (parts.length === 2) {
                return parts.pop().split(';').shift()
            }
            return null
        }

        /**
         * Check if a cookie exists
         */
        function cookieExists(name) {
            const cookie = getCookie(name)
            return cookie !== null && cookie !== undefined
        }

        /**
         * Hide the cookie consent dialog
         */
        function hideCookieDialog() {
            $cookieDialog.removeClass('cookie-consent--visible')
            setTimeout(() => {
                $cookieDialog.hide()
            }, 300)
        }

        /**
         * Consent with selected categories
         */
        function consentWithCookies() {
            const categories = {}
            $('.js-cookie-category:checked').each(function() {
                categories[$(this).val()] = true
            })
            setCookie(COOKIE_NAME, JSON.stringify(categories), COOKIE_LIFETIME)
            updateGoogleAnalyticsConsent(categories)
            hideCookieDialog()
        }

        /**
         * Save user preferences
         */
        function savePreferences() {
            consentWithCookies()
            $cookieCategories.slideUp()
            $customizeButton.removeClass('active')
        }

        /**
         * Reject all non-essential cookies
         */
        function rejectAllCookies() {
            // Only keep essential cookies
            const essentialOnly = {
                essential: true
            }

            setCookie(COOKIE_NAME, JSON.stringify(essentialOnly), COOKIE_LIFETIME)

            // Update Google Analytics consent if available
            updateGoogleAnalyticsConsent(essentialOnly)

            hideCookieDialog()
        }

        /**
         * Toggle customize preferences view
         */
        function toggleCustomizeView() {
            $cookieCategories.slideToggle()
            $customizeButton.toggleClass('active')
        }

        /**
         * Update Google Analytics consent mode
         */
        function updateGoogleAnalyticsConsent(categories) {
            if (typeof gtag !== 'undefined') {
                const consents = {
                    'ad_storage': categories.marketing ? 'granted' : 'denied',
                    'analytics_storage': categories.analytics ? 'granted' : 'denied',
                    'functionality_storage': categories.essential ? 'granted' : 'denied',
                    'personalization_storage': categories.marketing ? 'granted' : 'denied',
                    'security_storage': 'granted'
                }

                gtag('consent', 'update', consents)
            }
        }

        /**
         * Initialize - check if cookie already exists
         */
        if (cookieExists(COOKIE_NAME)) {
            hideCookieDialog()
        }

        /**
         * Event Listeners
         */
        $(document).on('click', '.js-cookie-consent-agree', function (e) {
            e.preventDefault()
            consentWithCookies()
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

        /**
         * Public API
         */
        return {
            consentWithCookies: consentWithCookies,
            rejectAllCookies: rejectAllCookies,
            hideCookieDialog: hideCookieDialog,
            savePreferences: savePreferences,
            getCookie: getCookie,
            setCookie: setCookie,
            cookieExists: cookieExists,
        }
    })()
})
