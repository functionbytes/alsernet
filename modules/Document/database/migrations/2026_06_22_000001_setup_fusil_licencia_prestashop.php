<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tag FUSIL en Prestashop (id_lang=1 = español)
        DB::connection('prestashop')->statement(
            "INSERT IGNORE INTO aalv_tag (id_lang, name) VALUES (1, 'FUSIL')"
        );

        $tagRow = DB::connection('prestashop')
            ->selectOne("SELECT id_tag FROM aalv_tag WHERE name = 'FUSIL' AND id_lang = 1");

        if ($tagRow) {
            $fusilTagId = $tagRow->id_tag;

            // 2. Asociar el tag FUSIL a todos los productos cuyo nombre contiene "fusil"
            DB::connection('prestashop')->statement("
                INSERT IGNORE INTO aalv_product_tag (id_product, id_tag, id_lang)
                SELECT DISTINCT pl.id_product, {$fusilTagId}, 1
                FROM aalv_product_lang pl
                WHERE pl.id_lang = 1 AND LOWER(pl.name) LIKE '%fusil%'
            ");
        }

        // 3. Marcar LICENCIA-B (id_product=77645) como producto virtual (sin gastos de envío)
        DB::connection('prestashop')->statement(
            "UPDATE aalv_product SET is_virtual = 1 WHERE id_product = 77645"
        );

        // 4. Crear el DocumentType "licencias" en la base de datos principal
        $existingType = DB::table('document_types')->where('slug', 'licencias')->first();

        if (! $existingType) {
            DB::table('document_types')->insert([
                'uid'              => (string) Str::uuid(),
                'slug'             => 'licencias',
                'label'            => 'Licencias Federativas',
                'description'      => 'Licencia federativa obligatoria para la compra de fusiles de pesca submarina',
                'icon'             => 'fas fa-id-card',
                'color'            => '#dc3545',
                'is_active'        => 1,
                'sort_order'       => 6,
                'sla_multiplier'   => 1.00,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Eliminar el DocumentType "licencias"
        DB::table('document_types')->where('slug', 'licencias')->delete();

        // Revertir LICENCIA-B a no virtual
        DB::connection('prestashop')->statement(
            "UPDATE aalv_product SET is_virtual = 0 WHERE id_product = 77645"
        );

        // Eliminar asignaciones del tag FUSIL y el tag mismo
        $tagRow = DB::connection('prestashop')
            ->selectOne("SELECT id_tag FROM aalv_tag WHERE name = 'FUSIL' AND id_lang = 1");

        if ($tagRow) {
            DB::connection('prestashop')
                ->table('aalv_product_tag')
                ->where('id_tag', $tagRow->id_tag)
                ->delete();

            DB::connection('prestashop')
                ->table('aalv_tag')
                ->where('id_tag', $tagRow->id_tag)
                ->delete();
        }
    }
};
