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
        Schema::create('mailrelay_variables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('variable_key', 255)->unique()->comment('Placeholder key, e.g., CUSTOMER_NAME');
            $table->enum('category', ['system', 'customer', 'order', 'document', 'custom'])->default('custom')->index();
            $table->string('module', 100)->nullable()->index()->comment('Module name: core, documents, orders, customers, etc.');
            $table->text('description')->nullable();
            $table->string('example_value', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->boolean('is_system')->default(false)->comment('System variables cannot be deleted');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mailrelay_variable_langs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailrelay_variable_id')->constrained('mailrelay_variables')->cascadeOnDelete();
            $table->unsignedBigInteger('lang_id');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('example_value', 255')->nullable();
            $table->timestamps();

            $table->unique(['mailrelay_variable_id', 'lang_id'], 'mailrelay_variable_lang_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailrelay_variable_langs');
        Schema::dropIfExists('mailrelay_variables');
    }
};
