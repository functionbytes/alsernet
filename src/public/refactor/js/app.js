/**
 * Acelle Mail — Main Application Entry Point
 * Initializes all core modules on DOMContentLoaded.
 */
document.addEventListener('DOMContentLoaded', () => {
    // SessionGuard: globally handle 401 so the user never sees a raw
    // `{"message":"Unauthenticated."}` alert/toast at logout or idle-session-
    // expiry. Runs before all other init so the wrapped fetch is in place
    // when modules start firing requests.
    (function () {
        function markSignOut() { window.__acelleSignOutClicked = true; }
        document.addEventListener('submit', function (e) {
            if (e.target && e.target.id === 'mc-logout-form') markSignOut();
        }, true);
        document.addEventListener('click', function (e) {
            var a = e.target && e.target.closest && e.target.closest('a[href$="/logout"]');
            if (a) markSignOut();
        }, true);

        function handle401() {
            if (window.__acelleSignOutClicked) return;
            if (/\/(login|admin\/login|rui\/admin\/login)(\?|$)/.test(location.pathname)) return;
            location.reload();
        }

        // jQuery callers — refactor's automation layout still loads jQuery
        // for the flow editor. Cover any $.ajax 401 there.
        if (window.jQuery) {
            window.jQuery(document).ajaxError(function (event, request) {
                if (request && request.status === 401) {
                    try { request.responseText = ''; } catch (e) {}
                    try { request.responseJSON = null; } catch (e) {}
                    handle401();
                }
            });
        }

        // fetch() callers — notifications, dashboard chart, list polling,
        // InlineEdit, dropdowns. Wrap once; subsequent loads are no-ops.
        if (window.fetch && !window.fetch.__acelleWrapped) {
            var nativeFetch = window.fetch;
            window.fetch = function (input, init) {
                return nativeFetch.call(this, input, init).then(function (resp) {
                    if (resp && resp.status === 401) {
                        handle401();
                        // Return an opaque empty-object response so callers'
                        // `.then(r.json())` doesn't expose the framework body.
                        return new Response('{}', {
                            status: 401,
                            headers: { 'Content-Type': 'application/json' },
                        });
                    }
                    return resp;
                });
            };
            window.fetch.__acelleWrapped = true;
        }
    })();

    // Initialize theme
    if (window.McTheme) {
        window.McTheme.init();
    }

    // Initialize color scheme
    if (window.McColorScheme) {
        window.McColorScheme.init();
    }

    // Initialize sidebar
    if (window.McSidebar) {
        window.McSidebar.init();
    }

    // Initialize notifications
    if (window.McNotify) {
        window.McNotify.init();
    }

    // Initialize dropdowns
    if (window.McDropdown) {
        window.McDropdown.init();
    }

    // Initialize popups
    if (window.McPopup) {
        window.McPopup.init();
    }

    // Initialize forms
    if (window.McForm) {
        window.McForm.init();
    }

    // Initialize tabs
    if (window.McTabs) {
        window.McTabs.init();
    }

    // Initialize file uploads
    if (window.McFileUpload) {
        window.McFileUpload.init();
    }

    // Initialize mc-file-input (simple form file inputs with drop zone style)
    document.querySelectorAll('.mc-file-input input[type="file"]').forEach(function(input) {
        if (input._mcFileInputInit) return;
        input._mcFileInputInit = true;
        var label = input.closest('.mc-file-input');
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                label.classList.add('has-file');
                var nameEl = label.querySelector('.mc-file-input-selected-name');
                var sizeEl = label.querySelector('.mc-file-input-selected-size');
                if (nameEl) nameEl.textContent = this.files[0].name;
                if (sizeEl) {
                    var kb = (this.files[0].size / 1024).toFixed(1);
                    sizeEl.textContent = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
                }
            } else {
                label.classList.remove('has-file');
            }
        });
        // Drag over visual feedback
        label.addEventListener('dragover', function(e) { e.preventDefault(); label.classList.add('is-dragover'); });
        label.addEventListener('dragleave', function() { label.classList.remove('is-dragover'); });
        label.addEventListener('drop', function(e) {
            e.preventDefault();
            label.classList.remove('is-dragover');
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Initialize list switcher(s) — auto-init for mc-list-switcher
    document.querySelectorAll('[data-list-switcher]').forEach(function(switcher) {
        if (switcher._mcSwitcherInit) return;
        switcher._mcSwitcherInit = true;
        var trigger = switcher.querySelector('[data-list-switcher-trigger]');
        var searchInput = switcher.querySelector('[data-list-switcher-search]');
        var items = switcher.querySelectorAll('.mc-list-switcher-item');

        if (trigger) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                switcher.classList.toggle('open');
                if (switcher.classList.contains('open') && searchInput) {
                    setTimeout(function() { searchInput.focus(); }, 50);
                }
            });
        }
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var q = this.value.toLowerCase().trim();
                items.forEach(function(item) {
                    var name = item.getAttribute('data-name') || '';
                    item.classList.toggle('mc-list-switcher-hidden', q && name.indexOf(q) === -1);
                });
            });
            searchInput.addEventListener('click', function(e) { e.stopPropagation(); });
        }
        switcher.querySelector('.mc-list-switcher-dropdown').addEventListener('click', function(e) { e.stopPropagation(); });
        document.addEventListener('click', function() { switcher.classList.remove('open'); });
    });

    // Initialize Power Search (command palette)
    if (window.McPowerSearchConfig && window.PowerSearch) {
        window.McPowerSearch = new PowerSearch(window.McPowerSearchConfig);
    }

    // Onboarding: WelcomeSlides (product intro) → AppTour (interactive guide)
    // Server flag (McOnboardingCompleted) is the source of truth.
    var isDashboard = window.location.pathname === '/' || window.location.pathname === '/dashboard';

    if (isDashboard && !window.McOnboardingCompleted) {
        // New user — show WelcomeSlides first, then chain AppTour after
        if (window.McWelcomeSlides && !localStorage.getItem('acelle_welcome_seen')) {
            setTimeout(function() {
                McWelcomeSlides.show(function() {
                    // After slides close, start interactive tour
                    if (window.McAppTour) {
                        setTimeout(function() { McAppTour.start(); }, 300);
                    }
                });
            }, 800);
        } else if (window.McAppTour && !localStorage.getItem('acelle_app_tour_completed')) {
            // Welcome slides already seen but tour not done — start tour directly
            setTimeout(function() { McAppTour.start(); }, 800);
        }
    }

    // Topbar buttons — manual re-trigger on any page
    if (window.McWelcomeSlides) {
        var welcomeBtn = document.getElementById('mc-welcome-trigger');
        if (welcomeBtn) {
            welcomeBtn.addEventListener('click', function() {
                McWelcomeSlides.show();
            });
        }
    }
    if (window.McAppTour) {
        var tourBtn = document.getElementById('mc-tour-trigger');
        if (tourBtn) {
            tourBtn.addEventListener('click', function() {
                McAppTour.start(true);
            });
        }
    }

    // Show flash messages from server
    const flashContainer = document.querySelector('[data-flash-messages]');
    if (flashContainer && window.McNotify) {
        const messages = flashContainer.querySelectorAll('[data-flash]');
        messages.forEach(msg => {
            const type = msg.dataset.flash;
            const text = msg.textContent.trim();
            if (text) {
                window.McNotify.show(text, type);
            }
        });
    }

    // =========================================================================
    //  Topbar Language Switcher
    //  Handles mc-lang-item clicks inside the topbar dropdown.
    // =========================================================================
    document.addEventListener('click', function(e) {
        var item = e.target.closest('.mc-topbar-lang-dropdown .mc-lang-item[data-lang-uid]');
        if (!item) return;
        e.preventDefault();

        var uid = item.dataset.langUid;
        var url = item.dataset.changeLangUrl;
        if (!uid || !url) return;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ uid: uid })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                if (window.McNotify) McNotify.success(data.message);
                setTimeout(function() { window.location.reload(); }, 600);
            }
        })
        .catch(function() {
            if (window.McNotify) McNotify.error('Something went wrong');
        });
    });

    // =========================================================================
    //  Button spinner (data-mc-spinner)
    //  Visual loading feedback while the browser navigates/submits. Does NOT
    //  prevent default — the form submits / link navigates normally; the spinner
    //  just sits on the triggering button until the next page loads.
    //
    //  Opt-in targets (any one enables it):
    //    - <form data-mc-spinner>             → spins the submit button used
    //    - <button data-mc-spinner>           → spins on click/submit
    //    - <a data-mc-spinner href="...">     → spins on navigation click
    // =========================================================================
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        var btn = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        if (!btn) return;
        if (form.hasAttribute('data-mc-spinner') || btn.hasAttribute('data-mc-spinner')) {
            btn.classList.add('is-loading');
        }
    });

    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-mc-spinner]');
        if (!el || el.tagName !== 'A') return;
        var href = el.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        if (el.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey) return;
        el.classList.add('is-loading');
    });

    // =========================================================================
    //  AJAX Action System
    //  Handles data-method="POST" links and data-bulk-action buttons via AJAX.
    //  After success: shows toast + dispatches 'list:reload' event.
    // =========================================================================

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /**
     * Execute a POST action via AJAX.
     * @param {string} url - Action URL
     * @param {FormData|null} formData - Form data (with _token + uids)
     * @param {string} actionLabel - Label for toast message
     */
    function ajaxAction(url, formData, actionLabel) {
        if (!formData) {
            formData = new FormData();
        }
        if (!formData.has('_token')) {
            formData.append('_token', getCsrfToken());
        }

        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) {
            return r.text().catch(function() { return ''; }).then(function(text) {
                return { ok: r.ok, status: r.status, body: text };
            });
        })
        .then(function(res) {
            var data;
            try { data = JSON.parse(res.body); } catch(e) { data = {}; }

            // Error response — extract message from JSON, plain text, or HTML
            if (!res.ok) {
                var msg = data.message || data.error || '';
                if (!msg && data.errors) {
                    msg = Object.values(data.errors).flat().join('. ');
                }
                if (!msg && res.body) {
                    var stripped = res.body.replace(/<[^>]+>/g, '').trim();
                    if (stripped.length < 300) msg = stripped;
                }
                if (window.McNotify) {
                    McNotify.error(msg || 'Something went wrong. Please try again.');
                }
                return;
            }

            // Success response
            var msg = data.message || (res.body ? res.body.trim() : '') || actionLabel || 'Done';
            if (window.McNotify) {
                McNotify.success(msg);
            }

            // Redirect or reload if server requests it (e.g. subscription state change)
            if (data.redirect) {
                setTimeout(function() { window.location.href = data.redirect; }, 600);
                return;
            }
            if (data.reload) {
                setTimeout(function() { window.location.reload(); }, 600);
                return;
            }

            // Tell any list on the page to reload
            document.dispatchEvent(new CustomEvent('list:reload'));
        })
        .catch(function(err) {
            if (window.McNotify) {
                McNotify.error(err.message || 'Something went wrong. Please try again.');
            }
        });
    }

    // Global action handler: data-method="POST" + data-confirm="..."
    // Supports: data-uid (sent as uids[]), data-body (JSON → FormData)
    document.addEventListener('click', function(e) {
        var link = e.target.closest('[data-method]');
        if (!link) return;

        e.preventDefault();
        var url = link.dataset.url || link.getAttribute('href');
        var confirmMsg = link.dataset.confirm;
        // Get visible text only — exclude material icon text nodes
        var clone = link.cloneNode(true);
        clone.querySelectorAll('.material-symbols-rounded').forEach(function(icon) { icon.remove(); });
        var actionLabel = clone.textContent.trim();

        function doAction() {
            var formData = null;

            // Support data-body: JSON string → FormData
            if (link.dataset.body) {
                try {
                    var body = JSON.parse(link.dataset.body);
                    formData = new FormData();
                    Object.keys(body).forEach(function(key) {
                        var val = body[key];
                        if (Array.isArray(val)) {
                            val.forEach(function(v) { formData.append(key + '[]', v); });
                        } else {
                            formData.append(key, val);
                        }
                    });
                } catch(e) { /* ignore parse errors */ }
            }

            // Support data-uid: sent as uids[]
            if (link.dataset.uid) {
                if (!formData) formData = new FormData();
                formData.append('uids[]', link.dataset.uid);
            }

            ajaxAction(url, formData, actionLabel);
        }

        if (confirmMsg) {
            if (window.McDialog) {
                McDialog.confirm({
                    title: 'Are you sure?',
                    message: confirmMsg,
                    type: link.classList.contains('mc-dropdown-item-danger') ? 'danger' : 'warning',
                    confirmText: actionLabel || 'Confirm',
                    onConfirm: doAction
                });
            } else if (confirm(confirmMsg)) {
                doAction();
            }
        } else {
            doAction();
        }
    });

    // Bulk select: check all checkbox
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('mc-check-all')) {
            var checked = e.target.checked;
            var container = e.target.closest('table') || document;
            container.querySelectorAll('.mc-row-check').forEach(function(cb) {
                cb.checked = checked;
            });
            updateBulkActions();
        }
        if (e.target.classList.contains('mc-row-check')) {
            updateBulkActions();
        }
    });

    function updateBulkActions() {
        var checked = document.querySelectorAll('.mc-row-check:checked');
        var bulkBtn = document.querySelector('.mc-bulk-actions-btn');
        var bulkCount = document.querySelector('.mc-bulk-count');
        if (bulkBtn) {
            if (checked.length > 0) {
                bulkBtn.classList.remove('mc-hidden');
                if (bulkCount) bulkCount.textContent = checked.length;
            } else {
                bulkBtn.classList.add('mc-hidden');
            }
        }
    }

    // Bulk action execution via AJAX
    document.addEventListener('click', function(e) {
        var action = e.target.closest('[data-bulk-action]');
        if (!action) return;

        e.preventDefault();
        var url = action.dataset.bulkAction;
        var confirmMsg = action.dataset.confirm;
        var checked = document.querySelectorAll('.mc-row-check:checked');
        if (checked.length === 0) return;

        var actionLabel = action.textContent.trim();

        function doBulk() {
            var formData = new FormData();
            formData.append('_token', getCsrfToken());
            Array.from(checked).forEach(function(cb) {
                formData.append('uids[]', cb.value);
            });
            ajaxAction(url, formData, actionLabel);
        }

        if (confirmMsg) {
            if (window.McDialog) {
                McDialog.confirm({
                    title: 'Are you sure?',
                    message: confirmMsg,
                    type: action.classList.contains('danger') ? 'danger' : 'warning',
                    confirmText: actionLabel,
                    onConfirm: doBulk
                });
            } else if (confirm(confirmMsg)) {
                doBulk();
            }
        } else {
            doBulk();
        }
    });
});
