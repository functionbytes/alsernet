$(document).ready(function() {
    $('#duplicate_languages_btn').click(function() {
        // Obtener el valor del idioma español (ID 1)
        var spanishLangId = 1;

        // Recorremos los campos multilingües
        var fields = ['url', 'id_video', 'title'];

        fields.forEach(function(field) {
            // Obtener el valor en español (por ID de idioma)
            var spanishValue = $('#' + field + '_' + spanishLangId).val();

            // Copiar el valor de español a los demás idiomas
            $('[id^="' + field + '_"]').each(function() {
                var fieldId = $(this).attr('id'); // Obtener el ID del campo (por ejemplo: url_2)
                var langId = fieldId.split('_')[1]; // Extraer el ID del idioma del ID del campo

                // Solo duplicamos si el idioma no es español
                if (langId != spanishLangId) {
                    $(this).val(spanishValue); // Asignar el valor de español al campo
                }
            });
        });
    });
});
