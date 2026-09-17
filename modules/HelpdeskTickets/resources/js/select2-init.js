/**
 * Inicializador genérico de select2 (".select2" → ancho 100%) — propiedad de
 * HelpdeskTickets.
 *
 * Vivía duplicado, línea por línea idéntica, como <script> suelto en 9+
 * Blades del módulo (managers/settings/{general,features,macros,
 * automations}, managers/search, agents/tickets). Un solo fichero,
 * reutilizado con <script src>, en vez de copiar y pegar el mismo bloque en
 * cada pantalla.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
    });
})();
