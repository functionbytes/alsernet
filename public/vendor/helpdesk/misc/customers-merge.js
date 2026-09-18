/**
 * Helpdesk · Misc — modal "Fusionar contacto" de customers/show.
 *
 * Requiere `window.HdCustomerMergeConfig` definido antes de cargar este
 * script:
 *
 *   window.HdCustomerMergeConfig = {
 *       baseCustomerId: {{ $customer->id }},
 *       searchUrl: '{{ route('manager.helpdesk.customers.search') }}',
 *       mergeUrl: '{{ route('manager.helpdesk.customers.merge') }}',
 *       showUrl: '{{ route('manager.helpdesk.customers.show', $customer) }}',
 *   };
 */
(function ($) {
    'use strict';

    const cfg = window.HdCustomerMergeConfig;

    if (!cfg) {
        return;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function init() {
        const BASE_CUSTOMER_ID = cfg.baseCustomerId;
        const SEARCH_URL = cfg.searchUrl;
        const MERGE_URL = cfg.mergeUrl;
        const SHOW_URL = cfg.showUrl;
        const CSRF = $('meta[name="csrf-token"]').attr('content');

        let selectedMergeeId = null;
        let searchTimer = null;

        const $modal = $('#mergeContactModal');

        if (!$modal.length) {
            return;
        }

        const $input = $('#mergeSearchInput');
        const $results = $('#mergeSearchResults');
        const $selected = $('#mergeSelectedContact');
        const $confirmBtn = $('#btnConfirmMerge');

        // Debounced search
        $input.on('input', function () {
            clearTimeout(searchTimer);
            const q = $(this).val().trim();

            if (q.length < 2) {
                $results.empty();
                return;
            }

            searchTimer = setTimeout(() => performSearch(q), 300);
        });

        function performSearch(q) {
            $results.html('<div class="text-center py-2 text-muted small"><i class="fas fa-circle-notch fa-spin me-1"></i> Buscando...</div>');

            $.get(SEARCH_URL, { q: q, exclude: BASE_CUSTOMER_ID }, function (data) {
                renderResults(data.filter(c => c.id !== BASE_CUSTOMER_ID));
            }).fail(function () {
                $results.html('<div class="text-muted small py-2">Error al buscar contactos.</div>');
            });
        }

        function renderResults(contacts) {
            if (contacts.length === 0) {
                $results.html('<div class="text-muted small py-2">No se encontraron contactos.</div>');
                return;
            }

            const items = contacts.map(c => `
                <div class="d-flex align-items-center gap-2 py-2 px-1 rounded merge-result-item bv-cursor-pointer"
                     data-id="${c.id}"
                     data-name="${escHtml(c.name)}"
                     data-email="${escHtml(c.email || '')}"
                     data-phone="${escHtml(c.phone || '')}"
                     data-conversations="${c.total_conversations || 0}">
                    <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center bv-icon-circle-32 bg-secondary-subtle text-secondary fw-bold small">
                        ${c.name ? c.name.substring(0, 2).toUpperCase() : '??'}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="mb-0 fw-semibold small text-truncate">${escHtml(c.name)}</p>
                        <small class="text-muted text-truncate d-block">${escHtml(c.email || c.phone || '—')}</small>
                    </div>
                    <small class="text-muted flex-shrink-0">${c.total_conversations} conv.</small>
                </div>
            `).join('<hr class="my-0">');

            $results.html(`<div class="border rounded">${items}</div>`);

            $results.find('.merge-result-item').on('click', function () {
                selectContact($(this).data());
            });
        }

        function selectContact(data) {
            selectedMergeeId = data.id;

            $('#mergeSelectedAvatar').text(data.name.substring(0, 2).toUpperCase());
            $('#mergeSelectedName').text(data.name);
            $('#mergeSelectedEmail').text(data.email || data.phone || '—');
            $('#mergeSelectedMeta').text(`${data.conversations} conversaciones`);

            $results.empty();
            $input.val('').closest('.mb-3').addClass('d-none');
            $selected.removeClass('d-none');
            $confirmBtn.prop('disabled', false);
        }

        $('#btnClearMerge').on('click', function () {
            clearSelection();
        });

        function clearSelection() {
            selectedMergeeId = null;
            $selected.addClass('d-none');
            $input.closest('.mb-3').removeClass('d-none');
            $input.val('');
            $results.empty();
            $confirmBtn.prop('disabled', true);
        }

        $modal.on('hidden.bs.modal', function () {
            clearSelection();
        });

        $confirmBtn.on('click', function () {
            if (! selectedMergeeId) {
                return;
            }

            $confirmBtn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin me-1"></i> Fusionando...');

            $.ajax({
                url: MERGE_URL,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                contentType: 'application/json',
                data: JSON.stringify({
                    base_customer_id: BASE_CUSTOMER_ID,
                    mergee_customer_id: selectedMergeeId,
                }),
                success: function (res) {
                    toastr.success(res.message || 'Contactos fusionados correctamente.');
                    $modal.modal('hide');
                    setTimeout(() => window.location.href = SHOW_URL, 800);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al fusionar los contactos.';
                    toastr.error(msg);
                    $confirmBtn.prop('disabled', false).html('<i class="fas fa-code-merge me-1"></i> Fusionar contactos');

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        toastr.warning(errors);
                    }
                },
            });
        });
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(jQuery);
