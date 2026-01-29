<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_template_categories', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('name');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_template_categories');
    }
};
