<?php

namespace Modules\Mailing\Observers;

use Illuminate\Support\Str;
use Modules\Mailing\Models\Template;

/**
 * TemplateObserver
 *
 * Handles automatic tracking and lifecycle management for Template models.
 * Migrated from Acelle Mail to Alsernet Mailing module.
 */
class TemplateObserver
{
    /**
     * Handle the Template "creating" event.
     */
    public function creating(Template $template): void
    {
        // Generate unique identifier if not set
        if (empty($template->uid)) {
            $template->uid = $this->generateUid();
        }

        // Set default metadata
        if (empty($template->metadata)) {
            $template->metadata = [];
        }

        // Set default shared status
        if (is_null($template->shared)) {
            $template->shared = false;
        }

        // Generate thumbnail if content is set
        if (! empty($template->content) && empty($template->thumbnail)) {
            $this->generateThumbnail($template);
        }
    }

    /**
     * Handle the Template "created" event.
     */
    public function created(Template $template): void
    {
        // Log template creation
        activity()
            ->performedOn($template)
            ->withProperties([
                'template_name' => $template->name,
                'template_uid' => $template->uid,
            ])
            ->log('Template created');

        // Clear template list cache
        $this->clearTemplateCache($template);
    }

    /**
     * Handle the Template "updating" event.
     */
    public function updating(Template $template): void
    {
        // Track name changes
        if ($template->isDirty('name')) {
            activity()
                ->performedOn($template)
                ->withProperties([
                    'old_name' => $template->getOriginal('name'),
                    'new_name' => $template->name,
                ])
                ->log('Template renamed');
        }

        // Regenerate thumbnail if content changed
        if ($template->isDirty('content')) {
            $this->generateThumbnail($template);

            // Invalidate compiled template cache
            $this->clearCompiledTemplateCache($template);
        }

        // Update updated_at timestamp
        $template->touch();
    }

    /**
     * Handle the Template "updated" event.
     */
    public function updated(Template $template): void
    {
        // Clear template cache
        $this->clearTemplateCache($template);

        // Log significant updates
        if ($template->wasChanged(['name', 'content', 'subject'])) {
            activity()
                ->performedOn($template)
                ->withProperties([
                    'changed_fields' => array_keys($template->getChanges()),
                ])
                ->log('Template updated');
        }

        // Update campaigns using this template
        $this->updateRelatedCampaigns($template);
    }

    /**
     * Handle the Template "deleting" event.
     */
    public function deleting(Template $template): void
    {
        // Check if template is being used
        if ($this->isTemplateInUse($template)) {
            // Log warning
            activity()
                ->performedOn($template)
                ->withProperties([
                    'template_name' => $template->name,
                    'warning' => 'Template deleted while in use',
                ])
                ->log('Template deleted');
        }

        // Log template deletion
        activity()
            ->performedOn($template)
            ->withProperties([
                'template_name' => $template->name,
                'campaigns_using' => $template->campaigns()->count(),
            ])
            ->log('Template deleted');
    }

    /**
     * Handle the Template "deleted" event.
     */
    public function deleted(Template $template): void
    {
        // Clear all template-related cache
        $this->clearTemplateCache($template);
        $this->clearCompiledTemplateCache($template);

        // Delete thumbnail file if exists
        $this->deleteThumbnail($template);
    }

    /**
     * Handle the Template "forceDeleted" event.
     */
    public function forceDeleted(Template $template): void
    {
        $this->clearTemplateCache($template);
        $this->deleteThumbnail($template);
    }

    /**
     * Generate a unique identifier for the template
     */
    protected function generateUid(): string
    {
        do {
            $uid = uniqid(Str::random(10));
        } while (Template::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Generate thumbnail for template
     */
    protected function generateThumbnail(Template $template): void
    {
        // This would typically use a service to generate a thumbnail
        // For now, we'll just set a placeholder
        if (class_exists('Modules\Mailing\Services\TemplateThumbnailService')) {
            try {
                $service = app('Modules\Mailing\Services\TemplateThumbnailService');
                $template->thumbnail = $service->generate($template->content);
            } catch (\Exception $e) {
                // Log error but don't fail
                logger()->error('Failed to generate template thumbnail', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete thumbnail file
     */
    protected function deleteThumbnail(Template $template): void
    {
        if (empty($template->thumbnail)) {
            return;
        }

        try {
            $disk = config('mailing.storage.disk', 'local');
            \Storage::disk($disk)->delete($template->thumbnail);
        } catch (\Exception $e) {
            logger()->error('Failed to delete template thumbnail', [
                'template_id' => $template->id,
                'thumbnail' => $template->thumbnail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if template is currently being used
     */
    protected function isTemplateInUse(Template $template): bool
    {
        // Check if model has campaigns relationship
        if (! method_exists($template, 'campaigns')) {
            return false;
        }

        return $template->campaigns()->exists();
    }

    /**
     * Update campaigns that use this template
     */
    protected function updateRelatedCampaigns(Template $template): void
    {
        if (! method_exists($template, 'campaigns')) {
            return;
        }

        // Queue job to update campaign cache
        if ($template->campaigns()->exists()) {
            dispatch(function () use ($template) {
                foreach ($template->campaigns as $campaign) {
                    cache()->forget("campaign.{$campaign->id}.rendered");
                    cache()->forget("campaign.{$campaign->id}");
                }
            })->afterResponse();
        }
    }

    /**
     * Clear template-related cache
     */
    protected function clearTemplateCache(Template $template): void
    {
        // Clear cache for this specific template
        cache()->forget("template.{$template->id}");
        cache()->forget("template.uid.{$template->uid}");

        // Clear template list cache
        cache()->forget('templates.list');
        cache()->forget('templates.count');

        // Clear user-specific template cache if available
        if ($template->user_id) {
            cache()->forget("user.{$template->user_id}.templates");
        }

        // Clear category cache if template has category
        if ($template->category_id) {
            cache()->forget("template.category.{$template->category_id}");
        }

        // Clear shared templates cache
        cache()->forget('templates.shared');
    }

    /**
     * Clear compiled template cache
     */
    protected function clearCompiledTemplateCache(Template $template): void
    {
        // Clear rendered/compiled versions
        cache()->forget("template.{$template->id}.compiled");
        cache()->forget("template.{$template->id}.rendered");
        cache()->forget("template.{$template->id}.preview");

        // Clear Twig cache if using Twig
        if (class_exists('Twig\Environment')) {
            try {
                $twigCache = storage_path('framework/cache/twig');
                if (file_exists($twigCache)) {
                    $files = glob($twigCache."/*_{$template->id}_*");
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }
    }
}
