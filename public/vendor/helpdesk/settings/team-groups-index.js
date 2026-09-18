/**
 * Listado de grupos de equipo — settings/team/groups.blade.php
 * Requiere: window.TeamGroupsIndexConfig = { flash: {success, error} }
 */
$(document).ready(function() {
    var cfg = window.TeamGroupsIndexConfig || {};

    // Auto-submit search on enter
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            $('#searchForm').submit();
        }
    });

    HDSettingsCommon.flashToastr(cfg.flash);
});
