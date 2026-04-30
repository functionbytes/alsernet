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
        Schema::create('chat_macros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index('macros_account_id_index');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('visibility')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable()->index('macros_created_by_id_foreign');
            $table->unsignedBigInteger('updated_by_id')->nullable()->index('macros_updated_by_id_foreign');
            $table->json('actions');
            $table->json('conditions')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_macros');
    }
};
