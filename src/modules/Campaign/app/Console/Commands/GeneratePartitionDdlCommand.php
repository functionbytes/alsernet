<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;

/**
 * Genera el DDL de particionamiento para las tablas de logs en MariaDB.
 * Se usa en producción para convertir tablas masivas a particionadas.
 */
class GeneratePartitionDdlCommand extends Command
{
    protected $signature = 'campaign:generate-partition-ddl
                            {--tables=campaign_tracking_logs,campaign_open_logs,campaign_click_logs}
                            {--start=2026-01-01}
                            {--end=2027-01-01}
                            {--engine=InnoDB}';

    protected $description = 'Genera DDL para particionar tablas de logs por RANGE(created_at) mensual';

    public function handle(): int
    {
        $tables = explode(',', $this->option('tables'));
        $start = new \DateTime($this->option('start'));
        $end = new \DateTime($this->option('end'));
        $engine = $this->option('engine');

        foreach ($tables as $table) {
            $this->info("-- DDL para tabla: {$table}");
            $this->newLine();

            $this->line("ALTER TABLE `{$table}` ENGINE={$engine};");
            $this->newLine();
            $this->line("ALTER TABLE `{$table}` PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (");

            $current = clone $start;
            $first = true;
            while ($current < $end) {
                $next = clone $current;
                $next->modify('first day of next month');
                $partitionName = 'p'.$current->format('Y').'_'.$current->format('m');
                $limit = $next->format('Y').$next->format('m');
                $prefix = $first ? '  ' : ', ';
                $this->line("{$prefix}PARTITION {$partitionName} VALUES LESS THAN ({$limit})");
                $current = $next;
                $first = false;
            }
            $this->line(', PARTITION pmax VALUES LESS THAN MAXVALUE');
            $this->line(');');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
