<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_contacts', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->char('first_name', 191)->nullable();
            $table->char('last_name', 191)->nullable();
            $table->char('company', 191)->nullable();
            $table->char('address_1', 191)->nullable();
            $table->char('address_2', 191)->nullable();
            $table->foreignId('country_id')->nullable();
            $table->char('state', 191)->nullable();
            $table->char('zip', 191)->nullable();
            $table->char('phone', 191)->nullable();
            $table->char('url', 191)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->char('email', 191)->nullable();
            $table->char('city', 191)->nullable();
            $table->text('tax_number')->nullable();
            $table->text('billing_address')->nullable();

            // Foreign Keys
            $table->foreign('country_id')
                ->references('id')
                ->on('mailing_countries')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_contacts');
    }
};
