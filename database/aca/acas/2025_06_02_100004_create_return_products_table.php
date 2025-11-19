<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->onDelete('set null')->after('category_id');
            $table->boolean('has_warranty')->default(true)->after('manufacturer_id');
            $table->integer('default_warranty_months')->default(12)->after('has_warranty');
            $table->json('warranty_types_available')->nullable()->after('default_warranty_months'); // IDs de tipos disponibles
            $table->boolean('requires_serial_number')->default(false)->after('warranty_types_available');
            $table->boolean('auto_warranty_registration')->default(false)->after('requires_serial_number');
            $table->json('warranty_metadata')->nullable()->after('auto_warranty_registration'); // Info específica de garantía
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufacturer_id');
            $table->dropColumn([
                'has_warranty',
                'default_warranty_months',
                'warranty_types_available',
                'requires_serial_number',
                'auto_warranty_registration',
                'warranty_metadata',
            ]);
        });
    }
};
