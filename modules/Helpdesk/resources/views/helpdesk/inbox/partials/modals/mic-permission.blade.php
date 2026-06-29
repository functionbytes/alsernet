{{-- Modal: Error de permiso de micrófono (#66 ve-mic-permission) --}}
<div class="bv-modal" data-bv-modal-name="mic-permission">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-microphone-slash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">PERMISO · NAVEGADOR</span>
                <div class="bv-modal-title">No se pudo acceder al micrófono</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-perm-hero">
                <div class="bv-perm-hero__ic"><i class="fas fa-microphone-slash"></i></div>
                <div class="bv-perm-hero__t">No se pudo acceder al micrófono</div>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">1</span> Permitir el micrófono en macOS</div>
                <ul>
                    <li>Abre <b>Ajustes del sistema</b> → Privacidad y seguridad → Micrófono</li>
                    <li>Activa el switch para <b>tu navegador</b></li>
                    <li>Si ya estaba activo, <b>desactívalo y vuelve a activarlo</b></li>
                    <li>Cierra completamente el navegador y reábrelo</li>
                </ul>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">2</span> Resetear el permiso del sitio</div>
                <p class="bv-perm-step__hint">Esta es la forma <b>rápida</b> que sí funciona: usa "Restablecer permisos", no toques el switch del micrófono.</p>
                <ul>
                    <li>Click en el icono <i class="fas fa-lock" style="font-size:10px"></i> a la izquierda de la URL en la barra de direcciones</li>
                    <li>Click en <b>"Restablecer permisos"</b> (al final del popup)</li>
                    <li><b>Recarga la página</b> (Cmd+R / Ctrl+R)</li>
                    <li>Vuelve a hacer click en el botón del micrófono → saldrá el prompt nativo</li>
                    <li>Click en <b>Permitir</b></li>
                </ul>
            </div>

            <div class="bv-perm-step">
                <div class="bv-perm-step__h"><span class="bv-perm-step__num">3</span> Si el micrófono está bloqueado globalmente</div>
                <p style="font-size:11.5px;line-height:1.55;margin:0 0 6px">Pega en una pestaña nueva:</p>
                <div class="bv-perm-url" id="bvMicPermUrl" data-url="chrome://settings/content/microphone">
                    <span>chrome://settings/content/microphone</span>
                    <button type="button" class="bv-perm-url__copy" title="Copiar"><i class="far fa-copy"></i></button>
                </div>
                <ul>
                    <li>En "<b>Comportamiento predeterminado</b>" verifica que esté "Los sitios pueden pedirte usar tu micrófono"</li>
                    <li>En "<b>No permitir</b>" busca la URL del sistema y elimínala si aparece</li>
                </ul>
            </div>

            <div class="bv-perm-diag" id="bvMicDiag" style="display:none">
                <div class="bv-perm-diag__h">Diagnóstico</div>
                <div class="bv-perm-diag__body"></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-mic-reload">Recargar página</button>
            <button class="btn-secondary" id="bv-mic-retry">Reintentar sin recargar</button>
            <button class="btn-secondary" data-bv-close>Salir sin micrófono</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'mic-permission') { return; }
        if (data && data.errorCode) {
            var html = '<div>Error: <code>' + $('<span>').text(data.errorCode).html() + '</code></div>';
            if (data.permission) { html += '<div>Permiso: <code>' + $('<span>').text(data.permission).html() + '</code></div>'; }
            $('#bvMicDiag .bv-perm-diag__body').html(html);
            $('#bvMicDiag').show();
        } else {
            $('#bvMicDiag').hide();
        }
    });

    $(document).on('click', '.bv-perm-url__copy', function () {
        var url = $(this).closest('.bv-perm-url').data('url') || $(this).closest('.bv-perm-url').find('span').text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                if (window.toastr) { toastr.success('URL copiada'); }
            });
        }
    });

    $(document).on('click', '#bv-mic-reload', function () {
        window.location.reload();
    });

    $(document).on('click', '#bv-mic-retry', function () {
        $('[data-bv-modal-name="mic-permission"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
        $(document).trigger('bv:mic:retry');
    });

}(window.jQuery));
</script>
@endpush
@endonce
