<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('seo_meta_id')->nullable();
            $table->string('url')->nullable()->comment('Audited URL if this was a URL audit');
            $table->tinyInteger('score')->unsigned();
            $table->char('grade', 1);
            $table->unsignedSmallInteger('issues_count')->default(0);
            $table->json('issues')->nullable()->comment('Array of audit issues');
            $table->unsignedSmallInteger('passed_count')->default(0);
            $table->timestamp('audited_at');
            $table->timestamps();

            $table->foreign('seo_meta_id')->references('id')->on('seo_metas')->onDelete('cascade');

            $table->index('seo_meta_id');
            $table->index('audited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_audit_logs');
    }
};
