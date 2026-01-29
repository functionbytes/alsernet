<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_websites', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('title');
            $table->text('url');
            $table->string('status');
            $table->unsignedInteger('customer_id')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');

            $table->index('customer_id');

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_websites');
    }
};
