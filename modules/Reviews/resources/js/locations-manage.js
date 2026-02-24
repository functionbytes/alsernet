/**
 * Locations Management - Gestión de ubicaciones de Google My Business
 */

$(document).ready(function() {
    // Toggle ubicación activa/inactiva
    $('.toggle-location-active').on('change', function() {
        const checkbox = $(this);
        const locationId = checkbox.data('location-id');
        const isActive = checkbox.is(':checked');

        $.ajax({
            url: `/settings/reviews/locations/${locationId}/toggle`,
            method: 'PATCH',
            data: {
                is_active: isActive ? 1 : 0,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message || 'Estado actualizado');
            },
            error: function(xhr) {
                checkbox.prop('checked', !isActive);
                toastr.error(xhr.responseJSON?.message || 'Error al actualizar estado');
            }
        });
    });

    // Sincronizar ubicación individual
    $('.sync-location').on('click', function() {
        const btn = $(this);
        const locationId = btn.data('location-id');
        const icon = btn.find('i');

        if (btn.prop('disabled')) {
            return;
        }

        btn.prop('disabled', true);
        icon.addClass('fa-spin');

        $.ajax({
            url: `/settings/reviews/locations/${locationId}/sync`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message || 'Sincronización iniciada');
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al sincronizar');
            },
            complete: function() {
                btn.prop('disabled', false);
                icon.removeClass('fa-spin');
            }
        });
    });
});
