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
        Schema::create('chat_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attribute_display_name');
            $table->string('attribute_key')->index('custom_attribute_definitions_attribute_key_index');
            $table->integer('attribute_display_type')->default(0);
            $table->integer('default_value')->nullable();
            $table->integer('attribute_model')->default(0);
            $table->unsignedBigInteger('account_id')->index('custom_attribute_definitions_account_id_index');
            $table->text('attribute_description')->nullable();
            $table->json('attribute_values')->nullable();
            $table->string('regex_pattern')->nullable();
            $table->string('regex_cue')->nullable();
            $table->timestamps();

            $table->unique(['attribute_key', 'attribute_model', 'account_id'], 'attribute_key_model_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_attributes');
    }
};
