/**
 * voice-notes.js — Standalone voice note recorder for the Helpdesk composer.
 *
 * Adds a dedicated mic button (#bv-mic-record) next to the attach button.
 * Uses MediaRecorder + audio/webm and uploads via the existing attachments endpoint.
 * Loaded after conversations.js. Does NOT modify conversations.js.
 */
(function ($) {
    'use strict';

    // State
    let mediaRecorder = null;
    let audioChunks   = [];
    let timerInterval = null;
    let elapsedSecs   = 0;
    let cancelled     = false;
    let micStream     = null;

    // ── DOM helpers ──────────────────────────────────────────────────────────

    function getMicBtn()    { return $('#bv-mic-record'); }
    function getTimerEl()   { return $('#bv-mic-timer'); }
    function getCancelBtn() { return $('#bv-mic-cancel'); }

    // ── UI state ─────────────────────────────────────────────────────────────

    function setRecordingUI(active) {
        const $btn    = getMicBtn();
        const $timer  = getTimerEl();
        const $cancel = getCancelBtn();

        if (active) {
            $btn.find('i').removeClass('fa-microphone').addClass('fa-stop');
            $btn.addClass('bv-mic-btn--recording');
            $timer.removeClass('bv-hidden');
            $cancel.removeClass('bv-hidden');
        } else {
            $btn.find('i').removeClass('fa-stop').addClass('fa-microphone');
            $btn.removeClass('bv-mic-btn--recording');
            $timer.addClass('bv-hidden');
            $cancel.addClass('bv-hidden');
            elapsedSecs = 0;
            $timer.text('0:00');
        }
    }

    function tickTimer() {
        elapsedSecs++;
        const m = Math.floor(elapsedSecs / 60);
        const s = String(elapsedSecs % 60).padStart(2, '0');
        getTimerEl().text(m + ':' + s);
    }

    // ── Recording ────────────────────────────────────────────────────────────

    async function startRecording() {
        if (!navigator.mediaDevices?.getUserMedia) {
            toastr.error('Tu navegador no soporta grabación de audio.');
            return;
        }

        try {
            micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioChunks = [];
            cancelled = false;

            // Prefer audio/webm; fall back to browser default
            const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '';
            const options  = mimeType ? { mimeType } : {};

            mediaRecorder = new MediaRecorder(micStream, options);

            mediaRecorder.ondataavailable = function (e) {
                if (!cancelled && e.data.size > 0) {
                    audioChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = function () {
                stopStream();
                if (!cancelled && audioChunks.length) {
                    const type  = mimeType || 'audio/webm';
                    const blob  = new Blob(audioChunks, { type });
                    const fname = 'voz-' + Date.now() + '.webm';
                    const file  = new File([blob], fname, { type });
                    uploadVoiceNote(file);
                }
                audioChunks = [];
            };

            mediaRecorder.start();

            elapsedSecs = 0;
            clearInterval(timerInterval);
            timerInterval = setInterval(tickTimer, 1000);
            setRecordingUI(true);

        } catch (err) {
            handleMicError(err);
        }
    }

    function stopRecording() {
        clearInterval(timerInterval);
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        setRecordingUI(false);
    }

    function cancelRecording() {
        cancelled = true;
        clearInterval(timerInterval);
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        stopStream();
        audioChunks = [];
        setRecordingUI(false);
    }

    function stopStream() {
        if (micStream) {
            micStream.getTracks().forEach(function (t) { t.stop(); });
            micStream = null;
        }
    }

    // ── Upload ───────────────────────────────────────────────────────────────

    function uploadVoiceNote(file) {
        const $composer = $('.bv-composer');
        const convId    = $composer.data('bv-conversation-id');

        if (!convId) {
            toastr.error('Selecciona una conversación antes de grabar.');
            return;
        }

        const fd  = new FormData();
        fd.append('files[]', file);

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/attachments',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
            success: function (resp) {
                if (resp?.item && typeof window.appendBubbleToThread === 'function') {
                    window.appendBubbleToThread(resp.item, false);
                }
            },
            error: function (xhr) {
                const msg = xhr?.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0]?.[0]
                    : (xhr?.responseJSON?.message || 'No se pudo enviar la nota de voz');
                toastr.error(msg);
            },
        });
    }

    // ── Error handling ───────────────────────────────────────────────────────

    function handleMicError(err) {
        console.error('[bv-mic-record]', err.name, err.message);

        const messages = {
            NotAllowedError:      'Permiso de micrófono denegado. Habilítalo en la barra de dirección.',
            PermissionDeniedError: 'Permiso de micrófono denegado. Habilítalo en la barra de dirección.',
            NotFoundError:        'No se detectó ningún micrófono conectado.',
            DevicesNotFoundError: 'No se detectó ningún micrófono conectado.',
            NotReadableError:     'El micrófono está en uso por otra aplicación.',
            TrackStartError:      'El micrófono está en uso por otra aplicación.',
            SecurityError:        'La grabación requiere HTTPS.',
        };

        toastr.error(messages[err.name] || 'Error al acceder al micrófono: ' + err.message);
    }

    // ── Inject button into composer ──────────────────────────────────────────

    function injectMicButton() {
        // Avoid double-inject
        if ($('#bv-mic-record').length) return;

        const $attachWrap = $('.bv-attach-wrap').first();
        if (!$attachWrap.length) return;

        const $btn = $(
            '<button class="btn-ico bv-mic-btn" id="bv-mic-record" type="button" title="Nota de voz">' +
                '<i class="fas fa-microphone"></i>' +
            '</button>'
        );

        const $timer = $(
            '<span class="bv-mic-timer bv-hidden" id="bv-mic-timer">0:00</span>'
        );

        const $cancel = $(
            '<button class="btn-ico bv-mic-btn bv-mic-cancel bv-hidden" id="bv-mic-cancel" ' +
                'type="button" title="Cancelar grabación">' +
                '<i class="fas fa-xmark"></i>' +
            '</button>'
        );

        $attachWrap.after($cancel).after($timer).after($btn);

        // Inline styles kept minimal — use classes where possible
        $btn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (mediaRecorder && mediaRecorder.state === 'recording') {
                stopRecording();
            } else {
                startRecording();
            }
        });

        $cancel.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            cancelRecording();
        });

        // Right-click on mic button also cancels
        $btn.on('contextmenu', function (e) {
            e.preventDefault();
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                cancelRecording();
            }
        });
    }

    // ── Init ─────────────────────────────────────────────────────────────────

    function init() {
        // The composer may be re-rendered when the user switches conversations.
        // Use a MutationObserver to re-inject the button when .bv-attach-wrap appears.
        injectMicButton();

        const observer = new MutationObserver(function () {
            if (!$('#bv-mic-record').length && $('.bv-attach-wrap').length) {
                injectMicButton();
            }
        });

        const target = document.querySelector('.bv-thread') || document.body;
        observer.observe(target, { childList: true, subtree: true });
    }

    // ── CSS injection ────────────────────────────────────────────────────────

    function injectStyles() {
        if (document.getElementById('bv-voice-notes-css')) return;

        const style = document.createElement('style');
        style.id = 'bv-voice-notes-css';
        style.textContent = [
            '.bv-mic-btn--recording i { color: #ef4444; }',
            '.bv-mic-timer {',
            '  font-size: 11px; font-weight: 600; color: #ef4444;',
            '  padding: 0 4px; display: inline-flex; align-items: center;',
            '}',
            '.bv-mic-cancel { color: #6b7280; }',
        ].join('\n');

        document.head.appendChild(style);
    }

    // ── Boot ─────────────────────────────────────────────────────────────────

    $(function () {
        injectStyles();
        init();
    });

}(jQuery));
