<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_customer_groups', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('name');
            $table->text('options');
            $table->unsignedInteger('admin_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('cascade');

            $table->index('admin_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_customer_groups');
    }
};
