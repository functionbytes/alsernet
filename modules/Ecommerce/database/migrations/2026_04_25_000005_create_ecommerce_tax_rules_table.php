<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop geographic columns no longer needed
        Schema::table('ecommerce_tax_rules', function (Blueprint $table): void {
            $table->dropColumn(['country', 'state', 'city', 'zip_code']);
        });

        // Add new range-based columns and change percentage type
        Schema::table('ecommerce_tax_rules', function (Blueprint $table): void {
            $table->string('name')->after('tax_id')->default('');
            $table->string('basis', 20)->after('name')->default('price');
            $table->decimal('price_from', 15, 2)->after('basis')->default(0);
            $table->decimal('price_to', 15, 2)->nullable()->after('price_from');
            $table->decimal('percentage', 5, 2)->default(0)->change();
            $table->integer('order')->after('percentage')->default(0);

            $table->index('tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_tax_rules', function (Blueprint $table): void {
            $table->dropIndex(['tax_id']);
            $table->dropColumn(['name', 'basis', 'price_from', 'price_to', 'order']);
            $table->float('percentage', 8, 6)->default(0)->change();

            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('zip_code', 20)->nullable();
        });
    }
};
