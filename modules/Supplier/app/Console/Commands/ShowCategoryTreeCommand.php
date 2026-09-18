<?php

namespace Modules\Supplier\Console\Commands;

use Illuminate\Console\Command;
use Modules\Supplier\Helpers\CategoryTreeHelper;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;

class ShowCategoryTreeCommand extends Command
{
    protected $signature = 'supplier:category-tree
                            {--sport= : Filter by sport ERP ID}
                            {--search= : Search term}
                            {--counts : Show product counts}
                            {--all : Show inactive items too}
                            {--stats : Show statistics}';

    protected $description = 'Display the product category hierarchy tree (Sport > Category)';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('   ÁRBOL DE CATEGORÍAS DE PRODUCTOS - MÓDULO SUPPLIER');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->option('stats')) {
            return $this->showStatistics();
        }

        if ($search = $this->option('search')) {
            return $this->searchTree($search);
        }

        return $this->showTree();
    }

    protected function showTree(): int
    {
        $activeOnly = ! $this->option('all');
        $withCounts = $this->option('counts');
        $sportFilter = $this->option('sport');

        $query = Sport::with(['categories']);

        if ($activeOnly) {
            $query->where('available', true);
        }

        if ($sportFilter) {
            $query->where('erp_id', $sportFilter);
        }

        $sports = $query->get();

        if ($sports->isEmpty()) {
            $this->warn('No se encontraron deportes/categorías.');
            $this->info('Ejecuta primero: php artisan supplier:sync-sports');

            return Command::SUCCESS;
        }

        foreach ($sports as $sport) {
            $this->renderSport($sport, $activeOnly, $withCounts);
        }

        $this->newLine();
        $this->info("Total deportes: {$sports->count()}");

        return Command::SUCCESS;
    }

    protected function renderSport(Sport $sport, bool $activeOnly, bool $withCounts): void
    {
        $status = $sport->available ? '' : ' [INACTIVO]';
        $this->line("<fg=cyan;options=bold>{$sport->name}</> (ERP ID: {$sport->erp_id}){$status}");

        $categories = $activeOnly
            ? $sport->categories->where('available', true)
            : $sport->categories;

        foreach ($categories->values() as $index => $category) {
            $isLast = $index === $categories->count() - 1;
            $connector = $isLast ? '└─' : '├─';
            $status = $category->available ? '' : ' [INACTIVO]';
            $count = $withCounts ? " ({$category->products()->count()} productos)" : '';
            $this->line("  <fg=yellow>{$connector} {$category->name}</> (ERP: {$category->erp_id}){$status}{$count}");
        }

        $this->newLine();
    }

    protected function searchTree(string $term): int
    {
        $this->info("Buscando: '{$term}'");
        $this->newLine();

        $results = CategoryTreeHelper::search($term);
        $totalResults = collect($results)->sum(fn ($items) => $items->count());

        if ($totalResults === 0) {
            $this->warn('No se encontraron resultados.');

            return Command::SUCCESS;
        }

        $labels = ['sports' => 'Deportes', 'categories' => 'Categorías'];

        foreach ($results as $type => $items) {
            if ($items->isEmpty()) {
                continue;
            }

            $this->line("<fg=cyan;options=bold>{$labels[$type]}:</>");

            foreach ($items as $item) {
                $this->line("  - {$item->name} (ERP ID: {$item->erp_id})");
            }

            $this->newLine();
        }

        $this->info("Total resultados: {$totalResults}");

        return Command::SUCCESS;
    }

    protected function showStatistics(): int
    {
        $this->info('Estadísticas de categorías:');
        $this->newLine();

        $sports = Sport::count();
        $activeSports = Sport::where('available', true)->count();
        $categories = Category::count();
        $activeCategories = Category::where('available', true)->count();

        $this->table(
            ['Nivel', 'Total', 'Activos', 'Inactivos'],
            [
                ['Deportes', $sports, $activeSports, $sports - $activeSports],
                ['Categorías', $categories, $activeCategories, $categories - $activeCategories],
            ]
        );

        return Command::SUCCESS;
    }
}
