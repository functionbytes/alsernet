<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_currencies', function (Blueprint $table): void {
            $table->decimal('exchange_rate', 15, 6)->default(1)->change();
        });

        Schema::table('ecommerce_taxes', function (Blueprint $table): void {
            $table->decimal('percentage', 8, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_currencies', function (Blueprint $table): void {
            $table->double('exchange_rate')->default(1)->change();
        });

        Schema::table('ecommerce_taxes', function (Blueprint $table): void {
            $table->float('percentage')->nullable()->change();
        });
    }
};
