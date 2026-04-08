<?php

namespace Modules\Page\Services;

use App\Models\User;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageAutoSave;

class PageAutoSaveService
{
    public function __construct(private readonly PageService $pageService) {}

    /**
     * Guardar cambios automáticamente.
     * Content is sanitized before storage so restored drafts are always clean.
     */
    public function saveAutoSave(Page $page, User $user, array $data): PageAutoSave
    {
        $validData = array_filter([
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'content' => isset($data['content'])
                ? $this->pageService->sanitizeContent($data['content'])
                : null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,
            'template' => $data['template'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_keywords' => $data['seo_keywords'] ?? null,
            'header_style' => $data['header_style'] ?? null,
            'seo_noindex' => $data['seo_noindex'] ?? null,
        ]);

        return PageAutoSave::createOrUpdateDraft($page, $user, $validData);
    }

    /**
     * Obtener el borrador más reciente
     */
    public function getLatestDraft(Page $page, User $user): ?PageAutoSave
    {
        return PageAutoSave::getLatestDraft($page, $user);
    }

    /**
     * Restaurar desde un borrador automático
     */
    public function restoreFromDraft(Page $page, User $user, PageAutoSave $draft): Page
    {
        $page->update($draft->data);

        // Eliminar el borrador después de restaurar
        $draft->delete();

        return $page->fresh();
    }

    /**
     * Eliminar borrador de un usuario
     */
    public function deleteDraft(Page $page, User $user): void
    {
        PageAutoSave::where('page_id', $page->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Limpiar todos los borradores expirados
     */
    public function cleanExpiredDrafts(): int
    {
        return PageAutoSave::cleanExpired();
    }

    /**
     * Obtener información de borrador para enviar al frontend
     */
    public function getDraftInfo(Page $page, User $user): ?array
    {
        $draft = $this->getLatestDraft($page, $user);

        if (! $draft) {
            return null;
        }

        return [
            'has_draft' => true,
            'saved_at' => $draft->saved_at->diffForHumans(),
            'expires_at' => $draft->expires_at?->format('Y-m-d H:i:s'),
            'user_name' => $user->full_name ?? $user->name,
        ];
    }
}
