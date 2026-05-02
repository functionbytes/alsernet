/**
 * File validation utilities
 */

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const ALLOWED_FILE_TYPES = [
    ...ALLOWED_IMAGE_TYPES,
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv',
];

export interface FileValidationResult {
    valid: boolean;
    error?: string;
}

/**
 * Validate file size and type
 */
export function validateFile(file: File): FileValidationResult {
    // Check file size
    if (file.size > MAX_FILE_SIZE) {
        return {
            valid: false,
            error: `File size must be less than ${MAX_FILE_SIZE / 1024 / 1024}MB`,
        };
    }

    // Check file type
    if (!ALLOWED_FILE_TYPES.includes(file.type)) {
        return {
            valid: false,
            error: 'File type not allowed',
        };
    }

    return { valid: true };
}

/**
 * Check if file is an image
 */
export function isImageFile(file: File): boolean {
    return ALLOWED_IMAGE_TYPES.includes(file.type);
}

/**
 * Format file size for display
 */
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Get file icon based on type
 */
export function getFileIcon(mimeType: string): string {
    if (ALLOWED_IMAGE_TYPES.includes(mimeType)) {
        return 'fa-image';
    }

    if (mimeType === 'application/pdf') {
        return 'fa-file-pdf';
    }

    if (mimeType.includes('word')) {
        return 'fa-file-word';
    }

    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return 'fa-file-excel';
    }

    if (mimeType.includes('text')) {
        return 'fa-file-alt';
    }

    return 'fa-file';
}
