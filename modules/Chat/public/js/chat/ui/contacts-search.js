(function ($) {
    'use strict';

    var searchTimeout = null;

    function init() {
        $(document).on('input', '#contact_search', function () {
            handleSearchInput($(this));
        });

        $(document).on('click', '.contact-result', function () {
            selectContact($(this));
        });

        $(document).on('click', '#remove_contact', function () {
            removeSelectedContact();
        });

        $('#newConversationModal').on('hidden.bs.modal', function () {
            resetModal();
        });
    }

    function handleSearchInput($input) {
        var query = $input.val().trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $('#contact_search_results').hide();
            return;
        }

        searchTimeout = setTimeout(function () {
            fetchContacts(query);
        }, 300);
    }

    function fetchContacts(query) {
        $.get('/helpdesk/conversations/search-contacts', { q: query })
            .done(function (contacts) {
                var $results = $('#contact_search_results').empty();

                if (!contacts.length) {
                    $results.append($('<div class="list-group-item text-muted">').text('No se encontraron contactos')).show();
                    return;
                }

                $.each(contacts, function (i, c) {
                    var label    = c.email || c.phone_number || 'Sin datos';
                    var $btn     = $('<button type="button" class="list-group-item list-group-item-action contact-result">');
                    var $strong  = $('<strong>').text(c.name);
                    var $small   = $('<small class="text-muted">').text(label);

                    $btn.data('id', c.id).data('name', c.name).data('email', label);
                    $btn.append($strong, $('<br>'), $small);
                    $results.append($btn);
                });

                $results.show();
            })
            .fail(function (xhr) {
                var msg = 'Error al buscar contactos';
                if (xhr.status === 0) {
                    msg = 'Sin conexión. Comprueba tu red e inténtalo de nuevo.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                $('#contact_search_results')
                    .empty()
                    .append($('<div class="list-group-item text-danger">').text(msg))
                    .show();

                if (window.toastr) {
                    toastr.error(msg);
                }

                console.error('Contact search failed:', xhr);
            });
    }

    function selectContact($element) {
        $('#selected_contact_id').val($element.data('id'));
        $('#selected_contact_name').text($element.data('name'));
        $('#selected_contact_email').text($element.data('email'));
        $('#contact_search').hide();
        $('#contact_search_results').hide();
        $('#selected_contact').show();
    }

    function removeSelectedContact() {
        $('#selected_contact_id').val('');
        $('#contact_search').val('').show();
        $('#selected_contact').hide();
    }

    function resetModal() {
        var form = document.getElementById('new-conversation-form');
        if (form) { form.reset(); }
        $('#selected_contact_id').val('');
        $('#contact_search').show();
        $('#contact_search_results').hide();
        $('#selected_contact').hide();
    }

    $(function () { init(); });

})(jQuery);
