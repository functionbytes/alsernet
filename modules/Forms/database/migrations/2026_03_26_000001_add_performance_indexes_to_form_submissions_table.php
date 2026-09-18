<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // is_starred was missing — used in stats queries and filter
            $table->index('is_starred');

            // Composite index for the most common stats query pattern (form + boolean flags)
            $table->index(['form_id', 'is_starred'], 'form_submissions_form_id_is_starred_index');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['is_starred']);
            $table->dropIndex('form_submissions_form_id_is_starred_index');
        });
    }
};
