<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_products_categories', function (Blueprint $table) {
            $table->boolean('allow_returns')->default(true)->after('description');
            $table->integer('default_return_days')->default(30)->after('allow_returns');
            $table->text('return_policy_text')->nullable()->after('default_return_days');
            $table->json('return_restrictions')->nullable()->after('return_policy_text');
        });
    }

    public function down(): void
    {
        Schema::table('return_products_categories', function (Blueprint $table) {
            $table->dropColumn([
                'allow_returns',
                'default_return_days',
                'return_policy_text',
                'return_restrictions'
            ]);
        });
    }
};
