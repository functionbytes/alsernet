<?php

namespace Modules\Storage\Traits;

use Illuminate\Support\Facades\File;

/**
 * Trait HasFileSystemPaths
 *
 * Gestiona las rutas del sistema de archivos específicas del usuario
 * para almacenar templates, attachments, productos, logs, etc.
 */
trait HasFileSystemPaths
{
    public const BASE_DIR = 'app/customers';

    public const ATTACHMENTS_DIR = 'home/attachments';

    public const TEMPLATES_DIR = 'home/templates';

    public const PRODUCT_DIR = 'home/inventaries';

    public const LOGS_DIR = 'home/logs/';

    /**
     * Get user's base path
     */
    public function getBasePath(?string $path = null): string
    {
        return $this->ensureDirectory(
            storage_path(join_paths(self::BASE_DIR, $this->uid)),
            $path
        );
    }

    /**
     * Get user's templates path
     */
    public function getTemplatesPath(?string $path = null): string
    {
        return $this->ensureDirectory($this->getBasePath(self::TEMPLATES_DIR), $path);
    }

    /**
     * Get user's products path
     */
    public function getProductsPath(?string $path = null): string
    {
        return $this->ensureDirectory($this->getBasePath(self::PRODUCT_DIR), $path);
    }

    /**
     * Get user's attachments path
     */
    public function getAttachmentsPath(?string $path = null): string
    {
        return $this->ensureDirectory($this->getBasePath(self::ATTACHMENTS_DIR), $path);
    }

    /**
     * Get user's log path
     */
    public function getLogPath(?string $path = null): string
    {
        return $this->ensureDirectory($this->getBasePath(self::LOGS_DIR), $path);
    }

    /**
     * Get lock path
     */
    public function getLockPath(string $path): string
    {
        return $this->user->getLockPath($path);
    }

    /**
     * Ensure the given directory exists, then return the joined path.
     */
    private function ensureDirectory(string $base, ?string $append): string
    {
        if ($append !== null && str_contains($append, '..')) {
            throw new \InvalidArgumentException("Path traversal not allowed in storage path: {$append}");
        }

        if (! File::exists($base)) {
            File::makeDirectory($base, 0755, true, true);
        }

        return join_paths($base, $append);
    }
}
