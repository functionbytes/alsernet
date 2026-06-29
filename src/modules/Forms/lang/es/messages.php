<?php

return [
    'crud' => [
        'form_created' => 'Formulario creado correctamente. Ahora puedes añadir campos.',
        'form_updated' => 'Formulario actualizado correctamente.',
        'form_deleted' => 'Formulario eliminado correctamente.',
        'form_delete_has_submissions' => 'No se puede eliminar el formulario porque tiene envíos activos.',
        'form_cloned' => 'Formulario clonado correctamente.',
        'form_clone_error' => 'Error al clonar el formulario. Por favor, inténtalo de nuevo.',
        'form_imported' => 'Formulario importado correctamente.',
        'import_invalid_json' => 'El archivo no contiene JSON válido.',
        'import_invalid_format' => 'Formato de archivo JSON inválido.',
        'import_too_many_fields' => 'El archivo supera el límite de :max campos permitidos.',
        'import_file_too_large' => 'El archivo excede el tamaño máximo permitido.',
        'import_invalid_fields_section' => 'La sección de campos del JSON no es válida.',
        'import_generic_error' => 'Error al importar el formulario. Por favor, verifica el archivo e inténtalo de nuevo.',
    ],

    'category' => [
        'created' => 'Categoría creada correctamente.',
        'updated' => 'Categoría actualizada correctamente.',
        'deleted' => 'Categoría eliminada correctamente.',
        'delete_has_active_forms' => 'No se puede eliminar la categoría porque tiene formularios activos.',
        'activated' => 'Categoría activada',
        'deactivated' => 'Categoría desactivada',
    ],

    'field' => [
        'created' => 'Campo creado correctamente.',
        'updated' => 'Campo actualizado correctamente.',
        'deleted' => 'Campo eliminado correctamente.',
        'duplicated' => 'Campo duplicado correctamente.',
        'not_found' => 'Campo no encontrado.',
        'delete_has_values' => 'No se puede eliminar el campo porque tiene datos de envíos asociados.',
        'translations_generated' => 'Traducciones generadas correctamente.',
        'translations_with_errors' => 'Traducción completada con algunos errores.',
        'deepl_not_configured' => 'DeepL no está configurado. Añade DEEPL_API_KEY.',
        'no_active_locales' => 'No hay idiomas activos configurados.',
    ],

    'submission' => [
        'deleted' => 'Envío eliminado correctamente.',
        'email_sent' => 'Email enviado correctamente',
        'form_expired' => 'Este formulario ha expirado.',
        'form_full' => 'Este formulario ha alcanzado el límite de envíos.',
        'no_permission' => 'No tienes permiso para enviar este formulario.',
        'incorrect_password' => 'Contraseña incorrecta.',
        'success_default' => 'Formulario enviado correctamente.',
        'access_invalid' => 'Este enlace de acceso no es válido o ha expirado.',
        'note_no_permission_delete' => 'No tienes permiso para eliminar esta nota.',
    ],

    'settings' => [
        'general_saved' => 'Configuración general guardada.',
        'emails_saved' => 'Configuración de emails guardada.',
        'access_saved' => 'Control de acceso guardado.',
        'gdpr_saved' => 'Configuración GDPR guardada.',
        'seo_saved' => 'SEO actualizado correctamente.',
    ],

    'common' => [
        'invalid_preview_token' => 'Token de preview inválido.',
        'qr_package_missing' => 'QR code package not installed.',
    ],
];
