<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_field_options', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('field_id');
            $table->string('label');
            $table->string('value');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('field_id')->references('id')->on('mailing_fields')->onDelete('cascade');

            $table->index('field_id');

            // Foreign Keys
            $table->foreign('field_id')
                ->references('id')
                ->on('mailing_fields')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_field_options');
    }
};
