<?php

namespace Modules\Document\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Document Type Configuration Helper
 *
 * Reads shared JSON config for document types
 * Used by both Laravel and external integrations (PrestaShop)
 */
class DocumentTypeConfig
{
    private static ?array $config = null;

    /**
     * Get all document types configuration
     */
    public static function all(): array
    {
        return self::load()['types'] ?? [];
    }

    /**
     * Get configuration for a specific document type
     */
    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /**
     * Get required documents for a type
     */
    public static function getRequiredDocuments(string $type): array
    {
        return self::get($type)['required_documents'] ?? [];
    }

    /**
     * Get label for a document type
     */
    public static function getLabel(string $type): string
    {
        return self::get($type)['label'] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get document labels (for individual document keys)
     */
    public static function getDocumentLabels(): array
    {
        return self::load()['document_labels'] ?? [];
    }

    /**
     * Get label for a specific document key
     */
    public static function getDocumentLabel(string $key): string
    {
        $labels = self::getDocumentLabels();

        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Check if a document type exists
     */
    public static function exists(string $type): bool
    {
        return isset(self::all()[$type]);
    }

    /**
     * Get all available document type keys
     */
    public static function getTypes(): array
    {
        return array_keys(self::all());
    }

    /**
     * Load configuration from JSON file
     */
    private static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        return Cache::remember('document_types_config', 3600, function () {
            $path = module_path('Document', 'config/document_types.json');

            if (! file_exists($path)) {
                return ['types' => [], 'document_labels' => []];
            }

            $json = file_get_contents($path);
            $config = json_decode($json, true);

            self::$config = $config ?? ['types' => [], 'document_labels' => []];

            return self::$config;
        });
    }

    /**
     * Clear cached configuration
     */
    public static function clearCache(): void
    {
        self::$config = null;
        Cache::forget('document_types_config');
    }

    /**
     * Export for PrestaShop integration
     * Returns minimal array structure for API consumption
     */
    public static function exportForApi(string $type): array
    {
        $config = self::get($type);

        if (! $config) {
            return [
                'type' => $type,
                'label' => ucfirst(str_replace('_', ' ', $type)),
                'required_documents' => [],
            ];
        }

        // Map required documents to labels
        $requiredWithLabels = [];
        foreach ($config['required_documents'] as $docKey) {
            $requiredWithLabels[$docKey] = self::getDocumentLabel($docKey);
        }

        return [
            'type' => $type,
            'label' => $config['label'],
            'required_documents' => $requiredWithLabels,
        ];
    }
}
