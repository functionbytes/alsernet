<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HelpdeskSocial\Exports\SocialCommentsExport;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'helpdesk-social:export')]
class ExportSocialCommentsCommand extends Command
{
    protected $signature = 'helpdesk-social:export
                            {--since= : Fecha de inicio (Y-m-d)}
                            {--until= : Fecha de fin (Y-m-d)}
                            {--platform= : Filtrar por plataforma}
                            {--status= : Filtrar por status}
                            {--format=csv : Formato de salida (csv, xlsx)}
                            {--output= : Ruta del archivo}';

    protected $description = 'Exporta comentarios sociales a CSV/XLSX';

    public function handle(): int
    {
        $since = $this->option('since');
        $until = $this->option('until');
        $platform = $this->option('platform');
        $status = $this->option('status');
        $format = $this->option('format');

        $output = $this->option('output') ?? storage_path(
            'app/exports/social-comments-'.now()->format('Y-m-d_His').'.'.$format
        );

        $this->info('Exportando comentarios...');

        try {
            $export = new SocialCommentsExport(
                since: $since,
                until: $until,
                platform: $platform,
                status: $status,
            );

            Excel::store($export, $output, 'local', match ($format) {
                'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                default => \Maatwebsite\Excel\Excel::CSV,
            });

            $this->info("Exportación completada: {$output}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error al exportar: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
