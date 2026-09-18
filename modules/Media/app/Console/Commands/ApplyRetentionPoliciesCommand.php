<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Modules\Media\Models\MediaFile;

class ApplyRetentionPoliciesCommand extends Command
{
    protected $signature = 'media:apply-retention {--dry-run : Solo muestra los archivos afectados sin modificarlos}';

    protected $description = 'Aplica políticas de retención por tipo, marca archivos viejos con expires_at';

    public function handle(): int
    {
        if (! config('media.retention.enabled', false)) {
            $this->info('Retention policies disabled.');

            return self::SUCCESS;
        }

        $policies = config('media.retention.policies', []);
        $total = 0;

        foreach ($policies as $type => $duration) {
            if ($duration === null) {
                continue;
            }

            $threshold = now()->sub($duration);

            $query = MediaFile::query()
                ->where('type', $type)
                ->where('created_at', '<', $threshold)
                ->whereNull('expires_at');

            $count = $query->count();

            if (! $this->option('dry-run')) {
                $query->update(['expires_at' => now()->addDays(30)]);
            }

            $this->info("{$type}: {$count} archivos marcados (expiran en 30 días)");
            $total += $count;
        }

        $this->info("Total: {$total} archivos bajo política de retención");

        return self::SUCCESS;
    }
}
