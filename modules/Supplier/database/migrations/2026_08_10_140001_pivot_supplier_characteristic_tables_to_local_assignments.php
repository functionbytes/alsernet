<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier_model_characteristics', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('supplier_model_characteristics', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id')
                ->comment('ID Oracle w_caracteristicas_orden — null hasta que se envía al ERP con éxito');
            $table->foreignId('product_id')->nullable()->after('erp_id')
                ->constrained('supplier_products')->nullOnDelete();
            $table->string('sync_status', 20)->default('pending')->after('estado')
                ->comment('pending|synced|error');
            $table->text('erp_response')->nullable()->after('sync_status');
            $table->foreignId('created_by')->nullable()->after('erp_response')
                ->constrained('users')->nullOnDelete();

            $table->index('product_id');
            $table->index('sync_status');
        });

        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->after('id')
                ->comment('ID Oracle w_perfiles_prod — null hasta que se envía al ERP con éxito');
            $table->foreignId('product_attribute_id')->nullable()->after('erp_id')
                ->constrained('supplier_product_attributes')->nullOnDelete();
            $table->string('sync_status', 20)->default('pending')->after('estado')
                ->comment('pending|synced|error');
            $table->text('erp_response')->nullable()->after('sync_status');
            $table->foreignId('created_by')->nullable()->after('erp_response')
                ->constrained('users')->nullOnDelete();

            $table->index('product_attribute_id');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_model_characteristics', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['product_id', 'sync_status', 'erp_response', 'created_by']);
        });

        Schema::table('supplier_model_characteristics', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('supplier_model_characteristics', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->unique()->after('id')
                ->comment('ID Oracle w_caracteristicas_orden');
        });

        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->dropForeign(['product_attribute_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['product_attribute_id', 'sync_status', 'erp_response', 'created_by']);
        });

        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->dropUnique(['erp_id']);
            $table->dropColumn('erp_id');
        });

        Schema::table('supplier_variant_characteristics', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_id')->unique()->after('id')
                ->comment('ID Oracle w_perfiles_prod');
        });
    }
};
