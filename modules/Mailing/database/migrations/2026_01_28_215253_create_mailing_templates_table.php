<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_templates', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('customer_id')->nullable();
            $table->char('name', 191);
            $table->longText('content')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('builder')->default(0);
            $table->integer('is_default')->nullable()->default(0);
            $table->char('theme', 191)->nullable();
            $table->char('type', 191);
            $table->integer('is_private')->default(0);

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_templates');
    }
};
