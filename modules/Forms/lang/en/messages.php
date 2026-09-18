<?php

return [
    'crud' => [
        'form_created' => 'Form created successfully. You can now add fields.',
        'form_updated' => 'Form updated successfully.',
        'form_deleted' => 'Form deleted successfully.',
        'form_delete_has_submissions' => 'Cannot delete the form because it has active submissions.',
        'form_cloned' => 'Form cloned successfully.',
        'form_clone_error' => 'Error cloning the form. Please try again.',
        'form_imported' => 'Form imported successfully.',
        'import_invalid_json' => 'The file does not contain valid JSON.',
        'import_invalid_format' => 'Invalid JSON file format.',
        'import_too_many_fields' => 'The file exceeds the limit of :max allowed fields.',
        'import_file_too_large' => 'The file exceeds the maximum allowed size.',
        'import_invalid_fields_section' => 'The JSON fields section is invalid.',
        'import_generic_error' => 'Error importing the form. Please verify the file and try again.',
    ],

    'category' => [
        'created' => 'Category created successfully.',
        'updated' => 'Category updated successfully.',
        'deleted' => 'Category deleted successfully.',
        'delete_has_active_forms' => 'Cannot delete the category because it has active forms.',
        'activated' => 'Category activated',
        'deactivated' => 'Category deactivated',
    ],

    'field' => [
        'created' => 'Field created successfully.',
        'updated' => 'Field updated successfully.',
        'deleted' => 'Field deleted successfully.',
        'duplicated' => 'Field duplicated successfully.',
        'not_found' => 'Field not found.',
        'delete_has_values' => 'Cannot delete the field because it has associated submission data.',
        'translations_generated' => 'Translations generated successfully.',
        'translations_with_errors' => 'Translation completed with some errors.',
        'deepl_not_configured' => 'DeepL is not configured. Add DEEPL_API_KEY.',
        'no_active_locales' => 'No active locales configured.',
    ],

    'submission' => [
        'deleted' => 'Submission deleted successfully.',
        'email_sent' => 'Email sent successfully',
        'form_expired' => 'This form has expired.',
        'form_full' => 'This form has reached the submission limit.',
        'no_permission' => 'You do not have permission to submit this form.',
        'incorrect_password' => 'Incorrect password.',
        'success_default' => 'Form submitted successfully.',
        'access_invalid' => 'This access link is invalid or has expired.',
        'note_no_permission_delete' => 'You do not have permission to delete this note.',
    ],

    'settings' => [
        'general_saved' => 'General settings saved.',
        'emails_saved' => 'Email settings saved.',
        'access_saved' => 'Access control saved.',
        'gdpr_saved' => 'GDPR settings saved.',
        'seo_saved' => 'SEO updated successfully.',
    ],

    'common' => [
        'invalid_preview_token' => 'Invalid preview token.',
        'qr_package_missing' => 'QR code package not installed.',
    ],
];
