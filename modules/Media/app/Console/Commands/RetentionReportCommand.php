<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Modules\Media\Models\MediaFile;

class RetentionReportCommand extends Command
{
    protected $signature = 'media:retention-report {--days=90 : Días sin modificaciones}';

    protected $description = 'Lista archivos sin modificaciones recientes (candidatos a archivar)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $stale = MediaFile::query()
            ->where('updated_at', '<', $threshold)
            ->selectRaw('user_id, count(*) as count, sum(size) as total_size')
            ->groupBy('user_id')
            ->orderByDesc('total_size')
            ->get();

        $this->info("Archivos sin cambios en {$days}+ días:");
        $this->table(
            ['User ID', 'Archivos', 'Tamaño'],
            $stale->map(fn ($r) => [
                $r->user_id,
                $r->count,
                round($r->total_size / 1024 / 1024, 2).' MB',
            ])->all()
        );

        $this->info(
            'Total: '.$stale->sum('count').' archivos, '.
            round($stale->sum('total_size') / 1024 / 1024 / 1024, 2).' GB'
        );

        return self::SUCCESS;
    }
}
