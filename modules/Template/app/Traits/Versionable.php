<?php

namespace Modules\Template\Traits;

use Illuminate\Support\Facades\Auth;
use Modules\Template\Models\TemplateVersion;

trait Versionable
{
    /**
     * Boot the versionable trait for a model.
     */
    public static function bootVersionable()
    {
        // This method is called when the trait is booted
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all versions for this template.
     */
    public function versions()
    {
        return $this->hasMany(TemplateVersion::class, 'template_id')->latest();
    }

    /**
     * Get the latest version.
     */
    public function latestVersion()
    {
        return $this->hasOne(TemplateVersion::class, 'template_id')->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Version Management Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new version of the current template state.
     */
    public function createVersion(?int $userId = null): TemplateVersion
    {
        // Get the next version number
        $nextVersionNumber = $this->getNextVersionNumber();

        // Use authenticated user if no user ID provided
        $userId = $userId ?? Auth::id();

        // Create the version
        $version = $this->versions()->create([
            'version' => $nextVersionNumber,
            'name' => $this->name,
            'content' => $this->content,
            'description' => $this->description,
            'created_by' => $userId,
            'slug' => $this->slug,
            'author' => $this->author,
            'changed_fields' => json_encode([]),
        ]);

        return $version;
    }

    /**
     * Restore a specific version.
     *
     * @throws \Exception
     */
    public function restoreVersion(int $versionId): bool
    {
        $version = $this->versions()->findOrFail($versionId);

        // Create a version before restoring (backup current state)
        $this->createVersion(Auth::id());

        // Restore the version data
        $this->fill([
            'name' => $version->name,
            'content' => $version->content,
            'description' => $version->description,
            'slug' => $version->slug,
            'author' => $version->author,
        ]);

        return $this->save();
    }

    /**
     * Get the version history for this template.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVersionHistory(int $limit = 50)
    {
        return $this->versions()
            ->with('createdBy')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Compare two versions and return differences.
     */
    public function compareVersions(int $versionId1, int $versionId2): array
    {
        $version1 = $this->versions()->findOrFail($versionId1);
        $version2 = $this->versions()->findOrFail($versionId2);

        $differences = [];

        // Compare fields
        $fieldsToCompare = [
            'name' => 'Nombre',
            'content' => 'Contenido',
            'description' => 'Descripción',
            'slug' => 'Slug',
            'author' => 'Autor',
        ];

        foreach ($fieldsToCompare as $field => $label) {
            $value1 = $version1->$field;
            $value2 = $version2->$field;

            if ($value1 !== $value2) {
                $differences[$field] = [
                    'label' => $label,
                    'old_value' => $value1,
                    'new_value' => $value2,
                    'changed' => true,
                ];
            } else {
                $differences[$field] = [
                    'label' => $label,
                    'old_value' => $value1,
                    'new_value' => $value2,
                    'changed' => false,
                ];
            }
        }

        return [
            'version1' => $version1,
            'version2' => $version2,
            'differences' => $differences,
            'has_changes' => collect($differences)->where('changed', true)->isNotEmpty(),
        ];
    }

    /**
     * Get the next version number.
     */
    protected function getNextVersionNumber(): int
    {
        $lastVersion = $this->versions()->latest()->first();

        return $lastVersion ? $lastVersion->version + 1 : 1;
    }

    /**
     * Get the current version number.
     */
    public function getCurrentVersionNumber(): int
    {
        $lastVersion = $this->versions()->latest()->first();

        return $lastVersion ? $lastVersion->version : 0;
    }

    /**
     * Check if the template has versions.
     */
    public function hasVersions(): bool
    {
        return $this->versions()->exists();
    }

    /**
     * Get total number of versions.
     */
    public function getTotalVersions(): int
    {
        return $this->versions()->count();
    }

    /**
     * Delete old versions, keeping only the most recent N versions.
     *
     * @return int Number of deleted versions
     */
    public function pruneVersions(int $keep = 10): int
    {
        $versionsToDelete = $this->versions()
            ->latest()
            ->skip($keep)
            ->pluck('id');

        if ($versionsToDelete->isEmpty()) {
            return 0;
        }

        return $this->versions()
            ->whereIn('id', $versionsToDelete)
            ->delete();
    }

    /**
     * Get a specific version by version number.
     */
    public function getVersionByNumber(int $versionNumber): ?TemplateVersion
    {
        return $this->versions()
            ->where('version', $versionNumber)
            ->first();
    }
}
