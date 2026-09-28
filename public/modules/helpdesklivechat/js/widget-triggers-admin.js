/**
 * Disparadores proactivos del chat web (settings/triggers): borrado desde el
 * listado y filas de condición dinámicas en el formulario.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        var $form = $('#trigger-form');
        if (!$form.length) { return; }

        var opsByType = $form.data('ops-by-type') || {};
        var opLabels = $form.data('op-labels') || {};
        var $list = $('#t-conditions');

        function fillOps($row) {
            var type = $row.find('.t-type').val();
            var $op = $row.find('.t-op');
            var current = $op.val() || $op.data('selected');
            $op.empty();
            (opsByType[type] || []).forEach(function (op) {
                $('<option>').val(op).text(opLabels[op] || op).prop('selected', op === current).appendTo($op);
            });
        }

        function renumber() {
            $list.find('.t-condition').each(function (i) {
                $(this).find('select, input').each(function () {
                    this.name = this.name.replace(/conditions\[\d+\]/, 'conditions[' + i + ']');
                });
            });
            $list.find('.t-remove').prop('disabled', $list.find('.t-condition').length <= 1);
        }

        function toggleMessage() {
            var isMessage = $('#t-action').val() === 'message';
            $('#t-message-wrap').toggleClass('d-none', !isMessage);
            $('#t-message').prop('required', isMessage);
        }

        $list.on('change', '.t-type', function () {
            var $row = $(this).closest('.t-condition');
            $row.find('.t-op').val('').data('selected', '');
            fillOps($row);
        });

        $list.on('click', '.t-remove', function () {
            if ($list.find('.t-condition').length > 1) {
                $(this).closest('.t-condition').remove();
                renumber();
            }
        });

        $('#t-add').on('click', function () {
            if ($list.find('.t-condition').length >= 10) { return; }
            var $row = $list.find('.t-condition').last().clone();
            $row.find('input').val('');
            $row.find('.t-type').val('page_time');
            $row.find('.t-op').data('selected', 'gte');
            $list.append($row);
            fillOps($row);
            renumber();
        });

        $('#t-action').on('change', toggleMessage);
        toggleMessage();
        renumber();
    });
})();
