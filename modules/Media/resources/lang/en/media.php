<?php

return [
    'title' => 'Media Manager',
    'description' => 'Manage files and folders',
    'search' => 'Search files...',
    'upload' => 'Upload file',
    'upload_folder' => 'Upload folder',
    'new_folder' => 'New folder',
    'bulk_actions' => 'Bulk actions',

    'actions' => [
        'rename' => 'Rename',
        'move' => 'Move',
        'copy' => 'Copy',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'favorite_add' => 'Add to favorites',
        'favorite_remove' => 'Remove from favorites',
        'preview' => 'Preview',
        'share' => 'Share',
        'download' => 'Download',
        'edit_image' => 'Edit image',
        'versions' => 'Version history',
        'access_logs' => 'View access logs',
        'set_expiration' => 'Set expiration',
    ],

    'views' => [
        'all_media' => 'All files',
        'trash' => 'Trash',
        'favorites' => 'Favorites',
        'recent' => 'Recent',
    ],

    'duplicates' => [
        'title' => 'Duplicate detector',
        'mode_exact' => 'Exact hash (identical)',
        'mode_similar' => 'Visually similar (pHash)',
        'no_duplicates' => 'No duplicates found.',
    ],

    'quota' => [
        'title' => 'Storage',
        'used' => ':used / :total',
        'exceeded' => 'Quota exceeded',
    ],

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
        'quota_exceeded' => 'Storage quota exceeded',
        'invalid_file' => 'File type not allowed',
    ],

    'success' => [
        'uploaded' => 'File uploaded successfully.',
        'deleted' => 'File deleted',
        'restored' => 'File restored',
        'renamed' => 'File renamed successfully.',
        'moved' => 'File moved successfully.',
        'copied' => 'File copied',
        'shared' => 'Share link copied',
    ],

    'permissions' => [
        'manage' => 'Manage media',
        'upload' => 'Upload files',
        'delete' => 'Delete files',
        'view' => 'View media',
    ],
];
