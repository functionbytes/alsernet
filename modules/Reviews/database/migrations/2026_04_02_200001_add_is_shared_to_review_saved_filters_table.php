<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_saved_filters', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('is_default');
            $table->unsignedBigInteger('shared_by')->nullable()->after('is_shared');
            $table->foreign('shared_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('review_saved_filters', function (Blueprint $table) {
            $table->dropForeign(['shared_by']);
            $table->dropColumn(['is_shared', 'shared_by']);
        });
    }
};
