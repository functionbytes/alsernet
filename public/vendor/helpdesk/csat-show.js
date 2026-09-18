$(function () {
    var $stars = $('.csat-star');
    var selected = 0;

    function paintStars(upTo) {
        $stars.each(function (i) {
            var active = i < upTo;
            $(this).toggleClass('active', active);
            $(this).find('i').attr('class', active ? 'fas fa-star' : 'far fa-star');
        });
    }

    $stars.on('click', function () {
        selected = parseInt($(this).data('value'));
        $('#star' + selected).prop('checked', true);
        paintStars(selected);
    });

    $stars.on('mouseenter', function () {
        paintStars(parseInt($(this).data('value')));
    });

    $stars.parent().on('mouseleave', function () {
        paintStars(selected);
    });

    $('#comment').on('input', function () {
        $('#char-count').text($(this).val().length);
    });
});
