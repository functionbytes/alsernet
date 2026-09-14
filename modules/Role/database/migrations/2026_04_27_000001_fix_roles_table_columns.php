<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'name')) {
                $table->string('name')->after('id');
            }
            if (! Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        if (Schema::hasColumn('roles', 'name') && Schema::hasColumn('roles', 'guard_name')) {
            $indexExists = collect(Schema::getIndexes('roles'))
                ->contains(fn ($index) => $index['name'] === 'roles_name_guard_name_unique');

            if (! $indexExists) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique(['name', 'guard_name']);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $indexExists = collect(Schema::getIndexes('roles'))
                ->contains(fn ($index) => $index['name'] === 'roles_name_guard_name_unique');

            if ($indexExists) {
                $table->dropUnique('roles_name_guard_name_unique');
            }

            if (Schema::hasColumn('roles', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
            if (Schema::hasColumn('roles', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
