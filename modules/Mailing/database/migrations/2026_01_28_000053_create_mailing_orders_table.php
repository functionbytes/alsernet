<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_orders', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('uid')->nullable()->unique();
            $table->integer('customer_id')->nullable();
            $table->integer('source_id')->nullable();
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('price')->nullable();
            $table->longText('meta')->nullable();
            $table->string('status')->nullable();
            $table->string('source_item_id')->nullable();
            $table->string('file')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('move')->nullable();
            $table->integer('total')->nullable();
            $table->integer('transport')->nullable();
            $table->date('delivery')->nullable();
            $table->date('receive_name')->nullable();
            $table->date('receive_address')->nullable();
            $table->date('receive_phone')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_orders');
    }
};
