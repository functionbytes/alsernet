(function ($) {
    'use strict';

    $('textarea[data-counter]').each(function () {
        const $textarea = $(this);
        const $counter = $($textarea.data('counter'));

        function update() {
            $counter.text($textarea.val().length);
        }

        $textarea.on('input', update);
        update();
    });

}(jQuery));
