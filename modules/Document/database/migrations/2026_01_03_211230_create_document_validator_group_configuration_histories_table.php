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
        Schema::create('document_validator_group_configuration_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validator_group_id');
            $table->unsignedBigInteger('configuration_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('configuration_key');
            $table->string('configuration_label');
            $table->boolean('old_value')->default(false);
            $table->boolean('new_value')->default(false);
            $table->enum('action', ['create', 'update', 'delete'])->default('update');
            $table->text('description')->nullable();
            $table->timestamps();

            // Foreign keys with explicit short constraint names
            $table->foreign('validator_group_id', 'dvgch_group_fk')
                ->references('id')->on('document_validator_groups')
                ->onDelete('cascade');
            $table->foreign('configuration_id', 'dvgch_config_fk')
                ->references('id')->on('document_validator_group_configurations')
                ->onDelete('cascade');
            $table->foreign('user_id', 'dvgch_user_fk')
                ->references('id')->on('users')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index('validator_group_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validator_group_configuration_histories');
    }
};
