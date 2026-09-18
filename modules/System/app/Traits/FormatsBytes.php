<?php

namespace Modules\System\Traits;

trait FormatsBytes
{
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / pow(1024, $i), $precision).' '.$units[$i];
    }
}
