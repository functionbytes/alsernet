<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->string('uid', 26)->nullable()->unique()->after('id');
        });

        DB::table('supplier_products')->whereNull('uid')->orderBy('id')->each(function ($row) {
            DB::table('supplier_products')
                ->where('id', $row->id)
                ->update(['uid' => (string) Str::ulid()]);
        });

        Schema::table('supplier_products', function (Blueprint $table) {
            $table->string('uid', 26)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
