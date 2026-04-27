<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
function initWysiwyg(selector) {
    if (typeof tinymce === 'undefined') return;
    tinymce.init({
        selector: selector,
        height: 400,
        menubar: false,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code fullscreen',
        content_css: false,
        skin: 'oxide',
        promotion: false,
        branding: false,
        license_key: 'gpl',
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initWysiwyg('textarea.wysiwyg-editor');
});
</script>
