{{-- Modal: Guía de menciones (user-facing) --}}
<div class="bv-modal" data-bv-modal-name="mention-help">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-circle-question"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">Composer · Ayuda</span>
                <div class="bv-modal-title">Cómo usar las menciones</div>
            </div>
            <button class="bv-modal-close" data-bv-close type="button"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <p class="bv-help-intro">
                Escribe <code>@</code> en el composer para abrir el selector, o usa los siguientes atajos directamente en el texto.
                La persona o equipo mencionado recibirá una notificación al guardar el mensaje.
            </p>

            {{-- Sintaxis --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">Sintaxis</div>
                <table class="bv-help-table">
                    <tbody>
                        <tr>
                            <td><code>@nombre.apellido</code></td>
                            <td>Mencionar a un agente individual</td>
                        </tr>
                        <tr>
                            <td><code>@team_key</code></td>
                            <td>Mencionar a un equipo (notifica a todos sus miembros)</td>
                        </tr>
                        <tr>
                            <td><code>@all</code></td>
                            <td>Notificar a todos los agentes del workspace</td>
                        </tr>
                        <tr>
                            <td><code>@here</code></td>
                            <td>Notificar solo a los agentes <strong>conectados ahora</strong></td>
                        </tr>
                        <tr>
                            <td><code>@team</code></td>
                            <td>Notificar al equipo asignado a esta conversación</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Cómo funciona --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">Cómo funciona</div>
                <ul class="bv-help-list">
                    <li>Al guardar el mensaje, cada persona única mencionada recibe una notificación en su campana del sistema.</li>
                    <li>Las menciones aparecen como <span class="bv-mention-chip">@ejemplo</span> en el thread y se pueden clickar para ver el detalle.</li>
                    <li>En notas internas, las menciones son privadas (el cliente nunca las ve).</li>
                    <li>En respuestas externas (WhatsApp, email…), los <code>@handles</code> se reemplazan por nombres legibles antes de enviar.</li>
                    <li>El autor del mensaje nunca recibe notificación de su propia mención.</li>
                </ul>
            </div>

            {{-- Canales --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">Canales de notificación</div>
                <ul class="bv-help-list">
                    <li><strong>Campana del sistema</strong> — siempre.</li>
                    <li><strong>Tiempo real (broadcast)</strong> — siempre, si el agente tiene la app abierta.</li>
                    <li><strong>Email</strong> — opcional, se activa desde tus preferencias de agente.</li>
                </ul>
            </div>

            {{-- Atajos --}}
            <div class="bv-help-section">
                <div class="bv-help-section-title">Atajos</div>
                <ul class="bv-help-shortcuts">
                    <li><kbd>@</kbd> en el composer abre el dropdown inline.</li>
                    <li>Botón <kbd><i class="fas fa-at"></i></kbd> abre el modal con tabs Agentes / Equipos.</li>
                    <li><kbd>↑</kbd> <kbd>↓</kbd> navegan resultados; <kbd>Enter</kbd> inserta.</li>
                    <li><kbd>Esc</kbd> cierra el dropdown sin insertar.</li>
                </ul>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-close type="button">Entendido</button>
        </div>
    </div>
</div>
