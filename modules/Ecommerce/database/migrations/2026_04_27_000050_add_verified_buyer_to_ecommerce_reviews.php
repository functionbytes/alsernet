<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('ecommerce_reviews', 'is_verified_buyer')) {
                $table->boolean('is_verified_buyer')->default(false)->after('star');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_reviews', function (Blueprint $table) {
            $table->dropColumn('is_verified_buyer');
        });
    }
};
