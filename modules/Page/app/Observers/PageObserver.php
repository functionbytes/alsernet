<?php

namespace Modules\Page\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\Page\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     * Create initial version when page is created.
     *
     * @return void
     */
    public function created(Page $page)
    {
        // Create initial version (v1) after page creation
        $page->createVersion(Auth::id());
    }

    /**
     * Handle the Page "updating" event.
     * Create a version before the page is updated.
     *
     * @return void
     */
    public function updating(Page $page)
    {
        // Check if any versionable fields have changed
        if ($this->hasVersionableChanges($page)) {
            // Create a version snapshot before the update
            $page->createVersion(Auth::id());
        }
    }

    /**
     * Check if any versionable fields have changed.
     */
    protected function hasVersionableChanges(Page $page): bool
    {
        $versionableFields = [
            'title',
            'content',
            'description',
            'template',
            'status',
            'slug',
            'seo_title',
            'seo_description',
            'seo_keywords',
        ];

        foreach ($versionableFields as $field) {
            if ($page->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle the Page "deleted" event.
     *
     * @return void
     */
    public function deleted(Page $page)
    {
        // Optionally, you can create a version when a page is soft-deleted
        // This would preserve the state at deletion time
    }

    /**
     * Handle the Page "restored" event.
     *
     * @return void
     */
    public function restored(Page $page)
    {
        // Create a version when page is restored from soft delete
        $page->createVersion(Auth::id());
    }
}
