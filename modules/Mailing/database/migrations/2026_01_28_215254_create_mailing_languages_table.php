<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_languages', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->char('name', 191);
            $table->char('code', 191)->nullable();
            $table->char('region_code', 191)->nullable();
            $table->char('status', 191)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('is_default')->default(0);
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_languages');
    }
};
