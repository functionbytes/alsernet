/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Parte de un split de public/vendor/helpdesk/conversations.js (7.138 líneas)
 * en varios archivos por responsabilidad. Ver conversations-core.js para los
 * helpers compartidos (openModal/closeModal/escapeHtml/refreshInboxList/...)
 * expuestos en window para que el resto de archivos los usen tal cual.
 */

(function ($) {
    'use strict';

    $(function () {
        // ─── Browser notification permission ─────────────────────────
        if (window.Notification && Notification.permission === 'default') {
            $(document).one('click', function () {
                Notification.requestPermission();
            });
        }


        // ─── Dropzone overlay (drag de archivos sobre todo el thread) ─
        let dragCounter = 0;
        function showDropOverlay() {
            if (document.getElementById('bv-drop-overlay')) return;
            const $body = $('.bv-th-body');
            if (!$body.length) return;
            $body.css('position', 'relative').append(
                '<div id="bv-drop-overlay" class="bv-drop-overlay">' +
                    '<i class="fas fa-cloud-arrow-up bv-drop-overlay-icon"></i>' +
                    '<div class="bv-drop-overlay-text">Suelta los archivos aquí</div>' +
                    '<div class="bv-drop-overlay-sub">Hasta 16 MB por archivo · imágenes, video, audio o documentos</div>' +
                '</div>'
            );
        }
        function hideDropOverlay() {
            $('#bv-drop-overlay').remove();
        }

        $(window).on('dragenter', function (e) {
            if (e.originalEvent?.dataTransfer?.types?.includes?.('Files')) {
                dragCounter++;
                showDropOverlay();
            }
        });
        $(window).on('dragleave', function () {
            dragCounter = Math.max(0, dragCounter - 1);
            if (dragCounter === 0) hideDropOverlay();
        });
        $(window).on('dragover', function (e) { e.preventDefault(); });
        $(window).on('drop', function (e) {
            dragCounter = 0;
            hideDropOverlay();
            const files = e.originalEvent?.dataTransfer?.files;
            if (!files || !files.length) return;
            // Si el drop fue sobre el thread (no el composer), igualmente subir
            const $target = $(e.target);
            if (!$target.closest('.bv-composer-input, .bv-composer-box').length) {
                e.preventDefault();
                if (typeof uploadFiles === 'function') uploadFiles(files);
            }
        });


        // ─── More menu toggle ─────────────────────────────────────────
        $(document).on('click', '#bv-btn-more', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-more-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
            }
        });

        // ─── Sort dropdown toggle ─────────────────────────────────────
        $(document).on('click', '#bv-btn-sort', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-sort-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                // Posicionar el menú (position:fixed) bajo el botón, alineado a la derecha
                const rect = this.getBoundingClientRect();
                const menuWidth = 220;
                let left = rect.right - menuWidth;
                if (left < 8) left = 8;
                if (left + menuWidth > window.innerWidth - 8) {
                    left = window.innerWidth - menuWidth - 8;
                }
                $menu.css({
                    top: (rect.bottom + 6) + 'px',
                    left: left + 'px',
                });
                $menu.addClass('on');
                $(this).attr('aria-expanded', 'true');
            }
        });

        $(document).on('click', '.bv-sort-opt', function (e) {
            e.stopPropagation();
            const sort = $(this).data('sort');
            $('.bv-sort-opt').removeClass('on').attr('aria-checked', 'false');
            $(this).addClass('on').attr('aria-checked', 'true');
            closeAllMenus();
            applyInboxFilters({ sort: sort === 'newest' ? null : sort });
        });

        // ─── Attach menu toggle ───────────────────────────────────────
        $(document).on('click', '#bv-btn-attach', function (e) {
            e.stopPropagation();
            const $menu = $('#bv-attach-menu');
            const wasOpen = $menu.hasClass('on');
            closeAllMenus();
            if (!wasOpen) {
                $menu.addClass('on');
            }
        });

        // closeAllMenus() ahora vive en conversations-core.js (ver comentario allí).

        // ─── Notification permission toggle button ───────────────────
        // Inject button if there's a topbar/header to host it
        function ensureNotifBtn() {
            if (document.getElementById('bv-toggle-notifications')) return;
            // Find topbar bell icon container
            const $bell = $('.bv-topbtn .fa-bell, .topbar .fa-bell').first().closest('button');
            if (!$bell.length) {
                // Fallback: prepend to thread head actions
                const $head = $('.bv-th-head .actions').first();
                if ($head.length) {
                    $head.prepend(
                        '<button class="bv-th-action" id="bv-toggle-notifications" title="Activar notificaciones">' +
                            '<i class="far fa-bell"></i>' +
                            '<span class="bad bv-hidden">!</span>' +
                        '</button>'
                    );
                }
                return;
            }
            $bell.attr('id', 'bv-toggle-notifications');
        }
        ensureNotifBtn();

        function updateNotifBtn() {
            const $btn = $('#bv-toggle-notifications');
            if (!$btn.length || typeof Notification === 'undefined') return;
            $btn.removeClass('granted denied default');
            $btn.addClass(Notification.permission || 'default');
            const titleMap = {
                granted: 'Notificaciones activadas',
                denied: 'Notificaciones bloqueadas — clic para ayuda',
                default: 'Activar notificaciones',
            };
            $btn.attr('title', titleMap[Notification.permission] || titleMap.default);
            // Switch icon to "bell" when granted, "bell-slash" when denied
            const $icon = $btn.find('i').first();
            if ($icon.length) {
                $icon.removeClass('fa-bell fa-bell-slash');
                $icon.addClass(Notification.permission === 'denied' ? 'fa-bell-slash' : 'fa-bell');
            }
        }
        updateNotifBtn();

        $(document).on('click', '#bv-toggle-notifications', async function (e) {
            if (typeof Notification === 'undefined') return;
            if (Notification.permission === 'granted') {
                return;
            }
            if (Notification.permission === 'denied') {
                e.preventDefault();
                e.stopPropagation();
                showNotifDeniedHelp();
                return;
            }
            const result = await Notification.requestPermission();
            updateNotifBtn();
            if (result === 'granted') {
                new Notification('🔔 Notificaciones activadas', { body: 'Recibirás alertas de nuevos mensajes' });
            } else if (result === 'denied') {
                showNotifDeniedHelp();
            }
        });

        function showNotifDeniedHelp() {
            $('#bv-notif-denied').remove();
            const $modal = $(
                '<div id="bv-notif-denied" class="bv-mic-denied-overlay">' +
                    '<div class="bv-mic-denied-card">' +
                        '<div class="bv-mic-denied-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-bell-slash"></i></div>' +
                        '<div class="bv-mic-denied-title">Notificaciones bloqueadas</div>' +
                        '<div class="bv-mic-denied-body">' +
                            'Para recibir alertas de nuevos mensajes en tiempo real:' +
                            '<ol class="bv-mic-denied-steps">' +
                                '<li>Haz clic en el icono <strong>🔒</strong> de la barra de direcciones</li>' +
                                '<li>Busca <strong>Notificaciones</strong></li>' +
                                '<li>Cambia a <strong>Permitir</strong></li>' +
                                '<li>Recarga la página</li>' +
                            '</ol>' +
                        '</div>' +
                        '<div class="bv-mic-denied-actions">' +
                            '<button class="bv-mic-denied-btn" id="bv-notif-retry"><i class="fas fa-bell"></i> Reintentar permiso</button>' +
                            '<button class="bv-mic-denied-btn-secondary" id="bv-notif-close">Entendido</button>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($modal);
        }

        $(document).on('click', '#bv-notif-close', function () {
            $('#bv-notif-denied').remove();
        });

        $(document).on('click', '#bv-notif-retry', async function () {
            try {
                const result = await Notification.requestPermission();
                if (result === 'granted') {
                    $('#bv-notif-denied').remove();
                    updateNotifBtn();
                } else {
                    if (window.toastr) toastr.error('Sigue bloqueado. Usa el icono 🔒 de la URL para activarlo.', '', { timeOut: 8000 });
                }
            } catch (e) {
                if (window.toastr) toastr.error('Error al pedir permiso');
            }
        });

        // ─── Lightbox de imágenes (estilo WhatsApp) ─────────────────
        const lightbox = {
            list: [],   // [{src, name, author, time}]
            idx: 0,
            zoom: 1,
            rot: 0,
            tx: 0, ty: 0,         // pan offset
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            dragOriginTx: 0,
            dragOriginTy: 0,
        };

        function filenameFromUrl(url) {
            try {
                const path = new URL(url, window.location.origin).pathname;
                return decodeURIComponent(path.split('/').pop() || 'archivo');
            } catch (e) {
                return decodeURIComponent((url || '').split('?')[0].split('/').pop() || 'archivo');
            }
        }

        function collectThreadImages($currentLink) {
            const list = [];
            $('.bv-th-inner .bv-attach-thumb').each(function () {
                const $a = $(this);
                const src = $a.attr('href') || $a.data('bv-preview-src');
                if (!src) return;
                const $bubble = $a.closest('.bv-bubble');
                const author = $bubble.data('bv-author') || 'Mensaje';
                const time = $bubble.find('.meta span').first().text() || '';
                // Prefer the original filename stored in data-bv-name (set from
                // attachment metadata). Fall back to the URL path as last resort.
                const dataName = $a.data('bv-name') || $a.find('img').attr('alt');
                const name = (typeof dataName === 'string' && dataName.trim() !== '')
                    ? dataName
                    : filenameFromUrl(src);
                list.push({ src: src, name: name, author, time });
            });
            const startIdx = Math.max(0, list.findIndex(x => x.src === ($currentLink.attr('href') || $currentLink.data('bv-preview-src'))));
            return { list, startIdx };
        }

        function applyLightboxTransform() {
            const $img = $('#bv-lightbox-img');
            // Pan solo tiene efecto cuando hay zoom > 1; al zoom out se resetea automáticamente
            if (lightbox.zoom <= 1) {
                lightbox.tx = 0;
                lightbox.ty = 0;
            }
            $img.css('transform',
                'translate(' + lightbox.tx + 'px, ' + lightbox.ty + 'px) ' +
                'scale(' + lightbox.zoom + ') ' +
                'rotate(' + lightbox.rot + 'deg)'
            );
            // Cursor según el estado: zoom-in cuando 1x, grab cuando hay zoom (grabbing al arrastrar)
            const cursor = lightbox.isDragging
                ? 'grabbing'
                : (lightbox.zoom > 1 ? 'grab' : 'zoom-in');
            $img.css('cursor', cursor);
        }

        const LB_DOC_ICONS = {
            // PDF
            pdf:  { cls: 'fas fa-file-pdf',        color: '#dc2626' },
            // Word / text
            doc:  { cls: 'fas fa-file-word',        color: '#2563eb' },
            docx: { cls: 'fas fa-file-word',        color: '#2563eb' },
            rtf:  { cls: 'fas fa-file-word',        color: '#3b82f6' },
            odt:  { cls: 'fas fa-file-word',        color: '#3b82f6' },
            // Excel / data
            xls:  { cls: 'fas fa-file-excel',       color: '#059669' },
            xlsx: { cls: 'fas fa-file-excel',       color: '#059669' },
            ods:  { cls: 'fas fa-file-excel',       color: '#059669' },
            csv:  { cls: 'fas fa-file-csv',         color: '#059669' },
            // PowerPoint
            ppt:  { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            pptx: { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            odp:  { cls: 'fas fa-file-powerpoint',  color: '#e67000' },
            // Archives
            zip:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            rar:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            '7z': { cls: 'fas fa-file-zipper',      color: '#71717a' },
            gz:   { cls: 'fas fa-file-zipper',      color: '#71717a' },
            tar:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            bz2:  { cls: 'fas fa-file-zipper',      color: '#71717a' },
            // Text / plain
            txt:  { cls: 'fas fa-file-lines',       color: '#71717a' },
            md:   { cls: 'fas fa-file-lines',       color: '#71717a' },
            // Code / markup
            json: { cls: 'fas fa-file-code',        color: '#f59e0b' },
            xml:  { cls: 'fas fa-file-code',        color: '#f59e0b' },
            html: { cls: 'fas fa-file-code',        color: '#f97316' },
            htm:  { cls: 'fas fa-file-code',        color: '#f97316' },
            css:  { cls: 'fas fa-file-code',        color: '#06b6d4' },
            js:   { cls: 'fas fa-file-code',        color: '#eab308' },
            php:  { cls: 'fas fa-file-code',        color: '#8b5cf6' },
            // Image variants (when shown as doc)
            svg:  { cls: 'fas fa-file-image',       color: '#10b981' },
            bmp:  { cls: 'fas fa-file-image',       color: '#71717a' },
            tiff: { cls: 'fas fa-file-image',       color: '#71717a' },
            tif:  { cls: 'fas fa-file-image',       color: '#71717a' },
            // Audio (fallback)
            mp3:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            wav:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            ogg:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            oga:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            m4a:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            aac:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            flac: { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            opus: { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            wma:  { cls: 'fas fa-file-audio',       color: '#7c3aed' },
            // Video (fallback)
            avi:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            mkv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            flv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            wmv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            ogv:  { cls: 'fas fa-file-video',       color: '#ef4444' },
            '3gp':{ cls: 'fas fa-file-video',       color: '#ef4444' },
        };

        function renderLightbox() {
            const item = lightbox.list[lightbox.idx];
            if (!item) return;

            const $img   = $('#bv-lightbox-img');
            const $video = $('#bv-lightbox-video');
            const $audio = $('#bv-lightbox-audio');
            const $doc   = $('#bv-lightbox-doc');

            // Reset all elements
            $img.hide().removeClass('bv-lb-error');
            if ($video.length) { $video[0].pause(); $video.hide(); $video[0].removeAttribute('src'); }
            if ($audio.length) { $audio[0].pause(); $audio.hide(); $audio[0].removeAttribute('src'); }
            $doc.hide();

            // Toggle zoom/rotate controls (only for images)
            const isImage = item.type === 'image';
            $('#bv-lightbox-zoom-in, #bv-lightbox-zoom-out, #bv-lightbox-rotate').toggle(isImage);

            if (item.type === 'video') {
                $video.attr('src', item.src).show();
                $video[0].load();
            } else if (item.type === 'audio') {
                $audio.attr('src', item.src).show();
                $audio[0].load();
            } else if (isImage) {
                $img.attr('src', item.src).attr('alt', item.name || '').show();
                lightbox.zoom = 1; lightbox.rot = 0; lightbox.tx = 0; lightbox.ty = 0;
                applyLightboxTransform();
            } else {
                // document / unknown
                const meta = LB_DOC_ICONS[item.ext] || { cls: 'fas fa-file', color: '#71717a' };
                $doc.find('.bv-lb-doc-icon i').attr('class', meta.cls).css('color', meta.color);
                $doc.find('.bv-lb-doc-name').text(item.name);
                const sizeStr = item.size > 0
                    ? (item.size < 1048576 ? Math.round(item.size / 1024) + ' KB' : (item.size / 1048576).toFixed(1) + ' MB')
                    : '';
                $doc.find('.bv-lb-doc-size').text(sizeStr);
                $doc.show();
            }

            $('#bv-lightbox-author').text(item.author);
            $('#bv-lightbox-sub').text(item.time);
            $('#bv-lightbox-counter').text((lightbox.idx + 1) + ' / ' + lightbox.list.length);
            renderLightboxStrip();
            const showNav = lightbox.list.length > 1;
            $('#bv-lightbox-prev, #bv-lightbox-next').toggle(showNav);
        }

        function renderLightboxStrip() {
            const $strip = $('#bv-lightbox-strip').empty();
            if (lightbox.list.length <= 1) { $strip.hide(); return; }
            $strip.show();
            lightbox.list.forEach((item, i) => {
                const active = i === lightbox.idx ? ' on' : '';
                let inner;
                if (item.type === 'image') {
                    inner = '<img src="' + item.src + '" alt="">';
                } else if (item.type === 'video') {
                    inner = '<i class="fas fa-video"></i>';
                } else if (item.type === 'audio') {
                    inner = '<i class="fas fa-volume-high"></i>';
                } else {
                    const meta = LB_DOC_ICONS[item.ext] || { cls: 'fas fa-file' };
                    inner = '<i class="' + meta.cls + '"></i>';
                }
                const $thumb = $('<button type="button" class="bv-lightbox-thumb' + active + '" data-idx="' + i + '">' + inner + '</button>');
                $strip.append($thumb);
            });
        }

        function openLightboxFromLink($a) {
            const { list, startIdx } = collectThreadImages($a);
            if (!list.length) return;
            lightbox.list = list;
            lightbox.idx = startIdx;
            renderLightbox();
            const $modal = $('[data-bv-modal-name="file-preview"]');
            $modal.addClass('on');
            $('body').css('overflow', 'hidden');
        }

        // Intercepta el click sobre miniaturas — sustituye al modal genérico
        $(document).on('click', '.bv-attach-thumb', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openLightboxFromLink($(this));
        });

        $(document).on('click', '#bv-lightbox-prev', function () {
            lightbox.idx = (lightbox.idx - 1 + lightbox.list.length) % lightbox.list.length;
            renderLightbox();
        });
        $(document).on('click', '#bv-lightbox-next', function () {
            lightbox.idx = (lightbox.idx + 1) % lightbox.list.length;
            renderLightbox();
        });
        $(document).on('click', '.bv-lightbox-thumb', function () {
            lightbox.idx = parseInt($(this).data('idx'), 10) || 0;
            renderLightbox();
        });
        function zoomLightbox(delta, anchorX, anchorY) {
            const $img = $('#bv-lightbox-img');
            if (!$img.length) return;
            const prevZoom = lightbox.zoom;
            const newZoom = Math.max(0.5, Math.min(6, prevZoom + delta));
            if (newZoom === prevZoom) return;

            // Si nos pasaron coords, hacer zoom centrado en el cursor
            if (typeof anchorX === 'number' && typeof anchorY === 'number') {
                const rect = $img[0].getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                // Vector cursor → centro del img actual
                const dx = anchorX - cx;
                const dy = anchorY - cy;
                const ratio = newZoom / prevZoom;
                // Mantén el punto bajo el cursor estable: ajusta tx/ty
                lightbox.tx = (lightbox.tx - dx) * ratio + dx;
                lightbox.ty = (lightbox.ty - dy) * ratio + dy;
            }
            lightbox.zoom = newZoom;
            applyLightboxTransform();
        }

        $(document).on('click', '#bv-lightbox-zoom-in', function () {
            zoomLightbox(0.25);
        });
        $(document).on('click', '#bv-lightbox-zoom-out', function () {
            zoomLightbox(-0.25);
        });
        $(document).on('click', '#bv-lightbox-rotate', function () {
            lightbox.rot = (lightbox.rot + 90) % 360;
            applyLightboxTransform();
        });

        // ─── Zoom con scroll del mouse ───────────────────────────────
        $(document).on('wheel', '.bv-lightbox-stage', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            e.preventDefault();
            const native = e.originalEvent;
            const delta = native.deltaY < 0 ? 0.2 : -0.2;
            zoomLightbox(delta, native.clientX, native.clientY);
        });

        // ─── Pan con drag (cuando zoom > 1) ──────────────────────────
        $(document).on('mousedown', '#bv-lightbox-img, .bv-lightbox-stage', function (e) {
            if (e.button !== 0) return; // solo botón principal
            if (lightbox.zoom <= 1) return;
            e.preventDefault();
            lightbox.isDragging = true;
            lightbox.dragStartX = e.clientX;
            lightbox.dragStartY = e.clientY;
            lightbox.dragOriginTx = lightbox.tx;
            lightbox.dragOriginTy = lightbox.ty;
            applyLightboxTransform();
        });
        $(document).on('mousemove', function (e) {
            if (!lightbox.isDragging) return;
            const dx = e.clientX - lightbox.dragStartX;
            const dy = e.clientY - lightbox.dragStartY;
            lightbox.tx = lightbox.dragOriginTx + dx;
            lightbox.ty = lightbox.dragOriginTy + dy;
            applyLightboxTransform();
        });
        $(document).on('mouseup mouseleave', function () {
            if (!lightbox.isDragging) return;
            lightbox.isDragging = false;
            applyLightboxTransform();
        });

        // ─── Doble click toggle 1x ↔ 2x ─────────────────────────────
        $(document).on('dblclick', '#bv-lightbox-img, .bv-lightbox-stage', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            e.preventDefault();
            if (lightbox.zoom > 1) {
                lightbox.zoom = 1;
                lightbox.tx = 0; lightbox.ty = 0;
                applyLightboxTransform();
            } else {
                zoomLightbox(1, e.clientX, e.clientY); // de 1 → 2
            }
        });
        $(document).on('click', '#bv-lightbox-open', function () {
            const item = lightbox.list[lightbox.idx];
            if (item) window.open(item.src, '_blank');
        });
        // Mapa MIME → extensión para fallback cuando el nombre no la tiene
        const MIME_EXT = {
            'image/png': 'png', 'image/jpeg': 'jpg', 'image/jpg': 'jpg',
            'image/gif': 'gif', 'image/webp': 'webp', 'image/svg+xml': 'svg',
            'image/heic': 'heic', 'image/avif': 'avif',
            'audio/webm': 'webm', 'audio/mpeg': 'mp3', 'audio/ogg': 'ogg',
            'audio/wav': 'wav', 'audio/x-m4a': 'm4a',
            'video/mp4': 'mp4', 'video/webm': 'webm', 'video/quicktime': 'mov',
            'application/pdf': 'pdf',
        };

        function ensureExtensionInName(name, blob) {
            if (!blob) return name || 'archivo';
            const hasExt = /\.[a-z0-9]{2,5}$/i.test(name || '');
            if (hasExt) return name;
            const ext = MIME_EXT[blob.type] || (blob.type.split('/')[1] || '').replace(/[^a-z0-9]/gi, '');
            const base = (name || 'archivo').replace(/\.+$/, '');
            return ext ? base + '.' + ext : base;
        }

        // Descarga forzada vía endpoint server con Content-Disposition.
        // Funciona en cualquier navegador (incluido Playwright/CDP).
        function downloadBlob(url, suggestedName) {
            const endpoint = '/panel/helpdesk/api/attachment-download?url=' + encodeURIComponent(url);
            const a = document.createElement('a');
            a.href = endpoint;
            a.download = suggestedName || filenameFromUrl(url);
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            return true;
        }

        $(document).on('click', '#bv-lightbox-download, #bv-lb-doc-dl', function () {
            const item = lightbox.list[lightbox.idx];
            if (!item) return;
            downloadBlob(item.src, item.name || filenameFromUrl(item.src));
        });

        // Teclado: ←→ navegan, ESC cierra
        $(document).on('keydown', function (e) {
            const $modal = $('[data-bv-modal-name="file-preview"]');
            if (!$modal.hasClass('on')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); $('#bv-lightbox-prev').click(); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); $('#bv-lightbox-next').click(); }
            else if (e.key === '+' || e.key === '=') $('#bv-lightbox-zoom-in').click();
            else if (e.key === '-') $('#bv-lightbox-zoom-out').click();
        });

        // Inbox initialized

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 1: Atajos adicionales
        // - R sin modificador → enfocar compositor (ya activa tab reply)
        // - /  sin foco en input → enfocar #bv-search-input
        // ═══════════════════════════════════════════════════════════════
        // El handler de teclado existente (línea ~590) ya cubre J/K, ?, Esc,
        // R (reply tab) y la G-sequence. Extendemos el switch existente
        // para que R también enfoque el input del compositor, y agregamos /
        // como atajo de búsqueda de lista cuando no hay foco en un campo.
        //
        // NOTA: El handler existente ya guarda e.key / key = e.key.toLowerCase().
        // Añadimos un segundo handler secundario que SÓLO actúa cuando el
        // foco NO está en un campo de texto, complementando sin reemplazar.
        $(document).on('keydown.bv-extras', function (e) {
            if ($(e.target).is('input, textarea, [contenteditable]')) return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;

            // / → enfocar búsqueda de conversaciones
            if (e.key === '/') {
                e.preventDefault();
                const $s = $('#bv-search-input');
                if ($s.length) {
                    $s.focus().select();
                }
                return;
            }

            // R → activar tab reply + enfocar compositor
            if (e.key.toLowerCase() === 'r') {
                const $tab = $('.bv-composer-tab[data-bv-tab="reply"]');
                if ($tab.length) {
                    $tab.click();
                    setTimeout(function () { $('.bv-composer-input').first().focus(); }, 50);
                }
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 2: Sonido al llegar mensaje nuevo
        // ═══════════════════════════════════════════════════════════════
        var BvSound = (function () {
            var STORAGE_KEY = 'bv:sound:enabled';

            function isEnabled() {
                var v = localStorage.getItem(STORAGE_KEY);
                return v === null ? true : v === '1';
            }

            function setEnabled(on) {
                localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
            }

            function playBeep() {
                if (!isEnabled()) return;
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    gain.gain.setValueAtTime(0.18, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.35);
                    osc.onended = function () { ctx.close(); };
                } catch (err) {
                    // AudioContext not available (e.g. server-side rendering)
                }
            }

            function applyUI() {
                var on = isEnabled();
                $('#bv-sound-toggle').find('i')
                    .toggleClass('fa-volume-up', on)
                    .toggleClass('fa-volume-mute', !on);
                $('#bv-sound-toggle').attr('title', on ? 'Silenciar notificaciones' : 'Activar notificaciones de sonido');
            }

            function init() {
                // Inyectar botón en la barra de estado (statusbar)
                var $sb = $('.bv-statusbar .spacer').first();
                if ($sb.length) {
                    $sb.before(
                        '<button id="bv-sound-toggle" class="sb-item sb-btn" style="background:none;border:none;cursor:pointer;padding:0 4px;color:inherit;">' +
                        '<i class="fas fa-volume-up"></i></button><span class="sep">│</span>'
                    );
                }
                applyUI();

                $(document).on('click', '#bv-sound-toggle', function () {
                    setEnabled(!isEnabled());
                    applyUI();
                });
            }

            return { init: init, playBeep: playBeep, isEnabled: isEnabled };
        })();
        BvSound.init();

        // Escuchar mensajes entrantes del cliente y reproducir beep
        window.addEventListener('inbox:incoming-message', function (ev) {
            var msg = ev.detail || {};
            var isCustomer = !msg.user_id && msg.author_id;
            if (!isCustomer) return;
            // ¿El agente está viendo ESTA conversación?
            var selectedId = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10);
            var msgConvId  = parseInt(msg.conversation_id || '0', 10);
            var isViewing  = msgConvId && msgConvId === selectedId;
            if (!isViewing) {
                BvSound.playBeep();
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 3: Badge favicon con conteo de no-leídos
        // ═══════════════════════════════════════════════════════════════
        var BvFavicon = (function () {
            var count = 0;
            var originalHref = null;
            var $link = null;

            function getLink() {
                if ($link && $link.length) return $link;
                $link = $('link[rel~="icon"]').first();
                if (!$link.length) {
                    $link = $('<link rel="icon" type="image/png">');
                    $('head').append($link);
                }
                return $link;
            }

            function drawBadge(src, n, cb) {
                var img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width  = 32;
                    canvas.height = 32;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, 32, 32);
                    if (n > 0) {
                        var label = n > 99 ? '99+' : String(n);
                        var r = label.length > 1 ? 10 : 8;
                        var cx = 32 - r, cy = r;
                        ctx.beginPath();
                        ctx.arc(cx, cy, r, 0, 2 * Math.PI);
                        ctx.fillStyle = '#90bb13';
                        ctx.fill();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold ' + (label.length > 1 ? '9' : '11') + 'px Arial';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, cx, cy);
                    }
                    cb(canvas.toDataURL('image/png'));
                };
                img.onerror = function () { cb(src); };
                img.src = src;
            }

            function update(n) {
                count = Math.max(0, n);
                var $l = getLink();
                if (!originalHref) {
                    originalHref = $l.attr('href') || '/favicon.ico';
                }
                drawBadge(originalHref, count, function (dataUrl) {
                    $l.attr('href', dataUrl);
                });
            }

            function increment() { update(count + 1); }
            function decrement() { update(count - 1); }
            function reset()     { update(0); }

            function initCount() {
                var n = $('.bv-conv.unread').length;
                if (n > 0) update(n);
            }

            return { init: initCount, increment: increment, decrement: decrement, reset: reset, update: update };
        })();
        BvFavicon.init();

        // Incrementar cuando llega mensaje entrante y no se está viendo
        window.addEventListener('inbox:incoming-message', function (ev) {
            var msg = ev.detail || {};
            var isCustomer = !msg.user_id && msg.author_id;
            if (!isCustomer) return;
            var selectedId = parseInt(new URLSearchParams(window.location.search).get('selected') || '0', 10);
            var msgConvId  = parseInt(msg.conversation_id || '0', 10);
            if (!msgConvId || msgConvId !== selectedId) {
                BvFavicon.increment();
            }
        });

        // Decrementar al abrir conversación (limpiar badge de esa conv)
        $(document).on('click', '.bv-conv', function () {
            var $item = $(this);
            var hadUnread = $item.hasClass('unread') || $item.find('.bv-ucount:not(:empty)').length > 0;
            if (hadUnread) {
                BvFavicon.decrement();
            }
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE 4: Modo concentración
        // ═══════════════════════════════════════════════════════════════

        // Inyectar estilos del modo concentración
        (function injectFocusModeStyles() {
            if (document.getElementById('bv-focus-mode-styles')) return;
            var css = [
                'body.bv-focus-mode .bv-list { display: none !important; }',
                'body.bv-focus-mode .bv-right { display: none !important; }',
                // Detect actual grid: keep nav sidebar visible, expand thread, hide list + right.
                'body.bv-focus-mode .conversations { grid-template-columns: var(--bv-nav-w, 240px) 1fr !important; }',
                'body.bv-focus-mode .bv-thread { max-width: 820px; margin: 0 auto; width: 100%; }',
                '#bv-focus-timer {',
                '  display: none;',
                '  position: absolute;',
                '  top: 0; left: 0; right: 0;',
                '  background: rgba(177,1,0,0.07);',
                '  border-bottom: 2px solid rgba(177,1,0,0.18);',
                '  text-align: center;',
                '  font-size: 13px;',
                '  font-weight: 600;',
                '  color: #90bb13;',
                '  padding: 5px 12px;',
                '  letter-spacing: 0.5px;',
                '  z-index: 10;',
                '}',
                'body.bv-focus-mode #bv-focus-timer { display: block; }',
                'body.bv-focus-mode .bv-thread { position: relative; }',
            ].join('\n');
            var $style = $('<style id="bv-focus-mode-styles">').text(css);
            $('head').append($style);
        })();

        // Inyectar botón de modo concentración en la cabecera del hilo
        function injectFocusModeButton() {
            if ($('#bv-focus-btn').length) return;
            var $sep = $('.bv-th-head .actions .bv-th-sep').first();
            if ($sep.length) {
                $sep.before(
                    '<button class="bv-th-action" id="bv-focus-btn" title="Modo concentración" aria-label="Modo concentración">' +
                    '<i class="fas fa-expand"></i></button>'
                );
            }
        }
        injectFocusModeButton();

        // Timer SLA: calcula cuánto tiempo lleva abierta la conversación
        var focusTimerInterval = null;
        function startFocusTimer() {
            if ($('#bv-focus-timer').length === 0) {
                $('.bv-thread').prepend('<div id="bv-focus-timer"></div>');
            }
            var $thread = $('.bv-thread');
            var createdAtStr = $thread.find('[data-bv-conv-created]').first().data('bv-conv-created') ||
                               $thread.attr('data-bv-conv-created');
            var createdAt = createdAtStr ? new Date(createdAtStr) : null;

            function updateTimer() {
                var now = new Date();
                var diffMs = createdAt ? (now - createdAt) : 0;
                var mins = Math.floor(diffMs / 60000);
                var hrs  = Math.floor(mins / 60);
                var label;
                if (!createdAt) {
                    label = 'Modo concentración activo';
                } else if (hrs > 0) {
                    label = 'Conversación abierta hace ' + hrs + 'h ' + (mins % 60) + 'm';
                } else {
                    label = 'Conversación abierta hace ' + mins + ' min';
                }
                $('#bv-focus-timer').text(label);
            }

            updateTimer();
            focusTimerInterval = setInterval(updateTimer, 30000);
        }

        function stopFocusTimer() {
            clearInterval(focusTimerInterval);
            focusTimerInterval = null;
            $('#bv-focus-timer').remove();
        }

        function isFocusMode() { return $('body').hasClass('bv-focus-mode'); }

        function enableFocusMode() {
            $('body').addClass('bv-focus-mode');
            $('#bv-focus-btn i').removeClass('fa-expand').addClass('fa-compress');
            $('#bv-focus-btn').attr('title', 'Salir del modo concentración');
            startFocusTimer();
        }

        function disableFocusMode() {
            $('body').removeClass('bv-focus-mode');
            $('#bv-focus-btn i').removeClass('fa-compress').addClass('fa-expand');
            $('#bv-focus-btn').attr('title', 'Modo concentración');
            stopFocusTimer();
        }

        $(document).on('click', '#bv-focus-btn', function () {
            if (isFocusMode()) {
                disableFocusMode();
            } else {
                enableFocusMode();
            }
        });

        // Esc también desactiva el modo concentración (el handler de Esc existente
        // cierra modales; lo extendemos aquí sólo cuando no hay modal abierto)
        $(document).on('keydown.bv-focus', function (e) {
            if (e.key !== 'Escape') return;
            if ($('.bv-modal.on').length) return; // ya lo maneja el handler anterior
            if (isFocusMode()) {
                disableFocusMode();
            }
        });

        // Re-inyectar botón si el thread se recarga (AJAX)
        $(document).on('bv:thread:loaded', function () {
            injectFocusModeButton();
        });

        // ═══════════════════════════════════════════════════════════════
        // FEATURE: Acceso rápido (atajos) + Cambiar color blanco/negro
        // ═══════════════════════════════════════════════════════════════
        // Modo oscuro retirado: el inbox se muestra siempre en claro. Se conserva
        // el botón de acceso rápido a los atajos de teclado.
        var BvTheme = (function () {
            function init() {
                localStorage.removeItem('bv:theme:dark');
                document.documentElement.removeAttribute('data-bv-dark');
                $('.conversations').first().attr('data-theme', 'light');

                var $sb = $('.bv-statusbar .spacer').first();
                if ($sb.length) {
                    $sb.before(
                        '<button id="bv-quick-access" class="bv-sb-btn" title="Acceso rápido — Atajos de teclado" aria-label="Acceso rápido">' +
                        '<i class="fas fa-keyboard"></i></button><span class="sep">│</span>'
                    );
                }

                $(document).on('click', '#bv-quick-access', function () {
                    openModal('shortcuts');
                });
            }

            return { init: init };
        })();
        BvTheme.init();


        // Exponer helpers compartidos con conversations-core.js / conversations-thread.js /
        // conversations-panel.js (se cargan antes que este archivo).
        window.downloadBlob = downloadBlob;
        window.renderLightbox = renderLightbox;
        window.openLightboxFromLink = openLightboxFromLink;

    });

    // Clase de pill de estado a partir del texto (compartida PS/ERP)
    window.bvOrderStatusClass = function (text) {
        var t = String(text != null ? text : '').toLowerCase();
        if (t.indexOf('entreg') >= 0 || t.indexOf('pago acept') >= 0 || t.indexOf('complet') >= 0 || t.indexOf('pagad') >= 0) { return 'is-completed'; }
        if (t.indexOf('envi') >= 0 || t.indexOf('ship') >= 0 || t.indexOf('tránsito') >= 0 || t.indexOf('transito') >= 0) { return 'is-shipped'; }
        if (t.indexOf('cancel') >= 0 || t.indexOf('reembols') >= 0 || t.indexOf('anulad') >= 0) { return 'is-cancelled'; }
        return 'is-pending';
    };

    // Helper: renderiza un bloque de dirección como HTML
    function renderAddrBlock(addr) {
        if (!addr || typeof addr !== 'object') { return null; }
        var e = function (s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        };
        var parts = [];
        var name = [addr.firstname, addr.lastname].filter(Boolean).join(' ');
        if (name) { parts.push('<strong>' + e(name) + '</strong>'); }
        if (addr.company) { parts.push(e(addr.company)); }
        if (addr.address1) { parts.push(e(addr.address1)); }
        if (addr.address2) { parts.push(e(addr.address2)); }
        var cityLine = [addr.postcode, addr.city].filter(Boolean).join(' ');
        if (cityLine) { parts.push(e(cityLine)); }
        if (addr.state && addr.state !== addr.city) { parts.push(e(addr.state)); }
        if (addr.country) { parts.push(e(addr.country)); }
        if (!parts.length) { return null; }
        var html = '<div class="bv-om-addr-lines">' + parts.join('<br>') + '</div>';
        var phone = addr.phone || addr.phone_mobile || null;
        if (phone) { html += '<span class="bv-addr-phone"><i class="fas fa-phone"></i> ' + e(phone) + '</span>'; }
        return html;
    }

    // Helper: rellena el panel Dirección del modal (shipping, billing opcional)
    // Mini-API para el módulo HelpdeskErp (motor ERP extraído a erp-inbox.js).
    // setAddressTab puebla el modal de detalle de pedido compartido (ERP + PrestaShop).
    window.HDInbox = window.HDInbox || {};
    window.HDInbox.setAddressTab = function (shipping, billing) { return setAddressTab(shipping, billing); };

    function setAddressTab(shipping, billing) {
        var addr = shipping && typeof shipping === 'object' ? shipping : null;
        var hasData = !!(addr && (addr.address1 || addr.city || addr.country || addr.firstname));
        var $grid = $('#bv-order-modal-addr-grid');

        $grid.toggleClass('bv-hidden', !hasData);
        $('#bv-order-modal-addr-empty').toggleClass('bv-hidden', hasData);

        if (!hasData) { return; }

        // Set address type label
        var billAddr = billing && typeof billing === 'object' ? billing : null;
        var hasBill = !!(billAddr && (billAddr.address1 || billAddr.city));
        var addrLabel, addrIcon;
        if (!hasBill) {
            addrLabel = 'Dirección de envío';
            addrIcon  = 'fas fa-truck';
        } else {
            var sameAddr = billing.address1 === shipping.address1 && billing.city === shipping.city;
            if (sameAddr) {
                addrLabel = 'Dirección combinada (envío y facturación)';
                addrIcon  = 'fas fa-location-dot';
            } else {
                addrLabel = 'Dirección de envío';
                addrIcon  = 'fas fa-truck';
            }
        }
        $('#bv-om-addr-type-text').text(addrLabel);
        $('#bv-om-addr-type-icon').attr('class', addrIcon);

        // Reset all rows
        $grid.find('.lbl, .val').addClass('bv-hidden').removeClass('bv-last-row');

        function showRow(lblId, valId, val) {
            var has = !!(val && String(val).trim());
            $('#' + lblId).toggleClass('bv-hidden', !has);
            $('#' + valId).toggleClass('bv-hidden', !has);
            if (has) { $('#' + valId).text(String(val)); }
        }

        var name = [addr.firstname, addr.lastname].filter(Boolean).join(' ');
        showRow('bv-om-ship-name-lbl', 'bv-om-ship-name', name);
        showRow('bv-om-ship-company-lbl', 'bv-om-ship-company', addr.company);
        showRow('bv-om-ship-addr1-lbl', 'bv-om-ship-addr1', addr.address1);
        showRow('bv-om-ship-addr2-lbl', 'bv-om-ship-addr2', addr.address2);
        var city = [addr.postcode, addr.city].filter(Boolean).join(' ');
        showRow('bv-om-ship-city-lbl', 'bv-om-ship-city', city);
        var state = addr.state && addr.state !== addr.city ? addr.state : '';
        showRow('bv-om-ship-state-lbl', 'bv-om-ship-state', state);
        showRow('bv-om-ship-country-lbl', 'bv-om-ship-country', addr.country);
        var phone = addr.phone || addr.phone_mobile || '';
        showRow('bv-om-ship-phone-lbl', 'bv-om-ship-phone', phone);

        // Mark last visible row to remove its border-bottom
        $grid.find('.lbl:not(.bv-hidden)').last().addClass('bv-last-row');
        $grid.find('.val:not(.bv-hidden)').last().addClass('bv-last-row');
    }

    // ─── Order detail modal (#37 ve-order-view) — datos embebidos en la tarjeta ───
    $(document).on('click', '.rp3-order[data-bv-modal="order"]', function () {
        const $el = $(this).closest('.rp3-order[data-bv-modal="order"]');
        const ref = $el.data('order-ref') || '—';
        const status = $el.data('order-status') || '—';
        const date = $el.data('order-date') || '—';
        const total = $el.data('order-total') || '—';
        const productsJson = $el.attr('data-order-products');
        let products = [];
        try { products = JSON.parse(productsJson); } catch (e) { }
        const url = $el.data('order-url') || '';
        const platform = $el.data('order-platform') || '';
        const payment = $el.data('order-payment') || '—';

        $('#bv-order-modal-chip').text(ref);
        $('#bv-order-modal-date').text(date);
        $('#bv-order-modal-status')
            .text(status)
            .attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(status));
        $('#bv-order-modal-payment').text(payment);
        $('#bv-order-modal-total').text(total + ' €');
        $('#bv-order-modal-products-count').text(products.length ? products.length + (products.length === 1 ? ' artículo' : ' artículos') : '');

        var subtotal = 0;
        products.forEach(function (p) {
            subtotal += (parseFloat(p.price) || 0) * (parseInt(p.qty) || 1);
        });
        $('#bv-order-modal-subtotal').text(subtotal.toFixed(2).replace('.', ',') + ' €');
        $('#bv-order-modal-shipping-val').text('—');
        $('#bv-order-modal-tax-row').addClass('bv-hidden');
        $('#bv-order-modal-discount-row').addClass('bv-hidden');

        var productsHtml = '';
        if (products.length === 0) {
            productsHtml = '<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos</div></div>';
        } else {
            products.forEach(function (p) {
                var unitPrice = parseFloat(p.price) || 0;
                var qty       = parseInt(p.qty) || 1;
                var lineTotal = unitPrice * qty;
                var meta = '\xd7' + qty + (unitPrice > 0 ? ' \xb7 €\xa0' + unitPrice.toFixed(2) : '');
                productsHtml +=
                    '<div class="bv-om-prod-card">' +
                        '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                        '<div class="bv-om-pc-info">' +
                            '<div class="bv-om-pc-name">' + escapeHtml(p.name || 'Producto') + '</div>' +
                            '<div class="bv-om-pc-meta">' + meta + '</div>' +
                        '</div>' +
                        '<div class="bv-om-pc-price">€\xa0' + lineTotal.toFixed(2) + '</div>' +
                    '</div>';
            });
        }
        $('#bv-order-modal-products').html(productsHtml);
        $('#bv-order-modal-tracking-field').addClass('bv-hidden');
        setAddressTab(null, null);

        var custName = ($('.bv-cp-name-btn').first().text() || $('.bv-right-name').first().text() || '—').trim();
        $('#bv-order-modal-cust-name').text(custName);

        var $link = $('#bv-order-modal-external-link');
        if (url) {
            $link.attr('href', url).removeClass('bv-hidden');
            if (platform === 'prestashop') {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en PrestaShop');
            } else if (platform === 'erp') {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Ver en ERP');
            } else {
                $link.html('<i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en tienda');
            }
        } else {
            $link.addClass('bv-hidden');
        }
    });




    // El modal "Escalar a ticket" (crear ticket desde la conversación) vivía
    // aquí, ~230 líneas dentro del bundle del core. Ahora es de HelpdeskTickets:
    // modules/HelpdeskTickets/resources/js/inbox-create-ticket.js, cargado por
    // su propio slot. Con el módulo apagado ya no se sirve.

    // ─── Ticket detail modal ──────────────────────────────────────────────
    //
    // El selector era '.rp3-ticket[data-bv-modal="ticket"]'. Esa clase dejó de
    // existir cuando el tab de tickets del panel derecho se rehízo con
    // '.tk-card' (right-panel-tickets-tab.blade.php), así que este handler no
    // llegaba a dispararse NUNCA: el modal abría por el atributo genérico
    // data-bv-modal, la petición no salía y la ficha se quedaba con los
    // guiones de la plantilla — el "T-—" y los "—" que se veían en pantalla.
    // Se aceptan las dos clases: la vieja por si algún módulo satélite sigue
    // pintando tarjetas con ella.
    $(document).on('click', '.tk-card[data-bv-modal="ticket"], .rp3-ticket[data-bv-modal="ticket"]', function () {
        var $el = $(this);
        var ticketId = $el.data('ticket-id');
        if (!ticketId) return;

        var convId = $('.bv-composer').data('bv-conversation-id')
            || $('.bv-conv.on').data('bv-conv-id')
            || new URLSearchParams(window.location.search).get('selected');

        // Estado de carga con lo que ya se sabe por la tarjeta pulsada, para
        // que la cabecera no parpadee en blanco mientras llega la respuesta.
        $('#bv-ticket-modal-num').text($el.find('.id').text() || '');
        $('#bv-ticket-modal-title').text($el.find('.title').text() || 'Cargando…');
        $('#bv-ticket-modal-desc').text('Cargando…');
        $('#bv-ticket-modal-pills').empty();
        $('#bv-ticket-modal-grid').empty();
        $('#bv-ticket-modal-sla, #bv-ticket-modal-tags, #bv-ticket-modal-convo, #bv-ticket-modal-erp').prop('hidden', true);
        $('#bv-ticket-modal-lastmove-wrap, #bv-ticket-modal-activity-wrap').prop('hidden', true);

        if (!convId) {
            $('#bv-ticket-modal-desc').text('No se pudo cargar el detalle del ticket.');
            return;
        }

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ticket-detail/' + ticketId,
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            if (!resp || !resp.ticket) return;
            renderTicketModal(resp.ticket);
        }).fail(function () {
            $('#bv-ticket-modal-desc').text('No se pudo cargar el detalle del ticket.');
        });
    });

    var TICKET_PRIORITY_LABEL = { urgent: 'Urgente', high: 'Alta', normal: 'Normal', low: 'Baja' };
    // Los mismos cuatro colores que la barra de las tarjetas del panel
    // (.tk-card.prio-*), para que el chip y la tarjeta no digan cosas distintas.
    var TICKET_PRIORITY_COLOR = { urgent: '#5e7a0d', high: '#f59e0b', normal: '#3b82f6', low: '#10b981' };

    function ticketInitials(name) {
        return String(name || '')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(function (w) { return w.charAt(0); })
            .join('')
            .toUpperCase() || '—';
    }

    function renderTicketModal(t) {
        var priority = t.priority || 'normal';

        $('#bv-ticket-modal-num').text(t.ticket_number || '');
        $('#bv-ticket-modal-title').text(t.subject || 'Sin título');

        // 1 · Chips de situación
        $('#bv-ticket-modal-pills').html(
            '<span class="bv-tkm-pill status"><span class="d" style="background:' + escapeHtml(t.status.color || '#71717a') + '"></span>' + escapeHtml(t.status.name) + '</span>' +
            '<span class="bv-tkm-pill"><span class="d" style="background:' + (TICKET_PRIORITY_COLOR[priority] || '#3b82f6') + '"></span>' + escapeHtml(TICKET_PRIORITY_LABEL[priority] || priority) + '</span>' +
            (t.category ? '<span class="bv-tkm-pill">' + escapeHtml(t.category) + '</span>' : '')
        );

        // 2 · SLA
        var $sla = $('#bv-ticket-modal-sla');
        if (t.sla) {
            $sla.attr('data-kind', t.sla.kind || 'ok').prop('hidden', false);
            $('#bv-ticket-modal-sla-label').text(t.sla.label);
            $('#bv-ticket-modal-sla-sub').text(
                [t.sla.policy, t.sla.first_response].filter(Boolean).join(' · ')
            );
        } else {
            $sla.prop('hidden', true);
        }

        // 3 · Cliente
        var cust = t.customer || {};
        $('#bv-ticket-modal-avatar').text(cust.initials || ticketInitials(cust.name));
        $('#bv-ticket-modal-side-name').text(cust.name || '—');
        $('#bv-ticket-modal-side-meta').text(cust.email || '');
        $('#bv-ticket-modal-customer').attr('href', cust.url || '#').toggleClass('is-static', !cust.url);
        $('#bv-ticket-modal-erp').text(cust.erp_id ? 'ERP ' + cust.erp_id : '').prop('hidden', !cust.erp_id);

        // 4 · Descripción — el botón de desplegar solo aparece si hay algo que
        // desplegar (se mide sobre el nodo ya pintado, no sobre el texto).
        var $desc = $('#bv-ticket-modal-desc').removeClass('is-open').text(t.description || 'Sin descripción.');
        var $more = $('#bv-ticket-modal-desc-more');
        $more.prop('hidden', $desc[0].scrollHeight <= $desc[0].clientHeight + 2);

        // 5 · Último movimiento
        if (t.last_move) {
            $('#bv-ticket-modal-lastmove-wrap').prop('hidden', false);
            $('#bv-ticket-modal-lastmove-av').text(ticketInitials(t.last_move.author));
            $('#bv-ticket-modal-lastmove-text').text(t.last_move.excerpt || '—');
            $('#bv-ticket-modal-lastmove-meta').text(
                [
                    t.last_move.author + (t.last_move.is_customer ? ' · cliente' : ''),
                    t.last_move.when,
                    t.last_move.mail_status ? 'correo ' + t.last_move.mail_status : null
                ].filter(Boolean).join(' · ')
            );
            $('#bv-ticket-modal-msgcount').text(t.items_count ? t.items_count + (t.items_count === 1 ? ' mensaje' : ' mensajes') : '');
        } else {
            $('#bv-ticket-modal-lastmove-wrap').prop('hidden', true);
        }

        // 6 · Detalles. Una fila sin valor no se pinta: la ficha de antes
        // enseñaba siete filas con "—" en casi todas.
        var rows = [
            ['Asignado', t.assignee || 'Sin asignar'],
            ['Equipo', t.group],
            ['Origen', t.source_label],
            ['Pedido', t.order_ref],
            ['Creado', t.created_at],
            ['Actualizado', t.updated_at]
        ].filter(function (r) { return r[1]; });

        $('#bv-ticket-modal-grid').html(rows.map(function (r) {
            return '<span class="k">' + escapeHtml(r[0]) + '</span><span class="v">' + escapeHtml(String(r[1])) + '</span>';
        }).join(''));

        // 7 · Etiquetas
        var tags = t.tags || [];
        $('#bv-ticket-modal-tags')
            .html(tags.map(function (tag) { return '<span class="bv-tkm-tag">' + escapeHtml(tag) + '</span>'; }).join(''))
            .prop('hidden', !tags.length);

        // 8 · Conversación de origen
        if (t.conversation) {
            $('#bv-ticket-modal-convo').prop('hidden', false).data('convo-id', t.conversation.id);
            $('#bv-ticket-modal-convo-meta').text(
                [t.conversation.channel, t.conversation.status, '#' + t.conversation.id].filter(Boolean).join(' · ')
            );
        } else {
            $('#bv-ticket-modal-convo').prop('hidden', true);
        }

        // 9 · Actividad real
        var activity = t.activity || [];
        if (activity.length) {
            $('#bv-ticket-modal-activity-wrap').prop('hidden', false);
            $('#bv-ticket-modal-activity').html(activity.map(function (ev, i) {
                var last = i === activity.length - 1;
                return '<div class="bv-tkm-tl-item' + (last ? ' last' : '') + '">' +
                    '<span class="dot' + (ev.is_customer ? ' customer' : '') + '"></span>' +
                    '<div class="body">' +
                        '<span class="who">' + escapeHtml(ev.who) + '</span>' +
                        (ev.excerpt ? '<span class="what">' + escapeHtml(ev.excerpt) + '</span>' : '') +
                    '</div>' +
                    '<span class="when">' + escapeHtml(ev.when || '') + '</span>' +
                '</div>';
            }).join(''));
        } else {
            $('#bv-ticket-modal-activity-wrap').prop('hidden', true);
        }

        // Pie
        $('#bv-ticket-modal-open').attr('href', t.url || '#');
        $('#bv-ticket-modal-resolve').data('ticket-id', t.id);
        $('#bv-ticket-modal-assign').data('ticket-id', t.id);
    }

    // Desplegar/plegar la descripción
    $(document).on('click', '#bv-ticket-modal-desc-more', function () {
        var $desc = $('#bv-ticket-modal-desc').toggleClass('is-open');
        $(this).text($desc.hasClass('is-open') ? 'Ver menos' : 'Ver descripción completa');
    });

    // La conversación de origen abre la bandeja en esa conversación.
    $(document).on('click', '#bv-ticket-modal-convo', function () {
        var id = $(this).data('convo-id');
        if (id) window.location = '/panel/helpdesk/conversations?selected=' + id;
    });

    // Resolver y auto-asignarse. El botón "Resolver ticket" llevaba desde la
    // primera versión del modal sin nada detrás: ni handler aquí ni endpoint
    // que aceptara la llamada.
    function ticketModalAction(action, $btn) {
        var ticketId = $btn.data('ticket-id');
        var convId = $('.bv-composer').data('bv-conversation-id')
            || $('.bv-conv.on').data('bv-conv-id')
            || new URLSearchParams(window.location.search).get('selected');
        if (!ticketId || !convId) return;

        var label = $btn.text();
        $btn.prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ticket-detail/' + ticketId + '/action',
            method: 'POST',
            data: { action: action },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            if (window.toastr) toastr.success((resp && resp.message) || 'Hecho');
            $('[data-bv-modal-name="ticket"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
            // El panel derecho enseña el estado y el asignado del ticket: tras
            // cambiarlos hay que repintarlo, o seguiría diciendo lo de antes.
            if (typeof window.bvRefreshTicketsPanel === 'function') window.bvRefreshTicketsPanel();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo completar la acción.';
            if (window.toastr) toastr.error(msg); else alert(msg);
        }).always(function () {
            $btn.prop('disabled', false).text(label);
        });
    }

    $(document).on('click', '#bv-ticket-modal-resolve', function () { ticketModalAction('resolve', $(this)); });
    $(document).on('click', '#bv-ticket-modal-assign', function () { ticketModalAction('assign_me', $(this)); });

    // ─── Cargar datos de Remarketing en pestaña Carritos ───────────────────────
    var remarketingOrdersLoaded = {};
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab="carts"]').length) { return; }
        var customerId = $('.bv-right').data('customer-id');
        if (!customerId || remarketingOrdersLoaded[customerId]) return;
        var $tab = $('[data-bv-tab-content="carts"]');
        var $container = $tab.find('.rp3-scroll');
        if (!$container.length) {
            $container = $('<div class="rp3-scroll"></div>').appendTo($tab);
        }
        $container.prepend('<div id="bv-remarketing-loading" style="padding:12px;text-align:center;color:#9aa0ab;font-size:12px"><i class="fas fa-circle-notch fa-spin"></i> Cargando pedidos…</div>');
        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/ecommerce',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            $('#bv-remarketing-loading').remove();
            if (!resp || !resp.success) return;
            var html = '';
            if (resp.orders && resp.orders.length) {
                html += '<div class="rp3-section"><div class="rp3-sec-head">Historial de pedidos <span class="count">· ' + resp.orders.length + '</span></div>';
                resp.orders.forEach(function (o) {
                    var statusColor = 'var(--warning)';
                    var s = (o.status || '').toLowerCase();
                    if (s === 'completed' || s === 'complete' || s === 'entregado') statusColor = 'var(--success)';
                    else if (s === 'shipped' || s === 'enviado') statusColor = 'var(--info)';
                    else if (s === 'cancelled' || s === 'cancelado') statusColor = 'var(--danger)';
                    var dateStr = o.placed_at_human || '—';
                    var itemsHtml = '';
                    if (o.items && o.items.length) {
                        itemsHtml = '<div style="margin-top:6px;font-size:12px;color:#6c757d;">' + o.items.map(function(i){ return i.title + ' ×' + i.quantity; }).join(', ') + '</div>';
                    }
                    html += '<div class="rp3-order" style="cursor:pointer">' +
                        '<div class="thumb"><i class="fa-solid fa-box"></i></div>' +
                        '<div class="body">' +
                            '<div class="id">#' + escapeHtml(o.order_number || o.id) + '</div>' +
                            '<div class="t">' + (o.items_count || 0) + ' producto' + (o.items_count === 1 ? '' : 's') + '</div>' +
                            '<div class="meta">' +
                                '<b>' + (o.total ? o.total.toFixed(2).replace('.', ',') : '0,00') + ' ' + (o.currency || '€') + '</b> ' +
                                '<span style="color:' + statusColor + ';font-weight:600;">●</span> <span>' + escapeHtml(o.status || 'Pendiente') + '</span> <span>·</span> <span>' + escapeHtml(dateStr) + '</span>' +
                            '</div>' + itemsHtml +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';
            }
            if (resp.carts && resp.carts.length) {
                resp.carts.forEach(function (c) {
                    var itemCount = c.items_count || 0;
                    html += '<div class="rp3-section"><div class="rp3-sec-head">Carrito abandonado</div>' +
                        '<div class="rp3-cart"><div class="hd"><span class="dot"></span><i class="fa-solid fa-cart-shopping"></i> ' + itemCount + ' item' + (itemCount === 1 ? '' : 's') + '</div>' +
                        '<div class="rp3-cart-items">';
                    (c.items || []).forEach(function (it) {
                        html += '<div class="rp3-cart-item"><div class="th"></div><div class="n">' + escapeHtml(it.title || 'Producto') + '</div><div class="p">' + (it.price ? it.price.toFixed(2).replace('.', ',') : '0,00') + ' €</div></div>';
                    });
                    html += '</div></div></div>';
                });
            }
            if (html) {
                $container.prepend(html);
            }
            remarketingOrdersLoaded[customerId] = true;
        }).fail(function () {
            $('#bv-remarketing-loading').remove();
        });
    }, true);

    // ─── Service Worker (PWA) ─────────────────────────────────────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.warn('[BV] SW registration failed:', err);
        });
    }

    // ─── Customer Data Lookup (multi-source: PS + ERP) ───────────────────────
    (function () {
        var $aside = $('.bv-right');
        if (!$aside.length || (!$aside.data('has-ps') && !$aside.data('has-erp'))) return;

        var lookupUrl = $aside.data('lookup-url');
        var csrf      = $aside.data('csrf');
        var inboxId   = $aside.data('inbox-id');
        var lookupEmail  = $aside.data('lookup-email') || null;
        var lookupPsId   = $aside.data('lookup-ps-id') || null;
        var lookupErpId  = $aside.data('lookup-erp-id') || null;
        if (!lookupEmail && !lookupPsId && !lookupErpId) return;

        // Cache de tabs ya cargados — evita peticiones duplicadas
        var loaded = {};

        // Los tabs PrestaShop (ps-*) y ERP (erp-*) ahora los aportan los módulos
        // HelpdeskPrestashop / HelpdeskErp como inbox-slots (render server-side).
        // El lazy-load de Engagement queda desactivado para no sobreescribirlos.
        var tabConfig = {};

        function esc(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function emptyState(icon, title, sub) {
            return '<div class="bv-tab-empty">' +
                '<i class="fas fa-' + icon + '"></i>' +
                '<div class="bv-tab-empty-title">' + esc(title) + '</div>' +
                '<div class="bv-tab-empty-sub">' + esc(sub) + '</div>' +
                '</div>';
        }

        function fetchAndRender(tabName, force) {
            var cfg = tabConfig[tabName];
            if (!cfg) return;
            if (!force && loaded[tabName]) return;

            var $target = $(cfg.target);
            if (!$target.length) return;

            $target.html('<div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');

            $.ajax({
                url: lookupUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                data: {
                    inbox_id: inboxId,
                    lookup: {
                        email: lookupEmail,
                        external_id: cfg.platform === 'erp' ? lookupErpId : lookupPsId,
                    },
                    actions: cfg.actions,
                    platform: cfg.platform,
                    force: force ? 1 : 0,
                },
                success: function (resp) {
                    if (!resp.success) {
                        $target.html(emptyState('triangle-exclamation', 'Error al cargar', ''));
                        return;
                    }
                    var platformData = ((resp.data || {})[cfg.platform]) || {};
                    $target.html(cfg.renderer(platformData));
                    loaded[tabName] = true;
                },
                error: function () {
                    if (window.toastr) toastr.error('No se pudieron cargar los datos');
                    $target.html(emptyState('triangle-exclamation', 'Error de red', ''));
                },
            });
        }

        function renderPsOrders(d) {
            var o = d.orders;
            if (!o || !o.ok || !o.data || !o.data.orders || !o.data.orders.length) {
                return emptyState('bag-shopping', 'Sin pedidos', 'No hay pedidos registrados en PrestaShop');
            }
            // PS state_id → CSS color var (2=Pago aceptado, 3=Preparando, 4=Enviado, 5=Entregado, 6=Cancelado, 7=Reembolsado)
            var stateColorMap = {
                2: 'var(--bv-info, #1d4ed8)',
                3: 'var(--bv-warning, #b45309)',
                4: 'var(--bv-info, #1d4ed8)',
                5: 'var(--bv-success, #0d7a3f)',
                6: 'var(--bv-primary-strong, #5e7a0d)',
                7: 'var(--bv-warning, #b45309)'
            };
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Pedidos <span class="count">· ' + o.data.orders.length + '</span></div>';
            o.data.orders.forEach(function (ord) {
                var stLabel = ord.state_name || '—';
                var stColor = stateColorMap[ord.state_id] || 'var(--bv-text-muted, #8c929d)';
                var dateStr = (ord.created_at || ord.date_add || '').substring(0, 10);
                var ref     = ord.reference || ('#' + ord.id);
                html += '<div class="rp3-order"' +
                    ' data-order-id="' + esc(ord.id) + '"' +
                    ' data-order-platform="prestashop"' +
                    ' data-order-ref="' + esc(ref) + '">' +
                    '<div class="thumb"><i class="fas fa-box"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(ref) + '</div>' +
                        '<div class="meta">' +
                            '<b>' + esc(parseFloat(ord.total || 0).toFixed(2)) + ' €</b>' +
                            '<span style="color:' + stColor + ';font-weight:600;">●</span>' +
                            '<span>' + esc(stLabel) + '</span>' +
                            (dateStr ? '<span>·</span><span>' + esc(dateStr) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsReturns(d) {
            var r = d.returns;
            if (!r || !r.ok || !r.data || !r.data.returns || !r.data.returns.length) {
                return emptyState('rotate-left', 'Sin devoluciones', 'No hay devoluciones registradas en PrestaShop');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Devoluciones <span class="count">· ' + r.data.returns.length + '</span></div>';
            r.data.returns.forEach(function (ret) {
                html += '<div class="rp3-order">' +
                    '<div class="thumb"><i class="fas fa-rotate-left"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">#' + esc(ret.order_reference || ret.id) + '</div>' +
                        '<div class="t">' + esc(ret.reason || 'Devolución') + '</div>' +
                        '<div class="meta"><b>' + (ret.items ? ret.items.length : 0) + ' item(s)</b> · <span>' + esc(ret.state_name || '') + '</span> · <span>' + esc((ret.created_at || '').substring(0, 10)) + '</span></div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsVouchers(d) {
            var v = d.vouchers;
            if (!v || !v.ok || !v.data || !v.data.vouchers || !v.data.vouchers.length) {
                return emptyState('tag', 'Sin cupones', 'Este cliente no tiene cupones asignados');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Cupones <span class="count">· ' + v.data.vouchers.length + '</span></div>';
            v.data.vouchers.forEach(function (vo) {
                var reduction = vo.reduction_percent > 0 ? (vo.reduction_percent + '%') :
                    vo.reduction_amount > 0 ? (parseFloat(vo.reduction_amount).toFixed(2) + ' €') :
                    vo.free_shipping ? 'Envío gratis' : '—';
                html += '<div class="rp3-order' + (vo.expired ? ' is-expired' : '') + '">' +
                    '<div class="thumb"><i class="fas fa-tag"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(vo.code || '—') + '</div>' +
                        '<div class="t">' + esc(vo.description || '') + '</div>' +
                        '<div class="meta"><b>' + reduction + '</b>' +
                            (vo.date_to ? ' · hasta ' + esc(String(vo.date_to).substring(0, 10)) : '') +
                            (vo.expired ? ' · <span class="text-muted">expirado</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }

        function renderPsAddresses(d) {
            var a = d.addresses;
            if (!a || !a.ok || !a.data || !a.data.addresses || !a.data.addresses.length) {
                return emptyState('location-dot', 'Sin direcciones', 'No hay direcciones registradas');
            }
            var html = '<div class="rp3-scroll"><div class="rp3-section">';
            html += '<div class="rp3-sec-head">Direcciones <span class="count">· ' + a.data.addresses.length + '</span></div>';
            a.data.addresses.forEach(function (ad) {
                html += '<div class="rp3-order">' +
                    '<div class="thumb"><i class="fas fa-location-dot"></i></div>' +
                    '<div class="body">' +
                        '<div class="id">' + esc(ad.alias || ad.id) + '</div>' +
                        '<div class="t">' + esc((ad.firstname || '') + ' ' + (ad.lastname || '')) + '</div>' +
                        '<div class="meta">' + esc(ad.address || '') + ' · ' + esc(ad.postcode || '') + ' ' + esc(ad.city || '') + ' · ' + esc(ad.country || '') + '</div>' +
                        (ad.phone ? '<div class="meta"><i class="fas fa-phone"></i> ' + esc(ad.phone) + '</div>' : '') +
                    '</div>' +
                '</div>';
            });
            html += '</div></div>';
            return html;
        }


        // Lazy-load al hacer clic en un tab de integración
        $(document).on('click', '[data-bv-tab]', function () {
            var tab = $(this).data('bv-tab');
            if (tabConfig[tab]) fetchAndRender(tab, false);
        });

        // Refresco manual desde el botón de la tab Pedidos
        $(document).on('click', '.bv-refresh-source', function () {
            var tab = $('.bv-right-tab.on').data('bv-tab');
            if (tabConfig[tab]) fetchAndRender(tab, true);
        });

        // ─── Order detail modal ───────────────────────────────────────
        var psStoreUrl = $aside.data('ps-store-url') || '';

        function resetOrderModal(ref) {
            $('#bv-order-modal-chip').text(ref || '#—');
            $('#bv-order-modal-date').text('—');
            $('#bv-order-modal-status').text('Cargando…').attr('class', 'bv-ov-st');
            $('#bv-order-modal-payment').text('—');
            $('#bv-order-modal-products-count').text('');
            $('#bv-order-modal-products').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i></div>');
            $('#bv-order-modal-subtotal, #bv-order-modal-shipping-val, #bv-order-modal-tax, #bv-order-modal-total').text('—');
            $('#bv-order-modal-tax-row').removeClass('bv-hidden');
            $('#bv-order-modal-discount-row').addClass('bv-hidden');
            $('#bv-order-modal-tracking-field').addClass('bv-hidden');
            $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            $('#bv-order-modal-history-field').addClass('bv-hidden');
            $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            setAddressTab(null, null);
            $('#bv-order-modal-order-id').text('—');
            $('#bv-order-modal-reference').text('—');
            $('#bv-order-modal-external-link').addClass('bv-hidden');
            // Reset to info tab
            $('[data-bv-om-tab]').removeClass('is-active');
            $('[data-bv-om-tab="info"]').addClass('is-active');
            $('[data-bv-om-panel]').removeClass('is-active');
            $('[data-bv-om-panel="info"]').addClass('is-active');
        }

        function populateOrderModal(order) {
            var cur   = order.currency || 'EUR';
            var ref   = order.reference || ('#' + order.id);
            var state = order.state_name || '—';
            var date  = (order.created_at || '').substring(0, 10);

            $('#bv-order-modal-chip').text(ref);
            $('#bv-order-modal-date').text(date || '—');
            $('#bv-order-modal-status')
                .text(state)
                .attr('class', 'bv-ov-st ' + window.bvOrderStatusClass(state));

            // Nº pedido y referencia
            $('#bv-order-modal-order-id').text(order.id ? '#' + order.id : '—');
            $('#bv-order-modal-reference').text(order.reference || '—');

            // Products
            var lines = order.lines || [];
            $('#bv-order-modal-products-count').text(lines.length ? lines.length + (lines.length === 1 ? ' artículo' : ' artículos') : '');
            var prodsHtml = '';
            lines.forEach(function (l) {
                var lineTotal = parseFloat(l.total || (l.unit_price || 0) * (l.quantity || 1)) || 0;
                var unitPrice = parseFloat(l.unit_price || 0);
                var metaParts = [];
                if (l.reference) { metaParts.push('Ref: ' + esc(l.reference)); }
                metaParts.push('\xd7' + (l.quantity || 1));
                if (unitPrice > 0) { metaParts.push(cur + '\xa0' + unitPrice.toFixed(2)); }
                var nameContent = esc(l.name);
                if (l.url) {
                    nameContent = '<a class="bv-om-pc-name-link" href="' + esc(l.url) + '" target="_blank" rel="noopener">' +
                        nameContent + ' <i class="fas fa-arrow-up-right-from-square"></i></a>';
                }
                prodsHtml +=
                    '<div class="bv-om-prod-card">' +
                        '<div class="bv-om-pc-thumb"><i class="fas fa-box"></i></div>' +
                        '<div class="bv-om-pc-info">' +
                            '<div class="bv-om-pc-name">' + nameContent + '</div>' +
                            '<div class="bv-om-pc-meta">' + metaParts.join(' \xb7 ') + '</div>' +
                        '</div>' +
                        '<div class="bv-om-pc-price">' + cur + '\xa0' + lineTotal.toFixed(2) + '</div>' +
                    '</div>';
            });
            $('#bv-order-modal-products').html(
                prodsHtml || '<div class="bv-oc-empty"><i class="fas fa-box-open"></i><div class="title">Sin productos</div></div>'
            );

            // Totals
            var tot = order.totals || {};
            var ship = parseFloat(tot.shipping || 0);
            var disc = parseFloat(tot.discount || 0);
            $('#bv-order-modal-subtotal').text(parseFloat(tot.subtotal || 0).toFixed(2) + ' ' + cur);
            $('#bv-order-modal-shipping-val').text(ship > 0 ? ship.toFixed(2) + ' ' + cur : 'Gratis');
            $('#bv-order-modal-tax').text(parseFloat(tot.tax || 0).toFixed(2) + ' ' + cur);
            $('#bv-order-modal-tax-row').removeClass('bv-hidden');
            if (disc > 0) {
                $('#bv-order-modal-discount').text('− ' + disc.toFixed(2) + ' ' + cur);
                $('#bv-order-modal-discount-row').removeClass('bv-hidden');
            } else {
                $('#bv-order-modal-discount-row').addClass('bv-hidden');
            }
            $('#bv-order-modal-total').text(parseFloat(tot.total || 0).toFixed(2) + ' ' + cur);

            // Payment
            var payments = order.payments || [];
            $('#bv-order-modal-payment').text(payments.length ? (payments[0].payment_method || 'Pago') : '—');

            // Customer + dirección
            var custName = ($('.bv-cp-name-btn').first().text() || $('.bv-right-name').first().text() || '—').trim();
            $('#bv-order-modal-cust-name').text(custName);

            var shippingAddr = order.shipping_address || order.address || null;
            var billingAddr  = order.billing_address  || order.invoice_address || null;
            setAddressTab(shippingAddr, billingAddr);

            // Panel Seguimiento
            var tracking = order.tracking || [];
            if (tracking.length) {
                var trackHtml = '';
                tracking.forEach(function (t) {
                    var tn = t.tracking_number || '';
                    if (!tn) { return; }
                    var meta = [];
                    if (t.carrier_name) { meta.push(esc(t.carrier_name)); }
                    if (t.weight && parseFloat(t.weight) > 0) { meta.push(parseFloat(t.weight).toFixed(2) + ' kg'); }
                    trackHtml +=
                        '<div class="bv-ov-track">' +
                            '<i class="fa-solid fa-truck"></i>' +
                            '<div class="bv-ov-track-body">' +
                                '<b>' + esc(tn) + '</b>' +
                                (meta.length ? '<span class="bv-ov-track-meta">' + meta.join(' · ') + '</span>' : '') +
                            '</div>' +
                            '<button type="button" class="bv-ov-copy bv-copy-tracking" data-val="' + esc(tn) + '" title="Copiar">' +
                                '<i class="fa-regular fa-copy"></i>' +
                            '</button>' +
                        '</div>';
                });
                if (trackHtml) {
                    $('#bv-order-modal-tracking-container').html(trackHtml);
                    $('#bv-order-modal-tracking-field').removeClass('bv-hidden');
                    $('#bv-order-modal-tracking-empty').addClass('bv-hidden');
                } else {
                    $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                    $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
                }
            } else {
                $('#bv-order-modal-tracking-field').addClass('bv-hidden');
                $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            }

            // Panel Estados
            var history = order.history || [];
            if (history.length) {
                var histHtml = '<div class="bv-order-timeline">';
                history.forEach(function (h, i) {
                    var isLast = i === history.length - 1;
                    var d = (h.date || '').substring(0, 16).replace('T', ' ');
                    histHtml +=
                        '<div class="bv-ot-step' + (isLast ? ' is-current' : '') + '">' +
                            '<div class="bv-ot-dot" style="background:' + esc(h.color || '#ccc') + '"></div>' +
                            '<div class="bv-ot-body">' +
                                '<span class="bv-ot-name">' + esc(h.state_name || '') + '</span>' +
                                '<span class="bv-ot-date">' + esc(d) + '</span>' +
                            '</div>' +
                        '</div>';
                });
                histHtml += '</div>';
                $('#bv-order-modal-history').html(histHtml);
                $('#bv-order-modal-history-field').removeClass('bv-hidden');
                $('#bv-order-modal-states-empty').addClass('bv-hidden');
            } else {
                $('#bv-order-modal-history-field').addClass('bv-hidden');
                $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            }

            // External link
            if (psStoreUrl && order.id) {
                var adminUrl = psStoreUrl.replace(/\/+$/, '') + '/index.php?controller=AdminOrders&id_order=' + order.id + '&vieworder=1';
                $('#bv-order-modal-external-link')
                    .attr('href', adminUrl)
                    .html('<i class="fas fa-arrow-up-right-from-square"></i> Abrir en PrestaShop')
                    .removeClass('bv-hidden');
            }
        }

        function openOrderModal() {
            var $m = $('[data-bv-modal-name="order"]');
            if ($m.length) { $m.addClass('on'); $('body').css('overflow', 'hidden'); }
        }

        // Tab switching dentro del modal de pedido (3 tabs: info / tracking / states)
        $(document).on('click', '[data-bv-modal-name="order"] [data-bv-om-tab]', function () {
            var tab = $(this).data('bv-om-tab');
            var $modal = $('[data-bv-modal-name="order"]');
            $modal.find('[data-bv-om-tab]').removeClass('is-active');
            $(this).addClass('is-active');
            $modal.find('[data-bv-om-panel]').removeClass('is-active');
            $modal.find('[data-bv-om-panel="' + tab + '"]').addClass('is-active');
        });

        // Caché en memoria para detalles PS: la primera apertura hace AJAX,
        // las siguientes son instantáneas. Se invalida al cerrar el modal
        // de la conversación (SPA nav) o al recargar.
        var psDetailCache = {};

        function showPsOrderSkeleton(ref) {
            var skLine = '<div class="bv-om-prod-skeleton"><div class="bv-sk-thumb"></div><div class="bv-sk-body"><div class="bv-sk-line w70"></div><div class="bv-sk-line w40"></div></div></div>';
            $('#bv-order-modal-chip').text(ref || '#—');
            $('#bv-order-modal-cust-name').text('—');
            $('#bv-order-modal-order-id').text('—');
            $('#bv-order-modal-reference').text('—');
            $('#bv-order-modal-date').text('—');
            $('#bv-order-modal-status').text('—').attr('class', 'bv-ov-st');
            $('#bv-order-modal-payment').text('—');
            setAddressTab(null, null);
            $('#bv-order-modal-subtotal').text('—');
            $('#bv-order-modal-shipping-val').text('—');
            $('#bv-order-modal-total').text('—');
            $('#bv-order-modal-products-count').text('');
            $('#bv-order-modal-tracking-field').addClass('bv-hidden');
            $('#bv-order-modal-tracking-empty').removeClass('bv-hidden');
            $('#bv-order-modal-history-field').addClass('bv-hidden');
            $('#bv-order-modal-states-empty').removeClass('bv-hidden');
            $('#bv-order-modal-external-link').addClass('bv-hidden');
            // Reset to info tab
            $('[data-bv-modal-name="order"] [data-bv-om-tab]').removeClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-tab="info"]').addClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-panel]').removeClass('is-active');
            $('[data-bv-modal-name="order"] [data-bv-om-panel="info"]').addClass('is-active');
            $('#bv-order-modal-products').html(skLine + skLine);
        }

        // Deferred callbacks for in-flight pre-warm requests
        var psPrewarmDeferreds = {};

        function fetchPsOrderDetail(orderId, baseUrl, custEmail, onDone) {
            if (!orderId || !baseUrl) { return; }

            // Already cached — call onDone immediately
            if (psDetailCache[orderId]) {
                if (onDone) { onDone(psDetailCache[orderId]); }
                return;
            }

            // Already in flight — queue the callback
            if (psPrewarmDeferreds[orderId]) {
                if (onDone) { psPrewarmDeferreds[orderId].push(onDone); }
                return;
            }

            psPrewarmDeferreds[orderId] = onDone ? [onDone] : [];
            var detailUrl = baseUrl + orderId + '/detail' + (custEmail ? '?email=' + encodeURIComponent(custEmail) : '');

            $.ajax({
                url: detailUrl,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                success: function (resp) {
                    if (resp.success && resp.data) {
                        psDetailCache[orderId] = resp.data;
                        var cbs = psPrewarmDeferreds[orderId] || [];
                        delete psPrewarmDeferreds[orderId];
                        cbs.forEach(function (cb) { cb(resp.data); });
                    } else {
                        delete psPrewarmDeferreds[orderId];
                    }
                },
                error: function () {
                    delete psPrewarmDeferreds[orderId];
                },
            });
        }

        // Pre-warm on hover so the modal is instant on click
        $(document).on('mouseenter', '.rp3-order[data-order-platform="prestashop"]', function () {
            var orderId = String($(this).data('order-id') || '');
            var baseUrl = $('#bv-ps-orders').data('ps-order-detail-url') || '';
            var custEmail = ($('[data-customer-email]').first().data('customer-email') || '').trim();
            fetchPsOrderDetail(orderId, baseUrl, custEmail, null);
        });

        $(document).on('click', '.rp3-order[data-order-platform="prestashop"]', function (e) {
            e.stopImmediatePropagation();
            var $el     = $(this);
            var orderId = String($el.data('order-id') || '');
            var ref     = $el.data('order-ref') || '#—';
            var baseUrl = $('#bv-ps-orders').data('ps-order-detail-url') || '';
            var custEmail = ($('[data-customer-email]').first().data('customer-email') || '').trim();

            // Always open modal immediately; populate with data or skeleton
            if (psDetailCache[orderId]) {
                openOrderModal();
                populateOrderModal(psDetailCache[orderId]);
                return;
            }

            showPsOrderSkeleton(ref);
            openOrderModal();

            fetchPsOrderDetail(orderId, baseUrl, custEmail, function (data) {
                populateOrderModal(data);
            });
        });

        // Copy tracking number
        $(document).on('click', '.bv-copy-tracking', function () {
            var val = $(this).data('val');
            if (val && navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function () {
                    if (window.toastr) { toastr.success('Número de tracking copiado'); }
                });
            }
        });

    })();

    // ═══════════════════════════════════════════════════════════════
    // FEATURE: Preview conversación anterior (modal mv4-prev-thread)
    // ═══════════════════════════════════════════════════════════════
    (function () {
        function escHtml(s) {
            if (s == null) { return ''; }
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        var channelNames = {
            whatsapp: 'WhatsApp', facebook: 'Facebook', instagram: 'Instagram',
            email: 'Email', widget: 'Widget',
        };

        function buildPreviewBody(data) {
            var conv    = data.conversation;
            var msgs    = data.messages || [];
            var chName  = channelNames[conv.channel] || conv.channel;
            var chColor = {
                whatsapp: '#25d366', facebook: '#1877f2', instagram: '#e4405f',
                email: '#52525b', widget: '#6366f1',
            }[conv.channel] || '#8c929d';

            var isOpen  = conv.status && conv.status.is_open;
            var stColor = isOpen ? '#16a34a' : '#8c929d';
            var stName  = (conv.status && conv.status.name) || 'Abierta';

            // Meta pills
            var pills = '';
            pills += '<span class="mv4-pill"><span class="d" style="background:' + chColor + '"></span>' + escHtml(chName) + '</span>';
            pills += '<span class="mv4-pill"><span class="d" style="background:' + stColor + '"></span>' + escHtml(stName) + '</span>';
            if (conv.assignee) {
                pills += '<span class="mv4-pill">' + escHtml(conv.assignee.name) + '</span>';
            }
            pills += '<span class="mv4-pill mono">' + escHtml(conv.created_at) + '</span>';

            // Thread messages
            var thread = '';
            if (!msgs.length) {
                thread = '<div style="text-align:center;color:var(--bv-text-muted,#8c929d);font-size:12px;padding:20px 0">Sin mensajes</div>';
            } else {
                msgs.forEach(function (m) {
                    var dir = m.is_from_agent ? 'out' : 'in';
                    thread += '<div class="mv4-pmsg ' + dir + '">' +
                        '<div class="bubble">' +
                        (m.is_from_agent && m.sender_name ? '<div class="who">' + escHtml(m.sender_name) + '</div>' : '') +
                        '<div class="body">' + escHtml(m.body) + '</div>' +
                        '<div class="t">' + escHtml(m.created_at) + '</div>' +
                        '</div></div>';
                });
            }

            return '<div class="mv4-prev-head"><div class="mv4-prev-meta">' + pills + '</div></div>' +
                   '<div class="mv4-prev-thread">' + thread + '</div>';
        }

        function openPrevConvModal() {
            var $modal = $('[data-bv-modal-name="preview-conv"]');
            $modal.addClass('on');
            $('body').css('overflow', 'hidden');
        }

        $(document).on('click', '.rp3-prev', function () {
            var $el      = $(this);
            var url      = $el.data('preview-url');
            var openUrl  = $el.data('open-url');
            var subject  = $el.data('subject') || 'Conversación';

            // Reset modal state
            $('#prev-conv-subject').text(subject);
            $('#prev-conv-open-btn').attr('href', openUrl || '#');
            $('#prev-conv-body').html('<div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');

            // Open modal immediately (shows loading)
            openPrevConvModal();

            if (!url) { return; }

            $.get(url)
                .done(function (data) {
                    $('#prev-conv-subject').text(data.conversation.subject || subject);
                    $('#prev-conv-open-btn').attr('href', data.open_url || openUrl || '#');
                    $('#prev-conv-body').html(buildPreviewBody(data));
                })
                .fail(function () {
                    $('#prev-conv-body').html(
                        '<div class="bv-tab-empty"><i class="fas fa-triangle-exclamation"></i>' +
                        '<div class="bv-tab-empty-title">Error al cargar</div>' +
                        '<div class="bv-tab-empty-sub">No se pudo obtener el historial de esta conversación</div></div>'
                    );
                });
        });

        // Exportar: abre la URL de la conversación en nueva pestaña (sin backend de PDF)
        $(document).on('click', '#prev-conv-export-btn', function () {
            var href = $('#prev-conv-open-btn').attr('href');
            if (href && href !== '#') { window.open(href, '_blank'); }
        });
    })();

    // ═══════════════════════════════════════════════════════════════
    // FEATURE: Notificaciones push en tiempo real (Helpdesk)
    // Escucha el evento 'notification-received' (Echo/Reverb) y el evento
    // 'bv:notifications:refreshed' que emite window.NotificationManager
    // (modules/Notification/public/js/notifications.js) cada vez que
    // consulta /api/notifications. Este módulo NO hace su propio fetch:
    // reutiliza los datos ya pedidos por NotificationManager para evitar
    // pegarle dos veces a la misma API.
    // ═══════════════════════════════════════════════════════════════
    (function () {
        var HD_TYPES = {
            helpdesk_conversation_assigned: { level: 'info',    refresh: true  },
            helpdesk_new_conversation:      { level: 'info',    refresh: true  },
            helpdesk_message_received:      { level: 'info',    refresh: true  },
            helpdesk_mention:               { level: 'warning', refresh: false },
            helpdesk_status_changed:        { level: 'info',    refresh: true  },
            helpdesk_escalation:            { level: 'error',   refresh: false },
        };

        var seenDbIds   = new Set();
        var seenEcho    = {};
        var initialized = false;

        function echoKey(n) { return (n.type || '') + '_' + (n.entity_id || ''); }

        function wasRecentlyShownByEcho(n) {
            var t = seenEcho[echoKey(n)];
            return !!(t && (Date.now() - t) < 60000);
        }

        function markEcho(n) { seenEcho[echoKey(n)] = Date.now(); }

        function normalizeUrl(raw) {
            if (!raw) { return null; }
            try {
                var parsed = new URL(raw);
                return window.location.origin + parsed.pathname + (parsed.search || '');
            } catch (_e) { return raw; }
        }

        function showNotification(n) {
            var meta  = HD_TYPES[n.type];
            var url   = normalizeUrl(n.action_url);
            var title = n.title   || 'Helpdesk';
            var msg   = n.message || '';

            var opts = { closeButton: true, tapToDismiss: true, timeOut: 8000, extendedTimeOut: 2000 };
            if (url) {
                opts.onclick = function () { window.location.href = url; };
            }

            if (window.toastr && toastr[meta.level]) {
                toastr[meta.level](msg, title, opts);
            }

            if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                try {
                    var push = new window.Notification(title, { body: msg, icon: '/favicon.ico', tag: 'hd-' + (n.id || echoKey(n)) });
                    if (url) {
                        push.onclick = function () { window.focus(); window.location.href = url; push.close(); };
                    }
                } catch (_e) {}
            }

            if (meta.refresh && typeof window.applyInboxFilters === 'function') {
                setTimeout(function () { window.applyInboxFilters({}); }, 1000);
            }
        }

        // Listener para evento global (Echo / Reverb cuando esté disponible)
        window.addEventListener('notification-received', function (e) {
            var n = e.detail || {};
            if (!n.type || !HD_TYPES[n.type]) { return; }
            if (wasRecentlyShownByEcho(n)) { return; }
            markEcho(n);
            if (n.id) { seenDbIds.add(n.id); }
            showNotification(n);
        });

        // Timestamp de cuándo cargó la página — solo mostramos toasts para notificaciones más nuevas
        var pageLoadTs = new Date();

        // FE-06: NotificationManager ya sondea /api/notifications (cada 60s con
        // realtime conectado, cada 15s si no) y dispara este evento con el
        // resultado. Aquí solo decidimos qué toasts mostrar con esos datos.
        window.addEventListener('bv:notifications:refreshed', function (e) {
            var list = (e.detail && e.detail.notifications) || [];

            if (!initialized) {
                // Primera carga: registrar todos los IDs actuales sin mostrar toasts
                list.forEach(function (n) { seenDbIds.add(n.id); });
                initialized = true;
                return;
            }

            list.forEach(function (n) {
                if (seenDbIds.has(n.id)) { return; }
                seenDbIds.add(n.id);
                if (!HD_TYPES[n.type]) { return; }
                // Solo mostrar si la notificación es posterior a la carga de la página
                if (pageLoadTs && n.created_at_full && new Date(n.created_at_full) < pageLoadTs) { return; }
                if (wasRecentlyShownByEcho(n)) { return; }
                showNotification(n);
            });
        });
    })();


})(jQuery);
