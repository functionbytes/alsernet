<?php

namespace Modules\Media\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use ZipArchive;

class GdprExportCommand extends Command
{
    protected $signature = 'media:gdpr-export
                            {user : ID o email del usuario}
                            {--output=storage/app/gdpr : directorio destino relativo a base_path()}';

    protected $description = 'Exporta todos los datos de Media de un usuario (archivos + metadata + activity log) como ZIP GDPR-compliant';

    public function handle(): int
    {
        $user = $this->resolveUser($this->argument('user'));

        if (! $user) {
            $this->error("Usuario no encontrado: {$this->argument('user')}");

            return self::FAILURE;
        }

        $this->info("Exportando datos de Media para user {$user->id} ({$user->email})...");

        $outputDir = base_path(rtrim($this->option('output'), '/'));

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $zipPath = $outputDir.'/gdpr_media_user_'.$user->id.'_'.now()->format('Ymd_His').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear ZIP en {$zipPath}");

            return self::FAILURE;
        }

        $files = MediaFile::withTrashed()->where('user_id', $user->id)->get();

        $manifest = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name ?? null,
            'exported_at' => now()->toIso8601String(),
            'counts' => [
                'files' => $files->count(),
                'folders' => MediaFolder::withTrashed()->where('user_id', $user->id)->count(),
                'favorites' => MediaFavorite::where('user_id', $user->id)->count(),
            ],
        ];

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        [$fileMetadata, $included, $missing] = $this->addFiles($zip, $files);

        $zip->addFromString('files.json', json_encode($fileMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $folders = MediaFolder::withTrashed()->where('user_id', $user->id)->get()->map->toArray()->all();
        $zip->addFromString('folders.json', json_encode($folders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $favorites = MediaFavorite::where('user_id', $user->id)->get()->map->toArray()->all();
        $zip->addFromString('favorites.json', json_encode($favorites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->addFromString('activity_log.json', $this->buildActivityLog($user->id));
        $zip->addFromString('README.md', $this->buildReadme($user->email, $manifest['exported_at']));

        $zip->close();

        $sizeMb = round(filesize($zipPath) / 1024 / 1024, 2);

        $this->info("Exportacion completa: {$zipPath}");
        $this->line("  Tamano: {$sizeMb} MB | Archivos incluidos: {$included} | Missing: {$missing}");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, MediaFile>  $files
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int}
     */
    private function addFiles(ZipArchive $zip, $files): array
    {
        $metadata = [];
        $included = 0;
        $missing = 0;

        foreach ($files as $file) {
            $entry = [
                'id' => $file->id,
                'uid' => $file->uid,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'url' => $file->url,
                'folder_id' => $file->folder_id,
                'disk' => $file->disk,
                'visibility' => $file->visibility,
                'metadata' => $file->metadata,
                'file_hash' => $file->file_hash,
                'created_at' => $file->created_at?->toIso8601String(),
                'updated_at' => $file->updated_at?->toIso8601String(),
                'deleted_at' => $file->deleted_at?->toIso8601String(),
            ];

            $disk = Storage::disk($file->disk);

            if ($disk->exists($file->url)) {
                $exportPath = "files/{$file->id}_{$file->name}";
                $zip->addFile($disk->path($file->url), $exportPath);
                $entry['exported_path'] = $exportPath;
                $included++;
            } else {
                $entry['exported_path'] = null;
                $entry['note'] = 'Archivo fisico no disponible en storage';
                $missing++;
            }

            $metadata[] = $entry;
        }

        return [$metadata, $included, $missing];
    }

    private function buildActivityLog(int $userId): string
    {
        try {
            $rows = DB::table('activity_log')
                ->where('causer_id', $userId)
                ->where(function ($q): void {
                    $q->where('log_name', 'like', 'media.%')
                        ->orWhere('subject_type', 'like', '%Media%');
                })
                ->get();

            return json_encode($rows->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function buildReadme(string $email, string $exportedAt): string
    {
        return <<<MD
        # Exportacion GDPR - Datos de Media

        **Usuario**: {$email}
        **Exportado**: {$exportedAt}

        ## Contenido

        - `manifest.json`: Metadata de la exportacion y contadores
        - `files.json`: Lista completa de archivos con metadata
        - `folders.json`: Carpetas creadas por el usuario
        - `favorites.json`: Favoritos marcados
        - `activity_log.json`: Registro de actividad (spatie/activitylog)
        - `files/`: Copias fisicas de los archivos disponibles en storage

        ## Derecho al olvido

        Para solicitar la eliminacion total de estos datos, contactar al administrador.
        Comando relacionado: `media:gdpr-delete {user}`.
        MD;
    }

    private function resolveUser(string $ref): ?User
    {
        return is_numeric($ref)
            ? User::find((int) $ref)
            : User::where('email', $ref)->first();
    }
}
