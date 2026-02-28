<?php

namespace Modules\Page\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageAutoSaveService;

class PageAutoSaveController extends Controller
{
    public function __construct(
        private readonly PageAutoSaveService $autoSaveService
    ) {}

    /**
     * Guardar automáticamente cambios de página
     * PATCH /api/v1/pages/{page}/auto-save
     */
    public function save(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:draft,published,pending'],
            'template' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'header_style' => ['nullable', 'string'],
            'seo_noindex' => ['nullable', 'boolean'],
        ]);

        $draft = $this->autoSaveService->saveAutoSave($page, auth()->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado automáticamente',
            'data' => [
                'saved_at' => $draft->saved_at->diffForHumans(),
                'expires_at' => $draft->expires_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Obtener borrador actual
     * GET /api/v1/pages/{page}/auto-save
     */
    public function getDraft(Page $page): JsonResponse
    {
        $this->authorize('view', $page);

        $draft = $this->autoSaveService->getLatestDraft($page, auth()->user());

        if (! $draft) {
            return response()->json([
                'success' => false,
                'message' => 'No hay borrador guardado',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Borrador encontrado',
            'data' => [
                'id' => $draft->id,
                'title' => $draft->data['title'] ?? null,
                'slug' => $draft->data['slug'] ?? null,
                'content' => $draft->content,
                'description' => $draft->data['description'] ?? null,
                'status' => $draft->status,
                'saved_at' => $draft->saved_at->diffForHumans(),
                'expires_at' => $draft->expires_at?->format('Y-m-d H:i:s'),
                'can_restore' => $draft->expires_at?->isFuture() ?? false,
            ],
        ]);
    }

    /**
     * Restaurar desde borrador
     * POST /api/v1/pages/{page}/auto-save/restore
     */
    public function restore(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $draft = $this->autoSaveService->getLatestDraft($page, auth()->user());

        if (! $draft || ! $draft->expires_at || $draft->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'El borrador ha expirado',
            ], 410);
        }

        $restored = $this->autoSaveService->restoreFromDraft($page, auth()->user(), $draft);

        return response()->json([
            'success' => true,
            'message' => 'Página restaurada desde borrador',
            'data' => [
                'id' => $restored->id,
                'title' => $restored->title,
                'updated_at' => $restored->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Descartar borrador
     * DELETE /api/v1/pages/{page}/auto-save
     */
    public function discard(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $this->autoSaveService->deleteDraft($page, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Borrador eliminado',
        ]);
    }
}
