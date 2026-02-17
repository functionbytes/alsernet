<?php

namespace Modules\Page\Traits;

use Illuminate\Support\Facades\Auth;
use Modules\Page\Models\PageVersion;

trait Versionable
{
    /**
     * Boot the versionable trait for a model.
     */
    public static function bootVersionable()
    {
        // This method is called when the trait is booted
        // You can add event listeners here if needed
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all versions for this page.
     */
    public function versions()
    {
        return $this->hasMany(PageVersion::class, 'page_id')->latest();
    }

    /**
     * Get the latest version.
     */
    public function latestVersion()
    {
        return $this->hasOne(PageVersion::class, 'page_id')->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Version Management Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new version of the current page state.
     */
    public function createVersion(?int $userId = null): PageVersion
    {
        // Get the next version number
        $nextVersionNumber = $this->getNextVersionNumber();

        // Use authenticated user if no user ID provided
        $userId = $userId ?? Auth::id();

        // Create the version
        $version = $this->versions()->create([
            'version_number' => $nextVersionNumber,
            'title' => $this->title,
            'content' => $this->content,
            'description' => $this->description,
            'user_id' => $userId,
            'template' => $this->template,
            'status' => $this->status,
            'slug' => $this->slug,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
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
            'title' => $version->title,
            'content' => $version->content,
            'description' => $version->description,
            'template' => $version->template,
            'status' => $version->status,
            'slug' => $version->slug,
            'seo_title' => $version->seo_title,
            'seo_description' => $version->seo_description,
            'seo_keywords' => $version->seo_keywords,
        ]);

        return $this->save();
    }

    /**
     * Get the version history for this page.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVersionHistory(int $limit = 50)
    {
        return $this->versions()
            ->with('user')
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
            'title' => 'Título',
            'content' => 'Contenido',
            'description' => 'Descripción',
            'template' => 'Plantilla',
            'status' => 'Estado',
            'slug' => 'Slug',
            'seo_title' => 'SEO Título',
            'seo_description' => 'SEO Descripción',
            'seo_keywords' => 'SEO Palabras Clave',
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

        return $lastVersion ? $lastVersion->version_number + 1 : 1;
    }

    /**
     * Get the current version number.
     */
    public function getCurrentVersionNumber(): int
    {
        $lastVersion = $this->versions()->latest()->first();

        return $lastVersion ? $lastVersion->version_number : 0;
    }

    /**
     * Check if the page has versions.
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
    public function getVersionByNumber(int $versionNumber): ?PageVersion
    {
        return $this->versions()
            ->where('version_number', $versionNumber)
            ->first();
    }
}
