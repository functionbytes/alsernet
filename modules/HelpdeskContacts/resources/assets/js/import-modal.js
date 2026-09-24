/**
 * Modal "Importar CSV" del listado de contactos (contacts/partials/_import-modal.blade.php).
 *
 * Solo hace preview client-side (nombre/tamaño del fichero, columnas
 * detectadas, conteo de filas) antes de un submit de formulario NORMAL
 * (no AJAX) a contacts.import.process — el procesado real del CSV completo
 * (encoding, BOM, comillas) sigue siendo responsabilidad exclusiva del
 * backend (ContactsController::importProcess/importChunk); este preview es
 * best-effort sobre las primeras líneas, no una re-implementación fiel.
 */
(function ($) {
    'use strict';

    // Mismos alias que ContactsController::importProcess() — cabecera ya
    // normalizada (sin tildes, minúsculas, trim) → campo destino legible.
    var ALIASES = {
        name: 'Nombre', nombre: 'Nombre',
        email: 'Email', correo: 'Email',
        phone: 'Teléfono', telefono: 'Teléfono', movil: 'Teléfono',
        whatsapp_phone: 'WhatsApp', whatsapp: 'WhatsApp'
    };

    function esc(str) {
        if (str == null) { return ''; }
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Equivalente JS de Str::ascii(strtolower(trim())): quita diacríticos vía
    // normalización Unicode NFD (misma razón que el comentario en el
    // controlador — "Teléfono" tiene que casar con "telefono").
    function normalizeHeader(h) {
        return String(h || '')
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .trim();
    }

    // Parser CSV mínimo para UNA línea (soporta comillas dobles básicas) —
    // solo para el preview de cabecera, no para procesar el fichero entero.
    function parseCsvLine(line) {
        var out = [];
        var cur = '';
        var inQuotes = false;
        for (var i = 0; i < line.length; i++) {
            var ch = line[i];
            if (ch === '"') {
                inQuotes = !inQuotes;
            } else if (ch === ',' && !inQuotes) {
                out.push(cur);
                cur = '';
            } else {
                cur += ch;
            }
        }
        out.push(cur);
        return out.map(function (v) { return v.replace(/^"|"$/g, '').trim(); });
    }

    function formatBytes(bytes) {
        if (bytes < 1024) { return bytes + ' B'; }
        if (bytes < 1024 * 1024) { return Math.round(bytes / 1024) + ' KB'; }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderColumns(headers) {
        var $list = $('#contact-import-columns-list');
        var html = '';
        headers.forEach(function (raw, idx) {
            var normalized = normalizeHeader(raw);
            var dest = ALIASES[normalized] || null;
            html += '<div class="ct-import-col-row' + (idx > 0 ? ' has-divider' : '') + '">' +
                '<span class="src">' + esc(raw || '(vacío)') + '</span>' +
                (dest
                    ? '<span class="arrow">→</span><span class="dest">' + esc(dest) + '</span>'
                    : '<span class="ignored">se ignora</span>') +
                '</div>';
        });
        $list.html(html);
        $('#contact-import-columns').removeClass('d-none');

        return headers.some(function (raw) { return ALIASES[normalizeHeader(raw)] === 'Nombre' || ALIASES[normalizeHeader(raw)] === 'Email'; });
    }

    function resetPreview() {
        $('#contact-import-file-name').text('Elige un fichero CSV');
        $('#contact-import-file-info').text('nombre, email, teléfono · hasta 5 MB');
        $('#contact-import-file-action').text('Seleccionar fichero');
        $('#contact-import-drop').removeClass('has-file');
        $('#contact-import-note-text').text('Las filas sin nombre ni email, o con un email inválido, se omitirán. Al terminar se muestra el resumen con las filas rechazadas descargables.');
        $('#contact-import-columns').addClass('d-none');
        $('#contact-import-columns-list').empty();
        $('#contact-import-submit').prop('disabled', true).text('Selecciona un archivo');
    }

    $(document).on('change', '#contact-import-file', function () {
        var file = this.files && this.files[0];
        if (!file) { resetPreview(); return; }

        $('#contact-import-drop').addClass('has-file');
        $('#contact-import-file-name').text(file.name);
        $('#contact-import-file-info').text(formatBytes(file.size));
        $('#contact-import-file-action').text('Cambiar fichero');

        // El fichero entero (tope del backend: 5 MB) para contar filas de
        // verdad y cuántas se omitirán — mismo criterio que importChunk():
        // sin nombre ni email, o con email de formato inválido.
        var reader = new FileReader();
        reader.onload = function (e) {
            var text = String(e.target.result || '').replace(/^\uFEFF/, '');
            var lines = text.split(/\r\n|\r|\n/).filter(function (l) { return l.trim() !== ''; });

            if (!lines.length) {
                $('#contact-import-submit').prop('disabled', true).text('CSV vacío');
                return;
            }

            var headers = parseCsvLine(lines[0]);
            var hasUsableColumn = renderColumns(headers);
            var nameIdx = -1;
            var emailIdx = -1;
            headers.forEach(function (h, i) {
                var dest = ALIASES[normalizeHeader(h)];
                if (dest === 'Nombre' && nameIdx === -1) { nameIdx = i; }
                if (dest === 'Email' && emailIdx === -1) { emailIdx = i; }
            });

            var dataRows = lines.length - 1;
            var omitted = 0;
            lines.slice(1).forEach(function (line) {
                var cells = parseCsvLine(line);
                var name = nameIdx > -1 ? (cells[nameIdx] || '').trim() : '';
                var email = emailIdx > -1 ? (cells[emailIdx] || '').trim() : '';
                if ((!name && !email) || (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))) {
                    omitted++;
                }
            });

            $('#contact-import-file-info').text(dataRows + (dataRows === 1 ? ' fila' : ' filas') + ' · ' + formatBytes(file.size));

            if (!hasUsableColumn) {
                $('#contact-import-submit').prop('disabled', true).text('Falta columna de nombre o email');
                return;
            }

            $('#contact-import-note-text').text(
                (omitted ? (omitted + (omitted === 1 ? ' fila sin nombre ni email válido se omitirá. ' : ' filas sin nombre ni email válido se omitirán. ')) : 'Ninguna fila se omitirá por falta de datos. ') +
                'Al terminar se muestra el resumen con las filas rechazadas descargables.'
            );

            var toImport = dataRows - omitted;
            $('#contact-import-submit').prop('disabled', toImport <= 0)
                .text('Importar ' + toImport + (toImport === 1 ? ' contacto' : ' contactos'));
        };
        reader.readAsText(file);
    });

    $(document).on('click', '#contact-import-template-btn', function () {
        var csv = 'nombre,email,telefono,whatsapp_phone\n'
            + 'Ejemplo Uno,ejemplo1@correo.com,600111222,34600111222\n'
            + 'Ejemplo Dos,ejemplo2@correo.com,600333444,\n';
        var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'plantilla-contactos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    $(document).on('hidden.bs.modal', '#contact-import-modal', function () {
        $('#contact-import-form')[0].reset();
        resetPreview();
    });
})(window.jQuery);
