<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\DocumentConfiguration;
use Modules\Document\Entities\DocumentType;

class DocumentTypeService
{
    public static function getRequiredDocuments(string $documentType): array
    {
        $config = DocumentConfiguration::getByType($documentType);

        if ($config && $config->required_documents) {
            return $config->required_documents;
        }

        return self::getDefaultDocuments($documentType);
    }

    public static function getDefaultDocuments(string $documentType): array
    {
        // Completely dynamic - NO hardcoded defaults
        // Get defaults from DocumentType entity if it exists
        $docType = DocumentType::where('slug', $documentType)->first();

        if ($docType && ! empty($docType->required_documents)) {
            return $docType->required_documents;
        }

        // If no DocumentType found or no required_documents configured, return empty array
        // The system must rely on DocumentType configuration, not hardcoded defaults
        return [];
    }

    public static function getMissingDocuments(string $documentType, array $uploadedDocs): array
    {
        $requiredDocs = self::getRequiredDocuments($documentType);
        $missingDocs = [];

        foreach ($requiredDocs as $docKey => $docLabel) {
            if (! isset($uploadedDocs[$docKey])) {
                $missingDocs[$docKey] = $docLabel;
            }
        }

        return $missingDocs;
    }

    public static function allDocumentsUploaded(string $documentType, array $uploadedDocs): bool
    {
        $requiredDocs = self::getRequiredDocuments($documentType);

        foreach ($requiredDocs as $docKey => $docLabel) {
            if (! isset($uploadedDocs[$docKey])) {
                return false;
            }
        }

        return true;
    }
}
