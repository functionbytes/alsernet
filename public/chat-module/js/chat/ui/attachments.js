(function ($) {
    'use strict';

    /**
     * Abre un adjunto en el modal viewer
     */
    function openViewer(url, filename, mimeType) {
        var config    = window.ChatConfig || {};
        var iconMap   = config.fileIconMap || {};
        var dialog    = document.getElementById('attachmentLightboxDialog');
        var body      = document.getElementById('attachmentLightboxBody');
        var nameEl    = document.getElementById('attachmentLightboxName');
        var iconEl    = document.getElementById('attachmentLightboxIcon');
        var dl        = document.getElementById('attachmentLightboxDownload');

        body.innerHTML      = '';
        body.style.padding  = '0';
        dialog.style.maxWidth = '90vw';

        nameEl.textContent = filename;
        dl.href            = url;
        dl.setAttribute('download', filename);

        var iconInfo = iconMap[mimeType] ||
            (mimeType.indexOf('image/') === 0 ? ['fas fa-image', '#0dcaf0'] : ['fas fa-file', '#6c757d']);
        var icoClass = iconInfo[0];
        var icoColor = iconInfo[1];

        iconEl.innerHTML   = '<i class="' + icoClass + '"></i>';
        iconEl.style.color = icoColor;

        if (mimeType.indexOf('image/') === 0) {
            renderImage(body, url, filename, dialog);
        } else if (mimeType === 'application/pdf') {
            renderPdf(body, url, dialog);
        } else if (mimeType.indexOf('audio/') === 0) {
            renderAudio(body, url, mimeType);
        } else if (mimeType.indexOf('video/') === 0) {
            renderVideo(body, url, mimeType, dialog);
        } else {
            renderDownloadCard(body, filename, icoClass, icoColor);
        }

        new bootstrap.Modal(document.getElementById('attachmentLightboxModal')).show();
    }

    function renderImage(body, url, filename, dialog) {
        body.innerHTML = '';
        var img = document.createElement('img');
        img.style.cssText = 'display:block;max-height:75vh;max-width:90vw;object-fit:contain;';
        img.alt   = filename;
        img.onload = function () {
            var maxW = Math.min(img.naturalWidth + 48, window.innerWidth * 0.9);
            dialog.style.maxWidth = maxW + 'px';
        };
        img.src = url;
        body.appendChild(img);
    }

    var currentIframe = null;

    function renderPdf(body, url, dialog) {
        // Cleanup previous iframe to prevent memory leak
        if (currentIframe) {
            currentIframe.src = 'about:blank';
            currentIframe.remove();
            currentIframe = null;
        }

        body.innerHTML = '';
        currentIframe = document.createElement('iframe');
        currentIframe.src = url + '#toolbar=1&navpanes=0';
        currentIframe.style.cssText = 'width:100%;height:75vh;border:none;display:block;background-color:#ffffff;';
        body.appendChild(currentIframe);
    }

    // Cleanup iframe on modal close
    $(document).ready(function() {
        $('#attachmentLightboxModal').on('hidden.bs.modal', function() {
            if (currentIframe) {
                currentIframe.src = 'about:blank';
                currentIframe.remove();
                currentIframe = null;
            }
        });
    });

    function renderAudio(body, url, mimeType) {
        body.innerHTML = '';
        body.style.padding = '2rem';
        var audio = document.createElement('audio');
        audio.controls    = true;
        audio.style.cssText = 'width:100%;min-width:300px;';
        var src = document.createElement('source');
        src.src  = url;
        src.type = mimeType;
        audio.appendChild(src);
        body.appendChild(audio);
    }

    function renderVideo(body, url, mimeType, dialog) {
        body.innerHTML = '';
        var video = document.createElement('video');
        video.controls    = true;
        video.style.cssText = 'max-width:85vw;max-height:75vh;display:block;';
        var src = document.createElement('source');
        src.src  = url;
        src.type = mimeType;
        video.appendChild(src);
        body.appendChild(video);
    }

    function renderDownloadCard(body, filename, icoClass, icoColor) {
        body.innerHTML = '';
        body.style.padding = '2.5rem 2rem';
        var card    = document.createElement('div');
        card.className = 'text-center w-100';
        var ico = document.createElement('i');
        ico.className  = icoClass + ' fa-4x mb-3 d-block';
        ico.style.color = icoColor;
        var nameDiv = document.createElement('p');
        nameDiv.className   = 'fw-semibold mb-1';
        nameDiv.textContent = filename;
        var hint = document.createElement('p');
        hint.className   = 'text-muted small mb-0';
        hint.textContent = 'Este tipo de archivo no se puede previsualizar. Usa el botón Descargar.';
        card.appendChild(ico);
        card.appendChild(nameDiv);
        card.appendChild(hint);
        body.appendChild(card);
    }

    /**
     * Construye un chip de previsualización para un archivo
     */
    function buildPreviewChip(file, index, convId) {
        var isImage = file.type.indexOf('image/') === 0;
        var chip    = $('<div class="attachment-chip d-flex align-items-center gap-1 border rounded px-2 py-1 bg-light">');

        if (isImage) {
            var img    = $('<img class="attachment-thumb rounded">').attr('alt', file.name);
            var reader = new FileReader();
            reader.onload = function (e) { img.attr('src', e.target.result); };
            reader.readAsDataURL(file);
            chip.append(img);
        } else {
            chip.append('<i class="fas fa-file text-muted"></i>');
        }

        chip.append($('<span class="small text-truncate">').text(file.name));
        chip.append(
            $('<button type="button" class="btn-close btn-close-sm ms-1" aria-label="Quitar">')
                .on('click', function () { removeAttachment(index, convId); })
        );

        return chip;
    }

    /**
     * Remueve un adjunto por índice y re-renderiza las previsualizaciones
     */
    function removeAttachment(index, convId) {
        var fileInput = document.getElementById('file-input-' + convId);
        var dt = new DataTransfer();
        $.each(Array.from(fileInput.files), function (i, file) {
            if (i !== index) { dt.items.add(file); }
        });
        fileInput.files = dt.files;
        renderPreviews(convId);
    }

    /**
     * Renderiza todas las previsualizaciones de adjuntos
     */
    function renderPreviews(convId) {
        var previewZone = $('#attachment-previews-' + convId);
        var fileInput   = document.getElementById('file-input-' + convId);
        previewZone.empty();

        if (!fileInput || !fileInput.files.length) {
            previewZone.addClass('d-none');
            return;
        }

        previewZone.removeClass('d-none');
        $.each(Array.from(fileInput.files), function (index, file) {
            previewZone.append(buildPreviewChip(file, index, convId));
        });
    }

    // Exponer API pública
    window.ChatViewer = {
        openViewer:     openViewer,
        renderPreviews: renderPreviews,
        removeAttachment: removeAttachment
    };

})(jQuery);
