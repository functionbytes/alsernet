<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Services\DocumentEmailService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Handles all file operations for documents
 * Extracted from DocumentsController for better separation of concerns
 */
class DocumentFileController extends Controller
{
    /**
     * Upload document file
     */
    public function upload(Request $request): JsonResponse
    {
        // Validación de archivo
        $request->validate([
            'uid' => 'required|string',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $document = Document::findByUid($request->uid);

        if (! $document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        $type = 'documents';

        $document->clearMediaCollection($type);

        // Sanitizar nombre de archivo
        $file = $request->file('file');
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        $media = $document->addMediaFromRequest('file')
            ->usingFileName($sanitizedName)
            ->toMediaCollection($type);

        // Asegurar que el archivo es accesible al servidor web
        $mediaPath = $media->getPath();
        if (file_exists($mediaPath)) {
            @chmod($mediaPath, 0644);
        }
        $mediaDir = dirname($mediaPath);
        if (is_dir($mediaDir)) {
            @chmod($mediaDir, 0755);
        }

        // Procesar upload: cambiar status a "received" y enviar confirmación
        app(DocumentEmailService::class)->processDocumentUpload($document);

        return response()->json([
            'status' => 'success',
            'statement_id' => $document->id,
            'media' => [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'file' => $media->file_name,
                'size' => $media->size,
                'path' => $media->getUrl(),
            ],
        ]);
    }

    /**
     * Get file from document
     */
    public function getFile($documentUid, $type): JsonResponse
    {
        // Validar tipo de colección permitido
        $allowedTypes = ['documents', 'additional_attachments'];
        if (! in_array($type, $allowedTypes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de archivo no permitido.',
            ], 400);
        }

        $document = Document::findByUid($documentUid);

        if (! $document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        $media = $document->getMedia($type)->first();

        if (! $media) {
            return response()->json([]);
        }

        return response()->json([[
            'id' => $media->id,
            'uuid' => $media->uuid,
            'file' => $media->file_name,
            'size' => $media->size,
            'path' => $media->getUrl(),
        ]]);
    }

    /**
     * Delete file
     */
    public function deleteFile($id): JsonResponse
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Verificar que el media pertenece a un documento
        if ($media->model_type !== Document::class) {
            return response()->json([
                'status' => 'error',
                'message' => 'Acceso no autorizado.',
            ], 403);
        }

        // Verificar que el documento existe
        $document = Document::find($media->model_id);
        if (! $document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Documento asociado no encontrado.',
            ], 404);
        }

        $media->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Admin upload document on behalf of customer
     */
    public function adminUploadDocument(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Validar archivos subidos
            $request->validate([
                'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
                'types.*' => 'required|string|in:dni_frontal,dni_trasera,licencia,hunting_license,firearms_license,certificate,otros',
            ]);

            $files = $request->file('files', []);
            $types = $request->input('types', []);

            if (count($files) !== count($types)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad de archivos no coincide con la cantidad de tipos.',
                ], 400);
            }

            $uploadedFiles = [];

            foreach ($files as $index => $file) {
                $type = $types[$index];

                // Sanitizar nombre de archivo
                $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

                // Guardar en colección correspondiente
                $collectionName = 'additional_attachments';

                $media = $document->addMedia($file)
                    ->usingFileName($sanitizedName)
                    ->withCustomProperties(['upload_type' => $type])
                    ->toMediaCollection($collectionName);

                $uploadedFiles[] = [
                    'id' => $media->id,
                    'type' => $type,
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Documentos subidos correctamente por el administrador',
                'files' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir documentos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload additional attachment
     */
    public function uploadAdditionalAttachment(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $request->validate([
                'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
                'type' => 'required|string|in:dni_frontal,dni_trasera,licencia,hunting_license,firearms_license,certificate,otros',
            ]);

            $file = $request->file('file');
            $type = $request->input('type');

            // Sanitizar nombre de archivo
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

            $media = $document->addMedia($file)
                ->usingFileName($sanitizedName)
                ->withCustomProperties(['upload_type' => $type])
                ->toMediaCollection('additional_attachments');

            return response()->json([
                'success' => true,
                'message' => 'Archivo adicional subido correctamente',
                'file' => [
                    'id' => $media->id,
                    'type' => $type,
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir archivo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get additional attachments
     */
    public function getAdditionalAttachments($uid): JsonResponse
    {
        $document = Document::findByUid($uid);

        if (! $document) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        $attachments = $document->getMedia('additional_attachments')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'size' => $media->size,
                'type' => $media->getCustomProperty('upload_type'),
                'url' => $media->getUrl(),
                'created_at' => $media->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Delete additional attachment
     */
    public function deleteAdditionalAttachment(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $mediaId = $request->input('media_id');

            $media = Media::find($mediaId);

            if (! $media || $media->model_id !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado.',
                ], 404);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available network disks
     */
    public function getNetworkDisks(): JsonResponse
    {
        // Obtener discos de red configurados desde settings
        $networkDisks = Setting::get('network_disks', '[]');
        $disks = json_decode($networkDisks, true) ?: [];

        return response()->json([
            'success' => true,
            'disks' => $disks,
        ]);
    }

    /**
     * Upload to network drive
     */
    public function uploadToNetworkDrive(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $request->validate([
                'disk_path' => 'required|string',
            ]);

            $diskPath = $request->input('disk_path');

            // Obtener archivos del documento
            $media = $document->getMedia('documents')->first();

            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivos para subir.',
                ], 400);
            }

            $sourcePath = $media->getPath();
            $destinationPath = $diskPath.'/'.$media->file_name;

            // Copiar archivo al disco de red
            if (copy($sourcePath, $destinationPath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo copiado al disco de red exitosamente',
                    'path' => $destinationPath,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al copiar archivo al disco de red',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Prepare image for PDF (convert to valid PNG)
     * Private helper method
     */
    private function prepareImageForPDF($srcPath, $destDir = __DIR__.'/../../../../../storage/app/imageDocs')
    {
        if (! file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = basename($srcPath);
        $destPath = $destDir.'/'.$filename;

        // Si ya existe y es reciente (menos de 1 hora), reutilizar
        if (file_exists($destPath) && (time() - filemtime($destPath)) < 3600) {
            return $destPath;
        }

        $imageType = @exif_imagetype($srcPath);

        if ($imageType === IMAGETYPE_PNG) {
            // Ya es PNG, solo copiar
            copy($srcPath, $destPath);
        } elseif ($imageType === IMAGETYPE_JPEG) {
            // Convertir JPEG a PNG
            $image = imagecreatefromjpeg($srcPath);
            imagepng($image, $destPath);
            imagedestroy($image);
        } else {
            // Tipo no soportado, copiar como está
            copy($srcPath, $destPath);
        }

        return $destPath;
    }
}
