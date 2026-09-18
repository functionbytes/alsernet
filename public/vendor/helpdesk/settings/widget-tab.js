/**
 * Partial settings/inboxes/_partials/widget-tab.blade.php — pestaña "Widget"
 * del formulario de bandejas para el canal Web (requiere HelpdeskLivechat).
 */
(function () {
    // Show/hide dependent timeout fields
    document.querySelectorAll('.wt-timeout-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var target = document.querySelector(cb.dataset.target);
            if (target) {
                target.style.display = cb.checked ? '' : 'none';
            }
        });
    });

    // CMS type selector → toggle which install snippet is visible
    var cmsSelect = document.getElementById('wt_cms_type');
    var integrationWrapper = document.getElementById('wt_integration_wrapper');
    var integrationSelect = document.getElementById('wt_platform_integration');

    function showSnippetFor(cms) {
        document.querySelectorAll('.wt-install-snippet').forEach(function (el) {
            el.classList.toggle('d-none', el.dataset.cms !== cms);
        });
    }

    function fetchIntegrations(cms) {
        if (! integrationSelect) {
            return;
        }
        var url = integrationSelect.dataset.url;
        var currentId = integrationSelect.dataset.currentId;
        integrationSelect.innerHTML = '<option value="">Sin vincular</option>';

        if (cms === 'custom' || ! url) {
            return;
        }

        fetch(url + '?platform=' + encodeURIComponent(cms), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (rows) {
                if (! Array.isArray(rows) || rows.length === 0) {
                    return;
                }
                rows.forEach(function (row) {
                    var opt = document.createElement('option');
                    opt.value = row.id;
                    opt.textContent = row.name + (row.store_url ? ' — ' + row.store_url : '');
                    if (String(row.id) === String(currentId)) {
                        opt.selected = true;
                    }
                    integrationSelect.appendChild(opt);
                });
            })
            .catch(function () { /* silent — endpoint optional */ });
    }

    if (cmsSelect) {
        cmsSelect.addEventListener('change', function () {
            var val = cmsSelect.value;
            showSnippetFor(val);

            if (integrationWrapper) {
                integrationWrapper.classList.toggle('d-none', val === 'custom');
            }
            fetchIntegrations(val);
        });

        // Initial population on page load (only if Engagement is wired)
        if (integrationSelect) {
            fetchIntegrations(cmsSelect.value);
        }
    }

    // Copy buttons for install snippets (event delegation)
    document.querySelectorAll('.wt-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var pre = btn.parentElement.querySelector('.wt-install-code');
            var label = btn.querySelector('.wt-copy-label');
            if (! pre || ! navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText(pre.textContent).then(function () {
                if (label) {
                    label.textContent = '¡Copiado!';
                    setTimeout(function () { label.textContent = 'Copiar'; }, 2000);
                }
            });
        });
    });
})();

function copyWidgetHmacToken() {
    var val = document.getElementById('wt_hmac_token').value;
    navigator.clipboard.writeText(val).then(function () {
        var el = document.getElementById('wt_copy_hmac_label');
        el.textContent = '¡Copiada!';
        setTimeout(function () { el.textContent = 'Copiar'; }, 2000);
    });
}
