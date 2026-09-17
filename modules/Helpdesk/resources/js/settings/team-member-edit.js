/**
 * Edicion de miembro del equipo — settings/team/member-edit.blade.php
 * Requiere: window.TeamMemberEditConfig = { flash: {success, error} }
 */
$(document).ready(function() {
    var cfg = window.TeamMemberEditConfig || {};

    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: function() {
            return $(this).find('option:first').text();
        },
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Availability change handler
    $('#availabilitySelect').on('change', function() {
        if ($(this).val() === 'working_hours') {
            $('#workingHoursSection').slideDown(300);
        } else {
            $('#workingHoursSection').slideUp(300);
        }
    });

    // Day checkbox handler
    $('.day-checkbox').on('change', function() {
        const row = $(this).closest('tr');
        const timeInputs = row.find('input[type="time"]');
        const isChecked = $(this).is(':checked');

        timeInputs.prop('disabled', !isChecked);

        if (isChecked) {
            row.addClass('table-success');
        } else {
            row.removeClass('table-success');
        }
    });

    // Group checkbox handler
    $('.group-checkbox').on('change', function() {
        const card = $(this).closest('.group-card');
        const priorityToggle = card.find('.priority-toggle');
        const isChecked = $(this).is(':checked');

        if (isChecked) {
            priorityToggle.slideDown(200);
            card.addClass('border-primary').removeClass('border');
        } else {
            priorityToggle.slideUp(200);
            card.removeClass('border-primary').addClass('border');
        }

        // Update counter
        updateGroupCounter();
    });

    // Update group counter
    function updateGroupCounter() {
        const selectedCount = $('.group-checkbox:checked').length;
        $('#selectedGroupsCount').text(selectedCount);
    }

    HDSettingsCommon.flashToastr(cfg.flash);
});
