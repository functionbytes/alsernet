<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Modules\Media\Models\MediaFile;

class MediaStatsCommand extends Command
{
    protected $signature = 'media:stats {--user= : Filtrar por user_id} {--by=type : Agrupar por campo}';

    protected $description = 'Estadísticas agregadas de uso del módulo Media';

    public function handle(): int
    {
        $this->info('Estadísticas Media');

        $query = MediaFile::query()
            ->when($this->option('user'), fn ($q) => $q->where('user_id', $this->option('user')));

        $total = $query->count();
        $totalSize = (int) $query->sum('size');

        $this->table(['Métrica', 'Valor'], [
            ['Total archivos', number_format($total)],
            ['Tamaño total', $this->formatBytes($totalSize)],
            ['Promedio/archivo', $total > 0 ? $this->formatBytes($totalSize / $total) : '0'],
        ]);

        $byType = MediaFile::query()
            ->selectRaw('type, count(*) as count, sum(size) as total_size')
            ->when($this->option('user'), fn ($q) => $q->where('user_id', $this->option('user')))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        $this->newLine();
        $this->info('Por tipo:');
        $this->table(
            ['Tipo', 'Archivos', 'Tamaño'],
            $byType->map(fn ($r) => [$r->type, $r->count, $this->formatBytes((int) $r->total_size)])->all()
        );

        $topUsers = MediaFile::query()
            ->selectRaw('user_id, count(*) as count, sum(size) as total_size')
            ->groupBy('user_id')
            ->orderByDesc('total_size')
            ->limit(5)
            ->get();

        $this->newLine();
        $this->info('Top 5 usuarios por uso:');
        $this->table(
            ['User ID', 'Archivos', 'Tamaño'],
            $topUsers->map(fn ($r) => [$r->user_id, $r->count, $this->formatBytes((int) $r->total_size)])->all()
        );

        $growth = MediaFile::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as day, count(*) as count, sum(size) as bytes')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $this->newLine();
        $this->info('Crecimiento últimos 30 días:');
        $this->line('Archivos nuevos: '.$growth->sum('count'));
        $this->line('Tamaño agregado: '.$this->formatBytes((int) $growth->sum('bytes')));

        return self::SUCCESS;
    }

    private function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
