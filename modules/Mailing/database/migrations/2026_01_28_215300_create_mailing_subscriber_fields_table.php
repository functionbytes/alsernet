<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_subscriber_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id');
            $table->foreignId('field_id');
            $table->text('value')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign Keys
            $table->foreign('field_id')
                ->references('id')
                ->on('mailing_fields')
                ->onDelete('cascade');
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_subscriber_fields');
    }
};
