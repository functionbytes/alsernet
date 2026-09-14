<script>
$(document).ready(function() {
    function toggleDriverFields() {
        const driver = $('#driver').val();

        // Hide all sections and clear their input values to prevent stale data submitting
        $('.driver-fields').hide().find('input, select, textarea').each(function() {
            const $input = $(this);
            // Don't clear password fields that carry the "preserve existing" semantic (edit mode)
            if ($input.attr('type') === 'password' && !$input.data('create-mode')) {
                return;
            }
            // Don't clear radio/checkbox inputs — they retain their default state
            if ($input.attr('type') === 'radio' || $input.attr('type') === 'checkbox') {
                return;
            }
            $input.val('');
        });

        if (driver === 'local') $('#localFields').show();
        else if (driver === 'ftp') $('#ftpFields').show();
        else if (driver === 'sftp') $('#sftpFields').show();
        else if (driver === 's3') $('#s3Fields').show();

        // Secret is required only when creating an S3 disk (edit form omits the required attribute)
        const $secret = $('input[name="secret"]');
        if ($secret.length && $secret.data('create-mode')) {
            $secret.prop('required', driver === 's3');
        }
    }

    // On page load: show the correct section without clearing values
    const initialDriver = $('#driver').val();
    $('.driver-fields').hide();
    if (initialDriver === 'local') $('#localFields').show();
    else if (initialDriver === 'ftp') $('#ftpFields').show();
    else if (initialDriver === 'sftp') $('#sftpFields').show();
    else if (initialDriver === 's3') $('#s3Fields').show();

    // Apply required state on load
    const $secret = $('input[name="secret"]');
    if ($secret.length && $secret.data('create-mode')) {
        $secret.prop('required', initialDriver === 's3');
    }

    // On change: toggle AND clear stale values
    $('#driver').on('change', toggleDriverFields);
});
</script>
