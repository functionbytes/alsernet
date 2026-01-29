<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('invoice_id');
            $table->string('item_id');
            $table->string('item_type');
            $table->double('amount', 8, 2)->default(0.00);
            $table->double('tax', 8, 2)->default(0.00);
            $table->double('discount', 8, 2)->default(0.00);
            $table->string('title');
            $table->text('description');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('invoice_id')->references('id')->on('mailing_invoices')->onDelete('cascade');

            $table->index('invoice_id');

            // Foreign Keys
            $table->foreign('invoice_id')
                ->references('id')
                ->on('mailing_invoices')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_invoice_items');
    }
};
