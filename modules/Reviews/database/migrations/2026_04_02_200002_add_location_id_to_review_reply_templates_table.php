<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_reply_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('review_google_location_id')->nullable()->after('created_by');
            $table->foreign('review_google_location_id')
                ->references('id')
                ->on('review_google_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('review_reply_templates', function (Blueprint $table) {
            $table->dropForeign(['review_google_location_id']);
            $table->dropColumn('review_google_location_id');
        });
    }
};
