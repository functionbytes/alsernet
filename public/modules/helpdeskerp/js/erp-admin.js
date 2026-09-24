/*
 | «Ajustes de Gestión» y «Métricas de Gestión».
 |  - Repetidor de plantillas de seguimiento (añadir / quitar filas).
 |  - Confirmación de «Restablecer» por sección.
 |  - Filtro de agente que se envía solo al cambiar.
 | Sin dependencias: funciona con o sin jQuery.
 */
(function () {
    'use strict';

    function closest(el, selector) {
        while (el && el.nodeType === 1) {
            if (el.matches(selector)) {
                return el;
            }
            el = el.parentElement;
        }
        return null;
    }

    function syncEmpty(list) {
        var name = list.getAttribute('data-era-list');
        var empty = document.querySelector('[data-era-empty="' + name + '"]');
        if (empty) {
            empty.classList.toggle('era-hidden', list.querySelector('[data-era-row]') !== null);
        }
    }

    function addRow(name) {
        var list = document.querySelector('[data-era-list="' + name + '"]');
        var tpl = document.getElementById('eraTpl-' + name);
        if (!list || !tpl) {
            return;
        }
        var next = parseInt(list.getAttribute('data-next') || '0', 10) || 0;
        list.setAttribute('data-next', String(next + 1));

        var holder = document.createElement('div');
        holder.innerHTML = tpl.innerHTML.replace(/__i__/g, String(next)).trim();
        var row = holder.firstElementChild;
        if (!row) {
            return;
        }
        list.appendChild(row);
        syncEmpty(list);

        var first = row.querySelector('input');
        if (first) {
            first.focus();
        }
    }

    document.addEventListener('click', function (e) {
        var add = closest(e.target, '[data-era-add]');
        if (add) {
            e.preventDefault();
            if (!add.disabled) {
                addRow(add.getAttribute('data-era-add'));
            }
            return;
        }

        var remove = closest(e.target, '[data-era-remove]');
        if (remove) {
            e.preventDefault();
            var row = closest(remove, '[data-era-row]');
            var list = row ? closest(row, '[data-era-list]') : null;
            if (row) {
                row.parentNode.removeChild(row);
            }
            if (list) {
                syncEmpty(list);
            }
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-era-reset')) {
            return;
        }
        var label = form.getAttribute('data-era-reset') || 'esta sección';
        if (!window.confirm('¿Restablecer «' + label + '»? Se descarta lo guardado y vuelven los valores de configuración.')) {
            e.preventDefault();
        }
    }, true);

    document.addEventListener('change', function (e) {
        var select = closest(e.target, '[data-era-autosubmit]');
        if (select && select.form) {
            select.form.submit();
        }
    });
})();
