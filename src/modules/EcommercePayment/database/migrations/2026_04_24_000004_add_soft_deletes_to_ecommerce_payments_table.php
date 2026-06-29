<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecommerce_payments') || Schema::hasColumn('ecommerce_payments', 'deleted_at')) {
            return;
        }

        Schema::table('ecommerce_payments', function (Blueprint $table): void {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ecommerce_payments', 'deleted_at')) {
            return;
        }

        Schema::table('ecommerce_payments', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
