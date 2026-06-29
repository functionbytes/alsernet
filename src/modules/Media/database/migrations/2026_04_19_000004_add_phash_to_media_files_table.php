<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table): void {
            $table->string('phash', 16)->nullable()->after('file_hash')->index();
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table): void {
            $table->dropIndex(['phash']);
            $table->dropColumn('phash');
        });
    }
};
