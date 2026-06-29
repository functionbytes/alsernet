<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Configuracion\FamiliaCl;
use Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl;
use Modules\Erp\Models\Oracle\Otros\CategoriaCl;
use Modules\Erp\Models\Oracle\Otros\DeporteCl;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;
use Modules\Supplier\Entities\SupplierErpCategory;
use Modules\Supplier\Entities\SupplierFamily;
use Modules\Supplier\Entities\SupplierGroup;
use Modules\Supplier\Entities\SupplierSport;
use Modules\Supplier\Entities\SupplierSubfamily;

class SyncCategories extends Command
{
    protected $signature = 'erp:sync-categories
        {--full : Full sync (ignore timestamps)}
        {--chunk=100 : Chunk size for processing}
        {--dry-run : Run without making changes}';

    protected $description = 'Sync ERP category hierarchy (Sports > Categories > Families > Subfamilies > Groups)';

    private $stats = [
        'sports' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        'categories' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        'families' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        'subfamilies' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        'groups' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
    ];

    public function handle()
    {
        $this->info('🔄 Starting ERP Category Sync...');
        $this->newLine();

        $fullSync = $this->option('full');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        try {
            // Level 1: Sports (DeporteCl)
            $this->syncSports($fullSync, $chunkSize, $dryRun);

            // Level 2: Categories (CategoriaCl)
            $this->syncCategories($fullSync, $chunkSize, $dryRun);

            // Level 3: Families (FamiliaCl)
            $this->syncFamilies($fullSync, $chunkSize, $dryRun);

            // Level 4: Subfamilies (SubfamiliaCl)
            $this->syncSubfamilies($fullSync, $chunkSize, $dryRun);

            // Level 5: Groups (GrupoCl)
            $this->syncGroups($fullSync, $chunkSize, $dryRun);

            $this->printSummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Fatal error: '.$e->getMessage());
            \Log::error('ERP Category Sync Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    private function syncSports(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📊 Level 1: Syncing Sports (DeporteCl)...');

        $query = DeporteCl::query();

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierSports')
                    ->orWhereHas('supplierSports', function ($sq) {
                        $sq->whereColumn('deporte_cl.fmodificacion', '>', 'supplier_sports.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($sports) use ($bar, $dryRun) {
            foreach ($sports as $sport) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($sport) {
                            $this->syncSport($sport);
                        });
                    } else {
                        $this->stats['sports']['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['sports']['errors']++;
                    \Log::error('Error syncing sport', [
                        'erp_id' => $sport->iddeporte_cl,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncSport($oracleSport): void
    {
        $checksum = md5(json_encode([
            'descripcion' => $oracleSport->descripcion,
            'desc_corta' => $oracleSport->desc_corta,
            'estado' => $oracleSport->estado,
        ]));

        $existing = SupplierSport::where('erp_sport_id', $oracleSport->iddeporte_cl)->first();

        $data = [
            'erp_sport_id' => $oracleSport->iddeporte_cl,
            'code' => $oracleSport->prefijo,
            'name' => $oracleSport->descripcion,
            'short_name' => $oracleSport->desc_corta,
            'is_active' => (bool) $oracleSport->estado,
            'metadata' => [
                'checksum' => $checksum,
                'prefijo_segunda_mano' => $oracleSport->prefijo_segunda_mano,
                'codigo_segunda_mano_automatico' => $oracleSport->codigo_segunda_mano_automatico,
            ],
            'erp_created_at' => $oracleSport->fcreacion,
            'erp_updated_at' => $oracleSport->fmodificacion,
            'erp_deleted_at' => $oracleSport->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['sports']['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['sports']['skipped']++;
            }
        } else {
            SupplierSport::create($data);
            $this->stats['sports']['created']++;
        }
    }

    private function syncCategories(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📊 Level 2: Syncing Categories (CategoriaCl)...');

        $query = CategoriaCl::with('deporteCl');

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierErpCategories')
                    ->orWhereHas('supplierErpCategories', function ($sq) {
                        $sq->whereColumn('categoria_cl.fmodificacion', '>', 'supplier_erp_categories.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($categories) use ($bar, $dryRun) {
            foreach ($categories as $category) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($category) {
                            $this->syncCategory($category);
                        });
                    } else {
                        $this->stats['categories']['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['categories']['errors']++;
                    \Log::error('Error syncing category', [
                        'erp_id' => $category->idcategoria_cl,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncCategory($oracleCategory): void
    {
        $sport = SupplierSport::where('erp_sport_id', $oracleCategory->iddeporte_cl)->first();

        if (! $sport) {
            throw new \Exception("Parent sport not found: {$oracleCategory->iddeporte_cl}");
        }

        $checksum = md5(json_encode([
            'descripcion' => $oracleCategory->descripcion,
            'desc_corta' => $oracleCategory->desc_corta,
            'estado' => $oracleCategory->estado,
        ]));

        $existing = SupplierErpCategory::where('erp_category_id', $oracleCategory->idcategoria_cl)->first();

        $data = [
            'erp_category_id' => $oracleCategory->idcategoria_cl,
            'sport_id' => $sport->id,
            'erp_sport_id' => $oracleCategory->iddeporte_cl,
            'name' => $oracleCategory->descripcion,
            'short_name' => $oracleCategory->desc_corta,
            'is_active' => (bool) $oracleCategory->estado,
            'show_in_stock_report' => (bool) $oracleCategory->aparece_inf_stock,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleCategory->fcreacion,
            'erp_updated_at' => $oracleCategory->fmodificacion,
            'erp_deleted_at' => $oracleCategory->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['categories']['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['categories']['skipped']++;
            }
        } else {
            SupplierErpCategory::create($data);
            $this->stats['categories']['created']++;
        }
    }

    private function syncFamilies(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📊 Level 3: Syncing Families (FamiliaCl)...');

        $query = FamiliaCl::with('categoriaCl');

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierFamilies')
                    ->orWhereHas('supplierFamilies', function ($sq) {
                        $sq->whereColumn('familia_cl.fmodificacion', '>', 'supplier_families.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($families) use ($bar, $dryRun) {
            foreach ($families as $family) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($family) {
                            $this->syncFamily($family);
                        });
                    } else {
                        $this->stats['families']['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['families']['errors']++;
                    \Log::error('Error syncing family', [
                        'erp_id' => $family->idfamilia_cl,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncFamily($oracleFamily): void
    {
        $category = SupplierErpCategory::where('erp_category_id', $oracleFamily->idcategoria_cl)->first();

        if (! $category) {
            throw new \Exception("Parent category not found: {$oracleFamily->idcategoria_cl}");
        }

        $checksum = md5(json_encode([
            'descripcion' => $oracleFamily->descripcion,
            'desc_corta' => $oracleFamily->desc_corta,
            'estado' => $oracleFamily->estado,
        ]));

        $existing = SupplierFamily::where('erp_family_id', $oracleFamily->idfamilia_cl)->first();

        $data = [
            'erp_family_id' => $oracleFamily->idfamilia_cl,
            'category_id' => $category->id,
            'erp_category_id' => $oracleFamily->idcategoria_cl,
            'name' => $oracleFamily->descripcion,
            'short_name' => $oracleFamily->desc_corta,
            'is_active' => (bool) $oracleFamily->estado,
            'is_weapons' => (bool) $oracleFamily->sonarmas,
            'is_blank_weapons' => (bool) $oracleFamily->sonarmasfogueo,
            'is_cartridges' => (bool) $oracleFamily->soncartuchos,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleFamily->fcreacion,
            'erp_updated_at' => $oracleFamily->fmodificacion,
            'erp_deleted_at' => $oracleFamily->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['families']['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['families']['skipped']++;
            }
        } else {
            SupplierFamily::create($data);
            $this->stats['families']['created']++;
        }
    }

    private function syncSubfamilies(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📊 Level 4: Syncing Subfamilies (SubfamiliaCl)...');

        $query = SubfamiliaCl::with('familiaCl');

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierSubfamilies')
                    ->orWhereHas('supplierSubfamilies', function ($sq) {
                        $sq->whereColumn('subfamilia_cl.fmodificacion', '>', 'supplier_subfamilies.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($subfamilies) use ($bar, $dryRun) {
            foreach ($subfamilies as $subfamily) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($subfamily) {
                            $this->syncSubfamily($subfamily);
                        });
                    } else {
                        $this->stats['subfamilies']['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['subfamilies']['errors']++;
                    \Log::error('Error syncing subfamily', [
                        'erp_id' => $subfamily->idsubfamilia_cl,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncSubfamily($oracleSubfamily): void
    {
        $family = SupplierFamily::where('erp_family_id', $oracleSubfamily->idfamilia_cl)->first();

        if (! $family) {
            throw new \Exception("Parent family not found: {$oracleSubfamily->idfamilia_cl}");
        }

        $checksum = md5(json_encode([
            'descripcion' => $oracleSubfamily->descripcion,
            'desc_corta' => $oracleSubfamily->desc_corta,
            'estado' => $oracleSubfamily->estado,
        ]));

        $existing = SupplierSubfamily::where('erp_subfamily_id', $oracleSubfamily->idsubfamilia_cl)->first();

        $data = [
            'erp_subfamily_id' => $oracleSubfamily->idsubfamilia_cl,
            'family_id' => $family->id,
            'erp_family_id' => $oracleSubfamily->idfamilia_cl,
            'name' => $oracleSubfamily->descripcion,
            'short_name' => $oracleSubfamily->desc_corta,
            'is_active' => (bool) $oracleSubfamily->estado,
            'is_ammunition' => (bool) $oracleSubfamily->escartucheria,
            'is_metal_ammunition' => (bool) $oracleSubfamily->esmunicionmetalica,
            'show_lots' => (bool) $oracleSubfamily->mostrarlotes,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleSubfamily->fcreacion,
            'erp_updated_at' => $oracleSubfamily->fmodificacion,
            'erp_deleted_at' => $oracleSubfamily->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['subfamilies']['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['subfamilies']['skipped']++;
            }
        } else {
            SupplierSubfamily::create($data);
            $this->stats['subfamilies']['created']++;
        }
    }

    private function syncGroups(bool $fullSync, int $chunkSize, bool $dryRun): void
    {
        $this->info('📊 Level 5: Syncing Groups (GrupoCl)...');

        $query = GrupoCl::with('subfamiliaCl');

        if (! $fullSync) {
            $query->where(function ($q) {
                $q->doesntHave('supplierGroups')
                    ->orWhereHas('supplierGroups', function ($sq) {
                        $sq->whereColumn('grupo_cl.fmodificacion', '>', 'supplier_groups.last_synced_at');
                    });
            });
        }

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($chunkSize, function ($groups) use ($bar, $dryRun) {
            foreach ($groups as $group) {
                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($group) {
                            $this->syncGroup($group);
                        });
                    } else {
                        $this->stats['groups']['skipped']++;
                    }
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->stats['groups']['errors']++;
                    \Log::error('Error syncing group', [
                        'erp_id' => $group->idgrupo_cl,
                        'error' => $e->getMessage(),
                    ]);
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function syncGroup($oracleGroup): void
    {
        $subfamily = SupplierSubfamily::where('erp_subfamily_id', $oracleGroup->idsubfamilia_cl)->first();

        if (! $subfamily) {
            throw new \Exception("Parent subfamily not found: {$oracleGroup->idsubfamilia_cl}");
        }

        $checksum = md5(json_encode([
            'descripcion' => $oracleGroup->descripcion,
            'desc_corta' => $oracleGroup->desc_corta,
            'estado' => $oracleGroup->estado,
        ]));

        $existing = SupplierGroup::where('erp_group_id', $oracleGroup->idgrupo_cl)->first();

        $data = [
            'erp_group_id' => $oracleGroup->idgrupo_cl,
            'subfamily_id' => $subfamily->id,
            'erp_subfamily_id' => $oracleGroup->idsubfamilia_cl,
            'name' => $oracleGroup->descripcion,
            'short_name' => $oracleGroup->desc_corta,
            'prefix' => $oracleGroup->prefijo,
            'next_number' => $oracleGroup->prox_num,
            'is_active' => (bool) $oracleGroup->estado,
            'exclude_store_request' => (bool) $oracleGroup->excluir_pedir_a_tienda,
            'require_serial_number' => (bool) $oracleGroup->pedir_numero_serie,
            'intrastat' => $oracleGroup->intrastat,
            'metadata' => ['checksum' => $checksum],
            'erp_created_at' => $oracleGroup->fcreacion,
            'erp_updated_at' => $oracleGroup->fmodificacion,
            'erp_deleted_at' => $oracleGroup->fbaja,
            'last_synced_at' => now(),
        ];

        if ($existing) {
            $oldChecksum = $existing->metadata['checksum'] ?? null;
            if ($oldChecksum !== $checksum) {
                $existing->update($data);
                $this->stats['groups']['updated']++;
            } else {
                $existing->touch('last_synced_at');
                $this->stats['groups']['skipped']++;
            }
        } else {
            SupplierGroup::create($data);
            $this->stats['groups']['created']++;
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('📊 SYNC SUMMARY - ERP CATEGORIES');
        $this->info('═══════════════════════════════════════════════════');

        foreach ($this->stats as $level => $counts) {
            $this->line(strtoupper($level).':');
            $this->line("  ✅ Created:  {$counts['created']}");
            $this->line("  🔄 Updated:  {$counts['updated']}");
            $this->line("  ⏭️  Skipped:  {$counts['skipped']}");
            $this->line("  ❌ Errors:   {$counts['errors']}");
            $this->newLine();
        }

        $this->info('═══════════════════════════════════════════════════');
    }
}
