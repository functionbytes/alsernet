<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentNote;
use Modules\Document\Services\DocumentActionService;

/**
 * Handles document notes operations
 * Extracted from DocumentsController for better separation of concerns
 */
class DocumentNoteController extends Controller
{
    /**
     * Add a new note to the document
     */
    public function addNote(Request $request, $uid): JsonResponse
    {
        try {
            // Verificar permiso para agregar notas
            if (! auth()->user()->canDocument('add-notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para agregar notas.',
                ], 403);
            }

            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $adminId = auth()->check() ? auth()->id() : 0;

            // Agregar la nota usando el servicio
            $note = DocumentActionService::addNote(
                $document,
                $adminId,
                $request->input('content'),
                true // is_internal
            );

            // Cargar relación de autor
            $note->load('author');

            return response()->json([
                'success' => true,
                'message' => 'Nota agregada correctamente',
                'note' => [
                    'id' => $note->id,
                    'content' => $note->content,
                    'created_at' => $note->created_at,
                    'author' => $note->author ? [
                        'firstname' => $note->author->firstname ?? '',
                        'lastname' => $note->author->lastname ?? '',
                        'full_name' => $note->author->full_name ?? '',
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar nota: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing note
     */
    public function updateNote(Request $request, $uid, $noteId): JsonResponse
    {
        try {
            // Verificar permiso para editar notas
            if (! auth()->user()->canDocument('edit-notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar notas.',
                ], 403);
            }

            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $note = DocumentNote::find($noteId);

            if (! $note || $note->document_id !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota no encontrada.',
                ], 404);
            }

            $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $note->update([
                'content' => $request->input('content'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar nota: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a note
     */
    public function deleteNote(Request $request, $uid, $noteId): JsonResponse
    {
        try {
            // Verificar permiso para eliminar notas
            if (! auth()->user()->canDocument('delete-notes')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar notas.',
                ], 403);
            }

            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $note = DocumentNote::find($noteId);

            if (! $note || $note->document_id !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota no encontrada.',
                ], 404);
            }

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nota: '.$e->getMessage(),
            ], 500);
        }
    }
}
