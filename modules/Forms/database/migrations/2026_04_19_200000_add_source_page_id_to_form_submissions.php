<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('form_submissions', 'source_page_id')) {
                $table->unsignedBigInteger('source_page_id')->nullable()->after('referrer_url');
                $table->index(['form_id', 'source_page_id'], 'form_submissions_form_source_page_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'source_page_id')) {
                $table->dropIndex('form_submissions_form_source_page_index');
                $table->dropColumn('source_page_id');
            }
        });
    }
};
