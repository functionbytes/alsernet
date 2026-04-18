$(function () {

    // ── Cookie helpers ────────────────────────────────────────────────────────
    function setCookie(name, durationMs) {
        var d = new Date();
        d.setTime(d.getTime() + (durationMs || 3600000));
        document.cookie = name + '=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var c = '; ' + document.cookie;
        var parts = c.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    }

    // ── Error display ─────────────────────────────────────────────────────────
    function showFormError(container, msg) {
        var errEl = container.find('.newsletter-error-message');
        if (errEl.length) {
            errEl.text(msg).show();
        } else if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            console.error('[Newsletter]', msg);
        }
    }

    function showFormErrors(container, errors) {
        var msgs = [];
        $.each(errors, function (k, v) { msgs.push(Array.isArray(v) ? v[0] : v); });
        showFormError(container, msgs.join('\n'));
    }

    // ── reCAPTCHA helpers ─────────────────────────────────────────────────────
    function renderCaptcha(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if (el.querySelector('iframe')) return;
        if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
            try {
                grecaptcha.render(id, { sitekey: el.getAttribute('data-sitekey') });
            } catch (e) { /* already rendered */ }
        } else {
            window.recaptchaInputs = window.recaptchaInputs || [];
            window.recaptchaInputs.push(id);
        }
    }

    function resetCaptcha() {
        if (typeof refreshRecaptcha === 'function') {
            refreshRecaptcha();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // POPUP
    // ═══════════════════════════════════════════════════════════════════════════
    var popupEl = $('#newsletter-popup');

    if (popupEl.length) {
        var delay = 1000 * (parseInt(popupEl.data('delay')) || 5);

        if (!getCookie('newsletter_popup')) {
            fetch(popupEl.data('url'), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Network error');
                return r.json();
            })
            .then(function (res) {
                var d = res.data;
                if (d.show_popup === false) return;
                popupEl.html(d.html);
                if (typeof Theme !== 'undefined' && Theme.lazyLoadInstance) Theme.lazyLoadInstance.update();
                setTimeout(function () {
                    if (popupEl.find('.newsletter-popup-content').length) {
                        popupEl.modal('show');
                    }
                }, delay);
            })
            .catch(function (err) {
                console.error('[Newsletter popup] fetch error:', err);
            });
        }

        popupEl.on('shown.bs.modal', function () {
            renderCaptcha('newsletter-popup-captcha');
        });

        popupEl.on('hide.bs.modal', function () {
            var dontShow = popupEl.find('input[name="dont_show_again"]').is(':checked');
            setCookie('newsletter_popup', dontShow ? 2592000000 : 3600000);
        });

        document.addEventListener('newsletter.subscribed', function () {
            setCookie('newsletter_popup', 604800000);
        });

        $(document).on('submit', 'form.bb-newsletter-popup-form', function (ev) {
            ev.preventDefault();
            var form = $(ev.currentTarget);
            var btn = form.find('button[type=submit]');
            popupEl.find('.newsletter-message').hide();

            $.ajax({
                type: 'POST',
                cache: false,
                url: form.prop('action'),
                data: new FormData(form[0]),
                contentType: false,
                processData: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function () { btn.prop('disabled', true).addClass('button-loading'); },
                success: function (res) {
                    if (res.success) {
                        form.find('input[name="email"]').val('');
                        form.find('input[name="name"]').val('');
                        popupEl.find('.newsletter-success-message').text(res.message).show();
                        document.dispatchEvent(new CustomEvent('newsletter.subscribed'));
                        setTimeout(function () { popupEl.modal('hide'); }, 2000);
                    } else {
                        showFormError(popupEl, res.message);
                        resetCaptcha();
                    }
                },
                error: function (xhr) {
                    resetCaptcha();
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showFormErrors(popupEl, xhr.responseJSON.errors);
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        showFormError(popupEl, xhr.responseJSON.message);
                    } else {
                        showFormError(popupEl, xhr.statusText || 'Error al enviar');
                    }
                },
                complete: function () { btn.prop('disabled', false).removeClass('button-loading'); }
            });
        });

        window.newsletterPopupDebug = {
            clearCookie: function () { deleteCookie('newsletter_popup'); },
            reload: function () { deleteCookie('newsletter_popup'); location.reload(); }
        };
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // INLINE FORM
    // ═══════════════════════════════════════════════════════════════════════════
    $(document).on('submit', 'form.bb-newsletter-inline-form', function (ev) {
        ev.preventDefault();
        var form = $(ev.currentTarget);
        var btn = form.find('button[type=submit]');
        form.find('.newsletter-message').hide();

        $.ajax({
            type: 'POST',
            cache: false,
            url: form.prop('action'),
            data: new FormData(form[0]),
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function () { btn.prop('disabled', true).addClass('button-loading'); },
            success: function (res) {
                if (res.success) {
                    form.find('input[name="email"], input[name="name"]').val('');
                    form.find('.newsletter-success-message').text(res.message).show();
                } else {
                    showFormError(form, res.message);
                }
            },
            error: function (xhr) {
                resetCaptcha();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showFormErrors(form, xhr.responseJSON.errors);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    showFormError(form, xhr.responseJSON.message);
                } else {
                    showFormError(form, xhr.statusText || 'Error al enviar');
                }
            },
            complete: function () { btn.prop('disabled', false).removeClass('button-loading'); }
        });
    });

});
