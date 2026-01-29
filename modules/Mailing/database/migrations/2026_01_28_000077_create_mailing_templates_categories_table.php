<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_templates_categories', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('template_id');
            $table->unsignedInteger('category_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('category_id')->references('id')->on('mailing_template_categories')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('mailing_templates')->onDelete('cascade');

            $table->index('category_id');
            $table->index('template_id');

            // Foreign Keys
            $table->foreign('category_id')
                ->references('id')
                ->on('mailing_template_categories')
                ->onDelete('cascade');
            $table->foreign('template_id')
                ->references('id')
                ->on('mailing_templates')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_templates_categories');
    }
};
