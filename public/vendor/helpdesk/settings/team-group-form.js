/**
 * Formulario de grupo de equipo (alta/edicion) —
 * settings/team/group-create.blade.php y settings/team/group-edit.blade.php
 * Requiere: window.TeamGroupFormConfig = { flash: {success, error} }
 *
 * group-create no trae `.select2-priority` / `.priority-select` — las partes que
 * dependen de esos elementos no hacen nada en esa pantalla (no-op seguro).
 */
$(document).ready(function () {
    var cfg = window.TeamGroupFormConfig || {};

    // Initialize Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    // Initialize Select2 for priority dropdowns (solo existe en group-edit)
    function initPrioritySelect2() {
        $('.select2-priority').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    minimumResultsForSearch: Infinity, // Hide search box
                    allowClear: false,
                    dropdownAutoWidth: true,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return 'Sin resultados';
                        }
                    }
                });
            }
        });
    }

    // Initialize on page load
    initPrioritySelect2();

    // Update summary counter
    function updateSummary() {
        const count = $('.member-checkbox:checked').length;
        $('#totalMembers').text(count);
    }

    // Select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.member-checkbox').each(function() {
            $(this).prop('checked', isChecked);
            const priorityCell = $(this).closest('tr').find('.priority-cell');
            priorityCell.toggleClass('d-none', ! isChecked);
        });

        // Reindex all checked members
        if (isChecked) {
            $('.member-checkbox:checked').each(function(index) {
                $(this).attr('name', `members[${index}][user_id]`);
                $(this).siblings('.priority-input, input[type="hidden"]').attr('name', `members[${index}][priority]`);
            });
        } else {
            $('.member-checkbox').each(function() {
                $(this).removeAttr('name');
                $(this).siblings('.priority-input, input[type="hidden"]').removeAttr('name');
            });
        }

        updateSummary();
    });

    // Individual checkbox change
    $('.member-checkbox').on('change', function() {
        const priorityCell = $(this).closest('tr').find('.priority-cell');
        const isChecked = $(this).is(':checked');

        priorityCell.toggle(isChecked);

        // Reindex all checked members
        $('.member-checkbox:checked').each(function(index) {
            $(this).attr('name', `members[${index}][user_id]`);
            $(this).siblings('.priority-input, input[type="hidden"]').attr('name', `members[${index}][priority]`);
        });

        // Remove name attribute from unchecked boxes
        $('.member-checkbox:not(:checked)').each(function() {
            $(this).removeAttr('name');
            $(this).siblings('.priority-input, input[type="hidden"]').removeAttr('name');
        });

        updateSummary();

        // Update select all checkbox state
        const totalCheckboxes = $('.member-checkbox').length;
        const checkedCheckboxes = $('.member-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Priority select change (solo existe en group-edit)
    $('.priority-select').on('change', function() {
        const priorityInput = $(this).closest('tr').find('.priority-input');
        priorityInput.val($(this).val());

        // Update the index for the inputs
        $('.member-checkbox:checked').each(function(index) {
            $(this).attr('name', `members[${index}][user_id]`);
            $(this).closest('tr').find('.priority-input').attr('name', `members[${index}][priority]`);
        });
    });

    // Form validation
    $('#groupForm').on('submit', function(e) {
        const checkedMembers = $('.member-checkbox:checked').length;
        if (checkedMembers === 0) {
            e.preventDefault();
            toastr.error('Debe seleccionar al menos un miembro para el grupo', 'Error de Validación');
            return false;
        }

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> ' + (cfg.submitLoadingText || 'Guardando...'));
    });

    // Initialize summary on page load
    updateSummary();

    HDSettingsCommon.flashToastr(cfg.flash);
});
