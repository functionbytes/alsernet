<?php

namespace Modules\Media\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Services\MediaFileService;
use Modules\Media\Services\MediaFolderService;

class GdprDeleteCommand extends Command
{
    protected $signature = 'media:gdpr-delete
                            {user : ID o email del usuario}
                            {--confirmed : Omitir confirmacion interactiva}';

    protected $description = 'Elimina permanentemente todos los datos de Media de un usuario (GDPR right-to-be-forgotten)';

    public function handle(MediaFileService $fileService, MediaFolderService $folderService): int
    {
        $user = $this->resolveUser($this->argument('user'));

        if (! $user) {
            $this->error("Usuario no encontrado: {$this->argument('user')}");

            return self::FAILURE;
        }

        if (! $this->option('confirmed')) {
            $confirmed = $this->confirm(
                "ADVERTENCIA: Esto eliminara PERMANENTEMENTE todos los archivos, carpetas y favoritos de {$user->email}. Continuar?"
            );

            if (! $confirmed) {
                $this->info('Operacion cancelada.');

                return self::FAILURE;
            }
        }

        $this->info("Eliminando datos de Media de {$user->email}...");

        $favsDeleted = MediaFavorite::where('user_id', $user->id)->delete();

        $files = MediaFile::withTrashed()->where('user_id', $user->id)->get();

        foreach ($files as $file) {
            $fileService->forceDelete($file);
        }

        // Load folders ordered so parent folders come first; forceDelete handles descendants recursively
        $folders = MediaFolder::withTrashed()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->get();

        foreach ($folders as $folder) {
            if (MediaFolder::withTrashed()->find($folder->id)) {
                $folderService->forceDelete($folder);
            }
        }

        $this->purgeActivityLog($user->id);

        $this->info(
            "Completado: {$files->count()} archivos, {$folders->count()} carpetas raiz, {$favsDeleted} favoritos eliminados."
        );

        return self::SUCCESS;
    }

    private function purgeActivityLog(int $userId): void
    {
        try {
            DB::table('activity_log')
                ->where('causer_id', $userId)
                ->where(function ($q): void {
                    $q->where('log_name', 'like', 'media.%')
                        ->orWhere('subject_type', 'like', '%Media%');
                })
                ->delete();
        } catch (\Throwable) {
            // activity_log may not exist in all environments
        }
    }

    private function resolveUser(string $ref): ?User
    {
        return is_numeric($ref)
            ? User::find((int) $ref)
            : User::where('email', $ref)->first();
    }
}
