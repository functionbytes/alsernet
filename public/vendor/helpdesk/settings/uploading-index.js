/**
 * Configuracion de subida de archivos — settings/uploading/index.blade.php
 * Requiere: window.UploadingIndexConfig = { flash: {success, error} }
 */
$(document).ready(function () {
    var cfg = window.UploadingIndexConfig || {};

    $('.select2').select2({ width: '100%' });
    $('.select2-tags').select2({ width: '100%', tags: true, tokenSeparators: [',', ' '] });

    $('#enable_image_compression').on('change', function () {
        $('#compression-fields').toggleClass('d-none', !this.checked);
    });

    $('#max_file_size_mb').on('input', function () {
        var mb = parseInt($(this).val() || 0);
        $('#fileSizeHelp').text('Tamaño maximo permitido por archivo (' + mb + ' MB = ' + (mb * 1024) + ' KB)');
    });

    $('#image_quality').on('input', function () {
        var q = parseInt($(this).val() || 0);
        var label = q >= 90 ? 'alta calidad, archivo mayor' : q >= 70 ? 'equilibrio calidad/tamaño' : 'menor calidad, archivo mas pequeño';
        $('#qualityHelp').text(q + '% — ' + label);
    });

    HDSettingsCommon.flashToastr(cfg.flash);
});
