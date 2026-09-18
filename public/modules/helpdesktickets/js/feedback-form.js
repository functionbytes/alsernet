/**
 * Formulario público de feedback CSAT (public/feedback-form.blade.php) —
 * propiedad de HelpdeskTickets.
 *
 * Vivía como dos <script> sueltos dentro del propio Blade. Sin datos
 * dinámicos de servidor: el umbral de motivo ya viaja como
 * data-reason-threshold en el propio <form>.
 *
 * Tras editar hay que copiarlo a public/modules/helpdesktickets/js/.
 */
(function () {
    'use strict';

    $(function () {
        $('.select2').select2({ width: '100%' });
    });

    $(function () {
        var stars = document.querySelectorAll('.rating-star');
        var form = document.querySelector('form[data-reason-threshold]');
        var threshold = form ? parseInt(form.dataset.reasonThreshold, 10) : 3;
        var reasonBlock = document.getElementById('reason-block');

        stars.forEach(function (star, idx) {
            star.addEventListener('click', function () {
                var rating = idx + 1;
                var radio = document.getElementById('r' + rating);
                radio.checked = true;
                stars.forEach(function (s, i) {
                    s.classList.toggle('active', i <= idx);
                    s.querySelector('i').className = i <= idx ? 'fas fa-star' : 'far fa-star';
                });
                // Solo pedimos el motivo cuando la nota es baja (<= umbral).
                if (reasonBlock) {
                    reasonBlock.classList.toggle('d-none', rating > threshold);
                }
            });
        });
    });
})();
