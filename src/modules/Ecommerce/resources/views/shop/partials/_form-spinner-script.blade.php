<script>
$(function () {
    $(document).on('submit', '.js-loading-form', function () {
        const $btn = $(this).find('button[type="submit"]');
        $btn.addClass('btn-loading').prop('disabled', true);
    });
});
</script>
