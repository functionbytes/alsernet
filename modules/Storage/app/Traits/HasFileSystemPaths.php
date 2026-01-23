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
    public const BASE_DIR = 'app/customers'; // storage/customers/000000

    public const ATTACHMENTS_DIR = 'home/attachments';  // storage/customers/000000/home/files

    public const TEMPLATES_DIR = 'home/templates';  // storage/customers/000000/home/files

    public const PRODUCT_DIR = 'home/inventaries';

    public const LOGS_DIR = 'home/logs/';

    /**
     * Get user's base path
     */
    public function getBasePath($path = null): string
    {
        $base = storage_path(join_paths(self::BASE_DIR, $this->uid)); // storage/app/customers/000000/

        if (! File::exists($base)) {
            File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Get user's templates path
     */
    public function getTemplatesPath($path = null): string
    {
        $base = $this->getBasePath(self::TEMPLATES_DIR);

        if (! File::exists($base)) {
            File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Get user's products path
     */
    public function getProductsPath($path = null): string
    {
        $base = $this->getBasePath(self::PRODUCT_DIR);

        if (! File::exists($base)) {
            File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Get user's attachments path
     */
    public function getAttachmentsPath($path = null): string
    {
        $base = $this->getBasePath(self::ATTACHMENTS_DIR);

        if (! File::exists($base)) {
            File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Get user's log path
     */
    public function getLogPath($path = null): string
    {
        $base = $this->getBasePath(self::LOGS_DIR);

        if (! File::exists($base)) {
            File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    /**
     * Get lock path
     */
    public function getLockPath($path): string
    {
        return $this->user->getLockPath($path);
    }
}
