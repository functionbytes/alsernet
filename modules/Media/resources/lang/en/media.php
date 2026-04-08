<?php

return [
    'title' => 'Media Manager',
    'description' => 'Manage files and folders',

    'files' => [
        'title' => 'Files',
        'upload' => 'Upload file',
        'download' => 'Download',
        'delete' => 'Delete',
        'rename' => 'Rename',
        'move' => 'Move',
        'copy' => 'Copy',
        'preview' => 'Preview',
        'details' => 'Details',
        'size' => 'Size',
        'type' => 'Type',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'no_files' => 'No files found',
    ],

    'folders' => [
        'title' => 'Folders',
        'create' => 'New folder',
        'rename' => 'Rename folder',
        'delete' => 'Delete folder',
        'empty' => 'Empty folder',
        'no_folders' => 'No folders found',
    ],

    'stats' => [
        'total_files' => 'Total files',
        'total_size' => 'Total size',
        'images' => 'Images',
        'documents' => 'Documents',
        'videos' => 'Videos',
        'other' => 'Other',
    ],

    'errors' => [
        'upload_failed' => 'File upload failed.',
        'delete_failed' => 'Could not delete the file.',
        'not_found' => 'File not found.',
        'too_large' => 'File exceeds the maximum allowed size.',
        'invalid_type' => 'File type is not allowed.',
        'folder_not_empty' => 'Cannot delete a non-empty folder.',
    ],

    'success' => [
        'uploaded' => 'File uploaded successfully.',
        'deleted' => 'File deleted successfully.',
        'renamed' => 'File renamed successfully.',
        'moved' => 'File moved successfully.',
    ],

    'permissions' => [
        'manage' => 'Manage media',
        'upload' => 'Upload files',
        'delete' => 'Delete files',
        'view' => 'View media',
    ],
];
