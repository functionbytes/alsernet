/**
 * Voto util/no util del articulo publico (public/helpcenter/show.blade.php).
 * Extraido del <script> inline de esa vista — el slug del articulo llega
 * por data-article-slug en #article-vote (unico dato que este fichero no
 * puede resolver por su cuenta).
 */
(function () {
    'use strict';

    $(function () {
        var articleSlug = $('#article-vote').data('article-slug');
        var selectedVote = null;

        $('[data-vote]').on('click', function () {
            selectedVote = parseInt($(this).data('vote'));

            if (selectedVote === -1) {
                $('#article-vote-comment').slideDown();
            } else {
                submitVote(null);
            }
        });

        $('#article-vote-submit').on('click', function () {
            submitVote($('#vote-comment-text').val());
        });

        function submitVote(comment) {
            $.ajax({
                url: '/api/helpcenter/articles/' + articleSlug + '/vote',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { vote: selectedVote, comment: comment },
                success: function () {
                    $('#article-vote').hide();
                    $('#article-vote-comment').hide();
                    $('#article-vote-thanks').show();
                },
                error: function (xhr) {
                    var message = xhr.status === 429
                        ? 'Demasiadas peticiones. Espera un momento antes de volver a intentarlo.'
                        : 'No se pudo registrar tu voto. Inténtalo de nuevo.';

                    $('#article-vote-error-text').text(message);
                    $('#article-vote-error').show();
                }
            });
        }
    });
})();
