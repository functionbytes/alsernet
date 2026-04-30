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
        Schema::create('chat_saved_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id')->index('saved_views_user_id_foreign');
            $table->string('name');
            $table->string('view_type')->default('conversation')->comment('conversation, contact, report, etc.');
            $table->json('filters')->comment('Filter criteria: status, assignee, inbox, labels, date range, etc.');
            $table->string('sort_by')->nullable()->comment('Field to sort by');
            $table->string('sort_direction')->default('desc')->comment('asc or desc');
            $table->boolean('is_shared')->default(false)->comment('Share with team members');
            $table->boolean('is_default')->default(false)->comment('Default view for user');
            $table->integer('position')->default(0)->comment('Display order');
            $table->timestamps();

            $table->index(['account_id', 'is_shared'], 'saved_views_account_id_is_shared_index');
            $table->index(['account_id', 'user_id'], 'saved_views_account_id_user_id_index');
            $table->index(['account_id', 'view_type'], 'saved_views_account_id_view_type_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_saved_views');
    }
};
